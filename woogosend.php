<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/sofyansitorus
 * @package           WooGoSend
 *
 * @wordpress-plugin
 * Plugin Name:       WooGoSend
 * Plugin URI:        https://WooGoSend.com
 * Description:       WooCommerce shipping rates calculator allows you to easily offer shipping rates based on the distance calculated using Google Maps Distance Matrix Service API.
 * Version:           1.4.0
 * Author:            Sofyan Sitorus
 * Author URI:        https://github.com/sofyansitorus
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woogosend
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 4.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Define plugin main constants.
define( 'WOOGOSEND_FILE', __FILE__ );
define( 'WOOGOSEND_PATH', plugin_dir_path( WOOGOSEND_FILE ) );
define( 'WOOGOSEND_URL', plugin_dir_url( WOOGOSEND_FILE ) );

// Load the helpers.
require_once WOOGOSEND_PATH . '/includes/constants.php';
require_once WOOGOSEND_PATH . '/includes/helpers.php';

// Register the class autoload.
if ( function_exists( 'woogosend_autoload' ) ) {
	spl_autoload_register( 'woogosend_autoload' );
}

/**
 * Boot the plugin
 */
if ( woogosend_is_plugin_active( 'woocommerce/woocommerce.php' ) && class_exists( 'WooGoSend' ) ) {
	// Initialize the WooGoSend class.
	WooGoSend::get_instance();
}
