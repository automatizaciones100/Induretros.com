<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/breadcrumbs` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_breadcrumbs_block( $attributes, $content, $block ) {
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	$post_ID      = $block->context['postId'];
	$product      = wc_get_product( $post_ID );
	if ( ! is_object( $product ) ) {
		return '';
	}
	$content      = '';
	$output_css   = '';
	$wrap_classes = 'kwt-breadcrumbs-wrap kwt-breadcrumbs-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	$wrap_classes .= ' kwt-link-style-' . ( ! empty( $attributes['linkStyle'] ) ? $attributes['linkStyle'] : 'inherit' );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-breadcrumbs-single';
		if ( class_exists( 'Kadence\Theme' ) ) {
			$args = array( 'show_title' => true );
			if ( isset( $attributes['showTitle'] ) && false == $attributes['showTitle'] ) {
				$args = array( 'show_title' => false );
			}
			ob_start();
			Kadence\kadence()->print_breadcrumb( $args );
			$content = ob_get_contents();
			ob_end_clean();
		} else {
			ob_start();
			woocommerce_breadcrumb();
			$content = ob_get_contents();
			ob_end_clean();
		}
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_breadcrumbs_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/breadcrumbs` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_breadcrumbs_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-breadcrumbs-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-breadcrumbs-wrap.kwt-breadcrumbs-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->render_text_align( $attributes, 'textAlign' );
	$css->set_selector( '.kwt-breadcrumbs-wrap.kwt-breadcrumbs-' . $unique_id . ' .woocommerce-breadcrumb, .kwt-breadcrumbs-wrap.kwt-breadcrumbs-' . $unique_id . ' .kadence-breadcrumbs' );
	$css->render_typography( $attributes, 'typography' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->set_selector( '.kwt-breadcrumbs-wrap.kwt-breadcrumbs-' . $unique_id . ' .woocommerce-breadcrumb a, .kwt-breadcrumbs-wrap.kwt-breadcrumbs-' . $unique_id . ' .kadence-breadcrumbs a' );
	$css->render_color( $attributes, 'colorLink', 'color' );
	$css->set_selector( '.kwt-breadcrumbs-wrap.kwt-breadcrumbs-' . $unique_id . ' .woocommerce-breadcrumb a:hover, .kwt-breadcrumbs-wrap.kwt-breadcrumbs-' . $unique_id . ' .kadence-breadcrumbs a:hover' );
	$css->render_color( $attributes, 'colorLinkHover', 'color' );
	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}
