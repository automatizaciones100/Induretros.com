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
function kadence_wootemplate_render_tabs_block( $attributes, $content, $block ) {
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
	$wrap_classes = 'kwt-tabs-wrap kwt-tabs-' . ( ! empty( $attributes['uniqueID'] ) ? $attributes['uniqueID'] : $post_ID );
	$desk_style = ! empty( $attributes['style'][0] ) ? $attributes['style'][0] : 'inherit';
	$tablet_style = ! empty( $attributes['style'][1] ) ? $attributes['style'][1] : $desk_style;
	$mobile_style = ! empty( $attributes['style'][2] ) ? $attributes['style'][2] : $tablet_style;
	if ( 'accordion' === $desk_style || 'accordion' === $tablet_style || 'accordion' === $mobile_style ) {
		wp_enqueue_script( 'kadence-wc-accordion-tab', KADENCE_WOO_EXTRAS_URL . 'lib/templates/assets/js/wc-accordion-tabs.js', array( 'jquery' ), KADENCE_WOO_EXTRAS_VERSION, true );
		$wrap_classes .= ' kwt-tabs-style-accordion';
		if ( ! empty( $attributes['startClosed'] ) && $attributes['startClosed'] ) {
			$wrap_classes .= ' kwt-tabs-accordion-start-closed';
		}
	}
	$wrap_classes .= ' kwt-tabs-desk-style-' . $desk_style;
	$wrap_classes .= ' kwt-tabs-tablet-style-' . $tablet_style;
	$wrap_classes .= ' kwt-tabs-mobile-style-' . $mobile_style;

	if ( is_singular( 'product' ) && is_main_query() && get_queried_object_id() === $post_ID ) {
		global $kadence_product_tabs_block;
		$kadence_product_tabs_block = $attributes;
		add_filter( 'woocommerce_product_tabs', 'kadence_wootemplate_filter_active_tabs', 80 );
		if ( isset( $attributes['removeHeadings'] ) && true == $attributes['removeHeadings'] ) {
			add_filter( 'woocommerce_product_description_heading', '__return_false' );
			add_filter( 'woocommerce_product_additional_information_heading', '__return_false' );
		}
		$wrap_classes .= ' kwt-tabs-single';
		ob_start();
		woocommerce_output_product_data_tabs();
		$content = ob_get_contents();
		ob_end_clean();
		remove_filter( 'woocommerce_product_tabs', 'kadence_wootemplate_filter_active_tabs', 80 );
	}
	if ( ! $content ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrap_classes ) );
	$css = kadence_wootemplate_render_tabs_output_css( $attributes );
	if ( ! empty( $css ) ) {
		$output_css = '<style>' . $css . '</style>';
	}
	return $output_css . sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
}
/**
 * Filter active tabs.
 *
 * @param array $tabs Tabs.
 */
function kadence_wootemplate_filter_active_tabs( $tabs ) {
	global $kadence_product_tabs_block;
	$attributes = $kadence_product_tabs_block;
	if ( isset( $attributes['disabledTabs'] ) && is_array( $attributes['disabledTabs'] ) ) {
		foreach ( $attributes['disabledTabs'] as $tab_key => $tab_settings ) {
			if ( isset( $tabs[ $tab_key ] ) ) {
				if ( isset( $tab_settings['disabled'] ) && true === $tab_settings['disabled'] ) {
					unset( $tabs[ $tab_key ] );
				} else if ( isset( $tab_settings['priority'] ) && ! empty( $tab_settings['priority'] ) ) {
					$tabs[ $tab_key ]['priority'] = $tab_settings['priority'];
				}
			}
		}
	}
	return $tabs;
}
/**
 * Renders the `kadence-wootemplate-blocks/tabs` block css.
 *
 * @param array  $attributes Block attributes.
 * @param string $unique_id  Block Unique Id.
 */
function kadence_wootemplate_render_tabs_output_css( $attributes ) {
	if ( ! class_exists( 'Kadence_Woo_CSS' ) ) {
		return '';
	}
	if ( ! isset( $attributes['uniqueID'] ) ) {
		return '';
	}
	$unique_id = $attributes['uniqueID'];
	$style_id  = 'kwt-tabs-' . esc_attr( $unique_id );
	$css = Kadence_Woo_CSS::get_instance();
	if ( $css->has_styles( $style_id ) ) {
		return '';
	}
	$css->set_style_id( $style_id );
	$css->set_selector( '.kwt-tabs-wrap.kwt-tabs-' . $unique_id );
	$css->render_measure( $attributes, 'padding', 'padding' );
	$css->render_measure( $attributes, 'margin', 'margin' );
	$css->set_selector( '.kwt-tabs-wrap.kwt-tabs-' . $unique_id . '.kwt-tabs-style-accordion .kwt-accordion-title a, .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .woocommerce-tabs ul.tabs li a, .woocommerce div.product .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .woocommerce-tabs ul.tabs li a' );
	$css->render_typography( $attributes, 'typography' );
	$css->render_text_align( $attributes, 'textAlign' );
	$css->render_color( $attributes, 'color', 'color' );
	$css->render_color( $attributes, 'background', 'background' );
	$css->render_measure( $attributes, 'titlePadding', 'padding' );
	$css->render_measure( $attributes, 'borderWidth', 'border-width' );
	$css->render_measure( $attributes, 'borderRadius', 'border-radius' );
	$css->render_border_color( $attributes, 'border' );
	$css->set_selector( '.kwt-tabs-wrap.kwt-tabs-' . $unique_id . '.kwt-tabs-style-accordion .kwt-accordion-title, .woocommerce div.product .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .woocommerce-tabs ul.tabs li' );
	$css->render_measure( $attributes, 'titleMargin', 'margin' );
	$css->set_selector( '.kwt-tabs-wrap.kwt-tabs-' . $unique_id . '.kwt-tabs-style-accordion .kwt-accordion-title a:hover, .kwt-tabs-wrap.kwt-tabs-' . $unique_id . '.kwt-tabs-style-accordion .kwt-accordion-title.active a:hover, .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .woocommerce-tabs ul.tabs li a:hover, .woocommerce div.product .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .woocommerce-tabs ul.tabs li a:hover' );
	$css->render_color( $attributes, 'colorHover', 'color' );
	$css->render_color( $attributes, 'backgroundHover', 'background' );
	$css->render_border_color( $attributes, 'borderHover' );
	$css->set_selector( '.kwt-tabs-wrap.kwt-tabs-' . $unique_id . '.kwt-tabs-style-accordion .kwt-accordion-title.active a, .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .woocommerce-tabs ul.tabs li.active a, .woocommerce div.product .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .woocommerce-tabs ul.tabs li.active a' );
	$css->render_color( $attributes, 'colorActive', 'color' );
	$css->render_color( $attributes, 'backgroundActive', 'background' );
	$css->render_border_color( $attributes, 'borderActive' );
	$css->set_selector( '.kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .woocommerce-tabs .panel, .woocommerce div.product .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .woocommerce-tabs .panel' );
	$css->render_color( $attributes, 'contentBackground', 'background' );
	$css->render_border_color( $attributes, 'contentBorder' );
	$css->render_measure( $attributes, 'contentBorderWidth', 'border-width' );
	$css->render_measure( $attributes, 'contentBorderRadius', 'border-radius' );
	$css->render_measure( $attributes, 'contentPadding', 'padding' );
	$css->render_measure( $attributes, 'contentMargin', 'margin' );

	$css->set_selector( '.kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' table.shop_attributes td, .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' table.shop_attributes th' );
	$css->render_typography( $attributes, 'infoTypography' );
	$css->render_color( $attributes, 'infoColor', 'color' );
	$css->render_color( $attributes, 'infoBackground', 'background' );
	$css->set_selector( '.woocommerce .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' table.shop_attributes tr:nth-child(even) td, .woocommerce .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' table.shop_attributes tr:nth-child(even) th' );
	$css->render_color( $attributes, 'infoColorEven', 'color' );
	$css->render_color( $attributes, 'infoBackgroundEven', 'background' );
	$css->set_selector( '.woocommerce .kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' table.shop_attributes tr th' );
	$css->render_typography( $attributes, 'infoLabelTypography' );
	$css->render_text_align( $attributes, 'infoLabelAlign' );
	$css->render_responsive_range( $attributes, 'infoLabelWidth', 'width' );

	if ( class_exists( 'Kadence_Woo_Google_Fonts' ) ) {
		$fonts_instance = Kadence_Woo_Google_Fonts::get_instance();
		$fonts_instance->add_fonts( $css->fonts_output() );
	}

	$css->set_selector( '.kwt-tabs-wrap.kwt-tabs-' . $unique_id . ' .wc-tab.inactive');
	$css->add_property( 'display', 'none !important' );

	return $css->css_output();
}