<?php
/**
 * The file that defines the services class
 *
 * @link       https://github.com/sofyansitorus
 * @since      1.3
 *
 * @package    WooGoSend
 * @subpackage WooGoSend/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * The services class.
 *
 * @since      1.3
 * @package    WooGoSend
 * @subpackage WooGoSend/includes
 * @author     Sofyan Sitorus <sofyansitorus@gmail.com>
 */
class WooGoSend_Services {

	/**
	 * Get data for services available
	 *
	 * @since 1.3
	 *
	 * @return array
	 */
	public function get_data() {
		return array(
			'instant'  => array(
				'label'   => __( 'Instant Delivery', 'woogosend' ),
				'default' => array(
					'per_km_cost'         => 2500,
					'per_km_min_distance' => 0,
					'min_cost'            => 25000,
					'max_cost'            => 0,
					'max_weight'          => 20,
					'max_width'           => 70,
					'max_length'          => 50,
					'max_height'          => 50,
					'max_distance'        => 40,
				),
			),
			'same_day' => array(
				'label'   => __( 'Same Day Delivery', 'woogosend' ),
				'default' => array(
					'per_km_cost'         => 2500,
					'per_km_min_distance' => 15,
					'min_cost'            => 15000,
					'max_cost'            => 25000,
					'max_weight'          => 7,
					'max_width'           => 40,
					'max_length'          => 40,
					'max_height'          => 17,
					'max_distance'        => 40,
				),
			),
		);
	}

	/**
	 * Get services setings fields
	 *
	 * @since 1.3
	 *
	 * @return array
	 */
	public function get_fields() {
		$raw_fields = array(
			'enable'              => array(
				'title'       => __( 'Enable', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable delivery service.', 'woogosend' ),
				'desc_tip'    => true,
				'class'       => 'woogosend-toggle-service',
			),
			'title'               => array(
				'title'       => __( 'Label', 'woogosend' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woogosend' ),
				'desc_tip'    => true,
				'is_required' => true,
			),
			'per_km_cost'         => array(
				'title'             => __( 'Per Kilometer Cost', 'woogosend' ),
				'type'              => 'number',
				'description'       => __( 'Per kilometer rates that will be billed to customer. Set to zero to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1000',
				),
			),
			'per_km_min_distance' => array(
				'title'             => __( 'Per Kilometer Cost Minimum Distance (km)', 'woogosend' ),
				'type'              => 'number',
				'description'       => __( 'Minimum distance required to charge the per kilometer cost.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1',
				),
			),
			'min_cost'            => array(
				'title'             => __( 'Minimum Cost', 'woogosend' ),
				'type'              => 'number',
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
				'type'              => 'number',
				'description'       => __( 'Maximum shipping cost that will be billed to customer. Set to zero to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '1000',
				),
			),
			'max_weight'          => array(
				'title'             => __( 'Maximum Package Weight', 'woogosend' ) . ' (kg)',
				'type'              => 'number',
				'description'       => __( 'Maximum package weight in kilograms that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			),
			'max_width'           => array(
				'title'             => __( 'Maximum Package Width', 'woogosend' ) . ' (cm)',
				'type'              => 'number',
				'description'       => __( 'Maximum package size width in centimeters that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			),
			'max_length'          => array(
				'title'             => __( 'Maximum Package Length', 'woogosend' ) . ' (cm)',
				'type'              => 'number',
				'description'       => __( 'Maximum package size length in centimeters that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			),
			'max_height'          => array(
				'title'             => __( 'Maximum Package Height', 'woogosend' ) . ' (cm)',
				'type'              => 'number',
				'description'       => __( 'Maximum package size height in centimeters that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			),
			'max_distance'        => array(
				'title'             => __( 'Maximum Distance', 'woogosend' ) . ' (km)',
				'type'              => 'number',
				'description'       => __( 'Maximum distance in kilometers that will be allowed to use this service.', 'woogosend' ),
				'desc_tip'          => true,
				'is_required'       => true,
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			),
			'multiple_drivers'    => array(
				'title'       => __( 'Multiple Drivers', 'woogosend' ),
				'label'       => __( 'Enable', 'woogosend' ),
				'type'        => 'checkbox',
				'description' => __( 'Split shipping into several drivers if the total package weight and dimensions exceeded the limit. Will be handy for bulk quantity orders.', 'woogosend' ),
				'desc_tip'    => true,
			),
		);

		$fields = array();

		foreach ( $this->get_data() as $sevice_key => $sevice ) {
			$service_fields = array();

			foreach ( $raw_fields as $key => $field ) {
				if ( 'title' === $key ) {
					$field['default'] = sprintf( 'GoSend - %s', $sevice['label'] );
				} elseif ( isset( $sevice['default'][ $key ] ) ) {
					$field['default'] = $sevice['default'][ $key ];
				}

				$service_fields[ $key . '_' . $sevice_key ] = $field;
			}

			$fields[ $sevice_key ] = array(
				'label'  => $sevice['label'],
				'fields' => $service_fields,
			);
		}

		return $fields;
	}

	/**
	 * Calculate the shipping cost
	 *
	 * @since 1.3
	 *
	 * @param string  $service_id Service ID.
	 * @param integer $distance   Shipping distance.
	 * @param array   $envelope   Package dimensions, weight & quantity info.
	 * @param array   $settings   Plugin settings data.
	 *
	 * @return array
	 */
	public function calculate_cost( $service_id, $distance, $envelope, $settings ) {
		$data = $this->get_data();

		// Validate Service ID.
		if ( ! array_key_exists( $service_id, $data ) ) {
			// translators: %1$s is service ID.
			return new WP_Error( 'invalid_service_id', sprintf( __( 'Invalid service ID: %1$s', 'woogosend' ), $service_id ) );
		}

		// Validate distance.
		$max_distance = isset( $settings[ 'max_distance_' . $service_id ] ) ? $settings[ 'max_distance_' . $service_id ] : $data['default']['max_distance'];
		if ( $max_distance && $distance > $max_distance ) {
			// translators: %1$s is service ID.
			return new WP_Error( 'max_distance_exceeded', sprintf( __( 'Shipping distance exceeded the maximum setting: %1$s', 'woogosend' ), $service_id ) );
		}

		$max_weight    = isset( $settings[ 'max_weight_' . $service_id ] ) ? $settings[ 'max_weight_' . $service_id ] : $data['default']['max_weight'];
		$max_width     = isset( $settings[ 'max_width_' . $service_id ] ) ? $settings[ 'max_width_' . $service_id ] : $data['default']['max_width'];
		$max_length    = isset( $settings[ 'max_length_' . $service_id ] ) ? $settings[ 'max_length_' . $service_id ] : $data['default']['max_length'];
		$max_height    = isset( $settings[ 'max_height_' . $service_id ] ) ? $settings[ 'max_height_' . $service_id ] : $data['default']['max_height'];
		$max_dimension = $max_width * $max_length * $max_height;

		$envelope_weight    = $envelope['weight'];
		$envelope_width     = $envelope['width'];
		$envelope_length    = $envelope['length'];
		$envelope_height    = $envelope['height'];
		$envelope_dimension = $envelope_width * $envelope_length * $envelope_height;

		// Calculate number of drivers needed.
		$drivers_count_weight    = ceil( ( $envelope_weight / $max_weight ) );
		$drivers_count_dimension = ceil( ( $envelope_dimension / $max_dimension ) );
		$drivers_count           = max( $drivers_count_weight, $drivers_count_dimension );

		// Validate package dimensions, weight & quantity.
		$multiple_drivers = isset( $settings[ 'multiple_drivers_' . $service_id ] ) ? 'yes' === $settings[ 'multiple_drivers_' . $service_id ] : false;

		if ( $envelope['quantity'] < 2 || $drivers_count > $envelope['quantity'] ) {
			$multiple_drivers = false;
		}

		if ( ! $multiple_drivers ) {
			// Validate package weight.
			if ( $envelope_weight > $max_weight ) {
				// translators: %1$s is service ID.
				return new WP_Error( 'max_weight_exceeded', sprintf( __( 'Package weight exceeded the maximum setting: %1$s', 'woogosend' ), $service_id ) );
			}

			// Validate package dimension.
			if ( $envelope_dimension > $max_dimension ) {
				// translators: %1$s is service ID.
				return new WP_Error( 'max_dimension_exceeded', sprintf( __( 'Package dimension exceeded the maximum setting: %1$s', 'woogosend' ), $service_id ) );
			}
		}

		// Calculate the cost.
		$min_cost            = isset( $settings[ 'min_cost_' . $service_id ] ) ? $settings[ 'min_cost_' . $service_id ] : $data['default']['min_cost'];
		$max_cost            = isset( $settings[ 'max_cost_' . $service_id ] ) ? $settings[ 'max_cost_' . $service_id ] : $data['default']['max_cost'];
		$per_km_cost         = isset( $settings[ 'per_km_cost_' . $service_id ] ) ? $settings[ 'per_km_cost_' . $service_id ] : $data['default']['per_km_cost'];
		$per_km_min_distance = isset( $settings[ 'per_km_min_distance_' . $service_id ] ) ? $settings[ 'per_km_min_distance_' . $service_id ] : $data['default']['per_km_min_distance'];

		// Format to aboslute integer.
		$min_cost            = absint( $min_cost );
		$max_cost            = absint( $max_cost );
		$per_km_cost         = absint( $per_km_cost );
		$per_km_min_distance = absint( $per_km_min_distance );

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
}
