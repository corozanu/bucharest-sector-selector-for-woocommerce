<?php
/**
 * Plugin settings under WooCommerce.
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BSSWOO_Admin_Settings
 */
class BSSWOO_Admin_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'bsswoo_efactura_sector';
		$this->label = __( 'eFactura Sector', 'bucharest-sector-selector-for-woocommerce' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_settings(): array {
		return apply_filters(
			'woocommerce_get_settings_' . $this->id,
			array(
				array(
					'title' => __( 'Bucharest Sector Selector', 'bucharest-sector-selector-for-woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Configure sector selection for Bucharest addresses to ensure SPV ANAF / e-Factura compatibility.', 'bucharest-sector-selector-for-woocommerce' ),
					'id'    => 'bsswoo_settings_title',
				),
				array(
					'title'   => __( 'Enable plugin logic', 'bucharest-sector-selector-for-woocommerce' ),
					'desc'    => __( 'Enable Bucharest sector selection and city sync.', 'bucharest-sector-selector-for-woocommerce' ),
					'id'      => 'bsswoo_enabled',
					'type'    => 'checkbox',
					'default' => 'yes',
				),
				array(
					'title'   => __( 'Enable shipping address logic', 'bucharest-sector-selector-for-woocommerce' ),
					'desc'    => __( 'Apply the same logic to the shipping address when it differs from billing.', 'bucharest-sector-selector-for-woocommerce' ),
					'id'      => 'bsswoo_shipping_enabled',
					'type'    => 'checkbox',
					'default' => 'yes',
				),
				array(
					'title'   => __( 'Hide city field for Bucharest', 'bucharest-sector-selector-for-woocommerce' ),
					'desc'    => __( 'Hide the city/locality field when Bucharest is selected.', 'bucharest-sector-selector-for-woocommerce' ),
					'id'      => 'bsswoo_hide_city',
					'type'    => 'checkbox',
					'default' => 'yes',
				),
				array(
					'title'       => __( 'Make city readonly instead of hidden', 'bucharest-sector-selector-for-woocommerce' ),
					'desc'        => __( 'Only applies when the city field is not hidden. The city will be auto-filled with the selected sector.', 'bucharest-sector-selector-for-woocommerce' ),
					'id'          => 'bsswoo_readonly_city',
					'type'        => 'checkbox',
					'default'     => 'no',
					'checkboxgroup' => '',
				),
				array(
					'title'   => __( 'Debug mode', 'bucharest-sector-selector-for-woocommerce' ),
					'desc'    => __( 'Log plugin events to WooCommerce logs (source: bsswoo).', 'bucharest-sector-selector-for-woocommerce' ),
					'id'      => 'bsswoo_debug',
					'type'    => 'checkbox',
					'default' => 'no',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'bsswoo_settings_end',
				),
			)
		);
	}
}
