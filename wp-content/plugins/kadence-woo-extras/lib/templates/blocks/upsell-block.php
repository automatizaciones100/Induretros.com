<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/upsell` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_upsell_block( $attributes, $content, $block ) {
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
	$wrap_classes = 'kwt-upsell-wrap kwt-upsell-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		if ( isset( $attributes['removeHeading'] ) && true == $attributes['removeHeading'] ) {
			add_filter( 'woocommerce_product_upsells_products_heading', '__return_false' );
		}
		add_filter(
			'woocommerce_upsell_display_args',
			function ( $args ) use ( $attributes ) {
				if ( ! empty( $attributes['columns'][0] ) ) {
					$args['columns'] = $attributes['columns'][0];
				}
				if ( ! empty( $attributes['items'] ) ) {
					$args['posts_per_page'] = $attributes['items'];
				}
				return $args;
			},
			99
		);
		$wrap_classes .= ' kwt-upsell-single';
		if ( ! empty( $attributes['columns'][0] ) ) {
			$wrap_classes .= ' kwt-upsell-columns-' . $attributes['columns'][0];
		}
		if ( ! empty( $attributes['columns'][1] ) ) {
			$wrap_classes .= ' kwt-upsell-tablet-columns-' . $attributes['columns'][1];
		}
		if ( ! empty( $attributes['columns'][2] ) ) {
			$wrap_classes .= ' kwt-upsell-mobile-columns-' . $attributes['columns'][2];
		}
		ob_start();
		woocommerce_upsell_display();
		$content = ob_get_contents();
		ob_end_clean();
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_upsell_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/upsell` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_upsell_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-upsell-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-upsell-wrap.kwt-upsell-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->set_selector( '.kwt-upsell-wrap.kwt-upsell-' . $unique_id . ' .upsells.products ul.products li.product .woocommerce-loop-product__title' );
	$css->render_typography( $attributes, 'typography' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->set_selector( '.kwt-upsell-wrap.kwt-upsell-' . $unique_id . ' .upsells.products ul.products li.product' );
	$css->render_color( $attributes, 'background', 'background' );
	// Columns.
	if ( ! empty( $attributes['columns'][0] ) ) {
		$css->set_selector( '.kwt-upsell-wrap.kwt-upsell-' . $unique_id . ' .upsells.products ul.products' );
		$css->add_property( 'display', 'grid' );
		$css->add_property( 'grid-template-columns', 'repeat( ' . $attributes['columns'][0] . ', minmax(0, 1fr) )' );
		$css->add_property( 'gap', '2.5rem' );
	}
	if ( ! empty( $attributes['columns'][1] ) ) {
		$css->set_media_state( 'tablet' );
		$css->set_selector( '.kwt-upsell-wrap.kwt-upsell-' . $unique_id . ' .upsells.products ul.products' );
		$css->add_property( 'display', 'grid' );
		$css->add_property( 'grid-template-columns', 'repeat( ' . $attributes['columns'][1] . ', minmax(0, 1fr) )' );
		$css->add_property( 'gap', '2rem' );
	}
	if ( ! empty( $attributes['columns'][2] ) ) {
		$css->set_media_state( 'mobile' );
		$css->set_selector( '.kwt-upsell-wrap.kwt-upsell-' . $unique_id . ' .upsells.products ul.products' );
		$css->add_property( 'display', 'grid' );
		$css->add_property( 'grid-template-columns', 'repeat( ' . $attributes['columns'][2] . ', minmax(0, 1fr) )' );
		$css->add_property( 'gap', '1.5rem' );
	}
	$css->set_media_state( 'desktop' );

	return $css->css_output();
}
