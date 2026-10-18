<?php
/**
 * Admin Loader
 *
 * @package LunexiaLiveFeedback
 */

namespace LunexiaLiveFeedback\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Loader Class
 */
class Admin_Loader {
    public function init() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Lunexia Live Feedback', 'lunexia-live-feedback' ),
            __( 'Live Feedback', 'lunexia-live-feedback' ),
            'lunexia_lf_manage',
            'lunexia-lf-feedback',
            [ $this, 'render_admin_page' ],
            'dashicons-format-chat',
            26
        );
    }

    public function render_admin_page() {
        echo '<div id="lunexia-lf-admin-app"><div id="lvf-admin-app"><div class="lvf-loading">' . esc_html__( 'Loading Lunexia Live Feedback admin...', 'lunexia-live-feedback' ) . '</div></div></div>';
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'lunexia-lf-feedback' ) === false ) {
            return;
        }

        // Get plugin root directory URL
        $plugin_dir = plugin_dir_url( dirname( dirname( dirname( __FILE__ ) ) ) . '/lunexia-live-feedback.php' );
        $asset_dir = $plugin_dir . 'assets/';

        // Check for Vite-built app
        $vite_manifest = LUNEXIA_LF_DIR . 'assets/dist/manifest.json';
        if ( ! file_exists( $vite_manifest ) ) {
            $vite_manifest = LUNEXIA_LF_DIR . 'assets/dist/.vite/manifest.json';
        }

        if ( file_exists( $vite_manifest ) ) {
            // Production/Vite build mode
            $manifest = json_decode( file_get_contents( $vite_manifest ), true );

            if ( isset( $manifest['src/admin/main.jsx'] ) ) {
                $entry = $manifest['src/admin/main.jsx'];

                // Enqueue CSS files
                if ( ! empty( $entry['css'] ) ) {
                    foreach ( $entry['css'] as $css_file ) {
                        wp_enqueue_style(
                            'lunexia-lf-admin-' . sanitize_key( $css_file ),
                            $asset_dir . 'dist/' . $css_file,
                            [],
                            LUNEXIA_LF_VERSION
                        );
                    }
                }

                // Enqueue JS file
                if ( ! empty( $entry['file'] ) ) {
                    wp_enqueue_script(
                        'lunexia-lf-admin-app',
                        $asset_dir . 'dist/' . $entry['file'],
                        [ 'wp-element', 'wp-api-fetch' ],
                        LUNEXIA_LF_VERSION,
                        true
                    );
                }

                // Enqueue imports
                if ( ! empty( $entry['imports'] ) ) {
                    foreach ( $entry['imports'] as $import_key ) {
                        if ( isset( $manifest[ $import_key ] ) && ! empty( $manifest[ $import_key ]['file'] ) ) {
                            wp_enqueue_script(
                                'lunexia-lf-admin-' . sanitize_key( $import_key ),
                                $asset_dir . 'dist/' . $manifest[ $import_key ]['file'],
                                [ 'lunexia-lf-admin-app' ],
                                LUNEXIA_LF_VERSION,
                                true
                            );
                        }
                    }
                }
            }
        } else {
            // Development/placeholder mode
            wp_enqueue_script(
                'lunexia-lf-admin-app',
                $asset_dir . 'js/admin-app.js',
                [ 'wp-element' ],
                LUNEXIA_LF_VERSION,
                true
            );
        }

        // Always enqueue styles
        wp_enqueue_style(
            'lunexia-lf-admin',
            $asset_dir . 'css/admin.css',
            [],
            LUNEXIA_LF_VERSION
        );

        // Pass data to app
        $admin_data = [
            'rest_url' => esc_url_raw( rest_url( 'lunexia-lf/v1/' ) ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'current_user' => [
                'id' => get_current_user_id(),
                'name' => wp_get_current_user()->display_name,
                'email' => wp_get_current_user()->user_email,
            ],
            'capabilities' => [
                'manage' => current_user_can( 'lunexia_lf_manage' ) || current_user_can( 'manage_options' ),
                'create' => current_user_can( 'lunexia_lf_create' ) || current_user_can( 'edit_posts' ),
                'resolve' => current_user_can( 'lunexia_lf_resolve' ) || current_user_can( 'manage_options' ),
                'assign' => current_user_can( 'lunexia_lf_assign' ) || current_user_can( 'manage_options' ),
                'upload' => current_user_can( 'lunexia_lf_upload' ) || current_user_can( 'upload_files' ),
            ],
            'settings' => [
                'frontend_enabled' => (bool) get_option( 'lunexia_lf_frontend_enabled', true ),
                'allow_guest_feedback' => (bool) get_option( 'lunexia_lf_allow_guest_feedback', true ),
                'notification_recipients' => get_option( 'lunexia_lf_notification_recipients', [] ),
                'notification_recipients_enabled' => (bool) get_option( 'lunexia_lf_notification_recipients_enabled', true ),
                'guest_notifications_enabled' => (bool) get_option( 'lunexia_lf_guest_notifications_enabled', true ),
            ],
            'plugin_version' => LUNEXIA_LF_VERSION,
        ];

        wp_localize_script( 'lunexia-lf-admin-app', 'lunexia_lf_admin', $admin_data );
        wp_add_inline_script( 'lunexia-lf-admin-app', 'window.lvf_admin = window.lunexia_lf_admin;', 'before' );
    }
}
