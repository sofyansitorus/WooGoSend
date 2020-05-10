<?php
/**
 * The file that defines the core plugin class
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
 * The core plugin class.
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
class WooGoSend_Services_Instant extends WooGoSend_Services {

	/**
	 * Default service settings data
	 *
	 * @var array
	 */
	protected $default_settings = array(
		'per_km_cost'         => 2500,
		'per_km_min_distance' => 0,
		'min_cost'            => 25000,
		'max_cost'            => 0,
		'max_weight'          => 20,
		'max_width'           => 70,
		'max_length'          => 50,
		'max_height'          => 50,
		'max_distance'        => 40,
	);

	/**
	 * Get service slug ID
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'instant';
	}

	/**
	 * Get service label
	 *
	 * @return string
	 */
	public function get_label() {
		return 'GoSend Instant';
	}
}
