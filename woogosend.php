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
 * @since             1.0.0
 * @package           WooGoSend
 *
 * @wordpress-plugin
 * Plugin Name:       WooGoSend
 * Plugin URI:        https://github.com/sofyansitorus/WooGoSend
 * Description:       WooCommerce per kilometer shipping rates calculator for GoSend courier from Go-Jek Indonesia.
 * Version:           1.1.0
 * Author:            Sofyan Sitorus
 * Author URI:        https://github.com/sofyansitorus
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woogosend
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function woogosend_load_textdomain() {
	load_plugin_textdomain( 'woogosend', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'woogosend_load_textdomain' );


/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	/**
	 * Load the main class
	 *
	 * @since    1.0.0
	 */
	function woogosend_shipping_init() {
		include plugin_dir_path( __FILE__ ) . 'includes/class-woogosend.php';
	}
	add_action( 'woocommerce_shipping_init', 'woogosend_shipping_init' );

	/**
	 * Register shipping method
	 *
	 * @since    1.0.0
	 * @param array $methods Existing shipping methods.
	 */
	function woogosend_shipping_methods( $methods ) {
		$methods['woogosend'] = 'WooGoSend';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'woogosend_shipping_methods' );

	// Show city field on the cart shipping calculator.
	add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_true' );

}// End if().
