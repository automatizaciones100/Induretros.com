<?php
/**
 * Kadence_Woo Extras Options Class
 *
 * @package Kadence Woo Extras
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Kadence_Woo_Extras_Settings class
 */
class KWE_Options {
	const OPT_NAME = 'kt_woo_extras';
	/**
	 * Instance control
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Holds options values
	 *
	 * @var values of the settings.
	 */
	protected static $options = null;

	/**
	 * Holds default option values
	 *
	 * @var default values of the plugin.
	 */
	protected static $default_options = null;

	/**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Action on init.
	 */
	public function __construct() {
		//add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		add_action( 'after_setup_theme', array( $this, 'get_settings_loaded' ), 30 );
	}
	/**
	 * On plugins loaded.
	 */
	public function get_settings_loaded() {
		$shopkit_settings = $this->get_stored_values();
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		$defaults = $this->get_default_values();
		$shopkit_settings = wp_parse_args( $shopkit_settings, $defaults );
		$GLOBALS[ self::OPT_NAME ] = $shopkit_settings;
	}
	/**
	 * Get options from database
	 */
	public static function get_default_values() {
		if ( ! class_exists( 'Kadence_Settings_Engine' ) ) {
			return array();
		}
		$sections = Kadence_Settings_Engine::construct_sections( self::OPT_NAME );
		return Kadence_Settings_Engine::default_values( self::OPT_NAME, $sections );
	}
	/**
	 * On plugins loaded.
	 */
	public function on_plugins_loaded() {
		$shopkit_settings = $this->get_stored_values();
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		$GLOBALS[ self::OPT_NAME ] = $shopkit_settings;
	}
	/**
	 * Set default theme option values
	 *
	 * @return default values of the theme.
	 */
	public static function defaults() {
		// Don't store defaults until after init.
		if ( is_null( self::$default_options ) ) {
			self::$default_options = apply_filters(
				'kadence_woo_extras_defaults',
				array(
					'content_width'   => array(
						'size' => 1290,
						'unit' => 'px',
					),
				)
			);
		}
		return self::$default_options;
	}
	/**
	 * Get options.
	 */
	public function get( $key, $default = '' ) {
		$stored_value = self::get_stored_value( $key, $default );
		// Allow developers to override.
		return apply_filters( 'kadence_woo_extras_option_value', $stored_value, $key );
	}
	/**
	 * Get setting of option array.
	 *
	 * @param string $key option key.
	 * @param string $first_key option array first key.
	 * @param string $second_key option array second key.
	 * @param string $third_key option array third key.
	 */
	public function get_sub( $key, $first_key = '', $second_key = '', $third_key = '' ) {
		$value = $this->get( $key );
		if ( ! empty( $first_key ) ) {
			if ( isset( $value[ $first_key ] ) && ( ! empty( $value[ $first_key ] ) || 0 === $value[ $first_key ] ) ) {
				$value = $value[ $first_key ];
			} else {
				$value = null;
			}
			if ( ! empty( $second_key ) ) {
				if ( isset( $value[ $second_key ] ) && ( ! empty( $value[ $second_key ] ) || 0 === $value[ $second_key ] ) ) {
					$value = $value[ $second_key ];
				} else {
					$value = null;
				}
				if ( ! empty( $third_key ) ) {
					if ( isset( $value[ $third_key ] ) &&( ! empty( $value[ $third_key ] ) || 0 === $value[ $third_key ] ) ) {
						$value = $value[ $third_key ];
					} else {
						$value = null;
					}
				}
			}
		}

		return $value;
	}
	/**
	 * Get options from database
	 */
	public static function get_stored_value( $key, $default = '' ) {
		// Get all stored values.
		$stored = self::get_stored_values();
		// Check if value exists in stored values array.
		if ( ! empty( $stored ) && ( ( isset( $stored[ $key ] ) && '0' == $stored[ $key ] ) || ! empty( $stored[ $key ] ) ) ) {
			return $stored[$key];
		}
		// Inline default.
		if ( ! empty( $default ) ) {
			return $default;
		}
		// Fallback to defaults array.
		$defaults = self::defaults();
		if ( isset( $defaults[ $key ] ) && '' !== $defaults[ $key ] ) {
			return $defaults[ $key ];
		}
		// Stored value not found.
		return null;
	}
	/**
	 * Get options from database
	 */
	public static function get_stored_values() {
		if ( is_null( self::$options ) ) {
			// Get all stored values.
			$stored = ( apply_filters( 'kadence_shopkit_network', false ) ? get_site_option( self::OPT_NAME, array() ) : get_option( self::OPT_NAME, array() ) );
			// Check if value exists in stored values array.
			if ( ! empty( $stored ) && ! is_array( $stored ) ) {
				$stored = json_decode( $stored, true );
			}
			self::$options = $stored;
		}
		return self::$options;
	}
}
KWE_Options::get_instance();
