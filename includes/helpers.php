<?php
/**
 * Helpers file
 *
 * @link       https://github.com/sofyansitorus
 * @since      1.5.0
 *
 * @package    WooGoSend
 * @subpackage WooGoSend/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Check if plugin is active
 *
 * @param string $plugin_file Plugin file name.
 */
function woogosend_is_plugin_active( $plugin_file ) {
	$active_plugins = (array) apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, (array) get_site_option( 'active_sitewide_plugins', array() ) );
	}

	return in_array( $plugin_file, $active_plugins, true ) || array_key_exists( $plugin_file, $active_plugins );
}

/**
 * Get i18n strings
 *
 * @param string $key Strings key.
 * @param string $default Default value.
 * @return mixed
 */
function woogosend_i18n( $key = '', $default = '' ) {
	$i18n = array(
		'drag_marker'  => __( 'Drag this marker or search your address at the input above.', 'woogosend' ),
		// translators: %s = distance unit.
		'per_unit'     => __( 'Per %s', 'woogosend' ),
		'map_is_error' => __( 'Map is error', 'woogosend' ),
		'latitude'     => __( 'Latitude', 'woogosend' ),
		'longitude'    => __( 'Longitude', 'woogosend' ),
		'buttons'      => array(
			'Get API Key'           => __( 'Get API Key', 'woogosend' ),
			'Back'                  => __( 'Back', 'woogosend' ),
			'Cancel'                => __( 'Cancel', 'woogosend' ),
			'Apply Changes'         => __( 'Apply Changes', 'woogosend' ),
			'Confirm Delete'        => __( 'Confirm Delete', 'woogosend' ),
			'Delete Selected Rates' => __( 'Delete Selected Rates', 'woogosend' ),
			'Add New Rate'          => __( 'Add New Rate', 'woogosend' ),
			'Save Changes'          => __( 'Save Changes', 'woogosend' ),
		),
		'errors'       => array(
			// translators: %s = Field name.
			'field_required'        => __( '%s field is required', 'woogosend' ),
			// translators: %1$s = Field name, %2$d = Minimum field value rule.
			'field_min_value'       => __( '%1$s field value cannot be lower than %2$d', 'woogosend' ),
			// translators: %1$s = Field name, %2$d = Maximum field value rule.
			'field_max_value'       => __( '%1$s field value cannot be greater than %2$d', 'woogosend' ),
			// translators: %s = Field name.
			'field_numeric'         => __( '%s field value must be numeric', 'woogosend' ),
			// translators: %s = Field name.
			'field_numeric_decimal' => __( '%s field value must be numeric and decimal', 'woogosend' ),
			// translators: %s = Field name.
			'field_select'          => __( '%s field value selected is not exists', 'woogosend' ),
			// translators: %1$d = row number, %2$s = error message.
			'table_rate_row'        => __( 'Table rate row #%1$d: %2$s', 'woogosend' ),
			// translators: %1$d = row number, %2$s = error message.
			'duplicate_rate_row'    => __( 'Shipping rules combination duplicate with rate row #%1$d: %2$s', 'woogosend' ),
			'finish_editing_api'    => __( 'Please finish the API Key Editing first!', 'woogosend' ),
			'table_rates_invalid'   => __( 'Table rates data is incomplete or invalid!', 'woogosend' ),
		),
		'Save Changes' => __( 'Save Changes', 'woogosend' ),
		'Add New Rate' => __( 'Add New Rate', 'woogosend' ),
	);

	if ( ! empty( $key ) && is_string( $key ) ) {
		$keys = explode( '.', $key );

		$temp = $i18n;
		foreach ( $keys as $path ) {
			$temp = &$temp[ $path ];
		}

		return is_null( $temp ) ? $default : $temp;
	}

	return $i18n;
}

/**
 * Get shipping method instances
 *
 * @since 2.0
 *
 * @param bool $enabled_only Filter to includes only enabled instances.
 * @return array
 */
function woogosend_instances( $enabled_only = true ) {
	$instances = array();

	$zone_data_store = new WC_Shipping_Zone_Data_Store();

	$shipping_methods = $zone_data_store->get_methods( '0', $enabled_only );

	if ( $shipping_methods ) {
		foreach ( $shipping_methods as $shipping_method ) {
			if ( WOOGOSEND_METHOD_ID !== $shipping_method->method_id ) {
				continue;
			}

			$instances[] = array(
				'zone_id'     => 0,
				'method_id'   => $shipping_method->method_id,
				'instance_id' => $shipping_method->instance_id,
			);
		}
	}

	$zones = WC_Shipping_Zones::get_zones();

	if ( ! empty( $zones ) ) {
		foreach ( $zones as $zone ) {
			$shipping_methods = $zone_data_store->get_methods( $zone['id'], $enabled_only );
			if ( $shipping_methods ) {
				foreach ( $shipping_methods as $shipping_method ) {
					if ( WOOGOSEND_METHOD_ID !== $shipping_method->method_id ) {
						continue;
					}

					$instances[] = array(
						'zone_id'     => 0,
						'method_id'   => $shipping_method->method_id,
						'instance_id' => $shipping_method->instance_id,
					);
				}
			}
		}
	}

	return apply_filters( 'woogosend_instances', $instances );
}

/**
 * Inserts a new key/value before the key in the array.
 *
 * @since 2.0.7
 *
 * @param string $before_key The key to insert before.
 * @param array  $array An array to insert in to.
 * @param string $new_key The new key to insert.
 * @param mixed  $new_value The new value to insert.
 *
 * @return array
 */
function woogosend_array_insert_before( $before_key, $array, $new_key, $new_value ) {
	if ( ! array_key_exists( $before_key, $array ) ) {
		return $array;
	}

	$new = array();

	foreach ( $array as $k => $value ) {
		if ( $k === $before_key ) {
			$new[ $new_key ] = $new_value;
		}

		$new[ $k ] = $value;
	}

	return $new;
}

/**
 * Inserts a new key/value after the key in the array.
 *
 * @since 2.0.7
 *
 * @param string $after_key The key to insert after.
 * @param array  $array An array to insert in to.
 * @param string $new_key The new key to insert.
 * @param mixed  $new_value The new value to insert.
 *
 * @return array
 */
function woogosend_array_insert_after( $after_key, $array, $new_key, $new_value ) {
	if ( ! array_key_exists( $after_key, $array ) ) {
		return $array;
	}

	$new = array();

	foreach ( $array as $k => $value ) {
		$new[ $k ] = $value;

		if ( $k === $after_key ) {
			$new[ $new_key ] = $new_value;
		}
	}

	return $new;
}

/**
 * Check is in development environment.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function woogosend_is_dev_env() {
	if ( defined( 'WOOGOSEND_DEV' ) && WOOGOSEND_DEV ) {
		return true;
	}

	if ( function_exists( 'getenv' ) && getenv( 'WOOGOSEND_DEV' ) ) {
		return true;
	}

	return false;
}

if ( ! function_exists( 'woogosend_autoload' ) ) :
	/**
	 * Class autoload
	 *
	 * @since 1.0.0
	 *
	 * @param string $class Class name.
	 *
	 * @return void
	 */
	function woogosend_autoload( $class ) {
		$class = strtolower( $class );

		if ( strpos( $class, 'woogosend' ) !== 0 ) {
			return;
		}

		require_once WOOGOSEND_PATH . 'includes/classes/class-' . str_replace( '_', '-', $class ) . '.php';
	}
endif;

if ( ! function_exists( 'woogosend_is_calc_shipping' ) ) :
	/**
	 * Check if current request is shipping calculator form.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	function woogosend_is_calc_shipping() {
		$field  = 'woocommerce-shipping-calculator-nonce';
		$action = 'woocommerce-shipping-calculator';

		if ( isset( $_POST['calc_shipping'], $_POST[ $field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action ) ) {
			return true;
		}

		return false;
	}
endif;

if ( ! function_exists( 'woogosend_calc_shipping_field_value' ) ) :
	/**
	 * Get calculated shipping for fields value.
	 *
	 * @since 2.1.3
	 *
	 * @param string $input_name Input name.
	 *
	 * @return mixed|bool False on failure
	 */
	function woogosend_calc_shipping_field_value( $input_name ) {
		$nonce_field  = 'woocommerce-shipping-calculator-nonce';
		$nonce_action = 'woocommerce-shipping-calculator';

		if ( isset( $_POST['calc_shipping'], $_POST[ $input_name ], $_POST[ $nonce_field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), $nonce_action ) ) {
			return sanitize_text_field( wp_unslash( $_POST[ $input_name ] ) );
		}

		return false;
	}
endif;

if ( ! function_exists( 'woogosend_shipping_fields' ) ) :
	/**
	 * Get shipping fields.
	 *
	 * @since 2.1.5
	 *
	 * @return array
	 */
	function woogosend_shipping_fields() {
		$different_address = ! empty( $_POST['ship_to_different_address'] ) && ! wc_ship_to_billing_address_only(); // phpcs:ignore WordPress
		$address_type      = $different_address ? 'shipping' : 'billing';
		$checkout_fields   = WC()->checkout->get_checkout_fields( $address_type );

		if ( ! $checkout_fields ) {
			return false;
		}

		return array(
			'type' => $address_type,
			'data' => $checkout_fields,
		);
	}
endif;
