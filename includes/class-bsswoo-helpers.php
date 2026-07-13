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
	 * Sector city values used for auto-sync detection.
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
