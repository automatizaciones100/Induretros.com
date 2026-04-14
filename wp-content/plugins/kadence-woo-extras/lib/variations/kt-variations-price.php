<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action( 'plugins_loaded', 'kt_variation_price_plugin_loaded' );

function kt_variation_price_plugin_loaded() {

	class kt_variation_price {

		public function __construct() {
			$shopkit_settings = get_option( 'kt_woo_extras' );
			if ( ! is_array( $shopkit_settings ) ) {
				$shopkit_settings = json_decode( $shopkit_settings, true );
			}
			if ( isset( $shopkit_settings['variation_price'] ) && $shopkit_settings['variation_price'] == 'lowprice' ) {
				add_filter('woocommerce_variable_price_html', array($this, 'kt_custom_variation_low_price'), 50, 2);
				add_filter( 'woocommerce_variable_sale_price_html',  array($this, 'kt_sale_custom_variation_low_price'), 50, 2 );
			} elseif(isset($shopkit_settings['variation_price']) && $shopkit_settings['variation_price'] == 'highprice') {
				add_filter('woocommerce_variable_price_html', array($this, 'kt_custom_variation_high_price'), 50, 2);
				add_filter( 'woocommerce_variable_sale_price_html',  array($this, 'kt_sale_custom_variation_high_price'), 50, 2 );
			}
		}

		public function kt_custom_variation_low_price( $price, $product ) {
			global $kt_woo_extras;
			if ( $product->get_variation_price( 'min', true ) !== $product->get_variation_price( 'max', true ) ) {
				if ( isset( $kt_woo_extras['before_variation_price'] ) && ! empty( $kt_woo_extras['before_variation_price'] ) ) {
					$before_text = $kt_woo_extras['before_variation_price'];
				} else {
					$before_text =  '';
				}
				if ( isset( $kt_woo_extras['after_variation_price'] ) && ! empty( $kt_woo_extras['after_variation_price'] ) ) {
					$after_text = $kt_woo_extras['after_variation_price'];
				} else {
					$after_text =  '';
				}
				$price = '<span class="kt-before-price-variation">'.$before_text.'</span> '.wc_price($product->get_variation_price( 'min', true ) ).' <span class="kt-after-price-variation">'.$after_text.'</span>';
			}

			return $price;
		}
		public function kt_sale_custom_variation_low_price( $price, $product ) {
			global $kt_woo_extras;
			if ( $product->get_variation_price( 'min', true ) !== $product->get_variation_price( 'max', true ) ) {
				if ( isset( $kt_woo_extras['before_variation_price'] ) && ! empty( $kt_woo_extras['before_variation_price'] ) ) { 
					$before_text = $kt_woo_extras['before_variation_price'];
				} else {
					$before_text =  '';
				}
				if ( isset( $kt_woo_extras['after_variation_price'] ) && ! empty( $kt_woo_extras['after_variation_price'] ) ) { 
					$after_text = $kt_woo_extras['after_variation_price'];
				} else {
					$after_text =  '';
				}
				$prices = $product->get_variation_prices( true );

				if ( $product->is_on_sale() ) {
					$min_regular_price = current( $prices['regular_price'] );
					$price = '<span class="kt-before-price-variation">'.$before_text.'</span> <del><span class="amount">'.wc_price( $min_regular_price ).'</span></del> <ins><span class="amount">'.wc_price( $product->get_price() ).'</span></ins> <span class="kt-after-price-variation">'.$after_text.'</span>';
				} else {
					$price = '<span class="kt-before-price-variation">'.$before_text.'</span> '. wc_price($product->get_variation_price( 'min', true ) ).' <span class="kt-after-price-variation">'.$after_text.'</span>';
				}
			}

			return $price;
		}
		public function kt_custom_variation_high_price( $price, $product ) {
			global $kt_woo_extras;
			if ( $product->get_variation_price( 'min', true ) !== $product->get_variation_price( 'max', true ) ) {
				if(isset($kt_woo_extras['before_variation_price']) && !empty($kt_woo_extras['before_variation_price'] ) ) { 
					$before_text = $kt_woo_extras['before_variation_price'];
				} else {
					$before_text =  '';
				}
				if(isset($kt_woo_extras['after_variation_price']) && !empty($kt_woo_extras['after_variation_price'] ) ) { 
					$after_text = $kt_woo_extras['after_variation_price'];
				} else {
					$after_text =  '';
				}
					$price = '<span class="kt-before-price-variation">'.$before_text.'</span> '.wc_price($product->get_variation_price( 'max', true )).' <span class="kt-after-price-variation">'.$after_text.'</span>';
			}

			return $price;
		}
		public function kt_sale_custom_variation_high_price( $price, $product ) {
			global $kt_woo_extras;
			if ( $product->get_variation_price( 'min', true ) !== $product->get_variation_price( 'max', true ) ) {
				if(isset($kt_woo_extras['before_variation_price']) && !empty($kt_woo_extras['before_variation_price'] ) ) { 
					$before_text = $kt_woo_extras['before_variation_price'];
				} else {
					$before_text =  '';
				}
				if(isset($kt_woo_extras['after_variation_price']) && !empty($kt_woo_extras['after_variation_price'] ) ) { 
					$after_text = $kt_woo_extras['after_variation_price'];
				} else {
					$after_text =  '';
				}
					$prices = $product->get_variation_prices( true );

					if ( $product->is_on_sale() ) {
						$max_regular_price = end( $prices['regular_price'] );
						$price = '<span class="kt-before-price-variation">'.$before_text.'</span> <del><span class="amount">'.wc_price( $max_regular_price ).'</span></del> <ins><span class="amount">'.wc_price($product->get_variation_price( 'max', true )).'</span></ins> <span class="kt-after-price-variation">'.$after_text.'</span>';
					} else {
						$price = '<span class="kt-before-price-variation">'.$before_text.'</span> '.wc_price($product->get_variation_price( 'max', true )).' <span class="kt-after-price-variation">'.$after_text.'</span>';
					}
			}
			return $price;
		}

	}
	$GLOBALS['kt_variation_price'] = new kt_variation_price();
}


