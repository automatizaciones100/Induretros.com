<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/meta` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_meta_block( $attributes, $content, $block ) {
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
	$wrap_classes = 'kwt-meta-wrap kwt-meta-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-meta-single';
		ob_start();
		do_action( 'kadence_before_product_meta_block' );
		woocommerce_template_single_meta();
		do_action( 'kadence_after_product_meta_block' );
		$content = ob_get_contents();
		ob_end_clean();
	} elseif ( 'product' === get_post_type() ) {
		// Product Loop.
		$wrap_classes .= ' kwt-meta-loop product_meta';
		ob_start();
		if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>
			<span class="sku_wrapper"><?php esc_html_e( 'SKU:', 'kadence-woo-extras' ); ?> <span class="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'kadence-woo-extras' ); ?></span></span>
		<?php endif; ?>
		<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'kadence-woo-extras' ) . ' ', '</span>' ); ?>
		<?php echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'kadence-woo-extras' ) . ' ', '</span>' ); ?>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_meta_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/meta` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_meta_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-meta-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-meta-wrap.kwt-meta-' . $unique_id . ':not(.add-specificity):not(.more-specificity) span' );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->render_typography( $attributes, 'typography' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->render_text_align( $attributes, 'textAlign' );
	$css->set_selector( '.kwt-meta-wrap.kwt-meta-' . $unique_id . ':not(.add-specificity):not(.more-specificity) a' );
	$css->render_color( $attributes, 'colorLink', 'color' );
	$css->render_typography( $attributes, 'delTypography' );
	$css->set_selector( '.kwt-meta-wrap.kwt-meta-' . $unique_id . ':not(.add-specificity):not(.more-specificity) a:hover' );
	$css->render_color( $attributes, 'colorLinkHover', 'color' );

	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}
