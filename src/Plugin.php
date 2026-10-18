<?php
/**
 * Main Plugin Class
 *
 * @package LunexiaLiveFeedback
 */

namespace LunexiaLiveFeedback;

use LunexiaLiveFeedback\DB\Migrations;
use LunexiaLiveFeedback\Rest\Feedback_Controller_v2 as Feedback_Controller;
use LunexiaLiveFeedback\Admin\Admin_Loader;
use LunexiaLiveFeedback\Frontend\Frontend_Loader;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Main Class
 */
class Plugin {
    protected static $instance = null;
    protected $migrations;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function activate() {
        try {
            $m = new Migrations();
            $m->install();

            // Ensure guest feedback is enabled by default on fresh installs.
            if ( null === get_option( 'lunexia_lf_allow_guest_feedback', null ) ) {
                update_option( 'lunexia_lf_allow_guest_feedback', true );
            }

            // Verify database integrity
            $verification = $m->verify_database();
            if ( ! $verification['valid'] ) {
                error_log( 'Lunexia Live Feedback Activation: Database verification notice: ' . wp_json_encode( $verification['issues'] ) );
            }

            self::add_caps();
            error_log( 'Lunexia Live Feedback: Plugin activated successfully' );
        } catch ( \Exception $e ) {
            error_log( 'Lunexia Live Feedback Activation Error: ' . $e->getMessage() );
            wp_die( esc_html__( 'Lunexia Live Feedback activation failed. Please check error logs.', 'lunexia-live-feedback' ) );
        }
    }

    public static function deactivate() {
        // Preserve data on deactivation
        error_log( 'Lunexia Live Feedback: Plugin deactivated' );
    }

    public static function uninstall() {
        try {
            $m = new Migrations();
            $m->uninstall();
            delete_option( 'lunexia_lf_allow_guest_feedback' );
            delete_option( 'lunexia_lf_frontend_enabled' );
            delete_option( 'lunexia_lf_notification_recipients' );
            delete_option( 'lunexia_lf_notification_recipients_enabled' );
            delete_option( 'lunexia_lf_guest_notifications_enabled' );
            error_log( 'Lunexia Live Feedback: Plugin uninstalled and data removed' );
        } catch ( \Exception $e ) {
            error_log( 'Lunexia Live Feedback Uninstall Error: ' . $e->getMessage() );
        }
    }

    public function __construct() {
        $this->migrations = new Migrations();
        
        // Ensure guest feedback option is initialized
        if ( false === get_option( 'lunexia_lf_allow_guest_feedback' ) ) {
            update_option( 'lunexia_lf_allow_guest_feedback', true );
        }
        
        // Run migrations before REST API requests
        add_action( 'rest_api_init', [ $this, 'ensure_database_ready' ], 1 );
        add_action( 'init', [ $this, 'ensure_database_ready' ] );
        add_action( 'lunexia_lf_run_migrations', [ $this, 'run_migrations' ] );

        // Ensure REST endpoints remain available even if REST is restricted globally.
        add_filter( 'rest_enabled', function( $enabled ) {
            $uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
            if ( strpos( $uri, '/wp-json/lunexia-lf/' ) !== false ) {
                return true;
            }
            return $enabled;
        }, 99 );

        add_action( 'admin_post_nopriv_lunexia_lf_submit_feedback', [ $this, 'handle_admin_post_feedback' ] );
        add_action( 'admin_post_lunexia_lf_submit_feedback', [ $this, 'handle_admin_post_feedback' ] );
        add_action( 'wp_ajax_nopriv_lunexia_lf_submit_feedback', [ $this, 'handle_admin_post_feedback' ] );
        add_action( 'wp_ajax_lunexia_lf_submit_feedback', [ $this, 'handle_admin_post_feedback' ] );

        // Perform database repair check in admin
        if ( is_admin() ) {
            add_action( 'admin_init', [ $this, 'repair_database_if_needed' ] );
        }
    }

    public function ensure_database_ready() {
        try {
            $this->migrations->migrate();
        } catch ( \Exception $e ) {
            error_log( 'Lunexia Live Feedback: Migration error - ' . $e->getMessage() );
        }
    }

    public function run_migrations() {
        try {
            $this->migrations->migrate();
            error_log( 'Lunexia Live Feedback: Migrations executed successfully' );
        } catch ( \Exception $e ) {
            error_log( 'Lunexia Live Feedback: Migration failed - ' . $e->getMessage() );
        }
    }

    public function repair_database_if_needed() {
        // Ensure the schema is fully migrated for guest tracking and comments.
        try {
            $this->migrations->migrate();
        } catch ( \Exception $e ) {
            error_log( 'Lunexia Live Feedback: Failed to run migrations during repair - ' . $e->getMessage() );
        }

        // Check if device_type column is missing
        global $wpdb;
        $table = $wpdb->prefix . 'lunexia_lf_feedback';
        
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'device_type' )
        );
        
        if ( empty( $result ) ) {
            try {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN device_type VARCHAR(32) DEFAULT 'desktop' AFTER browser_info" );
            } catch ( \Exception $e ) {
                error_log( 'Lunexia Live Feedback: Failed to add device_type column - ' . $e->getMessage() );
            }
        }
        
        // Check if updated_at column is missing
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'updated_at' )
        );
        
        if ( empty( $result ) ) {
            try {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at" );
            } catch ( \Exception $e ) {
                error_log( 'Lunexia Live Feedback: Failed to add updated_at column - ' . $e->getMessage() );
            }
        }
    }

    public function handle_admin_post_feedback() {
        $body = file_get_contents( 'php://input' );
        $raw_params = json_decode( $body, true );

        if ( ! is_array( $raw_params ) ) {
            $raw_params = ! empty( $_POST ) ? wp_unslash( $_POST ) : [];
        }

        // Recursively sanitize all input parameters
        $params = $this->sanitize_input_array( $raw_params );

        // Validate nonce or guest access
        $nonce = isset( $params['_wpnonce'] ) ? sanitize_text_field( $params['_wpnonce'] ) : '';
        if ( empty( $nonce ) && ! empty( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
        }
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) && ! wp_verify_nonce( $nonce, 'lunexia_lf_feedback_nonce' ) ) {
            if ( ! get_option( 'lunexia_lf_allow_guest_feedback', true ) ) {
                wp_send_json_error( __( 'Permission denied.', 'lunexia-live-feedback' ), 403 );
            }
        }

        $request = new \WP_REST_Request( 'POST', '/lunexia-lf/v1/feedback' );
        $request->set_body_params( $params );
        $request->set_json_params( $params );

        $controller = new Feedback_Controller();
        $response = $controller->create_feedback( $request );

        if ( is_wp_error( $response ) ) {
            $status = $response->get_error_data()['status'] ?? 500;
            wp_send_json_error( $response->get_error_message(), $status );
        }

        $response = rest_ensure_response( $response );
        rest_send_response( $response );
    }

    protected function sanitize_input_array( $input ) {
        if ( ! is_array( $input ) ) {
            return is_string( $input ) ? sanitize_text_field( $input ) : $input;
        }
        $sanitized = [];
        foreach ( $input as $key => $value ) {
            $sanitized_key = sanitize_key( $key );
            if ( is_array( $value ) ) {
                $sanitized[ $sanitized_key ] = $this->sanitize_input_array( $value );
            } elseif ( is_string( $value ) ) {
                $sanitized[ $sanitized_key ] = sanitize_text_field( $value );
            } else {
                $sanitized[ $sanitized_key ] = $value;
            }
        }
        return $sanitized;
    }

    public function run() {
        // Ensure schema migrations are applied before any request handling.
        try {
            $this->migrations->migrate();
        } catch ( \Exception $e ) {
            error_log( 'Lunexia Live Feedback: Failed to run migrations during plugin init - ' . $e->getMessage() );
        }

        // Ensure guest feedback setting exists for older installs.
        if ( false === get_option( 'lunexia_lf_allow_guest_feedback' ) ) {
            add_option( 'lunexia_lf_allow_guest_feedback', true );
        }

        // Initialize early for REST API
        add_action( 'rest_api_init', [ $this, 'register_routes' ], 10 );

        // Admin initialization
        if ( is_admin() ) {
            $admin = new Admin_Loader();
            $admin->init();
        }

        // Frontend initialization
        $frontend = new Frontend_Loader();
        $frontend->init();

        // Register activity logging for feedback changes
        add_action( 'lunexia_lf_feedback_created', [ $this, 'log_activity' ], 10, 2 );
        add_action( 'lunexia_lf_feedback_updated', [ $this, 'log_activity' ], 10, 2 );
    }

    public function register_routes() {
        $controller = new Feedback_Controller();
        $controller->register_routes();
    }

    public function log_activity( $feedback_id, $action_type ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lunexia_lf_activity';
        $wpdb->insert(
            $table,
            [
                'feedback_id' => $feedback_id,
                'actor_id'    => get_current_user_id() ?: null,
                'action'      => $action_type,
                'details'     => wp_json_encode( [ 'timestamp' => current_time( 'mysql' ) ] ),
            ],
            [ '%d', '%d', '%s', '%s' ]
        );
    }

    protected static function add_caps() {
        $roles = [
            'administrator' => [
                'lunexia_lf_manage'  => true,
                'lunexia_lf_create'  => true,
                'lunexia_lf_resolve' => true,
                'lunexia_lf_upload'  => true,
                'lunexia_lf_assign'  => true,
            ],
            'editor' => [
                'lunexia_lf_create'  => true,
                'lunexia_lf_resolve' => true,
                'lunexia_lf_upload'  => true,
            ],
            'author' => [
                'lunexia_lf_create' => true,
            ],
        ];

        foreach ( $roles as $role => $caps ) {
            $r = get_role( $role );
            if ( ! $r ) {
                continue;
            }
            foreach ( $caps as $cap => $grant ) {
                $r->add_cap( $cap, $grant );
            }
        }
    }

    /**
     * Get migration status for diagnostics
     */
    public static function get_migration_status() {
        $m = new Migrations();
        return [
            'migrations'   => $m->get_executed_migrations(),
            'verification' => $m->verify_database(),
        ];
    }
}
