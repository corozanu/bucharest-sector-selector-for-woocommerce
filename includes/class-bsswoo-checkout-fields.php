<?php
/**
 * Register and manage checkout / account address fields.
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BSSWOO_Checkout_Fields
 */
class BSSWOO_Checkout_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_checkout_fields' ), 20 );
		add_filter( 'woocommerce_billing_fields', array( $this, 'add_billing_fields' ), 20 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_fields' ), 20 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'adjust_default_address_fields' ), 20 );
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'get_checkout_value' ), 20, 2 );
	}

	/**
	 * Add sector fields to checkout.
	 *
	 * @param array<string, array<string, array<string, mixed>>> $fields Checkout fields.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public function add_checkout_fields( array $fields ): array {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return $fields;
		}

		if ( isset( $fields['billing'] ) ) {
			$fields['billing'] = $this->inject_sector_field( $fields['billing'], 'billing' );
		}

		if ( BSSWOO_Helpers::is_shipping_enabled() && isset( $fields['shipping'] ) ) {
			$fields['shipping'] = $this->inject_sector_field( $fields['shipping'], 'shipping' );
		}

		return $fields;
	}

	/**
	 * Add sector field to billing address forms.
	 *
	 * @param array<string, array<string, mixed>> $fields Billing fields.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_billing_fields( array $fields ): array {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return $fields;
		}

		return $this->inject_sector_field( $fields, 'billing' );
	}

	/**
	 * Add sector field to shipping address forms.
	 *
	 * @param array<string, array<string, mixed>> $fields Shipping fields.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_shipping_fields( array $fields ): array {
		if ( ! BSSWOO_Helpers::is_enabled() || ! BSSWOO_Helpers::is_shipping_enabled() ) {
			return $fields;
		}

		return $this->inject_sector_field( $fields, 'shipping' );
	}

	/**
	 * Adjust default address field classes for JS targeting.
	 *
	 * @param array<string, array<string, mixed>> $fields Default address fields.
	 * @return array<string, array<string, mixed>>
	 */
	public function adjust_default_address_fields( array $fields ): array {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return $fields;
		}

		if ( isset( $fields['city'] ) ) {
			$fields['city']['class'][] = 'bsswoo-city-field';
		}

		if ( isset( $fields['state'] ) ) {
			$fields['state']['class'][] = 'bsswoo-state-field';
		}

		return $fields;
	}

	/**
	 * Inject sector field after state field.
	 *
	 * @param array<string, array<string, mixed>> $fields  Address fields.
	 * @param string                              $prefix  billing|shipping.
	 * @return array<string, array<string, mixed>>
	 */
	private function inject_sector_field( array $fields, string $prefix ): array {
		$field_key = $prefix . '_sector_bucuresti';
		$new_field = BSSWOO_Helpers::get_sector_field_args( $prefix );

		/**
		 * Fires when a sector field is registered for an address context.
		 *
		 * @param string               $field_key Field key.
		 * @param array<string, mixed> $new_field Field definition.
		 * @param string               $prefix    Address context.
		 */
		do_action( 'bsswoo_sector_field_registered', $field_key, $new_field, $prefix );

		$reordered = array();
		$inserted  = false;

		foreach ( $fields as $key => $field ) {
			$reordered[ $key ] = $field;

			if ( $prefix . '_state' === $key ) {
				$reordered[ $field_key ] = $new_field;
				$inserted                = true;
			}
		}

		if ( ! $inserted ) {
			$reordered[ $field_key ] = $new_field;
		}

		$reordered[ $field_key ] = $this->maybe_set_sector_default( $reordered[ $field_key ], $prefix, $field_key );

		if ( isset( $reordered[ $prefix . '_city' ] ) ) {
			$reordered[ $prefix . '_city' ]['class'][] = 'bsswoo-city-field';
		}

		if ( isset( $reordered[ $prefix . '_state' ] ) ) {
			$reordered[ $prefix . '_state' ]['class'][] = 'bsswoo-state-field';
		}

		return $reordered;
	}

	/**
	 * Provide checkout values for sector fields and sync from existing city data.
	 *
	 * @param mixed  $value Field value.
	 * @param string $input Field key.
	 * @return mixed
	 */
	public function get_checkout_value( mixed $value, string $input ) {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return $value;
		}

		if ( ! in_array( $input, array( 'billing_sector_bucuresti', 'shipping_sector_bucuresti' ), true ) ) {
			return $value;
		}

		if ( null !== $value && '' !== $value ) {
			return $value;
		}

		$context = str_starts_with( $input, 'billing_' ) ? 'billing' : 'shipping';

		if ( 'shipping' === $context && ! BSSWOO_Helpers::is_shipping_enabled() ) {
			return $value;
		}

		if ( is_user_logged_in() ) {
			$user_id     = get_current_user_id();
			$stored_meta = get_user_meta( $user_id, $input, true );

			if ( is_string( $stored_meta ) && '' !== $stored_meta ) {
				return $stored_meta;
			}

			$city = get_user_meta( $user_id, $context . '_city', true );

			if ( BSSWOO_Helpers::is_sector_city( $city ) ) {
				return $city;
			}
		}

		return $value;
	}

	/**
	 * Set sector default from stored meta or existing city value on account pages.
	 *
	 * @param array<string, mixed> $field     Field definition.
	 * @param string               $prefix    billing|shipping.
	 * @param string               $field_key Field key.
	 * @return array<string, mixed>
	 */
	private function maybe_set_sector_default( array $field, string $prefix, string $field_key ): array {
		if ( ! is_user_logged_in() || ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return $field;
		}

		$user_id = get_current_user_id();
		$stored  = get_user_meta( $user_id, $field_key, true );

		if ( is_string( $stored ) && '' !== $stored ) {
			$field['default'] = $stored;
			return $field;
		}

		$city = get_user_meta( $user_id, $prefix . '_city', true );

		if ( BSSWOO_Helpers::is_sector_city( $city ) ) {
			$field['default'] = $city;
		}

		return $field;
	}
}
