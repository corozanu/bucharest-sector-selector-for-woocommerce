<?php
/**
 * Core plugin bootstrap class.
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BSSWOO_Plugin
 */
class BSSWOO_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var BSSWOO_Plugin|null
	 */
	private static ?BSSWOO_Plugin $instance = null;

	/**
	 * Checkout fields handler.
	 *
	 * @var BSSWOO_Checkout_Fields
	 */
	public BSSWOO_Checkout_Fields $checkout_fields;

	/**
	 * Frontend assets handler.
	 *
	 * @var BSSWOO_Frontend_Assets
	 */
	public BSSWOO_Frontend_Assets $frontend_assets;

	/**
	 * Validation and persistence handler.
	 *
	 * @var BSSWOO_Validation
	 */
	public BSSWOO_Validation $validation;

	/**
	 * Admin order display handler.
	 *
	 * @var BSSWOO_Admin_Order
	 */
	public BSSWOO_Admin_Order $admin_order;

	/**
	 * Get singleton instance.
	 *
	 * @return BSSWOO_Plugin
	 */
	public static function instance(): BSSWOO_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->checkout_fields = new BSSWOO_Checkout_Fields();
		$this->frontend_assets = new BSSWOO_Frontend_Assets();
		$this->validation      = new BSSWOO_Validation();
		$this->admin_order     = new BSSWOO_Admin_Order();

		add_filter( 'woocommerce_get_settings_pages', array( $this, 'register_settings_page' ) );

		/**
		 * Fires after the plugin core has been initialized.
		 *
		 * @param BSSWOO_Plugin $plugin Plugin instance.
		 */
		do_action( 'bsswoo_plugin_loaded', $this );
	}

	/**
	 * Register the WooCommerce settings tab after WC admin classes are available.
	 *
	 * @param array<int, WC_Settings_Page> $settings Existing settings pages.
	 * @return array<int, WC_Settings_Page>
	 */
	public function register_settings_page( array $settings ): array {
		if ( ! class_exists( 'WC_Settings_Page', false ) ) {
			return $settings;
		}

		require_once BSSWOO_PLUGIN_DIR . 'includes/class-bsswoo-admin-settings.php';

		$settings[] = new BSSWOO_Admin_Settings();

		return $settings;
	}
}
