<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/size_chart` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_size_chart_block( $attributes, $content, $block ) {
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
	$wrap_classes = 'kwt-size-chart-wrap kwt-size-chart-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-size-chart-single';
		ob_start();
		echo do_shortcode( '[kt_size_chart]' );
		$content = ob_get_contents();
		ob_end_clean();
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
