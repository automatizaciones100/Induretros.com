<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Render Inline CSS helper function
 *
 * @param array  $css the css for each rendered block.
 * @param string $style_id the unique id for the rendered style.
 * @param bool   $in_content the bool for whether or not it should run in content.
 */
function kadence_wootemplate_render_inline_css( $css, $style_id, $in_content = false ) {
	if ( ! is_admin() ) {
		wp_register_style( $style_id, false );
		wp_enqueue_style( $style_id );
		wp_add_inline_style( $style_id, $css );
		if ( 1 === did_action( 'wp_head' ) && $in_content ) {
			wp_print_styles( $style_id );
		}
	}
}
