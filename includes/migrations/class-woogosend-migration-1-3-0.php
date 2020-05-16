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
class WooGoSend_Migration_1_3_0 extends WooGoSend_Migration {

	/**
	 * Plugin migration data version
	 *
	 * @return string
	 */
	public static function get_version() {
		return '1.3.0';
	}

	/**
	 * Get options pair data
	 *
	 * @return array
	 */
	protected function get_options_pair() {
		return array(
			'api_key'            => 'gmaps_api_key',
			'travel_mode'        => 'gmaps_api_mode',
			'route_restrictions' => 'gmaps_api_avoid',
		);
	}
}
