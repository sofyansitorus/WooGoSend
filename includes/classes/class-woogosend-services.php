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
abstract class WooGoSend_Services {

	/**
	 * Setting fields data
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Default service settings data
	 *
	 * @var array
	 */
	protected $default_settings = array();

	/**
	 * Default service settings data
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Get service slug ID
	 *
	 * @return string
	 */
	abstract public function get_slug();

	/**
	 * Get service label
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Get service setting description
	 *
	 * @return string
	 */
	public function get_description() {
		// translators: %s is URL to Gojek's website.
		return sprintf( __( '<a href="%s" target="_blank">Click here</a> for more info about GoSend.', 'woogosend' ), 'https://www.go-jek.com/go-send/' );
	}

	/**
	 * Get setting fields data
	 *
	 * @var array
	 */
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

	/**
	 * Get setting fields raw data
	 *
	 * @return array
	 */
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
				'orig_type'         => 'price',
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
				'orig_type'         => 'price',
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
				'orig_type'         => 'price',
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
			'multiple_drivers'    => array(
				'title'       => __( 'Multiple Drivers', 'woogosend' ),
				'label'       => __( 'Enable', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'checkbox',
				'description' => __( 'Split shipping into several drivers if the total package weight and dimensions exceeded the limit. Will be handy for bulk quantity orders.', 'woogosend' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Calculate the shipping cost
	 *
	 * @param integer $distance   Shipping distance.
	 * @param array   $envelope   Package dimensions, weight & quantity info.
	 * @param array   $settings   Plugin settings data.
	 *
	 * @return array|WP_Error WP_Error object on failure.
	 */
	public function calculate_cost( $distance, $envelope, $settings = array() ) {
		if ( $settings ) {
			$this->set_settings( $settings );
		}

		// Validate distance.
		$max_distance = $this->get_setting( 'max_distance', true );

		if ( $max_distance && $distance > $max_distance ) {
			// translators: %1$s is service ID.
			return new WP_Error( 'max_distance_exceeded', sprintf( __( 'Shipping distance exceeded the maximum setting: %1$s', 'woogosend' ), $this->get_slug() ) );
		}

		$max_weight    = $this->get_setting( 'max_weight', true );
		$max_width     = $this->get_setting( 'max_width', true );
		$max_length    = $this->get_setting( 'max_length', true );
		$max_height    = $this->get_setting( 'max_height', true );
		$max_dimension = $max_width * $max_length * $max_height;

		$envelope_quantity  = isset( $envelope['quantity'] ) ? (int) $envelope['quantity'] : 0;
		$envelope_weight    = isset( $envelope['weight'] ) ? (int) $envelope['weight'] : 0;
		$envelope_width     = isset( $envelope['width'] ) ? (int) $envelope['width'] : 0;
		$envelope_length    = isset( $envelope['length'] ) ? (int) $envelope['length'] : 0;
		$envelope_height    = isset( $envelope['height'] ) ? (int) $envelope['height'] : 0;
		$envelope_dimension = $envelope_width * $envelope_length * $envelope_height;

		// Calculate number of drivers needed.
		$drivers_count_weight    = ceil( ( $envelope_weight / $max_weight ) );
		$drivers_count_dimension = ceil( ( $envelope_dimension / $max_dimension ) );
		$drivers_count           = max( $drivers_count_weight, $drivers_count_dimension );

		// Validate package dimensions, weight & quantity.
		$multiple_drivers = 'yes' === $this->get_setting( 'multiple_drivers', true );

		if ( $envelope_quantity < 2 || $drivers_count > $envelope_quantity ) {
			$multiple_drivers = false;
		}

		if ( ! $multiple_drivers ) {
			// Validate package weight.
			if ( $envelope_weight > $max_weight ) {
				// translators: %1$s is service ID.
				return new WP_Error( 'max_weight_exceeded', sprintf( __( 'Package weight exceeded the maximum setting: %1$s', 'woogosend' ), $this->get_slug() ) );
			}

			// Validate package dimension.
			if ( $envelope_dimension > $max_dimension ) {
				// translators: %1$s is service ID.
				return new WP_Error( 'max_dimension_exceeded', sprintf( __( 'Package dimension exceeded the maximum setting: %1$s', 'woogosend' ), $this->get_slug() ) );
			}
		}

		// Calculate the cost.
		$min_cost            = absint( $this->get_setting( 'min_cost', true ) );
		$max_cost            = absint( $this->get_setting( 'max_cost', true ) );
		$per_km_cost         = absint( $this->get_setting( 'per_km_cost', true ) );
		$per_km_min_distance = absint( $this->get_setting( 'per_km_min_distance', true ) );

		$total = 0;

		if ( $per_km_cost && $distance > $per_km_min_distance ) {
			$total += ( ( $distance - $per_km_min_distance ) * $per_km_cost );
		}

		if ( $min_cost && $total < $min_cost ) {
			$total = $min_cost;
		} elseif ( $max_cost && $total > $max_cost ) {
			$total = $max_cost;
		}

		if ( $drivers_count > 1 ) {
			$total = $total * $drivers_count;
		}

		return array(
			'total'         => $total,
			'drivers_count' => $drivers_count,
		);
	}

	/**
	 * Get setting field key
	 *
	 * @param string $key Field key.
	 * @return string
	 */
	public function get_field_key( $key ) {
		return $key . '_' . $this->get_slug();
	}

	/**
	 * Get default setting field value
	 *
	 * @param string $key Field key.
	 * @return mixed
	 */
	public function get_default_setting( $key ) {
		if ( isset( $this->default_settings[ $key ] ) ) {
			return $this->default_settings[ $key ];
		}

		return null;
	}

	/**
	 * Get settings field value
	 *
	 * @param string $key Field key.
	 * @param bool   $load_default Is load default setting.
	 * @return mixed
	 */
	public function get_setting( $key, $load_default = false ) {
		$field_key = $this->get_field_key( $key );

		if ( isset( $this->settings[ $field_key ] ) ) {
			return $this->settings[ $field_key ];
		}

		if ( $load_default ) {
			return $this->get_default_setting( $key );
		}

		return null;
	}

	/**
	 * Get all settings field value
	 *
	 * @param bool $load_default Is load default setting.
	 * @return array
	 */
	public function get_settings( $load_default = false ) {
		$settings = array();

		foreach ( array_keys( $this->get_fields_raw() ) as $key ) {
			$settings[ $key ] = $this->get_setting( $key, $load_default );
		}

		return $settings;
	}

	/**
	 * Get settings field value
	 *
	 * @param array $settings Settings data.
	 * @return void
	 */
	public function set_settings( $settings ) {
		$this->settings = $settings;
	}
}
