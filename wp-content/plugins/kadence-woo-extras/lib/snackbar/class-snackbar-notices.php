<?php
/**
 * This overrides woocommerce.
 *
 * @package Kadence Woo Extras
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class to build out snackbar notices.
 *
 * @category class
 */
class Kadence_Shop_Kit_Snackbar_Notice {

	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

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
	 * Build snackbar.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 150 );
	}
	/**
	 * Add in the snackbar scripts and styles.
	 */
	public function enqueue_scripts() {
		$add = false;
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		if ( is_cart() ) {
			if ( isset( $shopkit_settings['snackbar_cart'] ) && true === $shopkit_settings['snackbar_cart'] ) {
				$add = true;
			}
		} elseif ( is_checkout() ) {
			if ( isset( $shopkit_settings['snackbar_checkout'] ) && true === $shopkit_settings['snackbar_checkout'] ) {
				$add = true;
			}
		} else {
			$add = true;
		}
		if ( $add ) {
			wp_enqueue_style( 'kadence-snackbar-notice', KADENCE_WOO_EXTRAS_URL . 'lib/snackbar/css/kadence-snackbar-notice.css', false, KADENCE_WOO_EXTRAS_VERSION );
			if ( is_cart() || is_checkout() ) {
				wp_enqueue_script( 'kadence-snackbar-notice-cart', KADENCE_WOO_EXTRAS_URL . 'lib/snackbar/js/min/kadence-snackbar-notice-cart.min.js', array( 'jquery' ), KADENCE_WOO_EXTRAS_VERSION, true );
				$snackbar_translation_array = array(
					'close'    => __( 'Dismiss Notice', 'kadence-woo-extras' ),
				);
				wp_localize_script( 'kadence-snackbar-notice-cart', 'kadence_wsb', $snackbar_translation_array );
			} else {
				wp_enqueue_script( 'kadence-snackbar-notice', KADENCE_WOO_EXTRAS_URL . 'lib/snackbar/js/min/kadence-snackbar-notice.min.js', array(), KADENCE_WOO_EXTRAS_VERSION, true );
				$snackbar_translation_array = array(
					'close'    => __( 'Dismiss Notice', 'kadence-woo-extras' ),
				);
				wp_localize_script( 'kadence-snackbar-notice', 'kadence_wsb', $snackbar_translation_array );
			}
		}
	}
}
Kadence_Shop_Kit_Snackbar_Notice::get_instance();
