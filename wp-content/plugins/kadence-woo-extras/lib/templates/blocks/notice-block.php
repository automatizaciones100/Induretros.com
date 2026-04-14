<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/notice` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_notice_block( $attributes, $content, $block ) {
	$content      = '';
	$output_css   = '';
	$wrap_classes = 'woocommerce woocommerce-notices-wrapper kwt-notice-wrap kwt-notice-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : '' );
	if ( function_exists( 'wc_print_notices' ) ) {
		ob_start();
		echo wc_print_notices( true );
		$content = ob_get_contents();
		ob_end_clean();
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_notice_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/notice` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_notice_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-notice-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-notice-wrap.kwt-notice-' . $unique_id . ' .woocommerce-message:not(.kwsb-snackbar-notice)' );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->render_typography( $attributes, 'typography' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->render_color( $attributes, 'background', 'background' );
	$css->render_measure( $attributes, 'borderWidth', 'border-width' );
	$css->render_measure( $attributes, 'borderRadius', 'border-radius' );
	$css->render_border_color( $attributes, 'border' );
	$css->set_selector( '.kwt-notice-wrap.kwt-notice-' . $unique_id . ' .woocommerce-message:not(.kwsb-snackbar-notice) .button' );
	$css->render_measure( $attributes, 'btnPadding', 'padding' );
	$css->render_typography( $attributes, 'btnTypography' );
	$css->render_color( $attributes, 'btnColor', 'color' );
	$css->render_color( $attributes, 'btnBackground', 'background' );
	$css->render_measure( $attributes, 'btnBorderWidth', 'border-width' );
	$css->render_measure( $attributes, 'btnBorderRadius', 'border-radius' );
	$css->render_border_color( $attributes, 'btnBorder' );
	$css->set_selector( '.kwt-notice-wrap.kwt-notice-' . $unique_id . ' .woocommerce-message:not(.kwsb-snackbar-notice) .button:hover' );
	$css->render_color( $attributes, 'btnColorHover', 'color' );
	$css->render_color( $attributes, 'btnBackgroundHover', 'background' );
	$css->render_border_color( $attributes, 'btnBorderHover' );
	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}
