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
 * Description:       WooCommerce per kilometer shipping rates calculator for GoSend Go-Jek Indonesia courier.
 * Version:           1.2.6
 * Author:            Sofyan Sitorus
 * Author URI:        https://github.com/sofyansitorus
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woogosend
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.4.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check if WooCommerce is active
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	return;
}
// phpcs:enable

// Defines plugin named constants.
define( 'WOOGOSEND_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOGOSEND_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOGOSEND_VERSION', '1.2.6' );
define( 'WOOGOSEND_METHOD_ID', 'woogosend' );
define( 'WOOGOSEND_METHOD_TITLE', 'WooGoSend' );
define( 'WOOGOSEND_MAP_SECRET_KEY', 'QUl6YVN5Qk82MVFJUm52Zkc5c2tKTW1HV1JVbWhsSU5lcUZXaTdV' );

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
 * Load the main class
 *
 * @since    1.0.0
 */
function woogosend_shipping_init() {
	include plugin_dir_path( __FILE__ ) . 'includes/class-woogosend.php';
}
add_action( 'woocommerce_shipping_init', 'woogosend_shipping_init' );

/**
 * Add plugin action links.
 *
 * Add a link to the settings page on the plugins.php page.
 *
 * @since 1.1.1
 *
 * @param  array $links List of existing plugin action links.
 * @return array         List of modified plugin action links.
 */
function woogosend_plugin_action_links( $links ) {
	$zone_id = 0;
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		if ( empty( $zone['shipping_methods'] ) || empty( $zone['zone_id'] ) ) {
			continue;
		}
		foreach ( $zone['shipping_methods'] as $zone_shipping_method ) {
			if ( $zone_shipping_method instanceof WooGoSend ) {
				$zone_id = $zone['zone_id'];
				break;
			}
		}
		if ( $zone_id ) {
			break;
		}
	}

	$links = array_merge(
		array(
			'<a href="' . esc_url(
				add_query_arg(
					array(
						'page'               => 'wc-settings',
						'tab'                => 'shipping',
						'zone_id'            => $zone_id,
						'woogosend_settings' => true,
					),
					admin_url( 'admin.php' )
				)
			) . '">' . __( 'Settings', 'woogosend' ) . '</a>',
		),
		$links
	);

	return $links;
}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woogosend_plugin_action_links' );

/**
 * Enqueue admin scripts.
 *
 * @since 1.1.1
 * @param string $hook Passed screen ID in admin area.
 */
function woogosend_enqueue_scripts( $hook = null ) {
	if ( 'woocommerce_page_wc-settings' === $hook ) {

		// Enqueue admin scripts.
		$woogosend_admin_css = ( defined( 'WOOGOSEND_DEV' ) && WOOGOSEND_DEV ) ? add_query_arg( array( 't' => time() ), WOOGOSEND_URL . 'assets/css/woogosend-admin.css' ) : WOOGOSEND_URL . 'assets/css/woogosend-admin.min.css';
		wp_enqueue_style(
			'woogosend-admin', // Give the script a unique ID.
			$woogosend_admin_css, // Define the path to the JS file.
			array(), // Define dependencies.
			WOOGOSEND_VERSION, // Define a version (optional).
			false // Specify whether to put in footer (leave this false).
		);

		// Enqueue admin scripts.
		$woogosend_admin_js = ( defined( 'WOOGOSEND_DEV' ) && WOOGOSEND_DEV ) ? add_query_arg( array( 't' => time() ), WOOGOSEND_URL . 'assets/js/woogosend-admin.js' ) : WOOGOSEND_URL . 'assets/js/woogosend-admin.min.js';
		wp_enqueue_script(
			'woogosend-admin', // Give the script a unique ID.
			$woogosend_admin_js, // Define the path to the JS file.
			array( 'jquery', 'wp-util' ), // Define dependencies.
			WOOGOSEND_VERSION, // Define a version (optional).
			true // Specify whether to put in footer (leave this true).
		);

		wp_localize_script(
			'woogosend-admin',
			'woogosend_params',
			array(
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				'show_settings' => isset( $_GET['woogosend_settings'] ) && is_admin(),
				// phpcs:enable
				'method_id'     => WOOGOSEND_METHOD_ID,
				'method_title'  => WOOGOSEND_METHOD_TITLE,
				'txt'           => array(
					'drag_marker' => __( 'Drag this marker or search your address at the input above.', 'woogosend' ),
				),
				'marker'        => WOOGOSEND_URL . 'assets/img/marker.png',
				'language'      => get_locale(),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'woogosend_enqueue_scripts', 999 );

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
