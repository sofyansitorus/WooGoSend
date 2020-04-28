<?php
/**
 * The file that defines the services plugin class
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
 * The services plugin class.
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
abstract class WooGoSend_Services {
	protected $fields           = array();
	protected $default_settings = array();

	abstract public function get_slug();
	abstract public function get_label();

	public function get_description() {
		return sprintf( __( '<a href="%s" target="_blank">Click here</a> for more info about GoSend.', 'woogosend' ), 'https://www.go-jek.com/go-send/' );
	}

	public function get_fields() {
		if ( ! $this->fields ) {
			foreach ( $this->get_fields_raw() as $key => $field ) {
				if ( ! isset( $field['default'] ) ) {
					if ( 'title' === $key ) {
						$field['default'] = $this->get_label();
					} else {
						$field['default'] = $this->get_default_setting( $key );
					}
				}

				$this->fields[ $this->get_field_key( $key ) ] = $field;
			}
		}

		return $this->fields;
	}

	public function get_fields_raw() {
		return array(
			'enable'              => array(
				'title'       => __( 'Enable', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'checkbox',
				'description' => __( 'Enable delivery service.', 'woogosend' ),
				'desc_tip'    => true,
				'class'       => 'woogosend-toggle-service',
			),
			'multiple_drivers'    => array(
				'title'       => __( 'Multiple Drivers', 'woogosend' ),
				'label'       => __( 'Enable', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'checkbox',
				'description' => __( 'Split shipping into several drivers if the total package weight and dimensions exceeded the limit. Will be handy for bulk quantity orders.', 'woogosend' ),
				'desc_tip'    => true,
			),
			'title'               => array(
				'title'       => __( 'Label', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woogosend' ),
				'desc_tip'    => true,
				'is_required' => true,
			),
			'min_cost'            => array(
				'title'             => __( 'Minimum Cost', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'number',
				'description'       => __( 'Minimum shipping cost that will be billed to customer. Set to zero to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1000',
				),
			),
			'max_cost'            => array(
				'title'             => __( 'Maximum Cost', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'number',
				'description'       => __( 'Maximum shipping cost that will be billed to customer. Set to zero to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1000',
				),
			),
			'per_km_cost'         => array(
				'title'             => __( 'Per Kilometer Cost', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'number',
				'description'       => __( 'Per kilometer rates that will be billed to customer. Set to zero to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1000',
				),
			),
			'per_km_min_distance' => array(
				'title'             => __( 'Per Kilometer Cost Minimum Distance', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'number',
				'description'       => __( 'Minimum distance required to charge the per kilometer cost.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'       => '0',
					'step'      => '1',
					'data-unit' => 'km',
				),
			),
			'max_weight'          => array(
				'title'             => __( 'Maximum Package Weight', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'number',
				'description'       => __( 'Maximum package weight in kilograms that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'       => '1',
					'step'      => '1',
					'data-unit' => 'kg',
				),
			),
			'max_width'           => array(
				'title'             => __( 'Maximum Package Width', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'number',
				'description'       => __( 'Maximum package size width in centimeters that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'       => '1',
					'step'      => '1',
					'data-unit' => 'cm',
				),
			),
			'max_length'          => array(
				'title'             => __( 'Maximum Package Length', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'number',
				'description'       => __( 'Maximum package size length in centimeters that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'       => '1',
					'step'      => '1',
					'data-unit' => 'cm',
				),
			),
			'max_height'          => array(
				'title'             => __( 'Maximum Package Height', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'number',
				'description'       => __( 'Maximum package size height in centimeters that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'       => '1',
					'step'      => '1',
					'data-unit' => 'cm',
				),
			),
			'max_distance'        => array(
				'title'             => __( 'Maximum Distance', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'number',
				'description'       => __( 'Maximum distance in kilometers that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'       => '1',
					'step'      => '1',
					'data-unit' => 'km',
				),
			),
		);
	}

	private function get_field_key( $key ) {
		return $key . '_' . $this->get_slug();
	}

	private function get_default_setting( $key ) {
		if ( isset( $this->default_settings[ $key ] ) ) {
			return $this->default_settings[ $key ];
		}

		return '';
	}
}
