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
	 * Get options pair data
	 *
	 * @return array
	 */
	protected function get_options_pair() {
		return array(
			'api_key_picker' => 'api_key',
			'api_key'        => 'api_key_server',
		);
	}

	/**
	 * Get new option value: api_key
	 *
	 * @return mixed
	 */
	protected function get_new_option__api_key() {
		$api_key_split = isset( $this->wc_shipping->instance_settings['api_key_split'] ) ? $this->wc_shipping->instance_settings['api_key_split'] : 'no';

		if ( 'yes' === $api_key_split && isset( $this->wc_shipping->instance_settings['api_key_server'] ) ) {
			return $this->wc_shipping->instance_settings['api_key_server'];
		}

		return new WP_Error();
	}

	/**
	 * Get new option value: api_key_picker
	 *
	 * @return mixed
	 */
	protected function get_new_option__api_key_picker() {
		if ( isset( $this->wc_shipping->instance_settings['api_key'] ) ) {
			return $this->wc_shipping->instance_settings['api_key'];
		}

		return new WP_Error();
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
