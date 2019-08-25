<?php
/**
 * The file that defines the api request class
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
 * The API request class.
 *
 * @since      1.3
 * @package    WooGoSend
 * @subpackage WooGoSend/includes
 * @author     Sofyan Sitorus <sofyansitorus@gmail.com>
 */
class WooGoSend_API {

	/**
	 * URL of Google Maps Distance Matrix API
	 *
	 * @since 1.3
	 *
	 * @var string
	 */
	private $api_url = 'https://maps.googleapis.com/maps/api/distancematrix/json';

	/**
	 * Making HTTP request to Google Maps Distance Matrix API
	 *
	 * @since 1.3
	 *
	 * @throws Exception If error happen.
	 *
	 * @param array $args          Custom arguments for $settings and $package data.
	 * @param bool  $is_validation Is the request for validation purpose or not.
	 *
	 * @return array
	 */
	public function calculate_distance( $args = array(), $is_validation = false ) {
		try {
			$args = wp_parse_args(
				$args,
				array(
					'origins'      => $is_validation ? array( WOOGOSEND_DEFAULT_LAT, WOOGOSEND_DEFAULT_LNG ) : '',
					'destinations' => $is_validation ? array( WOOGOSEND_TEST_LAT, WOOGOSEND_TEST_LNG ) : '',
					'key'          => '',
					'avoid'        => '',
					'language'     => get_locale(),
					'units'        => 'metric',
					'mode'         => 'driving',
				)
			);

			foreach ( $args as $key => $value ) {
				if ( is_array( $value ) ) {
					$args[ $key ] = implode( ',', $value );
				}
			}

			$request_url = add_query_arg( $args, $this->api_url );

			$raw_response = wp_remote_get( esc_url_raw( $request_url ) );

			// Check if HTTP request is error.
			if ( is_wp_error( $raw_response ) ) {
				throw new Exception( $raw_response->get_error_message() );
			}

			$response_body = wp_remote_retrieve_body( $raw_response );

			// Check if API response is empty.
			if ( empty( $response_body ) ) {
				throw new Exception( __( 'API response is empty', 'woogosend' ) );
			}

			// Decode API response body.
			$response_data = json_decode( $response_body, true );

			// Check if JSON data is valid.
			$json_last_error_msg = json_last_error_msg();

			if ( $json_last_error_msg && 'No error' !== $json_last_error_msg ) {
				// translators: %s is json decoding error message.
				throw new Exception( sprintf( __( 'Error occured while decoding API response: %s', 'woogosend' ), $json_last_error_msg ) );
			}

			// Check API response is OK.
			$status = isset( $response_data['status'] ) ? $response_data['status'] : '';
			if ( 'OK' !== $status ) {
				$error_message = __( 'API Response Error', 'woogosend' ) . ': ' . $status;
				if ( isset( $response_data['error_message'] ) ) {
					$error_message .= ' - ' . $response_data['error_message'];
				}

				throw new Exception( $error_message );
			}

			$errors  = array();
			$results = array();

			// Get the shipping distance.
			foreach ( $response_data['rows'] as $row ) {
				foreach ( $row['elements'] as $element ) {
					// Check element status code.
					if ( 'OK' !== $element['status'] ) {
						$errors[] = $element['status'];
						continue;
					}

					$results[] = array(
						'distance'      => $element['distance']['value'],
						'distance_text' => $element['distance']['text'],
						'duration'      => $element['duration']['value'],
						'duration_text' => $element['duration']['text'],
					);
				}
			}

			if ( ! empty( $results ) ) {
				return $results;
			}

			if ( ! empty( $errors ) ) {
				$error_template = array(
					'NOT_FOUND'                 => __( 'Origin and/or destination of this pairing could not be geocoded', 'woogosend' ),
					'ZERO_RESULTS'              => __( 'No route could be found between the origin and destination', 'woogosend' ),
					'MAX_ROUTE_LENGTH_EXCEEDED' => __( 'Requested route is too long and cannot be processed', 'woogosend' ),
				);

				foreach ( $errors as $error_key ) {
					if ( isset( $error_template[ $error_key ] ) ) {
						throw new Exception( __( 'API Response Error', 'woogosend' ) . ': ' . $error_template[ $error_key ] );
					}
				}
			}

			throw new Exception( __( 'API Response Error', 'woogosend' ) . ': ' . __( 'No results found', 'woogosend' ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'api_request', $e->getMessage() );
		}
	}
}
