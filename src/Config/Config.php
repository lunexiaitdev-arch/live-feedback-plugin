<?php
namespace LunexiaLiveFeedback\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Central plugin configuration and constants
 */
class Config {
    const PLUGIN_VERSION = '0.1.0';
    const PLUGIN_SLUG = 'lunexia_lf';
    const PLUGIN_NAMESPACE = 'LunexiaLiveFeedback';
    const REST_NAMESPACE = 'lunexia-lf/v1';
    const ADMIN_CAPABILITY = 'lunexia_lf_manage';
    const MIN_WORDPRESS_VERSION = '5.8';
    const MIN_PHP_VERSION = '7.4';

    // Capability definitions
    const CAPABILITIES = [
        'lunexia_lf_manage'  => 'Manage Lunexia Live Feedback',
        'lunexia_lf_create'  => 'Create Feedback',
        'lunexia_lf_resolve' => 'Resolve Feedback',
        'lunexia_lf_assign'  => 'Assign Feedback',
        'lunexia_lf_upload'  => 'Upload Screenshots',
    ];

    // Role assignments
    const ROLE_CAPABILITIES = [
        'administrator' => [
            'lunexia_lf_manage'  => true,
            'lunexia_lf_create'  => true,
            'lunexia_lf_resolve' => true,
            'lunexia_lf_assign'  => true,
            'lunexia_lf_upload'  => true,
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

    // File size limits
    const MAX_UPLOAD_SIZE = 5242880; // 5MB

    // Allowed MIME types
    const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    // Table names
    public static function get_table( $table_name ) {
        global $wpdb;
        $tables = [
            'feedback'    => $wpdb->prefix . self::PLUGIN_SLUG . '_feedback',
            'comments'    => $wpdb->prefix . self::PLUGIN_SLUG . '_comments',
            'attachments' => $wpdb->prefix . self::PLUGIN_SLUG . '_attachments',
            'activity'    => $wpdb->prefix . self::PLUGIN_SLUG . '_activity',
            'assignments' => $wpdb->prefix . self::PLUGIN_SLUG . '_assignments',
            'migrations'  => $wpdb->prefix . self::PLUGIN_SLUG . '_migrations',
        ];
        return $tables[ $table_name ] ?? null;
    }

    /**
     * Get all table names
     */
    public static function get_all_tables() {
        $tables = [];
        foreach ( [ 'feedback', 'comments', 'attachments', 'activity', 'assignments', 'migrations' ] as $table ) {
            $tables[ $table ] = self::get_table( $table );
        }
        return $tables;
    }
}
