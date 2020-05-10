<?php
/**
 * The file that defines the services plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/sofyansitorus
 *
 * @package    WooGoSend
 * @subpackage WooGoSend/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The services plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    WooGoSend
 * @subpackage WooGoSend/includes
 * @author     Sofyan Sitorus <sofyansitorus@gmail.com>
 */
class WooGoSend_Migration_1_4_0 extends WooGoSend_Migration {

	/**
	 * Plugin migration data version
	 *
	 * @return string
	 */
	public static function get_version() {
		return '1.4.0';
	}

	/**
	 * Get migration options data to update
	 *
	 * @return array
	 */
	public function get_update_options() {
		$api_key        = isset( $this->wc_shipping->instance_settings['api_key'] ) ? $this->wc_shipping->instance_settings['api_key'] : '';
		$api_key_split  = isset( $this->wc_shipping->instance_settings['api_key_split'] ) ? $this->wc_shipping->instance_settings['api_key_split'] : 'no';
		$api_key_server = isset( $this->wc_shipping->instance_settings['api_key_server'] ) ? $this->wc_shipping->instance_settings['api_key_server'] : '';

		if ( 'yes' === $api_key_split ) {
			return array(
				'api_key'        => $api_key_server,
				'api_key_picker' => $api_key,
			);
		}

		return array(
			'api_key_picker' => $api_key,
		);
	}

	/**
	 * Get migration options data to delete
	 *
	 * @return array
	 */
	public function get_delete_options() {
		return array(
			'api_key_split',
			'api_key_server',
		);
	}
}
