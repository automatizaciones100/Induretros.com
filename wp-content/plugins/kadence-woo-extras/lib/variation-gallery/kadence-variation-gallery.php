<?php
/**
 * Add Variation Gallery Options to Woocommerce Variation Products.
 *
 * @package Kadence Woo Extras
 */

 // Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to Add Variation Gallery Options to Woocommerce Variation Products.
 *
 * @category class
 */
class Kadence_Variation_Gallery {
	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

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
	 * Class Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'extra_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_extra_variation_image' ), 10, 2 );
		add_action( 'kadence_shopkit_variation_gallery', array( $this, 'hook_in_variation_image_data' ), 10 );
		add_action( 'wp_ajax_kadence_variation_images_load_frontend_ajax', array( $this, 'ajax_load_frontend_images' ) );
		add_action( 'wp_ajax_nopriv_kadence_variation_images_load_frontend_ajax', array( $this, 'ajax_load_frontend_images' ) );
	}
	/**
	 * Get variation image data.
	 */
	public function ajax_load_frontend_images() {
		check_ajax_referer( 'kwsv', 'ajaxkwsvNonce' );
		$variation_id = absint( wp_unslash( $_POST['variation_id'] ) );
		$output_array = false;
		if ( isset( $variation_id ) && ! empty( $variation_id ) ) {
			$kskpg = Kadence_Shop_Kit_Product_Gallery::get_instance();
			$args = $kskpg->get_gallery_args();
			// before we get carried away lets make sure there are variation images.
			$variation_images = get_post_meta( $variation_id, '_kt_extra_variation_img_ids', true );
			if ( isset( $variation_images ) && ! empty( $variation_images ) ) {
				$attachment_ids = explode( ',', $variation_images );
				$output_array = $this->build_slide_array( $attachment_ids, $args );
			}
		}
		if ( $output_array ) {
			wp_send_json( json_encode( $output_array ) );
		} else {
			wp_send_json_error();
		}
	}
	/**
	 * Add variation image data to product.
	 */
	public function build_slide_array( $slides, $args ) {
		if ( ! is_array( $slides ) ) {
			return;
		}
		$slides_array = array();
		$thumbnails_array = array();
		foreach ( $slides as $slide ) {
			$alt = esc_attr( get_post_meta( $slide, '_wp_attachment_image_alt', true ) );
			if ( ! empty( $alt ) ) {
				$alttag = $alt;
			} else {
				$alttag = esc_attr( get_post_field( 'post_title', $slide ) );
			}
			$caption = get_post_field( 'post_excerpt', $slide );
			if ( empty( $caption ) ) {
				$data_caption = get_post_field( 'post_title', $slide );
			} else {
				$data_caption = $caption;
			}
			$video = false;
			if ( ! $args['is_custom'] ) {
				$woo_image_size = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
				$woo_img        = wp_get_attachment_image_src( $slide, $woo_image_size );
				$woo_meta       = wp_get_attachment_metadata( $slide );
				$full_src       = wp_get_attachment_image_src( $slide, 'full' );
				$img_srcset     = wp_calculate_image_srcset( array( $woo_img[1], $woo_img[2] ), $woo_img[0], $woo_meta, $slide );
				$img            = array(
					'full' => esc_url( $full_src[0] ),
					'full_width' => $full_src[1],
					'full_height' => $full_src[2],
					'src' => esc_url( $woo_img[0] ),
					'width' => $woo_img[1],
					'height' => $woo_img[2],
					'alt' => $alttag,
					'srcset' => $img_srcset ? 'srcset="' . esc_attr( $img_srcset ) . '" sizes="(max-width: ' . esc_attr( $woo_img[1] ) . 'px) 100vw, ' . esc_attr( $woo_img[1] ) . 'px"' : '',
				);
			} else {
				$img = kt_woo_get_image_array( $args['width'], $args['height'], true, 'attachment-shop-single', $alttag, $slide, false, 'woocommerce_single' );
			}
			$image_class_output = 'zoom kt-image-slide kt-no-lightbox';
			$img_link = $img['full'];
			$video_link = get_post_meta( $slide, '_kt_woo_product_video', true );
			if ( ! empty( $video_link ) ) {
				$img_link = get_post_meta( $slide, '_kt_woo_product_video', true );
				$image_class_output .= ' kt-woo-video-link';
				$video = true;
			}
			$thumbnail = kt_woo_get_image_array( $args['thumb_img_width'], $args['thumb_img_height'], true, 'attachment-shop-single', $alttag, $slide, false, 'thumbnail' );
			$html = '<li class="splide__slide">';
			$html .= '<a href="' . esc_url( $img_link ) . '"  data-rel="lightbox" itemprop="image" class="' . esc_attr( $image_class_output ) . '" title="' . esc_attr( get_post_field( 'post_title', $slide ) ) . '">';
			$html .= '<img width="' . esc_attr( $img['width'] ) . '" data-thumb="' . esc_url( $thumbnail['src'] ) . '" class="attachment-shop-single" data-caption="' . esc_attr( $data_caption ) . '" title="' . esc_attr( get_post_field( 'post_title', $slide ) ) . '" data-zoom-image="' . esc_url( $img['full'] ) . '" height="' . esc_attr( $img['height'] ) . '" src="' . esc_url( $img['src'] ) . '" alt="' . esc_attr( $img['alt'] ) . '" ' . $img['srcset'] . ' />';
			if ( $args['show_caption'] && ! empty( $caption ) ) {
				$html .= '<span class="sp-gal-image-caption">' . wp_kses_post( $caption ) . '</span>';
			}
			if ( $video ) {
				$html .= '<span class="kt-woo-play-btn">';
				$html .= kadence_woo_extras_get_icon( 'play', '', false, true );
				$html .= '</span>';
			}
			$html .= '</a>';
			$html .= '</li>';
			$slides_array[] = apply_filters( 'kadence_single_product_image_main_html', $html, $slide );
			$thumbnail_class = 'kt-woo-gallery-thumbnail splide__slide';
			if ( $video ) {
				$thumbnail_class .= ' kt-woo-video-thumb';
			}
			$html = '<li class="' . esc_attr( $thumbnail_class ) . '">';
			$html .= '<img width="' . esc_attr( $thumbnail['width'] ) . '" height="' . esc_attr( $thumbnail['height'] ) . '" src="' . esc_url( $thumbnail['src'] ) . '" alt="' . esc_attr( $thumbnail['alt'] ) . '" ' . $thumbnail['srcset'] . ' />';
			if ( $video ) {
				$html .= '<span class="kt-woo-play-btn">';
				$html .= kadence_woo_extras_get_icon( 'play-circle', '', false, true );
				$html .= '</span>';
			}
			$html .= '</li>';
			$thumbnails_array[] = apply_filters( 'kadence_single_product_image_thumbnail_html', $html, $slide ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
		}
		return array(
			'images'     => $slides_array,
			'thumbnails' => $thumbnails_array,
		);
	}
	/**
	 * Add variation image data to product.
	 */
	public function hook_in_variation_image_data( $args ) {
		global $product;
		// Get Available variations?
		$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		$available_variations = array();
		$available_variations['has_variation_images'] = false;
		$available_variations['is_ajax'] = false;
		if ( $get_variations ) {
			$variable_product = new WC_Product_Variable( $product->get_id() );
			foreach ( $variable_product->get_children() as $variation_id ) {
				$variation_images = get_post_meta( absint( $variation_id ), '_kt_extra_variation_img_ids', true );
				if ( isset( $variation_images ) && ! empty( $variation_images ) ) {
					$available_variations['has_variation_images'] = true;
					$attachment_ids = explode( ',', $variation_images );
					$available_variations[ $variation_id ] = $this->build_slide_array( $attachment_ids, $args );
				} else {
					$available_variations[ $variation_id ] = false;
				}
			}
		} else {
			$available_variations['is_ajax'] = true;
		}
		$attachment_ids = $product->get_gallery_image_ids();
		if ( $attachment_ids ) {
			$available_variations['original'] = $this->build_slide_array( $attachment_ids, $args );
		} else {
			$available_variations['original'] = false;
		}
		echo '<div id="pg-extra-' . esc_attr( get_the_ID() ) . '" class="kske-thumbnails" data-product_variation_images="' . htmlspecialchars( wp_json_encode( $available_variations ) ) . '"></div>';
	}
	/**
	 * Save extra images.
	 *
	 * @param numeric $post_id the post ID.
	 */
	public function save_extra_variation_image( $variation_id, $loop ) {
		if ( isset( $_POST['_kt_extra_variation_img_ids'][ $variation_id ] ) ) {
			$image_ids = wp_unslash( $_POST['_kt_extra_variation_img_ids'][ $variation_id ] );
			$update_ids = $image_ids;
			if ( $image_ids ) {
				$update_ids = array();
				$attachment_ids = explode( ',', $image_ids );
				foreach ( $attachment_ids as $id ) {
					$id = absint( $id );
					if ( ! empty( $id ) ) {
						$update_ids[] = $id;
					}
				}
				$update_ids = implode( ',', $update_ids );
			}
			update_post_meta( $variation_id, '_kt_extra_variation_img_ids', sanitize_text_field( $update_ids ) );
		}
	}
	/**
	 * Enqueue admin scripts.
	 */
	public function admin_scripts() {
		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		if ( in_array( $screen_id, array( 'product', 'edit-product' ) ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'kadence-variation-images-admin', KADENCE_WOO_EXTRAS_URL . 'lib/variation-gallery/css/variation-images-admin.css', false, KADENCE_WOO_EXTRAS_VERSION );
			wp_enqueue_script( 'kadence-variation-images-admin', KADENCE_WOO_EXTRAS_URL . 'lib/variation-gallery/js/variation-images-admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), KADENCE_WOO_EXTRAS_VERSION, true );
		}
	}
	/**
	 * Add Extra Fields.
	 *
	 * @param number $loop the loop number.
	 * @param array $variation_data the meta data.
	 * @param object $variation the post object.
	 */
	public function extra_fields( $loop, $variation_data, $variation ) {

		$variation_images = get_post_meta( $variation->ID, '_kt_extra_variation_img_ids', true );
		$output = '';
		if ( isset( $variation_images ) && ! empty( $variation_images ) ) {
			$attachment_ids = explode( ',', $variation_images );
			foreach ( $attachment_ids as $id ) {
				if ( ! empty( $id ) ) {
					$attachment = wp_get_attachment_image( $id, 'thumbnail' );
					$output .= '<li class="image" data-attachment-id="' . esc_attr( $id ) . '">';
					$output .= $attachment;
					$output .= '<ul class="actions">';
					$output .= '<li><a href="#" class="delete">' . esc_html__( 'Delete', 'kadence-woo-extras' ) . '</a></li>';
					$output .= '</ul>';
					$output .= '</li>';
				}
			}
		}
		echo '<div data-product_variation_id="' . esc_attr( $variation->ID ) . '" class="form-row form-row-full kwsv-variations-images-wrapper">';
		echo '<h4 style="margin-bottom:0">' . esc_html__( 'Variation Image Gallery', 'kadence-woo-extras' ) . '</h4>';
		echo '<div class="kwsv-variations-images-subtitle" style="margin-bottom:1em">' . esc_html__( 'This replaces the product level gallery on selection', 'kadence-woo-extras' ) . '</div>';
		echo '<div class="kwsv-gallery-wrap"><ul class="kwsv-gallery-list">' . $output . '</ul></div>';
		/* Hidden field*/
		woocommerce_wp_hidden_input(
			array( 
				'id'          => '_kt_extra_variation_img_ids[' . $variation->ID . ']',
				'desc_tip'    => 'true',
				'value'       => esc_attr( $variation_images ),
				'class' 	  => 'kwsv_gallery_images',
			)
		);
		echo '<p class="add_variation_images hide-if-no-js">';
		echo '<a href="#" onclick="return false;" data-product_variation_id="' . esc_attr( $variation->ID ) . '" class="button button-primary kwsv-upload-variation-img" data-choose="' . esc_attr__( 'Add images to product gallery', 'kadence-woo-extras' ) . '" data-update="' . esc_attr__( 'Add to gallery', 'kadence-woo-extras' ) . '" data-delete="' . esc_attr__( 'Delete image', 'kadence-woo-extras' ) . '" data-text="' . esc_attr__( 'Delete', 'kadence-woo-extras' ) . '">' . esc_html__( 'Add Variation Images', 'kadence-woo-extras' ) . '</a>';
		echo '</p>';
		echo '</div>';
	}
}
Kadence_Variation_Gallery::get_instance();
