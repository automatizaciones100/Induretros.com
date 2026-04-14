<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/reviews` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_reviews_block( $attributes, $content, $block, $pseudo_block = false ) {
	global $in_quickview;
	
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	$post_ID      = $block->context['postId'];
	$product      = wc_get_product( $post_ID );
	if ( ! is_object( $product ) ) {
		return '';
	}
	$content       = '';
	$output_css   = '';
	$wrap_classes  = 'kwt-reviews-wrap kwt-reviews-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	$desk_layout   = ! empty( $attributes['layout'][0] ) ? $attributes['layout'][0] : 'inherit';
	$tablet_layout = ! empty( $attributes['layout'][1] ) ? $attributes['layout'][1] : $desk_layout;
	$mobile_layout = ! empty( $attributes['layout'][2] ) ? $attributes['layout'][2] : $tablet_layout;
	//this attribute used to be setting these values to "row" when it should have been setting to "rows" for tablet and mobile. Fix for users who already had set the attribute wrong.
	$tablet_layout_fix = $tablet_layout == 'row' ? 'rows' : $tablet_layout;
	$mobile_layout_fix = $mobile_layout == 'row' ? 'rows' : $mobile_layout;

	$wrap_classes .= ' kwt-reviews-desk-layout-' . $desk_layout;
	$wrap_classes .= ' kwt-reviews-tablet-layout-' . $tablet_layout_fix;
	$wrap_classes .= ' kwt-reviews-mobile-layout-' . $mobile_layout_fix;
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-reviews-single';
		ob_start();
		comments_template();
		$content = ob_get_contents();
		ob_end_clean();
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
	$css = kadence_wootemplate_render_reviews_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/reviews` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_reviews_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-reviews-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-reviews-wrap.kwt-reviews-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );

	return $css->css_output();
}
