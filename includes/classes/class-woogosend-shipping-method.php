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
class WooGoSend_Shipping_Method extends WC_Shipping_Method {

	/**
	 * All options data
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * All debugs data
	 *
	 * @var array
	 */
	private $debugs = array();

	/**
	 * Rate fields data
	 *
	 * @var array
	 */
	private $services = array();

	/**
	 * Default data
	 *
	 * @var array
	 */
	private $field_default = array(
		'title'             => '',
		'disabled'          => false,
		'class'             => '',
		'css'               => '',
		'placeholder'       => '',
		'type'              => 'text',
		'desc_tip'          => false,
		'description'       => '',
		'default'           => '',
		'custom_attributes' => array(),
		'is_required'       => false,
	);

	/**
	 * Distance unit
	 *
	 * @var string
	 */
	private $distance_unit = 'metric';

	/**
	 * Constructor for your shipping class
	 *
	 * @param int $instance_id ID of shipping method instance.
	 */
	public function __construct( $instance_id = 0 ) {
		// ID for your shipping method. Should be unique.
		$this->id = WOOGOSEND_METHOD_ID;

		// Title shown in admin.
		$this->method_title = WOOGOSEND_METHOD_TITLE;

		// Title shown in admin.
		$this->title = $this->method_title;

		// Description shown in admin.
		$this->method_description = __( 'WooCommerce shipping rates calculator allows you to easily offer shipping rates based on the distance calculated using Google Maps Distance Matrix Service API.', 'woogosend' );

		$this->enabled = $this->get_option( 'enabled' );

		$this->instance_id = absint( $instance_id );

		$this->supports = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();
		$this->migrate_data();
	}

	/**
	 * Init settings
	 *
	 * @return void
	 */
	public function init() {
		// Register hooks.
		$this->register_services();

		// Register hooks.
		$this->init_hooks();

		// Load the settings API.
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		// Define user set variables.
		foreach ( $this->instance_form_fields as $key => $field ) {
			$default = isset( $field['default'] ) ? $field['default'] : null;

			$this->options[ $key ] = $this->get_option( $key, $default );

			$this->{$key} = $this->options[ $key ];
		}
	}

	/**
	 * Data migration handler
	 *
	 * @since 1.3
	 *
	 * @return void
	 */
	private function migrate_data() {
		if ( ! $this->get_instance_id() ) {
			return;
		}

		$data_version = get_option( 'woogosend_data_version' );

		if ( $data_version && version_compare( WOOGOSEND_VERSION, $data_version, '<=' ) ) {
			return;
		}

		$migrations = array();

		foreach ( glob( WOOGOSEND_PATH . 'includes/migrations/*.php' ) as $migration_file ) {
			$migration_file_name  = basename( $migration_file, '.php' );
			$migration_class_name = 'WooGoSend_Migration_' . str_replace( '-', '_', str_replace( 'class-woogosend-migration-', '', $migration_file_name ) );

			if ( isset( $migrations[ $migration_class_name ] ) ) {
				continue;
			}

			$migrations[ $migration_class_name ] = new $migration_class_name();
		}

		if ( $migrations ) {
			usort( $migrations, array( $this, 'sort_version' ) );
		}

		foreach ( $migrations as $migration ) {
			if ( $data_version && version_compare( $migration::get_version(), $data_version, '<=' ) ) {
				continue;
			}

			$migration->set_instance( $this );

			$migration_update_options = $migration->get_update_options();
			$migration_delete_options = $migration->get_delete_options();

			if ( ! $migration_update_options && ! $migration_delete_options ) {
				continue;
			}

			foreach ( $migration->get_update_options() as $key => $value ) {
				$this->instance_settings[ $key ] = $value;
			}

			foreach ( $migration->get_delete_options() as $key ) {
				unset( $this->instance_settings[ $key ] );
			}

			$data_version = $migration->get_version();

			// Update the settings data.
			update_option( $this->get_instance_option_key(), apply_filters( 'woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this ), 'yes' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			// Update the latest version migrated option.
			update_option( 'woogosend_data_version', $data_version, 'yes' );

			// translators: %s is data migration version.
			$this->show_debug( sprintf( __( 'Data migrated to version %s', 'woogosend' ), $data_version ) );
		}
	}

	/**
	 * Register courier services
	 *
	 * @return void
	 */
	private function register_services() {
		foreach ( glob( WOOGOSEND_PATH . 'includes/services/class-woogosend-services-*.php' ) as $file ) {
			$class_name = str_replace( array( 'class-', 'woogosend' ), array( '', 'WooGoSend' ), basename( $file, '.php' ) );
			$class_name = array_map( 'ucfirst', explode( '-', $class_name ) );
			$class_name = implode( '_', $class_name );

			if ( isset( $this->services[ $class_name ] ) ) {
				continue;
			}

			$this->services[ $class_name ] = new $class_name();
		}
	}

	/**
	 * Register actions/filters hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Save settings in admin if you have any defined.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		// Sanitize settings fields.
		add_filter( 'woocommerce_shipping_' . $this->id . '_instance_settings_values', array( $this, 'instance_settings_values' ), 10 );
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$form_fields = array(
			'field_group_general'         => array(
				'type'      => 'woogosend',
				'orig_type' => 'title',
				'class'     => 'woogosend-field-group',
				'title'     => __( 'General Settings', 'woogosend' ),
			),
			'tax_status'                  => array(
				'title'       => __( 'Tax Status', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'select',
				'description' => __( 'Tax status of fee.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => 'taxable',
				'options'     => array(
					'taxable' => __( 'Taxable', 'woogosend' ),
					'none'    => __( 'None', 'woogosend' ),
				),
			),
			'field_group_store_location'  => array(
				'type'        => 'woogosend',
				'orig_type'   => 'title',
				'class'       => 'woogosend-field-group',
				'title'       => __( 'Store Location Settings', 'woogosend' ),
				// translators: %s is the API services documentation URL.
				'description' => sprintf( __( 'This plugin requires Google API Key and API services enabled for Distance Matrix API, Maps JavaScript API, Geocoding API, Places API. <a href="%s" target="_blank">Click here</a> to go to Google API Console to get API Key and to enable the service.', 'woogosend' ), 'https://cloud.google.com/maps-platform/#get-started' ),
			),
			'api_key'                     => array(
				'title'             => __( 'Distance Calculator API Key', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'text',
				'desc_tip'          => __( 'API Key used to calculate the shipping address distance. Required Google API Service: Distance Matrix API.', 'woogosend' ),
				'description'       => __( 'Required Google API Services: Distance Matrix API', 'woogosend' ),
				'default'           => '',
				'is_required'       => true,
				'class'             => 'woogosend-api-key-input',
				'custom_attributes' => array(
					'data-link' => 'api_key',
					'data-key'  => 'api_key',
					'readonly'  => 'readonly',
				),
			),
			'api_key_picker'              => array(
				'title'             => __( 'Location Picker API Key', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'text',
				'desc_tip'          => __( 'API Key used to render the location picker map. Required Google API Services: Maps JavaScript API, Geocoding API, Places API.', 'woogosend' ),
				'description'       => __( 'Required Google API Services: Maps JavaScript API, Geocoding API, Places API', 'woogosend' ),
				'default'           => '',
				'is_required'       => true,
				'class'             => 'woogosend-api-key-input',
				'custom_attributes' => array(
					'data-link' => 'api_key',
					'data-key'  => 'api_key_picker',
					'readonly'  => 'readonly',
				),
			),
			'origin_type'                 => array(
				'title'             => __( 'Store Origin Data Type', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'select',
				'description'       => __( 'Preferred data that will be used as the origin info when calculating the distance.', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => 'coordinate',
				'options'           => array(
					'coordinate' => __( 'Coordinate (Recommended)', 'woogosend' ),
					'address'    => __( 'Address (Less Accurate)', 'woogosend' ),
				),
				'custom_attributes' => array(
					'data-fields' => wp_json_encode(
						array(
							'coordinate' => array( 'woocommerce_woogosend_origin_lat', 'woocommerce_woogosend_origin_lng' ),
							'address'    => array( 'woocommerce_woogosend_origin_address' ),
						)
					),
				),
			),
			'origin_lat'                  => array(
				'title'             => __( 'Store Location Latitude', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'text',
				'description'       => __( 'Store location latitude coordinates', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '',
				'is_required'       => true,
				'class'             => 'woogosend-field--origin',
				'custom_attributes' => array(
					'data-link' => 'location_picker',
					'readonly'  => true,
				),
			),
			'origin_lng'                  => array(
				'title'             => __( 'Store Location Longitude', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'text',
				'description'       => __( 'Store location longitude coordinates', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '',
				'is_required'       => true,
				'class'             => 'woogosend-field--origin',
				'custom_attributes' => array(
					'data-link' => 'location_picker',
					'readonly'  => true,
				),
			),
			'origin_address'              => array(
				'title'             => __( 'Store Location Address', 'woogosend' ),
				'type'              => 'woogosend',
				'orig_type'         => 'text',
				'description'       => __( 'Store location full address', 'woogosend' ),
				'desc_tip'          => true,
				'default'           => '',
				'is_required'       => true,
				'class'             => 'woogosend-field--origin',
				'custom_attributes' => array(
					'data-link' => 'location_picker',
					'readonly'  => true,
				),
			),
			'field_group_route'           => array(
				'type'      => 'woogosend',
				'orig_type' => 'title',
				'class'     => 'woogosend-field-group',
				'title'     => __( 'Route Settings', 'woogosend' ),
			),
			'travel_mode'                 => array(
				'title'       => __( 'Travel Mode', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'select',
				'description' => __( 'Google Maps Distance Matrix API travel mode parameter.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => 'driving',
				'options'     => array(
					'driving'   => __( 'Driving', 'woogosend' ),
					'walking'   => __( 'Walking', 'woogosend' ),
					'bicycling' => __( 'Bicycling', 'woogosend' ),
				),
				'api_request' => 'mode',
			),
			'route_restrictions'          => array(
				'title'       => __( 'Route Restrictions', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'select',
				'description' => __( 'Google Maps Distance Matrix API route restrictions parameter.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '',
				'options'     => array(
					''         => __( 'None', 'woogosend' ),
					'tolls'    => __( 'Avoid Tolls', 'woogosend' ),
					'highways' => __( 'Avoid Highways', 'woogosend' ),
					'ferries'  => __( 'Avoid Ferries', 'woogosend' ),
					'indoor'   => __( 'Avoid Indoor', 'woogosend' ),
				),
				'api_request' => 'avoid',
			),
			'preferred_route'             => array(
				'title'       => __( 'Preferred Route', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'select',
				'description' => __( 'Preferred route that will be used for calculation if API provide several routes', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => 'shortest_distance',
				'options'     => array(
					'shortest_distance' => __( 'Shortest Distance', 'woogosend' ),
					'longest_distance'  => __( 'Longest Distance', 'woogosend' ),
					'shortest_duration' => __( 'Shortest Duration', 'woogosend' ),
					'longest_duration'  => __( 'Longest Duration', 'woogosend' ),
				),
			),
			'round_up_distance'           => array(
				'title'       => __( 'Round Up Distance', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'checkbox',
				'description' => __( 'Round up the calculated shipping distance with decimal to the nearest absolute number.', 'woogosend' ),
				'desc_tip'    => true,
			),
			'show_distance'               => array(
				'title'       => __( 'Show Distance Info', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'checkbox',
				'description' => __( 'Show the distance info to customer during checkout.', 'woogosend' ),
				'desc_tip'    => true,
			),
			'field_group_location_picker' => array(
				'type'      => 'woogosend',
				'orig_type' => 'title',
				'class'     => 'woogosend-field-group woogosend-field-group-hidden',
				'title'     => __( 'Store Location Picker', 'woogosend' ),
			),
			'store_location_picker'       => array(
				'title'       => __( 'Store Location Picker', 'woogosend' ),
				'type'        => 'store_location_picker',
				'description' => __( 'Drag the store icon marker or search your address in the input box below.', 'woogosend' ),
			),
			'js_template'                 => array(
				'type' => 'js_template',
			),
		);

		foreach ( $this->services as $service ) {
			$form_fields[ 'field_group_services_' . $service->get_slug() ] = array(
				'type'        => 'woogosend',
				'orig_type'   => 'title',
				'class'       => 'woogosend-field-group',
				// translators: %s is Courier service name.
				'title'       => sprintf( __( 'Service Settings: %s', 'woogosend' ), $service->get_label() ),
				'description' => $service->get_description(),
			);

			$form_fields = array_merge( $form_fields, $service->get_fields() );
		}

		$form_fields['field_group_third_party'] = array(
			'type'        => 'woogosend',
			'orig_type'   => 'title',
			'class'       => 'woogosend-field-group',
			'title'       => __( 'Third-Party Settings', 'woogosend' ),
			'description' => __( 'Settings added by third-party plugins.', 'woogosend' ),
		);

		/**
		 * Developers can modify the $form_fields var via filter hooks.
		 *
		 * This example shows how you can modify the form fields data via custom function:
		 *
		 *      add_filter( 'woogosend_form_fields', 'my_woogosend_form_fields', 10, 2 );
		 *
		 *      function my_woogosend_form_fields( $form_fields, $instance_id ) {
		 *          return array();
		 *      }
		 */
		$this->instance_form_fields = apply_filters( 'woogosend_form_fields', $form_fields, $this->get_instance_id() );
	}

	/**
	 * Get Rate Field Value
	 *
	 * @param string $key Rate field key.
	 * @param array  $rate Rate row data.
	 * @param string $default Default rate field value.
	 */
	private function get_rate_field_value( $key, $rate, $default = '' ) {
		$value = isset( $rate[ $key ] ) ? $rate[ $key ] : $default;

		if ( 0 === strpos( $key, 'rate_class_' ) && isset( $rate['cost_type'] ) && 'fixed' === $rate['cost_type'] ) {
			$value = 0;
		}

		if ( 'min_cost' === $key && isset( $rate['rate_class_0'], $rate['cost_type'] ) && 'fixed' === $rate['cost_type'] ) {
			$value = $rate['rate_class_0'];
		}

		return $value;
	}

	/**
	 * Generate woogosend field.
	 *
	 * @param string $key Input field key.
	 * @param array  $data Settings field data.
	 */
	public function generate_woogosend_html( $key, $data ) {
		return $this->generate_settings_html(
			array(
				$key => $this->populate_field( $key, $data ),
			),
			false
		);
	}

	/**
	 * Generate js_template field.
	 */
	public function generate_js_template_html() {
		ob_start();
		?>
		<script type="text/template" id="tmpl-woogosend-button">
			<button href="{{data.href}}" class="button button-secondary woogosend-button {{data.class}}">{{{ data.text }}}</button>
		</script>

		<script type="text/template" id="tmpl-woogosend-buttons">
			<div id="woogosend-buttons" class="woogosend-buttons">
				<# if(data.btn_left) { #>
				<button id="woogosend-btn--{{data.btn_left.id}}" class="button button-primary button-large woogosend-buttons-item woogosend-buttons-item--left"><span class="dashicons dashicons-{{data.btn_left.icon}}"></span> {{data.btn_left.label}}</button>
				<# } #>
				<# if(data.btn_right) { #>
				<button id="woogosend-btn--{{data.btn_right.id}}" class="button button-primary button-large woogosend-buttons-item woogosend-buttons-item--right"><span class="dashicons dashicons-{{data.btn_right.icon}}"></span> {{data.btn_right.label}}</button>
				<# } #>
			</div>
		</script>

		<script type="text/template" id="tmpl-woogosend-map-search-panel">
			<div id="woogosend-map-search-panel" class="woogosend-map-search-panel woogosend-hidden expanded">
				<button type="button" id="woogosend-map-search-panel-toggle" class="woogosend-map-search-panel-toggle woogosend-map-search-element"><span class="dashicons"></button>
				<input id="woogosend-map-search-input" class="woogosend-fullwidth woogosend-map-search-input woogosend-map-search-element" type="search" placeholder="<?php echo esc_html__( 'Type your store location address...', 'woogosend' ); ?>" autocomplete="off">
			</div>
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate store_location_picker field.
	 *
	 * @param string $key Input field key.
	 * @param array  $data Settings field data.
	 */
	public function generate_store_location_picker_html( $key, $data ) {
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
			'default'           => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top" class="woogosend-row">
			<td colspan="2" class="woogosend-no-padding">
				<table id="woogosend-table-map-picker" class="form-table woogosend-table woogosend-table-map-picker" cellspacing="0">
					<tr valign="top">
						<td colspan="2" class="woogosend-no-padding">
							<div id="woogosend-map-wrap" class="woogosend-map-wrap">
								<div id="woogosend-map-canvas" class="woogosend-map-canvas"></div>
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Validate WOOGOSEND Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @param  string $key Field key.
	 * @param  string $value Posted Value.
	 * @throws Exception If the field value is invalid.
	 * @return string
	 */
	public function validate_woogosend_field( $key, $value ) {
		$field = isset( $this->instance_form_fields[ $key ] ) ? $this->instance_form_fields[ $key ] : false;

		if ( $field ) {
			$field = $this->populate_field( $key, $field );

			if ( isset( $field['orig_type'] ) ) {
				$field['type'] = $field['orig_type'];
			}

			$type = $this->get_field_type( $field );

			if ( 'woogosend' === $type ) {
				$type = 'text';
			}

			// Look for a validate_FIELDTYPE_field method.
			if ( is_callable( array( $this, 'validate_' . $type . '_field' ) ) ) {
				$value = $this->{'validate_' . $type . '_field'}( $key, $value );
			} else {
				$value = $this->validate_text_field( $key, $value );
			}

			try {
				// Validate required field value.
				if ( $field['is_required'] && ( ! strlen( trim( $value ) ) || is_null( $value ) ) ) {
					throw new Exception( wp_sprintf( woogosend_i18n( 'errors.field_required' ), $field['title'] ) );
				}

				if ( strlen( $value ) ) {
					// Validate min field value.
					if ( isset( $field['custom_attributes']['min'] ) && $value < $field['custom_attributes']['min'] ) {
						throw new Exception( wp_sprintf( woogosend_i18n( 'errors.field_min_value' ), $field['title'], $field['custom_attributes']['min'] ) );
					}

					// Validate max field value.
					if ( isset( $field['custom_attributes']['max'] ) && $value > $field['custom_attributes']['max'] ) {
						throw new Exception( wp_sprintf( woogosend_i18n( 'errors.field_max_value' ), $field['title'], $field['custom_attributes']['max'] ) );
					}
				}
			} catch ( Exception $e ) {
				// translators: %s is the error message.
				throw new Exception( sprintf( __( 'Error: %s', 'woogosend' ), $e->getMessage() ) );
			}

			return $value;
		}

		return $this->validate_text_field( $key, $value );
	}

	/**
	 * Get API Key for the API request
	 *
	 * @return string
	 */
	private function api_request_key() {
		return $this->api_key;
	}

	/**
	 * Making HTTP request to Google Maps Distance Matrix API
	 *
	 * @param array   $args Custom arguments for $settings and $package data.
	 * @param boolean $cache Is use the cached data.
	 * @throws Exception If error happen.
	 * @return array
	 */
	private function api_request( $args = array(), $cache = true ) {
		/**
		 * Developers can modify the api request via filter hooks.
		 *
		 * This example shows how you can modify the $pre var via custom function:
		 *
		 *      add_filter( 'woogosend_api_request_pre', 'my_woogosend_api_request_pre', 10, 4 );
		 *
		 *      function my_woogosend_api_request_pre( $false, $args, $cache, $obj ) {
		 *          // Return the response data array
		 *          return array(
		 *              'distance'      => 40,
		 *              'distance_text' => '40 km',
		 *              'duration'      => 3593,
		 *              'duration_text' => '1 hour 5 mins',
		 *              'response'      => array() // Raw response from API server
		 *          );
		 *      }
		 */
		$pre = apply_filters( 'woogosend_api_request_pre', false, $args, $cache, $this );

		if ( false !== $pre ) {
			return $pre;
		}

		try {
			$args = wp_parse_args(
				$args,
				array(
					'origin'      => array(),
					'destination' => array(),
					'settings'    => array(),
					'package'     => array(),
				)
			);

			// Imports variables from args: origin, destination, settings, package.
			$settings    = wp_parse_args( $args['settings'], $this->options );
			$package     = $args['package'];
			$origin      = $args['origin'];
			$destination = $args['destination'];

			// Check origin parameter.
			if ( empty( $origin ) ) {
				throw new Exception( __( 'Origin parameter is empty', 'woogosend' ) );
			}

			// Check destination parameter.
			if ( empty( $destination ) ) {
				throw new Exception( __( 'Destination parameter is empty', 'woogosend' ) );
			}

			if ( $cache && ! $this->is_debug_mode() ) {
				$cache_key = $this->id . '_' . $this->get_instance_id() . '_api_request_' . md5(
					wp_json_encode(
						array(
							'origin'      => $origin,
							'destination' => $destination,
							'package'     => $package,
							'settings'    => $settings,
						)
					)
				);

				// Check if the data already cached and return it.
				$cached_data = get_transient( $cache_key );

				if ( false !== $cached_data ) {
					return $cached_data;
				}
			}

			$api_request_data = array(
				'origins'      => $origin,
				'destinations' => $destination,
				'language'     => get_locale(),
				'key'          => $this->api_request_key(),
			);

			foreach ( $this->instance_form_fields as $key => $field ) {
				if ( ! isset( $field['api_request'] ) ) {
					continue;
				}

				$api_request_data[ $field['api_request'] ] = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
			}

			$api_request_data_debug = $api_request_data;

			if ( isset( $api_request_data_debug['key'] ) ) {
				$api_request_data_debug['key'] = str_repeat( '*', 20 );
			}

			$this->show_debug( array( 'API_REQUEST_DATA' => $api_request_data_debug ) );

			$api = new WooGoSend_API();

			$results = $api->calculate_distance( $api_request_data );

			if ( is_wp_error( $results ) ) {
				throw new Exception( $results->get_error_message() );
			}

			if ( count( $results ) > 1 ) {
				switch ( $settings['preferred_route'] ) {
					case 'longest_duration':
						usort( $results, array( $this, 'longest_duration_results' ) );
						break;

					case 'longest_distance':
						usort( $results, array( $this, 'longest_distance_results' ) );
						break;

					case 'shortest_duration':
						usort( $results, array( $this, 'shortest_duration_results' ) );
						break;

					default:
						usort( $results, array( $this, 'shortest_distance_results' ) );
						break;
				}
			}

			$distance = floatVal( $this->convert_distance( $results[0]['distance'] ) );

			if ( empty( $distance ) ) {
				$distance = 0.1;
			}

			if ( 'yes' === $settings['round_up_distance'] ) {
				$distance = ceil( $distance );
			}

			$result = array(
				'distance'         => $distance,
				'distance_text'    => sprintf( '%s %s', $distance, ( 'metric' === $this->distance_unit ? 'km' : 'mi' ) ),
				'duration'         => $results[0]['duration'],
				'duration_text'    => $results[0]['duration_text'],
				'api_request_data' => $api_request_data,
			);

			if ( $cache && ! $this->is_debug_mode() ) {
				delete_transient( $cache_key ); // To make sure the transient data re-created, delete it first.
				set_transient( $cache_key, $result, HOUR_IN_SECONDS ); // Store the data to transient with expiration in 1 hour for later use.
			}

			$result_debug = $result;

			if ( isset( $result_debug['api_request_data'] ) ) {
				unset( $result_debug['api_request_data'] );
			}

			$this->show_debug( array( 'API_RESPONSE_DATA' => $result_debug ) );

			/**
			 * Developers can modify the api request $result via filter hooks.
			 *
			 * This example shows how you can modify the $pre var via custom function:
			 *
			 *      add_filter( 'woogosend_api_request', 'my_woogosend_api_request', 10, 2 );
			 *
			 *      function my_woogosend_api_request( $result, $obj ) {
			 *          // Return the response data array
			 *          return array(
			 *              'distance'          => 40,
			 *              'distance_text'     => '40 km',
			 *              'duration'          => 3593,
			 *              'duration_text'     => '1 hour 5 mins',
			 *              'api_request_data'  => array() // API request parameters
			 *          );
			 *      }
			 */
			return apply_filters( 'woogosend_api_request', $result, $this );
		} catch ( Exception $e ) {
			return new WP_Error( 'api_request', $e->getMessage() );
		}
	}

	/**
	 * Populate field data
	 *
	 * @param array $key Current field key.
	 * @param array $data Current field data.
	 *
	 * @return array
	 */
	private function populate_field( $key, $data ) {
		$data = wp_parse_args( $data, $this->field_default );

		if ( isset( $data['orig_type'] ) ) {
			$data['type'] = $data['orig_type'];
		}

		if ( 'woogosend' === $data['type'] ) {
			$data['type'] = 'text';
		}

		$data_classes = isset( $data['class'] ) ? explode( ' ', $data['class'] ) : array();

		array_push( $data_classes, 'woogosend-field', 'woogosend-field-key--' . $key, 'woogosend-field-type--' . $data['type'] );

		if ( isset( $data['is_rate'] ) && $data['is_rate'] ) {
			array_push( $data_classes, 'woogosend-field--rate' );
			array_push( $data_classes, 'woogosend-field--rate--' . $data['type'] );
			array_push( $data_classes, 'woogosend-field--rate--' . $key );
		}

		if ( isset( $data['context'] ) && $data['context'] ) {
			array_push( $data_classes, 'woogosend-field--context--' . $data['context'] );
			array_push( $data_classes, 'woogosend-field--context--' . $data['context'] . '--' . $data['type'] );
			array_push( $data_classes, 'woogosend-field--context--' . $data['context'] . '--' . $key );

			if ( 'dummy' === $data['context'] ) {
				array_push( $data_classes, 'woogosend-fullwidth' );
			}
		}

		$data_is_required = isset( $data['is_required'] ) && $data['is_required'];

		if ( $data_is_required ) {
			array_push( $data_classes, 'woogosend-field--is-required' );
		}

		$data['class'] = implode( ' ', array_map( 'trim', array_unique( array_filter( $data_classes ) ) ) );

		$custom_attributes = array(
			'data-type'        => $data['type'],
			'data-key'         => $key,
			'data-title'       => isset( $data['title'] ) ? $data['title'] : $key,
			'data-id'          => $this->get_field_key( $key ),
			'data-context'     => isset( $data['context'] ) ? $data['context'] : '',
			'data-title'       => isset( $data['title'] ) ? $data['title'] : $key,
			'data-options'     => isset( $data['options'] ) ? wp_json_encode( $data['options'] ) : wp_json_encode( array() ),
			'data-validate'    => isset( $data['validate'] ) ? $data['validate'] : 'text',
			'data-is_rate'     => empty( $data['is_rate'] ) ? '0' : '1',
			'data-is_required' => empty( $data['is_required'] ) ? '0' : '1',
			'data-is_rule'     => empty( $data['is_rule'] ) ? '0' : '1',
		);

		$data['custom_attributes'] = array_merge( $data['custom_attributes'], $custom_attributes );

		return $data;
	}

	/**
	 * Processes and saves global shipping method options in the admin area.
	 *
	 * @return bool was anything saved?
	 */
	public function process_admin_options() {
		if ( ! $this->instance_id ) {
			return parent::process_admin_options();
		}

		// Check we are processing the correct form for this instance.
		if ( ! isset( $_REQUEST['instance_id'] ) || absint( $_REQUEST['instance_id'] ) !== $this->instance_id ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		$this->init_instance_settings();

		$post_data = $this->get_post_data();

		foreach ( $this->get_instance_form_fields() as $key => $field ) {
			if ( 'title' === $this->get_field_type( $field ) ) {
				continue;
			}

			try {
				$this->instance_settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
			} catch ( Exception $e ) {
				$this->add_error( $e->getMessage() );
			}
		}

		return update_option( $this->get_instance_option_key(), apply_filters( 'woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this ), 'yes' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Sanitize settings value before store to DB.
	 *
	 * @param array $settings Current settings data.
	 * @return array
	 */
	public function instance_settings_values( $settings ) {
		if ( $this->get_errors() ) {
			return $this->options;
		}

		return $settings;
	}

	/**
	 * Calculate shipping function.
	 *
	 * @param array $package Package data array.
	 * @throws Exception Throw error if validation not passed.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		try {
			$origin_info = $this->get_origin_info( $package );

			if ( is_wp_error( $origin_info ) ) {
				throw new Exception( $origin_info->get_error_message() );
			}

			$destination_info = $this->get_destination_info( $package );

			if ( is_wp_error( $destination_info ) ) {
				throw new Exception( $destination_info->get_error_message() );
			}

			$api_response = $this->api_request(
				array(
					'origin'      => $origin_info,
					'destination' => $destination_info,
					'package'     => $package,
				)
			);

			// Bail early if the API request error.
			if ( is_wp_error( $api_response ) ) {
				throw new Exception( $api_response->get_error_message() );
			}

			// Bail early if the API response is empty.
			if ( ! $api_response ) {
				throw new Exception( __( 'API Response data is empty', 'woogosend' ) );
			}

			$envelope = $this->get_envelope( $package );

			foreach ( $this->services as $service ) {
				$service->set_settings( $this->instance_settings );

				$this->show_debug(
					array(
						'SERVICE_SETTINGS_DATA' => array(
							'id'   => $service->get_slug(),
							'data' => $service->get_settings( true ),
						),
					)
				);

				if ( 'yes' !== $service->get_setting( 'enable' ) ) {
					$this->show_debug(
						array(
							'SERVICE_NOT_ENABLED' => $service->get_slug(),
						)
					);

					continue;
				}

				/**
				* Early filter for the service cost calculation
				*
				* @param array $shipping_cost {
				*      Default service cost info
				*
				*      @type int $total         Total shipping cost.
				*      @type int $drivers_count Number of drivers needed.
				* }
				* @param string $service_id        Current service ID.
				* @param int    $distance          Current distance.
				* @param array  $envelope          Current envelope info.
				* @param array  $instance_settings Current settings info.
				*
				* @return array
				*/
				$shipping_cost = apply_filters( 'woogosend_calculate_shipping_cost_pre', false, $service->get_slug(), $api_response['distance'], $envelope, $this->instance_settings );

				if ( false === $shipping_cost ) {
					$shipping_cost = $service->calculate_cost( $api_response['distance'], $envelope );
				}

				if ( is_wp_error( $shipping_cost ) ) {
					$this->show_debug( $shipping_cost->get_error_message() );

					continue;
				}

				$label = $service->get_setting( 'title', true );

				if ( ! $label ) {
					$label = $service->get_slug();
				}

				$drivers_count = isset( $shipping_cost['drivers_count'] ) ? (int) $shipping_cost['drivers_count'] : 1;

				if ( ! $drivers_count ) {
					$drivers_count = 1;
				}

				$label_extra = array();

				if ( $drivers_count > 1 ) {
					// translators: %s is the number of the drivers.
					$label_extra[] = sprintf( _n( '%s driver', '%s drivers', $drivers_count, 'woogosend' ), $drivers_count );
				}

				if ( 'yes' === $this->show_distance ) {
					$label_extra[] = $api_response['distance_text'];
				}

				if ( $label_extra ) {
					$label = sprintf( '%1$s (%2$s)', $label, implode( ', ', $label_extra ) );
				}

				$total = isset( $shipping_cost['total'] ) ? $shipping_cost['total'] : 0;

				$rate = array(
					'id'        => $this->get_rate_id( $service->get_slug() ),
					'label'     => $label,
					'cost'      => $total,
					'meta_data' => array(
						'api_response'  => $api_response,
						'drivers_count' => $drivers_count,
					),
				);

				$this->add_rate( $rate );
			}
		} catch ( Exception $e ) {
			$this->show_debug( $e->getMessage(), 'error' );
		}
	}

	/**
	 * Get the envelope info: weight, dimension, quantity.
	 *
	 * @param array $package The cart data.
	 *
	 * @return array
	 */
	public function get_envelope( $package ) {
		$data = array(
			'width'    => 0,
			'length'   => 0,
			'height'   => 0,
			'weight'   => 0,
			'quantity' => 0,
		);

		$length   = array();
		$width    = array();
		$height   = array();
		$weight   = array();
		$quantity = array();

		foreach ( $package['contents'] as $item ) {
			// Validate cart item quantity value.
			$item_quantity = absint( $item['quantity'] );

			if ( ! $item_quantity ) {
				continue;
			}

			$quantity[] = $item_quantity;

			// Validate cart item weight value.
			$item_weight = is_numeric( $item['data']->get_weight() ) ? $item['data']->get_weight() : 0;

			if ( $item_weight ) {
				array_push( $weight, $item_weight * $item_quantity );
			}

			// Validate cart item width value.
			$item_width = is_numeric( $item['data']->get_width() ) ? $item['data']->get_width() : 0;

			if ( $item_width ) {
				array_push( $width, $item_width * 1 );
			}

			// Validate cart item length value.
			$item_length = is_numeric( $item['data']->get_length() ) ? $item['data']->get_length() : 0;

			if ( $item_length ) {
				array_push( $length, $item_length * 1 );
			}

			// Validate cart item height value.
			$item_height = is_numeric( $item['data']->get_height() ) ? $item['data']->get_height() : 0;

			if ( $item_height ) {
				array_push( $height, $item_height * $item_quantity );
			}
		}

		if ( $weight ) {
			$data['weight'] = wc_get_weight( array_sum( $weight ), 'kg' );
		}

		if ( $width ) {
			$data['width'] = wc_get_dimension( max( $width ), 'cm' );
		}

		if ( $length ) {
			$data['length'] = wc_get_dimension( max( $length ), 'cm' );
		}

		if ( $height ) {
			$data['height'] = wc_get_dimension( array_sum( $height ), 'cm' );
		}

		if ( $quantity ) {
			$data['quantity'] = array_sum( $quantity );
		}

		/**
		 * Filter the service envelope info
		 *
		 * @param array $envelope {
		 *      Default envelope info
		 *
		 *      @type string $weight   Envelope mass weight.
		 *      @type string $width    Envelope dimension width.
		 *      @type string $length   Envelope dimension length.
		 *      @type string $height   Envelope dimension height.
		 *      @type string $quantity Envelope items quantity.
		 * }
		 * @param array $package     Current cart data.
		 *
		 * @return array
		 */
		return apply_filters( 'woogosend_envelope_info', $data, $package );
	}

	/**
	 * Get shipping origin info
	 *
	 * @param array $package The cart content data.
	 * @return array
	 */
	private function get_origin_info( $package ) {
		$origin_info = array();

		switch ( $this->origin_type ) {
			case 'coordinate':
				if ( ! empty( $this->origin_lat ) && ! empty( $this->origin_lng ) ) {
					$origin_info['origin_lat'] = $this->origin_lat;
					$origin_info['origin_lng'] = $this->origin_lng;
				}
				break;

			default:
				if ( ! empty( $this->origin_address ) ) {
					$origin_info['origin_address'] = $this->origin_address;
				}
				break;
		}

		/**
		 * Developers can modify the origin info via filter hooks.
		 *
		 * This example shows how you can modify the $origin_info var via custom function:
		 *
		 *      add_filter( 'woogosend_origin_info', 'my_woogosend_origin_info', 10, 3 );
		 *
		 *      function my_woogosend_origin_info( $origin_info, $package, $instance_id ) {
		 *          return array(
		 *               'origin_address' => '1600 Amphitheater Parkway,Mountain View,CA,94043',
		 *          );
		 *      }
		 */
		return apply_filters( 'woogosend_origin_info', $origin_info, $package, $this->get_instance_id() );
	}

	/**
	 * Get shipping destination info
	 *
	 * @throws Exception Throw error if validation not passed.
	 * @param array $package The cart content data.
	 * @return string
	 */
	private function get_destination_info( $package ) {
		/**
		 * Developers can modify the $destination_info var via filter hooks.
		 *
		 * This example shows how you can modify the shipping destination info via custom function:
		 *
		 *      add_filter( 'woogosend_destination_info_pre', 'my_woogosend_destination_info_pre', 10, 3 );
		 *
		 *      function my_woogosend_destination_info_pre( $false, $package, $instance_id ) {
		 *          return '1600 Amphitheater Parkway, Mountain View, CA, 94043, United State';
		 *      }
		 */
		$pre = apply_filters( 'woogosend_destination_info_pre', false, $package, $this->get_instance_id() );

		if ( false !== $pre ) {
			return $pre;
		}

		$errors = array();

		$destination_info = array(
			'address_1' => false,
			'address_2' => false,
			'city'      => false,
			'state'     => false,
			'postcode'  => false,
			'country'   => false,
		);

		$shipping_fields = woogosend_shipping_fields();

		if ( ! $shipping_fields ) {
			return '';
		}

		foreach ( $shipping_fields['data'] as $key => $field ) {
			$field_key = str_replace( $shipping_fields['type'] . '_', '', $key );

			if ( ! isset( $destination_info[ $field_key ] ) ) {
				continue;
			}

			if ( woogosend_is_calc_shipping() && ! apply_filters( 'woocommerce_shipping_calculator_enable_' . $field_key, true ) ) { // phpcs:ignore WordPress.NamingConventions
				continue;
			}

			try {
				$required = isset( $field['required'] ) ? $field['required'] : false;
				$value    = isset( $package['destination'][ $field_key ] ) ? $package['destination'][ $field_key ] : '';

				if ( $required && ! $value ) {
					// translators: %s is field key.
					throw new Exception( sprintf( __( 'Shipping destination field is empty: %s', 'woogosend' ), $field_key ) );
				}

				if ( $value && 'postcode' === $field_key && ! empty( $package['destination']['country'] ) ) {
					$country_code = $package['destination']['country'];

					if ( ! WC_Validation::is_postcode( $value, $country_code ) ) {
						// translators: %s is field key.
						throw new Exception( sprintf( __( 'Shipping destination field is invalid: %s', 'woogosend' ), $field_key ) );
					}
				}

				if ( $value ) {
					$destination_info[ $field_key ] = $value;
				}
			} catch ( Exception $e ) {
				$errors[ $field_key ] = $e->getMessage();
			}
		}

		// Print debug.
		if ( $errors ) {
			foreach ( $errors as $key => $error ) {
				$this->show_debug( $error, 'error' );
			}

			$destination_info = array();
		}

		// Try to get full info for country and state.
		foreach ( $destination_info as $field_key => $value ) {
			if ( ! $value ) {
				continue;
			}

			if ( ! in_array( $field_key, array( 'country', 'state' ), true ) ) {
				continue;
			}

			if ( 'country' === $field_key ) {
				$countries = WC()->countries->countries;

				if ( $countries && is_array( $countries ) && isset( $countries[ $value ] ) ) {
					$value = $countries[ $value ];
				}
			}

			if ( 'state' === $field_key && ! empty( $package['destination']['country'] ) ) {
				$states = WC()->countries->states;

				if ( $states && is_array( $states ) && isset( $states[ $package['destination']['country'] ][ $value ] ) ) {
					$value = $states[ $package['destination']['country'] ][ $value ];
				}
			}

			$destination_info[ $field_key ] = $value;
		}

		// Format address.
		$destination_info = WC()->countries->get_formatted_address( $destination_info, ', ' );

		/**
		 * Developers can modify the $destination_info var via filter hooks.
		 *
		 * This example shows how you can modify the shipping destination info via custom function:
		 *
		 *      add_filter( 'woogosend_destination_info', 'my_woogosend_destination_info', 10, 3 );
		 *
		 *      function my_woogosend_destination_info( $destination_info, $package, $instance_id ) {
		 *          return '1600 Amphitheater Parkway, Mountain View, CA, 94043, United State';
		 *      }
		 */
		return apply_filters( 'woogosend_destination_info', $destination_info, $package, $this->get_instance_id() );
	}

	/**
	 * Convert Meters to Distance Unit
	 *
	 * @param int $meters Number of meters to convert.
	 * @return int
	 */
	public function convert_distance( $meters ) {
		return ( 'metric' === $this->distance_unit ) ? $this->convert_distance_to_km( $meters ) : $this->convert_distance_to_mi( $meters );
	}

	/**
	 * Convert Meters to Miles
	 *
	 * @param int $meters Number of meters to convert.
	 * @return int
	 */
	public function convert_distance_to_mi( $meters ) {
		return wc_format_decimal( ( $meters * 0.000621371 ), 1 );
	}

	/**
	 * Convert Meters to Kilometers
	 *
	 * @param int $meters Number of meters to convert.
	 * @return int
	 */
	public function convert_distance_to_km( $meters ) {
		return wc_format_decimal( ( $meters * 0.001 ), 1 );
	}

	/**
	 * Sort ascending API response array by duration.
	 *
	 * @param array $a Array 1 that will be sorted.
	 * @param array $b Array 2 that will be compared.
	 * @return int
	 */
	public function shortest_duration_results( $a, $b ) {
		if ( $a['duration'] === $b['duration'] ) {
			return 0;
		}
		return ( $a['duration'] < $b['duration'] ) ? -1 : 1;
	}

	/**
	 * Sort descending API response array by duration.
	 *
	 * @param array $a Array 1 that will be sorted.
	 * @param array $b Array 2 that will be compared.
	 * @return int
	 */
	public function longest_duration_results( $a, $b ) {
		if ( $a['duration'] === $b['duration'] ) {
			return 0;
		}
		return ( $a['duration'] > $b['duration'] ) ? -1 : 1;
	}

	/**
	 * Sort ascending API response array by distance.
	 *
	 * @param array $a Array 1 that will be sorted.
	 * @param array $b Array 2 that will be compared.
	 * @return int
	 */
	public function shortest_distance_results( $a, $b ) {
		if ( $a['max_distance'] === $b['max_distance'] ) {
			return 0;
		}
		return ( $a['max_distance'] < $b['max_distance'] ) ? -1 : 1;
	}

	/**
	 * Sort migration by version.
	 *
	 * @param WooGoSend_Migration $a Object 1 migration handler.
	 * @param WooGoSend_Migration $b Object 1 migration handler.
	 * @return int
	 */
	public function sort_version( $a, $b ) {
		return version_compare( $a::get_version(), $b::get_version(), '<=' ) ? -1 : 1;
	}

	/**
	 * Sort descending API response array by distance.
	 *
	 * @param array $a Array 1 that will be sorted.
	 * @param array $b Array 2 that will be compared.
	 * @return int
	 */
	public function longest_distance_results( $a, $b ) {
		if ( $a['max_distance'] === $b['max_distance'] ) {
			return 0;
		}
		return ( $a['max_distance'] > $b['max_distance'] ) ? -1 : 1;
	}

	/**
	 * Check if run in debug mode
	 *
	 * @return bool
	 */
	public function is_debug_mode() {
		return get_option( 'woocommerce_shipping_debug_mode', 'no' ) === 'yes';
	}

	/**
	 * Show debug info
	 *
	 * @param string $message The text to display in the notice.
	 * @param string $type The type of notice.
	 * @return void
	 */
	public function show_debug( $message, $type = '' ) {
		if ( empty( $message ) ) {
			return;
		}

		if ( defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			return;
		}

		if ( defined( 'WC_DOING_AJAX' ) ) {
			return;
		}

		if ( ! $this->is_debug_mode() ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$message = is_array( $message ) ? wp_json_encode( $message ) : $message;

		$debug_key = md5( $message );

		if ( isset( $this->debugs[ $debug_key ] ) ) {
			return;
		}

		$this->debugs[ $debug_key ] = $message;

		$debug_prefix = strtoupper( $this->id ) . '_' . $this->get_instance_id();

		if ( ! empty( $type ) ) {
			$debug_prefix .= '_' . strtoupper( $type );
		}

		wc_add_notice( $debug_prefix . ' => ' . $message, 'notice' );
	}
}
