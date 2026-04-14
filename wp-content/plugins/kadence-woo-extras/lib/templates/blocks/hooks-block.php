<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/hooks` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_hooks_block( $attributes, $content, $block ) {
	$content      = '';
	$output_css   = '';
	if ( is_admin() ) {
		return '';
	}
	$wrap_classes = 'kwt-hooks-wrap kwt-hooks-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : '' );
	if ( ! empty( $attributes['hook'] ) ) {
		if ( ! empty( $attributes['hookType'] ) && 'function' === $attributes['hookType'] ) {
			if ( function_exists( $attributes['hook'] ) ) {
				ob_start();
				call_user_func( $attributes['hook'] );
				$content = ob_get_contents();
				ob_end_clean();
			}
		} else {
			ob_start();
			do_action( $attributes['hook'] );
			$content = ob_get_contents();
			ob_end_clean();
		}
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_hooks_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/hooks` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_hooks_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-hooks-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-hooks-wrap.kwt-hooks-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	return $css->css_output();
}
