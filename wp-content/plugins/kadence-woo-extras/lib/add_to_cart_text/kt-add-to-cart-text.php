<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action( 'plugins_loaded', 'kt_add_to_cart_text_plugin_loaded' );

function kt_add_to_cart_text_plugin_loaded() {

	class kt_add_to_cart_text {

		public function __construct() {
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'kt_add_to_cart_text' ), 15, 2 );
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'kt_single_add_to_cart_text' ), 10, 2 );
			add_filter( 'kadence_shop_kit_swatches_add_to_cart_text', array( $this, 'kt_add_to_cart_text_simple' ), 15, 2 );
		}
		public function kt_single_add_to_cart_text( $text, $product ) {
			global $kt_woo_extras;
			if ( !empty($kt_woo_extras['single_add_to_cart_text'] ) && !$product->is_type( 'external' ) ) {
				return $kt_woo_extras['single_add_to_cart_text'];
			}

			return $text;

		}

		public function kt_add_to_cart_text( $text, $product ) {
			global $kt_woo_extras;
			// out of stock
			if ( !empty($kt_woo_extras['out_add_to_cart_text'] ) && !$product->is_in_stock() ) {

				return $kt_woo_extras['out_add_to_cart_text'];
			}

			if ( !empty($kt_woo_extras['add_to_cart_text'] ) && $product->is_type( 'simple' ) ) {
				// simple 
				return $kt_woo_extras['add_to_cart_text'];

			} elseif ( !empty($kt_woo_extras['variable_add_to_cart_text'] ) && $product->is_type( 'variable') )  {

				// variable 
				return $kt_woo_extras['variable_add_to_cart_text'];

			} elseif ( !empty($kt_woo_extras['grouped_add_to_cart_text'] ) && $product->is_type( 'grouped' ) ) {

				// grouped 
				return $kt_woo_extras['grouped_add_to_cart_text'];

			}

			return $text;
		}

		public function kt_add_to_cart_text_simple( $text, $product ) {
			global $kt_woo_extras;
			// out of stock
			if ( !empty($kt_woo_extras['out_add_to_cart_text'] ) && !$product->is_in_stock() ) {

				return $kt_woo_extras['out_add_to_cart_text'];
			}

			if ( !empty($kt_woo_extras['add_to_cart_text'] ) ) {
				// simple 
				return $kt_woo_extras['add_to_cart_text'];
			}

			return $text;
		}

	}
	$GLOBALS['kt_add_to_cart_text'] = new kt_add_to_cart_text();
}

