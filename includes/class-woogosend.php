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
	die;
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
class WooGoSend extends WC_Shipping_Method {

	/**
	 * Fallback options data
	 *
	 * @since 1.3
	 * @var array
	 */
	private $fallback_options = array();

	/**
	 * All debugs data
	 *
	 * @since 1.3
	 * @var array
	 */
	private $debugs = array();

	/**
	 * Default data
	 *
	 * @since 1.3
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
	 * API object class
	 *
	 * @since 1.3
	 *
	 * @var WooGoSend_API
	 */
	private $api;

	/**
	 * Shipping services object class
	 *
	 * @since 1.3
	 *
	 * @var WooGoSend_Services
	 */
	private $services;

	/**
	 * Constructor for your shipping class
	 *
	 * @since 1.0.0
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
		$this->method_description = __( 'WooCommerce per kilometer shipping rates calculator for GoSend delivery service from Gojek Indonesia.', 'woogosend' );

		$this->enabled = $this->get_option( 'enabled' );

		$this->instance_id = absint( $instance_id );

		$this->supports = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->api      = new WooGoSend_API();
		$this->services = new WooGoSend_Services();

		$this->init_hooks();
		$this->init();
		$this->maybe_migrate_data();
	}

	/**
	 * Register actions/filters hooks
	 *
	 * @since 1.3
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Save settings in admin if you have any defined.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		// Check if this shipping method is availbale for current order.
		add_filter( 'woocommerce_shipping_' . $this->id . '_is_available', array( $this, 'check_is_available' ), 10, 2 );

		// Sanitize settings fields.
		add_filter( 'woocommerce_shipping_' . $this->id . '_instance_settings_values', array( $this, 'instance_settings_values' ), 10 );

		// Hook to woocommerce_cart_shipping_packages to inject filed address_2.
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'inject_cart_shipping_packages' ), 10 );
	}

	/**
	 * Init settings
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		// Load the settings API.
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		// Define user set variables.
		foreach ( $this->instance_form_fields as $key => $field ) {
			$default                        = isset( $field['default'] ) ? $field['default'] : null;
			$this->fallback_options[ $key ] = $this->get_option( $key, $default );
			$this->{$key}                   = $this->fallback_options[ $key ];
		}
	}

	/**
	 * Maybe do data migration process
	 *
	 * @since 1.3
	 *
	 * @return void
	 */
	private function maybe_migrate_data() {
		if ( ! $this->get_instance_id() ) {
			return;
		}

		$data_version = get_option( 'woogosend_data_version' );

		if ( $data_version && version_compare( $data_version, WOOGOSEND_VERSION, '>=' ) ) {
			return;
		}

		foreach ( glob( WOOGOSEND_PATH . 'includes/migration/*.php' ) as $migration_file ) {
			$migration_data = include $migration_file;

			if ( ! isset( $migration_data['version'] ) || version_compare( $migration_data['version'], WOOGOSEND_VERSION, '<=' ) ) {
				continue;
			}

			$migration_options = isset( $migration_data['options'] ) ? $migration_data['options'] : false;

			if ( ! $migration_options ) {
				continue;
			}

			foreach ( $migration_options as $old_key => $new_key ) {
				$old_option = $this->get_option( $old_key );
				$new_option = $this->get_option( $new_key );

				if ( ! is_null( $old_option ) && is_null( $new_option ) ) {
					$this->instance_settings[ $new_key ] = $old_option;
				}
			}

			// Update the settings data.
			update_option( $this->get_instance_option_key(), apply_filters( 'woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this ), 'yes' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			// Update the latest version migrated option.
			update_option( 'woogosend_data_version', $migration_data['version'], 'yes' );
		}
	}

	/**
	 * Init form fields.
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$form_fields = array(
			'field_group_general'             => array(
				'type'      => 'woogosend',
				'orig_type' => 'title',
				'class'     => 'woogosend-field-group',
				'title'     => __( 'General Settings', 'woogosend' ),
			),
			'tax_status'                      => array(
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
			'field_group_store_location'      => array(
				'type'      => 'woogosend',
				'orig_type' => 'title',
				'class'     => 'woogosend-field-group',
				'title'     => __( 'Store Location Settings', 'woogosend' ),
			),
			'api_key'                         => array(
				'title'       => __( 'API Key', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'api_key',
				'description' => __( 'Google maps platform API Key.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '',
				'placeholder' => __( 'Click the icon on the right to edit', 'woogosend' ),
				'is_required' => true,
			),
			'api_key_split'                   => array(
				'title'     => '',
				'label'     => __( 'Use different API Key server side request', 'woogosend' ),
				'type'      => 'woogosend',
				'orig_type' => 'checkbox',
			),
			'api_key_server'                  => array(
				'title'       => __( 'Server Side API Key', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'api_key',
				'description' => __( 'Google maps platform API Key for usage in server side request. This API Key will be used to calculate the distance of the customer during checkout.', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '',
				'placeholder' => __( 'Click the icon on the right to edit', 'woogosend' ),
			),
			'origin_type'                     => array(
				'title'             => __( 'Store Origin Location Data Type', 'woogosend' ),
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
			'origin_lat'                      => array(
				'title'       => __( 'Store Location Latitude', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'store_location',
				'description' => __( 'Store location latitude coordinates', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '',
				'placeholder' => __( 'Click the icon on the right to edit', 'woogosend' ),
				'is_required' => true,
				'disabled'    => true,
			),
			'origin_lng'                      => array(
				'title'       => __( 'Store Location Longitude', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'store_location',
				'description' => __( 'Store location longitude coordinates', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '',
				'placeholder' => __( 'Click the icon on the right to edit', 'woogosend' ),
				'is_required' => true,
				'disabled'    => true,
			),
			'origin_address'                  => array(
				'title'       => __( 'Store Location Address', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'store_location',
				'description' => __( 'Store location full address', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => '',
				'placeholder' => __( 'Click the icon on the right to edit', 'woogosend' ),
				'is_required' => true,
				'disabled'    => true,
			),
			'field_group_route'               => array(
				'type'      => 'woogosend',
				'orig_type' => 'title',
				'class'     => 'woogosend-field-group',
				'title'     => __( 'Route Settings', 'woogosend' ),
			),
			'travel_mode'                     => array(
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
			'route_restrictions'              => array(
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
			'preferred_route'                 => array(
				'title'       => __( 'Preferred Route', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'select',
				'description' => __( 'Prefered route that will be used for calculation if API provide several routes', 'woogosend' ),
				'desc_tip'    => true,
				'default'     => 'shortest_distance',
				'options'     => array(
					'shortest_distance' => __( 'Shortest Distance', 'woogosend' ),
					'longest_distance'  => __( 'Longest Distance', 'woogosend' ),
					'shortest_duration' => __( 'Shortest Duration', 'woogosend' ),
					'longest_duration'  => __( 'Longest Duration', 'woogosend' ),
				),
			),
			'round_up_distance'               => array(
				'title'       => __( 'Round Up Distance', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'checkbox',
				'description' => __( 'Round the distance up to the nearest absolute number.', 'woogosend' ),
				'desc_tip'    => true,
			),
			'show_distance'                   => array(
				'title'       => __( 'Show Distance Info', 'woogosend' ),
				'label'       => __( 'Yes', 'woogosend' ),
				'type'        => 'woogosend',
				'orig_type'   => 'checkbox',
				'description' => __( 'Show the distance info to customer during checkout.', 'woogosend' ),
				'desc_tip'    => true,
			),
			'field_group_location_picker'     => array(
				'type'      => 'woogosend',
				'orig_type' => 'title',
				'class'     => 'woogosend-field-group woogosend-field-group-hidden',
				'title'     => '',
			),
			'store_location_picker'           => array(
				'title'       => __( 'Store Location Picker', 'woogosend' ),
				'type'        => 'store_location_picker',
				'description' => __( 'Drag the store icon marker or search your address in the input box below.', 'woogosend' ),
			),
			'field_group_api_key_instruction' => array(
				'type'      => 'woogosend',
				'orig_type' => 'title',
				'class'     => 'woogosend-field-group woogosend-field-group-hidden',
				'title'     => __( 'How To Get API Key?', 'woogosend' ),
			),
			'api_key_instruction'             => array(
				'title' => __( 'How To Get API Key?', 'woogosend' ),
				'type'  => 'api_key_instruction',
			),
			'js_template'                     => array(
				'type' => 'js_template',
			),
		);

		foreach ( $this->services->get_fields() as $service_key => $service ) {
			$form_fields[ $service_key ] = array(
				'type'        => 'woogosend',
				'orig_type'   => 'title',
				'class'       => 'woogosend-field-group',
				// translators: %1$s is service ID.
				'title'       => sprintf( __( 'Service Settings: %1$s', 'woogosend' ), $service['label'] ),
				'description' => __( '<a href="https://www.go-jek.com/go-send/" target="_blank">Click here</a> for more info about GoSend.', 'woogosend' ),
			);

			foreach ( $service['fields'] as $service_field_key => $service_field ) {
				$form_fields[ $service_field_key ] = array_merge(
					$service_field,
					array(
						'type'      => 'woogosend',
						'orig_type' => $service_field['type'],
					)
				);
			}
		}

		/**
		 * Filters the setting fields
		 *
		 * @since 1.3
		 *
		 * @param array $form_fields Default fields data.
		 * @param int   $instance_id Current instance ID.
		 *
		 * @return array
		 */
		$this->instance_form_fields = apply_filters( 'woogosend_form_fields', $form_fields, $this->get_instance_id() );
	}

	/**
	 * Generate woogosend field.
	 *
	 * @since 1.3
	 *
	 * @param string $key Input field key.
	 * @param array  $data Settings field data.
	 *
	 * @return string
	 */
	public function generate_woogosend_html( $key, $data ) {
		$data = $this->populate_field( $key, $data );

		return $this->generate_settings_html( array( $key => $data ), false );
	}

	/**
	 * Generate js_template field.
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function generate_js_template_html() {
		ob_start();
		?>
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
			<div id="woogosend-map-search-panel" class="woogosend-map-search-panel woogosend-hidden">
				<button type="button" id="woogosend-map-search-panel-toggle" class="woogosend-map-search-element"><span class="dashicons dashicons-search"></button>
				<input id="woogosend-map-search-input" class="woogosend-fullwidth woogosend-map-search-input woogosend-map-search-element" type="search" placeholder="<?php echo esc_html__( 'Type your store location address...', 'woogosend' ); ?>" autocomplete="off">
			</div>
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate api_key field.
	 *
	 * @since 1.3
	 *
	 * @param string $key Input field key.
	 * @param array  $data Settings field data.
	 *
	 * @return string
	 */
	public function generate_api_key_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input type="hidden" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" />
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="text" id="<?php echo esc_attr( $field_key ); ?>--dummy" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" readonly="readonly" /> 
					<a href="#" class="button button-secondary button-small woogosend-edit-api-key woogosend-link" id="<?php echo esc_attr( $key ); ?>"><span class="dashicons dashicons-edit"></span><span class="dashicons dashicons-yes"></span><span class="spinner woogosend-spinner"></span></a>
					<div>
					<a href="#" class="woogosend-show-instructions woogosend-link"><?php esc_html_e( 'How to Get API Key?', 'woogosend' ); ?></a>
					</div>
					<?php echo $this->get_description_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate store_location field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Input field key.
	 * @param array  $data Settings field data.
	 *
	 * @return string
	 */
	public function generate_store_location_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" readonly="readonly" /> 
					<a href="#" class="button button-secondary button-small woogosend-link woogosend-edit-location"><span class="dashicons dashicons-location"></span></a>
					<?php echo $this->get_description_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate store_location_picker field.
	 *
	 * @since 1.3
	 *
	 * @param string $key Input field key.
	 * @param array  $data Settings field data.
	 *
	 * @return string
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
				<table id="woogosend-table-map-picker" class="form-table woogosend-table woogosend-table-map-picker woogosend-table woogosend-table-map-picker--<?php echo esc_attr( $key ); ?>" cellspacing="0">
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
	 * Generate api_key_instruction field.
	 *
	 * @since 1.3
	 *
	 * @param string $key Input field key.
	 * @param array  $data Settings field data.
	 *
	 * @return string
	 */
	public function generate_api_key_instruction_html( $key, $data ) {
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
		<tr valign="top">
			<td colspan="2" class="woogosend-no-padding">
				<div id="woogosend-map-instructions">
					<div class="woogosend-map-instructions woogosend-map-instructions--<?php echo esc_attr( $key ); ?>">
						<p><?php echo wp_kses_post( __( 'This plugin uses Google Maps Platform APIs where users are required to have a valid API key to be able to use their APIs. Make sure you checked 3 the checkboxes as shown below when creating the API Key.', 'woogosend' ) ); ?></p>
						<a href="https://cloud.google.com/maps-platform/#get-started" target="_blank" title="<?php echo esc_attr__( 'Enable Google Maps Platform', 'woogosend' ); ?>"><img src="<?php echo esc_attr( WOOGOSEND_URL ); ?>assets/img/map-instructions.jpg" /></a>
					</div>
				</div>
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
	 * @since 1.3
	 *
	 * @param  string $key Field key.
	 * @param  string $value Posted Value.
	 *
	 * @return string
	 *
	 * @throws Exception If the field value is invalid.
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

			return $value;
		}

		return $this->validate_text_field( $key, $value );
	}

	/**
	 * Validate and format api_key settings field.
	 *
	 * @since 1.3
	 *
	 * @param string $key Input field key.
	 * @param string $value Input field currenet value.
	 * @throws Exception If the field value is invalid.
	 *
	 * @return array
	 */
	public function validate_api_key_field( $key, $value ) {
		$post_data = $this->get_post_data();

		$validate_key  = false;
		$api_key_split = isset( $post_data[ $this->get_field_key( 'api_key_split' ) ] ) ? $post_data[ $this->get_field_key( 'api_key_split' ) ] : false;

		if ( 'api_key' === $key && ! $api_key_split ) {
			$validate_key = true;
		}

		if ( 'api_key_server' === $key && $api_key_split ) {
			$validate_key = true;
		}

		if ( $validate_key ) {
			$response = $this->api->calculate_distance(
				array(
					'key' => $value,
				),
				true
			);

			if ( is_wp_error( $response ) ) {
				// translators: %s = API response error message.
				throw new Exception( sprintf( __( 'Server API Key Error: %s', 'woogosend' ), $response->get_error_message() ) );
			}
		}

		return $value;
	}

	/**
	 * Get API Key for the API request
	 *
	 * @since 1.3
	 *
	 * @return string
	 */
	private function api_request_key() {
		if ( 'yes' === $this->api_key_split ) {
			return $this->api_key_server;
		}

		return $this->api_key;
	}

	/**
	 * Making HTTP request to Google Maps Distance Matrix API
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If error happen.
	 *
	 * @param array   $args Custom arguments for $settings and $package data.
	 * @param boolean $cache Is use the cached data.
	 *
	 * @return array
	 */
	private function api_request( $args = array(), $cache = true ) {
		/**
		 * Early filter the calculated distance matrix info
		 *
		 * @since 1.3
		 *
		 * @param array $info {
		 *      Default matrix info
		 *
		 *      @type int    $distance         Calculated distance.
		 *      @type string $distance_text    Calculated distance in text format.
		 *      @type int    $duration         Calculated uration.
		 *      @type string $duration_text    Calculated uration in text format.
		 *      @type array  $api_request_data Data used to create the request.
		 * }
		 * @param WooGoSend $woogosend Current instance WooGoSend class object.
		 *
		 * @return array
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
			$origin      = $args['origin'];
			$destination = $args['destination'];
			$settings    = wp_parse_args( $args['settings'], $this->instance_settings );
			$package     = $args['package'];

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

				// Check if the data already chached and return it.
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

			$results = $this->api->calculate_distance( $api_request_data );

			if ( is_wp_error( $results ) ) {
				throw new Exception( __( 'API Response Error', 'woogosend' ) . ': ' . $results->get_error_message() );
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

			$distance = floatVal( $this->convert_distance_to_km( $results[0]['distance'] ) );

			if ( empty( $distance ) ) {
				$distance = 0.1;
			}

			if ( 'yes' === $settings['round_up_distance'] ) {
				$distance = ceil( $distance );
			}

			$result = array(
				'distance'         => $distance,
				'distance_text'    => sprintf( '%s km', $distance ),
				'duration'         => $results[0]['duration'],
				'duration_text'    => $results[0]['duration_text'],
				'api_request_data' => $api_request_data,
			);

			if ( $cache && ! $this->is_debug_mode() ) {
				delete_transient( $cache_key ); // To make sure the transient data re-created, delete it first.
				set_transient( $cache_key, $result, HOUR_IN_SECONDS ); // Store the data to transient with expiration in 1 hour for later use.
			}

			$this->show_debug( __( 'API Response', 'woogosend' ) . ': ' . is_string( $result ) ? $result : wp_json_encode( $result ) );

			/**
			 * Filter the calculated distance matrix info
			 *
			 * @since 1.3
			 *
			 * @param array $info {
			 *      Default matrix info
			 *
			 *      @type int    $distance         Calculated distance.
			 *      @type string $distance_text    Calculated distance in text format.
			 *      @type int    $duration         Calculated uration.
			 *      @type string $duration_text    Calculated uration in text format.
			 *      @type array  $api_request_data Data used to create the request.
			 * }
			 * @param WooGoSend $woogosend Current instance WooGoSend class object.
			 *
			 * @return array
			 */
			return apply_filters( 'woogosend_api_request', $result, $this );
		} catch ( Exception $e ) {
			$this->show_debug( $e->getMessage() );

			return new WP_Error( 'api_request', $e->getMessage() );
		}
	}

	/**
	 * Populate field data
	 *
	 * @since 1.3
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

		$data_classes = isset( $data['class'] ) ? explode( ' ', $data['class'] ) : [];

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
			'data-id'          => $this->get_field_key( $key ),
			'data-context'     => isset( $data['context'] ) ? $data['context'] : '',
			'data-title'       => isset( $data['title'] ) ? $data['title'] : $key,
			'data-options'     => isset( $data['options'] ) ? wp_json_encode( $data['options'] ) : wp_json_encode( array() ),
			'data-validate'    => isset( $data['validate'] ) ? $data['validate'] : 'text',
			'data-is_rate'     => empty( $data['is_rate'] ) ? '0' : '1',
			'data-is_required' => empty( $data['is_required'] ) ? '0' : '1',
		);

		$data['custom_attributes'] = array_merge( $data['custom_attributes'], $custom_attributes );

		return $data;
	}

	/**
	 * Sanitize settings value before store to DB.
	 *
	 * @since 1.3
	 *
	 * @param array $settings Current settings data.
	 *
	 * @return array
	 */
	public function instance_settings_values( $settings ) {
		if ( $this->get_errors() ) {
			return $this->fallback_options;
		}

		return $settings;
	}

	/**
	 * Inject cart cart packages to calculate shipping for address 2 field.
	 *
	 * @since 1.3
	 *
	 * @param array $packages Current cart contents packages.
	 *
	 * @return array
	 */
	public function inject_cart_shipping_packages( $packages ) {
		if ( ! $this->is_calc_shipping() ) {
			return $packages;
		}

		$nonce_field  = 'woocommerce-shipping-calculator-nonce';
		$nonce_action = 'woocommerce-shipping-calculator';

		$address_1 = false;
		$address_2 = false;

		if ( isset( $_POST['calc_shipping_address_1'], $_POST['calc_shipping_address_2'], $_POST[ $nonce_field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), $nonce_action ) ) {
			$address_1 = sanitize_text_field( wp_unslash( $_POST['calc_shipping_address_1'] ) );
			$address_2 = sanitize_text_field( wp_unslash( $_POST['calc_shipping_address_2'] ) );
		}

		foreach ( $packages as $key => $package ) {
			if ( false !== $address_1 ) {
				WC()->customer->set_billing_address_1( $address_1 );
				WC()->customer->set_shipping_address_1( $address_1 );
				$packages[ $key ]['destination']['address_1'] = $address_1;
			}

			if ( false !== $address_2 ) {
				WC()->customer->set_billing_address_2( $address_2 );
				WC()->customer->set_shipping_address_2( $address_2 );
				$packages[ $key ]['destination']['address_2'] = $address_2;
			}
		}

		return $packages;
	}

	/**
	 * Check if this method available
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $available Current status is available.
	 * @param array   $package Current order package data.
	 *
	 * @return bool
	 */
	public function check_is_available( $available, $package ) {
		if ( empty( $package['contents'] ) || empty( $package['destination'] ) ) {
			return false;
		}

		return $available;
	}

	/**
	 * Calculate shipping function.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception Throw error if validation not passed.
	 *
	 * @param array $package Package data array.
	 *
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		try {
			$api_response = $this->api_request(
				array(
					'origin'      => $this->get_origin_info( $package ),
					'destination' => $this->get_destination_info( $package ),
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

			foreach ( $this->services->get_data() as $service_id => $service ) {
				if ( 'yes' !== $this->get_option( 'enable_' . $service_id ) ) {
					// translators: %1$s is service ID.
					$this->show_debug( sprintf( __( 'Service is not enabled: %1$s', 'woogosend' ), $service_id ) );
					continue;
				}

				/**
				 * Early filter the service cost info
				 *
				 * @since 1.3
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
				$shipping_cost = apply_filters( 'woogosend_calculate_shipping_cost_pre', false, $service_id, $api_response['distance'], $envelope, $this->instance_settings );

				if ( false === $shipping_cost ) {
					$shipping_cost = $this->services->calculate_cost( $service_id, $api_response['distance'], $envelope, $this->instance_settings );
				}

				if ( ! is_wp_error( $shipping_cost ) ) {
					$label = $this->get_option( 'title_' . $service_id );

					if ( ! $label ) {
						$label = isset( $service['label'] ) ? $service['label'] : $service_id;
					}

					$label_extra = array();

					if ( isset( $shipping_cost['drivers_count'] ) && $shipping_cost['drivers_count'] > 1 ) {
						// translators: %s is the number of the drivers.
						$label_extra[] = sprintf( _n( '%s driver', '%s drivers', $shipping_cost['drivers_count'], 'woogosend' ), $shipping_cost['drivers_count'] );
					}

					if ( 'yes' === $this->show_distance ) {
						$label_extra[] = $api_response['distance_text'];
					}

					if ( $label_extra ) {
						$label = sprintf( '%1$s (%2$s)', $label, implode( ', ', $label_extra ) );
					}

					$total = isset( $shipping_cost['total'] ) ? $shipping_cost['total'] : 0;

					$rate = array(
						'id'        => $this->get_rate_id( $service_id ),
						'label'     => $label,
						'cost'      => $total,
						'meta_data' => array(
							'api_response' => $api_response,
						),
					);

					$this->add_rate( $rate );
				} else {
					$this->show_debug( $shipping_cost->get_error_message() );
				}
			}
		} catch ( Exception $e ) {
			$this->show_debug( $e->getMessage() );
		}
	}

	/**
	 * Get the envelope info: weight, dimension, quantity.
	 *
	 * @since 1.3
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
		 * @since 1.3
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
	 * @since 1.0.0
	 *
	 * @param array $package The cart content data.
	 *
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
		 * Filter the shipping origin info
		 *
		 * @since 1.3
		 *
		 * @param array $origin_info {
		 *      Default origin info
		 *
		 *      @type string $origin_lat     Shipping origin coordinate latitude.
		 *      @type string $origin_lng     Shipping origin coordinate longitude.
		 *      @type string $origin_address Shipping origin address.
		 * }
		 * @param array $package     Current cart data.
		 * @param int   $instance_id Current instance id.
		 *
		 * @return array
		 */
		return apply_filters( 'woogosend_origin_info', $origin_info, $package, $this->get_instance_id() );
	}

	/**
	 * Get shipping destination info
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception Throw error if validation not passed.
	 *
	 * @param array $package The cart content data.
	 *
	 * @return array
	 */
	private function get_destination_info( $package ) {
		/**
		 * Early Filter the shipping destination info
		 *
		 * @since 1.3
		 *
		 * @param array $destination_info {
		 *      Default destination info
		 *
		 *      @type string $country   Shipping country.
		 *      @type string $postcode  Shipping post code.
		 *      @type string $state     Shipping state.
		 *      @type string $city      Shipping city.
		 *      @type string $address_1 Shipping address 1.
		 *      @type string $address_2 Shipping address 2.
		 * }
		 * @param array $package          Current cart data.
		 * @param int   $instance_id      Current instance id.
		 *
		 * @return bool
		 */
		$pre = apply_filters( 'woogosend_destination_info_pre', false, $package, $this->get_instance_id() );

		if ( false !== $pre ) {
			return $pre;
		}

		$destination_info = array();

		// Set initial destination info.
		if ( isset( $package['destination'] ) ) {
			foreach ( $package['destination'] as $key => $value ) {
				if ( 'address' === $key ) {
					continue;
				}

				$destination_info[ $key ] = $value;
			}
		}

		$errors = array();

		$country_code = ! empty( $destination_info['country'] ) ? $destination_info['country'] : false;

		$country_locale = WC()->countries->get_country_locale();

		$rules = $country_locale['default'];

		if ( $country_code && isset( $country_locale[ $country_code ] ) ) {
			$rules = array_merge( $rules, $country_locale[ $country_code ] );
		}

		// Validate shipping fields.
		foreach ( $rules as $rule_key => $rule ) {
			if ( in_array( $rule_key, array( 'first_name', 'last_name', 'company' ), true ) ) {
				continue;
			}

			if ( ! apply_filters( 'woocommerce_shipping_calculator_enable_' . $rule_key, true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				continue;
			}

			$field_value = isset( $destination_info[ $rule_key ] ) ? $destination_info[ $rule_key ] : '';
			$is_required = isset( $rule['required'] ) ? $rule['required'] : false;

			if ( $is_required && ! strlen( strval( $field_value ) ) ) {
				// translators: %s = Field label.
				$errors[ $rule_key ] = sprintf( __( 'Shipping destination field is empty: %s', 'woogosend' ), $rule['label'] );
			}

			if ( $country_code && $field_value && 'postcode' === $rule_key && ! WC_Validation::is_postcode( $field_value, $country_code ) ) {
				// translators: %s = Field label.
				$errors[ $rule_key ] = sprintf( __( 'Shipping destination field is invalid: %s', 'woogosend' ), $rule['label'] );
			}
		}

		if ( $errors ) {
			// Set debug if error.
			foreach ( $errors as $error ) {
				$this->show_debug( $error );
			}

			// Reset destionation info if error.
			$destination_info = array();
		} else {
			$destination_array = array();
			$states            = WC()->countries->states;
			$countries         = WC()->countries->countries;

			foreach ( $destination_info as $key => $value ) {
				// Skip for empty field.
				if ( ! strlen( strval( $field_value ) ) ) {
					continue;
				}

				if ( ! apply_filters( 'woocommerce_shipping_calculator_enable_' . $key, true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					continue;
				}

				switch ( $key ) {
					case 'country':
						if ( ! $country_code ) {
							$country_code = $value;
						}

						$destination_array[ $key ] = isset( $countries[ $value ] ) ? $countries[ $value ] : $value; // Set country full name.
						break;

					case 'state':
						if ( ! $country_code ) {
							$country_code = isset( $destination_info['country'] ) ? $destination_info['country'] : 'undefined';
						}

						$destination_array[ $key ] = isset( $states[ $country_code ][ $value ] ) ? $states[ $country_code ][ $value ] : $value; // Set state full name.
						break;

					default:
						$destination_array[ $key ] = $value;
						break;
				}
			}

			$destination_info = WC()->countries->get_formatted_address( $destination_array, ', ' );
		}

		/**
		 * Filter the shipping destination info
		 *
		 * @since 1.0.1
		 *
		 * @param array $destination_info {
		 *      Default destination info
		 *
		 *      @type string $country   Shipping country.
		 *      @type string $postcode  Shipping post code.
		 *      @type string $state     Shipping state.
		 *      @type string $city      Shipping city.
		 *      @type string $address_1 Shipping address 1.
		 *      @type string $address_2 Shipping address 2.
		 * }
		 * @param array $package     Current cart data.
		 * @param int   $instance_id Current instance id.
		 *
		 * @return array
		 */
		return apply_filters( 'woogosend_destination_info', $destination_info, $package, $this->get_instance_id() );
	}

	/**
	 * Check if current request is shipping calculator form.
	 *
	 * @since 1.3
	 *
	 * @return bool
	 */
	public function is_calc_shipping() {
		$nonce_field  = 'woocommerce-shipping-calculator-nonce';
		$nonce_action = 'woocommerce-shipping-calculator';

		if ( isset( $_POST['calc_shipping'], $_POST[ $nonce_field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), $nonce_action ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Convert Meters to Miles
	 *
	 * @since 1.3
	 *
	 * @param int $meters Number of meters to convert.
	 *
	 * @return int
	 */
	public function convert_distance_to_mi( $meters ) {
		return wc_format_decimal( ( $meters * 0.000621371 ), 1 );
	}

	/**
	 * Convert Meters to Kilometres
	 *
	 * @since 1.3
	 *
	 * @param int $meters Number of meters to convert.
	 *
	 * @return int
	 */
	public function convert_distance_to_km( $meters ) {
		return wc_format_decimal( ( $meters * 0.001 ), 1 );
	}

	/**
	 * Sort ascending API response array by duration.
	 *
	 * @since 1.3
	 *
	 * @param array $a Array 1 that will be sorted.
	 * @param array $b Array 2 that will be compared.
	 *
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
	 * @since 1.3
	 *
	 * @param array $a Array 1 that will be sorted.
	 * @param array $b Array 2 that will be compared.
	 *
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
	 * @since 1.3
	 *
	 * @param array $a Array 1 that will be sorted.
	 * @param array $b Array 2 that will be compared.
	 *
	 * @return int
	 */
	public function shortest_distance_results( $a, $b ) {
		if ( $a['max_distance'] === $b['max_distance'] ) {
			return 0;
		}
		return ( $a['max_distance'] < $b['max_distance'] ) ? -1 : 1;
	}

	/**
	 * Sort descending API response array by distance.
	 *
	 * @since 1.3
	 *
	 * @param array $a Array 1 that will be sorted.
	 * @param array $b Array 2 that will be compared.
	 *
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
	 * @since 1.3
	 *
	 * @return bool
	 */
	public function is_debug_mode() {
		return get_option( 'woocommerce_shipping_debug_mode', 'no' ) === 'yes';
	}

	/**
	 * Show debug info
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The text to display in the notice.
	 *
	 * @return void
	 */
	public function show_debug( $message ) {
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

		wc_add_notice( $debug_prefix . ' => ' . $message, 'notice' );
	}
}
