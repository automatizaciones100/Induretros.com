<?php
/**
 * Class Kadence_Woo_Blocks
 *
 * @package Kadence Shop Kit
 */

/**
 * Renders the `kadence-wootemplate-blocks/image` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */
function kadence_wootemplate_render_image_block( $attributes, $content, $block ) {
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
	$wrap_classes = 'kwt-image-wrap kwt-image-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	if ( ! empty( $attributes['isFull'] ) && true == $attributes['isFull'] ) {
		$wrap_classes .= ' kwt-image-full';
	}
	$show_sale = ( isset( $attributes['showSale'] ) && false === $attributes['showSale'] ? false : true );
	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		$wrap_classes .= ' kwt-image-single';
		$image_size = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
		$post_thumbnail_id = $product->get_image_id();
		if ( $post_thumbnail_id ) {
			$html = '<div class="woocommerce-product-gallery__image">';
			$html .= wp_get_attachment_image( $post_thumbnail_id, $image_size, false );
			$html .= '</div>';
		} else {
			$html  = '<div class="woocommerce-product-gallery__image--placeholder">';
			$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
			$html .= '</div>';
		}
		ob_start();
		echo $html;
		$content = ob_get_contents();
		ob_end_clean();
	} elseif ( 'product' === get_post_type() ) {
		// Product Loop.
		$wrap_classes .= ' kwt-image-loop';
		$has_link = ( isset( $attributes['isLink'] ) && true == $attributes['isLink'] ) ? false : true;
		ob_start();
		if ( $has_link ) {
			$has_hover_image = '';
			if ( class_exists( 'Kadence\Theme' ) ) {
				if ( 'none' !== Kadence\kadence()->option( 'product_archive_image_hover_switch' ) ) {
					if ( is_a( $product, 'WC_Product' ) ) {
						$attachment_ids = $product->get_gallery_image_ids();
						if ( $attachment_ids ) {
							$has_hover_image = ' product-has-hover-image';
						}
					}
				}
			}
			$link_target        = ( isset( $attributes['linkTarget'] ) && $attributes['linkTarget'] ? true : false );
			$link_rel           = ( ! empty( $attributes['rel'] ) ? ' rel="' . esc_attr( $attributes['rel'] ) . '"' : '' );
			$image_link = apply_filters( 'kadence_woocommerce_loop_product_image_link', get_the_permalink(), $product );
			$open_link_html = sprintf(
				'<a class="%1$s" href="%2$s" target="%3$s"%4$s>',
				'woocommerce-loop-image-link woocommerce-LoopProduct-link woocommerce-loop-product__link' . esc_attr( $has_hover_image ),
				apply_filters( 'woocommerce_loop_product_link', $image_link, $product ),
				( apply_filters( 'woocommerce_shop_loop_image_link_target', $link_target ) ? '_blank' : '_self' ),
				$link_rel,
			);
			echo $open_link_html;
		}
		if ( $show_sale ) {
			woocommerce_show_product_loop_sale_flash();
		}
		woocommerce_template_loop_product_thumbnail();
		if ( class_exists( 'Kadence\Theme' ) ) {
			if ( 'none' !== Kadence\kadence()->option( 'product_archive_image_hover_switch' ) ) {
				if ( is_a( $product, 'WC_Product' ) ) {
					$attachment_ids = $product->get_gallery_image_ids();
					if ( $attachment_ids ) {
						$attachment_ids     = array_values( $attachment_ids );
						$secondary_image_id    = $attachment_ids['0'];
						$secondary_image_alt   = get_post_meta( $secondary_image_id, '_wp_attachment_image_alt', true );
						echo wp_get_attachment_image(
							$secondary_image_id,
							apply_filters( 'single_product_archive_thumbnail_size', 'woocommerce_thumbnail' ),
							false,
							array(
								'class' => 'secondary-product-image attachment-woocommerce_thumbnail attachment-shop-catalog wp-post-image wp-post-image--secondary',
								'alt'   => $secondary_image_alt,
							)
						);
					}
				}
			}
		}
		if ( $has_link ) {
			echo '</a>';
		}
		$content = ob_get_contents();
		ob_end_clean();
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_image_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Renders the `kadence-wootemplate-blocks/image` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_image_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-image-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	if ( ! empty( $attributes['isFull'] ) && true == $attributes['isFull'] ) {
		$css->set_selector( '.woocommerce ul.products li.product .kwt-image-wrap.kwt-image-full.kwt-image-' . $unique_id . ' img, .kwt-image-wrap.kwt-image-full.kwt-image-' . $unique_id . ' img' );
		$css->add_property( 'width', '100%' );
		$css->add_property( 'height', 'auto' );
	} else {
		$css->set_selector( '.woocommerce ul.products li.product .kwt-image-wrap.kwt-image-' . $unique_id . ' a.woocommerce-loop-image-link img, .kwt-image-wrap.kwt-image-' . $unique_id . ' a.woocommerce-loop-image-link img' );
		$css->add_property( 'width', 'auto' );
	}
	$css->set_selector( '.kwt-image-wrap.kwt-image-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->set_selector( '.woocommerce ul.products li.product .kwt-image-wrap.kwt-image-' . $unique_id . ' img, .kwt-image-wrap.kwt-image-' . $unique_id . ' img' );
	$css->render_align_by_margin( $attributes, 'textAlign' );
	$css->set_selector( '.woocommerce ul.products li.product .kwt-image-wrap.kwt-image-' . $unique_id . ' .onsale' );
	$css->render_measure( $attributes, 'salePadding', 'padding' );
	$css->render_measure( $attributes, 'salePosition', 'position' );
	$css->render_typography( $attributes, 'saleTypography' );
	$css->render_color( $attributes, 'saleColor', 'color' );
	$css->render_color( $attributes, 'saleBackground', 'background' );
	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}
	return $css->css_output();
}
