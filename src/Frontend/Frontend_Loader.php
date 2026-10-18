<?php
/**
 * Frontend Loader
 *
 * @package LunexiaLiveFeedback
 */

namespace LunexiaLiveFeedback\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend Loader Class
 */
class Frontend_Loader {
    public function init() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer', [ $this, 'render_toggle' ] );
    }

    public function enqueue_assets() {
        $plugin_file = dirname( dirname( dirname( __FILE__ ) ) ) . '/lunexia-live-feedback.php';
        $asset_dir = plugin_dir_url( $plugin_file ) . 'assets/';
        $asset_path = plugin_dir_path( $plugin_file ) . 'assets/';

        // Use file modification time for cache-busting so users see updates immediately.
        $css_file = $asset_path . 'css/overlay.css';
        $js_file = $asset_path . 'js/overlay.js';
        $css_ver = file_exists( $css_file ) ? filemtime( $css_file ) : '0.1.0';
        $js_ver = file_exists( $js_file ) ? filemtime( $js_file ) : '0.1.0';

        wp_enqueue_style( 'lunexia-lf-frontend', $asset_dir . 'css/overlay.css', [], $css_ver );
        wp_enqueue_script( 'lunexia-lf-frontend', $asset_dir . 'js/overlay.js', [], $js_ver, true );

        $frontend_data = [
            'rest_url' => esc_url_raw( rest_url( 'lunexia-lf/v1/' ) ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'fallback_submit_url' => esc_url_raw( admin_url( 'admin-post.php?action=lunexia_lf_submit_feedback' ) ),
            'fallback_ajax_url' => esc_url_raw( admin_url( 'admin-ajax.php?action=lunexia_lf_submit_feedback' ) ),
            'current_user_can_create' => current_user_can( 'lunexia_lf_create' ) || current_user_can( 'edit_posts' ),
            'current_user_can_manage' => current_user_can( 'lunexia_lf_manage' ) || current_user_can( 'manage_options' ),
            'allow_guest_feedback' => (bool) get_option( 'lunexia_lf_allow_guest_feedback', true ),
            'frontend_enabled' => (bool) get_option( 'lunexia_lf_frontend_enabled', true ),
        ];

        wp_localize_script( 'lunexia-lf-frontend', 'lunexia_lf', $frontend_data );
        wp_add_inline_script( 'lunexia-lf-frontend', 'window.lvf = window.lunexia_lf;', 'before' );
    }

    public function render_toggle() {
        $frontend_enabled = (bool) get_option( 'lunexia_lf_frontend_enabled', true );
        $allow_guest_feedback = (bool) get_option( 'lunexia_lf_allow_guest_feedback', true );
        $can_use_feedback = current_user_can( 'lunexia_lf_create' ) || current_user_can( 'lunexia_lf_manage' ) || current_user_can( 'edit_posts' ) || current_user_can( 'manage_options' ) || $allow_guest_feedback;

        if ( ! $frontend_enabled || ! $can_use_feedback ) {
            return;
        }

        echo '<div id="lunexia-lf-toggle-root"><div id="lvf-toggle-root"></div></div>';
    }
}
