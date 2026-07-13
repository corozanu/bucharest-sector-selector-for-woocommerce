<?php
/**
 * Enqueue frontend scripts and styles.
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BSSWOO_Frontend_Assets
 */
class BSSWOO_Frontend_Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets on checkout and My Account address pages.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! BSSWOO_Helpers::is_enabled() ) {
			return;
		}

		if ( ! $this->should_enqueue() ) {
			return;
		}

		wp_enqueue_style(
			'bsswoo-checkout',
			BSSWOO_PLUGIN_URL . 'assets/css/checkout.css',
			array(),
			BSSWOO_VERSION
		);

		$config = array(
			'bucharestStates'  => $this->get_bucharest_state_values(),
			'sectorValues'     => BSSWOO_Helpers::SECTOR_VALUES,
			'hideCity'         => array(
				'billing'  => BSSWOO_Helpers::should_hide_city( 'billing' ),
				'shipping' => BSSWOO_Helpers::should_hide_city( 'shipping' ),
			),
			'readonlyCity'     => array(
				'billing'  => BSSWOO_Helpers::should_readonly_city( 'billing' ),
				'shipping' => BSSWOO_Helpers::should_readonly_city( 'shipping' ),
			),
			'shippingEnabled'  => BSSWOO_Helpers::is_shipping_enabled(),
			'selectSectorText' => __( 'Selectează sectorul', 'bucharest-sector-selector-for-woocommerce' ),
		);

		if ( $this->is_block_checkout() && BSSWOO_Helpers::is_blocks_api_available() ) {
			$this->enqueue_blocks_assets( $config );
			return;
		}

		$this->enqueue_classic_assets( $config );
	}

	/**
	 * Enqueue classic checkout and My Account assets.
	 *
	 * @param array<string, mixed> $config Shared script configuration.
	 * @return void
	 */
	private function enqueue_classic_assets( array $config ): void {
		$dependencies = array( 'jquery' );

		if ( wp_script_is( 'wc-checkout', 'registered' ) ) {
			$dependencies[] = 'wc-checkout';
		}

		wp_enqueue_script(
			'bsswoo-checkout',
			BSSWOO_PLUGIN_URL . 'assets/js/checkout.js',
			$dependencies,
			BSSWOO_VERSION,
			true
		);

		wp_localize_script( 'bsswoo-checkout', 'bsswooCheckout', $config );
	}

	/**
	 * Enqueue WooCommerce Checkout Block assets.
	 *
	 * @param array<string, mixed> $config Shared script configuration.
	 * @return void
	 */
	private function enqueue_blocks_assets( array $config ): void {
		$dependencies = array( 'wp-data' );

		if ( wp_script_is( 'wc-blocks-checkout', 'registered' ) ) {
			$dependencies[] = 'wc-blocks-checkout';
		}

		wp_enqueue_script(
			'bsswoo-checkout-blocks',
			BSSWOO_PLUGIN_URL . 'assets/js/checkout-blocks.js',
			$dependencies,
			BSSWOO_VERSION,
			true
		);

		$config['blocksFieldId'] = BSSWOO_Helpers::get_blocks_field_id();

		wp_localize_script( 'bsswoo-checkout-blocks', 'bsswooBlocks', $config );
	}

	/**
	 * Determine whether assets should load on the current page.
	 *
	 * @return bool
	 */
	private function should_enqueue(): bool {
		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
			return true;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			global $wp;

			if ( isset( $wp->query_vars['edit-address'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether the current checkout page uses the Checkout Block.
	 *
	 * @return bool
	 */
	private function is_block_checkout(): bool {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return false;
		}

		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( ! function_exists( 'has_block' ) ) {
			return false;
		}

		return has_block( 'woocommerce/checkout', $post );
	}

	/**
	 * Collect possible Bucharest state values for frontend detection.
	 *
	 * @return string[]
	 */
	private function get_bucharest_state_values(): array {
		$values = array( 'B', 'BUCURESTI', 'București', 'Bucuresti', 'BUCUREȘTI' );

		if ( function_exists( 'WC' ) && WC()->countries ) {
			$states = WC()->countries->get_states( 'RO' );

			if ( is_array( $states ) ) {
				foreach ( $states as $code => $label ) {
					if ( BSSWOO_Helpers::is_bucharest_state( $code ) || BSSWOO_Helpers::is_bucharest_state( $label ) ) {
						$values[] = (string) $code;
						$values[] = (string) $label;
					}
				}
			}
		}

		return array_values( array_unique( $values ) );
	}
}
