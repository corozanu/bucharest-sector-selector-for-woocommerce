<?php
/**
 * Admin order display for Bucharest sector data.
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BSSWOO_Admin_Order
 */
class BSSWOO_Admin_Order {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'render_billing_sector' ), 20, 1 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'render_shipping_sector' ), 20, 1 );
	}

	/**
	 * Display billing sector in admin order screen.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function render_billing_sector( WC_Order $order ): void {
		$this->render_sector( $order, 'billing' );
	}

	/**
	 * Display shipping sector in admin order screen.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function render_shipping_sector( WC_Order $order ): void {
		if ( ! BSSWOO_Helpers::is_shipping_enabled() ) {
			return;
		}

		$this->render_sector( $order, 'shipping' );
	}

	/**
	 * Render sector information for an address context.
	 *
	 * @param WC_Order $order   Order object.
	 * @param string   $context billing|shipping.
	 * @return void
	 */
	private function render_sector( WC_Order $order, string $context ): void {
		$sector = BSSWOO_Helpers::get_order_sector( $order, $context );

		if ( '' === $sector ) {
			return;
		}

		echo '<p><strong>' . esc_html__( 'Sector București', 'bucharest-sector-selector-for-woocommerce' ) . ':</strong> ' . esc_html( $sector ) . '</p>';
	}
}
