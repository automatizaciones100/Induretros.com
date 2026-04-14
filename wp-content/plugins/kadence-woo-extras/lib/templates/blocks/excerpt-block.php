<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/excerpt` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_excerpt_block( $attributes, $content, $block, $pseudo_block = false ) {
	global $in_quickview;
	
	static $seen_ids = array();
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	global $post;
	$post_ID      = $block->context['postId'];
	$content      = '';
	$output_css   = '';
	$wrap_classes = 'kwt-excerpt-wrap woocommerce-product-details__short-description kwt-excerpt-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-excerpt-single';
	} elseif ( 'product' === get_post_type() ) {
		// Product Loop.
		$wrap_classes .= ' kwt-excerpt-loop';
	}
	ob_start();
	do_action( 'kadence_before_short_description_block' );
	echo apply_filters( 'woocommerce_short_description', $post->post_excerpt );  // WPCS: XSS ok. 
	do_action( 'kadence_after_short_description_block' );
	$content = ob_get_contents();
	ob_end_clean();
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = '';
	if( ! $pseudo_block ) {
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	} else {
		$wrapper_attributes = 'class="' . $wrap_classes . '"';
	}
	$css = kadence_wootemplate_render_excerpt_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/excerpt` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_excerpt_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-excerpt-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-excerpt-wrap.kwt-excerpt-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->render_text_align( $attributes, 'textAlign' );
	$css->render_typography( $attributes, 'typography' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->set_selector( '.kwt-excerpt-wrap.kwt-excerpt-' . $unique_id . ' a' );
	$css->render_color( $attributes, 'colorLink', 'color' );
	$css->set_selector( '.kwt-excerpt-wrap.kwt-excerpt-' . $unique_id . ' a:hover' );
	$css->render_color( $attributes, 'colorLinkHover', 'color' );
	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}
