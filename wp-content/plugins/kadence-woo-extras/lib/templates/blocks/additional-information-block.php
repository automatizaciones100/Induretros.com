<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/additional_information` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_additional_information_block( $attributes, $content, $block ) {
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	$post_ID      = $block->context['postId'];
	$product      = wc_get_product( $post_ID );
	if ( ! is_object( $product ) ) {
		return '';
	}
	$content      = '';
	$output_css = '';
	$wrap_classes = 'kwt-additional-information-wrap kwt-additional-information-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-additional-information-single';
		ob_start();
		do_action( 'woocommerce_product_additional_information', $product );
		$content = ob_get_contents();
		ob_end_clean();
	} elseif ( 'product' === get_post_type() ) {
		$wrap_classes .= ' kwt-additional-information-loop';
		ob_start();
		do_action( 'woocommerce_product_additional_information', $product );
		$content = ob_get_contents();
		ob_end_clean();
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_additional_information_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/additional_information` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_additional_information_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-additional-information-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-additional-information-wrap.kwt-additional-information-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->set_selector( '.kwt-additional-information-wrap.kwt-additional-information-' . $unique_id . ' table.shop_attributes td, .kwt-additional-information-wrap.kwt-additional-information-' . $unique_id . ' table.shop_attributes th' );
	$css->render_typography( $attributes, 'typography' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->render_color( $attributes, 'background', 'background' );
	$css->set_selector( '.woocommerce .kwt-additional-information-wrap.kwt-additional-information-' . $unique_id . ' table.shop_attributes tr:nth-child(even) td, .woocommerce .kwt-additional-information-wrap.kwt-additional-information-' . $unique_id . ' table.shop_attributes tr:nth-child(even) th' );
	$css->render_color( $attributes, 'colorEven', 'color' );
	$css->render_color( $attributes, 'backgroundEven', 'background' );
	$css->set_selector( '.woocommerce .kwt-additional-information-wrap.kwt-additional-information-' . $unique_id . ' table.shop_attributes tr th' );
	$css->render_typography( $attributes, 'labelTypography' );
	$css->render_text_align( $attributes, 'labelAlign' );
	$css->render_responsive_range( $attributes, 'labelWidth', 'width' );
	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}