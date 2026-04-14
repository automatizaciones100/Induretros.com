<?php
/**
 * Checks if WooCommerce is enabled
 *
 * @package Kadence WooCommerce Extras
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Checks if WooCommerce is enabled
 */
class Kadence_Woo_Extras_Plugin_Check {

	/**
	 * Checks if WooCommerce is enabled
	 *
	 * @var array of active plugins.
	 */
	private static $active_plugins;

	/**
	 * Build array of active plugis.
	 */
	public static function init() {

		self::$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
	}

	/**
	 * Checks if woocommerce is active.
	 */
	public static function check_woo() {

		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'woocommerce/woocommerce.php', self::$active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', self::$active_plugins );
	}

	/**
	 * Checks if woocommerce is active.
	 */
	public static function check_kadence_blocks() {

		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'kadence-blocks/kadence-blocks.php', self::$active_plugins ) || array_key_exists( 'kadence-blocks/kadence-blocks.php', self::$active_plugins );
	}
	/**
	 * Checks if woocommerce is active.
	 */
	public static function check_classic_editor() {

		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'classic-editor/classic-editor.php', self::$active_plugins ) || array_key_exists( 'classic-editor/classic-editor.php', self::$active_plugins );
	}

}

/**
 * Checks if WooCommerce is enabled
 */
function kadence_woo_extras_is_classic_editor_active() {
	return Kadence_Woo_Extras_Plugin_Check::check_classic_editor();
}

/**
 * Checks if WooCommerce is enabled
 */
function kadence_woo_extras_is_woo_active() {
	return Kadence_Woo_Extras_Plugin_Check::check_woo();
}

/**
 * Checks if Kadence Blocks is enabled
 */
function kadence_woo_extras_is_kadence_blocks_active() {
	return Kadence_Woo_Extras_Plugin_Check::check_kadence_blocks();
}

