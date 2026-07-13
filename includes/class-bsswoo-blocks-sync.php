<?php
/**
 * Sync Blocks checkout sector data with legacy order meta and city fields.
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BSSWOO_Blocks_Sync
 */
class BSSWOO_Blocks_Sync {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_set_additional_field_value', array( $this, 'sync_additional_field_value' ), 10, 4 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'sync_order_from_store_api' ), 20, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'sync_order_on_create' ), 25, 1 );
		add_action( 'woocommerce_store_api_cart_update_customer_from_request', array( $this, 'sync_customer_from_store_api' ), 20, 2 );
	}

	/**
	 * Mirror Blocks field values into legacy meta keys for e-Factura integrations.
	 *
	 * @param string   $key        Field key.
	 * @param mixed    $value      Field value.
	 * @param string   $group      billing|shipping|other.
	 * @param WC_Order|WC_Customer|WC_Subscription $wc_object Object being updated.
	 * @return void
	 */
	public function sync_additional_field_value( string $key, mixed $value, string $group, $wc_object ): void {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		if ( BSSWOO_Helpers::get_blocks_field_id() !== $key ) {
			return;
		}

		if ( ! in_array( $group, array( 'billing', 'shipping' ), true ) ) {
			return;
		}

		if ( 'shipping' === $group && ! BSSWOO_Helpers::is_shipping_enabled() ) {
			return;
		}

		$sector   = BSSWOO_Helpers::sanitize_sector( $value );
		$meta_key = BSSWOO_Helpers::get_sector_meta_key( $group );

		if ( '' === $sector ) {
			if ( is_object( $wc_object ) && method_exists( $wc_object, 'delete_meta_data' ) ) {
				$wc_object->delete_meta_data( $meta_key );
			}
			return;
		}

		if ( ! is_object( $wc_object ) || ! method_exists( $wc_object, 'update_meta_data' ) ) {
			return;
		}

		$wc_object->update_meta_data( $meta_key, $sector );

		if ( $wc_object instanceof WC_Order ) {
			$state = 'billing' === $group ? $wc_object->get_billing_state() : $wc_object->get_shipping_state();
			BSSWOO_Helpers::apply_sector_to_order( $wc_object, $group, $state, $sector );
			return;
		}

		if ( $wc_object instanceof WC_Customer ) {
			if ( 'billing' === $group ) {
				$wc_object->set_billing_city( $sector );
			} else {
				$wc_object->set_shipping_city( $sector );
			}
		}

		BSSWOO_Helpers::debug_log(
			'Blocks field mirrored to legacy meta.',
			array(
				'context' => $group,
				'sector'  => $sector,
			)
		);
	}

	/**
	 * Ensure sector and city are synced when Blocks checkout creates an order.
	 *
	 * @param WC_Order        $order   Order object.
	 * @param WP_REST_Request $request Request object.
	 * @return void
	 */
	public function sync_order_from_store_api( WC_Order $order, WP_REST_Request $request ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		$this->sync_all_order_contexts( $order );
	}

	/**
	 * Fallback sync for any checkout flow that creates an order object.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function sync_order_on_create( WC_Order $order ): void {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		$this->sync_all_order_contexts( $order );
	}

	/**
	 * Sync billing and shipping sector/city values on an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	private function sync_all_order_contexts( WC_Order $order ): void {
		$contexts = array( 'billing' );

		if ( BSSWOO_Helpers::is_shipping_enabled() ) {
			$contexts[] = 'shipping';
		}

		foreach ( $contexts as $context ) {
			$state  = 'billing' === $context ? $order->get_billing_state() : $order->get_shipping_state();
			$sector = BSSWOO_Helpers::get_order_sector( $order, $context );

			if ( ! BSSWOO_Helpers::is_bucharest_state( $state ) ) {
				$order->delete_meta_data( BSSWOO_Helpers::get_sector_meta_key( $context ) );
				continue;
			}

			if ( '' === $sector ) {
				continue;
			}

			BSSWOO_Helpers::apply_sector_to_order( $order, $context, $state, $sector );
		}

		/**
		 * Fires after Blocks sector data has been synced on an order.
		 *
		 * @param WC_Order $order Order object.
		 */
		do_action( 'bsswoo_blocks_order_synced', $order );
	}

	/**
	 * Sync city values when Blocks checkout updates the customer via Store API.
	 *
	 * @param WC_Customer     $customer Customer object.
	 * @param WP_REST_Request $request  Request object.
	 * @return void
	 */
	public function sync_customer_from_store_api( WC_Customer $customer, WP_REST_Request $request ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		BSSWOO_Helpers::sync_customer_city_from_sector( $customer, 'billing' );

		if ( BSSWOO_Helpers::is_shipping_enabled() ) {
			BSSWOO_Helpers::sync_customer_city_from_sector( $customer, 'shipping' );
		}
	}
}
