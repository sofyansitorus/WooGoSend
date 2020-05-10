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
abstract class WooGoSend_Migration {

	/**
	 * WC Shipping instance
	 *
	 * @var WC_Shipping_Method
	 */
	protected $wc_shipping;

	/**
	 * Class constructor
	 *
	 * @param WC_Shipping_Method|false $wc_shipping Shipping instance.
	 */
	public function __construct( $wc_shipping = false ) {
		if ( $wc_shipping ) {
			$this->set_instance( $wc_shipping );
		}
	}

	/**
	 * Get migration options data to update
	 *
	 * @return array
	 */
	public function get_update_options() {
		return array();
	}

	/**
	 * Set WC Shipping instance
	 *
	 * @param WC_Shipping_Method|false $wc_shipping Shipping instance.
	 *
	 * @return void
	 */
	public function set_instance( $wc_shipping ) {
		$this->wc_shipping = $wc_shipping;
	}

	/**
	 * Get migration options data to delete
	 *
	 * @return array
	 */
	public function get_delete_options() {
		return array();
	}

	/**
	 * Plugin migration data version
	 *
	 * @return string
	 */
	abstract public static function get_version();
}
