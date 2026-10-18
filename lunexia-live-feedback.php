<?php
/**
 * Plugin Name: Lunexia Live Feedback
 * Description: Capture customer insights directly on your WordPress site with a sleek live feedback overlay and in-context review panel.
 * Version:     0.1.0
 * Author:      Lunexia IT
 * Author URI:  https://lunexiait.com
 * Text Domain: lunexia-live-feedback
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package LunexiaLiveFeedback
 */

/**
 * Lunexia Live Feedback - Customer feedback collection plugin for WordPress.
 * Copyright (C) 2024  Lunexia IT
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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'LUNEXIA_LF_FILE' ) ) {
    define( 'LUNEXIA_LF_FILE', __FILE__ );
}
if ( ! defined( 'LUNEXIA_LF_DIR' ) ) {
    define( 'LUNEXIA_LF_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'LUNEXIA_LF_URL' ) ) {
    define( 'LUNEXIA_LF_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'LUNEXIA_LF_VERSION' ) ) {
    define( 'LUNEXIA_LF_VERSION', '0.1.0' );
}

require_once LUNEXIA_LF_DIR . 'vendor/autoload.php';

use LunexiaLiveFeedback\Plugin;

function lunexia_lf_init() {
    $plugin = Plugin::instance();
    $plugin->run();
}

register_activation_hook( __FILE__, [ '\LunexiaLiveFeedback\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\LunexiaLiveFeedback\Plugin', 'deactivate' ] );
register_uninstall_hook( __FILE__, '\LunexiaLiveFeedback\Plugin::uninstall' );

add_action( 'plugins_loaded', 'lunexia_lf_init' );
