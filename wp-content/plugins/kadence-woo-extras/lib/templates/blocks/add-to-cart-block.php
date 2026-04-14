<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/add-to-cart` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_add_to_cart_block( $attributes, $content, $block, $pseudo_block = false ) {
	global $in_quickview;

	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	$content    = '';
	$output_css = '';
	$post_ID    = $block->context['postId'];
	$hide_quantity = isset( $attributes['showQuantity'] ) && false == $attributes['showQuantity'];
	$wrap_classes = 'kwt-add-to-cart-wrap kwt-add-to-cart-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	$wrap_classes .= $hide_quantity ? ' kwt-add-to-cart-hide-quantity' : '';
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-add-to-cart-single';
		if ( $hide_quantity ) {
			add_filter( 'woocommerce_quantity_input_args', 'kadence_wootemplate_add_to_cart_hide_quantity' );
			if ( isset( $attributes['fullBtn'] ) && true == $attributes['fullBtn'] ) {
				$wrap_classes .= ' kwt-add-to-cart-full-btn';
			}
		}
		ob_start();
		woocommerce_template_single_add_to_cart();
		$content = ob_get_contents();
		ob_end_clean();
		if ( $hide_quantity ) {
			remove_filter( 'woocommerce_quantity_input_args', 'kadence_wootemplate_add_to_cart_hide_quantity' );
		}
	} elseif ( 'product' === get_post_type() ) {
		// Product Loop.
		$wrap_classes .= ' kwt-add-to-cart-loop';
		if ( isset( $attributes['fullBtn'] ) && true == $attributes['fullBtn'] ) {
			$wrap_classes .= ' kwt-add-to-cart-full-btn-loop';
		}
		ob_start();
		woocommerce_template_loop_add_to_cart();
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
	$css = kadence_wootemplate_render_add_to_cart_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}

	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/add-to-cart` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_add_to_cart_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-add-to-cart-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-add-to-cart-wrap.kwt-add-to-cart-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->set_selector( '.kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' .cart:not(.variations_form), .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' .cart.variations_form .single_variation_wrap' );
	$css->render_flex_align( $attributes, 'textAlign' );
	$css->set_selector( '.kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' .cart.variations_form .single_variation_wrap' );
	$css->add_property( 'flex-direction', 'column' );
	$css->set_selector( '.kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' .cart.variations_form .woocommerce-variation-add-to-cart' );
	$css->render_flex_align( $attributes, 'textAlign' );
	$css->set_selector( '.kwt-add-to-cart-loop.kwt-add-to-cart-' . $unique_id );
	$css->render_text_align( $attributes, 'textAlign' );
	// Loop.
	$css->set_selector( '.woocommerce ul.products li.product .kwt-add-to-cart-loop.kwt-add-to-cart-' . $unique_id . ' .button' );
	$css->add_property( 'display', 'inline-block' );
	$css->add_property( 'border-style', 'solid' );
	$css->render_typography( $attributes, 'btnTypography' );
	$css->render_color( $attributes, 'btnColor', 'color' );
	$css->render_color( $attributes, 'btnBackground', 'background' );
	$css->render_color( $attributes, 'btnBorderColor', 'border-color' );
	$css->render_measure( $attributes, 'btnPadding', 'padding' );
	$css->render_measure( $attributes, 'btnBorder', 'border-width' );
	$css->render_measure( $attributes, 'btnBorderRadius', 'border-radius' );
	$css->set_selector( '.woocommerce ul.products li.product .kwt-add-to-cart-loop.kwt-add-to-cart-' . $unique_id . ' .button:hover' );
	$css->render_color( $attributes, 'btnColorHover', 'color' );
	$css->render_color( $attributes, 'btnBackgroundHover', 'background' );
	$css->render_color( $attributes, 'btnBorderColorHover', 'border-color' );
	if ( isset( $attributes['fullBtn'] ) && true == $attributes['fullBtn'] ) {
		$css->set_selector( '.woocommerce ul.products li.product .kwt-add-to-cart-loop.kwt-add-to-cart-full-btn-loop.kwt-add-to-cart-' . $unique_id . ' .button' );
		$css->add_property( 'display', 'block' );
		$css->add_property( 'width', '100%' );
	}
	// Single.
	$css->set_selector( '.woocommerce div.product .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' .cart .button.single_add_to_cart_button, .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' .cart .button.single_add_to_cart_button' );
	$css->render_typography( $attributes, 'btnTypography' );
	$css->set_selector( '.kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' .cart .button.single_add_to_cart_button' );
	$css->render_color( $attributes, 'btnColor', 'color' );
	$css->render_color( $attributes, 'btnBackground', 'background' );
	$css->render_color( $attributes, 'btnBorderColor', 'border-color' );
	$css->render_measure( $attributes, 'btnPadding', 'padding' );
	$css->render_measure( $attributes, 'btnBorder', 'border-width' );
	$css->render_measure( $attributes, 'btnBorderRadius', 'border-radius' );
	$css->set_selector( '.kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' .cart .button.single_add_to_cart_button:hover' );
	$css->render_color( $attributes, 'btnColorHover', 'color' );
	$css->render_color( $attributes, 'btnBackgroundHover', 'background' );
	$css->render_color( $attributes, 'btnBorderColorHover', 'border-color' );

	$css->set_selector( '.woocommerce div.product .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity.spinners-added .qty, .woocommerce div.product .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity .qty' );
	$css->render_typography( $attributes, 'qtyTypography' );
	$css->set_selector( '.woocommerce div.product .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity.spinners-added, .woocommerce div.product .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity:not(.spinners-added) .qty' );
	$css->render_color( $attributes, 'qtyBackground', 'background' );
	$css->render_color( $attributes, 'qtyBorderColor', 'border-color' );
	$css->render_measure( $attributes, 'qtyBorder', 'border-width' );
	$css->render_measure( $attributes, 'qtyBorderRadius', 'border-radius' );
	$css->set_selector( '.kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity.spinners-added .qty, .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity .qty' );
	$css->render_measure( $attributes, 'qtyPadding', 'padding' );
	$css->render_color( $attributes, 'qtyColor', 'color' );
	$css->set_selector( '.kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity.spinners-added:focus-within, .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity:not(.spinners-added) .qty:focus' );
	$css->render_color( $attributes, 'qtyBackgroundHover', 'background' );
	$css->render_color( $attributes, 'qtyBorderColorHover', 'border-color' );
	$css->set_selector( '.kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity.spinners-added:focus-within .qty, .kwt-add-to-cart-single.kwt-add-to-cart-' . $unique_id . ' form.cart div.quantity:not(.spinners-added) .qty:focus' );
	$css->render_color( $attributes, 'qtyColorHover', 'color' );

	$css->set_selector( '.kwt-add-to-cart-wrap.kwt-add-to-cart-' . $unique_id . ':not(added-for-specificity):not(also-add-for-specificity) a:hover' );
	$css->render_color( $attributes, 'colorHover' );
	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}

/**
 * Changes the max/min quantity to 1 to trick the quantity field to be read only
 *
 * @param array $args quantity attributes.
 */
function kadence_wootemplate_add_to_cart_hide_quantity( $args ) {
	$args['max_value'] = 1;
	$args['min_value'] = 1;
	return $args;
}
