<?php
/**
 * Custom Functions
 *
 * @package Kadence Woo Extras.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Checks if Theme is Kadence is enabled
 */
function theme_is_kadence() {
	if ( class_exists( 'Kadence_API_Manager' ) ) {
		return true;
	}
	return false;
}

function kt_woo_get_srcset( $width, $height, $url, $id ) {
	if ( empty( $id ) || empty( $url ) ) {
		return;
	}
	$image_meta = get_post_meta( $id, '_wp_attachment_metadata', true );
	if ( empty( $image_meta['file'] ) ) {
		return;
	}
	// If possible add in our images on the fly sizes
	$ext = substr( $image_meta['file'], strrpos( $image_meta['file'], '.' ) );
	$pathflyfilename = str_replace( $ext, '-' . $width . 'x' . $height . '' . $ext, $image_meta['file'] );
	$pathretinaflyfilename = str_replace( $ext, '-' . $width . 'x' . $height . '@2x' . $ext, $image_meta['file'] );
	$flyfilename = basename( $image_meta['file'], $ext ) . '-' . $width . 'x' . $height . '' . $ext;
	$retinaflyfilename = basename( $image_meta['file'], $ext ) . '-' . $width . 'x' . $height . '@2x' . $ext;

	$upload_info = wp_upload_dir();
	$upload_dir = $upload_info['basedir'];

	$flyfile = trailingslashit( $upload_dir ) . $pathflyfilename;
	$retinafile = trailingslashit( $upload_dir ) . $pathretinaflyfilename;
	if ( empty( $image_meta['sizes'] ) ) {
		$image_meta['sizes'] = array();}
	if ( file_exists( $flyfile ) ) {
		$kt_add_imagesize = array(
			'kt_on_fly' => array(
				'file' => $flyfilename,
				'width' => $width,
				'height' => $height,
				'mime-type' => $image_meta['sizes']['thumbnail']['mime-type'],
			),
		);
		$image_meta['sizes'] = array_merge( $image_meta['sizes'], $kt_add_imagesize );
	}
	if ( file_exists( $retinafile ) ) {
		$size = getimagesize( $retinafile );
		if ( ( $size[0] === 2 * $width ) && ( $size[1] === 2 * $height ) ) {
			$kt_add_imagesize_retina = array(
				'kt_on_fly_retina' => array(
					'file' => $retinaflyfilename,
					'width' => 2 * $width,
					'height' => 2 * $height,
					'mime-type' => $image_meta['sizes']['thumbnail']['mime-type'],
				),
			);
			$image_meta['sizes'] = array_merge( $image_meta['sizes'], $kt_add_imagesize_retina );
		}
	}
	if ( function_exists( 'wp_calculate_image_srcset' ) ) {
		$output = wp_calculate_image_srcset( array( $width, $height ), $url, $image_meta, $id );
	} else {
		$output = '';
	}
	return $output;
}
function kt_woo_get_srcset_output( $width, $height, $url, $id ) {
	$img_srcset = kt_woo_get_srcset( $width, $height, $url, $id );
	if ( ! empty( $img_srcset ) ) {
		$output = 'srcset="' . esc_attr( $img_srcset ) . '" sizes="(max-width: ' . esc_attr( $width ) . 'px) 100vw, ' . esc_attr( $width ) . 'px"';
	} else {
		$output = '';
	}
	return $output;
}


add_action( 'cmb2_render_kt_woo_text_number', 'kt_woo_small_render_text_number', 10, 5 );
function kt_woo_small_render_text_number( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
	echo $field_type_object->input(
		array(
			'class' => 'cmb2-text-small',
			'type' => 'number',
			'step' => 'any',
		)
	);
}
add_filter( 'cmb2_sanitize_kt_woo_text_number', 'kt_woo_small_sanitize_text_number', 10, 2 );
function kt_woo_small_sanitize_text_number( $null, $new ) {
	// $new = preg_replace( "/[^0-9]/", "", $new );
	return $new;
}

// Utility function: Get an array of discount percentages from the variations
function kt_woo_get_variations_discount_percentage( $product ) {
	$prices = $product->get_variation_prices( true );
	$percentages = array();

	foreach( $prices['regular_price']  as $key_id => $price ) {
		if( $price != $prices['price'][$key_id] ) {
			$reg_price  = floatval($price);
			$sale_price = floatval($prices['price'][$key_id]);
			if ( $reg_price ) {
				$percentages[] = round( ($reg_price - $sale_price) / $reg_price * 100);
			}
		}
	}
	return array_unique($percentages);
}

// Utility function: Get an array of discount percentages from the groups
function kt_woo_get_grouped_discount_percentage( $product ) {
	$children = $product->get_children( true );
	$percentages = array();

	foreach( $children as $child ) {
		$percentages[] = kt_woo_get_the_sale_percentage( $child );
	}
	return array_unique($percentages);
}

// Gets the global products sale percentage
function kt_woo_get_the_sale_percentage( $the_product = 0 ) {
	global $product;
	$the_product = $the_product ? wc_get_product( $the_product ) : $product;

	if ( ! $the_product->is_on_sale() ) return false; // Only "On sale" products

	if ( $the_product->is_type('variable') ) {
		$percentages = kt_woo_get_variations_discount_percentage( $the_product );
		return  max($percentages);
	} else if ( $the_product->is_type('grouped') ) {
		$percentages = kt_woo_get_grouped_discount_percentage( $the_product );
		return  max($percentages);
	} else {
		$reg_price  = (float) $the_product->get_regular_price();
		$sale_price = (float) $the_product->get_sale_price();
		if ( $reg_price ) {
			return round( ($reg_price - $sale_price) / $reg_price * 100);
		}
	}
}

function kt_woo_small_render_text_vote_up( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
	echo $field_type_object->input(
		array(
			'class' => 'cmb2-text-small',
			'type' => 'number',
		)
	);
}
add_action( 'cmb2_render_text_vote_up', 'kt_woo_small_render_text_vote_up', 10, 5 );
function kt_woo_sanitize_text_vote_up_callback( $override_value, $value, $post_id ) {
	$votes = get_post_meta( $post_id, '_kt_review_votes', true );
	$old = get_post_meta( $post_id, '_kt_review_upvotes', true );
	if ( $old >= $value ) {
		$new = $old - $value;
		$newvotes = $votes - $new;
	} else {
		$new = $value - $old;
		$newvotes = $votes + $new;
	}
	update_post_meta( $post_id, '_kt_review_votes', $newvotes );
	return $value;
}
add_filter( 'cmb2_sanitize_text_vote_up', 'kt_woo_sanitize_text_vote_up_callback', 10, 3 );
function kt_woo_small_render_text_vote_down( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
	echo $field_type_object->input(
		array(
			'class' => 'cmb2-text-small',
			'type' => 'number',
		)
	);
}
add_action( 'cmb2_render_text_vote_down', 'kt_woo_small_render_text_vote_down', 10, 5 );
function kt_woo_sanitize_text_vote_down_callback( $override_value, $value, $post_id ) {
	$votes = get_post_meta( $post_id, '_kt_review_votes', true );
	$old = get_post_meta( $post_id, '_kt_review_downvotes', true );
	if ( $old >= $value ) {
		$new = $old - $value;
		$newvotes = $votes + $new;
	} else {
		$new = $value - $old;
		$newvotes = $votes - $new;
	}
	update_post_meta( $post_id, '_kt_review_votes', $newvotes );
	return $value;
}
add_filter( 'cmb2_sanitize_text_vote_down', 'kt_woo_sanitize_text_vote_down_callback', 10, 3 );

function kt_woo_get_post_options( $query_args ) {

	$args = wp_parse_args(
		$query_args,
		array(
			'post_type'   => 'post',
			'numberposts' => -1,
		)
	);

	$posts = get_posts( $args );

	$post_options = array();
	if ( $posts ) {
		foreach ( $posts as $post ) {
			$post_options[ $post->ID ] = $post->post_title;
		}
	}

	return $post_options;
}

function kt_woo_coupon_posts_options() {
	$posts = kt_woo_get_post_options(
		array(
			'post_type' => 'shop_coupon',
			'numberposts' => -1,
		)
	);
	$posts['0'] = __( 'Select a Coupon', 'kadence-woo-extras' );
	return $posts;
}
function kt_woo_product_posts_options() {
	$posts = kt_woo_get_post_options(
		array(
			'post_type' => 'product',
			'numberposts' => -1,
		)
	);
	$posts['0'] = __( 'Select a Product', 'kadence-woo-extras' );
	return $posts;
}
function kt_woo_product_posts_options_muiti() {
	$posts = kt_woo_get_post_options(
		array(
			'post_type' => 'product',
			'numberposts' => -1,
		)
	);
	return $posts;
}
function kt_woo_size_chart_posts_options() {
	$posts = kt_woo_get_post_options(
		array(
			'post_type' => 'kt_size_chart',
			'numberposts' => -1,
		)
	);
	$posts['0'] = __( 'None', 'kadence-woo-extras' );
	return $posts;
}
function kt_woo_change_cmb2_styles() {
	wp_deregister_style( 'cmb2-styles' );
	wp_register_style( 'cmb2-styles', KADENCE_WOO_EXTRAS_URL . '/cmb/css/cmb2.css' );
}
// add_action('init', 'kt_woo_change_cmb2_styles', 20);

function kt_get_term_options( $field ) {
	$args = $field->args( 'get_terms_args' );
	$args = is_array( $args ) ? $args : array();

	$args = wp_parse_args( $args, array( 'taxonomy' => 'category' ) );

	$taxonomy = $args['taxonomy'];

	$terms = (array) cmb2_utils()->wp_at_least( '4.5.0' )
		? get_terms( $args )
		: get_terms( $taxonomy, $args );

	// Initate an empty array
	$term_options = array();
	if ( ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			$term_options[ $term->term_id ] = $term->name;
		}
	}

	return $term_options;
}

function kt_get_post_options( $field ) {
	$args = $field->args( 'get_posts_args' );
	$args = is_array( $args ) ? $args : array();

	$args = wp_parse_args( $args, array( 'post_type' => 'post', 'numberposts' => 10000 ) );

	$posts = get_posts( $args );

	$post_options = array();
	if ( ! empty( $posts ) ) {
		foreach ( $posts as $post ) {
			$post_options[ $post->ID ] = $post->post_title;
		}
	}

	return $post_options;
}

function kt_woo_default_placeholder_image() {
	return apply_filters( 'kt_woo_default_placeholder_image', 'http://placehold.it/' );
}

function kt_woo_get_image_array( $width = null, $height = null, $crop = true, $class = null, $alt = null, $id = null, $placeholder = false, $fallback = 'full' ) {
	if ( empty( $id ) ) {
		$id = get_post_thumbnail_id();
	}
	if ( ! empty( $id ) ) {
		$kt_woo_get_image = KT_WOO_Get_Image::getInstance();
		$image = $kt_woo_get_image->process( $id, $width, $height, $fallback );
		if ( empty( $alt ) ) {
			$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
		}
		$return_array = array(
			'src' => $image[0],
			'width' => $image[1],
			'height' => $image[2],
			'srcset' => $image[3],
			'class' => $class,
			'alt' => $alt,
			'full' => $image[4],
			'id' => ( isset( $image[5] ) ? $image[5] : '' ),
			'full_width' => ( isset( $image[6] ) ? $image[6] : '' ),
			'full_height' => ( isset( $image[7] ) ? $image[7] : '' ),
			'src_set' => ( isset( $image[8] ) ? $image[8] : '' ),
			'sizes' => ( isset( $image[9] ) ? $image[9] : '' ),
		);
	} else if ( empty( $id ) && $placeholder == true ) {
		if ( empty( $height ) ) {
			$height = $width;
		}
		if ( empty( $width ) ) {
			$width = $height;
		}
		$return_array = array(
			'src' => kt_woo_default_placeholder_image() . $width . 'x' . $height . '?text=Image+Placeholder',
			'width' => $width,
			'height' => $height,
			'srcset' => '',
			'sizes' => '',
			'src_set' => '',
			'id' => '',
			'class' => $class,
			'alt' => $alt,
			'full' => kt_woo_default_placeholder_image() . $width . 'x' . $height . '?text=Image+Placeholder',
			'full_width' => '',
			'full_height' => '',
		);
	} else {
		$return_array = array(
			'src' => '',
			'width' => '',
			'height' => '',
			'srcset' => '',
			'sizes' => '',
			'id' => '',
			'src_set' => '',
			'class' => '',
			'alt' => '',
			'full' => '',
			'full_width' => '',
			'full_height' => '',
		);
	}

	return $return_array;
}

function kt_woo_get_full_image_output( $width = null, $height = null, $crop = true, $class = null, $alt = null, $id = null, $placeholder = false, $lazy = false, $schema = true, $extra = null ) {
	$img = kt_woo_get_image_array( $width, $height, $crop, $class, $alt, $id, $placeholder );
	if ( $lazy ) {
		   $image_src_output = 'src="' . esc_url( $img['src'] ) . '"';
	} else {
		$image_src_output = 'src="' . esc_url( $img['src'] ) . '"';
	}
	$extras = '';
	if ( is_array( $extra ) ) {
		foreach ( $extra as $key => $value ) {
			$extras .= esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
		}
	} else {
		$extras = $extra;
	}
	if ( ! empty( $img['src'] ) && $schema == true ) {
		$output = '<div itemprop="image" itemscope itemtype="http://schema.org/ImageObject">';
		$output .= '<img ' . $image_src_output . ' width="' . esc_attr( $img['width'] ) . '" height="' . esc_attr( $img['height'] ) . '" ' . $img['srcset'] . ' class="' . esc_attr( $img['class'] ) . '" itemprop="contentUrl" alt="' . esc_attr( $img['alt'] ) . '" ' . $extras . '>';
		$output .= '<meta itemprop="url" content="' . esc_url( $img['src'] ) . '">';
		$output .= '<meta itemprop="width" content="' . esc_attr( $img['width'] ) . 'px">';
		$output .= '<meta itemprop="height" content="' . esc_attr( $img['height'] ) . 'px">';
		$output .= '</div>';
		return $output;

	} elseif ( ! empty( $img['src'] ) ) {
		return '<img ' . $image_src_output . ' width="' . esc_attr( $img['width'] ) . '" height="' . esc_attr( $img['height'] ) . '" ' . $img['srcset'] . ' class="' . esc_attr( $img['class'] ) . '" alt="' . esc_attr( $img['alt'] ) . '" ' . $extras . '>';
	} else {
		return null;
	}
}
/**
 * Get an SVG Icon
 *
 * @param string $icon the icon name.
 * @param string $icon_title the icon title for screen readers.
 * @param bool   $base if the baseline class should be added.
 */
function kadence_woo_extras_get_icon( $icon = 'search', $icon_title = '', $base = true, $aria = false ) {
	$display_title = apply_filters( 'kadence_svg_icons_have_title', true );
	$output = '<span class="kadence-svg-iconset' . ( $base ? ' svg-baseline' : '' ) . '">';
	switch ( $icon ) {
		case 'play':
			$output .= '<svg'. ( ! $aria ? ' aria-hidden="true"' : '' ) . ' class="kadence-svg-icon kadence-play-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">';
			if ( $display_title ) {
				$output .= '<title>' . ( ! empty( $icon_title ) ? $icon_title : esc_html__( 'Play', 'kadence-woo-extras' ) ) . '</title>';
			}
			$output .= '<path d="M15 10.001c0 0.299-0.305 0.514-0.305 0.514l-8.561 5.303c-0.624 0.409-1.134 0.106-1.134-0.669v-10.297c0-0.777 0.51-1.078 1.135-0.67l8.561 5.305c-0.001 0 0.304 0.215 0.304 0.514z"></path>
			</svg>';
			break;
		case 'play-circle':
			$output .= '<svg'. ( ! $aria ? ' aria-hidden="true"' : '' ) . ' class="kadence-svg-icon kadence-play-circle-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">';
			if ( $display_title ) {
				$output .= '<title>' . ( ! empty( $icon_title ) ? $icon_title : esc_html__( 'Play', 'kadence-woo-extras' ) ) . '</title>';
			}
			$output .= '<path d="M8 0c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zM8 14.5c-3.59 0-6.5-2.91-6.5-6.5s2.91-6.5 6.5-6.5 6.5 2.91 6.5 6.5-2.91 6.5-6.5 6.5zM6 4.5l6 3.5-6 3.5z"></path>
			</svg>';
			break;
		case 'eye':
			$output .= '<svg'. ( ! $aria ? ' aria-hidden="true"' : '' ) . ' class="kadence-svg-icon kadence-eye-svg" fill="currentColor" version="1.1" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28">';
			if ( false ) {
				$output .= '<title>' . ( ! empty( $icon_title ) ? $icon_title : esc_html__( 'Eye', 'kadence-woo-extras' ) ) . '</title>';
			}
			$output .= '<path d="M26 15c-1.484-2.297-3.516-4.266-5.953-5.516A6.943 6.943 0 0121 13c0 3.859-3.141 7-7 7s-7-3.141-7-7c0-1.234.328-2.453.953-3.516C5.515 10.734 3.484 12.703 2 15c2.672 4.125 6.984 7 12 7s9.328-2.875 12-7zM14.75 9a.76.76 0 00-.75-.75c-2.609 0-4.75 2.141-4.75 4.75 0 .406.344.75.75.75s.75-.344.75-.75A3.268 3.268 0 0114 9.75a.76.76 0 00.75-.75zM28 15c0 .391-.125.75-.313 1.078C24.812 20.812 19.546 24 14 24S3.187 20.797.313 16.078C.126 15.75 0 15.39 0 15s.125-.75.313-1.078C3.188 9.203 8.454 6 14 6s10.813 3.203 13.687 7.922c.187.328.313.688.313 1.078z"></path>
			</svg>';
			break;
		default:
			$output .= '';
			break;
	}
	$output .= '</span>';

	$output = apply_filters( 'kadence_svg_icon', $output, $icon, $icon_title, $base );

	return $output;
}
/**
 * Print an SVG Icon
 *
 * @param string $icon the icon name.
 * @param string $icon_title the icon title for screen readers.
 * @param bool   $base if the baseline class should be added.
 */
function kadence_woo_extras_print_icon( $icon = 'search', $icon_title = '', $base = true, $aria = false ) {
	echo kadence_woo_extras_get_icon( $icon, $icon_title, $base, $aria ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
