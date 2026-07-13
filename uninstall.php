<?php
/**
 * Uninstall routine.
 *
 * @package BSSWOO
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$options = array(
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

foreach ( $options as $option ) {
	delete_option( $option );
}

$user_meta_keys = array(
	'billing_sector_bucuresti',
	'shipping_sector_bucuresti',
);

foreach ( $user_meta_keys as $meta_key ) {
	delete_metadata( 'user', 0, $meta_key, '', true );
}
