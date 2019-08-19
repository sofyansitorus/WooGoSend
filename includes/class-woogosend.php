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
class WooGoSend extends WC_Shipping_Method {
	/**
	 * URL of Google Maps Distance Matrix API
	 *
	 * @since    1.0.0
	 * @var string
	 */
	private $google_api_url = 'https://maps.googleapis.com/maps/api/distancematrix/json';

	/**
	 * Constructor for your shipping class
	 *
	 * @since    1.0.0
	 * @param int $instance_id ID of shipping method instance.
	 */
	public function __construct( $instance_id = 0 ) {
		// ID for your shipping method. Should be unique.
		$this->id = WOOGOSEND_METHOD_ID;

		// Title shown in admin.
		$this->method_title = WOOGOSEND_METHOD_TITLE;

		// Description shown in admin.
		$this->method_description = __( 'Per kilometer shipping rates calculator for GoSend Go-Jek Indonesia courier.', 'woogosend' );

		$this->enabled = $this->get_option( 'enabled' );

		$this->instance_id = absint( $instance_id );

		$this->supports = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();
	}

	/**
	 * Init settings
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function init() {
		// Load the settings API.
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		// Define user set variables.
		$this->title                   = $this->get_option( 'title', 'GoSend' );
		$this->gmaps_api_key           = $this->get_option( 'gmaps_api_key' );
		$this->origin_lat              = $this->get_option( 'origin_lat' );
		$this->origin_lng              = $this->get_option( 'origin_lng' );
		$this->gmaps_api_units         = $this->get_option( 'gmaps_api_units', 'metric' );
		$this->gmaps_api_mode          = $this->get_option( 'gmaps_api_mode', 'driving' );
		$this->gmaps_api_avoid         = $this->get_option( 'gmaps_api_avoid' );
		$this->tax_status              = $this->get_option( 'tax_status' );
		$this->enable_fallback_request = $this->get_option( 'enable_fallback_request', 'no' );
		$this->enable_instant          = $this->get_option( 'enable_instant' );
		$this->title_instant           = $this->get_option( 'title_instant', 'Instant' );
		$this->min_cost_instant        = $this->get_option( 'min_cost_instant' );
		$this->per_km_cost_instant     = $this->get_option( 'per_km_cost_instant' );
		$this->max_weight_instant      = $this->get_option( 'max_weight_instant' );
		$this->max_width_instant       = $this->get_option( 'max_width_instant' );
		$this->max_length_instant      = $this->get_option( 'max_length_instant' );
		$this->max_height_instant      = $this->get_option( 'max_height_instant' );
		$this->max_distance_instant    = $this->get_option( 'max_distance_instant' );
		$this->show_distance_instant   = $this->get_option( 'show_distance_instant' );
		$this->enable_same_day         = $this->get_option( 'enable_same_day' );
		$this->title_same_day          = $this->get_option( 'title_same_day', 'Same Day' );
		$this->min_cost_same_day       = $this->get_option( 'min_cost_same_day' );
		$this->max_cost_same_day       = $this->get_option( 'max_cost_same_day' );
		$this->max_weight_same_day     = $this->get_option( 'max_weight_same_day' );
		$this->max_width_same_day      = $this->get_option( 'max_width_same_day' );
		$this->max_length_same_day     = $this->get_option( 'max_length_same_day' );
		$this->max_height_same_day     = $this->get_option( 'max_height_same_day' );
		$this->max_distance_same_day   = $this->get_option( 'max_distance_same_day' );
		$this->show_distance_same_day  = $this->get_option( 'show_distance_same_day' );

		// Save settings in admin if you have any defined.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		// Check if this shipping method is availbale for current order.
		add_filter( 'woocommerce_shipping_' . $this->id . '_is_available', array( $this, 'check_is_available' ), 10, 2 );
	}

	/**
	 * Init form fields.
	 *
	 * @since    1.0.0
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'                     => array(
				'title'       => __( 'Title', 'woogosend' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woogosend' ),
				'default'     => 'GoSend',
				'desc_tip'    => true,
			),
			'gmaps_api_key'             => array(
				'title'       => __( 'Google Maps Distance Matrix API', 'woogosend' ),
				'type'        => 'text',
				'description' => __( 'This plugin require Google Maps Distance Matrix API Services enabled in your Google API Console. <a href="https://developers.google.com/maps/documentation/distance-matrix/get-api-key" target="_blank">Click here</a> to get API Key and to enable the services.', 'woogosend' ),
				'default'     => '',
			),
			'origin'                    => array(
				'title' => __( 'Store Location', 'woogosend' ),
				'type'  => 'address_picker',
			),
			'origin_lat'                => array(
				'title' => __( 'Store Location Latitude', 'woogosend' ),
				'type'  => 'coordinates',
			),
			'origin_lng'                => array(
				'title' => __( 'Store Location Logitude', 'woogosend' ),
				'type'  => 'coordinates',
			),
			'gmaps_api_mode'            => array(
				'title'       => __( 'Travel Mode', 'woogosend' ),
				'type'        => 'select',
				'description' => __( 'Google Maps Distance Matrix API travel mode parameter.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => 'driving',
				'options'     => array(
					'driving'   => __( 'Driving', 'woogosend' ),
					'walking'   => __( 'Walking', 'woogosend' ),
					'bicycling' => __( 'Bicycling', 'woogosend' ),
				),
			),
			'gmaps_api_avoid'           => array(
				'title'       => __( 'Restrictions', 'woogosend' ),
				'type'        => 'select',
				'description' => __( 'Google Maps Distance Matrix API restrictions parameter.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => 'driving',
				'options'     => array(
					''         => __( 'None', 'woogosend' ),
					'tolls'    => __( 'Avoid Tolls', 'woogosend' ),
					'highways' => __( 'Avoid Highways', 'woogosend' ),
					'ferries'  => __( 'Avoid Ferries', 'woogosend' ),
					'indoor'   => __( 'Avoid Indoor', 'woogosend' ),
				),
			),
			'tax_status'                => array(
				'title'   => __( 'Tax status', 'woogosend' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __( 'Taxable', 'woogosend' ),
					'none'    => __( 'None', 'woogosend' ),
				),
			),
			'enable_fallback_request'   => array(
				'title'       => __( 'Enable Fallback Request', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'checkbox',
				'description' => __( 'If there is no results for API request using full address, the system will attempt to make another API request to the Google API server without "Address Line 1" parameter. The fallback request will only using "Address Line 2", "City", "State/Province", "Postal Code" and "Country" parameters.', 'woogosend' ),
				'desc_tip'    => true,
			),
			'instant_title'             => array(
				'title'       => __( 'Instant Delivery Service Options', 'woogosend' ),
				'type'        => 'title',
				'description' => __( '<a href="https://www.go-jek.com/go-send/" target="_blank">Click here</a> for more info about GoSend.', 'woogosend' ),
			),
			'enable_instant'            => array(
				'title'       => __( 'Enable', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable Instant delivery service.', 'woogosend' ),
				'desc_tip'    => true,
				'class'       => 'woogosend-toggle-service',
			),
			'title_instant'             => array(
				'title'       => __( 'Label', 'woogosend' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woogosend' ),
				'default'     => 'Instant',
				'desc_tip'    => true,
			),
			'min_cost_instant'          => array(
				'title'       => __( 'Minimum Cost', 'woogosend' ),
				'type'        => 'price',
				'description' => __( 'Minimum shipping cost that will be billed to customer.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '25000',
			),
			'per_km_cost_instant'       => array(
				'title'       => __( 'Per Kilometer Cost', 'woogosend' ),
				'type'        => 'price',
				'description' => __( 'Per kilometer rates that will be billed to customer.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '2500',
			),
			'max_weight_instant'        => array(
				'title'             => __( 'Maximum Package Weight', 'woogosend' ) . ' (kg)',
				'type'              => 'number',
				'description'       => __( 'Maximum package weight in kilograms that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '20',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'max_width_instant'         => array(
				'title'             => __( 'Maximum Package Width', 'woogosend' ) . ' (cm)',
				'type'              => 'number',
				'description'       => __( 'Maximum package size width in centimeters that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '70',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'max_length_instant'        => array(
				'title'             => __( 'Maximum Package Length', 'woogosend' ) . ' (cm)',
				'type'              => 'number',
				'description'       => __( 'Maximum package size length in centimeters that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '50',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'max_height_instant'        => array(
				'title'             => __( 'Maximum Package Height', 'woogosend' ) . ' (cm)',
				'type'              => 'number',
				'description'       => __( 'Maximum package size height in centimeters that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '50',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'max_distance_instant'      => array(
				'title'             => __( 'Maximum Distance', 'woogosend' ) . ' (km)',
				'type'              => 'number',
				'description'       => __( 'Maximum distance in kilometers that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '40',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'show_distance_instant'     => array(
				'title'       => __( 'Show distance', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'checkbox',
				'description' => __( 'Show the distance info to customer during checkout.', 'woogosend' ),
				'desc_tip'    => true,
			),
			'multiple_drivers_instant'  => array(
				'title'       => __( 'Multiple Drivers', 'woogosend' ),
				'label'       => __( 'Enable', 'woogosend' ),
				'type'        => 'checkbox',
				'description' => __( 'Split shipment into several drivers if the package bulk weight and dimensions exceeded the limit.', 'woogosend' ),
				'desc_tip'    => true,
			),
			'same_day_title'            => array(
				'title'       => __( 'Same Day Delivery Service Options', 'woogosend' ),
				'type'        => 'title',
				'description' => __( '<a href="https://www.go-jek.com/go-send/" target="_blank">Click here</a> for more info about GoSend.', 'woogosend' ),
			),
			'enable_same_day'           => array(
				'title'       => __( 'Enable', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable Same Day delivery service.', 'woogosend' ),
				'desc_tip'    => true,
				'class'       => 'woogosend-toggle-service',
			),
			'title_same_day'            => array(
				'title'       => __( 'Label', 'woogosend' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woogosend' ),
				'default'     => 'Same Day',
				'desc_tip'    => true,
			),
			'min_cost_same_day'         => array(
				'title'       => __( 'Minimum Cost', 'woogosend' ),
				'type'        => 'price',
				'description' => __( 'Shipping cost that will be billed to customer for distance under 15 km.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '15000',
			),
			'max_cost_same_day'         => array(
				'title'       => __( 'Maximum Cost', 'woogosend' ),
				'type'        => 'price',
				'description' => __( 'Shipping cost that will be billed to customer for distance start from 15 km.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '20000',
			),
			'max_weight_same_day'       => array(
				'title'             => __( 'Maximum Package Weight', 'woogosend' ) . ' (kg)',
				'type'              => 'number',
				'description'       => __( 'Maximum package weight in kilograms that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '7',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'max_width_same_day'        => array(
				'title'             => __( 'Maximum Package Width', 'woogosend' ) . ' (cm)',
				'type'              => 'number',
				'description'       => __( 'Maximum package size width in centimeters that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '40',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'max_length_same_day'       => array(
				'title'             => __( 'Maximum Package Length', 'woogosend' ) . ' (cm)',
				'type'              => 'number',
				'description'       => __( 'Maximum package size length in centimeters that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '40',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'max_height_same_day'       => array(
				'title'             => __( 'Maximum Package Height', 'woogosend' ) . ' (cm)',
				'type'              => 'number',
				'description'       => __( 'Maximum package size height in centimeters that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '17',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'max_distance_same_day'     => array(
				'title'             => __( 'Maximum Distance', 'woogosend' ) . ' (km)',
				'type'              => 'number',
				'description'       => __( 'Maximum distance in kilometers that will be allowed to use this courier. Leave blank to disable.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '40',
				'custom_attributes' => array( 'min' => '1' ),
			),
			'show_distance_same_day'    => array(
				'title'       => __( 'Show distance', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'checkbox',
				'description' => __( 'Show the distance info to customer during checkout.', 'woogosend' ),
				'desc_tip'    => true,
			),
			'multiple_drivers_same_day' => array(
				'title'       => __( 'Multiple Drivers', 'woogosend' ),
				'label'       => __( 'Enable', 'woogosend' ),
				'type'        => 'checkbox',
				'description' => __( 'Split shipment into several drivers if the package bulk weight and dimensions exceeded the limit.', 'woogosend' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Generate origin settings field.
	 *
	 * @since 1.2.4
	 * @param string $key Settings field key.
	 * @param array  $data Settings field data.
	 */
	public function generate_address_picker_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );

		$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'options'           => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start(); ?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php echo esc_html( $this->get_tooltip_html( $data ) ); ?>
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<input type="hidden" id="map-secret-key" value="<?php echo esc_attr( WOOGOSEND_MAP_SECRET_KEY ); ?>">
				<div id="<?php echo esc_attr( $this->id ); ?>-map-wrapper" class="<?php echo esc_attr( $this->id ); ?>-map-wrapper"></div>
				<div id="<?php echo esc_attr( $this->id ); ?>-lat-lng-wrap">
					<div><label for="<?php echo esc_attr( $field_key ); ?>_lat"><?php echo esc_html( 'Latitude', 'woogosend' ); ?></label><input type="text" id="<?php echo esc_attr( $field_key ); ?>_lat" name="<?php echo esc_attr( $field_key ); ?>_lat" value="<?php echo esc_attr( $this->get_option( $key . '_lat' ) ); ?>" class="origin-coordinates"></div>
					<div><label for="<?php echo esc_attr( $field_key ); ?>_lng"><?php echo esc_html( 'Longitude', 'woogosend' ); ?></label><input type="text" id="<?php echo esc_attr( $field_key ); ?>_lng" name="<?php echo esc_attr( $field_key ); ?>_lng" value="<?php echo esc_attr( $this->get_option( $key . '_lng' ) ); ?>" class="origin-coordinates"></div>
				</div>
				<?php echo wp_kses( $this->get_description_html( $data ), wp_kses_allowed_html( 'post' ) ); ?>
				<script type="text/html" id="tmpl-<?php echo esc_attr( $this->id ); ?>-map-search">
					<input id="{{data.map_search_id}}" class="<?php echo esc_attr( $this->id ); ?>-map-search controls" type="text" placeholder="<?php echo esc_attr( __( 'Search your store location', 'woogosend' ) ); ?>" autocomplete="off" />
				</script>
				<script type="text/html" id="tmpl-<?php echo esc_attr( $this->id ); ?>-map-canvas">
					<div id="{{data.map_canvas_id}}" class="<?php echo esc_attr( $this->id ); ?>-map-canvas"></div>
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate coordinates settings field.
	 *
	 * @since 1.2.4
	 */
	public function generate_coordinates_html() {}

	/**
	 * Validate gmaps_api_key settings field.
	 *
	 * @since    1.0.0
	 * @param  string $key Settings field key.
	 * @param  string $value Posted field value.
	 * @throws Exception If the field value is invalid.
	 * @return string
	 */
	public function validate_gmaps_api_key_field( $key, $value ) {
		try {
			if ( empty( $value ) ) {
				throw new Exception( __( 'API Key is required', 'woogosend' ) );
			}
			return $value;
		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return $this->gmaps_api_key;
		}
	}

	/**
	 * Validate origin_lat settings field.
	 *
	 * @since    1.0.0
	 * @param  string $key Settings field key.
	 * @param  string $value Posted field value.
	 * @throws Exception If the field value is invalid.
	 * @return string
	 */
	public function validate_origin_lat_field( $key, $value ) {
		try {
			if ( empty( $value ) ) {
				throw new Exception( __( 'Store Location Latitude is required', 'woogosend' ) );
			}
			return $value;
		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return $this->origin_lat;
		}
	}

	/**
	 * Validate origin_lng settings field.
	 *
	 * @since    1.0.0
	 * @param  string $key Settings field key.
	 * @param  string $value Posted field value.
	 * @throws Exception If the field value is invalid.
	 * @return string
	 */
	public function validate_origin_lng_field( $key, $value ) {
		try {
			if ( empty( $value ) ) {
				throw new Exception( __( 'Store Location Longitude is required', 'woogosend' ) );
			}
			return $value;
		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return $this->origin_lng;
		}
	}

	/**
	 * Check if this method available
	 *
	 * @since    1.0.0
	 * @param boolean $available Current status is available.
	 * @param array   $package Current order package data.
	 * @return bool
	 */
	public function check_is_available( $available, $package ) {
		if ( ! $available || empty( $package['contents'] ) || empty( $package['destination'] ) ) {
			return false;
		}

		if ( 'ID' !== WC()->countries->get_base_country() ) {
			return false;
		}

		return $available;
	}

	/**
	 * Calculate shipping function.
	 *
	 * @since    1.0.0
	 * @param array $package Package data array.
	 * @throws Exception If the item weight and dimensions exceeded the limit.
	 */
	public function calculate_shipping( $package = array() ) {
		$api_request = $this->api_request( $package );
		if ( ! $api_request ) {
			return;
		}
		$this->calculate_shipping_instant( $package, $api_request );
		$this->calculate_shipping_same_day( $package, $api_request );
	}

	/**
	 * Calculate shipping for instant delivery service.
	 *
	 * @since    1.0.0
	 * @param array $package Package data array.
	 * @param array $api_request API response data array.
	 * @throws Exception If the item weight and dimensions exceeded the limit.
	 */
	private function calculate_shipping_instant( $package = array(), $api_request = array() ) {
		if ( 'yes' !== $this->enable_instant ) {
			return;
		}

		if ( $this->max_distance_instant && $api_request['distance'] > $this->max_distance_instant ) {
			return;
		}

		$shipping_cost_total = 0;

		$multiple_drivers = 'yes' === $this->get_option( 'multiple_drivers_instant', 'no' );

		$drivers_count = $this->calculate_drivers_count( $package['contents'], $this->max_weight_instant, $this->max_width_instant, $this->max_length_instant, $this->max_height_instant, $multiple_drivers );

		if ( ! $drivers_count ) {
			return;
		}

		// translators: Number of drivers needed.
		$drivers_count_text = sprintf( _n( '%s driver', '%s drivers', $drivers_count, 'woogosend' ), $drivers_count );

		$shipping_cost_total = $this->per_km_cost_instant * $api_request['distance'];

		if ( $this->min_cost_instant && $shipping_cost_total < $this->min_cost_instant ) {
			$shipping_cost_total = $this->min_cost_instant;
		}

		$shipping_cost_total *= $drivers_count;
		$title_text           = sprintf( '%s - %s', $this->title, $this->title_instant );

		switch ( $this->show_distance_instant ) {
			case 'yes':
				$label = ( $drivers_count > 1 ) ? sprintf( '%s (%s, %s)', $title_text, $drivers_count_text, $api_request['distance_text'] ) : sprintf( '%s (%s)', $title_text, $api_request['distance_text'] );
				break;
			default:
				$label = ( $drivers_count > 1 ) ? sprintf( '%s (%s)', $title_text, $drivers_count_text, $api_request['distance_text'] ) : $title_text;
				break;
		}

		$rate = array(
			'id'        => $this->get_rate_id( 'instant:' . $drivers_count ),
			'label'     => $label,
			'cost'      => $shipping_cost_total,
			'meta_data' => $api_request,
		);

		// Register the rate.
		$this->add_rate( $rate );

		/**
		 * Developers can add additional rates via action.
		 *
		 * This example shows how you can add an extra rate via custom function:
		 *
		 *      add_filter( 'woocommerce_woogosend_instant_shipping_add_rate', 'add_another_custom_flat_rate', 10, 2 );
		 *
		 *      function add_another_custom_flat_rate( $method, $rate ) {
		 *          $new_rate          = $rate;
		 *          $new_rate['id']    .= ':' . 'custom_rate_name'; // Append a custom ID.
		 *          $new_rate['label'] = 'Rushed Shipping'; // Rename to 'Rushed Shipping'.
		 *          $new_rate['cost']  += 2; // Add $2 to the cost.
		 *
		 *          // Add it to WC.
		 *          $method->add_rate( $new_rate );
		 *      }.
		 */
		do_action( 'woocommerce_' . $this->id . '_instant_shipping_add_rate', $this, $rate ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Calculate shipping same day delivery service.
	 *
	 * @since    1.0.0
	 * @param array $package Package data array.
	 * @param array $api_request API response data array.
	 * @throws Exception If the item weight and dimensions exceeded the limit.
	 */
	private function calculate_shipping_same_day( $package = array(), $api_request = array() ) {
		if ( 'yes' !== $this->enable_same_day ) {
			return;
		}

		if ( $this->max_distance_same_day && $api_request['distance'] > $this->max_distance_same_day ) {
			return;
		}

		$shipping_cost_total = 0;
		$multiple_drivers    = 'yes' === $this->get_option( 'multiple_drivers_same_day', 'no' );

		$drivers_count = $this->calculate_drivers_count( $package['contents'], $this->max_weight_same_day, $this->max_width_same_day, $this->max_length_same_day, $this->max_height_same_day, $multiple_drivers );

		if ( ! $drivers_count ) {
			return;
		}

		// translators: Number of drivers needed.
		$drivers_count_text = sprintf( _n( '%s driver', '%s drivers', $drivers_count, 'woogosend' ), $drivers_count );

		$shipping_cost_total = $this->min_cost_same_day;

		if ( $this->max_cost_same_day && $api_request['distance'] >= 15 ) {
			$shipping_cost_total = $this->max_cost_same_day;
		}

		$shipping_cost_total *= $drivers_count;

		$title_text = sprintf( '%s - %s', $this->title, $this->title_same_day );

		switch ( $this->show_distance_same_day ) {
			case 'yes':
				$label = ( $drivers_count > 1 ) ? sprintf( '%s (%s, %s)', $title_text, $drivers_count_text, $api_request['distance_text'] ) : sprintf( '%s (%s)', $title_text, $api_request['distance_text'] );
				break;
			default:
				$label = ( $drivers_count > 1 ) ? sprintf( '%s (%s)', $title_text, $drivers_count_text, $api_request['distance_text'] ) : $title_text;
				break;
		}

		$rate = array(
			'id'        => $this->get_rate_id( 'same_day:' . $drivers_count ),
			'label'     => $label,
			'cost'      => $shipping_cost_total,
			'meta_data' => $api_request,
		);

		// Register the rate.
		$this->add_rate( $rate );

		/**
		 * Developers can add additional rates via action.
		 *
		 * This example shows how you can add an extra rate via custom function:
		 *
		 *      add_filter( 'woocommerce_woogosend_same_day_shipping_add_rate', 'add_another_custom_flat_rate', 10, 2 );
		 *
		 *      function add_another_custom_flat_rate( $method, $rate ) {
		 *          $new_rate          = $rate;
		 *          $new_rate['id']    .= ':' . 'custom_rate_name'; // Append a custom ID.
		 *          $new_rate['label'] = 'Rushed Shipping'; // Rename to 'Rushed Shipping'.
		 *          $new_rate['cost']  += 2; // Add $2 to the cost.
		 *
		 *          // Add it to WC.
		 *          $method->add_rate( $new_rate );
		 *      }.
		 */
		do_action( 'woocommerce_' . $this->id . '_same_day_shipping_add_rate', $this, $rate ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Calculate number of the drivers needed
	 *
	 * @since    1.1.0
	 * @param array $contents Items in cart.
	 * @param int   $max_weight Max package weight limit.
	 * @param int   $max_width Max package width limit.
	 * @param int   $max_length Max package length limit.
	 * @param int   $max_height Max package height limit.
	 * @param bool  $multiple_drivers Multiple drivers option status.
	 * @throws Exception If the item weight or dimensions exceeded the limit.
	 * @return int
	 */
	private function calculate_drivers_count( $contents, $max_weight = 0, $max_width = 0, $max_length = 0, $max_height = 0, $multiple_drivers = false ) {
		$drivers_count = 1;

		$item_weight_bulk = array();
		$item_width_bulk  = array();
		$item_length_bulk = array();
		$item_height_bulk = array();

		foreach ( $contents as $hash => $item ) {
			// Validate item quantity data.
			$quantity = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 1;
			if ( ! $quantity ) {
				$quantity = 1;
			}

			// Validate item weight data.
			$item_weight = wc_get_weight( $item['data']->get_weight(), 'kg' );
			if ( ! $item_weight || ! is_numeric( $item_weight ) ) {
				$item_weight = 0;
			}
			$item_weight *= $quantity;
			if ( $max_weight && $item_weight > $max_weight ) {
				return;
			}

			// Validate item width data.
			$item_width = wc_get_dimension( $item['data']->get_width(), 'cm' );
			if ( ! $item_width || ! is_numeric( $item_width ) ) {
				$item_width = 0;
			}
			if ( $max_width && $item_width > $max_width ) {
				return;
			}

			// Validate item length data.
			$item_length = wc_get_dimension( $item['data']->get_length(), 'cm' );
			if ( ! $item_length || ! is_numeric( $item_length ) ) {
				$item_length = 0;
			}
			if ( $max_length && $item_length > $max_length ) {
				return;
			}

			// Validate item height data.
			$item_height = wc_get_dimension( $item['data']->get_height(), 'cm' );
			if ( ! $item_height || ! is_numeric( $item_height ) ) {
				$item_height = 0;
			}
			$item_height *= $quantity;
			if ( $max_height && $item_height > $max_height ) {
				return;
			}

			// Try to split the order for several shipments.
			try {
				$item_weight_bulk[] = $item_weight;
				if ( $max_weight && array_sum( $item_weight_bulk ) > $max_weight ) {
					throw new Exception( 'Exceeded maximum package weight', 1 );
				}

				$item_width_bulk[] = $item_width;
				if ( $max_width && max( $item_width_bulk ) > $max_width ) {
					throw new Exception( 'Exceeded maximum package width', 1 );
				}

				$item_length_bulk[] = $item_length;
				if ( $max_length && max( $item_length_bulk ) > $max_length ) {
					throw new Exception( 'Exceeded maximum package length', 1 );
				}

				$item_height_bulk[] = $item_height;
				if ( $max_height && array_sum( $item_height_bulk ) > $max_height ) {
					throw new Exception( 'Exceeded maximum package height', 1 );
				}
			} catch ( Exception $e ) {
				// Return if $multiple_drivers is disabled.
				if ( ! $multiple_drivers ) {
					return;
				}

				$item_weight_bulk = array();
				$item_width_bulk  = array();
				$item_length_bulk = array();
				$item_height_bulk = array();

				$drivers_count++;

				continue;
			}
		}

		return $drivers_count;
	}

	/**
	 * Making HTTP request to Google Maps Distance Matrix API
	 *
	 * @since    1.0.0
	 * @param array $package The cart content data.
	 * @return array
	 */
	private function api_request( $package ) {
		if ( empty( $this->gmaps_api_key ) ) {
			return false;
		}

		$destination_info = $this->get_destination_info( $package['destination'] );
		if ( empty( $destination_info ) ) {
			return false;
		}

		$origin_info = $this->get_origin_info( $package );
		if ( empty( $origin_info ) ) {
			return false;
		}

		$request_url_args = array(
			'key'          => rawurlencode( $this->gmaps_api_key ),
			'mode'         => rawurlencode( $this->gmaps_api_mode ),
			'avoid'        => is_string( $this->gmaps_api_avoid ) ? rawurlencode( $this->gmaps_api_avoid ) : '',
			'units'        => rawurlencode( $this->gmaps_api_units ),
			'language'     => rawurlencode( get_locale() ),
			'origins'      => rawurlencode( implode( ',', $origin_info ) ),
			'destinations' => rawurlencode( implode( ',', $destination_info ) ),
		);

		$transient_key = $this->id . '_api_request_' . md5( wp_json_encode( $request_url_args ) );

		// Check if the data already chached and return it.
		$cached_data = get_transient( $transient_key );

		if ( false !== $cached_data ) {
			$this->show_debug( __( 'Cached key', 'woogosend' ) . ': ' . $transient_key );
			$this->show_debug( __( 'Cached data', 'woogosend' ) . ': ' . wp_json_encode( $cached_data ) );
			return $cached_data;
		}

		$request_url = add_query_arg( $request_url_args, $this->google_api_url );

		$this->show_debug( __( 'API Request URL', 'woogosend' ) . ': ' . str_replace( rawurlencode( $this->gmaps_api_key ), '**********', $request_url ), 'notice' );

		$data = $this->process_api_response( wp_remote_get( esc_url_raw( $request_url ) ) );

		// Try to make fallback request if no results found.
		if ( ! $data && 'yes' === $this->enable_fallback_request && ! empty( $destination_info['address_2'] ) ) {
			unset( $destination_info['address'] );
			$request_url_args['destinations'] = rawurlencode( implode( ',', $destination_info ) );

			$request_url = add_query_arg( $request_url_args, $this->google_api_url );

			$this->show_debug( __( 'API Fallback Request URL', 'woogosend' ) . ': ' . str_replace( rawurlencode( $this->gmaps_api_key ), '**********', $request_url ), 'notice' );

			$data = $this->process_api_response( wp_remote_get( esc_url_raw( $request_url ) ) );
		}

		if ( $data ) {
			delete_transient( $transient_key ); // To make sure the transient data re-created, delete it first.
			set_transient( $transient_key, $data, HOUR_IN_SECONDS ); // Store the data to transient with expiration in 1 hour for later use.

			return $data;
		}

		return false;
	}

	/**
	 * Process API Response.
	 *
	 * @since 1.2.5
	 * @param array $raw_response HTTP API response.
	 * @return array|bool Formatted response data, false on failure.
	 */
	private function process_api_response( $raw_response ) {
		$distance      = 0;
		$distance_text = '';
		$error_message = '';

		// Check if HTTP request is error.
		if ( is_wp_error( $raw_response ) ) {
			$this->show_debug( $raw_response->get_error_message(), 'notice' );
			return false;
		}

		$response_body = wp_remote_retrieve_body( $raw_response );

		// Check if API response is empty.
		if ( empty( $response_body ) ) {
			$this->show_debug( __( 'API response is empty', 'woogosend' ), 'notice' );
			return false;
		}

		$response_data = json_decode( $response_body, true );

		// Check if JSON data is valid.
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			if ( function_exists( 'json_last_error_msg' ) ) {
				$this->show_debug( __( 'Error while decoding API response', 'woogosend' ) . ': ' . json_last_error_msg(), 'notice' );
			}
			return false;
		}

		// Check API response is OK.
		$status = isset( $response_data['status'] ) ? $response_data['status'] : '';
		if ( 'OK' !== $status ) {
			$error_message = __( 'API Response Error', 'woogosend' ) . ': ' . $status;
			if ( isset( $response_data['error_message'] ) ) {
				$error_message .= ' - ' . $response_data['error_message'];
			}
			$this->show_debug( $error_message, 'notice' );
			return false;
		}

		$element_lvl_errors = array(
			'NOT_FOUND'                 => __( 'Origin and/or destination of this pairing could not be geocoded', 'woogosend' ),
			'ZERO_RESULTS'              => __( 'No route could be found between the origin and destination', 'woogosend' ),
			'MAX_ROUTE_LENGTH_EXCEEDED' => __( 'Requested route is too long and cannot be processed', 'woogosend' ),
		);

		// Get the shipping distance.
		foreach ( $response_data['rows'] as $row ) {

			// Berak the loop is distance is defined.
			if ( $distance ) {
				break;
			}

			foreach ( $row['elements'] as $element ) {

				// Berak the loop is distance is defined.
				if ( $distance ) {
					break;
				}

				switch ( $element['status'] ) {
					case 'OK':
						if ( isset( $element['distance']['value'] ) && ! empty( $element['distance']['value'] ) ) {
							$distance      = $this->convert_m( $element['distance']['value'] );
							$distance_text = $element['distance']['text'];
						}
						break;
					default:
						$error_message = __( 'API Response Error', 'woogosend' ) . ': ' . $element['status'];
						if ( isset( $element_lvl_errors[ $element['status'] ] ) ) {
							$error_message .= ' - ' . $element_lvl_errors[ $element['status'] ];
						}
						break;
				}
			}
		}

		if ( ! $distance ) {
			if ( $error_message ) {
				$this->show_debug( $error_message, 'notice' );
			}
			return false;
		}

		return array(
			'distance'      => $distance,
			'distance_text' => $distance_text,
			'response'      => $response_data,
		);
	}

	/**
	 * Get shipping origin info
	 *
	 * @since    1.0.0
	 * @param array $package The cart content data.
	 * @return array
	 */
	private function get_origin_info( $package ) {
		$origin_info = array();

		if ( ! empty( $this->origin_lat ) && ! empty( $this->origin_lng ) ) {
			$origin_info['lat'] = $this->origin_lat;
			$origin_info['lng'] = $this->origin_lng;
		}

		/**
		 * Developers can modify the origin info via filter hooks.
		 *
		 * @since 1.0.1
		 *
		 * This example shows how you can modify the shipping origin info via custom function:
		 *
		 *      add_filter( 'woocommerce_woogosend_shipping_origin_info', 'modify_shipping_origin_info', 10, 2 );
		 *
		 *      function modify_shipping_origin_info( $origin_info, $package ) {
		 *          return '1600 Amphitheatre Parkway,Mountain View,CA,94043';
		 *      }
		 */
		return apply_filters( 'woocommerce_' . $this->id . '_shipping_origin_info', $origin_info, $package ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Get shipping destination info
	 *
	 * @since    1.0.0
	 * @param array $data Shipping destination data in associative array format: address, city, state, postcode, country.
	 * @return array
	 */
	private function get_destination_info( $data ) {
		$destination_info = array();

		$keys = array( 'address', 'address_2', 'city', 'state', 'postcode', 'country' );

		// Remove destination field keys for shipping calculator request.
		if ( isset( $_POST['calc_shipping'], $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-cart' ) ) {
			$keys_remove = array( 'address', 'address_2' );
			if ( ! apply_filters( 'woocommerce_shipping_calculator_enable_city', false ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				array_push( $keys_remove, 'city' );
			}
			if ( ! apply_filters( 'woocommerce_shipping_calculator_enable_postcode', false ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				array_push( $keys_remove, 'postcode' );
			}
			$keys = array_diff( $keys, $keys_remove );
		}

		$country_code = false;

		foreach ( $keys as $key ) {
			if ( ! isset( $data[ $key ] ) || empty( $data[ $key ] ) ) {
				continue;
			}
			switch ( $key ) {
				case 'country':
					if ( empty( $country_code ) ) {
						$country_code = $data[ $key ];
					}
					$full_country             = isset( WC()->countries->countries[ $country_code ] ) ? WC()->countries->countries[ $country_code ] : $country_code;
					$destination_info[ $key ] = trim( $full_country );
					break;
				case 'state':
					if ( empty( $country_code ) ) {
						$country_code = $data['country'];
					}
					$full_state               = isset( WC()->countries->states[ $country_code ][ $data[ $key ] ] ) ? WC()->countries->states[ $country_code ][ $data[ $key ] ] : $data[ $key ];
					$destination_info[ $key ] = trim( $full_state );
					break;
				default:
					$destination_info[ $key ] = trim( $data[ $key ] );
					break;
			}
		}

		/**
		 * Developers can modify the destination info via filter hooks.
		 *
		 * @since 1.0.1
		 *
		 * This example shows how you can modify the shipping destination info via custom function:
		 *
		 *      add_filter( 'woocommerce_woogosend_shipping_destination_info', 'modify_shipping_destination_info', 10, 2 );
		 *
		 *      function modify_shipping_destination_info( $destination_info, $destination_info_arr ) {
		 *          return '1600 Amphitheatre Parkway,Mountain View,CA,94043';
		 *      }
		 */
		return apply_filters( 'woocommerce_' . $this->id . '_shipping_destination_info', $destination_info, $this ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Convert Meters to Distance Unit
	 *
	 * @since    1.2.4
	 * @param int $meters Number of meters to convert.
	 * @return int
	 */
	private function convert_m( $meters ) {
		return ( 'metric' === $this->gmaps_api_units ) ? $this->convert_m_to_km( $meters ) : $this->convert_m_to_mi( $meters );
	}

	/**
	 * Convert Meters to Miles
	 *
	 * @since    1.2.4
	 * @param int $meters Number of meters to convert.
	 * @return int
	 */
	private function convert_m_to_mi( $meters ) {
		return $meters * 0.000621371;
	}

	/**
	 * Convert Meters to Kilometres
	 *
	 * @since    1.2.4
	 * @param int $meters Number of meters to convert.
	 * @return int
	 */
	private function convert_m_to_km( $meters ) {
		return $meters * 0.001;
	}

	/**
	 * Show debug info
	 *
	 * @since    1.0.0
	 * @param string $message     The text to display in the notice.
	 * @param string $notice_type The name of the notice type - either error, success or notice.
	 * @return void
	 */
	private function show_debug( $message, $notice_type = 'notice' ) {
		$debug_mode = 'yes' === get_option( 'woocommerce_shipping_debug_mode', 'no' );

		if ( ! $debug_mode || ! current_user_can( 'manage_options' ) || wc_has_notice( $message ) || ( defined( 'WC_DOING_AJAX' ) && WC_DOING_AJAX ) ) {
			return;
		}

		wc_add_notice( $message, $notice_type );
	}
}
