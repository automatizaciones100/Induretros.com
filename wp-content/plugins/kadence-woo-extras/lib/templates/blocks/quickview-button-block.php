<?php
/**
 * Class Kadence_Woo_Extras_Quickview_Button_Blocks
 *
 * @package Kadence Woo Extras
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the `kadence-wootemplate-blocks/quickview-button` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_quickview_button_block( $attributes, $content, $block, $pseudo_block = false ) {
	global $in_quickview, $product;

	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	$content    = '';
	$output_css = '';
	$post_ID    = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
	$post_id_to_use = isset( $attributes['productId'] ) ? $attributes['productId'] : $post_ID;

	$the_product = $post_id_to_use ? wc_get_product( $post_id_to_use ) : $product;
	$wrap_classes = 'kwt-quickview-button-' . ( isset( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_id_to_use );

	$content = Kadence_Shop_Kit_Quickview::get_instance()->get_the_quickview_button_html( $the_product, true );

	if ( ! $content ) {
		return '';
	}

	$wrapper_attributes = '';
	if( ! $pseudo_block ) {
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	} else {
		$wrapper_attributes = 'class="' . $wrap_classes . '"';
	}

	$css = kadence_wootemplate_render_quickview_button_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}

	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/quickview-button` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_quickview_button_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-quickview-button-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}

	return $css->css_output();
}

/**
 * Changes the max/min quantity to 1 to trick the quantity field to be read only
 *
 * @param array $args quantity attributes.
 */
function kadence_wootemplate_quickview_button_hide_quantity( $args ) {
	$args['max_value'] = 1;
	$args['min_value'] = 1;
	return $args;
}
