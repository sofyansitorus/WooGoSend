<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/sofyansitorus
 * @since      1.0.0
 *
 * @package    WooGoSend
 * @subpackage WooGoSend/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WooGoSend
 * @subpackage WooGoSend/includes
 * @author     Sofyan Sitorus <sofyansitorus@gmail.com>
 */
class WooGoSend_Services_Same_Day extends WooGoSend_Services {

	/**
	 * Default service settings data
	 *
	 * @var array
	 */
	protected $default_settings = array(
		'per_km_cost'         => 2500,
		'per_km_min_distance' => 15,
		'min_cost'            => 15000,
		'max_cost'            => 25000,
		'max_weight'          => 7,
		'max_width'           => 40,
		'max_length'          => 40,
		'max_height'          => 17,
		'max_distance'        => 40,
	);

	/**
	 * Get service slug ID
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'same_day';
	}

	/**
	 * Get service label
	 *
	 * @return string
	 */
	public function get_label() {
		return 'GoSend Same Day';
	}
}
