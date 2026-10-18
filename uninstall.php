<?php
/**
 * Lunexia Live Feedback - Uninstall
 *
 * Fired when the plugin is uninstalled.
 *
 * @package LunexiaLiveFeedback
 * @subpackage Uninstall
 * @version 0.1.0
 * @license GPLv2 or later
 * @link https://lunexiait.com
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$tables = [
    $wpdb->prefix . 'lunexia_lf_feedback',
    $wpdb->prefix . 'lunexia_lf_comments',
    $wpdb->prefix . 'lunexia_lf_attachments',
    $wpdb->prefix . 'lunexia_lf_activity',
    $wpdb->prefix . 'lunexia_lf_assignments',
    $wpdb->prefix . 'lunexia_lf_migrations',
];

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

$options = [
    'lunexia_lf_allow_guest_feedback',
    'lunexia_lf_frontend_enabled',
    'lunexia_lf_notification_recipients',
    'lunexia_lf_notification_recipients_enabled',
    'lunexia_lf_guest_notifications_enabled',
];

foreach ( $options as $option ) {
    delete_option( $option );
}
