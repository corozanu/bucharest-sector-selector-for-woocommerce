<?php
/**
 * Helper functions for Bucharest sector detection and options.
 *
 * @package BSSWOO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BSSWOO_Helpers
 */
class BSSWOO_Helpers {

	/**
	 * Blocks checkout additional field ID.
	 */
	public const BLOCKS_FIELD_ID = 'bucharest-sector-selector-for-woocommerce/sector';

	/**
	 * Get the Blocks checkout additional field ID.
	 *
	 * @return string
	 */
	public static function get_blocks_field_id(): string {
		/**
		 * Filter the Blocks checkout additional field ID.
		 *
		 * @param string $field_id Field ID.
		 */
		return (string) apply_filters( 'bsswoo_blocks_field_id', self::BLOCKS_FIELD_ID );
	}

	/**
	 * Get the WooCommerce Blocks CheckoutFields service if available.
	 *
	 * @return object|null
	 */
	public static function get_checkout_fields_service(): ?object {
		if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Package' ) ) {
			return null;
		}

		try {
			return \Automattic\WooCommerce\Blocks\Package::container()->get(
				\Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class
			);
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			return null;
		}
	}

	/**
	 * Whether the Additional Checkout Fields API is available.
	 *
	 * @return bool
	 */
	public static function is_blocks_api_available(): bool {
		return function_exists( 'woocommerce_register_additional_checkout_field' );
	}

	/**
	 * Get sector options formatted for the Blocks checkout API.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_blocks_select_options(): array {
		$options = array(
			array(
				'value' => '',
				'label' => __( 'Selectează sectorul', 'bucharest-sector-selector-for-woocommerce' ),
			),
		);

		foreach ( self::SECTOR_VALUES as $sector ) {
			$options[] = array(
				'value' => $sector,
				'label' => __( $sector, 'bucharest-sector-selector-for-woocommerce' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
			);
		}

		/**
		 * Filter sector options for the Blocks checkout field.
		 *
		 * @param array<int, array<string, string>> $options Select options.
		 */
		return apply_filters( 'bsswoo_blocks_select_options', $options );
	}

	/**
	 * JSON Schema conditions used to hide the Blocks sector field.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_blocks_hidden_schema(): array {
		$schema = array(
			array(
				'type'       => 'object',
				'properties' => array(
					'customer' => array(
						'properties' => array(
							'address' => array(
								'properties' => array(
									'country' => array(
										'not' => array(
											'const' => 'RO',
										),
									),
								),
							),
						),
					),
				),
			),
			array(
				'type'       => 'object',
				'properties' => array(
					'customer' => array(
						'properties' => array(
							'address' => array(
								'properties' => array(
									'state' => array(
										'not' => array(
											'const' => 'B',
										),
									),
								),
							),
						),
					),
				),
			),
		);

		/**
		 * Filter JSON Schema used to hide the Blocks sector field.
		 *
		 * @param array<int, array<string, mixed>> $schema Hidden schema rules.
		 */
		return apply_filters( 'bsswoo_blocks_hidden_schema', $schema );
	}

	/**
	 * JSON Schema conditions used to require the Blocks sector field.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_blocks_required_schema(): array {
		$schema = array(
			array(
				'type'       => 'object',
				'properties' => array(
					'customer' => array(
						'properties' => array(
							'address' => array(
								'properties' => array(
									'country' => array(
										'const' => 'RO',
									),
									'state'   => array(
										'const' => 'B',
									),
								),
							),
						),
					),
				),
			),
		);

		/**
		 * Filter JSON Schema used to require the Blocks sector field.
		 *
		 * @param array<int, array<string, mixed>> $schema Required schema rules.
		 */
		return apply_filters( 'bsswoo_blocks_required_schema', $schema );
	}

	/**
	 * Read a sector value from Blocks checkout data on an order.
	 *
	 * @param WC_Order $order   Order object.
	 * @param string   $context billing|shipping.
	 * @return string
	 */
	public static function get_blocks_sector_from_order( WC_Order $order, string $context ): string {
		$service = self::get_checkout_fields_service();

		if ( $service && method_exists( $service, 'get_field_from_object' ) ) {
			$value = $service->get_field_from_object( self::get_blocks_field_id(), $order, $context );

			if ( is_string( $value ) && '' !== $value ) {
				return self::sanitize_sector( $value );
			}
		}

		return '';
	}

	/**
	 * Get the legacy sector meta key for an address context.
	 *
	 * @param string $context billing|shipping.
	 * @return string
	 */
	public static function get_sector_meta_key( string $context ): string {
		return '_' . $context . '_sector_bucuresti';
	}

	/**
	 * Resolve the sector value for an order address context.
	 *
	 * @param WC_Order $order   Order object.
	 * @param string   $context billing|shipping.
	 * @return string
	 */
	public static function get_order_sector( WC_Order $order, string $context ): string {
		$legacy = self::sanitize_sector( (string) $order->get_meta( self::get_sector_meta_key( $context ) ) );

		if ( '' !== $legacy ) {
			return $legacy;
		}

		$blocks = self::get_blocks_sector_from_order( $order, $context );

		if ( '' !== $blocks ) {
			return $blocks;
		}

		$city = 'billing' === $context ? $order->get_billing_city() : $order->get_shipping_city();

		return self::is_sector_city( $city ) ? $city : '';
	}

	/**
	 * Apply sector data to an order address context.
	 *
	 * @param WC_Order $order   Order object.
	 * @param string   $context billing|shipping.
	 * @param string   $state   State value.
	 * @param string   $sector  Sector value.
	 * @return void
	 */
	public static function apply_sector_to_order( WC_Order $order, string $context, string $state, string $sector ): void {
		$meta_key = self::get_sector_meta_key( $context );
		$sector   = self::sanitize_sector( $sector );

		if ( ! self::is_bucharest_state( $state ) ) {
			$order->delete_meta_data( $meta_key );
			return;
		}

		if ( '' === $sector ) {
			return;
		}

		$order->update_meta_data( $meta_key, $sector );

		/**
		 * Fires before the city is set from the selected sector.
		 *
		 * @param string   $sector  Selected sector.
		 * @param string   $context Address context.
		 * @param WC_Order $order   Order object.
		 */
		do_action( 'bsswoo_before_set_city', $sector, $context, $order );

		if ( 'billing' === $context ) {
			$order->set_billing_city( $sector );
		} else {
			$order->set_shipping_city( $sector );
		}

		self::debug_log(
			'Order city set from sector.',
			array(
				'context'  => $context,
				'sector'   => $sector,
				'order_id' => $order->get_id(),
				'source'   => 'blocks',
			)
		);

		/**
		 * Fires after the city has been set from the selected sector.
		 *
		 * @param string   $sector  Selected sector.
		 * @param string   $context Address context.
		 * @param WC_Order $order   Order object.
		 */
		do_action( 'bsswoo_after_set_city', $sector, $context, $order );
	}

	/**
	 * Get validation message for a missing sector.
	 *
	 * @param string $context billing|shipping.
	 * @return string
	 */
	public static function get_missing_sector_message( string $context ): string {
		return 'billing' === $context
			? __( 'Pentru adresa de facturare din București, selectați sectorul.', 'bucharest-sector-selector-for-woocommerce' )
			: __( 'Pentru adresa de livrare din București, selectați sectorul.', 'bucharest-sector-selector-for-woocommerce' );
	}

	 *
	 * @var string[]
	 */
	public const SECTOR_VALUES = array(
		'Sector 1',
		'Sector 2',
		'Sector 3',
		'Sector 4',
		'Sector 5',
		'Sector 6',
	);

	/**
	 * Normalize a state value for comparison.
	 *
	 * @param mixed $state State value.
	 * @return string
	 */
	public static function normalize_state( mixed $state ): string {
		$state = remove_accents( (string) $state );
		$state = strtoupper( trim( $state ) );
		$state = preg_replace( '/\s+/', ' ', $state );

		return $state;
	}

	/**
	 * Determine whether a state value represents Bucharest.
	 *
	 * @param mixed $state_value State value.
	 * @return bool
	 */
	public static function is_bucharest_state( mixed $state_value ): bool {
		$normalized = self::normalize_state( $state_value );

		$bucharest_values = array(
			'B',
			'BUCURESTI',
		);

		$is_bucharest = in_array( $normalized, $bucharest_values, true );

		/**
		 * Filter whether a state value should be treated as Bucharest.
		 *
		 * @param bool   $is_bucharest Whether the state is Bucharest.
		 * @param string $state_value  Original state value.
		 */
		return (bool) apply_filters( 'bsswoo_is_bucharest_state', $is_bucharest, (string) $state_value );
	}

	/**
	 * Get sector dropdown options.
	 *
	 * @return array<string, string>
	 */
	public static function get_sector_options(): array {
		$options = array(
			''         => __( 'Selectează sectorul', 'bucharest-sector-selector-for-woocommerce' ),
			'Sector 1' => __( 'Sector 1', 'bucharest-sector-selector-for-woocommerce' ),
			'Sector 2' => __( 'Sector 2', 'bucharest-sector-selector-for-woocommerce' ),
			'Sector 3' => __( 'Sector 3', 'bucharest-sector-selector-for-woocommerce' ),
			'Sector 4' => __( 'Sector 4', 'bucharest-sector-selector-for-woocommerce' ),
			'Sector 5' => __( 'Sector 5', 'bucharest-sector-selector-for-woocommerce' ),
			'Sector 6' => __( 'Sector 6', 'bucharest-sector-selector-for-woocommerce' ),
		);

		/**
		 * Filter sector dropdown options.
		 *
		 * @param array<string, string> $options Sector options.
		 */
		return apply_filters( 'bsswoo_sector_options', $options );
	}

	/**
	 * Check if a city value is an auto-managed sector value.
	 *
	 * @param mixed $city City value.
	 * @return bool
	 */
	public static function is_sector_city( mixed $city ): bool {
		return in_array( trim( (string) $city ), self::SECTOR_VALUES, true );
	}

	/**
	 * Determine whether the city field should be hidden for a context.
	 *
	 * @param string $context billing|shipping.
	 * @return bool
	 */
	public static function should_hide_city( string $context ): bool {
		$hide = 'yes' === get_option( 'bsswoo_hide_city', 'yes' );

		/**
		 * Filter whether the city field should be hidden for Bucharest.
		 *
		 * @param bool   $hide    Whether to hide the city field.
		 * @param string $context Address context: billing or shipping.
		 */
		return (bool) apply_filters( 'bsswoo_should_hide_city', $hide, $context );
	}

	/**
	 * Determine whether the city field should be readonly for a context.
	 *
	 * @param string $context billing|shipping.
	 * @return bool
	 */
	public static function should_readonly_city( string $context ): bool {
		if ( self::should_hide_city( $context ) ) {
			return false;
		}

		return 'yes' === get_option( 'bsswoo_readonly_city', 'no' );
	}

	/**
	 * Check if plugin logic is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return 'yes' === get_option( 'bsswoo_enabled', 'yes' );
	}

	/**
	 * Check if shipping address logic is enabled.
	 *
	 * @return bool
	 */
	public static function is_shipping_enabled(): bool {
		return 'yes' === get_option( 'bsswoo_shipping_enabled', 'yes' );
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	public static function is_debug_enabled(): bool {
		return 'yes' === get_option( 'bsswoo_debug', 'no' );
	}

	/**
	 * Log a debug message when debug mode is active.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional context.
	 * @return void
	 */
	public static function debug_log( string $message, array $context = array() ): void {
		if ( ! self::is_debug_enabled() || ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger = wc_get_logger();
		$logger->debug( $message, array_merge( array( 'source' => 'bsswoo' ), $context ) );
	}

	/**
	 * Sanitize a sector value.
	 *
	 * @param mixed $sector Sector value.
	 * @return string
	 */
	public static function sanitize_sector( mixed $sector ): string {
		$sector = sanitize_text_field( (string) $sector );

		if ( '' === $sector ) {
			return '';
		}

		$options = self::get_sector_options();

		return array_key_exists( $sector, $options ) ? $sector : '';
	}

	/**
	 * Build a sector field definition.
	 *
	 * @param string $prefix Field prefix: billing or shipping.
	 * @return array<string, mixed>
	 */
	public static function get_sector_field_args( string $prefix ): array {
		return array(
			'type'              => 'select',
			'label'             => __( 'Sector București', 'bucharest-sector-selector-for-woocommerce' ),
			'required'          => false,
			'class'             => array( 'form-row-wide', 'bsswoo-sector-field', 'bsswoo-sector-field--' . $prefix ),
			'priority'          => 65,
			'options'           => self::get_sector_options(),
			'default'           => '',
			'custom_attributes' => array(
				'data-bsswoo-context' => $prefix,
			),
		);
	}
}
