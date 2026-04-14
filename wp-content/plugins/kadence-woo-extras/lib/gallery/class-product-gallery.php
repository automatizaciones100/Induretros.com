<?php
/**
 * This overrides woocommerce.
 *
 * @package Kadence Woo Extras
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class to build out custom product gallery.
 *
 * @category class
 */
class Kadence_Shop_Kit_Product_Gallery {

	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Gallery settings.
	 *
	 * @var null
	 */
	private static $gallery_args = null;

	/**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Build Gallery.
	 */
	public function __construct() {
		add_filter( 'wc_get_template', array( $this, 'filter_in_product_gallery' ), 20, 5 );
		add_action( 'kadence_shopkit_woocommerce_product_gallery', array( $this, 'render_gallery' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 150 );
		add_action( 'init', array( $this, 'remove_theme_filter' ), 20 );
		add_filter( 'cmb2_admin_init', array( $this, 'product_gallery_metaboxes' ), 100 );
		add_filter( 'cmb2_textarea_attributes', array( $this, 'product_gallery_metaboxes_args' ), 10, 4 );
		add_filter( 'woocommerce_get_image_size_single', array( $this, 'product_gallery_sizes' ) );
		add_filter( 'woocommerce_gallery_thumbnail_size', array( $this, 'product_variation_sizes' ) );
		// Add the filter for editing the custom url field.
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_media_link_input' ), null, 2 );
		// Add the filter for saving the custom url field.
		add_filter( 'attachment_fields_to_save', array( $this, 'add_media_link_save' ), null, 2 );
		// Add in thumbnail srcset.
		add_filter( 'woocommerce_available_variation', array( $this, 'add_thumb_srcset' ), 10, 3 );
		// Disable woocommerce core.
		add_action( 'init', array( $this, 'remove_woocommerce_core_scripts' ), 20 );
	}
	/**
	 * Add new image meta video link input.
	 */
	public function remove_woocommerce_core_scripts() {
		add_filter( 'woocommerce_single_product_flexslider_enabled', '__return_false' );
		add_filter( 'woocommerce_single_product_photoswipe_enabled', '__return_false' );
		add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false' );
		remove_theme_support( 'wc-product-gallery-slider' );
		remove_theme_support( 'wc-product-gallery-lightbox' );
		remove_theme_support( 'wc-product-gallery-zoom' );
	}
	/**
	 * Add new image meta video link input.
	 */
	public function add_media_link_input( $form_fields, $post ) {
		$form_fields['kt_woo_product_video'] = array(
			'label' => __( 'Product Video URL (Youtube, Vimeo)', 'kadence-woo-extras' ),
			'helps' => __( 'Used in Product Gallery for Popup Video', 'kadence-woo-extras' ),
			'input' => 'text',
			'value' => get_post_meta( $post->ID, '_kt_woo_product_video', true ),
		);
		return $form_fields;
	}
	/**
	 * Add in thumbnail srcset.
	 */
	public function add_thumb_srcset( $args, $variable_class, $variation ) {
		if ( isset( $args['image']['gallery_thumbnail_src'] ) && isset( $args['image']['gallery_thumbnail_src_w'] ) && isset( $args['image']['gallery_thumbnail_src_h'] ) ) {
			if ( ! is_null( self::$gallery_args ) ) {
				$temp_args = self::$gallery_args;
				$args['image']['gallery_thumbnail_src_w'] = $temp_args['thumb_img_width'];
				$args['image']['gallery_thumbnail_src_h'] = $temp_args['thumb_img_height'];
				$temp_thumb = kt_woo_get_image_array( $temp_args['thumb_img_width'], $temp_args['thumb_img_height'], true, 'attachment-shop-single', '', $args['image_id'], false, 'thumbnail' );
				$args['image']['gallery_thumbnail_src'] = $temp_thumb['src'];
				$args['image']['gallery_thumbnail_srcset'] = $temp_thumb['src_set'];
			} else {
				$gallery_thumbnail_size = array( $args['image']['gallery_thumbnail_src_w'], $args['image']['gallery_thumbnail_src_h'] );
				$args['image']['gallery_thumbnail_srcset'] = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $args['image_id'], $gallery_thumbnail_size ) : false;
			}
		}
		return $args;
	}
	/**
	 * Save new image meta video link input.
	 */
	public function add_media_link_save( $post, $attachment ) {

		if ( isset( $attachment['kt_woo_product_video'] ) ) {
			update_post_meta( $post['ID'], '_kt_woo_product_video', esc_url_raw( $attachment['kt_woo_product_video'] ) );
		}
		return $post;
	}
	/**
	 * Override the Product image template.
	 *
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments. (default: array).
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 */
	public function filter_in_product_gallery( $template, $template_name, $args, $template_path, $default_path ) {
		if ( 'single-product/product-image.php' != $template_name ) {
			return $template;
		}

		return KADENCE_WOO_EXTRAS_PATH . 'lib/gallery/product-image.php';
	}
	/**
	 * This maintains backwards compatibility.
	 */
	public function kt_woo_product_gallery() {
		require_once KADENCE_WOO_EXTRAS_PATH . 'lib/gallery/product-image.php';
	}
	/**
	 * Checks if Theme is Classic.
	 */
	public function theme_is_a_kadence_classic() {
		if ( class_exists( 'Kadence_API_Manager' ) ) {
			return true;
		}
		return false;
	}
	/**
	 * Add in the gallery scripts and styles.
	 */
	public function enqueue_scripts() {
		global $post;

		if ( is_singular( 'product' ) || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) {
			$shopkit_settings = get_option( 'kt_woo_extras' );
			if ( ! is_array( $shopkit_settings ) ) {
				$shopkit_settings = json_decode( $shopkit_settings, true );
			}
			$has_zoom     = ( ! empty( $shopkit_settings['ga_zoom'] ) ? $shopkit_settings['ga_zoom'] : false );
			$has_lightbox = ( isset( $shopkit_settings['product_gallery_lightbox'] ) && false == $shopkit_settings['product_gallery_lightbox'] ? false : true );
			$lightbox_style = ( isset( $shopkit_settings['ga_lightbox_skin'] ) && 'light' === $shopkit_settings['ga_lightbox_skin'] ? 'kadence-light' : 'kadence-dark' );
			$depend       = array( 'kadence-update-splide', 'jquery' );
			if ( $has_zoom ) {
				$depend[] = 'kadence-product-gallery-zoom';
			}
			if ( $has_lightbox ) {
				$depend[] = 'kadence-glightbox';
			}
			wp_enqueue_style( 'kadence-product-gallery', KADENCE_WOO_EXTRAS_URL . 'lib/gallery/css/kadence-product-gallery.css', false, KADENCE_WOO_EXTRAS_VERSION );
			wp_enqueue_style( 'kadence-kb-splide', KADENCE_WOO_EXTRAS_URL . 'lib/gallery/css/kadence-splide.css', false, KADENCE_WOO_EXTRAS_VERSION );
			wp_register_script( 'kadence-product-gallery-zoom', KADENCE_WOO_EXTRAS_URL . 'lib/gallery/js/min/jquery.elevateZoom.min.js', array( 'jquery' ), KADENCE_WOO_EXTRAS_VERSION, true );
			wp_enqueue_script( 'kadence-update-splide', KADENCE_WOO_EXTRAS_URL . 'lib/gallery/js/splide.min.js', array(), KADENCE_WOO_EXTRAS_VERSION, true );

			// Lightbox.
			wp_register_script( 'kadence-glightbox', KADENCE_WOO_EXTRAS_URL . 'inc/assets/js/glightbox.min.js', array(), KADENCE_WOO_EXTRAS_VERSION, true );
			if ( $has_lightbox ) {
				wp_enqueue_style( 'kadence-glightbox', KADENCE_WOO_EXTRAS_URL . 'inc/assets/css/glightbox.min.css', false, KADENCE_WOO_EXTRAS_VERSION );
			}
			wp_enqueue_script( 'kadence_product_gallery', KADENCE_WOO_EXTRAS_URL . 'lib/gallery/js/kadence-product-gallery.js', $depend, KADENCE_WOO_EXTRAS_VERSION, true );
			$gallery_translation_array = array(
				'plyr_js'          => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/js/min/plyr.js',
				'plyr_css'         => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/css/plyr.css',
				'ajax_nonce'       => wp_create_nonce( 'kwsv' ),
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'lightbox'         => $has_lightbox,
				'lightbox_style'   => $lightbox_style,
			);
			wp_localize_script( 'kadence_product_gallery', 'kadence_pg', $gallery_translation_array );
		}
	}
	/**
	 * Remove custom theme filter effecting controls..
	 */
	public function remove_theme_filter() {
		remove_filter( 'woocommerce_get_image_size_single', 'virtue_woo_product_gallery_sizes' );
	}
	/**
	 * Filter Product Image sizes for Woocommerce
	 *
	 * @param array $size the gallery sizes.
	 */
	public function product_gallery_sizes( $size ) {
		$temp_args = array();
		if ( is_null( self::$gallery_args ) ) {
			$shopkit_settings = get_option( 'kt_woo_extras' );
			if ( ! is_array( $shopkit_settings ) ) {
				$shopkit_settings = json_decode( $shopkit_settings, true );
			}
			$temp_args['is_custom'] = ( isset( $shopkit_settings['product_gallery_custom_size'] ) && true == $shopkit_settings['product_gallery_custom_size'] ? true : false );
			if ( $temp_args['is_custom'] ) {
				$temp_args['custom_width']  = ( ! empty( $shopkit_settings['ga_image_width'] ) ? $shopkit_settings['ga_image_width'] : 465 );
				$temp_args['ratio'] = ( ! empty( $shopkit_settings['ga_image_ratio'] ) ? $shopkit_settings['ga_image_ratio'] : 'square' );
				$temp_args['layout'] = ( ! empty( $shopkit_settings['ga_slider_layout'] ) ? $shopkit_settings['ga_slider_layout'] : 'above' );
				$temp_args['vlayout'] = ( 'left' === $temp_args['layout'] || 'right' === $temp_args['layout'] ? true : false );
				$temp_args['thumb_columns'] = ( ! empty( $shopkit_settings['ga_thumb_columns'] ) ? $shopkit_settings['ga_thumb_columns'] : 7 );
				$temp_args['thumb_ratio']   = ( ! empty( $shopkit_settings['ga_thumb_image_ratio'] ) ? $shopkit_settings['ga_thumb_image_ratio'] : 'square' );
				$temp_args['width'] = $temp_args['custom_width'];
				if ( 'custom' === $temp_args['ratio'] ) {
					if ( ! empty( $shopkit_settings['ga_image_height'] ) ) {
						$temp_args['height'] = $shopkit_settings['ga_image_height'];
					} elseif ( empty( $temp_args['height'] ) ) {
						$temp_args['height'] = 465;
					}
				} elseif ( 'portrait' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] * 1.35 );
				} elseif ( 'landscape' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] / 1.35 );
				} elseif ( 'landscape32' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] / 1.5 );
				} elseif ( 'landscape169' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] * 0.5625 );
				} elseif ( 'widelandscape' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] / 2 );
				} else {
					$temp_args['height'] = $temp_args['width'];
				}
			}
		} else {
			$temp_args = self::$gallery_args;
		}
		if ( $temp_args['is_custom'] && isset( $temp_args['width'] ) && isset( $temp_args['height'] ) ) {
			$size = array(
				'width'  => $temp_args['width'],
				'height' => $temp_args['height'],
				'crop'   => 1,
			);
		}

		return $size;
	}
	/**
	 * Filter Product Image sizes for Woocommerce
	 *
	 * @param array $sizes the gallery sizes.
	 */
	public function product_variation_sizes( $sizes ) {
		$temp_args = array();
		if ( is_null( self::$gallery_args ) ) {
			$shopkit_settings = get_option( 'kt_woo_extras' );
			if ( ! is_array( $shopkit_settings ) ) {
				$shopkit_settings = json_decode( $shopkit_settings, true );
			}
			$temp_args['is_custom'] = ( isset( $shopkit_settings['product_gallery_custom_size'] ) && true == $shopkit_settings['product_gallery_custom_size'] ? true : false );
			if ( $temp_args['is_custom'] ) {
				$temp_args['custom_width']  = ( ! empty( $shopkit_settings['ga_image_width'] ) ? $shopkit_settings['ga_image_width'] : 465 );
				$temp_args['ratio'] = ( ! empty( $shopkit_settings['ga_image_ratio'] ) ? $shopkit_settings['ga_image_ratio'] : 'square' );
				$temp_args['layout'] = ( ! empty( $shopkit_settings['ga_slider_layout'] ) ? $shopkit_settings['ga_slider_layout'] : 'above' );
				$temp_args['vlayout'] = ( 'left' === $temp_args['layout'] || 'right' === $temp_args['layout'] ? true : false );
				$temp_args['thumb_columns'] = ( ! empty( $shopkit_settings['ga_thumb_columns'] ) ? $shopkit_settings['ga_thumb_columns'] : 7 );
				$temp_args['thumb_ratio']   = ( ! empty( $shopkit_settings['ga_thumb_image_ratio'] ) ? $shopkit_settings['ga_thumb_image_ratio'] : 'square' );
				$temp_args['width'] = $temp_args['custom_width'];
				if ( 'custom' === $temp_args['ratio'] ) {
					if ( ! empty( $shopkit_settings['ga_image_height'] ) ) {
						$temp_args['height'] = $shopkit_settings['ga_image_height'];
					} elseif ( empty( $temp_args['height'] ) ) {
						$temp_args['height'] = 465;
					}
				} elseif ( 'portrait' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] * 1.35 );
				} elseif ( 'landscape' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] / 1.35 );
				} elseif ( 'landscape32' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] / 1.5 );
				} elseif ( 'landscape169' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] * 0.5625 );
				} elseif ( 'widelandscape' === $temp_args['ratio'] ) {
					$temp_args['height'] = floor( $temp_args['width'] / 2 );
				} else {
					$temp_args['height'] = $temp_args['width'];
				}
				if ( $temp_args['vlayout'] ) {
					$temp_args['thumb_img_height'] = floor( $temp_args['height'] / $temp_args['thumb_columns'] ) - 2;
					if ( 'portrait' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] / 1.35 );
					} elseif ( 'landscape' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] * 1.35 );
					} elseif ( 'landscape32' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] * 1.5 );
					} elseif ( 'landscape169' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] * 1.78 );
					} elseif ( 'widelandscape' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] * 2 );
					} elseif ( 'inherit' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = 120;
						$temp_args['thumb_img_height'] = null;
					} else {
						$temp_args['thumb_img_width'] = $temp_args['thumb_img_height'];
					}
				} else {
					$temp_args['thumb_img_width'] = floor( $temp_args['width'] / $temp_args['thumb_columns'] );
					if ( 'portrait' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] * 1.35 );
					} elseif ( 'landscape' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] / 1.35 );
					} elseif ( 'landscape32' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] / 1.5 );
					} elseif ( 'landscape169' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] * 0.5625 );
					} elseif ( 'inherit' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = null;
					} elseif ( 'widelandscape' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] / 2 );
					} else {
						$temp_args['thumb_img_height'] = $temp_args['thumb_img_width'];
					}
				}
			} else {
				$single_size = wc_get_image_size( 'single' );
				$temp_args['ratio'] = ( ! empty( $shopkit_settings['ga_image_ratio'] ) ? $shopkit_settings['ga_image_ratio'] : 'square' );
				$temp_args['layout'] = ( ! empty( $shopkit_settings['ga_slider_layout'] ) ? $shopkit_settings['ga_slider_layout'] : 'above' );
				$temp_args['vlayout'] = ( 'left' === $temp_args['layout'] || 'right' === $temp_args['layout'] ? true : false );
				$temp_args['thumb_columns'] = ( ! empty( $shopkit_settings['ga_thumb_columns'] ) ? $shopkit_settings['ga_thumb_columns'] : 7 );
				$temp_args['thumb_ratio']   = ( ! empty( $shopkit_settings['ga_thumb_image_ratio'] ) ? $shopkit_settings['ga_thumb_image_ratio'] : 'square' );
				if ( $temp_args['vlayout'] ) {
					$temp_args['height'] = ( ! empty( $single_size['height'] ) ? $single_size['height'] : 600 );
					$temp_args['thumb_img_height'] = floor( $temp_args['height'] / $temp_args['thumb_columns'] ) - 2;
					if ( 'portrait' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] / 1.35 );
					} elseif ( 'landscape' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] * 1.35 );
					} elseif ( 'landscape32' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] * 1.5 );
					} elseif ( 'landscape169' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] * 1.78 );
					} elseif ( 'widelandscape' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = floor( $temp_args['thumb_img_height'] * 2 );
					} elseif ( 'inherit' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_width'] = 120;
						$temp_args['thumb_img_height'] = null;
					} else {
						$temp_args['thumb_img_width'] = $temp_args['thumb_img_height'];
					}
				} else {
					$temp_args['width'] = ( ! empty( $single_size['width'] ) ? $single_size['width'] : 600 );
					$temp_args['thumb_img_width'] = floor( $temp_args['width'] / $temp_args['thumb_columns'] );
					if ( 'portrait' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] * 1.35 );
					} elseif ( 'landscape' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] / 1.35 );
					} elseif ( 'landscape32' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] / 1.5 );
					} elseif ( 'landscape169' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] * 0.5625 );
					} elseif ( 'inherit' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = null;
					} elseif ( 'widelandscape' === $temp_args['thumb_ratio'] ) {
						$temp_args['thumb_img_height'] = floor( $temp_args['thumb_img_width'] / 2 );
					} else {
						$temp_args['thumb_img_height'] = $temp_args['thumb_img_width'];
					}
				}
			}
		} else {
			$temp_args = self::$gallery_args;
		}
		if ( ! empty( $temp_args['thumb_img_width'] ) && ! empty( $temp_args['thumb_img_height'] ) ) {
			$sizes = array( $temp_args['thumb_img_width'], $temp_args['thumb_img_height'] );
		}

		return $sizes;
	}
	/**
	 * Add args to metabox settings.
	 */
	public function product_gallery_metaboxes_args( $args, $type_defaults, $field, $types ) {
		if ( '_kt_woo_gallery_shortcode' === $field->args['id'] ) {
			$args['rows'] = 2;
		}
		return $args;
	}
	/**
	 * Add in gallery options for a shortcode override.
	 */
	public function product_gallery_metaboxes() {
		$prefix = '_kt_woo_';
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		if ( isset( $shopkit_settings['ga_shortcode_option'] ) && true == $shopkit_settings['ga_shortcode_option'] ) {
			$kadence_product_gallery = new_cmb2_box(
				array(
					'id'            => $prefix . 'gallery_override',
					'title'         => __( 'Override product Gallery', 'kadence-woo-extras' ),
					'object_types'  => array( 'product' ), // Post type.
					'priority'      => 'low',
					'context'       => 'side',
				)
			);
			$kadence_product_gallery->add_field(
				array(
					'name'          => __( 'Replace Gallery with Shortcode', 'kadence-woo-extras' ),
					'id'            => $prefix . 'gallery_shortcode',
					'type'          => 'textarea_code',
					'rows' => 4,
					'options' => array(
						'textarea_rows'      => 2,
						'disable_codemirror' => true,
					),
				)
			);
		}
	}
	/**
	 * Determine how to render the gallery.
	 */
	public function render_gallery( $args = array() ) {
		global $product;

		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		$shortcode = get_post_meta( get_the_ID(), '_kt_woo_gallery_shortcode', true );
		if ( isset( $shortcode ) && ! empty( $shortcode ) && isset( $shopkit_settings['ga_shortcode_option'] ) && true == $shopkit_settings['ga_shortcode_option'] ) {
			echo '<div class="images woocommerce-product-gallery kad-light-gallery kt-shortcode-gallery"><div class="product_image">';
			echo apply_filters( 'do_shortcode', $shortcode );
			echo '</div></div>';
		} elseif ( ! has_post_thumbnail() ) {
			$wrapper_classes = apply_filters(
				'woocommerce_single_product_image_gallery_classes',
				array(
					'woocommerce-product-gallery',
					'woocommerce-product-gallery--without-images',
					'images',
				)
			);
			echo '<div class="kad-light-gallery ' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ) . '"><div class="product_image">';
			$inner_wrapper_classname = $product->is_type( 'variable' ) && ! empty( $product->get_available_variations( 'image' ) ) ?
				'woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder' :
				'woocommerce-product-gallery__image--placeholder';
			$html = sprintf( '<div class="%s">', esc_attr( $inner_wrapper_classname ) );
			$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
			$html .= '</div>';
			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, null ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
			echo '</div></div>';
		} else {
			$this->output_gallery( $args );
		}
	}
	/**
	 * Get Gallery args.
	 */
	public function get_gallery_args( $args = array() ) {
		if ( is_null( self::$gallery_args ) ) {
			$shopkit_settings = get_option( 'kt_woo_extras' );
			if ( ! is_array( $shopkit_settings ) ) {
				$shopkit_settings = json_decode( $shopkit_settings, true );
			}
			$defaults = array(
				'custom_width'  => ( ! empty( $shopkit_settings['ga_image_width'] ) ? $shopkit_settings['ga_image_width'] : 465 ),
				'ratio'         => ( ! empty( $shopkit_settings['ga_image_ratio'] ) ? $shopkit_settings['ga_image_ratio'] : 'square' ),
				'layout'        => ( ! empty( $shopkit_settings['ga_slider_layout'] ) ? $shopkit_settings['ga_slider_layout'] : 'above' ),
				'thumb_width'   => ( ! empty( $shopkit_settings['ga_thumb_width'] ) ? $shopkit_settings['ga_thumb_width'] : '20' ),
				'is_custom'     => ( isset( $shopkit_settings['product_gallery_custom_size'] ) && true == $shopkit_settings['product_gallery_custom_size'] ? true : false ),
				'transtype'     => ( ! empty( $shopkit_settings['ga_trans_type'] ) && 'true' === $shopkit_settings['ga_trans_type'] ? true : false ),
				'show_caption'  => ( ! empty( $shopkit_settings['ga_show_caption'] ) && 'true' === $shopkit_settings['ga_show_caption'] ? true : false ),
				'autoplay'      => ( ! empty( $shopkit_settings['ga_slider_autoplay'] ) && 'true' === $shopkit_settings['ga_slider_autoplay'] ? true : false ),
				'auto_height'   => ( ! empty( $shopkit_settings['ga_slider_auto_height'] ) && true === $shopkit_settings['ga_slider_auto_height'] ? true : false ),
				'pausetime'     => ( ! empty( $shopkit_settings['ga_slider_pausetime'] ) ? $shopkit_settings['ga_slider_pausetime'] : '7000' ),
				'transtime'     => ( ! empty( $shopkit_settings['ga_slider_transtime'] ) ? $shopkit_settings['ga_slider_transtime'] : '400' ),
				'zoomactive'    => ( ! empty( $shopkit_settings['ga_zoom'] ) ? $shopkit_settings['ga_zoom'] : false ),
				'zoomtype'      => ( ! empty( $shopkit_settings['ga_zoom_type'] ) ? $shopkit_settings['ga_zoom_type'] : 'window' ),
				'thumb_columns' => ( ! empty( $shopkit_settings['ga_thumb_columns'] ) ? $shopkit_settings['ga_thumb_columns'] : 7 ),
				'thumb_ratio'   => ( ! empty( $shopkit_settings['ga_thumb_image_ratio'] ) ? $shopkit_settings['ga_thumb_image_ratio'] : 'square' ),
				'arrows'        => ( ! empty( $shopkit_settings['ga_slider_arrows'] ) && 'true' === $shopkit_settings['ga_slider_arrows'] ? true : false ),
				'lightbox'      => ( isset( $shopkit_settings['product_gallery_lightbox'] ) && false == $shopkit_settings['product_gallery_lightbox'] ? false : true ),
				'thumb_hover'   => ( isset( $shopkit_settings['ga_thumb_hover'] ) && true == $shopkit_settings['ga_thumb_hover'] ? true : false ),
				'thumb_gap'     => 4,
			);
			// Responsive Defaults.
			$defaults['layout_tablet'] = ( ! empty( $shopkit_settings['ga_slider_layout_tablet'] ) ? $shopkit_settings['ga_slider_layout_tablet'] : $defaults['layout'] );
			$defaults['layout_mobile'] = ( ! empty( $shopkit_settings['ga_slider_layout_mobile'] ) ? $shopkit_settings['ga_slider_layout_mobile'] : $defaults['layout_tablet'] );
			$defaults['thumb_width_tablet'] = ( ! empty( $shopkit_settings['ga_thumb_width_tablet'] ) ? $shopkit_settings['ga_thumb_width_tablet'] : $defaults['thumb_width'] );
			$defaults['thumb_width_mobile'] = ( ! empty( $shopkit_settings['ga_thumb_width_mobile'] ) ? $shopkit_settings['ga_thumb_width_mobile'] : $defaults['thumb_width_tablet'] );
			$defaults['thumb_columns_tablet'] = ( ! empty( $shopkit_settings['ga_thumb_columns_tablet'] ) ? $shopkit_settings['ga_thumb_columns_tablet'] : $defaults['thumb_columns'] );
			$defaults['thumb_columns_mobile'] = ( ! empty( $shopkit_settings['ga_thumb_columns_mobile'] ) ? $shopkit_settings['ga_thumb_columns_mobile'] : $defaults['thumb_columns_tablet'] );
			$defaults['thumb_gap_tablet'] = $defaults['thumb_gap'];
			$defaults['thumb_gap_mobile'] = $defaults['thumb_gap'];
			// Generate Final Size Settings.
			$gallery_args = wp_parse_args( $args, $defaults );
			if ( $gallery_args['is_custom'] ) {
				$gallery_args['width'] = $gallery_args['custom_width'];
				if ( 'custom' === $gallery_args['ratio'] ) {
					if ( ! empty( $gallery_args['custom_height'] ) ) {
						$gallery_args['height'] = $gallery_args['custom_height'];
					} elseif ( ! empty( $shopkit_settings['ga_image_height'] ) ) {
						$gallery_args['height'] = $shopkit_settings['ga_image_height'];
					} elseif ( empty( $gallery_args['height'] ) ) {
						$gallery_args['height'] = 465;
					}
				} elseif ( 'portrait' === $gallery_args['ratio'] ) {
					$gallery_args['height'] = floor( $gallery_args['width'] * 1.35 );
				} elseif ( 'landscape' === $gallery_args['ratio'] ) {
					$gallery_args['height'] = floor( $gallery_args['width'] / 1.35 );
				} elseif ( 'landscape32' === $gallery_args['ratio'] ) {
					$gallery_args['height'] = floor( $gallery_args['width'] / 1.5 );
				} elseif ( 'landscape169' === $gallery_args['ratio'] ) {
					$gallery_args['height'] = floor( $gallery_args['width'] * 0.5625 );
				} elseif ( 'widelandscape' === $gallery_args['ratio'] ) {
					$gallery_args['height'] = floor( $gallery_args['width'] / 2 );
				} else {
					$gallery_args['height'] = $gallery_args['width'];
				}
			} else {
				$woo_image_size            = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
				if ( has_post_thumbnail() ) {
					$woo_img                = wp_get_attachment_image_src( get_post_thumbnail_id(), $woo_image_size );
					$gallery_args['width']  = ( ! empty( $woo_img[1] ) ? $woo_img[1] : '600' );
					$gallery_args['height'] = ( ! empty( $woo_img[2] ) ? $woo_img[2] : '600' );
				} else {
					$gallery_args['width']  = '600';
					$gallery_args['height'] = '600';
				}
			}

			$gallery_args['vlayout'] = ( 'left' === $gallery_args['layout'] || 'right' === $gallery_args['layout'] ? true : false );

			if ( $gallery_args['vlayout'] ) {
				$gallery_args['thumb_img_height'] = floor( $gallery_args['height'] / $gallery_args['thumb_columns'] ) - 2;
				if ( 'portrait' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] / 1.35 );
				} elseif ( 'landscape' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] * 1.35 );
				} elseif ( 'landscape32' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] * 1.5 );
				} elseif ( 'landscape169' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] * 1.78 );
				} elseif ( 'widelandscape' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] * 2 );
				} elseif ( 'inherit' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_width'] = 120;
					$gallery_args['thumb_img_height'] = null;
				} else {
					$gallery_args['thumb_img_width'] = $gallery_args['thumb_img_height'];
				}
			} else {
				$gallery_args['thumb_img_width'] = floor( $gallery_args['width'] / $gallery_args['thumb_columns'] );
				if ( 'portrait' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] * 1.35 );
				} elseif ( 'landscape' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] / 1.35 );
				} elseif ( 'landscape32' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] / 1.5 );
				} elseif ( 'landscape169' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] * 0.5625 );
				} elseif ( 'inherit' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_height'] = null;
				} elseif ( 'widelandscape' === $gallery_args['thumb_ratio'] ) {
					$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] / 2 );
				} else {
					$gallery_args['thumb_img_height'] = $gallery_args['thumb_img_width'];
				}
			}
			if ( 'left' === $gallery_args['layout'] ) {
				$gallery_args['margin']      = 'margin-left:' . esc_attr( $gallery_args['thumb_width'] ) . '%;';
				$gallery_args['thumb_style'] = 'width:' . esc_attr( $gallery_args['thumb_width'] ) . '%;';
			} elseif ( 'right' === $gallery_args['layout'] ) {
				$gallery_args['margin']      = 'margin-right:' . esc_attr( $gallery_args['thumb_width'] ) . '%;';
				$gallery_args['thumb_style'] = 'width:' . esc_attr( $gallery_args['thumb_width'] ) . '%;';
			} else {
				$gallery_args['margin']      = '';
				$gallery_args['thumb_style'] = 'width:auto;';
			}
			if ( $gallery_args['zoomactive'] ) {
				$gallery_args['arrows'] = false;
			}
			self::$gallery_args = $gallery_args;
		}
		return self::$gallery_args;
	}
	/**
	 * Get Render Gallery args.
	 */
	public function get_render_gallery_args( $args = array() ) {
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		$defaults = array(
			'custom_width'  => ( ! empty( $shopkit_settings['ga_image_width'] ) ? $shopkit_settings['ga_image_width'] : 465 ),
			'ratio'         => ( ! empty( $shopkit_settings['ga_image_ratio'] ) ? $shopkit_settings['ga_image_ratio'] : 'square' ),
			'layout'        => ( ! empty( $shopkit_settings['ga_slider_layout'] ) ? $shopkit_settings['ga_slider_layout'] : 'above' ),
			'thumb_width'   => ( ! empty( $shopkit_settings['ga_thumb_width'] ) ? $shopkit_settings['ga_thumb_width'] : '20' ),
			'is_custom'     => ( isset( $shopkit_settings['product_gallery_custom_size'] ) && true == $shopkit_settings['product_gallery_custom_size'] ? true : false ),
			'transtype'     => ( ! empty( $shopkit_settings['ga_trans_type'] ) && 'true' === $shopkit_settings['ga_trans_type'] ? true : false ),
			'show_caption'  => ( ! empty( $shopkit_settings['ga_show_caption'] ) && 'true' === $shopkit_settings['ga_show_caption'] ? true : false ),
			'autoplay'      => ( ! empty( $shopkit_settings['ga_slider_autoplay'] ) && 'true' === $shopkit_settings['ga_slider_autoplay'] ? true : false ),
			'auto_height'   => ( ! empty( $shopkit_settings['ga_slider_auto_height'] ) && true === $shopkit_settings['ga_slider_auto_height'] ? true : false ),
			'pausetime'     => ( ! empty( $shopkit_settings['ga_slider_pausetime'] ) ? $shopkit_settings['ga_slider_pausetime'] : '7000' ),
			'transtime'     => ( ! empty( $shopkit_settings['ga_slider_transtime'] ) ? $shopkit_settings['ga_slider_transtime'] : '400' ),
			'zoomactive'    => ( ! empty( $shopkit_settings['ga_zoom'] ) ? $shopkit_settings['ga_zoom'] : false ),
			'zoomtype'      => ( ! empty( $shopkit_settings['ga_zoom_type'] ) ? $shopkit_settings['ga_zoom_type'] : 'window' ),
			'thumb_columns' => ( ! empty( $shopkit_settings['ga_thumb_columns'] ) ? $shopkit_settings['ga_thumb_columns'] : 7 ),
			'thumb_ratio'   => ( ! empty( $shopkit_settings['ga_thumb_image_ratio'] ) ? $shopkit_settings['ga_thumb_image_ratio'] : 'square' ),
			'arrows'        => ( ! empty( $shopkit_settings['ga_slider_arrows'] ) && 'true' === $shopkit_settings['ga_slider_arrows'] ? true : false ),
			'pagination'    => ( isset( $shopkit_settings['ga_slider_pagination'] ) ? ( 'true' === $shopkit_settings['ga_slider_pagination'] ? true : false ) : true ),
			'lightbox'      => ( isset( $shopkit_settings['product_gallery_lightbox'] ) && false == $shopkit_settings['product_gallery_lightbox'] ? false : true ),
			'thumb_hover'   => ( isset( $shopkit_settings['ga_thumb_hover'] ) && true == $shopkit_settings['ga_thumb_hover'] ? true : false ),
			'thumb_gap'     => 4,
		);
		// Responsive Defaults.
		$defaults['layout_tablet'] = ( ! empty( $shopkit_settings['ga_slider_layout_tablet'] ) ? $shopkit_settings['ga_slider_layout_tablet'] : $defaults['layout'] );
		$defaults['layout_mobile'] = ( ! empty( $shopkit_settings['ga_slider_layout_mobile'] ) ? $shopkit_settings['ga_slider_layout_mobile'] : $defaults['layout_tablet'] );
		$defaults['thumb_width_tablet'] = ( ! empty( $shopkit_settings['ga_thumb_width_tablet'] ) ? $shopkit_settings['ga_thumb_width_tablet'] : $defaults['thumb_width'] );
		$defaults['thumb_width_mobile'] = ( ! empty( $shopkit_settings['ga_thumb_width_mobile'] ) ? $shopkit_settings['ga_thumb_width_mobile'] : $defaults['thumb_width_tablet'] );
		$defaults['thumb_columns_tablet'] = ( ! empty( $shopkit_settings['ga_thumb_columns_tablet'] ) ? $shopkit_settings['ga_thumb_columns_tablet'] : $defaults['thumb_columns'] );
		$defaults['thumb_columns_mobile'] = ( ! empty( $shopkit_settings['ga_thumb_columns_mobile'] ) ? $shopkit_settings['ga_thumb_columns_mobile'] : $defaults['thumb_columns_tablet'] );
		$defaults['thumb_gap_tablet'] = $defaults['thumb_gap'];
		$defaults['thumb_gap_mobile'] = $defaults['thumb_gap'];
		// Generate Final Size Settings.
		$gallery_args = wp_parse_args( $args, $defaults );
		if ( $gallery_args['is_custom'] ) {
			$gallery_args['width'] = $gallery_args['custom_width'];
			if ( 'custom' === $gallery_args['ratio'] ) {
				if ( ! empty( $gallery_args['custom_height'] ) ) {
					$gallery_args['height'] = $gallery_args['custom_height'];
				} elseif ( ! empty( $shopkit_settings['ga_image_height'] ) ) {
					$gallery_args['height'] = $shopkit_settings['ga_image_height'];
				} elseif ( empty( $gallery_args['height'] ) ) {
					$gallery_args['height'] = 465;
				}
			} elseif ( 'portrait' === $gallery_args['ratio'] ) {
				$gallery_args['height'] = floor( $gallery_args['width'] * 1.35 );
			} elseif ( 'landscape' === $gallery_args['ratio'] ) {
				$gallery_args['height'] = floor( $gallery_args['width'] / 1.35 );
			} elseif ( 'landscape32' === $gallery_args['ratio'] ) {
				$gallery_args['height'] = floor( $gallery_args['width'] / 1.5 );
			} elseif ( 'landscape169' === $gallery_args['ratio'] ) {
				$gallery_args['height'] = floor( $gallery_args['width'] * 0.5625 );
			} elseif ( 'widelandscape' === $gallery_args['ratio'] ) {
				$gallery_args['height'] = floor( $gallery_args['width'] / 2 );
			} else {
				$gallery_args['height'] = $gallery_args['width'];
			}
		} else {
			$woo_image_size            = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
			if ( has_post_thumbnail() ) {
				$woo_img                = wp_get_attachment_image_src( get_post_thumbnail_id(), $woo_image_size );
				$gallery_args['width']  = ( ! empty( $woo_img[1] ) ? $woo_img[1] : '600' );
				$gallery_args['height'] = ( ! empty( $woo_img[2] ) ? $woo_img[2] : '600' );
			} else {
				$gallery_args['width']  = '600';
				$gallery_args['height'] = '600';
			}
		}

		$gallery_args['vlayout'] = ( 'left' === $gallery_args['layout'] || 'right' === $gallery_args['layout'] ? true : false );

		if ( $gallery_args['vlayout'] ) {
			$gallery_args['thumb_img_height'] = floor( $gallery_args['height'] / $gallery_args['thumb_columns'] ) - 2;
			if ( 'portrait' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] / 1.35 );
			} elseif ( 'landscape' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] * 1.35 );
			} elseif ( 'landscape32' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] * 1.5 );
			} elseif ( 'landscape169' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] * 1.78 );
			} elseif ( 'widelandscape' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_width'] = floor( $gallery_args['thumb_img_height'] * 2 );
			} elseif ( 'inherit' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_width'] = 120;
				$gallery_args['thumb_img_height'] = null;
			} else {
				$gallery_args['thumb_img_width'] = $gallery_args['thumb_img_height'];
			}
		} else {
			$gallery_args['thumb_img_width'] = floor( $gallery_args['width'] / $gallery_args['thumb_columns'] );
			if ( 'portrait' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] * 1.35 );
			} elseif ( 'landscape' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] / 1.35 );
			} elseif ( 'landscape32' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] / 1.5 );
			} elseif ( 'landscape169' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] * 0.5625 );
			} elseif ( 'inherit' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_height'] = null;
			} elseif ( 'widelandscape' === $gallery_args['thumb_ratio'] ) {
				$gallery_args['thumb_img_height'] = floor( $gallery_args['thumb_img_width'] / 2 );
			} else {
				$gallery_args['thumb_img_height'] = $gallery_args['thumb_img_width'];
			}
		}
		if ( 'left' === $gallery_args['layout'] ) {
			$gallery_args['margin']      = 'margin-left:' . esc_attr( $gallery_args['thumb_width'] ) . '%;';
			$gallery_args['thumb_style'] = 'width:' . esc_attr( $gallery_args['thumb_width'] ) . '%;';
		} elseif ( 'right' === $gallery_args['layout'] ) {
			$gallery_args['margin']      = 'margin-right:' . esc_attr( $gallery_args['thumb_width'] ) . '%;';
			$gallery_args['thumb_style'] = 'width:' . esc_attr( $gallery_args['thumb_width'] ) . '%;';
		} else {
			$gallery_args['margin']      = '';
			$gallery_args['thumb_style'] = 'width:auto;';
		}
		if ( $gallery_args['zoomactive'] ) {
			$gallery_args['arrows'] = false;
		}
		self::$gallery_args = $gallery_args;
		return $gallery_args;
	}
	/**
	 * Output the Gallery.
	 */
	public function output_gallery( $args = array() ) {
		global $product;
		$args = $this->get_render_gallery_args( $args );
		if ( has_post_thumbnail() ) {
			$feature_id = array( get_post_thumbnail_id() );
			$attachment_ids = $product->get_gallery_image_ids();
			$images = array_merge( $feature_id, $attachment_ids );
			$count = count( $images );
			if ( empty( $attachment_ids ) ) {
				$args['margin']  = '';
				$args['layout']  = 'above';
				$args['layout_tablet']  = 'above';
				$args['layout_mobile']  = 'above';
				$args['vlayout'] = false;
			}
			$should_lazy = ( 'tiles' === $args['layout'] ? false : true );
			$lazy = apply_filters( 'kadence_shop_kit_gallery_lazy_load', $should_lazy );
			$is_slider = true;
			if ( 'list' === $args['layout'] || 'grid' === $args['layout'] || 'tiles' === $args['layout'] ) {
				$is_slider = false;
			}
			$args['thumb_load_columns'] = $args['thumb_columns'];
			$args['thumb_sm_load_columns'] = $args['thumb_columns_mobile'];
			$thumb_sm_max_width = '';
			$thumb_max_width = '';
			if ( $count > $args['thumb_columns'] ) {
				$thumb_class     = 'kt_thumb_show_arrow';
				$center          = 'true';
			} elseif ( $count === $args['thumb_columns'] ) {
				if ( $args['vlayout'] ) {
					$center          = 'false';
					$thumb_class     = 'kt_thumb_show_arrow';
				} else {
					$center          = 'false';
					$thumb_class     = 'kt_thumb_hide_arrow';
				}
			} else {
				if ( $args['vlayout'] ) {
					$center          = 'false';
					$thumb_class     = 'kt_thumb_show_arrow';
				} else {
					//$thumb_max_width = 'max-width:' . floor( ( 100 / $args['thumb_columns'] ) * $count ) . '%; margin:0 auto;';
					$thumb_max_width = floor( ( 100 / $args['thumb_columns'] ) * $count ) . '%';
					$center          = 'false';
					$args['thumb_load_columns']   = $count;
					$thumb_class     = 'kt_thumb_hide_arrow';
				}
			}
			if ( $count < $args['thumb_columns_mobile'] && ! $args['vlayout'] ) {
				$args['thumb_sm_load_columns'] = $count;
				$thumb_sm_max_width = floor( ( 100 / $args['thumb_columns_mobile'] ) * $count ) . '%';
			}
			$product_wrapper_width = ( $args['vlayout'] ? 'calc(' . $args['width'] . 'px + ' . $args['thumb_width'] . '% )' : $args['width'] . 'px;' );
			if ( 'grid' === $args['layout'] || 'tiles' === $args['layout'] ) {
				$product_wrapper_width = 'calc(' . $args['width'] . 'px * 2 );';
			}
			$css = new Kadence_Woo_CSS();
			if ( 'above' === $args['layout'] ) {
				$css->set_selector( '#pg-thumbnails-' . get_the_ID() . ' .thumb-wrapper' );
				$css->add_property( 'max-width', $thumb_max_width );
			}
			if ( 'above' === $args['layout_mobile'] ) {
				$css->start_media_query( '(max-width: 767px)' );
				$css->set_selector( '#pg-thumbnails-' . get_the_ID() . ' .thumb-wrapper' );
				$css->add_property( 'max-width', $thumb_sm_max_width );
				$css->stop_media_query();
			}

			//fade transition can make some slides invisible, this is not neccessary for these layouts.
			$css->set_selector( '#pg-main-' . get_the_ID() . '.kb-splide.splide--fade > .splide__slider > .splide__track > .splide__list > .splide__slide, #pg-main-' . get_the_ID() . '.kb-splide.splide--fade > .splide__track > .splide__list > .splide__slide' );
			if ( 'list' === $args['layout'] || 'grid' === $args['layout'] || 'tiles' === $args['layout'] ) {
				$css->add_property( 'opacity', 1 );
			}
			if ( 'list' === $args['layout_tablet'] || 'grid' === $args['layout_tablet'] || 'tiles' === $args['layout_tablet'] ) {
				$css->start_media_query( '(max-width: 1024px)' );
				$css->add_property( 'opacity', 1 );
				$css->stop_media_query();
			}
			if ( 'list' === $args['layout_mobile'] || 'grid' === $args['layout_mobile'] || 'tiles' === $args['layout_mobile'] ) {
				$css->start_media_query( '(max-width: 767px)' );
				$css->add_property( 'opacity', 1 );
				$css->stop_media_query();
			}
			$styles = $css->css_output();
			$wrapper_classes   = apply_filters(
				'woocommerce_single_product_image_gallery_classes',
				array(
					'woocommerce-product-gallery',
					'woocommerce-product-gallery--with-images',
					'images',
				)
			);
			if ( ! empty( $styles ) ) {
				echo '<style>' . $styles . '</style>';
			}
			echo '<div class="ksk-gallery kad-light-gallery kt-layout-' . esc_attr( $args['layout'] ) . ' kt-md-layout-' . esc_attr( $args['layout_tablet'] ) . ' kt-sm-layout-' . esc_attr( $args['layout_mobile'] ) . ' ' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ) . '">';
			echo '<div class="product_image" style="max-width:' . esc_attr( $product_wrapper_width ) . '">';
			echo '<div id="pg-main-' . esc_attr( get_the_ID() ) . '" class="kadence-product-gallery-main kadence-ga-splide-init kb-splide splide kt-carousel-arrowstyle-blackonlight" style="' . esc_attr( $args['margin'] ) . '" data-speed="' . esc_attr( $args['pausetime'] ) . '" data-animation-speed="' . esc_attr( $args['transtime'] ) . '" data-product-id="' . esc_attr( get_the_ID() ) . '" data-vlayout="' . esc_attr( $args['vlayout'] ? 'true' : 'false' ) . '"  data-animation="' . esc_attr( $args['transtype'] ? 'true' : 'false' ) . '" data-auto="' . esc_attr( $args['autoplay'] ? 'true' : 'false' ) . '" data-auto-height="' . esc_attr( $args['auto_height'] ? 'true' : 'false' ) . '" data-arrows="' . esc_attr( $args['arrows'] ? 'true' : 'false' ) . '" data-pagination="' . esc_attr( $args['pagination'] ? 'true' : 'false' ) . '" data-gallery-items="' . esc_attr( $count ) . '" data-zoom-type="' . esc_attr( $args['zoomtype'] ) . '" data-visible-captions="' . esc_attr( $args['show_caption'] ? 'true' : 'false' ) . '" data-zoom-active="' . esc_attr( $args['zoomactive'] ? 'true' : 'false' ) . '" data-thumb-show="' . esc_attr( $args['thumb_columns'] ) . '" data-md-thumb-show="' . esc_attr( $args['thumb_columns_tablet'] ) . '" data-sm-thumb-show="' . esc_attr( $args['thumb_columns_mobile'] ) . '" data-thumbcol="' . esc_attr( $args['thumb_load_columns'] ) . '" data-sm-thumbcol="' . esc_attr( $args['thumb_sm_load_columns'] ) . '" data-layout="' . esc_attr( $args['layout'] ) . '" data-md-layout="' . esc_attr( $args['layout_tablet'] ) . '" data-sm-layout="' . esc_attr( $args['layout_mobile'] ) . '" data-thumb-width="' . esc_attr( $args['thumb_width'] ) . '" data-md-thumb-width="' . esc_attr( $args['thumb_width_tablet'] ) . '" data-sm-thumb-width="' . esc_attr( $args['thumb_width_mobile'] ) . '" data-thumb-gap="' . esc_attr( $args['thumb_gap'] ) . '" data-md-thumb-gap="' . esc_attr( $args['thumb_gap_tablet'] ) . '" data-sm-thumb-gap="' . esc_attr( $args['thumb_gap_mobile'] ) . '" data-thumb-center="' . esc_attr( $center ) . '" data-thumb-hover="' . esc_attr( $args['thumb_hover'] ? 'true' : 'false' ) . '">';
			$number = 1;
			$thumbnails = array();
			echo '<div class="splide__track"><ul class="splide__list">';
			foreach ( $images as $key => $slide ) :
				$alt = esc_attr( get_post_meta( $slide, '_wp_attachment_image_alt', true ) );
				if ( ! empty( $alt ) ) {
					$alttag = $alt;
				} else {
					$alttag = esc_attr( get_post_field( 'post_title', $slide ) );
				}
				$data_caption = get_post_field( 'post_excerpt', $slide );
				$video = false;
				if ( ! $args['is_custom'] ) {
					$woo_image_size = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
					$woo_img        = wp_get_attachment_image_src( $slide, $woo_image_size );
					if ( $woo_img ) {
						$woo_meta       = wp_get_attachment_metadata( $slide );
						$full_src       = wp_get_attachment_image_src( $slide, 'full' );
						$img_srcset     = wp_calculate_image_srcset( array( $woo_img[1], $woo_img[2] ), $woo_img[0], $woo_meta, $slide );
					}
					$img = array(
						'full' => ! empty( $full_src[0] ) ? esc_url( $full_src[0] ) : '',
						'full_width' => ! empty( $full_src[1] ) ? $full_src[1] : '',
						'full_height' => ! empty( $full_src[2] ) ? $full_src[2] : '',
						'src' => ! empty( $woo_img[0] ) ? esc_url( $woo_img[0] ) : '',
						'width' => ! empty( $woo_img[1] ) ? $woo_img[1] : '',
						'height' => ! empty( $woo_img[2] ) ? $woo_img[2] : '',
						'alt' => ! empty( $alttag ) ? $alttag : '',
						'srcset' => ! empty( $img_srcset ) ? 'srcset="' . esc_attr( $img_srcset ) . '" sizes="(max-width: ' . esc_attr( ! empty( $woo_img[1] ) ? $woo_img[1] : '' ) . 'px) 100vw, ' . esc_attr( ! empty( $woo_img[1] ) ? $woo_img[1] : '' ) . 'px"' : '',
					);
				} else {
					$img = kt_woo_get_image_array( $args['width'], $args['height'], true, 'attachment-shop-single', $alttag, $slide, false, 'woocommerce_single' );
				}
				$image_class_output = 'zoom kt-image-slide kt-no-lightbox';
				if ( 1 === $number ) {
					$image_class_output = 'woocommerce-main-image ' . $image_class_output;
				}
				$img_link = $img['full'];
				$video_link = get_post_meta( $slide, '_kt_woo_product_video', true );
				if ( ! empty( $video_link ) && $args['lightbox'] ) {
					$img_link = get_post_meta( $slide, '_kt_woo_product_video', true );
					$image_class_output .= ' kt-woo-video-link';
					$video = true;
				}
				$thumbnails[ $key ] = array(
					'video' => $video,
					'img'   => kt_woo_get_image_array( $args['thumb_img_width'], $args['thumb_img_height'], true, 'attachment-shop-single', $alttag, $slide, false, 'thumbnail' ),
				);
				$html = '<li class="splide__slide' . ( 1 === $number ? ' woo-main-slide' : '' ) . '">';
				if ( $args['lightbox'] ) {
					$html .= '<a href="' . esc_url( $img_link ) . '"  data-rel="lightbox" itemprop="image" class="' . esc_attr( $image_class_output ) . '" data-description="' . esc_attr( $data_caption ) . '" title="' . esc_attr( get_post_field( 'post_title', $slide ) ) . '">';
				}
				if ( $lazy && $is_slider && 1 !== $number ) {
					$html .= '<img width="' . esc_attr( $img['width'] ) . '" style="width: ' . esc_attr( $img['width'] ) . 'px" data-splide-lazy="' . esc_url( $img['src'] ) . '" data-thumb="' . esc_url( $thumbnails[ $key ]['img']['src'] ) . '" class="attachment-shop-single" data-caption="' . esc_attr( $data_caption ) . '" title="' . esc_attr( get_post_field( 'post_title', $slide ) ) . '" data-zoom-image="' . esc_url( $img['full'] ) . '" height="' . esc_attr( $img['height'] ) . '" alt="' . esc_attr( $img['alt'] ) . '" ' . str_replace( 'srcset=', 'data-splide-lazy-srcset=', $img['srcset'] ) . ' />';
				} else {
					$html .= '<img width="' . esc_attr( $img['width'] ) . '" style="width: ' . esc_attr( $img['width'] ) . 'px" data-thumb="' . esc_url( $thumbnails[ $key ]['img']['src'] ) . '" class="attachment-shop-single skip-lazy" data-caption="' . esc_attr( $data_caption ) . '" title="' . esc_attr( get_post_field( 'post_title', $slide ) ) . '" data-zoom-image="' . esc_url( $img['full'] ) . '" height="' . esc_attr( $img['height'] ) . '"' . ( $lazy && 1 !== $number ? ' loading="lazy"' : '' ) . ' src="' . esc_url( $img['src'] ) . '" alt="' . esc_attr( $img['alt'] ) . '" ' . $img['srcset'] . ' />';
				}
				if ( $args['show_caption'] && ! empty( $data_caption ) ) {
					$html .= '<span class="sp-gal-image-caption">' . wp_kses_post( $data_caption ) . '</span>';
				}
				if ( $video ) {
					$html .= '<span class="kt-woo-play-btn">';
					$html .= kadence_woo_extras_get_icon( 'play', '', false, true );
					$html .= '</span>';
				}
				if ( $args['lightbox'] ) {
					$html .= '</a>';
				}
				$html .= '</li>';
				echo apply_filters( 'kadence_single_product_image_main_html', $html, $slide ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
				$number ++;
			endforeach;
			echo '</ul>';
			echo '</div>';
			echo '</div><!-- main -->';
			if ( ! empty( $attachment_ids ) ) {
				echo '<div id="pg-thumbnails-' . esc_attr( get_the_ID() ) . '" class="kadence-product-gallery-thumbnails splide kb-splide kt-carousel-arrowstyle-blackonlight ' . esc_attr( $thumb_class ) . '" style="' . esc_attr( $args['thumb_style'] ) . '">';
				echo '<div class="thumb-wrapper splide__slider">';
				echo '<div class="splide__track"><ul class="splide__list">';
				$number = 1;
				foreach ( $images as $key => $slide ) :
					$thumbnail_class = 'kt-woo-gallery-thumbnail splide__slide';
					if ( $thumbnails[ $key ]['video'] ) {
						$thumbnail_class .= ' kt-woo-video-thumb';
					}
					if ( 1 === $number ) {
						$thumbnail_class .= ' woocommerce-main-image-thumb';
					}
					$html = '<li class="' . esc_attr( $thumbnail_class ) . '">';
					$html .= '<img width="' . esc_attr( $thumbnails[ $key ]['img']['width'] ) . '" height="' . esc_attr( $thumbnails[ $key ]['img']['height'] ) . '" src="' . esc_url( $thumbnails[ $key ]['img']['src'] ) . '" alt="' . esc_attr( $thumbnails[ $key ]['img']['alt'] ) . '" ' . $thumbnails[ $key ]['img']['srcset'] . ' />';
					if ( $thumbnails[ $key ]['video'] ) {
						$html .= '<span class="kt-woo-play-btn">';
						$html .= kadence_woo_extras_get_icon( 'play-circle', '', false, true );
						$html .= '</span>';
					}
					$html .= '</li>';
					echo apply_filters( 'kadence_single_product_image_thumbnail_html', $html, $slide ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
					$number ++;
					endforeach;
					echo '</ul></div>';
					echo '</div>';
				echo '</div><!-- thumbnails -->';
			}
			echo '</div></div>';
			if ( $product->is_type( 'variable' ) || $product->is_type( 'variable-subscription' ) ) {
				do_action( 'kadence_shopkit_variation_gallery', $args );
			}
		}
	}
}
$GLOBALS['kt_product_gallery'] = Kadence_Shop_Kit_Product_Gallery::get_instance();
