<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/description` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_description_block( $attributes, $content, $block ) {
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	global $post;
	$post_ID      = $block->context['postId'];
	$content      = '';
	$output_css   = '';
	$wrap_classes = 'kwt-description-wrap kwt-description-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-description-single';
		ob_start();
		the_content();
		$content = ob_get_contents();
		ob_end_clean();
	} elseif ( 'product' === get_post_type() ) {
		// Product Loop.
		$wrap_classes .= ' kwt-description-loop';
		ob_start();
		do_action( 'kadence_before_description_block' );
		echo apply_filters( 'the_content', $post->post_content );
		do_action( 'kadence_after_description_block' );
		$content = ob_get_contents();
		ob_end_clean();
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_description_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/description` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_description_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-description-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-description-wrap.kwt-description-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->render_text_align( $attributes, 'textAlign' );
	$css->render_typography( $attributes, 'typography' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->set_selector( '.kwt-description-wrap.kwt-description-' . $unique_id . ' a' );
	$css->render_color( $attributes, 'colorLink', 'color' );
	$css->set_selector( '.kwt-description-wrap.kwt-description-' . $unique_id . ' a:hover' );
	$css->render_color( $attributes, 'colorLinkHover', 'color' );
	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}
