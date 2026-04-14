<?php
/**
 * Kadence_Woo Extras Settings Class
 *
 * @package Kadence Woo Extras
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use function KadenceWP\KadenceShopKit\StellarWP\Uplink\get_license_key;
use function KadenceWP\KadenceShopKit\StellarWP\Uplink\validate_license;
/**
 * Main Kadence_Woo_Extras_Settings class
 */
class Kadence_Woo_Extras_Settings {
	const OPT_NAME = 'kt_woo_extras';

	/**
	 * Action on init.
	 */
	public function __construct() {
		require_once KADENCE_WOO_EXTRAS_PATH . 'inc/settings/load.php';
		// Need to load this with priority higher then 10 so class is loaded.
		add_action( 'after_setup_theme', array( $this, 'add_sections' ), 20 );
		add_filter( 'kadence_settings_enqueue_args', array( $this, 'register_license_validation' ), 10, 2 );
		add_filter( 'kadence_settings_enqueue_sections', array( $this, 'register_dynamic_options' ), 10, 2 );
	}
	/**
	 * Register settings.
	 */
	public function register_dynamic_options( $sections, $opt_name ) {
		if ( ! empty( $opt_name ) && 'kt_woo_extras' === $opt_name ) {
			if ( ! empty( $sections['kt_woo_extra_swatches']['fields'] ) && is_array( $sections['kt_woo_extra_swatches']['fields'] ) ) {
				foreach ( $sections['kt_woo_extra_swatches']['fields'] as $key => $field ) {
					if ( isset( $field['id'] ) && 'variation_archive_swatches_catalog_attribute' === $field['id'] ) {
						$sections['kt_woo_extra_swatches']['fields'][ $key ]['options'] = $this->get_attribute_terms();
					}
				}
			}
		}
		return $sections;
	}
	/**
	 * Register settings.
	 */
	public function register_license_validation( $args, $opt_name ) {
		if ( ! empty( $opt_name ) && 'kt_woo_extras' === $opt_name ) {
			$key          = get_license_key( 'kadence-shop-kit' );
			$license_data = validate_license( 'kadence-shop-kit', $key );
			if ( isset( $license_data ) && is_object( $license_data ) && method_exists( $license_data, 'is_valid' ) && $license_data->is_valid() ) {
				$license_status = true;
			} else {
				$license_status = false;
			}
			$args['licenseActive'] = $license_status;
		}
		return $args;
	}
	/**
	 * Add sections to settings.
	 */
	public function add_sections() {
		if ( ! class_exists( 'Kadence_Settings_Engine' ) ) {
			return;
		}
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		$args = array(
			'v2'                               => true,
			'opt_name'                         => self::OPT_NAME,
			'menu_icon'                        => 'dashicons-cart',
			'menu_title'                       => __( 'Shop Kit', 'kadence-woo-extras' ),
			'page_title'                       => __( 'Kadence Shop Kit', 'kadence-woo-extras' ),
			'page_slug'                        => 'kadence-shop-kit-settings',
			'page_permissions'                 => 'manage_options',
			'menu_type'                        => 'menu',
			'page_parent'                      => ( apply_filters( 'kadence_shopkit_network', false ) ? 'settings.php' : 'options-general.php' ),
			'page_priority'                    => null,
			'footer_credit'                    => '',
			'class'                            => '',
			'admin_bar'                        => false,
			'admin_bar_priority'               => 999,
			'admin_bar_icon'                   => '',
			'show_import_export'               => false,
			'version'                          => KADENCE_WOO_EXTRAS_VERSION,
			'logo'                             => KADENCE_WOO_EXTRAS_URL . 'assets/kadence-logo.png',
			'changelog'                        => KADENCE_WOO_EXTRAS_PATH . 'changelog.txt',
			'network_admin'                    => apply_filters( 'kadence_shopkit_network', false ),
			'database'                         => ( apply_filters( 'kadence_shopkit_network', false ) ? 'network' : '' ),
			'license'                          => 'hidden-side-panel',
		);
		$args['tabs'] = array(
			'settings' => array(
				'id' => 'settings',
				'title' => __( 'Settings', 'kadence-woo-extras' ),
			),
		);
		$args['sidebar'] = array(
			'docs' => array(
				'title'       => __( 'Documentation', 'kadence-woo-extras' ),
				'description' => __( 'Need help? We have a knowledge base full of articles to get you started.', 'kadence-woo-extras' ),
				'link'        => 'https://www.kadencewp.com/help-center/knowledge-base/kadence-shop-kit/',
				'link_text'   => __( 'Browse Docs', 'kadence-woo-extras' ),
			),
			'support' => array(
				'title' => __( 'Support', 'kadence-woo-extras' ),
				'description' => __( 'Have a question, we are happy to help! Get in touch with our support team.', 'kadence-woo-extras' ),
				'link' => 'https://www.kadencewp.com/premium-support-tickets/',
				'link_text' => __( 'Submit a Ticket', 'kadence-woo-extras' ),
			),
			'facebook' => array(
				'title' => __( 'Web Creators Community', 'kadence-woo-extras' ),
				'description' => __( 'Join our community of fellow kadence users creating effective websites! Share your site, ask a question and help others.', 'kadence-woo-extras' ),
				'link' => 'https://www.facebook.com/groups/webcreatorcommunity',
				'link_text' => __( 'Join our Facebook Group', 'kadence-woo-extras' ),
			),
		);
		Kadence_Settings_Engine::set_args( self::OPT_NAME, $args );
		Kadence_Settings_Engine::set_groups(
			self::OPT_NAME,
			array(
				array(
					'id'    => 'customization',
					'title' => __( 'Product Customization', 'kadence-woo-extras' ),
				),
				array(
					'id'    => 'variations',
					'title' => __( 'Product Variations', 'kadence-woo-extras' ),
				),
				array(
					'id'    => 'features',
					'title' => __( 'Product Features', 'kadence-woo-extras' ),
				),
				array(
					'id'    => 'checkout',
					'title' => __( 'Cart & Checkout', 'kadence-woo-extras' ),
				)
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_gallery',
				'title' => __( 'Product Gallery', 'kadence-woo-extras' ),
				'desc' => __( 'Design dynamic galleries that uniquely connect with customers, no matter what device they\'re using to shop. Customize your look on mobile, tablet, and desktop.', 'kadence-woo-extras' ),
				'group' => 'customization',
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/gallery-preview.svg',
				'fields' => array(
					array(
						'id' => 'product_gallery',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Product Slider Gallery', 'kadence-woo-extras' ),
						'help' => __( 'This changes the woocommerce product image and gallery into a slider', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'ga_slider_layout_wrap',
						'responsive' => true,
						'desktop' => array(
							'id' => 'ga_slider_layout',
							'type' => 'image_select',
							'title' => __( 'Choose a gallery type', 'kadence-woo-extras' ),
							'options' => array(
								array(
									'value' => 'above',
									'alt' => 'Thumbnails Below Image',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-above.png',
								),
								array(
									'value' => 'left',
									'alt' => 'Thumbnails beside slider on the right',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-left.png',
								),
								array(
									'value' => 'right',
									'alt' => 'Thumbnails beside slider on the left',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-right.png',
								),
								array(
									'value' => 'list',
									'alt' => 'No Thumbnails, Images in a list.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-list.png',
								),
								array(
									'value' => 'grid',
									'alt' => 'Images in a two column grid.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-grid.png',
								),
								array(
									'value' => 'tiles',
									'alt' => 'Images in a tiles layout.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-tiles.png',
								),
								array(
									'value' => 'slider',
									'alt' => 'Images in a slider layout, no thumbnails.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-slider.png',
								),
							),
							'default' => 'above',
						),
						'tablet' => array(
							'id' => 'ga_slider_layout_tablet',
							'type' => 'image_select',
							'title' => __( 'Choose a gallery type', 'kadence-woo-extras' ),
							'options' => array(
								array(
									'value' => 'above',
									'alt' => 'Thumbnails Below Image',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-above.png',
								),
								array(
									'value' => 'left',
									'alt' => 'Thumbnails beside slider on the right',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-left.png',
								),
								array(
									'value' => 'right',
									'alt' => 'Thumbnails beside slider on the left',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-right.png',
								),
								array(
									'value' => 'list',
									'alt' => 'No Thumbnails, Images in a list.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-list.png',
								),
								array(
									'value' => 'grid',
									'alt' => 'Images in a two column grid.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-grid.png',
								),
								array(
									'value' => 'tiles',
									'alt' => 'Images in a tiles layout.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-tiles.png',
								),
								array(
									'value' => 'slider',
									'alt' => 'Images in a slider layout, no thumbnails.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-slider.png',
								),
							),
							'default' => '',
						),
						'mobile' => array(
							'id' => 'ga_slider_layout_mobile',
							'type' => 'image_select',
							'title' => __( 'Choose a gallery type', 'kadence-woo-extras' ),
							'options' => array(
								array(
									'value' => 'above',
									'alt' => 'Thumbnails Below Image',
									'img' => KADENCE_WOO_EXTRAS_URL . '/lib/gallery/img/gallery-type-above.png',
								),
								array(
									'value' => 'left',
									'alt' => 'Thumbnails beside slider on the left',
									'img' => KADENCE_WOO_EXTRAS_URL . '/lib/gallery/img/gallery-type-left.png',
								),
								array(
									'value' => 'right',
									'alt' => 'Thumbnails beside slider on the right',
									'img' => KADENCE_WOO_EXTRAS_URL . '/lib/gallery/img/gallery-type-right.png',
								),
								array(
									'value' => 'list',
									'alt' => 'No Thumbnails, Images in a list.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-list.png',
								),
								array(
									'value' => 'grid',
									'alt' => 'Images in a two column grid.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-grid.png',
								),
								array(
									'value' => 'tiles',
									'alt' => 'Images in a tiles layout.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-tiles.png',
								),
								array(
									'value' => 'slider',
									'alt' => 'Images in a slider layout, no thumbnails.',
									'img' => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/img/gallery-type-slider.png',
								),
							),
							'default' => '',
						),
						'required' => array( 'product_gallery', '=', '1' ),
					),
					array(
						'id' => 'product_gallery_custom_size',
						'type' => 'switch',
						'title' => __( 'Enable Custom Size for Gallery Images', 'kadence-woo-extras' ),
						'help' => __( 'This enables options to set custom image sizes for the product gallery.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'product_gallery', '=', '1' ),
					),
					array(
						'id' => 'ga_image_width',
						'type' => 'range',
						'title' => __( 'Product image width', 'kadence-woo-extras' ),
						'default'   => '465',
						'min'       => '200',
						'step'      => '1',
						'max'       => '2000',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'product_gallery_custom_size', '=', '1' ),
						),
					),
					array(
						'id' => 'ga_image_ratio',
						'type' => 'select',
						'title' => __( 'Product image ratio', 'kadence-woo-extras' ),
						'options' => array(
							'square' => __( 'Square 1:1', 'kadence-woo-extras' ),
							'portrait' => __( 'Portrait 3:4', 'kadence-woo-extras' ),
							'landscape' => __( 'Landscape 4:3', 'kadence-woo-extras' ),
							'landscape32' => __( 'Landscape 3:2', 'kadence-woo-extras' ),
							'landscape169' => __( 'Landscape 16:9', 'kadence-woo-extras' ),
							'widelandscape' => __( 'Wide Landscape 4:2', 'kadence-woo-extras' ),
							'custom' => __( 'Custom', 'kadence-woo-extras' ),
						),
						'default' => 'square',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'product_gallery_custom_size', '=', '1' ),
						),
					),
					array(
						'id' => 'ga_image_height',
						'type' => 'range',
						'title' => __( 'Product image Height', 'kadence-woo-extras' ),
						'default'       => '465',
						'min'       => '200',
						'customizer' => false,
						'step'      => '1',
						'max'       => '1200',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'product_gallery_custom_size', '=', '1' ),
							array( 'ga_image_ratio', '=', 'custom' ),
						),
					),
					array(
						'id' => 'ga_thumb_vertical_width_wrap',
						'responsive' => true,
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'ga_slider_layout', '!=', 'above' ),
							array( 'ga_slider_layout', '!=', 'grid' ),
							array( 'ga_slider_layout', '!=', 'tiles' ),
							array( 'ga_slider_layout', '!=', 'list' ),
							array( 'ga_slider_layout', '!=', 'slider' ),
						),
						'desktop' => array(
							'id'       => 'ga_thumb_width',
							'type'     => 'range',
							'title'    => __( 'Thumbnail Width %', 'kadence-woo-extras' ),
							'default'  => '20',
							'min'      => '2',	
							'step'     => '1',
							'max'      => '80',
						),
						'tablet' => array(
							'id'       => 'ga_thumb_width_tablet',
							'type'     => 'range',
							'title'    => __( 'Thumbnail Width %', 'kadence-woo-extras' ),
							'default'  => '',
							'min'      => '2',	
							'step'     => '1',
							'max'      => '80',
						),
						'mobile' => array(
							'id'       => 'ga_thumb_width_mobile',
							'type'     => 'range',
							'title'    => __( 'Thumbnail Width %', 'kadence-woo-extras' ),
							'default'  => '',
							'min'      => '2',	
							'step'     => '1',
							'max'      => '80',
						),
					),
					array(
						'id' => 'ga_thumb_columns_wrap',
						'responsive' => true,
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'ga_slider_layout', '!=', 'grid' ),
							array( 'ga_slider_layout', '!=', 'tiles' ),
							array( 'ga_slider_layout', '!=', 'list' ),
							array( 'ga_slider_layout', '!=', 'slider' ),
						),
						'desktop' => array(
							'id'       => 'ga_thumb_columns',
							'type'     => 'range',
							'title'    => __( 'Select how many thumbnail columns are visible at a time', 'kadence-woo-extras' ),
							'default'  => '6',
							'min'      => '2',	
							'step'     => '1',
							'max'      => '10',
						),
						'tablet' => array(
							'id'       => 'ga_thumb_columns_tablet',
							'type'     => 'range',
							'title'    => __( 'Select how many thumbnail columns are visible at a time', 'kadence-woo-extras' ),
							'default'  => '',
							'min'      => '2',	
							'step'     => '1',
							'max'      => '10',
						),
						'mobile' => array(
							'id'       => 'ga_thumb_columns_mobile',
							'type'     => 'range',
							'title'    => __( 'Select how many thumbnail columns are visible at a time', 'kadence-woo-extras' ),
							'default'  => '',
							'min'      => '2',	
							'step'     => '1',
							'max'      => '10',
						),
					),
					array(
						'id' => 'ga_thumb_image_ratio',
						'type' => 'select',
						'title' => __( 'Product thumbnail image ratio', 'kadence-woo-extras' ),
						'options' => array(
							'square' => __( 'Square 1:1', 'kadence-woo-extras' ),
							'portrait' => __( 'Portrait 3:4', 'kadence-woo-extras' ),
							'landscape' => __( 'Landscape 4:3', 'kadence-woo-extras' ),
							'landscape32' => __( 'Landscape 3:2', 'kadence-woo-extras' ),
							'landscape169' => __( 'Landscape 16:9', 'kadence-woo-extras' ),
							'widelandscape' => __( 'Wide Landscape 4:2', 'kadence-woo-extras' ),
							'inherit' => __( 'Inherit from image', 'kadence-woo-extras' ),
						),
						'default' => 'square',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'ga_slider_layout', '=', 'above' ),
						),
					),
					array(
						'id' => 'ga_trans_type',
						'type' => 'select',
						'title' => __( 'Slider transition', 'kadence-woo-extras' ),
						'options' => array(
							'false' => __( 'Slide', 'kadence-woo-extras' ),
							'true' => __( 'Fade', 'kadence-woo-extras' ),
						),
						'default' => 'false',
						'required' => array( 'product_gallery', '=', '1' ),
					),
					array(
						'id' => 'ga_slider_transtime',
						'type' => 'range',
						'title' => __( 'Slider transition speed', 'kadence-woo-extras' ),
						'help' => __( 'How long the transition takes, in milliseconds.', 'kadence-woo-extras' ),
						'default'   => '500',
						'min'       => '100',
						'step'      => '100',
						'max'       => '4000',
						'required' => array( 'product_gallery', '=', '1' ),
					),
					array(
						'id' => 'ga_slider_autoplay',
						'type' => 'select',
						'title' => __( 'Slider auto play', 'kadence-woo-extras' ),
						'options' => array(
							'false' => __( 'False', 'kadence-woo-extras' ),
							'true' => __( 'True', 'kadence-woo-extras' ),
						),
						'default' => 'false',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'ga_slider_layout', '!=', 'grid' ),
							array( 'ga_slider_layout', '!=', 'tiles' ),
							array( 'ga_slider_layout', '!=', 'list' ),
						),
					),
					array(
						'id' => 'ga_slider_pausetime',
						'type' => 'range',
						'title' => __( 'Slider pause time', 'kadence-woo-extras' ),
						'help' => __( 'How long to pause on each slide, in milliseconds.', 'kadence-woo-extras' ),
						'default'   => '7000',
						'min'       => '3000',
						'step'      => '1000',
						'max'       => '12000',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
						),
					),
					array(
						'id' => 'ga_slider_auto_height',
						'title' => __( 'Adjust height to each photo?', 'kadence-woo-extras' ),
						'type' => 'switch',
						'default' => 0,
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'ga_slider_layout', '!=', 'grid' ),
							array( 'ga_slider_layout', '!=', 'tiles' ),
							array( 'ga_slider_layout', '!=', 'list' ),
							array( 'ga_slider_layout', '!=', 'left' ),
							array( 'ga_slider_layout', '!=', 'right' ),
						),
					),
					array(
						'id' => 'ga_slider_auto_height_info',
						'title' => __( 'Warning! Using adjust height with autoplay is not recommended, on mobile devices this will cause your content to move up and down when slides change.', 'kadence-woo-extras' ),
						'type' => 'info',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'ga_slider_auto_height', '=', '1' ),
							array( 'ga_slider_autoplay', '=', 'True' ),
							array( 'ga_slider_layout', '!=', 'grid' ),
							array( 'ga_slider_layout', '!=', 'tiles' ),
							array( 'ga_slider_layout', '!=', 'list' ),
							array( 'ga_slider_layout', '!=', 'left' ),
							array( 'ga_slider_layout', '!=', 'right' ),
						),
					),
					array(
						'id' => 'ga_zoom',
						'type' => 'switch',
						'title' => __( 'Enable Product Image Hover Zoom', 'kadence-woo-extras' ),
						'help' => __( 'This allows you to magnify product images without having to click to a lightbox. Note that slider arrows do not show with zoom on.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'product_gallery', '=', '1' ),
					),
					array(
						'id' => 'ga_zoom_type',
						'type' => 'select',
						'title' => __( 'Show image magnification on top of image or beside image?', 'kadence-woo-extras' ),
						'options' => array(
							'window' => __( 'Magnify window is beside of image', 'kadence-woo-extras' ),
							'inner' => __( 'Magnify window is on top of image', 'kadence-woo-extras' ),
						),
						'default' => 'window',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'ga_zoom', '=', '1' ),
						),
					),
					array(
						'id' => 'ga_slider_arrows',
						'type' => 'select',
						'title' => __( 'Show slide arrows on product image?', 'kadence-woo-extras' ),
						'options' => array(
							'false' => __( 'False', 'kadence-woo-extras' ),
							'true' => __( 'True', 'kadence-woo-extras' ),
						),
						'default' => 'false',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'ga_zoom', '!=', '1' ),
							array( 'ga_slider_layout', '!=', 'grid' ),
							array( 'ga_slider_layout', '!=', 'tiles' ),
							array( 'ga_slider_layout', '!=', 'list' ),
						),
					),
					array(
						'id' => 'ga_thumb_hover',
						'type' => 'switch',
						'title' => __( 'Enable thumbnail hover selection.', 'kadence-woo-extras' ),
						'help' => __( 'This changes the main image when you hover over a thumbnail image.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'product_gallery', '=', '1' ),
					),
					array(
						'id' => 'product_gallery_lightbox',
						'type' => 'switch',
						'title' => __( 'Enable Lightbox', 'kadence-woo-extras' ),
						'help' => __( 'This allows users to open a lightbox of your product images on click', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'product_gallery', '=', '1' ),
					),
					array(
						'id' => 'ga_lightbox_skin',
						'type' => 'select',
						'title' => __( 'Lightbox Style', 'kadence-woo-extras' ),
						'options' => array(
							'dark' => __( 'Dark', 'kadence-woo-extras' ),
							'light' => __( 'Light', 'kadence-woo-extras' ),
						),
						'default' => 'false',
						'required' => array(
							array( 'product_gallery', '=', '1' ),
							array( 'ga_zoom', '!=', '1' ),
							array( 'ga_slider_layout', '!=', 'grid' ),
							array( 'ga_slider_layout', '!=', 'tiles' ),
							array( 'ga_slider_layout', '!=', 'list' ),
						),
					),
					array(
						'id' => 'ga_show_caption',
						'type' => 'select',
						'title' => __( 'Show Caption overlay on images', 'kadence-woo-extras' ),
						'options' => array(
							'false' => __( 'False', 'kadence-woo-extras' ),
							'true' => __( 'True', 'kadence-woo-extras' ),
						),
						'default' => 'false',
						'required' => array( 'product_gallery', '=', '1' ),
					),
					array(
						'id' => 'ga_shortcode_option',
						'type' => 'switch',
						'title' => __( 'Enable meta area for shortcode override', 'kadence-woo-extras' ),
						'help' => __( 'This adds a metabox area in each product allowing you to override the gallery with a shortcode or html content.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'product_gallery', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_templates',
				'title' => __( 'Product Templates', 'kadence-woo-extras' ),
				'desc' => __( 'Supercharge the WordPress block editor to create dynamic and attractive product layouts with Kadence Shop Kit’s custom template builder.', 'kadence-woo-extras' ),
				'group' => 'customization',
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/template-preview.svg',
				'fields' => array(
					array(
						'id' => 'product_templates',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Product Templates', 'kadence-woo-extras' ),
						'help' => __( 'This allows you to create custom product templates', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'product_templates_output_info',
						'type' => 'info',
						'title' => __( 'Find product templates under the "Products" menu item in your admin.', 'kadence-woo-extras' ),
						'required' => array( 'product_templates', '=', '1' ),
					),
					array(
						'id' => 'product_template_notices',
						'type' => 'switch',
						'title' => __( 'Add WooCommerce notices to top of single and archive templates?', 'kadence-woo-extras' ),
						'help' => __( 'You can manually add notices using a block, but if you are using the snackbar style notices, this is a simple way to add them.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'product_templates', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_swatches',
				'group' => 'variations',
				'title' => __( 'Variation Swatches', 'kadence-woo-extras' ),
				'desc' => __( 'Display product variations in a way that converts, including color swatches, image swatches, and radio boxes. Set displays on an attribute-by-attribute basis to optimize the best look for each variation.', 'kadence-woo-extras' ),
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/swatches-preview.svg',
				'fields' => array(
					array(
						'id' => 'variation_swatches',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable swatches control with variations', 'kadence-woo-extras' ),
						'help' => __( 'This allows you to display images or colors for variation choices.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id'    => 'single_product_swatch_info',
						'type'  => 'title',
						'title' => __( 'Product Page Settings', 'kadence-woo-extras' ),
						'required' => array( 'variation_swatches', '=', '1' ),
					),
					array(
						'id' => 'variation_label_placement',
						'type' => 'select',
						'title' => __( 'Variation label display', 'kadence-woo-extras' ),
						'help' => __( 'Control how the variation label is displayed on the product page', 'kadence-woo-extras' ),
						'default' => ( isset( $shopkit_settings['variation_label'] ) && true == $shopkit_settings['variation_label'] ? 'above' : 'default' ),
						'options' => array(
							'default' => __( 'Default', 'kadence-woo-extras' ),
							'above' => __( 'Above the variation', 'kadence-woo-extras' ),
							'hidden' => __( 'Hidden', 'kadence-woo-extras' ),
						),
						'required' => array( 'variation_swatches', '=', '1' ),
					),
					array(
						'id' => 'swatches_type',
						'type' => 'select',
						'title' => __( 'Choose a default swatch type', 'kadence-woo-extras' ),
						'options' => array(
							'dropdown' => __( 'Dropdown Select Box', 'kadence-woo-extras' ),
							'radio_box' => __( 'Radio Boxes', 'kadence-woo-extras' ),
							'color_image' => __( 'Image and Color swatches', 'kadence-woo-extras' ),
							'taxonomy' => __( 'Taxonomy defined', 'kadence-woo-extras' ),
						),
						'help' => __( 'This can be overridden in each product.', 'kadence-woo-extras' ),
						'default' => 'dropdown',
						'required' => array( 'variation_swatches', '=', '1' ),
					),
					array(
						'id' => 'swatches_label',
						'type' => 'select',
						'title' => __( 'Choose a default swatch label option', 'kadence-woo-extras' ),
						'help' => __( 'This can be overridden in each product.', 'kadence-woo-extras' ),
						'options' => array(
							'false' => __( 'No label', 'kadence-woo-extras' ),
							'above' => __( 'Show above', 'kadence-woo-extras' ),
							'below' => __( 'Show below', 'kadence-woo-extras' ),
							'tooltip' => __( 'Show above on hover', 'kadence-woo-extras' ),
						),
						'default' => 'false',
						'required' => array( 'variation_swatches', '=', '1' ),
					),
					array(
						'id'      => 'swatches_size',
						'type'    => 'radio_select',
						'title'   => __( 'Choose a default swatch size option', 'kadence-woo-extras' ),
						'help'    => __( 'This can be overridden in each product.', 'kadence-woo-extras' ),
						'options' => array(
							array(
								'value' => '16',
								'alt'   => __( '16px', 'kadence-woo-extras' ),
								'name'  => __( 'xxs', 'kadence-woo-extras' ),
							),
							array(
								'value' => '30',
								'alt'   => __( '30px', 'kadence-woo-extras' ),
								'name'  => __( 'xs', 'kadence-woo-extras' ),
							),
							array(
								'value' => '40',
								'alt'   => __( '40px', 'kadence-woo-extras' ),
								'name'  => __( 'sm', 'kadence-woo-extras' ),
							),
							array(
								'value' => '50',
								'alt'   => __( '50px', 'kadence-woo-extras' ),
								'name'  => __( 'md', 'kadence-woo-extras' ),
							),
							array(
								'value' => '60',
								'alt'   => __( '60px', 'kadence-woo-extras' ),
								'name'  => __( 'lg', 'kadence-woo-extras' ),
							),
							array(
								'value' => '75',
								'alt'   => __( '75px', 'kadence-woo-extras' ),
								'name'  => __( 'xl', 'kadence-woo-extras' ),
							),
							array(
								'value' => '90',
								'alt'   => __( '90px', 'kadence-woo-extras' ),
								'name'  => __( 'xxl', 'kadence-woo-extras' ),
							),
							array(
								'value' => '120',
								'alt'   => __( '120px', 'kadence-woo-extras' ),
								'name'  => __( '3xl', 'kadence-woo-extras' ),
							),
							array(
								'value' => '150',
								'alt'   => __( '150px', 'kadence-woo-extras' ),
								'name'  => __( '4xl', 'kadence-woo-extras' ),
							),
						),
						'default' => '50',
						'required' => array( 'variation_swatches', '=', '1' ),
					),
					array(
						'id' => 'choose_option_text',
						'type' => 'text',
						'title' => __( 'Dropdown "Choose an option" text', 'kadence-woo-extras' ),
						'help' => __( 'Standard: Choose an option', 'kadence-woo-extras' ),
						'required' => array( 'variation_swatches', '=', '1' ),
					),
					// array(
					// 	'id'    => 'style_product_swatch_info',
					// 	'type'  => 'title',
					// 	'title' => __( 'Style Settings', 'kadence-woo-extras' ),
					// 	'required' => array( 'variation_swatches', '=', '1' ),
					// ),
					// array(
					// 	'id'       => 'swatches_border_radius',
					// 	'type'     => 'range',
					// 	'title'    => __( 'Swatch Border Radius', 'kadence-woo-extras' ),
					// 	'default'  => '0',
					// 	'min'      => '0',
					// 	'step'     => '1',
					// 	'max'      => '100',
					// 	'required' => array( 
					// 		array( 'variation_swatches', '=', '1' ),
					// 		array( 'variation_archive_swatches', '=', '1' ),
					// 	),
					// ),
					array(
						'id'    => 'archive_product_swatch_info',
						'type'  => 'title',
						'title' => __( 'Archive Settings', 'kadence-woo-extras' ),
						'required' => array( 'variation_swatches', '=', '1' ),
					),
					array(
						'id' => 'variation_archive_swatches',
						'type' => 'switch',
						'title' => __( 'Enable swatches for Archives', 'kadence-woo-extras' ),
						'help' => __( 'Display variation swatches with all product loops on shop / archive pages.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'variation_swatches', '=', '1' ),
					),
					array(
						'id' => 'variation_archive_swatches_quickview',
						'type' => 'switch',
						'title' => __( 'Enable swatches for Archives when using quickview', 'kadence-woo-extras' ),
						'help' => __( 'Disable this if you want to show the variation swatches in quickview, but not the Archive product loops.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array(
							array( 'variation_swatches', '=', '1' ),
							array( 'variation_archive_swatches', '=', '1' ),
							array( 'product_quickview', '=', '1' ),
						),
					),
					array(
						'id' => 'variation_archive_swatches_mode',
						'type' => 'select',
						'title' => __( 'Archive Swatch Mode', 'kadence-woo-extras' ),
						'help' => __( 'Select how swatches work on shop / archive pages. Catalog Mode will only show one attribute.', 'kadence-woo-extras' ),
						'options' => array(
							'normal' => __( 'Normal', 'kadence-woo-extras' ),
							'catalog' => __( 'Catalog Mode', 'kadence-woo-extras' ),
						),
						'default' => 'normal',
						'required' => array( 
							array( 'variation_swatches', '=', '1' ),
							array( 'variation_archive_swatches', '=', '1' ),
						),
					),
					array(
						'id' => 'variation_archive_swatches_catalog_attribute',
						'type' => 'select',
						'title' => __( 'Catalog Attribute', 'kadence-woo-extras' ),
						'help' => __( 'Select which attribute to show for catalog mode.', 'kadence-woo-extras' ),
						'options' => array( '' => __( 'First Attribute', 'kadence-woo-extras' ) ),
						'default' => '',
						'required' => array( 
							array( 'variation_swatches', '=', '1' ),
							array( 'variation_archive_swatches', '=', '1' ),
							array( 'variation_archive_swatches_mode', '=', 'catalog' ),
						),
					),
					array(
						'id' => 'variation_archive_swatches_placement',
						'type' => 'select',
						'title' => __( 'Swatch Position', 'kadence-woo-extras' ),
						'help' => __( 'Select where the swatches display on archives', 'kadence-woo-extras' ),
						'options' => array(
							'above_title' => __( 'Before Title', 'kadence-woo-extras' ),
							'below_title' => __( 'After Title', 'kadence-woo-extras' ),
							'above_price' => __( 'Before Price', 'kadence-woo-extras' ),
							'below_price' => __( 'After Price', 'kadence-woo-extras' ),
						),
						'default' => 'below_price',
						'required' => array( 
							array( 'variation_swatches', '=', '1' ),
							array( 'variation_archive_swatches', '=', '1' ),
						),
					),
					array(
						'id' => 'variation_archive_label_placement',
						'type' => 'select',
						'title' => __( 'Variation label display', 'kadence-woo-extras' ),
						'help' => __( 'Control how the variation label is displayed on the product page', 'kadence-woo-extras' ),
						'default' => 'hidden',
						'options' => array(
							'default' => __( 'Beside Variation', 'kadence-woo-extras' ),
							'above' => __( 'Above the variation', 'kadence-woo-extras' ),
							'hidden' => __( 'Hidden', 'kadence-woo-extras' ),
						),
						'required' => array( 
							array( 'variation_swatches', '=', '1' ),
							array( 'variation_archive_swatches', '=', '1' ),
						),
					),
					array(
						'id'      => 'archive_swatches_size',
						'type'    => 'radio_select',
						'title'   => __( 'Choose a default swatch size option', 'kadence-woo-extras' ),
						'help'    => __( 'This can be overridden in each product.', 'kadence-woo-extras' ),
						'options' => array(
							array(
								'value' => '16',
								'alt'   => __( '16px', 'kadence-woo-extras' ),
								'name'  => __( 'xxs', 'kadence-woo-extras' ),
							),
							array(
								'value' => '30',
								'alt'   => __( '30px', 'kadence-woo-extras' ),
								'name'  => __( 'xs', 'kadence-woo-extras' ),
							),
							array(
								'value' => '40',
								'alt'   => __( '40px', 'kadence-woo-extras' ),
								'name'  => __( 'sm', 'kadence-woo-extras' ),
							),
							array(
								'value' => '50',
								'alt'   => __( '50px', 'kadence-woo-extras' ),
								'name'  => __( 'md', 'kadence-woo-extras' ),
							),
							array(
								'value' => '60',
								'alt'   => __( '60px', 'kadence-woo-extras' ),
								'name'  => __( 'lg', 'kadence-woo-extras' ),
							),
							array(
								'value' => '75',
								'alt'   => __( '75px', 'kadence-woo-extras' ),
								'name'  => __( 'xl', 'kadence-woo-extras' ),
							),
							array(
								'value' => '90',
								'alt'   => __( '90px', 'kadence-woo-extras' ),
								'name'  => __( 'xxl', 'kadence-woo-extras' ),
							),
							array(
								'value' => '120',
								'alt'   => __( '120px', 'kadence-woo-extras' ),
								'name'  => __( '3xl', 'kadence-woo-extras' ),
							),
							array(
								'value' => '150',
								'alt'   => __( '150px', 'kadence-woo-extras' ),
								'name'  => __( '4xl', 'kadence-woo-extras' ),
							),
						),
						'default' => '30',
						'required' => array( 
							array( 'variation_swatches', '=', '1' ),
							array( 'variation_archive_swatches', '=', '1' ),
						),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_reviews',
				'title' => __( 'Advanced Reviews', 'kadence-woo-extras' ),
				'desc' => __( 'Create better social proof with improved reviews, add titles, voting capabilities, and order reviews to show the highest-voted reviews first. Plus display a review overview board.' ),
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/review-preview.svg',
				'group' => 'features',
				'fields' => array(
					array(
						'id' => 'kt_reviews',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Advanced Reviews', 'kadence-woo-extras' ),
						'help' => __( 'This allows you to change the review order as well as have titles and vote on reviews.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'kt_reviews_order',
						'type' => 'select',
						'title' => __( 'Select Review Order', 'kadence-woo-extras' ),
						'options' => array(
							'votes_desc' => __( 'Order by most helpful with fallback newest first', 'kadence-woo-extras' ),
							'desc' => __( 'Newest First', 'kadence-woo-extras' ),
							'asc' => __( 'Oldest First', 'kadence-woo-extras' ),
						),
						'default' => 'votes_desc',
						'width' => 'width:60%',
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'kt_review_title',
						'type' => 'switch',
						'title' => __( 'Enable Review Titles', 'kadence-woo-extras' ),
						'help' => __( 'This allows you to have titles for reviews.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'kt_review_consent',
						'type' => 'switch',
						'title' => __( 'Enable Review Consent Checkbox', 'kadence-woo-extras' ),
						'help' => __( 'This adds a consent to privacy policy checkbox', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'kt_reviews_multi',
						'type' => 'switch',
						'title' => __( 'When using polylang or WPML show reviews in every language.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'kt_review_voting',
						'type' => 'switch',
						'title' => __( 'Enable Review Voting', 'kadence-woo-extras' ),
						'help' => __( 'This allows users to upvote reviews that are more helpful.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'vote_loggedin_only',
						'type' => 'switch',
						'title' => __( 'Voters must be logged in?', 'kadence-woo-extras' ),
						'help' => __( 'With this turned on only logged in users will be able to vote.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'kt_review_overview',
						'type' => 'switch',
						'title' => __( 'Show Review Overview?', 'kadence-woo-extras' ),
						'help' => __( 'With this turned on there will be overview of all reviews.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'kt_review_overview_highlight',
						'type' => 'color',
						'title' => __( 'Overview highlight color', 'kadence-woo-extras' ),
						'default' => '#2d5c88',
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'kt_reviews_featured',
						'type' => 'switch',
						'title' => __( 'Enabled Featured Reviews', 'kadence-woo-extras' ),
						'help' => __( 'With this turned on you can set reviews to be featured and show at the top of your list.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'kt_reviews_limited',
						'type' => 'switch',
						'title' => __( 'Enabled Load More for Reviews', 'kadence-woo-extras' ),
						'help' => __( 'With this turned on reviews will only load the inital amount and a load more button will show.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_reviews', '=', '1' ),
					),
					array(
						'id' => 'kt_reviews_limited_readmore',
						'type' => 'text',
						'title' => __( 'Read More Reviews Button Text', 'kadence-woo-extras' ),
						'help' => __( 'Default: Read More Reviews', 'kadence-woo-extras' ),
						'required' => array(
							array( 'kt_reviews', '=', '1' ),
							array( 'kt_reviews_limited', '=', '1' ),
						),
					),
					array(
						'id'       => 'kt_reviews_limited_count',
						'type'     => 'range',
						'title'    => __( 'Load more inital and per load amount', 'kadence-woo-extras' ),
						'default'  => '10',
						'min'      => '2',
						'step'     => '1',
						'max'      => '100',
						'required' => array(
							array( 'kt_reviews', '=', '1' ),
							array( 'kt_reviews_limited', '=', '1' ),
						),
					),
					array(
						'id' => 'kt_review_convert',
						'type' => 'raw',
						'title' => __( 'Convert previous reviews to Advanced Reviews', 'kadence-woo-extras' ),
						'help' => __( 'This will convert all your previous reviews into advanced.', 'kadence-woo-extras' ),
						'content'  => ( isset( $shopkit_settings['kt_reviews'] ) && true == $shopkit_settings['kt_reviews'] ? '<button id="kt-review-convert" class="button-primary kt-review-convert" onClick="KadenceConvertButton();"style="margin:10px 0;">Convert Reviews<span class="spinner-item"></span></button><p class="convert-info"></p>' : '<div id="kadence-convert-info" class="convert-info"><p class="kadence-settings-notice">Save with advanced reviews enabled and reload the page to show the convert button.</p></div>' ),
						'required' => array( 'kt_reviews', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_brands_options',
				'title' => __( 'Product Brands', 'kadence-woo-extras' ),
				'desc' => __( 'Leverage brand influence on product displays, choosing where to show on archive and single product pages. Show recognizable logos, or just the brand name. Add a filter-by-brands widget to your site.', 'kadence-woo-extras' ),
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/brands-preview.svg',
				'group' => 'features',
				'fields' => array(
					array(
						'id' => 'kt_product_brands_options',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Product Brands Options', 'kadence-woo-extras' ),
						'help' => __( 'This adds a new Taxonomy for Products', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'product_brands_singular',
						'type' => 'text',
						'title' => __( 'Singular Custom Name', 'kadence-woo-extras' ),
						'help' => __( 'Default: Product Brand', 'kadence-woo-extras' ),
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_plural',
						'type' => 'text',
						'title' => __( 'Plural Custom Name', 'kadence-woo-extras' ),
						'help' => __( 'Default: Product Brands', 'kadence-woo-extras' ),
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_slug',
						'type' => 'text',
						'title' => __( 'URL Custom slug', 'kadence-woo-extras' ),
						'help' => __( 'Default: product-brands (lowercase, no spaces)', 'kadence-woo-extras' ),
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_output_info',
						'type' => 'info',
						'title' => __( 'Product Brands Single Output settings', 'kadence-woo-extras' ),
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_single_output',
						'type' => 'select',
						'title' => __( 'Single Product Page Output', 'kadence-woo-extras' ),
						'options' => array(
							'none' => __( 'None', 'kadence-woo-extras' ),
							'above_title' => __( 'Above Title', 'kadence-woo-extras' ),
							'above_price' => __( 'Below Title, Above Price', 'kadence-woo-extras' ),
							'above_excerpt' => __( 'Below Price, Above Short Description', 'kadence-woo-extras' ),
							'above_addtocart' => __( 'Below Short Description, Above Add to cart', 'kadence-woo-extras' ),
							'above_meta' => __( 'Below Add to cart, Above meta content', 'kadence-woo-extras' ),
							'below_meta' => __( 'Below meta content', 'kadence-woo-extras' ),
						),
						'default' => 'none',
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_single_output_style',
						'type' => 'select',
						'title' => __( 'Show as text or image', 'kadence-woo-extras' ),
						'options' => array(
							'image' => __( 'Image', 'kadence-woo-extras' ),
							'text' => __( 'Text', 'kadence-woo-extras' ),
						),
						'default' => 'image',
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_single_output_width',
						'title' => __( 'Single Product Page Brand Image Width', 'kadence-woo-extras' ),
						'type' => 'range',
						'default'   => '200',
						'min'       => '40',
						'step'      => '2',
						'max'       => '1400',
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_single_output_cropped',
						'type' => 'switch',
						'title' => __( 'Enable to Hard Crop Image', 'kadence-woo-extras' ),
						'help' => __( 'If enabled you can force a specific height to hard crop to.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_single_output_height',
						'title' => __( 'Single Product Page Brand Image Height', 'kadence-woo-extras' ),
						'type' =>'range',
						'default'   => '200',
						'min'       => '40',
						'step'      => '2',
						'max'       => '1400',
						'required' => array(
							array( 'kt_product_brands_options', '=', '1' ),
							array( 'product_brands_single_output_cropped', '=', '1' ),
						),
					),
					array(
						'id' => 'product_brands_single_link',
						'type' => 'switch',
						'title' => __( 'Link Image to Brand Page', 'kadence-woo-extras' ),
						'help' => __( 'Adds Link to brand archive page from image.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_archive_output_info',
						'type' => 'info',
						'title' => __( 'Product Brands Archive Output settings', 'kadence-woo-extras' ),
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_archive_output',
						'type' => 'select',
						'title' => __( 'Archive Page Output', 'kadence-woo-extras' ),
						'options' => array(
							'none' => __( 'None', 'kadence-woo-extras' ),
							'above_image' => __( 'Above image', 'kadence-woo-extras' ),
							'above_title' => __( 'Below image, Above title', 'kadence-woo-extras' ),
							'above_excerpt' => __( 'Below title, Above excerpt', 'kadence-woo-extras' ),
							'above_price' => __( 'Below excerpt, Above price', 'kadence-woo-extras' ),
							'above_addtocart' => __( 'Below price, Above add to cart', 'kadence-woo-extras' ),
							'below_addtocart' => __( 'Below add to cart', 'kadence-woo-extras' ),
						),
						'default' => 'none',
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_archive_output_style',
						'type' => 'select',
						'title' => __( 'Show as text or image', 'kadence-woo-extras' ),
						'options' => array(
							'image' => __( 'Image', 'kadence-woo-extras' ),
							'text' => __( 'Text', 'kadence-woo-extras' ),
						),
						'default' => 'image',
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_archive_output_width',
						'title' => __( 'Archive Page Brand Image Width', 'kadence-woo-extras' ),
						'type' => 'range',
						'default'   => '200',
						'min'       => '40',
						'step'      => '2',
						'max'       => '1400',
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_archive_output_cropped',
						'type' => 'switch',
						'title' => __( 'Enable to Hard Crop Image', 'kadence-woo-extras' ),
						'help' => __( 'If enabled you can force a specific height to hard crop to.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),
					array(
						'id' => 'product_brands_archive_output_height',
						'title' => __( 'Archive Page Brand Image Height', 'kadence-woo-extras' ),
						'type' => 'range',
						'default'   => '200',
						'min'       => '40',
						'step'      => '2',
						'max'       => '1400',
						'required' => array(
							array( 'kt_product_brands_options', '=', '1' ),
							array( 'product_brands_archive_output_cropped', '=', '1' ),
						),
					),
					array(
						'id' => 'product_brands_archive_link',
						'type' => 'switch',
						'title' => __( 'Link Image to Brand Page', 'kadence-woo-extras' ),
						'help' => __( 'Adds Link to brand archive page from image.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_product_brands_options', '=', '1' ),
					),

				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_badges_options',
				'title' => __( 'Product Badges', 'kadence-woo-extras' ),
				'desc' => __( 'Add custom badges to products in your shop and single product pages. Highlight sale items or special categories.', 'kadence-woo-extras' ),
				//'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/brands-preview.svg',
				'group' => 'features',
				'fields' => array(
					array(
						'id' => 'kt_product_badges_options',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Product Badges Options', 'kadence-woo-extras' ),
						'help' => __( 'This adds a new post type to your site.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'kt_product_badges_disable_sale_flash',
						'type' => 'switch',
						'title' => __( 'Disable Woocommerce Sale Flash', 'kadence-woo-extras' ),
						'help' => __( 'WooCommerce displays a sale notice on products by default. This notice can overlap with badges. This setting disables the notice where badges may be active.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_product_badges_options', '=', '1' ),
					),
					array(
						'id' => 'kt_product_badges_info',
						'type' => 'info',
						'title' => __( 'Find Badges under the "Products" Menu item in your admin.', 'kadence-woo-extras' ),
						'required' => array( 'kt_product_badges_options', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_quickview',
				'title' => __( 'Product Quickview', 'kadence-woo-extras' ),
				'desc' => __( 'Let customers quickly preview products on the shop and category pages. (other areas optional)', 'kadence-woo-extras' ),
				'group' => 'features',
				//'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/gallery-preview.svg',
				'fields' => array(
					array(
						'id' => 'product_quickview',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Product Quickview', 'kadence-woo-extras' ),
						'help' => __( 'This adds quickview buttons to product listings', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'product_quickview_in_blocks',
						'type' => 'switch',
						'title' => __( 'Quickview Block', 'kadence-woo-extras' ),
						'help' => __( 'Adds a quickview button block to add quickview in any block content or template such as woocommmerce product collection blocks.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array(
							array( 'product_quickview', '=', '1' ),
						),
					),
					array(
						'id' => 'product_quickview_auto',
						'type' => 'switch',
						'title' => __( 'Automatically insert button', 'kadence-woo-extras' ),
						'help' => __( 'Quickview will attempt to automatically insert the Quickview button. Disable this if you wish to place quickview buttons yourself via shortcode [kt_quickview_button] or block.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_auto_placement',
						'type' => 'select',
						'title' => __( 'Button Placement', 'kadence-woo-extras' ),
						'options' => array(
							'' => __( 'After Add to Cart', 'kadence-woo-extras' ),
							'before-add' => __( 'Before add to Cart', 'kadence-woo-extras' ),
							'above-title' => __( 'Above Title', 'kadence-woo-extras' ),
							'below-title' => __( 'Below Title', 'kadence-woo-extras' ),
							'before-item' => __( 'Before Item', 'kadence-woo-extras' ),
							'after-item' => __( 'After Item', 'kadence-woo-extras' ),
							'above-price' => __( 'Above Price', 'kadence-woo-extras' ),
							'below-price' => __( 'Below Price', 'kadence-woo-extras' ),
						),
						'default' => '',
						'required' => array(
							array( 'product_quickview', '=', '1' ),
							array( 'product_quickview_auto', '!=', '0' ),
						),
					),
					array(
						'id' => 'product_quickview_auto_in_single',
						'type' => 'switch',
						'title' => __( 'Add quickview to single product pages', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array(
							array( 'product_quickview', '=', '1' ),
							array( 'product_quickview_auto', '!=', '0' ),
						),
					),
					array(
						'id' => 'product_quickview_show_label',
						'type' => 'switch',
						'title' => __( 'Quickview Button Show Text', 'kadence-woo-extras' ),
						'default' => '1',
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_label',
						'type' => 'text',
						'title' => __( 'Quickview Button Text', 'kadence-woo-extras' ),
						'help' => __( 'Default: Quickview', 'kadence-woo-extras' ),
						'required' => array(
							array( 'product_quickview', '=', '1' ),
							array( 'product_quickview_show_label', '!=', '0' ),
						),
					),
					array(
						'id' => 'product_quickview_button_icon',
						'type' => 'switch',
						'title' => __( 'Add a Button Icon', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_button_icon_choice',
						'type' => 'select',
						'options' => array(
							'eye' => __( 'Eye', 'kadence-woo-extras' ),
							'play' => __( 'Play', 'kadence-woo-extras' ),
							'click' => __( 'Click', 'kadence-woo-extras' ),
						),
						'title' => __( 'Button Icon', 'kadence-woo-extras' ),
						'default' => 'eye',
						'required' => array(
							array( 'product_quickview', '=', '1' ),
							array( 'product_quickview_button_icon', '==', '1' ),
						),
					),
					array(
						'id' => 'product_quickview_button_icon_color',
						'type' => 'color',
						'title' => __( 'Button Icon Color', 'kadence-woo-extras' ),
						'default' => '',
						'required' => array(
							array( 'product_quickview', '=', '1' ),
							array( 'product_quickview_button_icon', '==', '1' ),
						),
					),
					array(
						'id' => 'product_quickview_button_icon_color_hover',
						'type' => 'color',
						'title' => __( 'Button Icon Color Hover', 'kadence-woo-extras' ),
						'default' => '',
						'required' => array(
							array( 'product_quickview', '=', '1' ),
							array( 'product_quickview_button_icon', '==', '1' ),
						),
					),
					// array(
					// 	'id' => 'product_quickview_button_reveal_hover',
					// 	'type' => 'switch',
					// 	'title' => __( 'Reveal button on hover', 'kadence-woo-extras' ),
					// 	'default' => 0,
					// 	'required' => array( 'product_quickview', '=', '1' ),
					// ),
					array(
						'id' => 'product_quickview_content_info',
						'type' => 'info',
						'title' => __( 'WARNING! Your quickview button has no text or icon content. It will not be displayed.', 'kadence-woo-extras' ),
						'required' => array(
							array( 'product_quickview', '=', '1' ),
							array( 'product_quickview_show_label', '==', '0' ),
							array( 'product_quickview_button_icon', '==', '0' ),
						),
					),
					array(
						'id' => 'product_quickview_button_color',
						'type' => 'color',
						'title' => __( 'Button Text Color', 'kadence-woo-extras' ),
						'default' => '',
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_button_color_hover',
						'type' => 'color',
						'title' => __( 'Button Text Color Hover', 'kadence-woo-extras' ),
						'default' => '',
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_button_background',
						'type' => 'color',
						'title' => __( 'Button Background', 'kadence-woo-extras' ),
						'default' => '',
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_button_background_hover',
						'type' => 'color',
						'title' => __( 'Button Background Hover', 'kadence-woo-extras' ),
						'default' => '',
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_button_border_width',
						'type' => 'range',
						'title' => __( 'Button Border Width', 'kadence-woo-extras' ),
						'default' => '',
						'min'       => '0',
						'step'      => '1',
						'max'       => '50',
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_button_border_color',
						'type' => 'color',
						'title' => __( 'Button Border Color', 'kadence-woo-extras' ),
						'default' => '',
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_button_border_color_hover',
						'type' => 'color',
						'title' => __( 'Button Border Color Hover', 'kadence-woo-extras' ),
						'default' => '',
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_button_border_radius',
						'type' => 'range',
						'title' => __( 'Button Border Radius', 'kadence-woo-extras' ),
						'default' => '',
						'min'       => '0',
						'step'      => '1',
						'max'       => '200',
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_slides',
						'type' => 'switch',
						'title' => __( 'Allow scrolling', 'kadence-woo-extras' ),
						'help' => __( 'When quickview opens, users can "scroll" with arrows or "swipe" to the next product in the list.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_use_template',
						'type' => 'switch',
						'title' => __( 'Use a Woo Template', 'kadence-woo-extras' ),
						'help' => __( 'Enable Woo Templates to customize the appearance of your quick view, disable to use Shop Kit’s default', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'product_quickview', '=', '1' ),
					),
					array(
						'id' => 'product_quickview_template',
						'type' => 'select',
						'title' => __( 'Quickview Template', 'kadence-woo-extras' ),
						'options' => $this->get_quickview_templates_options(),
						'default' => '',
						'required' => array(
							array( 'product_quickview', '=', '1' ),
							array( 'product_quickview_use_template', '==', '1' ),
						),
					),
					array(
						'id' => 'product_quickview_template_info',
						'type' => 'info',
						'title' => __( 'Create Woo Templates in the "Products" Menu item in your admin. Select "Quickview" when making the new template.', 'kadence-woo-extras' ),
						'required' => array(
							array( 'product_quickview', '=', '1' ),
							array( 'product_quickview_use_template', '==', '1' ),
						),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_affiliate',
				'title' => __( 'Affiliate Product Options', 'kadence-woo-extras' ),
				'group' => 'features',
				'desc' => __( 'Grow your revenue capabilities by unlocking affiliate sales with the Shop Kit affiliate product options.', 'kadence-woo-extras' ),
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/affiliate-product-preview.svg',
				'fields' => array(
					array(
						'id' => 'kt_affiliate_options',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Affiliate/External Product Options', 'kadence-woo-extras' ),
						'help' => __( 'This gives you control to add direct links for product images and archive action buttons.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'affiliate_archive_info',
						'type' => 'info',
						'title' => __( 'Product Archive Settings', 'kadence-woo-extras' ),
						'required' => array( 'kt_affiliate_options', '=', '1' ),
					),
					array(
						'id' => 'kt_aa_image_link',
						'type' => 'switch',
						'title' => __( 'Enable Affiliate link for Product Images', 'kadence-woo-extras' ),
						'help' => __( 'This makes the product image link to the affilate instead of the single product page.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_affiliate_options', '=', '1' ),
					),
					array(
						'id' => 'kt_aa_image_link_target',
						'type' => 'switch',
						'title' => __( 'Enable Affiliate Product Image link to opens new browser tab', 'kadence-woo-extras' ),
						'help' => __( 'This makes the product image link open a new tab.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'kt_aa_image_link', '=', '1' ),
					),
					array(
						'id' => 'kt_aa_title_link',
						'type' => 'switch',
						'title' => __( 'Enable Affiliate link for Product Title', 'kadence-woo-extras' ),
						'help' => __( 'This makes the product title link to the affilate instead of the single product page.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_aa_image_link', '=', '1' ),
					),
					array(
						'id' => 'kt_aa_title_link_target',
						'type' => 'switch',
						'title' => __( 'Enable Affiliate Product Title link to opens new browser tab', 'kadence-woo-extras' ),
						'help' => __( 'This makes the product title link open a new tab.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'kt_aa_title_link', '=', '1' ),
					),
					array(
						'id' => 'kt_aa_action_link_target',
						'type' => 'switch',
						'title' => __( 'Enable Affiliate Button link to open new browser tab', 'kadence-woo-extras' ),
						'help' => __( 'This makes the product button link open a new tab.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'kt_affiliate_options', '=', '1' ),
					),
					array(
						'id' => 'affiliate_single_info',
						'type' => 'info',
						'help' => __( 'Product Single Settings', 'kadence-woo-extras' ),
						'required' => array( 'kt_affiliate_options', '=', '1' ),
					),
					array(
						'id' => 'kt_single_aa_image_link',
						'type' => 'switch',
						'title' => __( 'Enable Affiliate link for Product Images', 'kadence-woo-extras' ),
						'help' => __( 'This makes the product image link to the affiliate instead of the lightbox.', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'kt_affiliate_options', '=', '1' ),
					),
					array(
						'id' => 'kt_single_aa_image_link_target',
						'type' => 'switch',
						'title' => __( 'Enable Affiliate Product Image link to opens new browser tab', 'kadence-woo-extras' ),
						'help' => __( 'This makes the product image link open a new tab.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'kt_single_aa_image_link', '=', '1' ),
					),
					array(
						'id' => 'kt_single_aa_action_link_target',
						'type' => 'switch',
						'title' => __( 'Enable Affiliate Button link to open new browser tab', 'kadence-woo-extras' ),
						'help' => __( 'This makes the product button link open a new tab.', 'kadence-woo-extras' ),
						'default' => 1,
						'required' => array( 'kt_affiliate_options', '=', '1' ),
					),
				),
			)
		);

		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_add_to_cart',
				'title' => __( 'Add to Cart Text', 'kadence-woo-extras' ),
				'desc' => __( 'Build unique and creative custom text labels for a more personalized shopping experience. "Add to Cart" easily becomes "Buy Shoes." Take the mystery out of variable products setting specific archives names instead of "select options."', 'kadence-woo-extras' ),
				'group' => 'checkout',
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/add-to-cart-preview.svg',
				'fields' => array(
					array(
						'id' => 'kt_add_to_cart_text',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Custom add to cart text', 'kadence-woo-extras' ),
						'help' => __( 'This allows you to change the text for the add to cart buttons.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'add_to_cart_text_info',
						'type' => 'info',
						'title' => __( 'Archive Add to Cart', 'kadence-woo-extras' ),
						'required' => array( 'kt_add_to_cart_text', '=', '1' ),
					),
					array(
						'id' => 'add_to_cart_text',
						'type' => 'text',
						'title' => __( 'Simple Product', 'kadence-woo-extras' ),
						'help' => __( 'Default: Add to cart', 'kadence-woo-extras' ),
						'required' => array( 'kt_add_to_cart_text', '=', '1' ),
					),
					array(
						'id' => 'variable_add_to_cart_text',
						'type' => 'text',
						'title' => __( 'Variable Product', 'kadence-woo-extras' ),
						'help' => __( 'Default: Select options', 'kadence-woo-extras' ),
						'required' => array( 'kt_add_to_cart_text', '=', '1' ),
					),
					array(
						'id' => 'grouped_add_to_cart_text',
						'type' => 'text',
						'title' => __( 'Grouped Product', 'kadence-woo-extras' ),
						'help' => __( 'Default: View Products', 'kadence-woo-extras' ),
						'required' => array( 'kt_add_to_cart_text', '=', '1' ),
					),
					array(
						'id' => 'out_add_to_cart_text',
						'type' => 'text',
						'title' => __( 'Out of Stock Product', 'kadence-woo-extras' ),
						'help' => __( 'Default: Read More', 'kadence-woo-extras' ),
						'required' => array( 'kt_add_to_cart_text', '=', '1' ),
					),
					array(
						'id' => 'single_add_to_cart_text_info',
						'type' => 'info',
						'title' => __( 'Single Add to Cart', 'kadence-woo-extras' ),
						'required' => array( 'kt_add_to_cart_text', '=', '1' ),
					),
					array(
						'id' => 'single_add_to_cart_text',
						'type' => 'text',
						'title' => __( 'All Products', 'kadence-woo-extras' ),
						'help' => __( 'Default: Add to cart', 'kadence-woo-extras' ),
						'required' => array( 'kt_add_to_cart_text', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_variations',
				'title' => __( 'Variation Options', 'kadence-woo-extras' ),
				'desc' => __( 'Optionally change how your variable products display pricing by showing either the lowest or highest price in the variable product. You can also add text before or after the price, for example "Base Price: $45."', 'kadence-woo-extras' ),
				'group' => 'variations',
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/variation-preview.svg',
				'fields' => array(
					array(
						'id' => 'variation_price',
						'type' => 'select',
						'title' => __( 'Variation Price Output', 'kadence-woo-extras' ),
						'options' => array(
							'normal' => __( 'Show Price Range ($lowest - $highest)', 'kadence-woo-extras' ),
							'lowprice' => __( 'Show lowest price only', 'kadence-woo-extras' ),
							'highprice' => __( 'Show highest price only', 'kadence-woo-extras' ),
						),
						'default' => 'normal',
					),
					array(
						'id' => 'before_variation_price',
						'type' => 'text',
						'title' => __( 'Archive Text Before Variation Price', 'kadence-woo-extras' ),
						'help' => __( 'Example: From:', 'kadence-woo-extras' ),
						'required' => array( 'variation_price', '!=', 'normal' ),
					),
					array(
						'id' => 'after_variation_price',
						'type' => 'text',
						'title' => __( 'Archive Text After Variation Price', 'kadence-woo-extras' ),
						'help' => __( 'Example: Base Price', 'kadence-woo-extras' ),
						'required' => array( 'variation_price', '!=', 'normal' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_charts',
				'title' => __( 'Size Charts', 'kadence-woo-extras' ),
				'desc' => __( 'Simplify sizing decisions with easy-to-build size charts that can be applied to one product or to a whole product category. Add size charts to the product page as a tab, or use a button that opens a modal with your site chart.', 'kadence-woo-extras' ),
				'group' => 'features',
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/size-chart-preview.svg',
				'fields' => array(
					array(
						'id' => 'size_charts',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Size Charts', 'kadence-woo-extras' ),
						'help' => __( 'This gives you options to add popup size charts to products.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'size_charts_output_info',
						'type' => 'info',
						'title' => __( 'Find Size Charts under the "Products" menu item in your admin.', 'kadence-woo-extras' ),
						'required' => array( 'size_charts', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_global_tab',
				'title' => __( 'Global Tabs', 'kadence-woo-extras' ),
				'desc' => __( 'Build dynamic informational tabs using the WordPress block editor to unlock any kind of global tab layout you desire with Shop Kit\'s custom template builder.', 'kadence-woo-extras' ),
				'group' => 'features',
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/tabs-preview.svg',
				'fields' => array(
					array(
						'id'       => 'kt_global_tabs',
						'type'     => 'switch',
						'class' => 'ks-large-toggle',
						'title'    => __( 'Enable Global Tabs', 'kadence-woo-extras' ),
						'help' => __( 'This allows you to create tabs and apply them globally to products', 'kadence-woo-extras' ),
						'default'  => 0,
					),
					array(
						'id' => 'global_tabs_output_info',
						'type' => 'info',
						'title' => __( 'Find Global Tabs under the "Products" Menu item in your admin.', 'kadence-woo-extras' ),
						'required' => array( 'kt_global_tabs', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_snackbar_notices',
				'title' => __( 'Snackbar Style Notices', 'kadence-woo-extras' ),
				'desc' => __( 'Display succinct messages that clear after a short delay to encourage desired shopper behavior or provide helpful yet non-intrusive confirmation notices. Helpful for lightweight yet helpful messages such as add to cart confirmation and more.', 'kadence-woo-extras' ),
				'group' => 'checkout',
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/snack-bar-preview.svg',
				'fields' => array(
					array(
						'id' => 'snackbar_notices',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Snackbar Style Notices', 'kadence-woo-extras' ),
						'help' => __( 'This changes the default notice style in woocommerce from a banner in the page content to a fixed slide up that is dismissible. </br>This feature only affects notices in the classic checkout and cart experience. It will not affect notices in the cart or checkout blocks.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'snackbar_cart',
						'type' => 'switch',
						'title' => __( 'Enable Snackbar Style Notices for Cart Page', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'snackbar_notices', '=', '1' ),
					),
					array(
						'id' => 'snackbar_checkout',
						'type' => 'switch',
						'title' => __( 'Enable Snackbar Style Notices for Checkout Page', 'kadence-woo-extras' ),
						'default' => 0,
						'required' => array( 'snackbar_notices', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_notices',
				'title' => __( 'Conditional Cart Banners', 'kadence-woo-extras' ),
				'desc' => __( 'Create custom cart messages that target specific carts based on cart contents, total price, or total weight of items in the cart. Generate personalized upsells specific to the shopper\'s experience, or offer promotions to encourage another product purchase or choosing a product with more value.', 'kadence-woo-extras' ),
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/cart-banner-preview.svg',
				'group' => 'checkout',
				'fields' => array(
					array(
						'id' => 'kt_cart_notice',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Cart Banners', 'kadence-woo-extras' ),
						'help' => __( 'This gives you options to add specialized notices to the cart page to entice with specials and upsell products.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'cart_notice_output_info',
						'type' => 'info',
						'title' => __( 'Find Cart Banners under the "Woocommerce" Menu item in your admin.', 'kadence-woo-extras' ),
						'required' => array( 'kt_cart_notice', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_coupon_modal',
				'title' => __( 'Checkout Coupon Modal', 'kadence-woo-extras' ),
				'desc' => __( 'Add a coupon input field as a link in the order overview and open a modal to input the coupon. This feature is only available for the standard checkout, not the checkout block.', 'kadence-woo-extras' ),
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/coupon-modal-preview.svg',
				'group' => 'checkout',
				'fields' => array(
					array(
						'id' => 'kt_coupon_modal_checkout',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Coupon Checkout Modal.', 'kadence-woo-extras' ),
						'help' => __( 'This allows you to display the coupon input field as a link in the order overview and opens a modal to input coupon. Note this only works with standard checkout, not the checkout block.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'checkout_coupon_link_placement',
						'type' => 'select',
						'title' => __( 'Coupon Modal Link Placement', 'kadence-woo-extras' ),
						'options' => array(
							'before_review' => __( 'Before Order Review', 'kadence-woo-extras' ),
							'before_table_total' => __( 'In Order Review table before total', 'kadence-woo-extras' ),
							'after_table_total' => __( 'In Order Review table after total', 'kadence-woo-extras' ),
							'between_review_payment' => __( 'Between Order Review and Payment', 'kadence-woo-extras' ),
							'after_payment' => __( 'After Payment', 'kadence-woo-extras' ),
						),
						'default' => 'between_review_payment',
						'required' => array( 'kt_coupon_modal_checkout', '=', '1' ),
					),
					array(
						'id' => 'checkout_coupon_pre',
						'type' => 'text',
						'title' => __( 'Pre Modal Link Text', 'kadence-woo-extras' ),
						'help' => __( 'Standard: Have a promo code?', 'kadence-woo-extras' ),
						'default' => __( 'Have a promo code?', 'kadence-woo-extras' ),
						'required' => array( 'kt_coupon_modal_checkout', '=', '1' ),
					),
					array(
						'id' => 'checkout_coupon_link',
						'type' => 'text',
						'title' => __( 'Modal Link Text', 'kadence-woo-extras' ),
						'help' => __( 'Standard: Click here to enter your code.', 'kadence-woo-extras' ),
						'default' => __( 'Click here to enter your code.', 'kadence-woo-extras' ),
						'required' => array( 'kt_coupon_modal_checkout', '=', '1' ),
					),
					array(
						'id' => 'checkout_coupon_desc',
						'type' => 'text',
						'title' => __( 'Modal Description', 'kadence-woo-extras' ),
						'help' => __( 'Standard: If you have a promo code, please apply it below.', 'kadence-woo-extras' ),
						'default' => __( 'If you have a promo code, please apply it below.', 'kadence-woo-extras' ),
						'required' => array( 'kt_coupon_modal_checkout', '=', '1' ),
					),
					array(
						'id' => 'checkout_coupon_placeholder',
						'type' => 'text',
						'title' => __( 'Input Placeholder', 'kadence-woo-extras' ),
						'help' => __( 'Standard: Promo code', 'kadence-woo-extras' ),
						'default' => __( 'Promo code', 'kadence-woo-extras' ),
						'required' => array( 'kt_coupon_modal_checkout', '=', '1' ),
					),
					array(
						'id' => 'checkout_coupon_apply',
						'type' => 'text',
						'title' => __( 'Apply Button', 'kadence-woo-extras' ),
						'help' => __( 'Standard: Apply Code', 'kadence-woo-extras' ),
						'default' => __( 'Apply Code', 'kadence-woo-extras' ),
						'required' => array( 'kt_coupon_modal_checkout', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_checkout_editor',
				'title' => __( 'Checkout Editor', 'kadence-woo-extras' ),
				'desc' => __('Reduce checkout friction by customizing the checkout to your customers unique needs. Enable and disable checkout fields or create your own fields using the checkout manager. Easily customize your checkout forms and control which fields are added to WooCommerce emails.', 'kadence-woo-extras' ),
				'group' => 'checkout',
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/checkout-preview.svg',
				'fields' => array(
					array(
						'id' => 'kt_checkout_editor',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Checkout Fields Editor', 'kadence-woo-extras' ),
						'help' => __( 'This gives you total control over your checkout fields and allows you to add custom fields. Note this only works with standard checkout, not the checkout block.', 'kadence-woo-extras' ),
						'default' => 0,
					),
					array(
						'id' => 'checkout_editor_output_info',
						'type' => 'info',
						'title' => __( 'Find the checkout editor under the "Woocommerce" Menu item in your admin.', 'kadence-woo-extras' ),
						'required' => array( 'kt_checkout_editor', '=', '1' ),
					),
				),
			)
		);
		Kadence_Settings_Engine::set_section(
			self::OPT_NAME,
			array(
				'id' => 'kt_woo_extra_cat_desc',
				'title' => __( 'Extra Category Description', 'kadence-woo-extras' ),
				'desc' => __( 'Add an extra description box for each category to allow you to place content below the products on your category page.', 'kadence-woo-extras' ),
				'group' => 'features',
				'illustration' => KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/category-desc-preview.svg',
				'fields' => array(
					array(
						'id' => 'kt_extra_cat',
						'type' => 'switch',
						'class' => 'ks-large-toggle',
						'title' => __( 'Enable Extra Category Description Box', 'kadence-woo-extras' ),
						'help' => __( 'This gives you an extra description box for each category to allow you to place content below the products on your category page.', 'kadence-woo-extras' ),
						'default' => 0,
					),
				),
			)
		);
	}
	/**
	 * Get the attribute terms available.
	 *
	 * @return array
	 */
	public function get_attribute_terms() {
		$attribute_terms = array( '' => __( 'First Attribute', 'kadence-woo-extras' ) );
		if ( ! is_admin() || ! class_exists( 'woocommerce' ) || ! function_exists( 'wc_get_attribute_taxonomies' )) {
			return $attribute_terms;
		}
		$attributes = wc_get_attribute_taxonomies();
		//print_r( $attributes );
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attribute ) {
				$attribute_terms[ $attribute->attribute_name ] = $attribute->attribute_label;
			}
		}
		return $attribute_terms;
	}
	/**
	 * Get the attribute terms available.
	 *
	 * @return array
	 */
	public function get_quickview_templates_options() {
		$options_arr = array();
		
		$args = array(
			'post_type'              => 'kadence_wootemplate',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'post_status'            => 'publish',
			'numberposts'            => 333,
			'order'                  => 'ASC',
			'orderby'                => 'menu_order',
			'suppress_filters'       => false,
			'meta_key'               => '_kad_wootemplate_type',
			'meta_value'             => 'quickview'
		);

		$posts = get_posts( $args );

		if($posts) {
			foreach ($posts as $the_post) {
				$title = $the_post->post_title;
				$id = $the_post->ID;
				$options_arr[$id] = $title;
			}
		}

		if($options_arr) {
			$options_arr[''] = '-';
			return $options_arr;
		} else {
			return array(
				'' => 'No Quickview templates found'
			);
		}
	}
}
new Kadence_Woo_Extras_Settings();
