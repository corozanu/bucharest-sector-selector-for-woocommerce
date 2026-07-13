<?php
/**
 * WooCommerce Blocks checkout field registration.
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BSSWOO_Blocks_Fields
 */
class BSSWOO_Blocks_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_init', array( $this, 'register_fields' ) );
		add_action( 'woocommerce_blocks_validate_location_address_fields', array( $this, 'validate_address_fields' ), 10, 3 );
	}

	/**
	 * Register the sector field for WooCommerce Checkout Blocks.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		if ( ! BSSWOO_Helpers::is_enabled() || ! BSSWOO_Helpers::is_blocks_api_available() ) {
			return;
		}

		$field_id = BSSWOO_Helpers::get_blocks_field_id();

		try {
			woocommerce_register_additional_checkout_field(
				array(
					'id'                => $field_id,
					'label'             => __( 'Sector București', 'bucharest-sector-selector-for-woocommerce' ),
					'location'          => 'address',
					'type'              => 'select',
					'hidden'            => BSSWOO_Helpers::get_blocks_hidden_schema(),
					'required'          => BSSWOO_Helpers::get_blocks_required_schema(),
					'options'           => BSSWOO_Helpers::get_blocks_select_options(),
					'sanitize_callback' => array( $this, 'sanitize_field' ),
					'validate_callback' => array( $this, 'validate_field' ),
				)
			);
		} catch ( Exception $exception ) {
			BSSWOO_Helpers::debug_log(
				'Failed to register Blocks checkout field.',
				array(
					'field_id' => $field_id,
					'error'    => $exception->getMessage(),
				)
			);
			return;
		}

		BSSWOO_Helpers::debug_log(
			'Blocks checkout sector field registered.',
			array( 'field_id' => $field_id )
		);

		/**
		 * Fires after the Blocks checkout sector field has been registered.
		 *
		 * @param string $field_id Registered field ID.
		 */
		do_action( 'bsswoo_blocks_field_registered', $field_id );
	}

	/**
	 * Sanitize the Blocks sector field value.
	 *
	 * @param mixed $value Field value.
	 * @return string
	 */
	public function sanitize_field( mixed $value ): string {
		return BSSWOO_Helpers::sanitize_sector( $value );
	}

	/**
	 * Validate the Blocks sector field value.
	 *
	 * @param mixed  $value Field value.
	 * @param string $group billing|shipping|other.
	 * @return true|WP_Error
	 */
	public function validate_field( mixed $value, string $group ) {
		if ( ! in_array( $group, array( 'billing', 'shipping' ), true ) ) {
			return true;
		}

		if ( 'shipping' === $group && ! BSSWOO_Helpers::is_shipping_enabled() ) {
			return true;
		}

		$sector = BSSWOO_Helpers::sanitize_sector( $value );

		if ( '' !== $sector ) {
			return true;
		}

		return new WP_Error(
			'bsswoo_missing_sector',
			BSSWOO_Helpers::get_missing_sector_message( $group )
		);
	}

	/**
	 * Validate address additional fields during Blocks checkout.
	 *
	 * @param WP_Error              $errors Validation errors.
	 * @param array<string, mixed>  $fields Additional field values.
	 * @param string                $group  billing|shipping|other.
	 * @return void
	 */
	public function validate_address_fields( WP_Error $errors, array $fields, string $group ): void {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		if ( ! in_array( $group, array( 'billing', 'shipping' ), true ) ) {
			return;
		}

		if ( 'shipping' === $group && ! BSSWOO_Helpers::is_shipping_enabled() ) {
			return;
		}

		$field_id = BSSWOO_Helpers::get_blocks_field_id();
		$sector   = BSSWOO_Helpers::sanitize_sector( $fields[ $field_id ] ?? '' );
		$state    = $this->get_address_state_for_group( $group );

		if ( ! BSSWOO_Helpers::is_bucharest_state( $state ) ) {
			return;
		}

		BSSWOO_Helpers::debug_log(
			'Bucharest state detected during Blocks checkout validation.',
			array(
				'context' => $group,
				'state'   => $state,
				'sector'  => $sector,
			)
		);

		if ( '' !== $sector ) {
			return;
		}

		BSSWOO_Helpers::debug_log(
			'Blocks validation failed: missing sector for Bucharest.',
			array( 'context' => $group )
		);

		$errors->add( 'bsswoo_missing_sector', BSSWOO_Helpers::get_missing_sector_message( $group ) );
	}

	/**
	 * Resolve the state for the address group being validated.
	 *
	 * @param string $group billing|shipping.
	 * @return string
	 */
	private function get_address_state_for_group( string $group ): string {
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			return '';
		}

		$customer = WC()->customer;

		return 'billing' === $group
			? (string) $customer->get_billing_state()
			: (string) $customer->get_shipping_state();
	}
}
