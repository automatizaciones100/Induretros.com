<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/gallery` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_gallery_block( $attributes, $content, $block, $pseudo_block = false ) {
	global $in_quickview;

	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	$post_ID      = $block->context['postId'];
	$product      = wc_get_product( $post_ID );
	if ( ! is_object( $product ) ) {
		return '';
	}
	
	$shopkit_settings = get_option( 'kt_woo_extras' );
	if ( ! is_array( $shopkit_settings ) ) {
		$shopkit_settings = json_decode( $shopkit_settings, true );
	}
	$content      = '';
	$output_css   = '';
	$wrap_classes = 'kwt-gallery-wrap kwt-gallery-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );

	if( $in_quickview && ! class_exists( 'Kadence_Shop_Kit_Product_Gallery' ) ) {
		require_once KADENCE_WOO_EXTRAS_PATH . 'lib/gallery/class-product-gallery.php';
	}

	if ( ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) || $in_quickview ) {
		$show_sale = ( isset( $attributes['showSale'] ) && false === $attributes['showSale'] ? false : true );
		$wrap_classes .= ' kwt-gallery-single';
		if ( ( ( isset( $shopkit_settings['product_gallery'] ) && $shopkit_settings['product_gallery'] ) || $in_quickview ) && class_exists( 'Kadence_Shop_Kit_Product_Gallery' ) ) {
			$args = array();

			if ( $in_quickview && ! ( isset( $shopkit_settings['product_gallery'] ) && $shopkit_settings['product_gallery'] ) ) {
				$args['arrows'] = true;
				$args['pagination'] = false;
				$args['layout'] = 'slider';
			}

			if ( ! empty( $attributes['type'][0] ) ) {
				$args['layout'] = $attributes['type'][0];
			}
			if ( ! empty( $attributes['type'][1] ) ) {
				$args['layout_tablet'] = $attributes['type'][1];
			}
			if ( ! empty( $attributes['type'][2] ) ) {
				$args['layout_mobile'] = $attributes['type'][2];
			}
			if ( ! empty( $attributes['thumbWidth'][0] ) ) {
				$args['thumb_width'] = $attributes['thumbWidth'][0];
			}
			if ( ! empty( $attributes['thumbWidth'][1] ) ) {
				$args['thumb_width_tablet'] = $attributes['thumbWidth'][1];
			}
			if ( ! empty( $attributes['thumbWidth'][2] ) ) {
				$args['thumb_width_mobile'] = $attributes['thumbWidth'][2];
			}
			if ( ! empty( $attributes['thumbColumns'][0] ) ) {
				$args['thumb_columns'] = $attributes['thumbColumns'][0];
			}
			if ( ! empty( $attributes['thumbColumns'][1] ) ) {
				$args['thumb_columns_tablet'] = $attributes['thumbColumns'][1];
			}
			if ( ! empty( $attributes['thumbColumns'][2] ) ) {
				$args['thumb_columns_mobile'] = $attributes['thumbColumns'][2];
			}
			if ( isset( $attributes['thumbGap'][0] ) && is_numeric( $attributes['thumbGap'][0] ) ) {
				$args['thumb_gap'] = $attributes['thumbGap'][0];
			}
			if ( isset( $attributes['thumbGap'][1] ) && is_numeric( $attributes['thumbGap'][0] ) ) {
				$args['thumb_gap_tablet'] = $attributes['thumbGap'][1];
			} else if ( isset( $attributes['thumbGap'][0] ) && is_numeric( $attributes['thumbGap'][1] ) ) {
				$args['thumb_gap_tablet'] = $attributes['thumbGap'][0];
			}
			if ( isset( $attributes['thumbGap'][2] ) ) {
				$args['thumb_gap_mobile'] = $attributes['thumbGap'][2];
			} else if ( isset( $attributes['thumbGap'][1] ) && is_numeric( $attributes['thumbGap'][1] ) ) {
				$args['thumb_gap_mobile'] = $attributes['thumbGap'][1];
			} else if ( isset( $attributes['thumbGap'][0] ) && is_numeric( $attributes['thumbGap'][0] ) ) {
				$args['thumb_gap_mobile'] = $attributes['thumbGap'][0];
			}
			if ( isset( $attributes['arrows'] ) ) {
				$args['arrows'] = $attributes['arrows'];
			}
			if ( isset( $attributes['pagination'] ) ) {
				$args['pagination'] = $attributes['pagination'];
			}

			$kskpg = Kadence_Shop_Kit_Product_Gallery::get_instance();
			ob_start();
			if ( $show_sale ) {
				woocommerce_show_product_sale_flash();
			}
			$kskpg->render_gallery( $args );
			$content = ob_get_contents();
			ob_end_clean();
		} else {
			ob_start();
			if ( $show_sale ) {
				woocommerce_show_product_sale_flash();
			}
			woocommerce_show_product_images();
			$content = ob_get_contents();
			ob_end_clean();
		}
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = '';
	if( ! $pseudo_block ) {
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	} else {
		$wrapper_attributes = 'class="' . $wrap_classes . '"';
	}
	$css = kadence_wootemplate_render_gallery_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/gallery` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_gallery_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-gallery-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-gallery-wrap.kwt-gallery-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->set_selector( '.kwt-gallery-wrap.kwt-gallery-' . $unique_id . ' .onsale' );
	$css->render_measure( $attributes, 'salePadding', 'padding' );
	$css->render_measure( $attributes, 'salePosition', 'position' );
	$css->render_typography( $attributes, 'saleTypography' );
	$css->render_color( $attributes, 'saleColor', 'color' );
	$css->render_color( $attributes, 'saleBackground', 'background' );
	if ( isset( $attributes['thumbGap'][0] ) && is_numeric( $attributes['thumbGap'][0] ) ) {
		$css->set_selector( '.kwt-gallery-wrap.kwt-gallery-' . $unique_id . ' .ksk-gallery' );
		$css->add_property( '--thumb-gap', $attributes['thumbGap'][0] . 'px' );
	}
	if ( isset( $attributes['thumbGap'][1] ) && is_numeric( $attributes['thumbGap'][1] ) ) {
		$css->set_media_state( 'tablet' );
		$css->set_selector( '.kwt-gallery-wrap.kwt-gallery-' . $unique_id . ' .ksk-gallery' );
		$css->add_property( '--thumb-gap', $attributes['thumbGap'][1] . 'px' );
	}
	if ( isset( $attributes['thumbGap'][2] ) && is_numeric( $attributes['thumbGap'][2] ) ) {
		$css->set_media_state( 'mobile' );
		$css->set_selector( '.kwt-gallery-wrap.kwt-gallery-' . $unique_id . ' .ksk-gallery' );
		$css->add_property( '--thumb-gap', $attributes['thumbGap'][2] . 'px' );
	}
	$css->set_media_state( 'desktop' );
	if ( isset( $attributes['thumbGridGap'][0] ) && is_numeric( $attributes['thumbGridGap'][0] ) ) {
		$css->set_selector( '.kwt-gallery-wrap.kwt-gallery-' . $unique_id . ' .ksk-gallery' );
		$css->add_property( '--thumb-grid-gap', $attributes['thumbGridGap'][0] . 'px' );
	}
	if ( isset( $attributes['thumbGridGap'][1] ) && is_numeric( $attributes['thumbGridGap'][1] ) ) {
		$css->set_media_state( 'tablet' );
		$css->set_selector( '.kwt-gallery-wrap.kwt-gallery-' . $unique_id . ' .ksk-gallery' );
		$css->add_property( '--thumb-grid-gap', $attributes['thumbGridGap'][1] . 'px' );
	}
	if ( isset( $attributes['thumbGridGap'][2] ) && is_numeric( $attributes['thumbGridGap'][2] ) ) {
		$css->set_media_state( 'mobile' );
		$css->set_selector( '.kwt-gallery-wrap.kwt-gallery-' . $unique_id . ' .ksk-gallery' );
		$css->add_property( '--thumb-grid-gap', $attributes['thumbGridGap'][2] . 'px' );
	}
	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}