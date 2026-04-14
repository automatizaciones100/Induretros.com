<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include a template by precedance
 *
 * Looks at the theme directory first
 *
 * @param string $template_name Template file name.
 * @param array  $args Arguments to pass with the template.
 * @return void
 */
function wpb_wamc_get_template( $template_name, $args = array() ) {
	$template = locate_template(
		array(
			WPB_WAMC_THEME_DIR_PATH . $template_name,
			$template_name,
		),
		false,
		true,
		$args
	);

	if ( ! $template ) {
		$template = WPB_WAMC_TEMPLATE_PATH . $template_name;
	}

	if ( file_exists( $template ) ) {
		include $template;
	}
}

/**
 * PHP implode with key and value ( For data attr )
 *
 * @param array $atts Data attributes array.
 * @return string
 */
function wpb_wmca_data_atts( $atts ) {
	$output = array();
	if ( $atts && ! empty( $atts ) ) {
		foreach ( $atts as $key => $value ) {
			if ( isset( $value ) && '' !== $value ) {
				$output[] = 'data-' . $key . '="' . esc_attr( $value ) . '"';
			}
		}
	}
	return implode( ' ', $output );
}
