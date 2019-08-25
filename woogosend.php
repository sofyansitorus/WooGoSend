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
 * Description:       WooCommerce per kilometer shipping rates calculator for GoSend delivery service from Gojek Indonesia.
 * Version:           1.3.0
 * Author:            Sofyan Sitorus
 * Author URI:        https://github.com/sofyansitorus
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woogosend
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Defines plugin named constants.
if ( ! defined( 'WOOGOSEND_FILE' ) ) {
	define( 'WOOGOSEND_FILE', __FILE__ );
}
if ( ! defined( 'WOOGOSEND_PATH' ) ) {
	define( 'WOOGOSEND_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WOOGOSEND_URL' ) ) {
	define( 'WOOGOSEND_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WOOGOSEND_DEFAULT_LAT' ) ) {
	define( 'WOOGOSEND_DEFAULT_LAT', '-6.178784361374902' );
}
if ( ! defined( 'WOOGOSEND_DEFAULT_LNG' ) ) {
	define( 'WOOGOSEND_DEFAULT_LNG', '106.82303292695315' );
}
if ( ! defined( 'WOOGOSEND_TEST_LAT' ) ) {
	define( 'WOOGOSEND_TEST_LAT', '-6.181472315327319' );
}
if ( ! defined( 'WOOGOSEND_TEST_LNG' ) ) {
	define( 'WOOGOSEND_TEST_LNG', '106.8170462364319' );
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$woogosend_plugin_data = get_plugin_data( WOOGOSEND_FILE, false, false );

if ( ! defined( 'WOOGOSEND_VERSION' ) ) {
	$woogosend_version = isset( $woogosend_plugin_data['Version'] ) ? $woogosend_plugin_data['Version'] : '1.0.0';
	define( 'WOOGOSEND_VERSION', $woogosend_version );
}

if ( ! defined( 'WOOGOSEND_METHOD_ID' ) ) {
	$woogosend_method_id = isset( $woogosend_plugin_data['TextDomain'] ) ? $woogosend_plugin_data['TextDomain'] : 'woogosend';
	define( 'WOOGOSEND_METHOD_ID', $woogosend_method_id );
}

if ( ! defined( 'WOOGOSEND_METHOD_TITLE' ) ) {
	$woogosend_method_title = isset( $woogosend_plugin_data['Name'] ) ? $woogosend_plugin_data['Name'] : 'WooGoSend';
	define( 'WOOGOSEND_METHOD_TITLE', $woogosend_method_title );
}

/**
 * Include required core files.
 */
require_once WOOGOSEND_PATH . '/includes/helpers.php';
require_once WOOGOSEND_PATH . '/includes/class-woogosend-api.php';
require_once WOOGOSEND_PATH . '/includes/class-woogosend-services.php';

/**
 * Check if WooCommerce plugin is active
 */
if ( ! woogosend_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	return;
}

// Show fields in the shipping calculator form.
add_filter( 'woocommerce_shipping_calculator_enable_postcode', '__return_true' );
add_filter( 'woocommerce_shipping_calculator_enable_state', '__return_true' );
add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_true' );
add_filter( 'woocommerce_shipping_calculator_enable_address_1', '__return_true' );
add_filter( 'woocommerce_shipping_calculator_enable_address_2', '__return_true' );

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 *
 * @return void
 */
function woogosend_load_textdomain() {
	load_plugin_textdomain( 'woogosend', false, basename( WOOGOSEND_PATH ) . '/languages' );
}
add_action( 'plugins_loaded', 'woogosend_load_textdomain' );

/**
 * Add plugin action links.
 *
 * Add a link to the settings page on the plugins.php page.
 *
 * @since 1.1.1
 *
 * @param array $links List of existing plugin action links.
 *
 * @return array List of modified plugin action links.
 */
function woogosend_plugin_action_links( $links ) {
	$links = array_merge(
		array(
			'<a href="' . esc_url(
				add_query_arg(
					array(
						'page'               => 'wc-settings',
						'tab'                => 'shipping',
						'zone_id'            => 0,
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
 * Load the main class
 *
 * @since 1.0.0
 *
 * @return void
 */
function woogosend_shipping_init() {
	include plugin_dir_path( __FILE__ ) . 'includes/class-woogosend.php';
}
add_action( 'woocommerce_shipping_init', 'woogosend_shipping_init' );

/**
 * Register shipping method
 *
 * @since 1.0.0
 *
 * @param array $methods Existing shipping methods.
 *
 * @return array
 */
function woogosend_shipping_methods( $methods ) {
	return array_merge(
		$methods,
		array(
			'woogosend' => 'WooGoSend',
		)
	);
}
add_filter( 'woocommerce_shipping_methods', 'woogosend_shipping_methods' );

/**
 * Enqueue both scripts and styles in the backend area.
 *
 * @since 1.3
 *
 * @param string $hook Current admin page hook.
 *
 * @return void
 */
function woogosend_enqueue_scripts_backend( $hook ) {
	if ( false !== strpos( $hook, 'wc-settings' ) ) {
		$is_debug = defined( 'WOOGOSEND_DEV' ) && WOOGOSEND_DEV;

		// Define the styles URL.
		$css_url = WOOGOSEND_URL . 'assets/css/woogosend-backend.min.css';
		if ( $is_debug ) {
			$css_url = add_query_arg( array( 't' => time() ), str_replace( '.min', '', $css_url ) );
		}

		// Enqueue admin styles.
		wp_enqueue_style(
			'woogosend-backend', // Give the script a unique ID.
			$css_url, // Define the path to the JS file.
			array(), // Define dependencies.
			WOOGOSEND_VERSION, // Define a version (optional).
			false // Specify whether to put in footer (leave this false).
		);

		// Define the scripts URL.
		$js_url = WOOGOSEND_URL . 'assets/js/woogosend-backend.min.js';
		if ( $is_debug ) {
			$js_url = add_query_arg( array( 't' => time() ), str_replace( '.min', '', $js_url ) );
		}

		// Enqueue admin scripts.
		wp_enqueue_script(
			'woogosend-backend', // Give the script a unique ID.
			$js_url, // Define the path to the JS file.
			array( 'jquery' ), // Define dependencies.
			WOOGOSEND_VERSION, // Define a version (optional).
			true // Specify whether to put in footer (leave this true).
		);

		// Localize the script data.
		wp_localize_script(
			'woogosend-backend',
			'woogosend_backend',
			array(
				'showSettings'           => isset( $_GET['woogosend_settings'] ) && is_admin(), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'methodId'               => WOOGOSEND_METHOD_ID,
				'methodTitle'            => WOOGOSEND_METHOD_TITLE,
				'marker'                 => WOOGOSEND_URL . 'assets/img/marker.png',
				'defaultLat'             => WOOGOSEND_DEFAULT_LAT,
				'defaultLng'             => WOOGOSEND_DEFAULT_LNG,
				'testLat'                => WOOGOSEND_TEST_LAT,
				'testLng'                => WOOGOSEND_TEST_LNG,
				'language'               => get_locale(),
				'isDebug'                => $is_debug,
				'i18n'                   => woogosend_i18n(),
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'validate_api_key_nonce' => wp_create_nonce( 'woogosend_validate_api_key_server' ),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'woogosend_enqueue_scripts_backend' );

/**
 * Enqueue scripts in the frontend area.
 *
 * @since 1.3
 *
 * @return void
 */
function woogosend_enqueue_scripts_frontend() {
	// Bail early if there is no instances enabled.
	if ( ! woogosend_instances() ) {
		return;
	}

	// Define scripts URL.
	$js_url = WOOGOSEND_URL . 'assets/js/woogosend-frontend.min.js';
	if ( defined( 'WOOGOSEND_DEV' ) && WOOGOSEND_DEV ) {
		$js_url = add_query_arg( array( 't' => time() ), str_replace( '.min', '', $js_url ) );
	}

	// Enqueue scripts.
	wp_enqueue_script(
		'woogosend-frontend', // Give the script a unique ID.
		$js_url, // Define the path to the JS file.
		array( 'jquery', 'wp-util' ), // Define dependencies.
		WOOGOSEND_VERSION, // Define a version (optional).
		true // Specify whether to put in footer (leave this true).
	);

	$fields = array(
		'postcode',
		'state',
		'city',
		'address_1',
		'address_2',
	);

	// Localize the script data.
	$woogosend_frontend = array();

	foreach ( $fields as $field ) {
		/**
		 * Filters the shipping calculator fields
		 *
		 * @since 1.3
		 *
		 * @param bool $enabled Is field enabled status.
		 */
		$enabled = apply_filters( 'woocommerce_shipping_calculator_enable_' . $field, true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$woogosend_frontend[ 'shipping_calculator_' . $field ] = $enabled;
	}

	wp_localize_script( 'woogosend-frontend', 'woogosend_frontend', $woogosend_frontend );
}
add_action( 'wp_enqueue_scripts', 'woogosend_enqueue_scripts_frontend' );

/**
 * Print hidden element for the custom address 1 field and address 2 field value
 * in shipping calculator form.
 *
 * @since 1.3
 *
 * @return void
 */
function woogosend_after_shipping_calculator() {
	// Bail early if there is no instances enabled.
	if ( ! woogosend_instances() ) {
		return;
	}

	// Address 1 hidden field.
	if ( apply_filters( 'woocommerce_shipping_calculator_enable_address_1', true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$address_1 = WC()->cart->get_customer()->get_shipping_address();
		?>
		<input type="hidden" id="woogosend-calc-shipping-field-value-address_1" value="<?php echo esc_attr( $address_1 ); ?>" />
		<?php
	}

	// Address 2 hidden field.
	if ( apply_filters( 'woocommerce_shipping_calculator_enable_address_2', true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$address_2 = WC()->cart->get_customer()->get_shipping_address_2();
		?>
		<input type="hidden" id="woogosend-calc-shipping-field-value-address_2" value="<?php echo esc_attr( $address_2 ); ?>" />
		<?php
	}
}
add_action( 'woocommerce_after_shipping_calculator', 'woogosend_after_shipping_calculator' );

/**
 * AJAX handler for Server Side API Key validation.
 *
 * @since 1.3
 *
 * @return void
 */
function woogosend_validate_api_key_server() {
	$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'woogosend_validate_api_key_server' ) ) {
		wp_send_json_error( 'Sorry, your nonce did not verify.', 400 );
	}

	if ( ! $key ) {
		$key = 'InvalidKey';
	}

	$api = new WooGoSend_API();

	$distance = $api->calculate_distance(
		array(
			'key' => $key,
		),
		true
	);

	if ( is_wp_error( $distance ) ) {
		wp_send_json_error( $distance->get_error_message(), 400 );
	}

	wp_send_json_success( $distance );
}
add_action( 'wp_ajax_woogosend_validate_api_key_server', 'woogosend_validate_api_key_server' );
