<?php
/**
 * REST API Controller for Feedback
 *
 * @package LunexiaLiveFeedback
 */

namespace LunexiaLiveFeedback\Rest;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Feedback REST Controller
 */
class Feedback_Controller_v2 extends WP_REST_Controller {
    protected $namespace = 'lunexia-lf/v1';
    protected $rest_base = 'feedback';
    protected $max_request_size = 5242880; // 5MB
    protected $allowed_image_types = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];

    public function register_routes() {
        foreach ( [ $this->namespace, 'lv' . 'f/v1' ] as $ns ) {
            $this->register_routes_for_namespace( $ns );
        }
    }

    protected function register_routes_for_namespace( $ns ) {
        // GET /feedback - List feedback
        register_rest_route(
            $ns,
            '/' . $this->rest_base,
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_feedback' ],
                'permission_callback' => [ $this, 'check_list_permissions' ],
            ]
        );

        // POST /feedback - Create feedback
        register_rest_route(
            $ns,
            '/' . $this->rest_base,
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'create_feedback' ],
                'permission_callback' => [ $this, 'check_create_feedback_permissions' ],
            ]
        );

        // GET /feedback/:id - Get single feedback
        register_rest_route(
            $ns,
            '/' . $this->rest_base . '/(?P<id>\d+)',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_single_feedback' ],
                'permission_callback' => [ $this, 'check_read_permissions' ],
            ]
        );

        // PATCH /feedback/:id - Update feedback
        register_rest_route(
            $ns,
            '/' . $this->rest_base . '/(?P<id>\d+)',
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'update_feedback' ],
                'permission_callback' => [ $this, 'check_manage_permissions' ],
            ]
        );

        // DELETE /feedback/:id - Delete feedback
        register_rest_route(
            $ns,
            '/' . $this->rest_base . '/(?P<id>\d+)',
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'delete_feedback' ],
                'permission_callback' => [ $this, 'check_manage_permissions' ],
            ]
        );

        // POST /feedback/:id/assign - Assign feedback
        register_rest_route(
            $ns,
            '/' . $this->rest_base . '/(?P<id>\d+)/assign',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'assign_feedback' ],
                'permission_callback' => [ $this, 'check_assign_permissions' ],
            ]
        );

        // POST /feedback/:id/resolve - Resolve feedback
        register_rest_route(
            $ns,
            '/' . $this->rest_base . '/(?P<id>\d+)/resolve',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'resolve_feedback' ],
                'permission_callback' => [ $this, 'check_resolve_permissions' ],
            ]
        );

        // POST /comments - Create comment
        register_rest_route(
            $ns,
            '/comments',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'create_comment' ],
                'permission_callback' => [ $this, 'check_create_comment_permissions' ],
            ]
        );

        // GET /comments/:id - Get comment thread
        register_rest_route(
            $ns,
            '/comments/(?P<id>\d+)',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_comments' ],
                'permission_callback' => [ $this, 'check_read_permissions' ],
            ]
        );

        // DELETE /comments/:id - Delete comment
        register_rest_route(
            $ns,
            '/comments/(?P<id>\d+)',
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'delete_comment' ],
                'permission_callback' => [ $this, 'check_manage_permissions' ],
            ]
        );

        // GET /settings - Read plugin settings
        register_rest_route(
            $ns,
            '/settings',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'check_manage_permissions' ],
            ]
        );

        // POST /settings - Update plugin settings
        register_rest_route(
            $ns,
            '/settings',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'update_settings' ],
                'permission_callback' => [ $this, 'check_manage_permissions' ],
            ]
        );

        // GET /debug - Debug information (restricted to administrators)
        register_rest_route(
            $ns,
            '/debug',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_debug_info' ],
                'permission_callback' => [ $this, 'check_manage_permissions' ],
            ]
        );

        // POST /attachments - Upload attachment
        register_rest_route(
            $ns,
            '/attachments',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'create_attachment' ],
                'permission_callback' => [ $this, 'check_upload_permissions' ],
            ]
        );
    }

    /**
     * Permission callbacks
     */
    public function check_list_permissions( $request = null ) {
        if ( current_user_can( 'lunexia_lf_manage' ) || current_user_can( 'manage_options' ) ) {
            return true;
        }
        if ( is_user_logged_in() && ( current_user_can( 'lunexia_lf_create' ) || current_user_can( 'edit_posts' ) ) ) {
            return true;
        }
        if ( $this->allow_guest_feedback() && $request instanceof WP_REST_Request && ! empty( $request->get_param( 'guest_session' ) ) ) {
            return true;
        }
        return false;
    }

    public function check_read_permissions( $request = null ) {
        if ( current_user_can( 'lunexia_lf_manage' ) || current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( $request instanceof WP_REST_Request ) {
            $feedback_id = intval( $request->get_param( 'id' ) );
            if ( $feedback_id > 0 ) {
                global $wpdb;
                $feedback_table = $this->get_table_name( 'feedback' );
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $feedback = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT author_id, guest_session FROM {$feedback_table} WHERE id = %d",
                        $feedback_id
                    )
                );

                if ( ! $feedback ) {
                    return new \WP_Error(
                        'rest_feedback_not_found',
                        __( 'Feedback not found.', 'lunexia-live-feedback' ),
                        [ 'status' => 404 ]
                    );
                }

                if ( is_user_logged_in() ) {
                    if ( ! current_user_can( 'lunexia_lf_create' ) && ! current_user_can( 'edit_posts' ) ) {
                        return new \WP_Error(
                            'rest_forbidden',
                            __( 'You do not have permission to view this feedback.', 'lunexia-live-feedback' ),
                            [ 'status' => 403 ]
                        );
                    }

                    $current_user_id = get_current_user_id();
                    $is_author = intval( $feedback->author_id ) === intval( $current_user_id );

                    $assignments_table = $this->get_table_name( 'assignments' );
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $is_assigned = (bool) $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$assignments_table} WHERE feedback_id = %d AND user_id = %d",
                            $feedback_id,
                            $current_user_id
                        )
                    );

                    if ( $is_author || $is_assigned ) {
                        return true;
                    }

                    return new \WP_Error(
                        'rest_forbidden',
                        __( 'You do not have permission to view this feedback.', 'lunexia-live-feedback' ),
                        [ 'status' => 403 ]
                    );
                }

                if ( ! $this->allow_guest_feedback() ) {
                    return new \WP_Error(
                        'rest_forbidden',
                        __( 'Guest feedback is disabled.', 'lunexia-live-feedback' ),
                        [ 'status' => 403 ]
                    );
                }

                $guest_session = $request->get_param( 'guest_session' );
                if ( empty( $guest_session ) || empty( $feedback->guest_session ) || ! hash_equals( (string) $feedback->guest_session, (string) $guest_session ) ) {
                    return new \WP_Error(
                        'rest_forbidden',
                        __( 'You do not have permission to view this feedback.', 'lunexia-live-feedback' ),
                        [ 'status' => 403 ]
                    );
                }

                return true;
            }
        }

        if ( is_user_logged_in() && ( current_user_can( 'lunexia_lf_create' ) || current_user_can( 'edit_posts' ) ) ) {
            return true;
        }

        if ( $this->allow_guest_feedback() && $request instanceof WP_REST_Request && ! empty( $request->get_param( 'guest_session' ) ) ) {
            return true;
        }

        return false;
    }

    /**
     * Permission callback for creating a comment
     *
     * Ensures only authorized users or guests verifying ownership
     * through the feedback guest session can create comments.
     *
     * @param WP_REST_Request|null $request REST request.
     * @return bool|\WP_Error True if authorized, WP_Error otherwise.
     */
    public function check_create_comment_permissions( $request = null ) {
        if ( $this->can_manage_feedback() ) {
            return true;
        }

        if ( ! ( $request instanceof WP_REST_Request ) ) {
            return new \WP_Error(
                'rest_invalid_request',
                __( 'Invalid request.', 'lunexia-live-feedback' ),
                [ 'status' => 400 ]
            );
        }

        $params = $request->get_json_params();
        $feedback_id = intval( $request->get_param( 'feedback_id' ) ?: ( $params['feedback_id'] ?? 0 ) );
        if ( $feedback_id <= 0 ) {
            return new \WP_Error(
                'rest_invalid_feedback',
                __( 'A valid feedback ID is required.', 'lunexia-live-feedback' ),
                [ 'status' => 400 ]
            );
        }

        global $wpdb;
        $feedback_table = $this->get_table_name( 'feedback' );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $feedback = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT author_id, guest_session FROM {$feedback_table} WHERE id = %d",
                $feedback_id
            )
        );

        if ( ! $feedback ) {
            return new \WP_Error(
                'rest_feedback_not_found',
                __( 'Feedback not found.', 'lunexia-live-feedback' ),
                [ 'status' => 404 ]
            );
        }

        if ( is_user_logged_in() ) {
            if ( ! current_user_can( 'lunexia_lf_create' ) && ! current_user_can( 'edit_posts' ) ) {
                return new \WP_Error(
                    'rest_forbidden',
                    __( 'You do not have permission to comment on this feedback.', 'lunexia-live-feedback' ),
                    [ 'status' => 403 ]
                );
            }

            $current_user_id = get_current_user_id();
            $is_author = intval( $feedback->author_id ) === intval( $current_user_id );

            $assignments_table = $this->get_table_name( 'assignments' );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $is_assigned = (bool) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$assignments_table} WHERE feedback_id = %d AND user_id = %d",
                    $feedback_id,
                    $current_user_id
                )
            );

            if ( $is_author || $is_assigned ) {
                return true;
            }

            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to comment on this feedback.', 'lunexia-live-feedback' ),
                [ 'status' => 403 ]
            );
        }

        // Guest comment validation: verify ownership through the feedback guest session
        if ( ! $this->allow_guest_feedback() ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'Guest feedback is disabled.', 'lunexia-live-feedback' ),
                [ 'status' => 403 ]
            );
        }

        $guest_session = $request->get_param( 'guest_session' ) ?: ( $params['guest_session'] ?? null );
        if ( ! empty( $guest_session ) ) {
            $guest_session = sanitize_text_field( $guest_session );
        }

        if ( empty( $guest_session ) || empty( $feedback->guest_session ) ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to comment on this feedback without verifying ownership through the guest session.', 'lunexia-live-feedback' ),
                [ 'status' => 403 ]
            );
        }

        if ( ! hash_equals( (string) $feedback->guest_session, (string) $guest_session ) ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to comment on this feedback.', 'lunexia-live-feedback' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Permission callback for creating new feedback
     *
     * @param WP_REST_Request|null $request REST request.
     * @return bool True if authorized, false otherwise.
     */
    public function check_create_feedback_permissions( $request = null ) {
        if ( current_user_can( 'lunexia_lf_manage' ) || current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( ! get_option( 'lunexia_lf_frontend_enabled', true ) ) {
            return false;
        }

        if ( is_user_logged_in() && ( current_user_can( 'lunexia_lf_create' ) || current_user_can( 'edit_posts' ) ) ) {
            return true;
        }

        return $this->allow_guest_feedback();
    }

    /**
     * General/legacy create permission callback
     *
     * @param WP_REST_Request|null $request REST request.
     * @return bool|\WP_Error
     */
    public function check_create_permissions( $request = null ) {
        if ( $request instanceof WP_REST_Request && ! empty( $request->get_param( 'feedback_id' ) ) ) {
            return $this->check_create_comment_permissions( $request );
        }
        return $this->check_create_feedback_permissions( $request );
    }

    protected function allow_guest_feedback() {
        return (bool) get_option( 'lunexia_lf_allow_guest_feedback', true );
    }

    public function can_manage_feedback() {
        return current_user_can( 'lunexia_lf_manage' ) || current_user_can( 'manage_options' );
    }

    public function get_table_name( $name = 'feedback' ) {
        global $wpdb;
        return $wpdb->prefix . 'lunexia_lf_' . $name;
    }

    public function get_settings( $request ) {
        return [
            'frontend_enabled' => (bool) get_option( 'lunexia_lf_frontend_enabled', true ),
            'allow_guest_feedback' => (bool) get_option( 'lunexia_lf_allow_guest_feedback', true ),
            'notification_recipients' => $this->get_notification_recipients(),
            'notification_recipients_enabled' => (bool) get_option( 'lunexia_lf_notification_recipients_enabled', true ),
            'guest_notifications_enabled' => (bool) get_option( 'lunexia_lf_guest_notifications_enabled', true ),
        ];
    }

    public function get_debug_info( $request ) {
        return [
            'plugin_enabled' => (bool) get_option( 'lunexia_lf_frontend_enabled', true ),
            'guest_feedback_enabled' => (bool) get_option( 'lunexia_lf_allow_guest_feedback', true ),
            'rest_url' => esc_url_raw( rest_url( 'lunexia-lf/v1/' ) ),
            'user_id' => get_current_user_id(),
            'user_logged_in' => is_user_logged_in(),
            'user_can_create' => current_user_can( 'lunexia_lf_create' ) || current_user_can( 'edit_posts' ),
            'user_can_manage' => current_user_can( 'lunexia_lf_manage' ) || current_user_can( 'manage_options' ),
            'wordpress_version' => get_bloginfo( 'version' ),
            'rest_enabled' => rest_is_enabled(),
            'php_version' => phpversion(),
            'notification_recipients' => $this->get_notification_recipients(),
        ];
    }

    public function update_settings( $request ) {
        $params = $request->get_json_params();

        $frontend_enabled = isset( $params['frontend_enabled'] ) ? (bool) $params['frontend_enabled'] : true;
        $allow_guest_feedback = isset( $params['allow_guest_feedback'] ) ? (bool) $params['allow_guest_feedback'] : false;
        $notification_recipients = isset( $params['notification_recipients'] ) ? $this->parse_notification_recipients( $params['notification_recipients'] ) : [];
        $notification_recipients_enabled = isset( $params['notification_recipients_enabled'] ) ? (bool) $params['notification_recipients_enabled'] : (bool) get_option( 'lunexia_lf_notification_recipients_enabled', true );
        $guest_notifications_enabled = isset( $params['guest_notifications_enabled'] ) ? (bool) $params['guest_notifications_enabled'] : (bool) get_option( 'lunexia_lf_guest_notifications_enabled', true );

        update_option( 'lunexia_lf_frontend_enabled', $frontend_enabled );
        update_option( 'lunexia_lf_allow_guest_feedback', $allow_guest_feedback );
        update_option( 'lunexia_lf_notification_recipients', $notification_recipients );
        update_option( 'lunexia_lf_notification_recipients_enabled', $notification_recipients_enabled );
        update_option( 'lunexia_lf_guest_notifications_enabled', $guest_notifications_enabled );

        return [
            'success' => true,
            'settings' => [
                'frontend_enabled' => $frontend_enabled,
                'allow_guest_feedback' => $allow_guest_feedback,
                'notification_recipients' => $notification_recipients,
                'notification_recipients_enabled' => $notification_recipients_enabled,
                'guest_notifications_enabled' => $guest_notifications_enabled,
            ],
        ];
    }

    protected function parse_notification_recipients( $recipients ) {
        if ( is_string( $recipients ) ) {
            $recipients = preg_split( '/[\r\n,;]+/', $recipients );
        }

        if ( ! is_array( $recipients ) ) {
            return [];
        }

        $emails = [];
        foreach ( $recipients as $email ) {
            $email = trim( $email );
            if ( empty( $email ) ) {
                continue;
            }
            $email = sanitize_email( $email );
            if ( is_email( $email ) ) {
                $emails[] = $email;
            }
        }

        return array_values( array_unique( $emails ) );
    }

    protected function get_notification_recipients() {
        $recipients = get_option( 'lunexia_lf_notification_recipients', [] );
        if ( is_string( $recipients ) ) {
            $recipients = $this->parse_notification_recipients( $recipients );
        }

        if ( ! is_array( $recipients ) ) {
            return [];
        }

        return array_values( array_unique( $recipients ) );
    }

    protected function is_admin_notifications_enabled() {
        return (bool) get_option( 'lunexia_lf_notification_recipients_enabled', true );
    }

    protected function is_guest_notifications_enabled() {
        return (bool) get_option( 'lunexia_lf_guest_notifications_enabled', true );
    }

    protected function send_comment_notification( $comment ) {
        $recipients = $this->get_notification_recipients();
        $admin_enabled = $this->is_admin_notifications_enabled();
        $guest_enabled = $this->is_guest_notifications_enabled();

        if ( ! $admin_enabled && ! $guest_enabled ) {
            return;
        }

        global $wpdb;
        $feedback_table = $this->get_table_name( 'feedback' );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $feedback = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, page_url, status, priority, guest_email FROM {$feedback_table} WHERE id = %d",
                $comment->feedback_id
            )
        );

        if ( ! $feedback ) {
            return;
        }

        $author_name = ! empty( $comment->author_name ) ? $comment->author_name : 'Anonymous';
        if ( ! empty( $comment->guest_name ) ) {
            $author_name = $comment->guest_name . ' (Guest)';
        }

        $subject = sprintf( 'New comment on feedback #%d', $feedback->id );
        $html = $this->build_transactional_email_html(
            sprintf( 'New comment on feedback #%d', $feedback->id ),
            'A new comment was added to your feedback thread.',
            [
                'Feedback ID' => $feedback->id,
                'Comment author' => $author_name,
                'Comment' => $comment->content,
                'Page' => $feedback->page_url ?: 'Unknown',
                'Status' => ucfirst( $feedback->status ),
                'Priority' => ucfirst( $feedback->priority ),
            ],
            'A new comment has been posted on this feedback item.',
            [
                'Review the comment in the admin panel.',
                'Respond if follow-up is needed.',
                'Track feedback progress until resolution.',
            ],
            'https://lunexiait.com'
        );

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        if ( $admin_enabled && ! empty( $recipients ) ) {
            $sent = wp_mail( $recipients, $subject, $html, $headers );
            if ( ! $sent ) {
                error_log( 'Lunexia Live Feedback: wp_mail to admins failed. Recipients: ' . wp_json_encode( $recipients ) );
            } else {
                error_log( 'Lunexia Live Feedback: wp_mail to admins succeeded. Recipients: ' . wp_json_encode( $recipients ) );
            }
        } else {
            error_log( 'Lunexia Live Feedback: Admin notifications disabled or no recipients configured.' );
        }

        if ( $guest_enabled && ! empty( $feedback->guest_email ) && empty( $comment->guest_session ) ) {
            $guest_subject = sprintf( 'New comment on your feedback #%d', $feedback->id );
            $guest_html = $this->build_transactional_email_html(
                sprintf( 'New comment on feedback #%d', $feedback->id ),
                'Our team has replied to your feedback.',
                [
                    'Feedback ID' => $feedback->id,
                    'Comment' => $comment->content,
                    'Page' => $feedback->page_url ?: 'Unknown',
                    'Status' => ucfirst( $feedback->status ),
                ],
                'A member of our team has added a comment to your feedback.',
                [
                    'Check the response to your feedback.',
                    'Stay updated on progress.',
                    'Reply if you need further assistance.',
                ],
                'https://lunexiait.com'
            );

            $sent_guest = wp_mail( [ $feedback->guest_email ], $guest_subject, $guest_html, $headers );
            if ( ! $sent_guest ) {
                error_log( 'Lunexia Live Feedback: wp_mail to guest failed. Email: ' . $feedback->guest_email );
            } else {
                error_log( 'Lunexia Live Feedback: wp_mail to guest succeeded. Email: ' . $feedback->guest_email );
            }
        } else {
            error_log( 'Lunexia Live Feedback: Guest notification skipped; guest_enabled=' . (int) $guest_enabled . ' guest_email=' . (string) $feedback->guest_email );
        }
    }

    protected function send_feedback_notification( $feedback ) {
        $recipients = $this->get_notification_recipients();
        $admin_enabled = $this->is_admin_notifications_enabled();
        $guest_enabled = $this->is_guest_notifications_enabled();

        $subject = sprintf( 'New feedback submitted #%d', $feedback->id );
        $html = $this->build_transactional_email_html(
            sprintf( 'Feedback received: #%d', $feedback->id ),
            'A new feedback item was submitted.',
            [
                'Feedback ID' => $feedback->id,
                'Page' => $feedback->page_url ?: 'Unknown',
                'Status' => ucfirst( $feedback->status ),
                'Priority' => ucfirst( $feedback->priority ),
                'Guest name' => $feedback->guest_name ?: 'N/A',
                'Guest email' => $feedback->guest_email ?: 'N/A',
            ],
            'A new feedback item is waiting for review in your dashboard.',
            [
                'View it in the admin panel.',
                'Assign or resolve as needed.',
                'Follow up with the submitter if necessary.',
            ],
            'https://lunexiait.com'
        );

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        if ( $admin_enabled && ! empty( $recipients ) ) {
            $sent = wp_mail( $recipients, $subject, $html, $headers );
            if ( ! $sent ) {
                error_log( 'Lunexia Live Feedback: wp_mail feedback notification to admins failed. Recipients: ' . wp_json_encode( $recipients ) );
            } else {
                error_log( 'Lunexia Live Feedback: wp_mail feedback notification to admins succeeded. Recipients: ' . wp_json_encode( $recipients ) );
            }
        } else {
            error_log( 'Lunexia Live Feedback: Feedback admin notifications disabled or no recipients configured.' );
        }

        error_log( 'Lunexia Live Feedback: Guest feedback notification disabled on submission; only resolved notifications are sent.' );
    }

    protected function send_guest_resolved_notification( $feedback ) {
        if ( empty( $feedback->guest_email ) || ! $this->is_guest_notifications_enabled() ) {
            return;
        }

        $subject = sprintf( 'Your feedback #%d has been resolved', $feedback->id );
        $html = $this->build_transactional_email_html(
            sprintf( 'Feedback #%d resolved', $feedback->id ),
            'Your feedback has been marked as resolved.',
            [
                'Feedback ID' => $feedback->id,
                'Page' => $feedback->page_url ?: 'Unknown',
                'Status' => 'Resolved',
            ],
            'This feedback item has been completed. No action is required unless you want to follow up.',
            [
                'Review the resolution details.',
                'Reach out if you need further assistance.',
                'Keep sharing feedback to help us improve.',
            ],
            'https://lunexiait.com'
        );

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        $sent = wp_mail( [ $feedback->guest_email ], $subject, $html, $headers );
        if ( ! $sent ) {
            error_log( 'Lunexia Live Feedback: wp_mail resolved notification failed for guest: ' . $feedback->guest_email );
        } else {
            error_log( 'Lunexia Live Feedback: wp_mail resolved notification succeeded for guest: ' . $feedback->guest_email );
        }
    }

    protected function build_transactional_email_html( $title, $subtitle, $details = [], $alert_text = '', $actions = [], $footer_url = '' ) {
        $title = esc_html( $title );
        $subtitle = esc_html( $subtitle );
        $footer_url = esc_url( $footer_url );
        $details_html = '';

        foreach ( $details as $label => $value ) {
            $details_html .= sprintf(
                '<tr><td style="padding: 8px 0; font-weight: 600; color: #475569; width: 40%%; vertical-align: top;">%s</td><td style="padding: 8px 0; color: #0f172a; width: 60%%; vertical-align: top;">%s</td></tr>',
                esc_html( $label ),
                nl2br( esc_html( $value ) )
            );
        }

        $actions_html = '';
        foreach ( $actions as $action ) {
            $actions_html .= sprintf(
                '<li style="margin-bottom: 10px; color: #334155; font-size: 14px; line-height: 1.6;">%s</li>',
                esc_html( $action )
            );
        }

        $logo_url = LUNEXIA_LF_URL . 'assets/img/lunexiait.webp';

        return '<!doctype html>' .
            '<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">' .
            '</head><body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;">' .
            '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f1f5f9;padding:32px 16px;min-height:100vh;border-collapse:collapse;width:100%;">' .
            '<tr><td align="center">' .
            '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 20px 40px rgba(15,23,42,0.08);border-collapse:collapse;">' .
            '<tr><td style="background:#0f172a;padding:32px 28px;text-align:center;color:#ffffff;">' .
            '<img src="' . esc_url( $logo_url ) . '" alt="Lunexia" style="height:35px;margin-bottom:24px;display:inline-block;border-radius:16px;" />' .
            '<h1 style="margin:18px 0 8px;font-size:24px;line-height:1.25;color:#ffffff;">' . $title . '</h1>' .
            '<p style="margin:0;font-size:15px;line-height:1.75;color:#cbd5e1;">' . $subtitle . '</p>' .
            '</td></tr>' .
            '<tr><td style="padding:24px 28px;background:#f8fafc;">' .
            '<div style="background:#f8fafc;border-radius:14px;padding:20px;">' .
            '<p style="margin:0 0 12px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#64748b;">Event details</p>' .
            '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;width:100%;">' . $details_html . '</table>' .
            '</div>' .
            '</td></tr>' .
            '<tr><td style="padding:0 28px 24px;">' .
            '<div style="background:#fef3c7;border:1px solid #f5d08a;border-radius:14px;padding:18px;">' .
            '<p style="margin:0;font-size:14px;font-weight:700;color:#92400e;text-align:center;">' . esc_html( $alert_text ) . '</p>' .
            '</div>' .
            '</td></tr>' .
            '<tr><td style="padding:0 28px 28px;">' .
            '<div style="background:#f8fafc;border-radius:14px;padding:20px;">' .
            '<p style="margin:0 0 12px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#64748b;">Recommended actions</p>' .
            '<ul style="margin:0;padding-left:20px;color:#334155;font-size:14px;line-height:1.8;">' . $actions_html . '</ul>' .
            '</div>' .
            '</td></tr>' .
            '<tr><td style="padding:0 28px 32px;text-align:center;color:#64748b;font-size:12px;line-height:1.6;">' .
            '<p style="margin:0;">This message was generated automatically by Lunexia Security.</p>' .
            '<p style="margin:8px 0 0;">Developed by Lunexia IT</p>' .
            '<p style="margin:8px 0 0;"><a href="' . $footer_url . '" style="color:#1d4ed8;text-decoration:none;">' . $footer_url . '</a></p>' .
            '</td></tr>' .
            '</table>' .
            '</td></tr>' .
            '</table>' .
            '</body></html>';
    }

    public function check_manage_permissions() {
        if ( ! current_user_can( 'lunexia_lf_manage' ) && ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        return $this->verify_nonce();
    }

    public function check_assign_permissions() {
        if ( ! current_user_can( 'lunexia_lf_assign' ) && ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        return $this->verify_nonce();
    }

    public function check_resolve_permissions() {
        if ( ! current_user_can( 'lunexia_lf_resolve' ) && ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        return $this->verify_nonce();
    }

    public function check_upload_permissions() {
        if ( ! current_user_can( 'lunexia_lf_upload' ) && ! current_user_can( 'upload_files' ) ) {
            return false;
        }
        return $this->verify_nonce();
    }

    /**
     * Verify nonce from request headers
     */
    protected function verify_nonce() {
        $nonce = $this->get_nonce_from_request();
        if ( ! $nonce ) {
            error_log( 'Lunexia Live Feedback: No nonce provided in request' );
            return true; // Allow requests without nonce for frontend submissions
        }
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            error_log( 'Lunexia Live Feedback: Invalid nonce - ' . sanitize_text_field( $nonce ) );
            return new \WP_Error( 'invalid_nonce', 'Invalid nonce', [ 'status' => 403 ] );
        }
        return true;
    }

    /**
     * Extract nonce from request headers
     */
    protected function get_nonce_from_request() {
        // Try native WordPress header first
        if ( ! empty( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
        }

        // Fallback for custom headers
        $headers = function_exists( 'getallheaders' ) ? getallheaders() : [];
        if ( ! empty( $headers['X-WP-Nonce'] ) ) {
            return sanitize_text_field( $headers['X-WP-Nonce'] );
        }

        return null;
    }

    /**
     * GET /feedback - List all feedback with filters, search, pagination
     */
    public function get_feedback( WP_REST_Request $request ) {
        global $wpdb;

        // Pagination
        $page = intval( $request->get_param( 'page' ) ?? 1 );
        $per_page = intval( $request->get_param( 'per_page' ) ?? 20 );
        $per_page = min( max( $per_page, 1 ), 100 ); // Limit 1-100

        // Filters
        $page_url = $request->get_param( 'page_url' );
        $status = $request->get_param( 'status' );
        $priority = $request->get_param( 'priority' );
        $search = $request->get_param( 'search' );
        $author_id = $request->get_param( 'author_id' );
        $device_type = $request->get_param( 'device_type' );
        $guest_session = $request->get_param( 'guest_session' );

        $table = $this->get_table_name( 'feedback' );
        $where = [];
        $params = [];

        // Build query - enforce guest isolation and ownership
        if ( ! $this->can_manage_feedback() ) {
            if ( ! empty( $guest_session ) ) {
                $where[] = 'guest_session = %s';
                $params[] = sanitize_text_field( $guest_session );
            } elseif ( is_user_logged_in() ) {
                $where[] = 'author_id = %d';
                $params[] = get_current_user_id();
            } else {
                return new \WP_Error( 'forbidden', 'You do not have permission to view feedback without a session', [ 'status' => 403 ] );
            }
        } else {
            if ( $guest_session ) {
                $where[] = 'guest_session = %s';
                $params[] = sanitize_text_field( $guest_session );
            } elseif ( $author_id ) {
                $where[] = 'author_id = %d';
                $params[] = intval( $author_id );
            }
        }

        if ( $page_url ) {
            $where[] = 'page_url = %s';
            $params[] = sanitize_text_field( $page_url );
        }

        if ( $status ) {
            $where[] = 'status = %s';
            $params[] = sanitize_text_field( $status );
        }

        if ( $priority ) {
            $where[] = 'priority = %s';
            $params[] = sanitize_text_field( $priority );
        }

        if ( $device_type ) {
            $where[] = 'device_type = %s';
            $params[] = sanitize_text_field( $device_type );
        }

        // Search in multiple fields
        if ( $search ) {
            $search_term = '%' . $wpdb->esc_like( sanitize_text_field( $search ) ) . '%';
            $where[] = '(page_url LIKE %s OR browser_info LIKE %s)';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $offset = ( $page - 1 ) * $per_page;

        if ( ! empty( $where ) ) {
            $where_clause = 'WHERE ' . implode( ' AND ', $where );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where_clause}", $params ) );

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $results = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->prepare(
                    "SELECT * FROM {$table} {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    array_merge( $params, [ $per_page, $offset ] )
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                )
            );
        }

        // Sanitize output
        $results = array_map( [ $this, 'sanitize_feedback_response' ], $results );

        return rest_ensure_response( [
            'data' => $results,
            'pagination' => [
                'total' => intval( $total ),
                'pages' => ceil( $total / $per_page ),
                'current_page' => $page,
                'per_page' => $per_page,
            ],
        ] );
    }

    /**
     * GET /feedback/:id - Get single feedback with comments and activity
     */
    public function get_single_feedback( WP_REST_Request $request ) {
        global $wpdb;

        $id = intval( $request->get_param( 'id' ) );
        $guest_session = $request->get_param( 'guest_session' );
        $table = $this->get_table_name( 'feedback' );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $feedback = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
        );

        if ( ! $feedback ) {
            return new \WP_Error( 'not_found', 'Feedback not found', [ 'status' => 404 ] );
        }

        // Enforce guest isolation and ownership
        if ( ! $this->can_manage_feedback() ) {
            if ( ! empty( $guest_session ) ) {
                if ( empty( $feedback->guest_session ) || ! hash_equals( (string) $feedback->guest_session, (string) $guest_session ) ) {
                    return new \WP_Error( 'forbidden', 'You do not have permission to view this feedback', [ 'status' => 403 ] );
                }
            } elseif ( is_user_logged_in() ) {
                $is_author = intval( $feedback->author_id ) === intval( get_current_user_id() );
                $assignments_table = $this->get_table_name( 'assignments' );
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $is_assigned = (bool) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$assignments_table} WHERE feedback_id = %d AND user_id = %d",
                        $id,
                        get_current_user_id()
                    )
                );
                if ( ! $is_author && ! $is_assigned ) {
                    return new \WP_Error( 'forbidden', 'You do not have permission to view this feedback', [ 'status' => 403 ] );
                }
            } else {
                return new \WP_Error( 'forbidden', 'You do not have permission to view this feedback', [ 'status' => 403 ] );
            }
        }

        $feedback = $this->sanitize_feedback_response( $feedback );

        // Get comments - filter by guest session if guest viewing
        $comments_table = $this->get_table_name( 'comments' );
        if ( ! $this->can_manage_feedback() && ! empty( $guest_session ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $comments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT c.*, u.display_name as author_name FROM {$comments_table} c LEFT JOIN {$wpdb->users} u ON c.author_id = u.ID WHERE c.feedback_id = %d AND (c.guest_session = %s OR c.author_id IS NOT NULL) ORDER BY c.created_at ASC",
                    $id,
                    sanitize_text_field( $guest_session )
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $comments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT c.*, u.display_name as author_name FROM {$comments_table} c LEFT JOIN {$wpdb->users} u ON c.author_id = u.ID WHERE c.feedback_id = %d ORDER BY c.created_at ASC",
                    $id
                )
            );
        }

        $comments = array_map( [ $this, 'sanitize_comment_response' ], $comments );

        // Get activity
        $activity_table = $this->get_table_name( 'activity' );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $activity = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, u.display_name as actor_name FROM {$activity_table} a LEFT JOIN {$wpdb->users} u ON a.actor_id = u.ID WHERE a.feedback_id = %d ORDER BY a.created_at DESC",
                $id
            )
        );

        // Get assignments
        $assignments_table = $this->get_table_name( 'assignments' );
        $assignments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, u.display_name as user_name FROM {$assignments_table} a 
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
                WHERE a.feedback_id = %d",
                $id
            )
        );

        return rest_ensure_response( [
            'feedback' => $feedback,
            'comments' => $comments,
            'activity' => $activity,
            'assignments' => $assignments,
        ] );
    }

    /**
     * POST /feedback - Create feedback
     */
    public function create_feedback( WP_REST_Request $request ) {
        global $wpdb;

        $params = $request->get_json_params();

        // Validate required fields
        $page_url = sanitize_text_field( $params['page_url'] ?? '' );
        if ( empty( $page_url ) ) {
            return new \WP_Error( 'invalid_url', 'page_url is required', [ 'status' => 400 ] );
        }

        $x = intval( $params['x'] ?? 0 );
        $y = intval( $params['y'] ?? 0 );
        $vw = intval( $params['viewport_width'] ?? 0 );
        $vh = intval( $params['viewport_height'] ?? 0 );
        $sw = intval( $params['screen_width'] ?? 0 );
        $sh = intval( $params['screen_height'] ?? 0 );

        $selector = ! empty( $params['selector'] ) ? sanitize_text_field( $params['selector'] ) : null;
        $browser = ! empty( $params['browser'] ) ? sanitize_text_field( $params['browser'] ) : null;
        $device_type = ! empty( $params['device_type'] ) ? sanitize_text_field( $params['device_type'] ) : $this->get_device_type( $vw );

        // Extract guest information
        $guest_session = ! empty( $params['guest_session'] ) ? sanitize_text_field( $params['guest_session'] ) : null;
        $guest_name = ! empty( $params['guest_name'] ) ? sanitize_text_field( $params['guest_name'] ) : null;
        $guest_email = ! empty( $params['guest_email'] ) ? sanitize_email( $params['guest_email'] ) : null;

        $table = $this->get_table_name( 'feedback' );
        
        // Create table if it doesn't exist
        $this->ensure_feedback_table_exists();
        
        $inserted = $wpdb->insert(
            $table,
            [
                'page_url' => $page_url,
                'selector' => $selector,
                'coord_x' => $x,
                'coord_y' => $y,
                'viewport_width' => $vw,
                'viewport_height' => $vh,
                'screen_width' => $sw,
                'screen_height' => $sh,
                'browser_info' => $browser,
                'device_type' => $device_type,
                'author_id' => get_current_user_id() ?: null,
                'guest_session' => $guest_session,
                'guest_name' => $guest_name,
                'guest_email' => $guest_email,
                'status' => 'open',
                'priority' => 'normal',
            ],
            [ '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            error_log( 'Lunexia Live Feedback: Failed to create feedback - ' . $wpdb->last_error );
            return new \WP_Error( 'db_insert_failed', 'Could not create feedback: ' . $wpdb->last_error, [ 'status' => 500 ] );
        }

        $feedback_id = $wpdb->insert_id;

        // Log activity
        try {
            $this->log_activity( $feedback_id, 'created', 'Feedback created' );
        } catch ( \Exception $e ) {
            error_log( 'Lunexia Live Feedback: Failed to log activity - ' . $e->getMessage() );
        }

        // Handle screenshot if provided
        if ( ! empty( $params['screenshot_base64'] ) ) {
            try {
                $this->handle_screenshot( $params['screenshot_base64'], $feedback_id );
            } catch ( \Exception $e ) {
                error_log( 'Lunexia Live Feedback: Failed to handle screenshot - ' . $e->getMessage() );
            }
        }

        // Create initial comment if provided
        if ( ! empty( $params['initial_comment'] ) ) {
            try {
                // Ensure comments table exists before inserting initial comment
                $this->ensure_comments_table_exists();
                $this->create_comment_internal( $feedback_id, null, sanitize_text_field( $params['initial_comment'] ), $guest_session, $guest_name, $guest_email );
            } catch ( \Exception $e ) {
                error_log( 'Lunexia Live Feedback: Failed to create initial comment - ' . $e->getMessage() );
            }
        }

        $feedback = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $feedback_id )
        );

        if ( ! $feedback ) {
            error_log( 'Lunexia Live Feedback: Could not retrieve feedback after creation - ID: ' . $feedback_id );
            return new \WP_Error( 'db_read_failed', 'Could not retrieve feedback', [ 'status' => 500 ] );
        }

        try {
            $this->send_feedback_notification( $feedback );
        } catch ( \Exception $e ) {
            error_log( 'Lunexia Live Feedback: Failed to send feedback notification - ' . $e->getMessage() );
        }

        return rest_ensure_response( [
            'id' => $feedback_id,
            'feedback' => $this->sanitize_feedback_response( $feedback ),
        ] );
    }

    protected function get_device_type( $viewport_width ) {
        if ( $viewport_width <= 768 ) {
            return 'mobile';
        }
        if ( $viewport_width <= 1024 ) {
            return 'tablet';
        }
        return 'desktop';
    }

    /**
     * Ensure feedback table exists, create if missing
     */
    protected function ensure_feedback_table_exists() {
        global $wpdb;
        
        $table = $this->get_table_name( 'feedback' );
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check if table exists
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table ) {
            return true;
        }
        
        // Table doesn't exist, create it
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            page_url longtext NOT NULL,
            selector longtext,
            coord_x int(11) DEFAULT 0,
            coord_y int(11) DEFAULT 0,
            viewport_width int(11) DEFAULT 0,
            viewport_height int(11) DEFAULT 0,
            screen_width int(11) DEFAULT 0,
            screen_height int(11) DEFAULT 0,
            browser_info longtext,
            device_type varchar(50),
            author_id bigint(20) unsigned DEFAULT NULL,
            guest_session varchar(100),
            guest_name varchar(255),
            guest_email varchar(255),
            status varchar(50) DEFAULT 'open',
            priority varchar(50) DEFAULT 'normal',
            screenshot_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        error_log( 'Lunexia Live Feedback: Created feedback table' );
        return true;
    }

    /**
     * Ensure comments table exists, create if missing
     */
    protected function ensure_comments_table_exists() {
        global $wpdb;

        $table = $this->get_table_name( 'comments' );
        $charset_collate = $wpdb->get_charset_collate();

        // Check if table exists
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table ) {
            return true;
        }

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            feedback_id bigint(20) unsigned NOT NULL,
            parent_id bigint(20) unsigned DEFAULT NULL,
            author_id bigint(20) unsigned DEFAULT NULL,
            guest_session varchar(255) DEFAULT NULL,
            guest_name varchar(255) DEFAULT NULL,
            guest_email varchar(255) DEFAULT NULL,
            content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        error_log( 'Lunexia Live Feedback: Created comments table' );
        return true;
    }

    /**
     * PATCH /feedback/:id - Update feedback status, priority, etc.
     */
    public function update_feedback( WP_REST_Request $request ) {
        global $wpdb;

        $id = intval( $request->get_param( 'id' ) );
        $params = $request->get_json_params();

        $table = $this->get_table_name( 'feedback' );

        // Verify feedback exists
        $exists = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE id = %d", $id )
        );
        if ( ! $exists ) {
            return new \WP_Error( 'not_found', 'Feedback not found', [ 'status' => 404 ] );
        }

        $update_data = [];
        $update_format = [];

        // Allow only certain fields to be updated
        $allowed_fields = [ 'status', 'priority' ];

        foreach ( $allowed_fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                $update_data[ $field ] = sanitize_text_field( $params[ $field ] );
                $update_format[] = '%s';
            }
        }

        if ( empty( $update_data ) ) {
            return new \WP_Error( 'no_updates', 'No valid fields to update', [ 'status' => 400 ] );
        }

        $wpdb->update( $table, $update_data, [ 'id' => $id ], $update_format, [ '%d' ] );

        // Log activity
        $this->log_activity( $id, 'updated', wp_json_encode( $update_data ) );

        $feedback = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
        );

        return rest_ensure_response( [
            'id' => $id,
            'feedback' => $this->sanitize_feedback_response( $feedback ),
        ] );
    }

    /**
     * POST /feedback/:id/resolve - Resolve feedback
     */
    public function resolve_feedback( WP_REST_Request $request ) {
        global $wpdb;

        $id = intval( $request->get_param( 'id' ) );
        $table = $this->get_table_name( 'feedback' );

        // Fetch feedback before updating to get guest info
        $feedback = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
        );

        if ( ! $feedback ) {
            return new \WP_Error( 'not_found', 'Feedback not found', [ 'status' => 404 ] );
        }

        $wpdb->update(
            $table,
            [ 'status' => 'resolved' ],
            [ 'id' => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        $this->log_activity( $id, 'resolved', 'Feedback marked as resolved' );

        // Send notification to guest if feedback was submitted by guest
        if ( ! empty( $feedback->guest_email ) ) {
            $this->send_guest_resolved_notification( $feedback );
        }

        $feedback = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
        );

        return rest_ensure_response( $this->sanitize_feedback_response( $feedback ) );
    }

    /**
     * POST /feedback/:id/assign - Assign feedback to user
     */
    public function assign_feedback( WP_REST_Request $request ) {
        global $wpdb;

        $id = intval( $request->get_param( 'id' ) );
        $params = $request->get_json_params();

        $user_id = intval( $params['user_id'] ?? 0 );
        if ( $user_id <= 0 ) {
            return new \WP_Error( 'invalid_user', 'user_id is required', [ 'status' => 400 ] );
        }

        // Verify user exists
        if ( ! get_user_by( 'id', $user_id ) ) {
            return new \WP_Error( 'user_not_found', 'User not found', [ 'status' => 404 ] );
        }

        $assignments_table = $this->get_table_name( 'assignments' );

        // Check if already assigned
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$assignments_table} WHERE feedback_id = %d AND user_id = %d",
                $id,
                $user_id
            )
        );

        if ( ! $exists ) {
            $wpdb->insert(
                $assignments_table,
                [ 'feedback_id' => $id, 'user_id' => $user_id ],
                [ '%d', '%d' ]
            );
        }

        $this->log_activity( $id, 'assigned', wp_json_encode( [ 'user_id' => $user_id ] ) );

        $assignment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT a.*, u.display_name as user_name FROM {$assignments_table} a 
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
                WHERE a.feedback_id = %d AND a.user_id = %d",
                $id,
                $user_id
            )
        );

        return rest_ensure_response( $assignment );
    }

    /**
     * DELETE /feedback/:id - Delete feedback
     */
    public function delete_feedback( WP_REST_Request $request ) {
        global $wpdb;

        $id = intval( $request->get_param( 'id' ) );
        $feedback_table = $this->get_table_name( 'feedback' );

        $feedback = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$feedback_table} WHERE id = %d", $id )
        );

        if ( ! $feedback ) {
            return new \WP_Error( 'not_found', 'Feedback not found', [ 'status' => 404 ] );
        }

        $wpdb->delete( $this->get_table_name( 'comments' ), [ 'feedback_id' => $id ], [ '%d' ] );
        $wpdb->delete( $this->get_table_name( 'assignments' ), [ 'feedback_id' => $id ], [ '%d' ] );
        $wpdb->delete( $this->get_table_name( 'activity' ), [ 'feedback_id' => $id ], [ '%d' ] );
        $wpdb->delete( $this->get_table_name( 'attachments' ), [ 'feedback_id' => $id ], [ '%d' ] );
        $wpdb->delete( $feedback_table, [ 'id' => $id ], [ '%d' ] );

        return rest_ensure_response( [ 'deleted' => true ] );
    }

    /**
     * POST /comments - Create comment
     */
    public function create_comment( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        $feedback_id = intval( $request->get_param( 'feedback_id' ) ?: ( $params['feedback_id'] ?? 0 ) );
        if ( $feedback_id <= 0 ) {
            return new \WP_Error( 'invalid_feedback', 'feedback_id is required', [ 'status' => 400 ] );
        }

        // Verify feedback exists
        global $wpdb;
        $feedback_table = $this->get_table_name( 'feedback' );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $feedback = $wpdb->get_row(
            $wpdb->prepare( "SELECT id, author_id, guest_session FROM {$feedback_table} WHERE id = %d", $feedback_id )
        );
        if ( ! $feedback ) {
            return new \WP_Error( 'feedback_not_found', 'Feedback not found', [ 'status' => 404 ] );
        }

        // Extract guest information
        $guest_session = ! empty( $params['guest_session'] ) ? sanitize_text_field( $params['guest_session'] ) : ( ! empty( $request->get_param( 'guest_session' ) ) ? sanitize_text_field( $request->get_param( 'guest_session' ) ) : null );
        $guest_name = ! empty( $params['guest_name'] ) ? sanitize_text_field( $params['guest_name'] ) : ( ! empty( $request->get_param( 'guest_name' ) ) ? sanitize_text_field( $request->get_param( 'guest_name' ) ) : null );
        $guest_email = ! empty( $params['guest_email'] ) ? sanitize_email( $params['guest_email'] ) : ( ! empty( $request->get_param( 'guest_email' ) ) ? sanitize_email( $request->get_param( 'guest_email' ) ) : null );

        // Enforce ownership check inside controller
        if ( ! $this->can_manage_feedback() ) {
            if ( is_user_logged_in() ) {
                $current_user_id = get_current_user_id();
                $is_author = intval( $feedback->author_id ) === intval( $current_user_id );
                $assignments_table = $this->get_table_name( 'assignments' );
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $is_assigned = (bool) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$assignments_table} WHERE feedback_id = %d AND user_id = %d",
                        $feedback_id,
                        $current_user_id
                    )
                );
                if ( ! $is_author && ! $is_assigned ) {
                    return new \WP_Error( 'forbidden', 'You do not have permission to comment on this feedback', [ 'status' => 403 ] );
                }
            } else {
                if ( ! $this->allow_guest_feedback() || empty( $guest_session ) || empty( $feedback->guest_session ) || ! hash_equals( (string) $feedback->guest_session, (string) $guest_session ) ) {
                    return new \WP_Error( 'forbidden', 'You do not have permission to comment on this feedback without verifying ownership through the feedback guest session', [ 'status' => 403 ] );
                }
            }
        }

        $content = ! empty( $params['content'] ) ? sanitize_textarea_field( $params['content'] ) : ( ! empty( $request->get_param( 'content' ) ) ? sanitize_textarea_field( $request->get_param( 'content' ) ) : '' );
        if ( empty( $content ) ) {
            return new \WP_Error( 'invalid_content', 'content is required', [ 'status' => 400 ] );
        }

        $parent_id = ! empty( $params['parent_id'] ) ? intval( $params['parent_id'] ) : ( ! empty( $request->get_param( 'parent_id' ) ) ? intval( $request->get_param( 'parent_id' ) ) : null );

        try {
            $comment_id = $this->create_comment_internal( $feedback_id, $parent_id, $content, $guest_session, $guest_name, $guest_email );

            if ( ! $comment_id ) {
                error_log( 'Lunexia Live Feedback: Failed to create comment for feedback ' . $feedback_id );
                return new \WP_Error( 'db_insert_failed', 'Could not create comment', [ 'status' => 500 ] );
            }

            $comments_table = $this->get_table_name( 'comments' );
            $comment = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT c.*, u.display_name as author_name FROM {$comments_table} c 
                    LEFT JOIN {$wpdb->users} u ON c.author_id = u.ID 
                    WHERE c.id = %d",
                    $comment_id
                )
            );

            $this->send_comment_notification( $comment );

            return rest_ensure_response( $this->sanitize_comment_response( $comment ) );
        } catch ( \Exception $e ) {
            error_log( 'Lunexia Live Feedback: Comment creation error - ' . $e->getMessage() );
            return new \WP_Error( 'comment_error', 'Failed to create comment: ' . $e->getMessage(), [ 'status' => 500 ] );
        }
    }

    /**
     * Internal helper to create comment
     */
    protected function create_comment_internal( $feedback_id, $parent_id, $content, $guest_session = null, $guest_name = null, $guest_email = null ) {
        global $wpdb;

        $table = $this->get_table_name( 'comments' );
        $inserted = $wpdb->insert(
            $table,
            [
                'feedback_id' => $feedback_id,
                'parent_id' => $parent_id,
                'author_id' => get_current_user_id() ?: null,
                'guest_session' => $guest_session,
                'guest_name' => $guest_name,
                'guest_email' => $guest_email,
                'content' => $content,
            ],
            [ '%d', '%d', '%d', '%s', '%s', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            return false;
        }

        $comment_id = $wpdb->insert_id;

        // Log activity for new comment
        $this->log_activity( $feedback_id, 'commented', wp_json_encode( [ 'comment_id' => $comment_id ] ) );

        return $comment_id;
    }

    /**
     * GET /comments/:id - Get comment thread for feedback
     */
    public function get_comments( WP_REST_Request $request ) {
        global $wpdb;

        $feedback_id = intval( $request->get_param( 'id' ) );
        $guest_session = $request->get_param( 'guest_session' );

        $feedback_table = $this->get_table_name( 'feedback' );
        $feedback = $wpdb->get_row(
            $wpdb->prepare( "SELECT guest_session, author_id FROM {$feedback_table} WHERE id = %d", $feedback_id )
        );

        if ( ! $feedback ) {
            return new \WP_Error( 'feedback_not_found', 'Feedback not found', [ 'status' => 404 ] );
        }

        if ( ! $this->can_manage_feedback() ) {
            if ( $guest_session ) {
                if ( empty( $feedback->guest_session ) || ! hash_equals( (string) $feedback->guest_session, (string) $guest_session ) ) {
                    return new \WP_Error( 'forbidden', 'You do not have permission to view comments for this feedback', [ 'status' => 403 ] );
                }
            } elseif ( is_user_logged_in() ) {
                $is_author = intval( $feedback->author_id ) === intval( get_current_user_id() );
                $assignments_table = $this->get_table_name( 'assignments' );
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $is_assigned = (bool) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$assignments_table} WHERE feedback_id = %d AND user_id = %d",
                        $feedback_id,
                        get_current_user_id()
                    )
                );
                if ( ! $is_author && ! $is_assigned ) {
                    return new \WP_Error( 'forbidden', 'You do not have permission to view comments for this feedback', [ 'status' => 403 ] );
                }
            } else {
                return new \WP_Error( 'forbidden', 'You do not have permission to view comments for this feedback', [ 'status' => 403 ] );
            }
        }

        $comments_table = $this->get_table_name( 'comments' );
        if ( ! $this->can_manage_feedback() && ! empty( $guest_session ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $comments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT c.*, u.display_name as author_name FROM {$comments_table} c LEFT JOIN {$wpdb->users} u ON c.author_id = u.ID WHERE c.feedback_id = %d AND (c.guest_session = %s OR c.author_id IS NOT NULL) ORDER BY c.created_at ASC",
                    $feedback_id,
                    sanitize_text_field( $guest_session )
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $comments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT c.*, u.display_name as author_name FROM {$comments_table} c LEFT JOIN {$wpdb->users} u ON c.author_id = u.ID WHERE c.feedback_id = %d ORDER BY c.created_at ASC",
                    $feedback_id
                )
            );
        }

        $comments = array_map( [ $this, 'sanitize_comment_response' ], $comments );

        return rest_ensure_response( $comments );
    }

    /**
     * DELETE /comments/:id - Delete comment
     */
    public function delete_comment( WP_REST_Request $request ) {
        global $wpdb;

        $id = intval( $request->get_param( 'id' ) );
        $comments_table = $this->get_table_name( 'comments' );

        // Get comment to verify it exists and get feedback_id for activity log
        $comment = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$comments_table} WHERE id = %d", $id )
        );

        if ( ! $comment ) {
            return new \WP_Error( 'not_found', 'Comment not found', [ 'status' => 404 ] );
        }

        $wpdb->delete( $comments_table, [ 'id' => $id ], [ '%d' ] );

        $this->log_activity( $comment->feedback_id, 'comment_deleted', wp_json_encode( [ 'comment_id' => $id ] ) );

        return rest_ensure_response( [ 'deleted' => true ] );
    }

    /**
     * POST /attachments - Upload attachment
     */
    public function create_attachment( WP_REST_Request $request ) {
        // Get file from request
        $params = $request->get_json_params();

        if ( empty( $params['base64'] ) ) {
            return new \WP_Error( 'invalid_file', 'base64 file data is required', [ 'status' => 400 ] );
        }

        $feedback_id = ! empty( $params['feedback_id'] ) ? intval( $params['feedback_id'] ) : null;
        $comment_id = ! empty( $params['comment_id'] ) ? intval( $params['comment_id'] ) : null;

        $attachment_id = $this->handle_base64_upload(
            $params['base64'],
            ! empty( $params['filename'] ) ? sanitize_file_name( $params['filename'] ) : null
        );

        if ( ! $attachment_id ) {
            return new \WP_Error( 'upload_failed', 'Failed to upload attachment', [ 'status' => 500 ] );
        }

        // Associate attachment with feedback/comment
        global $wpdb;
        if ( $feedback_id || $comment_id ) {
            $wpdb->insert(
                $this->get_table_name( 'attachments' ),
                [
                    'feedback_id' => $feedback_id,
                    'comment_id' => $comment_id,
                    'attachment_id' => $attachment_id,
                ],
                [ '%d', '%d', '%d' ]
            );
        }

        $attachment = get_post( $attachment_id );

        return rest_ensure_response( [
            'id' => $attachment_id,
            'url' => wp_get_attachment_url( $attachment_id ),
            'type' => $attachment->post_mime_type,
        ] );
    }

    /**
     * Handle base64 file upload
     */
    protected function handle_base64_upload( $base64_data, $filename = null ) {
        // Validate data URI format
        if ( strpos( $base64_data, ';base64,' ) === false ) {
            return false;
        }

        $parts = explode( ';base64,', $base64_data );
        if ( count( $parts ) !== 2 ) {
            return false;
        }

        $meta = $parts[0];
        $data = $parts[1];

        // Validate MIME type
        if ( ! preg_match( '/^data:([a-z0-9\/\-+.]+)/', $meta, $m ) ) {
            return false;
        }

        $mime = $m[1];

        // Only allow images
        if ( ! in_array( $mime, $this->allowed_image_types, true ) ) {
            return false;
        }

        // Decode data
        $decoded = base64_decode( $data, true );
        if ( false === $decoded ) {
            return false;
        }

        // Check size
        if ( strlen( $decoded ) > $this->max_request_size ) {
            return false;
        }

        // Generate filename
        if ( ! $filename ) {
            $ext = str_replace( 'image/', '', $mime );
            $filename = 'lunexia-lf-attachment-' . wp_generate_uuid4() . '.' . $ext;
        }

        // Upload file
        $upload = wp_upload_bits( $filename, null, $decoded );
        if ( $upload['error'] ) {
            return false;
        }

        // Create attachment post
        $filetype = wp_check_filetype( $upload['file'] );
        $attachment_data = [
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name( basename( $upload['file'] ) ),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attachment_id = wp_insert_attachment( $attachment_data, $upload['file'] );

        if ( ! is_wp_error( $attachment_id ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
            wp_update_attachment_metadata( $attachment_id, $attach_metadata );
        }

        return $attachment_id;
    }

    /**
     * Handle screenshot upload (legacy compatibility)
     */
    protected function handle_screenshot( $base64, $feedback_id ) {
        $attachment_id = $this->handle_base64_upload( $base64, 'lunexia-lf-screenshot-' . time() . '.png' );

        if ( ! $attachment_id ) {
            return false;
        }

        global $wpdb;
        $wpdb->update(
            $this->get_table_name( 'feedback' ),
            [ 'screenshot_id' => $attachment_id ],
            [ 'id' => $feedback_id ],
            [ '%d' ],
            [ '%d' ]
        );

        return $attachment_id;
    }

    /**
     * Log activity for audit trail
     */
    protected function log_activity( $feedback_id, $action, $details = null ) {
        global $wpdb;

        $wpdb->insert(
            $this->get_table_name( 'activity' ),
            [
                'feedback_id' => $feedback_id,
                'actor_id' => get_current_user_id() ?: null,
                'action' => $action,
                'details' => $details,
            ],
            [ '%d', '%d', '%s', '%s' ]
        );
    }

    /**
     * Sanitize feedback for JSON response
     */
    protected function sanitize_feedback_response( $feedback ) {
        return [
            'id' => intval( $feedback->id ),
            'page_url' => esc_url_raw( $feedback->page_url ),
            'selector' => $feedback->selector ? sanitize_text_field( $feedback->selector ) : null,
            'coord_x' => intval( $feedback->coord_x ),
            'coord_y' => intval( $feedback->coord_y ),
            'viewport_width' => intval( $feedback->viewport_width ),
            'viewport_height' => intval( $feedback->viewport_height ),
            'screen_width' => intval( $feedback->screen_width ),
            'screen_height' => intval( $feedback->screen_height ),
            'browser_info' => $feedback->browser_info ? sanitize_text_field( $feedback->browser_info ) : null,
            'device_type' => sanitize_text_field( $feedback->device_type ?? 'desktop' ),
            'author_id' => intval( $feedback->author_id ),
            'status' => sanitize_text_field( $feedback->status ),
            'priority' => sanitize_text_field( $feedback->priority ),
            'screenshot_id' => $feedback->screenshot_id ? intval( $feedback->screenshot_id ) : null,
            'screenshot_url' => $feedback->screenshot_id ? esc_url_raw( wp_get_attachment_url( $feedback->screenshot_id ) ) : null,
            'created_at' => $feedback->created_at ?? current_time( 'mysql' ),
            'updated_at' => $feedback->updated_at ?? $feedback->created_at ?? current_time( 'mysql' ),
        ];
    }

    /**
     * Sanitize comment for JSON response
     */
    protected function sanitize_comment_response( $comment ) {
        return [
            'id' => intval( $comment->id ),
            'feedback_id' => intval( $comment->feedback_id ),
            'parent_id' => $comment->parent_id ? intval( $comment->parent_id ) : null,
            'author_id' => intval( $comment->author_id ),
            'author_name' => sanitize_text_field( $comment->author_name ?? '' ),
            'content' => wp_kses_post( $comment->content ),
            'created_at' => $comment->created_at,
        ];
    }
}
