<?php
/**
 * Validation and order data persistence.
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BSSWOO_Validation
 */
class BSSWOO_Validation {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_order_data' ), 20, 2 );
		add_action( 'woocommerce_after_save_address_validation', array( $this, 'validate_account_address' ), 10, 5 );
		add_action( 'woocommerce_customer_save_address', array( $this, 'save_account_address' ), 10, 2 );
		add_filter( 'woocommerce_process_myaccount_field_billing_sector_bucuresti', array( $this, 'sanitize_sector_field' ) );
		add_filter( 'woocommerce_process_myaccount_field_shipping_sector_bucuresti', array( $this, 'sanitize_sector_field' ) );
	}

	/**
	 * Validate checkout sector selection.
	 *
	 * @return void
	 */
	public function validate_checkout(): void {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		$this->validate_context( 'billing' );

		if ( ! BSSWOO_Helpers::is_shipping_enabled() ) {
			return;
		}

		$ship_to_different = isset( $_POST['ship_to_different_address'] ) && wc_string_to_bool( wp_unslash( $_POST['ship_to_different_address'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( $ship_to_different ) {
			$this->validate_context( 'shipping' );
		}
	}

	/**
	 * Validate a single address context.
	 *
	 * @param string $context billing|shipping.
	 * @return void
	 */
	private function validate_context( string $context ): void {
		$state_key  = $context . '_state';
		$sector_key = $context . '_sector_bucuresti';

		$state  = isset( $_POST[ $state_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $state_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$sector = isset( $_POST[ $sector_key ] ) ? BSSWOO_Helpers::sanitize_sector( wp_unslash( $_POST[ $sector_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! BSSWOO_Helpers::is_bucharest_state( $state ) ) {
			return;
		}

		BSSWOO_Helpers::debug_log(
			'Bucharest state detected during checkout validation.',
			array(
				'context' => $context,
				'state'   => $state,
				'sector'  => $sector,
			)
		);

		if ( '' === $sector ) {
			BSSWOO_Helpers::debug_log(
				'Validation failed: missing sector for Bucharest.',
				array( 'context' => $context )
			);

			$message = BSSWOO_Helpers::get_missing_sector_message( $context );

			wc_add_notice( $message, 'error' );
			return;
		}

		$city_key = $context . '_city';

		if ( isset( $_POST[ $city_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$_POST[ $city_key ] = $sector; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		}

		BSSWOO_Helpers::debug_log(
			'City synced to selected sector during validation.',
			array(
				'context' => $context,
				'city'    => $sector,
			)
		);
	}

	/**
	 * Persist sector and city values on the order.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $data  Posted checkout data.
	 * @return void
	 */
	public function save_order_data( WC_Order $order, array $data ): void {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		$this->persist_order_context( $order, 'billing', $data );

		if ( BSSWOO_Helpers::is_shipping_enabled() ) {
			$this->persist_order_context( $order, 'shipping', $data );
		}

		/**
		 * Fires after order sector data has been saved.
		 *
		 * @param WC_Order $order Order object.
		 * @param array    $data  Checkout data.
		 */
		do_action( 'bsswoo_order_data_saved', $order, $data );
	}

	/**
	 * Persist sector data for one address context on the order.
	 *
	 * @param WC_Order $order   Order object.
	 * @param string   $context billing|shipping.
	 * @param array    $data    Checkout data.
	 * @return void
	 */
	private function persist_order_context( WC_Order $order, string $context, array $data ): void {
		$state_key  = $context . '_state';
		$sector_key = $context . '_sector_bucuresti';

		$state  = $data[ $state_key ] ?? '';
		$sector = BSSWOO_Helpers::sanitize_sector( $data[ $sector_key ] ?? '' );

		if ( '' === $sector ) {
			$sector = BSSWOO_Helpers::get_blocks_sector_from_order( $order, $context );
		}

		if ( ! BSSWOO_Helpers::is_bucharest_state( $state ) ) {
			$order->delete_meta_data( BSSWOO_Helpers::get_sector_meta_key( $context ) );
			return;
		}

		BSSWOO_Helpers::apply_sector_to_order( $order, $context, $state, $sector );
	}

	/**
	 * Validate My Account address save.
	 *
	 * @param int           $user_id      User ID.
	 * @param string        $address_type billing|shipping.
	 * @param array         $address      Address data.
	 * @param WC_Customer $customer     Customer object.
	 * @param WP_Error    $errors       Validation errors.
	 * @return void
	 */
	public function validate_account_address( int $user_id, string $address_type, array $address, WC_Customer $customer, WP_Error $errors ): void {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		if ( 'shipping' === $address_type && ! BSSWOO_Helpers::is_shipping_enabled() ) {
			return;
		}

		$state_key  = $address_type . '_state';
		$sector_key = $address_type . '_sector_bucuresti';

		$state  = $address[ $state_key ] ?? '';
		$sector = BSSWOO_Helpers::sanitize_sector( $address[ $sector_key ] ?? '' );

		if ( ! BSSWOO_Helpers::is_bucharest_state( $state ) ) {
			return;
		}

		if ( '' === $sector ) {
			BSSWOO_Helpers::debug_log(
				'Account address validation failed: missing sector for Bucharest.',
				array(
					'user_id' => $user_id,
					'context' => $address_type,
				)
			);

			$errors->add( 'bsswoo_missing_sector', BSSWOO_Helpers::get_missing_sector_message( $address_type ) );
			return;
		}

		$city_key = $address_type . '_city';

		if ( isset( $_POST[ $city_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$_POST[ $city_key ] = $sector; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		}
	}

	/**
	 * Save sector data when customer updates address in My Account.
	 *
	 * @param int    $user_id      User ID.
	 * @param string $address_type billing|shipping.
	 * @return void
	 */
	public function save_account_address( int $user_id, string $address_type ): void {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		if ( 'shipping' === $address_type && ! BSSWOO_Helpers::is_shipping_enabled() ) {
			return;
		}

		$sector_key = $address_type . '_sector_bucuresti';
		$state_key  = $address_type . '_state';
		$city_key   = $address_type . '_city';

		$state  = isset( $_POST[ $state_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $state_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$sector = isset( $_POST[ $sector_key ] ) ? BSSWOO_Helpers::sanitize_sector( wp_unslash( $_POST[ $sector_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! BSSWOO_Helpers::is_bucharest_state( $state ) ) {
			delete_user_meta( $user_id, $sector_key );
			return;
		}

		if ( '' === $sector ) {
			return;
		}

		update_user_meta( $user_id, $sector_key, $sector );
		update_user_meta( $user_id, $city_key, $sector );

		BSSWOO_Helpers::debug_log(
			'Account address city synced to sector.',
			array(
				'user_id' => $user_id,
				'context' => $address_type,
				'sector'  => $sector,
			)
		);
	}

	/**
	 * Sanitize sector field on My Account save.
	 *
	 * @param mixed $value Field value.
	 * @return string
	 */
	public function sanitize_sector_field( mixed $value ): string {
		return BSSWOO_Helpers::sanitize_sector( $value );
	}
}
