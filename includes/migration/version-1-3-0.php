<?php
/**
 * The file that defines the data migration structure
 *
 * @link       https://github.com/sofyansitorus
 * @since      1.3
 *
 * @package    WooGoSend
 * @subpackage WooGoSend/includes/migration
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

return array(
	'version' => '1.3.0',
	'options' => array(
		'gmaps_api_key'   => 'api_key',
		'gmaps_api_mode'  => 'travel_mode',
		'gmaps_api_avoid' => 'route_restrictions',
	),
);
