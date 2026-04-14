<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Override the default output.
 *
 * @param array $args Arguments for the function.
 */
function wc_dropdown_variation_attribute_options( $args = array() ) {
	kt_variation_swatches_attribute_options( $args );
}
/**
 * Override the default output.
 *
 * @param array $args Arguments for the function.
 */
function kad_wc_radio_variation_attribute_options( $args = array() ) {
	kt_variation_swatches_attribute_options( $args );
}
/**
 * Determine which output to use for the variation swatches.
 *
 * @param array $args Arguments for the function.
 */
function kt_variation_swatches_attribute_options( $args ) {
	$args = wp_parse_args(
		apply_filters( 'woocommerce_dropdown_variation_attribute_options_args', $args ),
		array(
			'options' => false,
			'attribute' => false,
			'product' => false,
			'selected' => false,
			'name' => '',
			'id' => '',
			'class' => '',
			'show_option_none' => __( 'Choose an option', 'woocommerce' ),
			'rand_ident' => uniqid(),
		)
	);
	global $post, $kt_woo_extras;
	$key = md5( sanitize_title( $args['attribute'] ) );
	$type = get_post_meta( $post->ID, '_kt_variation_swatch_type', true );
	if ( ! empty( $type ) ) {
		if ( ! isset( $type[ $key ]['display_type'] ) || $type[ $key ]['display_type'] == 'default' ) {
			if ( isset( $kt_woo_extras['swatches_type'] ) ) {
				$kt_type = $kt_woo_extras['swatches_type'];
			} else {
				$kt_type = 'dropdown';
			}
		} else {
			$kt_type = $type[ $key ]['display_type'];
		}
	} else {
		if ( isset( $kt_woo_extras['swatches_type'] ) ) {
			$kt_type = $kt_woo_extras['swatches_type'];
		} else {
			$kt_type = 'dropdown';
		}
	}
	if ( ! empty( $args['attribute'] ) && 'taxonomy' == $kt_type ) {
		if ( taxonomy_exists( $args['attribute'] ) ) {
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			$att_type = '';
			foreach ( $attribute_taxonomies as $tax ) {
				if ( 'pa_' . $tax->attribute_name == $args['attribute'] ) {
					$att_type = $tax->attribute_type;
					break;
				}
			}
			if ( ! empty( $att_type ) && ( $att_type == 'dropdown' ||  $att_type == 'radio_box' ) ) {
				$kt_type = $att_type;
			}
		}
	}
	if ( is_a( $args['product'], 'WC_Product_PW_Gift_Card' ) ) {
		$kt_type = 'dropdown';
	}
	// Get selected value.
	if ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {
		$selected_key = 'attribute_' . sanitize_title( $args['attribute'] );
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$args['selected'] = isset( $_REQUEST[ $selected_key ] ) ? wc_clean( wp_unslash( $_REQUEST[ $selected_key ] ) ) : $args['product']->get_variation_default_attribute( $args['attribute'] );
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
	if ( ( empty( $kt_woo_extras['variation_label_placement'] ) && isset( $kt_woo_extras['variation_label'] ) && true == $kt_woo_extras['variation_label'] ) || ( ! empty( $kt_woo_extras['variation_label_placement'] ) && 'above' === $kt_woo_extras['variation_label_placement'] ) ) {
		kt_wc_variation_attribute_label( $args );
	}
	$kt_type = apply_filters( 'kadence_variation_swatch_type', $kt_type, $args );
	$key             = md5( sanitize_title( $args['attribute'] ) );
	$type           = get_post_meta( $post->ID, '_kt_variation_swatch_type', true );
	// Label Size.
	if ( ! empty( $type ) ) {
		if ( $type[ $key ]['display_type'] == $kt_type && $type[ $key ]['display_type'] != 'default' && $type[ $key ]['display_type'] != 'taxonomy' ) {
			//if type is the type to use and it's not "default" and is not "taxonomy"
			if ( isset( $type[ $key ]['display_size'] ) && ! empty( $type[ $key ]['display_size'] ) && $type[ $key ]['display_size'] != 'default' ) {
				//and it has a defined non-default size, use this size
				$size = $type[ $key ]['display_size'];
			} else {
				//otherwise use the sitewide default
				$size = ( ! empty( $kt_woo_extras['swatches_size'] ) ? $kt_woo_extras['swatches_size'] : '60' );
			}
		} else {
			//otherwise use the sitewide default
			$size = ( ! empty( $kt_woo_extras['swatches_size'] ) ? $kt_woo_extras['swatches_size'] : '60' );
		}
	} else {
		//otherwise use the sitewide default
		$size = ( ! empty( $kt_woo_extras['swatches_size'] ) ? $kt_woo_extras['swatches_size'] : '60' );
	}
	$args['size'] = $size;
	// Show Label?
	if ( ! empty( $type ) ) {
		if ( ( ! isset( $type[ $key ]['display_label'] ) ) || ( ! empty( $type[ $key ]['display_label'] ) && $type[ $key ]['display_label'] == 'default' ) ) {
			$label = ( ! empty( $kt_woo_extras['swatches_label'] ) ? $kt_woo_extras['swatches_label'] : 'false' );
		} else {
			$label = $type[ $key ]['display_label'];
		}
	} else {
		$label = ( ! empty( $kt_woo_extras['swatches_label'] ) ? $kt_woo_extras['swatches_label'] : 'false' );
	}
	$args['label'] = $label;
	echo apply_filters( 'kadence_variation_attribute_options_html_before', '', $args );
	if ( 'radio_box' == $kt_type ) {
		$args['class'] = 'kt-no-select2';
		echo '<div class="kt-radio-variation-container">';
		kt_wc_radio_variation_attribute_options( $args );
		kt_variable_swatch_wc_dropdown_variation_attribute_options( $args );
		echo '</div>';
	} elseif ( 'color_image' == $kt_type ) {
		$args['class'] = 'kt-no-select2';
		echo '<div class="kt-radio-variation-container">';
		kt_wc_color_image_variation_attribute_options( $args );
		kt_variable_swatch_wc_dropdown_variation_attribute_options( $args );
		echo '</div>';
	} elseif ( 'taxonomy' == $kt_type ) {
		$args['class'] = 'kt-no-select2';
		echo '<div class="kt-radio-variation-container">';
		kt_wc_color_image_tax_variation_attribute_options( $args );
		kt_variable_swatch_wc_dropdown_variation_attribute_options( $args );
		echo '</div>';
	} else {
		kt_variable_swatch_wc_dropdown_variation_attribute_options( $args );
	}
	echo apply_filters( 'kadence_variation_attribute_options_html_after', '', $args );
}
/**
 * Determine which output to use for the variation swatches.
 *
 * @param array $args Arguments for the function.
 */
function kt_archive_variation_swatches_attribute_options( $args ) {
	$args = wp_parse_args(
		apply_filters( 'woocommerce_dropdown_variation_attribute_options_args', $args ),
		array(
			'options' => false,
			'attribute' => false,
			'product' => false,
			'selected' => false,
			'name' => '',
			'id' => '',
			'class' => '',
			'show_option_none' => __( 'Choose an option', 'woocommerce' ),
			'rand_ident' => uniqid(),
		)
	);
	global $post, $kt_woo_extras;
	$key = md5( sanitize_title( $args['attribute'] ) );
	$type = get_post_meta( $post->ID, '_kt_variation_swatch_type', true );
	if ( ! empty( $type ) ) {
		if ( ! isset( $type[ $key ]['display_type'] ) || $type[ $key ]['display_type'] == 'default' ) {
			if ( isset( $kt_woo_extras['swatches_type'] ) ) {
				$kt_type = $kt_woo_extras['swatches_type'];
			} else {
				$kt_type = 'dropdown';
			}
		} else {
			$kt_type = $type[ $key ]['display_type'];
		}
	} else {
		if ( isset( $kt_woo_extras['swatches_type'] ) ) {
			$kt_type = $kt_woo_extras['swatches_type'];
		} else {
			$kt_type = 'dropdown';
		}
	}
	if ( ! empty( $args['attribute'] ) && 'taxonomy' == $kt_type ) {
		if ( taxonomy_exists( $args['attribute'] ) ) {
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			$att_type = '';
			foreach ( $attribute_taxonomies as $tax ) {
				if ( 'pa_' . $tax->attribute_name == $args['attribute'] ) {
					$att_type = $tax->attribute_type;
					break;
				}
			}
			if ( ! empty( $att_type ) && ( $att_type == 'dropdown' ||  $att_type == 'radio_box' ) ) {
				$kt_type = $att_type;
			}
		}
	}
	if ( is_a( $args['product'], 'WC_Product_PW_Gift_Card' ) ) {
		$kt_type = 'dropdown';
	}
	// Get selected value.
	if ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {
		$selected_key = 'attribute_' . sanitize_title( $args['attribute'] );
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$args['selected'] = isset( $_REQUEST[ $selected_key ] ) ? wc_clean( wp_unslash( $_REQUEST[ $selected_key ] ) ) : $args['product']->get_variation_default_attribute( $args['attribute'] );
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
	if ( ( ! empty( $kt_woo_extras['variation_archive_label_placement'] ) && 'above' === $kt_woo_extras['variation_archive_label_placement'] ) ) {
		kt_wc_variation_attribute_label( $args );
	}
	$kt_type = apply_filters( 'kadence_variation_swatch_type', $kt_type, $args );
	$key             = md5( sanitize_title( $args['attribute'] ) );
	$type           = get_post_meta( $post->ID, '_kt_variation_swatch_type', true );
	// Label Size.
	if ( ! empty( $type ) ) {
		if ( ( ! isset( $type[ $key ]['display_size'] ) ) || ( ! empty( $type[ $key ]['display_size'] ) && $type[ $key ]['display_size'] == 'default' ) ) {
			$args['size'] = ( ! empty( $kt_woo_extras['archive_swatches_size'] ) ? $kt_woo_extras['archive_swatches_size'] : '30' );
		} else {
			$args['size'] = $type[ $key ]['display_size'];
		}
	} else {
		$args['size'] = ( ! empty( $kt_woo_extras['archive_swatches_size'] ) ? $kt_woo_extras['archive_swatches_size'] : '30' );
	}
	// Show Label?
	if ( ! empty( $type ) ) {
		if ( ( ! isset( $type[ $key ]['display_label'] ) ) || ( ! empty( $type[ $key ]['display_label'] ) && $type[ $key ]['display_label'] == 'default' ) ) {
			$args['label'] = ( ! empty( $kt_woo_extras['swatches_label'] ) ? $kt_woo_extras['swatches_label'] : 'false' );
		} else {
			$args['label'] = $type[ $key ]['display_label'];
		}
	} else {
		$args['label'] = ( ! empty( $kt_woo_extras['swatches_label'] ) ? $kt_woo_extras['swatches_label'] : 'false' );
	}
	echo apply_filters( 'kadence_variation_attribute_options_html_before', '', $args );
	if ( 'radio_box' == $kt_type ) {
		$args['class'] = 'kt-no-select2';
		echo '<div class="kt-radio-variation-container">';
		kt_wc_radio_variation_attribute_options( $args );
		kt_variable_swatch_wc_dropdown_variation_attribute_options( $args );
		echo '</div>';
	} elseif ( 'color_image' == $kt_type ) {
		$args['class'] = 'kt-no-select2';
		echo '<div class="kt-radio-variation-container">';
		kt_wc_color_image_variation_attribute_options( $args );
		kt_variable_swatch_wc_dropdown_variation_attribute_options( $args );
		echo '</div>';
	} elseif ( 'taxonomy' == $kt_type ) {
		$args['class'] = 'kt-no-select2';
		echo '<div class="kt-radio-variation-container">';
		kt_wc_color_image_tax_variation_attribute_options( $args );
		kt_variable_swatch_wc_dropdown_variation_attribute_options( $args );
		echo '</div>';
	} else {
		kt_variable_swatch_wc_dropdown_variation_attribute_options( $args );
	}
	echo apply_filters( 'kadence_variation_attribute_options_html_after', '', $args );
}
/**
 * Output a list of variation label attributes for use in the add to cart forms.
 *
 * @param array $args Arguments for the function.
 */
function kt_wc_variation_attribute_label( $args = array() ) {
	echo '<div class="kt-variation-label">';
	echo '<label for="' . esc_attr( ( $args['id'] ? $args['id'] : sanitize_title( $args['attribute'] ) ) ) . '-' . $args['rand_ident'] . '">' . wc_attribute_label( $args['attribute'] ) . '</label>';
	echo apply_filters( 'kadence_variation_attribute_label_html', '', $args );
	echo '</div>';
}
/**
 * Output a list of radio variation attributes for use in the add to cart forms.
 *
 * @param array $args Arguments for the function.
 */
function kt_wc_radio_variation_attribute_options( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected'         => false,
			'name'             => '',
			'id'               => '',
			'rand_ident'	   => '',
		)
	);
	$options   = $args['options'];
	$product   = $args['product'];
	$attribute = $args['attribute'];
	$name      = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
	$id        = $args['id'] ? $args['id'] : sanitize_title( $attribute );
	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[ $attribute ];
	}
	echo '<fieldset id="' . esc_attr( $id ) . '" class="kad_radio_variations" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';

	if ( ! empty( $options ) ) {
		if ( $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $options ) ) {
					echo '<input type="radio" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( sanitize_title( $args['selected'] ), $term->slug, false ) . ' id="' . esc_attr( sanitize_title( $name ) ) . esc_attr( $term->slug ) . esc_attr( $product->get_id() ) . '-' . $args['rand_ident'] . '" name="' . sanitize_title( $name ) . '"><label for="' . esc_attr( sanitize_title( $name ) ) . esc_attr( $term->slug ) . esc_attr( $product->get_id() ) . '-' . $args['rand_ident'] . '">' . apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) . '</label>';
				}
			}
		} else {
			foreach ( $options as $option ) {
				echo '<input type="radio" value="' . esc_attr( $option ) . '" ' . checked( $args['selected'], $option, false ) . ' id="' . esc_attr( sanitize_title( $name ) ) . esc_attr( sanitize_title( $option ) ) . esc_attr( $product->get_id() ) . '-' . $args['rand_ident'] . '" name="' . esc_attr( sanitize_title( $name ) ) . '"><label for="' . esc_attr( sanitize_title( $name ) ) . esc_attr( sanitize_title( $option ) ) . esc_attr( $product->get_id() ) . '-' . $args['rand_ident'] . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</label>';
			}
		}
	}
	echo '</fieldset>';
}
/**
 * Output a list of image variation attributes for use in the add to cart forms.
 *
 * @param array $args Arguments for the function.
 */
function kt_wc_color_image_variation_attribute_options( $args = array() ) {
	global $post, $kt_woo_extras;
	$args = wp_parse_args(
		$args,
		array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected'         => false,
			'name'             => '',
			'id'               => '',
			'label'            => 'default',
			'size'             => '60',
			'rand_ident'       => '',
		)
	);
	$options   = $args['options'];
	$product   = $args['product'];
	$attribute = $args['attribute'];
	$name      = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
	$id        = $args['id'] ? $args['id'] : sanitize_title( $attribute );
	$label     = $args['label'];
	$size      = $args['size'];
	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[ $attribute ];
	}
	$label_size   = 'width:' . $size . 'px; height:' . $size . 'px;';
	$key          = md5( sanitize_title( $args['attribute'] ) );
	$type_options = get_post_meta( $post->ID, '_kt_variation_swatch_type_options', true );
	echo '<fieldset id="' . esc_attr( $id ) . '" class="kad_radio_variations kt-image-color-sw" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';
	if ( ! empty( $options ) ) {
		if ( $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $options ) ) {
					$option_key = md5( $term->slug );
					// color or Image?
					if ( ! empty( $type_options ) ) {
						if ( isset( $type_options[ $key ][ $option_key ]['type'] ) && $type_options[ $key ][ $option_key ]['type'] == 'image' ) {
							if ( isset( $type_options[ $key ][ $option_key ]['image'] ) && ! empty( $type_options[ $key ][ $option_key ]['image'] ) ) {
								$image_id = $type_options[ $key ][ $option_key ]['image_id'];
							} else {
								$image_id = null;
							}
							$color = '';
							$data_color = '';
							$image = true;
						} else {
							if ( isset( $type_options[ $key ][ $option_key ]['color'] ) && ! empty( $type_options[ $key ][ $option_key ]['color'] ) ) {
								$color = 'background:' . $type_options[ $key ][ $option_key ]['color'] . ';';
								$data_color = 'data-color="' . $type_options[ $key ][ $option_key ]['color'] . '"';
							} else {
								$color = 'background:#999999;';
								$data_color = 'data-color="#999999"';
							}
							$image = false;
						}
					} else {
						$color = 'background:#999999;';
						$data_color = 'data-color="#999999"';
						$image = false;
					}
					// Accessibility this has to come first.
					echo '<input type="radio" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( sanitize_title( $args['selected'] ), $term->slug, false ) . ' id="' . esc_attr( sanitize_title( $name ) ) . esc_attr( $term->slug ) . esc_attr( $product->get_id() ) . '-' . $args['rand_ident'] . '" name="' . sanitize_title( $name ) . '">';
					echo '<label for="' . esc_attr( sanitize_title( $name ) ) . esc_attr( $term->slug ) . esc_attr( $product->get_id() ) . '-' . $args['rand_ident'] . '" ' . $data_color . '>';
					if ( $label == 'above' ) {
						echo '<span class="label_text">' . apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) . '</span>';
					}
					if ( $label == 'tooltip' ) {
						$tooldata = 'aria-describedby="label_tooltip_' . esc_attr( $term->term_id ) . '"';
						$toolelement = '<span class="kadence-wp-tips" id="label_tooltip_' . esc_attr( $term->term_id ) . '" role="tooltip" aria-hidden="true">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</span>';
					} else {
						$tooldata = '';
						$toolelement = '';
					}
					echo '<span class="kt_color_sw_span' . ( 'tooltip' === $label ? ' kadence-wp-tips-container' : '' ) . '" style="' . $color . ' ' . $label_size . '" ' . $tooldata . '>';
					if ( $image ) {
						echo kt_woo_get_full_image_output( $size, $size, true, 'kt-sw-img', sanitize_title( $term->name ), $image_id, false, false, false );
					}
					echo $toolelement;
					echo '</span>';
					if ( $label == 'below' ) {
						echo '<span class="label_text">' . apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) . '</span>';
					}
					echo '</label>';
				}
			}
		} else {
			foreach ( $options as $option ) {
				// color or Image?
				$option_key = md5( sanitize_title( strtolower( $option ) ) );
				if ( isset( $type_options[ $key ][ $option_key ]['type'] ) && $type_options[ $key ][ $option_key ]['type'] == 'image' ) {
					if ( isset( $type_options[ $key ][ $option_key ]['image'] ) && ! empty( $type_options[ $key ][ $option_key ]['image'] ) ) {
						$image_id = $type_options[ $key ][ $option_key ]['image_id'];
					} else {
						$image_id = null;
					}
					$color = '';
					$data_color = '';
					$image = true;
				} else {
					if ( isset( $type_options[ $key ][ $option_key ]['color'] ) && ! empty( $type_options[ $key ][ $option_key ]['color'] ) ) {
							$color = 'background:' . $type_options[ $key ][ $option_key ]['color'] . ';';
							$data_color = 'data-color="' . $type_options[ $key ][ $option_key ]['color'] . '"';
					} else {
						$color = 'background:#999999;';
						$data_color = 'data-color="#999999"';
					}
					$image = false;
				}
				echo '<input type="radio" value="' . esc_attr( $option ) . '" ' . checked( $args['selected'], $option, false ) . ' id="' . esc_attr( sanitize_title( $name ) ) . esc_attr( sanitize_title( $option ) ) . esc_attr( $product->get_id() ) . '" name="' . sanitize_title( $name ) . '">';
				echo '<label for="' . esc_attr( sanitize_title( $name ) ) . esc_attr( sanitize_title( $option ) ) . esc_attr( $product->get_id() ) . '" ' . $data_color . '>';
				if ( $label == 'above' ) {
					echo '<span class="label_text">' . apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) . '</span>';
				}
				if ( $label == 'tooltip' ) {
					$tooldata = 'aria-describedby="label_tooltip_' . esc_attr( $option_key ) . '"';
					$toolelement = '<span class="kadence-wp-tips" id="label_tooltip_' . esc_attr( $option_key ) . '" role="tooltip" aria-hidden="true">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</span>';
				} else {
					$tooldata = '';
					$toolelement = '';
				}
				echo '<span class="kt_color_sw_span' . ( 'tooltip' === $label ? ' kadence-wp-tips-container' : '' ) . '" style="' . $color . ' ' . $label_size . '" ' . $tooldata . '>';
				if ( $image ) {
					echo kt_woo_get_full_image_output( $size, $size, true, 'kt-sw-img', sanitize_title( $option ), $image_id, false, false, false );
				}
				echo $toolelement;
				echo '</span>';
				if ( $label == 'below' ) {
					echo '<span class="label_text">' . apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) . '</span>';
				}
				echo '</label>';
			}
		}
	}
	echo '</fieldset>';
}
/**
 * Output a list of taxonomy based variation attributes for use in the add to cart forms.
 *
 * @param array $args Arguments for the function.
 */
function kt_wc_color_image_tax_variation_attribute_options( $args = array() ) {
	global $post, $kt_woo_extras;
	$args = wp_parse_args(
		$args,
		array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected'         => false,
			'name'             => '',
			'id'               => '',
			'label'            => 'default',
			'size'             => '60',
			'rand_ident'       => '',
		)
	);
	$options   = $args['options'];
	$product   = $args['product'];
	$attribute = $args['attribute'];
	$name      = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
	$id        = $args['id'] ? $args['id'] : sanitize_title( $attribute );
	$label     = $args['label'];
	$size      = $args['size'];
	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[ $attribute ];
	}
	$key = md5( sanitize_title( $args['attribute'] ) );
	$type_options   = get_post_meta( $post->ID, '_kt_variation_swatch_type_options', true );
	$label_size   = 'width:' . $size . 'px; height:' . $size . 'px;';

	echo '<fieldset id="' . esc_attr( $id ) . '" class="kad_radio_variations kt-image-color-sw" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';
	if ( ! empty( $options ) ) {
		if ( $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $options ) ) {
					$cat_term_id = $term->term_id;
					$meta = get_option( 'kt_woo_extras_tax_swatch_type' );
					if ( empty( $meta ) ) {
						$meta = array();
					}
					if ( ! is_array( $meta ) ) {
						$meta = (array) $meta;
					}
					$swatch_type = get_term_meta( $cat_term_id, '_kt_woo_extras_swatch_type', true );
					if ( empty( $swatch_type ) ) {
						$data = isset( $meta[ $cat_term_id ] ) ? $meta[ $cat_term_id ] : array();
						$swatch_type = ( ! empty( $data['kt_woo_extras_swatch_type'] ) ? $data['kt_woo_extras_swatch_type'] : '' );
					}
					// color or Image?
					if ( ! empty( $swatch_type ) && 'image' === $swatch_type ) {
						$swatch_image = get_term_meta( $cat_term_id, '_kt_woo_extras_swatch_image', true );
						if ( ! isset( $swatch_image[0] ) ) {
							$data = isset( $meta[ $cat_term_id ] ) ? $meta[ $cat_term_id ] : array();
							$swatch_image = ( ! empty( $data['kt_woo_extras_swatch_image'] ) ? $data['kt_woo_extras_swatch_image'] : '' );
						}
						if ( isset( $swatch_image[0] ) ) {
							$image_id = $swatch_image[0];

						} else {
							$image_id = null;
						}
						$color = '';
						$data_color = '';
						$image = true;
					} else {
						$swatch_color = get_term_meta( $cat_term_id, '_kt_woo_extras_swatch_color', true );
						if ( empty( $swatch_color ) ) {
							$data = isset( $meta[ $cat_term_id ] ) ? $meta[ $cat_term_id ] : array();
							$swatch_color = ( ! empty( $data['kt_woo_extras_swatch_color'] ) ? $data['kt_woo_extras_swatch_color'] : '' );
						}
						if ( ! empty( $swatch_color ) ) {
							$color = 'background:' . $swatch_color . ';';
							$data_color = 'data-color="' . $swatch_color . '"';
						} else {
							$color = 'background:#999999;';
							$data_color = 'data-color="#999999"';
						}
						$image = false;
					}
					//add a random identifier to the radio input and label id's to differentiate multiple swatch groups per page
					echo '<input type="radio" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" value="' . esc_attr( $term->slug ) . '" ' . checked( sanitize_title( $args['selected'] ), $term->slug, false ) . ' id="' . esc_attr( sanitize_title( $name ) ) . esc_attr( $term->slug ) . esc_attr( $product->get_id() ) . '-' . $args['rand_ident'] . '" name="' . sanitize_title( $name ) . '">';
					echo '<label for="' . esc_attr( sanitize_title( $name ) ) . esc_attr( $term->slug ) . esc_attr( $product->get_id() ) . '-' . $args['rand_ident'] . '" ' . $data_color . '>';
					if ( $label == 'above' ) {
						echo '<span class="label_text">' . apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) . '</span>';
					}
					if ( $label == 'tooltip' ) {
						$tooldata = 'data-toggle="tooltip" data-placement="top" data-original-title="' . esc_attr( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '"';
					} else {
						$tooldata = '';
					}
					if ( $label == 'tooltip' ) {
						$tooldata = 'aria-describedby="label_tooltip_' . esc_attr( $term->term_id ) . '"';
						$toolelement = '<span class="kadence-wp-tips" id="label_tooltip_' . esc_attr( $term->term_id ) . '" role="tooltip" aria-hidden="true">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</span>';
					} else {
						$tooldata = '';
						$toolelement = '';
					}
					echo '<span class="kt_color_sw_span' . ( 'tooltip' === $label ? ' kadence-wp-tips-container' : '' ) . '" style="' . $color . ' ' . $label_size . '" ' . $tooldata . '>';
					if ( $image ) {
						echo kt_woo_get_full_image_output( $size, $size, true, 'kt-sw-img', sanitize_title( $term->name ), $image_id, false, false, false );
					}
					echo $toolelement;
					echo '</span>';
					if ( $label == 'below' ) {
						echo '<span class="label_text">' . apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) . '</span>';
					}
					echo '</label>';
				}
			}
		} else {
			foreach ( $options as $option ) {
				// color or Image?.
				$option_key = md5( sanitize_title( strtolower( $option ) ) );
				if ( isset( $type_options[ $key ][ $option_key ]['type'] ) && $type_options[ $key ][ $option_key ]['type'] == 'image' ) {
					if ( isset( $type_options[ $key ][ $option_key ]['image'] ) && ! empty( $type_options[ $key ][ $option_key ]['image'] ) ) {
						$image_id = $type_options[ $key ][ $option_key ]['image_id'];
					} else {
						$image_id = null;
					}
					$color = '';
					$data_color = '';
					$image = true;
				} else {
					if ( isset( $type_options[ $key ][ $option_key ]['color'] ) && ! empty( $type_options[ $key ][ $option_key ]['color'] ) ) {
						$color = 'background:' . $type_options[ $key ][ $option_key ]['color'] . ';';
						$data_color = 'data-color="' . $type_options[ $key ][ $option_key ]['color'] . '"';
					} else {
						$color = 'background:#999999;';
						$data_color = 'data-color="#999999"';
					}
					$image = false;
				}
				echo '<input type="radio" value="' . esc_attr( $option ) . '" ' . checked( $args['selected'], $option, false ) . ' id="' . esc_attr( sanitize_title( $name ) ) . esc_attr( sanitize_title( $option ) ) . esc_attr( $product->get_id() ) . '" name="' . sanitize_title( $name ) . '">';
				echo '<label for="' . esc_attr( sanitize_title( $name ) ) . esc_attr( sanitize_title( $option ) ) . esc_attr( $product->get_id() ) . '" ' . $data_color . '>';
				if ( $label == 'above' ) {
					echo '<span class="label_text">' . apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) . '</span>';
				}
				if ( $label == 'tooltip' ) {
					$tooldata = 'data-toggle="tooltip" data-placement="top" data-original-title="' . esc_attr( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '"';
				} else {
					$tooldata = '';
				}
				echo '<span class="kt_color_sw_span' . ( 'tooltip' === $label ? ' kadence-wp-tips-container' : '' ) . '" style="' . $color . ' ' . $label_size . '" ' . $tooldata . '>';
				if ( $image ) {
					echo kt_woo_get_full_image_output( $size, $size, true, 'kt-sw-img', sanitize_title( $option ), $image_id, false, false, false );
				}
				echo '</span>';
				if ( $label == 'below' ) {
					echo '<span class="label_text">' . apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) . '</span>';
				}
				echo '</label>';
			}
		}
	}
	echo '</fieldset>';
}

/**
 * Output a list of variation attributes for use in the add to cart forms.
 *
 * @param array $args Arguments for the function.
 */
function kt_variable_swatch_wc_dropdown_variation_attribute_options( $args = array() ) {
	$args = wp_parse_args(
		apply_filters( 'woocommerce_dropdown_variation_attribute_options_args', $args ),
		array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected'         => false,
			'name'             => '',
			'id'               => '',
			'class'            => '',
			'show_option_none' => __( 'Choose an option', 'kadence-woo-extras' ),
		)
	);
	$options   = $args['options'];
	$product   = $args['product'];
	$attribute = $args['attribute'];
	$name      = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
	$id        = $args['id'] ? $args['id'] : sanitize_title( $attribute );
	$class     = $args['class'];

	if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[ $attribute ];
	}
	global $kt_woo_extras;
	if ( isset( $kt_woo_extras['choose_option_text'] ) && ! empty( $kt_woo_extras['choose_option_text'] ) ) {
		if ( ! is_a( $args['product'], 'WC_Product_PW_Gift_Card' ) ) {
			$args['show_option_none'] = $kt_woo_extras['choose_option_text'];
		}
	}
	if ( 'kt-no-select2' == $class ) {
		$html = '<select class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';
	} else {
		$html = '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';
	}

	if ( $args['show_option_none'] ) {
		$html .= '<option value="">' . esc_html( $args['show_option_none'] ) . '</option>';
	}

	if ( ! empty( $options ) ) {
		if ( $product && taxonomy_exists( $attribute ) ) {
			// Get terms if this is a taxonomy - ordered. We need the names too.
			$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );

			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $options ) ) {
					$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
				}
			}
		} else {
			foreach ( $options as $option ) {
				$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
				$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
			}
		}
	}

	$html .= '</select>';

	echo apply_filters( 'woocommerce_dropdown_variation_attribute_options_html', $html, $args );
}
