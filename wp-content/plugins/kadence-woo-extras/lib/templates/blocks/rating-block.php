<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/rating` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_rating_block( $attributes, $content, $block, $pseudo_block = false ) {
	global $in_quickview;
	
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
	$wrap_classes = 'rating kwt-rating-wrap kwt-rating-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-rating-single';
		ob_start();
		woocommerce_template_single_rating();
		$content = ob_get_contents();
		ob_end_clean();
		$tag_name = 'div';
	} elseif ( 'product' === get_post_type() ) {
		// Product Loop.
		$wrap_classes .= ' kwt-rating-loop';
		$tag_name      = 'span';
		$content = wc_get_rating_html( $product->get_average_rating() );
		if ( $content && isset( $attributes['isLink'] ) && $attributes['isLink'] ) {
			$link               = apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );
			$link_target        = ( isset( $attributes['linkTarget'] ) && $attributes['linkTarget'] ? true : false );
			$link_rel           = ( ! empty( $attributes['rel'] ) ? ' rel="' . esc_attr( $attributes['rel'] ) . '"' : '' );
			$content            = sprintf(
				'<a class="%1$s" href="%2$s" target="%3$s"%4$s>%5$s</a>',
				'kwt-rating-link',
				$link,
				( apply_filters( 'woocommerce_shop_loop_title_target', $link_target ) ? '_blank' : '_self' ),
				$link_rel,
				$content
			);
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
	$css = kadence_wootemplate_render_rating_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<%1$s %2$s>%3$s</%1$s>', $tag_name, $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/rating` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_rating_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-rating-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-rating-wrap.kwt-rating-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->set_selector( '.kwt-rating-wrap.kwt-rating-' . $unique_id );
	$css->render_typography( $attributes, 'typography' );
	$css->set_selector( '.kwt-rating-wrap.kwt-rating-' . $unique_id . ' .woocommerce-product-rating' );
	$css->render_flex_align( $attributes, 'textAlign' );
	$css->set_selector( '.kwt-rating-wrap.kwt-rating-' . $unique_id . ' > .star-rating' );
	$css->render_align_by_margin( $attributes, 'textAlign' );
	$css->set_selector( '.kwt-rating-wrap.kwt-rating-' . $unique_id . ' .woocommerce-product-rating .star-rating, .kwt-rating-wrap.kwt-rating-' . $unique_id . ' .star-rating' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->set_selector( '.kwt-rating-wrap.kwt-rating-' . $unique_id . ' .woocommerce-product-rating a' );
	$css->render_color( $attributes, 'colorLink', 'color' );
	$css->set_selector( '.kwt-rating-wrap.kwt-rating-' . $unique_id . ' .woocommerce-product-rating a:hover' );
	$css->render_color( $attributes, 'colorLinkHover', 'color' );

	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}
