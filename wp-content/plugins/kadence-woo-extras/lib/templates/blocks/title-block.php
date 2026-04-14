<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/title` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_title_block( $attributes, $content, $block, $pseudo_block = false ) {
	global $in_quickview;
	
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	$output_css = '';
	$post_ID    = $block->context['postId'];
	$wrap_classes = 'kwt-title-wrap kwt-title-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	$item_classes = '';
	$has_link = ( isset( $attributes['isLink'] ) && false === $attributes['isLink'] ? false : true );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-title-single';
		$item_classes .= 'product_title entry-title';
		$tag_name     = 'h1';
		ob_start();
		woocommerce_template_single_title();
		$content = ob_get_contents();
		ob_end_clean();
	} elseif ( 'product' === get_post_type() ) {
		// Product Loop.
		$wrap_classes .= ' kwt-title-loop';
		$item_classes .= apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title entry-title' );
		$tag_name     = 'h2';
		if ( $has_link ) {
			$title       = get_the_title( $post_ID );
			$link_target = ( isset( $attributes['linkTarget'] ) && true == $attributes['linkTarget'] ? true : false );
			$link_rel    = ( ! empty( $attributes['rel'] ) ? ' rel="' . esc_attr( $attributes['rel'] ) . '"' : '' );
			$title   = sprintf( '<a href="%1$s" target="%2$s"%3$s>%4$s</a>', apply_filters( 'woocommerce_shop_loop_title_link', get_the_permalink( $post_ID ) ), ( apply_filters( 'woocommerce_shop_loop_title_link_target', $link_target ) ? '_blank' : '_self' ), $link_rel, $title );
			$temp_content = sprintf(
				'<%1$s class="%2$s">%3$s</%1$s>',
				$tag_name,
				$item_classes,
				$title
			);
			ob_start();
			do_action( 'kadence_woocommerce_template_loop_product_title_before', $post_ID );
			echo $temp_content; // WPCS: XSS ok.
			do_action( 'kadence_woocommerce_template_loop_product_title_after', $post_ID );
			$content = ob_get_contents();
			ob_end_clean();
		} else {
			ob_start();
			do_action( 'kadence_woocommerce_template_loop_product_title_before', $post_ID );
			woocommerce_template_loop_product_title();
			do_action( 'kadence_woocommerce_template_loop_product_title_after', $post_ID );
			$content = ob_get_contents();
			ob_end_clean();
		}
	}
	if ( ! $content ) {
		return '';
	}
	if ( ! empty( $attributes['tagName'] ) ) {
		$title    = get_the_title( $post_ID );
		$tag_name = $attributes['tagName'];
		$content = sprintf(
			'<%1$s class="%2$s">%3$s</%1$s>',
			$tag_name,
			$item_classes,
			$title
		);
	}
	$wrapper_attributes = '';
	if( ! $pseudo_block ) {
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	} else {
		$wrapper_attributes = 'class="' . $wrap_classes . '"';
	}
	$css = kadence_wootemplate_render_title_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/title` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_title_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-title-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.woocommerce .kwt-title-wrap.kwt-title-' . $unique_id . ':not(added-for-specificity):not(also-add-for-specificity) .entry-title, .woocommerce .kwt-title-wrap.kwt-title-' . $unique_id . ':not(added-for-specificity):not(also-add-for-specificity) .woocommerce-loop-product__title' );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->render_typography( $attributes, 'typography' );
	$css->render_text_align( $attributes, 'textAlign' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->set_selector( '.kwt-title-wrap.kwt-title-' . $unique_id . ':not(added-for-specificity):not(also-add-for-specificity) a:hover' );
	$css->render_color( $attributes, 'colorHover', 'color' );

	$css->render_media_queries();
	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}
