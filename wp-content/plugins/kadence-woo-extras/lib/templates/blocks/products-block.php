<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/products` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_products_block( $attributes, $content, $block ) {
	$content      = '';
	$output_css   = '';
	$wrap_classes = 'kwt-products-wrap kwt-products-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	if ( is_product_taxonomy() || is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) {
		if ( ! empty( $attributes['columns'][0] ) ) {
			$wrap_classes .= ' kwt-products-columns-' . $attributes['columns'][0];
		}
		if ( ! empty( $attributes['columns'][1] ) ) {
			$wrap_classes .= ' kwt-products-tablet-columns-' . $attributes['columns'][1];
		}
		if ( ! empty( $attributes['columns'][2] ) ) {
			$wrap_classes .= ' kwt-products-mobile-columns-' . $attributes['columns'][2];
		}
		ob_start();
		if ( ! empty( $attributes['columns'][0] ) ) {
			wc_set_loop_prop( 'columns', absint( $attributes['columns'][0] ) );
		}
		if ( have_posts() ) {

			/**
			 * woocommerce_before_shop_loop hook.
			 *
			 * @hooked wc_print_notices - 10
			 * @hooked woocommerce_result_count - 20
			 * @hooked woocommerce_catalog_ordering - 30
			 */
			do_action( 'woocommerce_before_shop_loop' );

			woocommerce_product_loop_start();
			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();
					/**
					 * Hook: woocommerce_shop_loop.
					 */
					do_action( 'woocommerce_shop_loop' );

					wc_get_template_part( 'content', 'product' );
				}
			}
			woocommerce_product_loop_end();

			/**
			 * woocommerce_after_shop_loop hook.
			 *
			 * @hooked woocommerce_pagination - 10
			 */
			do_action( 'woocommerce_after_shop_loop' );
		} else {
			/**
			 * Hook: woocommerce_no_products_found.
			 *
			 * @hooked wc_no_products_found - 10
			 */
			do_action( 'woocommerce_no_products_found' );

		}
		$content = ob_get_contents();
		ob_end_clean();
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_products_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/products` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_products_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-products-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-products-wrap.kwt-products-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	// Columns.
	if ( ! empty( $attributes['columns'][1] ) ) {
		$css->set_media_state( 'tablet' );
		$css->set_selector( '.woocommerce .kwt-products-wrap.kwt-products-' . $unique_id . ' ul.products' );
		$css->add_property( 'display', 'grid' );
		$css->add_property( 'grid-template-columns', 'repeat( ' . $attributes['columns'][1] . ', minmax(0, 1fr) )' );
		$css->add_property( 'gap', '2rem' );
	}
	if ( ! empty( $attributes['columns'][2] ) ) {
		$css->set_media_state( 'mobile' );
		$css->set_selector( '.woocommerce .kwt-products-wrap.kwt-products-' . $unique_id . ' ul.products' );
		$css->add_property( 'display', 'grid' );
		$css->add_property( 'grid-template-columns', 'repeat( ' . $attributes['columns'][2] . ', minmax(0, 1fr) )' );
		$css->add_property( 'gap', '1.5rem' );
	}
	$css->set_media_state( 'desktop' );
	return $css->css_output();
}
