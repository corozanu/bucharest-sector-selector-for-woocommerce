<?php
/**
 * Plugin Name:       Bucharest Sector Selector for WooCommerce
 * Plugin URI:        https://github.com/corozanu/bucharest-sector-selector-for-woocommerce
 * Description:       Adds Bucharest sector selection at checkout for e-Factura / SPV ANAF address compatibility.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            Catalin Corozanu
 * Author URI:        https://crz.ro
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bucharest-sector-selector-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.4
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

define( 'BSSWOO_VERSION', '1.3.0' );
define( 'BSSWOO_PLUGIN_FILE', __FILE__ );
define( 'BSSWOO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BSSWOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BSSWOO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload plugin classes.
 *
 * @param string $class_name Class name.
 */
function bsswoo_autoload( string $class_name ): void {
	if ( 0 !== strpos( $class_name, 'BSSWOO_' ) ) {
		return;
	}

	$file = BSSWOO_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

spl_autoload_register( 'bsswoo_autoload' );

/**
 * Migrate legacy option names from older plugin prefixes.
 *
 * @return void
 */
function bsswoo_migrate_legacy_options(): void {
	$map = array(
		'bsswoo_enabled'           => array( 'wcbse_enabled', 'swbe_enabled' ),
		'bsswoo_shipping_enabled'  => array( 'wcbse_shipping_enabled', 'swbe_shipping_enabled' ),
		'bsswoo_hide_city'         => array( 'wcbse_hide_city', 'swbe_hide_city' ),
		'bsswoo_readonly_city'     => array( 'wcbse_readonly_city', 'swbe_readonly_city' ),
		'bsswoo_debug'             => array( 'wcbse_debug', 'swbe_debug' ),
	);

	foreach ( $map as $new_key => $legacy_keys ) {
		if ( false !== get_option( $new_key, false ) ) {
			continue;
		}

		foreach ( $legacy_keys as $legacy_key ) {
			$legacy_value = get_option( $legacy_key, false );

			if ( false !== $legacy_value ) {
				update_option( $new_key, $legacy_value );
				break;
			}
		}
	}
}
register_activation_hook( __FILE__, 'bsswoo_migrate_legacy_options' );

/**
 * Initialize plugin after plugins are loaded.
 */
function bsswoo_init(): void {
	load_plugin_textdomain(
		'bucharest-sector-selector-for-woocommerce',
		false,
		dirname( BSSWOO_PLUGIN_BASENAME ) . '/languages'
	);

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'bsswoo_woocommerce_missing_notice' );
		return;
	}

	BSSWOO_Plugin::instance();
}
add_action( 'plugins_loaded', 'bsswoo_init' );

/**
 * Display admin notice when WooCommerce is not active.
 */
function bsswoo_woocommerce_missing_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__(
			'Bucharest Sector Selector for WooCommerce requires WooCommerce to be installed and active.',
			'bucharest-sector-selector-for-woocommerce'
		)
	);
}
