<?php
/**
 * Uninstall routine.
 *
 * @package BSSWOO
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$bsswoo_options = array(
	'bsswoo_enabled',
	'bsswoo_shipping_enabled',
	'bsswoo_hide_city',
	'bsswoo_readonly_city',
	'bsswoo_debug',
	'wcbse_enabled',
	'wcbse_shipping_enabled',
	'wcbse_hide_city',
	'wcbse_readonly_city',
	'wcbse_debug',
	'swbe_enabled',
	'swbe_shipping_enabled',
	'swbe_hide_city',
	'swbe_readonly_city',
	'swbe_debug',
);

foreach ( $bsswoo_options as $bsswoo_option ) {
	delete_option( $bsswoo_option );
}

$bsswoo_user_meta_keys = array(
	'billing_sector_bucuresti',
	'shipping_sector_bucuresti',
);

foreach ( $bsswoo_user_meta_keys as $bsswoo_meta_key ) {
	delete_metadata( 'user', 0, $bsswoo_meta_key, '', true );
}
