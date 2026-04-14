<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_action( 'plugins_loaded', 'kt_badges_plugin_loaded' );

/**
 * Entry function.
 */
function kt_badges_plugin_loaded() {

	/**
	 * THe Badges class.
	 */
	class Kadence_Badges {

		public static $loop_page_types = array(
			'loop',
			'woocommerce_blocks_product_grid',
			'woocommerce-product-image',
			'kadence-wootemplate-blocks-image',
		);
		public static $single_page_types = array(
			'single',
			'kadence-wootemplate-blocks-gallery',
			'woocommerce-single-product',
		);

		/**
		 * Constructor.
		 */
		public function __construct() {
			//only init if woocommerce is active
			if ( class_exists( 'WooCommerce' ) ) {
				$shopkit_settings = get_option( 'kt_woo_extras' );
				if ( ! is_array( $shopkit_settings ) ) {
					$shopkit_settings = json_decode( $shopkit_settings, true );
				}

				add_action( 'init', array( $this, 'kt_woo_badges_post_type' ), 10 );
				add_action( 'admin_menu', array( $this, 'kt_woo_badges_menu' ) );

				//render on archive, realted, etc product loops for classic woo
				add_action( 'woocommerce_after_shop_loop_item', array( $this, 'kt_woo_badges' ) );
				//render on single product page
				add_action( 'wp_footer', array( $this, 'kt_woo_badges' ) );
				//render for blocks
				add_filter( 'render_block', array( $this, 'render_block' ), 12, 3 );
				//woocommerce product grid block used in related products, etc for block based woo
				add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'add_badges_to_product_grid_block' ), 10, 3 );
				//custom actions if someone wants to add their own badge entry point
				add_action( 'kt_woo_badges', array( $this, 'kt_woo_badges' ) );
				add_action( 'kt_woo_badges_loop', array( $this, 'kt_woo_badges' ) );

				add_filter( 'cmb2_admin_init', array( $this, 'kt_woo_badges_metaboxes' ) );
				add_action( 'cmb2_after_post_form__kt_woo_badge', array( $this, 'kt_woo_badges_custom_css_for_metabox' ), 10 );

				add_action( 'wp_enqueue_scripts', array( $this, 'kt_woo_badges_enqueue_scripts' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

				add_action( 'wp_ajax_kt_woo_get_badge_html', array( $this, 'get_badge_html_ajax' ) );
				add_action( 'wp_ajax_nopriv_kt_woo_get_badge_html', array( $this, 'get_badge_html_ajax' ) );

				add_action( 'body_class', array( $this, 'add_body_class' ), 70 );

				if ( isset( $shopkit_settings['kt_product_badges_disable_sale_flash'] ) && $shopkit_settings['kt_product_badges_disable_sale_flash'] ) {
					remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
					remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
				}

			}
		}

		/**
		 * Add the dynamic content to blocks.
		 *
		 * @param string $block_content The block content.
		 * @param array  $block The block info.
		 * @param object $wp_block The block class object.
		 */
		public function render_block( $block_content, $block, $wp_block ) {
			$block_name = ! empty( $block['blockName'] ) && $block['blockName'] ? $block['blockName'] : '';
			if ( $block_name == 'kadence-wootemplate-blocks/gallery' ) {
				if ( $wp_block && ! empty ( $wp_block->context['postId'] ) && $wp_block->context['postId'] ){
					return $block_content . $this->kt_woo_badges( $wp_block->context['postId'], 'kadence-wootemplate-blocks-gallery', true );
				}
			} else if ( $block_name == 'kadence-wootemplate-blocks/image' ) {
				if ( $wp_block && ! empty ( $wp_block->context['postId'] ) && $wp_block->context['postId'] ){
					return $block_content . $this->kt_woo_badges( $wp_block->context['postId'], 'kadence-wootemplate-blocks-image', true );
				}
			} else if ( $block_name == 'woocommerce/single-product' ) {
				if ( $block && ! empty ( $block['attrs']['productId'] ) && $block['attrs']['productId'] ){
					return $block_content . $this->kt_woo_badges( $block['attrs']['productId'], 'woocommerce-single-product', true );
				}
			} else if ( $block_name == 'woocommerce/product-image' ) {
				if ( $wp_block && ! empty ( $wp_block->context['postId'] ) && $wp_block->context['postId'] ){
					return $block_content . $this->kt_woo_badges( $wp_block->context['postId'], 'woocommerce-product-image', true );
				}
			}
			return $block_content;
		}

		/**
		 * Enqueue badge styles.
		 */
		public function kt_woo_badges_enqueue_scripts() {
			global $post;

			if ( is_shop() || is_product() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) {
				$this->enqueue_assets();
			}
		}

		/**
		 * Load script for admin.
		 */
		public function enqueue_admin_scripts() {
			global $parent_file;
			global $post;
			if ( 'edit.php?post_type=kt_woo_badge' == $parent_file ) {
				wp_enqueue_style( 'kadence_badge_css', KADENCE_WOO_EXTRAS_URL . 'lib/badges/css/kt_badges.css', false, KADENCE_WOO_EXTRAS_VERSION );
				wp_enqueue_script( 'kadence-admin-badges-js', KADENCE_WOO_EXTRAS_URL . 'lib/badges/js/kt_admin_badges.js', KADENCE_WOO_EXTRAS_VERSION, true );
				wp_localize_script(
					'kadence-admin-badges-js',
					'kadenceAdminBadges',
					array(
						'ajax_url'       => admin_url( 'admin-ajax.php' ),
						'ajax_nonce'     => wp_create_nonce( 'kadence-admin-badges-ajax-verification' ),
						'post_id'        => $post ? $post->ID : '',
					)
				);
			}
		}

		/**
		 * Enqueue badge styles.
		 */
		public function enqueue_assets() {
			wp_enqueue_style( 'kadence_badge_css', KADENCE_WOO_EXTRAS_URL . 'lib/badges/css/kt_badges.css', false, KADENCE_WOO_EXTRAS_VERSION );
			wp_enqueue_script( 'kadence-badges-js', KADENCE_WOO_EXTRAS_URL . 'lib/badges/js/kt_badges.js', KADENCE_WOO_EXTRAS_VERSION, true );
		}

		/**
		 * Returns if this page type corresponds to a singluar display.
		 *
		 * @param string $page_type Either the badge id or the badge post object.
		 */
		public static function is_singular( $page_type ) {
			if ( in_array( $page_type, self::$single_page_types ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Badge html.
		 *
		 * @param mixed $badge Either the badge id or the badge post object.
		 */
		public function get_badge_html( $badge, $page_type = 'single' ) {
			$badge_post = get_post( $badge );

			return wc_get_template_html( 'kt-single-badge.php', array( 'badge' => $badge_post, 'page_type' => $page_type ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/badges/' );
		}

		/**
		 * Badge render function.
		 */
		public function kt_woo_badges( $post_id = 0, $page_type = '', $return = false ) {
			global $product;

			$page_type = $page_type ? $page_type : ( current_action() == 'woocommerce_after_shop_loop_item' || current_action() == 'kt_woo_badges_loop' ? 'loop' : 'single' );

			$args = array(
				'post_type' => 'kt_woo_badge',
				'orderby' => 'menu_order',
				'order' => 'DESC',
				'post_status' => array( 'publish' ),
			);

			//simple cache for badges query;
			static $badges_query;
			if ( ! isset( $badges_query ) ) {
				$badges_query = new WP_Query( $args );
			}
			$active_badges = array();

			if ( $badges_query && $badges_query->posts ) {
				foreach ( $badges_query->posts as $badge_post ) {

					if ( $this->is_badge_active_on_post( $badge_post, $page_type, $post_id ) ) {
						$active_badges[] = $badge_post;
					}
				}
			}

			if ( $active_badges ) {
				//if we're rendering a badge, make sure our scripts and styles are enqueued
				$this->enqueue_assets();

				//render the first badge in the list because we ordered by priority earlier
				$badge_html = $this->get_badge_html( $active_badges[0]->ID, $page_type );
				if ( $return ) {
					return $badge_html;
				} else {
					echo $badge_html;
				}
			}
		}

		/**
		 * Determines whter a badge should be shown on this product/page combo.
		 *
		 * @param mixed $badge Either the badge post object.
		 */
		public function is_badge_active_on_post( $badge, $page_type = 'single', $post_id = 0 ) {
			// Resolve post ID first (avoid relying on global $product for reliability in loops/AJAX).
			if ( ! $post_id ) {
				$post_id = get_the_ID();
			}
			$the_post_id = $post_id;
			$the_product  = $the_post_id ? wc_get_product( $the_post_id ) : false;
			if ( ! is_a( $the_product, 'WC_Product' ) ) {
				$the_product = false;
			}
			$should_show_on_page = false;
			$should_show_on_post = false;

			$badge_visibility_page = get_post_meta( $badge->ID, '_kt_woo_badge_visibility_page', true );
			$badge_visibility_product = get_post_meta( $badge->ID, '_kt_woo_badge_visibility_product', true );
			$badge_products = get_post_meta( $badge->ID, '_kt_woo_badge_products', true );
			$badge_categories = get_post_meta( $badge->ID, '_kt_woo_badge_categories', true );
			$badge_tags = get_post_meta( $badge->ID, '_kt_woo_badge_tags', true );

			//page visibility checks
			if ( $badge_visibility_page == '' ) {
				$should_show_on_page = true;
			} else if ( $badge_visibility_page == 'archive' ) {
				if ( ! self::is_singular( $page_type ) ) {
					$should_show_on_page = true;
				}
			} else if ( $badge_visibility_page == 'single' ) {
				if ( self::is_singular( $page_type ) ) {
					$should_show_on_page = true;
				}
			}

			//post visibility checks
			if ( $badge_visibility_product == '' ) {
				$should_show_on_post = true;
			} else if ( $badge_visibility_product == 'product' ) {
				if ( $badge_products && in_array( $the_post_id, $badge_products ) ) {
					$should_show_on_post = true;
				}
			} else if ( $badge_visibility_product == 'tag' ) {
				$tags = wp_list_pluck( get_the_terms( $the_post_id, 'product_tag' ), 'term_id' );
				if ( $badge_tags && is_array( $badge_tags ) && $tags && is_array( $tags ) && array_intersect( $badge_tags, $tags ) ) {
					$should_show_on_post = true;
				}
			} else if ( $badge_visibility_product == 'category' ) {
				$categories = wp_list_pluck( get_the_terms( $the_post_id, 'product_cat' ), 'term_id' );
				if ( $categories && $badge_categories && array_intersect( $badge_categories, $categories ) ) {
					$should_show_on_post = true;
				}
			} else if ( $badge_visibility_product == 'sale' ) {
				if ( is_a( $the_product, 'WC_Product' ) && $the_product->is_on_sale() ) {
					$should_show_on_post = true;
				}
			} else if ( $badge_visibility_product == 'notsale' ) {
				if ( is_a( $the_product, 'WC_Product' ) && ! $the_product->is_on_sale() ) {
					$should_show_on_post = true;
				}
			} else if ( $badge_visibility_product == 'backorder' ) {
				if ( is_a( $the_product, 'WC_Product' ) && $the_product->is_on_backorder() ) {
					$should_show_on_post = true;
				}
			} else if ( $badge_visibility_product == 'featured' ) {
				if ( is_a( $the_product, 'WC_Product' ) && $the_product->is_featured() ) {
					$should_show_on_post = true;
				}
			} else if ( $badge_visibility_product == 'outofstock' ) {
				if ( is_a( $the_product, 'WC_Product' ) && ! $the_product->is_in_stock() ) {
					$should_show_on_post = true;
				}
			} else if ( $badge_visibility_product == 'lowstock' ) {
				if ( is_a( $the_product, 'WC_Product' )) {
					$the_product_quantity = $the_product->get_stock_quantity();
					$the_product_low_stock_amount = wc_get_low_stock_amount( $the_product );
					if ( ! empty( $the_product_quantity ) && ! empty( $the_product_low_stock_amount ) && $the_product_quantity <= $the_product_low_stock_amount ) {
						$should_show_on_post = true;
					}
				}
			}

			return $should_show_on_page && $should_show_on_post;
		}

		/**
		 * Ajax response to get the badge html.
		 */
		public function get_badge_html_ajax() {
			check_ajax_referer( 'kadence-admin-badges-ajax-verification' );
			$post_id = absint( sanitize_text_field( isset( $_GET['post_id'] ) ? $_GET['post_id'] : '' ) );
			$data = array();
			$data['html'] = $this->get_badge_html( $post_id );
			wp_send_json_success( $data );
		}

		/**
		 * Create the badges admin menu item.
		 */
		public function kt_woo_badges_menu() {
			add_submenu_page(
				'edit.php?post_type=product',
				__( 'Badges', 'kadence-woo-extras' ),
				__( 'Badges', 'kadence-woo-extras' ),
				'manage_woocommerce',
				'edit.php?post_type=kt_woo_badge',
				false
			);
		}

		/**
		 * Create the badges post type.
		 */
		public function kt_woo_badges_post_type() {
			$badge_labels = array(
				'name' => __( 'Badge', 'kadence-woo-extras' ),
				'singular_name' => __( 'Badge Item', 'kadence-woo-extras' ),
				'add_new' => __( 'Add New Badge', 'kadence-woo-extras' ),
				'add_new_item' => __( 'Add New Badge', 'kadence-woo-extras' ),
				'edit_item' => __( 'Edit Badge', 'kadence-woo-extras' ),
				'new_item' => __( 'New Badge', 'kadence-woo-extras' ),
				'all_items' => __( 'All Badges', 'kadence-woo-extras' ),
				'view_item' => __( 'View Badge', 'kadence-woo-extras' ),
				'search_items' => __( 'Search Badgees', 'kadence-woo-extras' ),
				'not_found' => __( 'No Badge found', 'kadence-woo-extras' ),
				'not_found_in_trash' => __( 'No Badges found in Trash', 'kadence-woo-extras' ),
				'parent_item_colon' => '',
				'menu_name' => __( 'Badge', 'kadence-woo-extras' ),
			);

			$badge_args = array(
				'labels' => $badge_labels,
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'exclude_from_search' => true,
				'show_in_menu' => false,
				'query_var' => true,
				'rewrite'  => false,
				'has_archive' => false,
				'capability_type' => 'post',
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => array( 'title', 'page-attributes' ),
			);

			register_post_type( 'kt_woo_badge', $badge_args );
		}

		/**
		 * Add the badges meta boxes for additional options.
		 *
		 * @param string $image the string of the badge image to retrieve.
		 */
		public static function get_badge_image( $image ) {
			$image = $image ? $image : 'bestseller_1';

			$images = array(
				'bestseller_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/bestseller_1.svg',
					'alt' => __( 'Best Seller', 'kadence-woo-extras' ),
				),
				'bestseller_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/bestseller_2.svg',
					'alt' => __( 'Best Seller', 'kadence-woo-extras' ),
				),
				'bestseller_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/bestseller_3.svg',
					'alt' => __( 'Best Seller', 'kadence-woo-extras' ),
				),
				'bestseller_4' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/bestseller_4.svg',
					'alt' => __( 'Best Seller', 'kadence-woo-extras' ),
				),
				'bestseller_5' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/bestseller_5.svg',
					'alt' => __( 'Best Seller', 'kadence-woo-extras' ),
				),
				'blackfriday_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/blackfriday_1.svg',
					'alt' => __( 'Black Friday', 'kadence-woo-extras' ),
				),
				'blackfriday_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/blackfriday_2.svg',
					'alt' => __( 'Black Friday', 'kadence-woo-extras' ),
				),
				'blackfriday_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/blackfriday_3.svg',
					'alt' => __( 'Black Friday', 'kadence-woo-extras' ),
				),
				'cybermonday_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/cybermonday_1.svg',
					'alt' => __( 'Cyber Monday', 'kadence-woo-extras' ),
				),
				'cybermonday_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/cybermonday_2.svg',
					'alt' => __( 'Cyber Monday', 'kadence-woo-extras' ),
				),
				'cybermonday_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/cybermonday_3.svg',
					'alt' => __( 'Cyber Monday', 'kadence-woo-extras' ),
				),
				'deal_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/deal_1.svg',
					'alt' => __( 'Deal', 'kadence-woo-extras' ),
				),
				'deal_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/deal_2.svg',
					'alt' => __( 'Deal', 'kadence-woo-extras' ),
				),
				'deal_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/deal_3.svg',
					'alt' => __( 'Deal', 'kadence-woo-extras' ),
				),
				'deal_4' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/deal_4.svg',
					'alt' => __( 'Deal', 'kadence-woo-extras' ),
				),
				'deal_5' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/deal_5.svg',
					'alt' => __( 'Deal', 'kadence-woo-extras' ),
				),
				'deal_6' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/deal_6.svg',
					'alt' => __( 'Deal', 'kadence-woo-extras' ),
				),
				'discount_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/discount_1.svg',
					'alt' => __( 'Discount', 'kadence-woo-extras' ),
				),
				'discount_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/discount_2.svg',
					'alt' => __( 'Discount', 'kadence-woo-extras' ),
				),
				'discount_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/discount_3.svg',
					'alt' => __( 'Discount', 'kadence-woo-extras' ),
				),
				'discount_4' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/discount_4.svg',
					'alt' => __( 'Discount', 'kadence-woo-extras' ),
				),
				'discount_5' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/discount_5.svg',
					'alt' => __( 'Discount', 'kadence-woo-extras' ),
				),
				'discount_6' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/discount_6.svg',
					'alt' => __( 'Discount', 'kadence-woo-extras' ),
				),
				'new_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/new_1.svg',
					'alt' => __( 'New', 'kadence-woo-extras' ),
				),
				'new_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/new_2.svg',
					'alt' => __( 'New', 'kadence-woo-extras' ),
				),
				'new_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/new_3.svg',
					'alt' => __( 'New', 'kadence-woo-extras' ),
				),
				'new_4' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/new_4.svg',
					'alt' => __( 'New', 'kadence-woo-extras' ),
				),
				'new_5' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/new_5.svg',
					'alt' => __( 'New', 'kadence-woo-extras' ),
				),
				'nostock_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/nostock_1.svg',
					'alt' => __( 'Out of Stock', 'kadence-woo-extras' ),
				),
				'nostock_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/nostock_2.svg',
					'alt' => __( 'Out of Stock', 'kadence-woo-extras' ),
				),
				'nostock_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/nostock_3.svg',
					'alt' => __( 'Out of Stock', 'kadence-woo-extras' ),
				),
				'nostock_4' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/nostock_4.svg',
					'alt' => __( 'Out of Stock', 'kadence-woo-extras' ),
				),
				'sale_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/sale_1.svg',
					'alt' => __( 'Sale', 'kadence-woo-extras' ),
				),
				'sale_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/sale_2.svg',
					'alt' => __( 'Sale', 'kadence-woo-extras' ),
				),
				'sale_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/sale_3.svg',
					'alt' => __( 'Sale', 'kadence-woo-extras' ),
				),
				'sale_4' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/sale_4.svg',
					'alt' => __( 'Sale', 'kadence-woo-extras' ),
				),
				'sale_5' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/sale_5.svg',
					'alt' => __( 'Sale', 'kadence-woo-extras' ),
				),
				'shipping_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/shipping_1.svg',
					'alt' => __( 'Free Shipping', 'kadence-woo-extras' ),
				),
				'shipping_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/shipping_2.svg',
					'alt' => __( 'Free Shipping', 'kadence-woo-extras' ),
				),
				'shipping_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/shipping_3.svg',
					'alt' => __( 'Free Shipping', 'kadence-woo-extras' ),
				),
				'shipping_4' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/shipping_4.svg',
					'alt' => __( 'Free Shipping', 'kadence-woo-extras' ),
				),
				'toprated_1' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/toprated_1.svg',
					'alt' => __( 'Top Rated', 'kadence-woo-extras' ),
				),
				'toprated_2' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/toprated_2.svg',
					'alt' => __( 'Top Rated', 'kadence-woo-extras' ),
				),
				'toprated_3' => array(
					'src' => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/toprated_3.svg',
					'alt' => __( 'Top Rated', 'kadence-woo-extras' ),
				),
			);

			return $images[ $image ];
		}

		/**
		 * Replaces particular strings in {key} format with dynamic data.
		 *
		 * @param string $content the content to search through.
		 */
		public static function do_string_replacements( $content ) {
			global $product;
			$product_quantity_string = '0';
			$discount_percentage_string = '0%';

			if ( str_contains( $content, '{quantity}' ) ) {
				if ( $product ) {
					$product_quantity = $product->get_stock_quantity();
					$product_quantity_string = ! empty( $product_quantity ) ? $product_quantity : '';
				}
				$content = str_replace( '{quantity}', $product_quantity, $content );
			}
			if ( str_contains( $content, '{sale-percentage}' ) ) {
				if ( $product && $product->is_on_sale() ) {
					$discount_percentage = kt_woo_get_the_sale_percentage();
					$discount_percentage_string = $discount_percentage ? $discount_percentage . '%' : '';
				}
				$content = str_replace( '{sale-percentage}', $discount_percentage_string, $content );
			}

			return $content;
		}

		/**
		 * Add the badges meta boxes for additional options.
		 */
		public function kt_woo_badges_metaboxes() {
			$prefix = '_kt_woo_';
			$kt_woo_badge = new_cmb2_box(
				array(
					'id'            => $prefix . 'badge',
					'title'         => __( 'Badge Settings', 'kadence-woo-extras' ),
					'object_types'  => array( 'kt_woo_badge' ), // Post type
				)
			);

			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Badge Type', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_type',
					'type'          => 'select',
					'options'          => array(
						'premade'        => __( 'Image', 'kadence-woo-extras' ),
						'image-custom'        => __( 'Custom Image', 'kadence-woo-extras' ),
						'text'       => __( 'Text', 'kadence-woo-extras' ),
						'html'      => __( 'HTML', 'kadence-woo-extras' ),
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Badge Text', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_text',
					'type'          => 'text',
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_type',
						'data-kadence-condition-value' => 'text',
					),
				)
			);

			$kt_woo_badge->add_field(
				array(
					'name'             => __( 'Badge Image', 'kadence-woo-extras' ),
					'id'               => $prefix . 'badge_image',
					'type'             => 'radio_image',
					'options'          => array(
						''    => __('Best Seller', 'kadence-woo-extras'),
						'bestseller_2'  => __('Best Seller', 'kadence-woo-extras'),
						'bestseller_3'  => __('Best Seller', 'kadence-woo-extras'),
						'bestseller_4'  => __('Best Seller', 'kadence-woo-extras'),
						'bestseller_5'  => __('Best Seller', 'kadence-woo-extras'),
						'blackfriday_1'  => __('Black Friday', 'kadence-woo-extras'),
						'blackfriday_2'  => __('Black Friday', 'kadence-woo-extras'),
						'blackfriday_3'  => __('Black Friday', 'kadence-woo-extras'),
						'cybermonday_1'  => __('Cyber Monday', 'kadence-woo-extras'),
						'cybermonday_2'  => __('Cyber Monday', 'kadence-woo-extras'),
						'cybermonday_3'  => __('Cyber Monday', 'kadence-woo-extras'),
						'deal_1'  => __('Deal', 'kadence-woo-extras'),
						'deal_2'  => __('Deal', 'kadence-woo-extras'),
						'deal_3'  => __('Deal', 'kadence-woo-extras'),
						'deal_4'  => __('Deal', 'kadence-woo-extras'),
						'deal_5'  => __('Deal', 'kadence-woo-extras'),
						'deal_6'  => __('Deal', 'kadence-woo-extras'),
						'discount_1'  => __('Discount', 'kadence-woo-extras'),
						'discount_2'  => __('Discount', 'kadence-woo-extras'),
						'discount_3'  => __('Discount', 'kadence-woo-extras'),
						'discount_4'  => __('Discount', 'kadence-woo-extras'),
						'discount_5'  => __('Discount', 'kadence-woo-extras'),
						'discount_6'  => __('Discount', 'kadence-woo-extras'),
						'new_1'  => __('New', 'kadence-woo-extras'),
						'new_2'  => __('New', 'kadence-woo-extras'),
						'new_3'  => __('New', 'kadence-woo-extras'),
						'new_4'  => __('New', 'kadence-woo-extras'),
						'new_5'  => __('New', 'kadence-woo-extras'),
						'nostock_1'  => __('Out of Stock', 'kadence-woo-extras'),
						'nostock_2'  => __('Out of Stock', 'kadence-woo-extras'),
						'nostock_3'  => __('Out of Stock', 'kadence-woo-extras'),
						'nostock_4'  => __('Out of Stock', 'kadence-woo-extras'),
						'sale_1'  => __('Sale', 'kadence-woo-extras'),
						'sale_2'  => __('Sale', 'kadence-woo-extras'),
						'sale_3'  => __('Sale', 'kadence-woo-extras'),
						'sale_4'  => __('Sale', 'kadence-woo-extras'),
						'sale_5'  => __('Sale', 'kadence-woo-extras'),
						'shipping_1'  => __('Free Shipping', 'kadence-woo-extras'),
						'shipping_2'  => __('Free Shipping', 'kadence-woo-extras'),
						'shipping_3'  => __('Free Shipping', 'kadence-woo-extras'),
						'shipping_4'  => __('Free Shipping', 'kadence-woo-extras'),
						'toprated_1'  => __('Top Rated', 'kadence-woo-extras'),
						'toprated_2'  => __('Top Rated', 'kadence-woo-extras'),
						'toprated_3'  => __('Top Rated', 'kadence-woo-extras'),
					),
					'images_path'      => KADENCE_WOO_EXTRAS_URL . 'lib/badges/images/',
					'images'           => array(
						''    => 'bestseller_1.svg',
						'bestseller_2'  => 'bestseller_2.svg',
						'bestseller_3'  => 'bestseller_3.svg',
						'bestseller_4'  => 'bestseller_4.svg',
						'bestseller_5'  => 'bestseller_5.svg',
						'blackfriday_1'  => 'blackfriday_1.svg',
						'blackfriday_2'  => 'blackfriday_2.svg',
						'blackfriday_3'  => 'blackfriday_3.svg',
						'cybermonday_1'  => 'cybermonday_1.svg',
						'cybermonday_2'  => 'cybermonday_2.svg',
						'cybermonday_3'  => 'cybermonday_3.svg',
						'deal_1'  => 'deal_1.svg',
						'deal_2'  => 'deal_2.svg',
						'deal_3'  => 'deal_3.svg',
						'deal_4'  => 'deal_4.svg',
						'deal_5'  => 'deal_5.svg',
						'deal_6'  => 'deal_6.svg',
						'discount_1'  => 'discount_1.svg',
						'discount_2'  => 'discount_2.svg',
						'discount_3'  => 'discount_3.svg',
						'discount_4'  => 'discount_4.svg',
						'discount_5'  => 'discount_5.svg',
						'discount_6'  => 'discount_6.svg',
						'new_1'  => 'new_1.svg',
						'new_2'  => 'new_2.svg',
						'new_3'  => 'new_3.svg',
						'new_4'  => 'new_4.svg',
						'new_5'  => 'new_5.svg',
						'nostock_1'  => 'nostock_1.svg',
						'nostock_2'  => 'nostock_2.svg',
						'nostock_3'  => 'nostock_3.svg',
						'nostock_4'  => 'nostock_4.svg',
						'sale_1'  => 'sale_1.svg',
						'sale_2'  => 'sale_2.svg',
						'sale_3'  => 'sale_3.svg',
						'sale_4'  => 'sale_4.svg',
						'sale_5'  => 'sale_5.svg',
						'shipping_1'  => 'shipping_1.svg',
						'shipping_2'  => 'shipping_2.svg',
						'shipping_3'  => 'shipping_3.svg',
						'shipping_4'  => 'shipping_4.svg',
						'toprated_1'  => 'toprated_1.svg',
						'toprated_2'  => 'toprated_2.svg',
						'toprated_3'  => 'toprated_3.svg',
					),
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_type',
						'data-kadence-condition-value' => 'premade',
					),
				),
			);

			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Badge Image', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_image_custom',
					'type'          => 'file',
					'options' => array(
						'url' => false, // Hide the text input for the url
					),
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_type',
						'data-kadence-condition-value' => 'image-custom',
					),
				)
			);
			//conditional hardcoded into javascript
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Badge HTML', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_html',
					'type'          => 'wysiwyg',
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Badge Position', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_position',
					'type'          => 'select',
					'options'          => array(
						''        => __( 'Top Right', 'kadence-woo-extras' ),
						'bottom-right'       => __( 'Bottom Right', 'kadence-woo-extras' ),
						'bottom-left'      => __( 'Bottom Left', 'kadence-woo-extras' ),
						'top-left'      => __( 'Top Left', 'kadence-woo-extras' ),
						'center'      => __( 'Center', 'kadence-woo-extras' ),
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Max Width (px)', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_max_width',
					'type'          => 'kt_woo_text_number',
					'attributes' => array(
						'min' => '0',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Max Width Loop (px)', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_max_width_loop',
					'type'          => 'kt_woo_text_number',
					'attributes' => array(
						'min' => '0',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Margin (px)', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_margin',
					'type'          => 'kt_woo_text_number',
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Padding (px)', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_padding',
					'type'          => 'kt_woo_text_number',
					'attributes' => array(
						'min' => '0',
					),
				)
			);

			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Badge Design Settings', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_design_info',
					'type'          => 'title',
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_text_color',
					'type'          => 'colorpicker',
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_type',
						'data-kadence-condition-value' => 'text,premade',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Background Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_background',
					'type'          => 'colorpicker',
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Font Size (px)', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_font_size',
					'type'          => 'kt_woo_text_number',
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_type',
						'data-kadence-condition-value' => 'text',
						'min' => '0'
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Font style', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_font_style',
					'type'          => 'select',
					'options'          => array(
						''        => __( 'Regular', 'kadence-woo-extras' ),
						'italic'       => __( 'Italic', 'kadence-woo-extras' ),
					),
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_type',
						'data-kadence-condition-value' => 'text',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Font Weight', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_font_weight',
					'type'          => 'select',
					'options'          => array(
						''        => __( 'Regular', 'kadence-woo-extras' ),
						'bold'       => __( 'Bold', 'kadence-woo-extras' ),
					),
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_type',
						'data-kadence-condition-value' => 'text',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Text Align', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_text_align',
					'type'          => 'select',
					'options'          => array(
						''        => __( 'Left', 'kadence-woo-extras' ),
						'center'       => __( 'Center', 'kadence-woo-extras' ),
						'right'       => __( 'Right', 'kadence-woo-extras' ),
					),
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_type',
						'data-kadence-condition-value' => 'text',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Border Radius', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_border_radius',
					'type'          => 'kt_woo_text_number',
					'attributes' => array(
						'min' => '0',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Border Width (px)', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_border_width',
					'type'          => 'kt_woo_text_number',
					'attributes' => array(
						'min' => '0',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Border Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_border_color',
					'type'          => 'colorpicker',
					'default'       => 'red',
				)
			);

			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Badge Visibility Settings', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_visibility_info',
					'type'          => 'title',
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'General Visibility', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_visibility_page',
					'type'          => 'select',
					'options'          => array(
						''        => __( 'Product loops and single products', 'kadence-woo-extras' ),
						'archive'       => __( 'Product loops only', 'kadence-woo-extras' ),
						'single'         => __( 'Single products only', 'kadence-woo-extras' ),
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'          => __( 'Product Visibility', 'kadence-woo-extras' ),
					'id'            => $prefix . 'badge_visibility_product',
					'type'          => 'select',
					'options'          => array(
						''        => __( 'All Products', 'kadence-woo-extras' ),
						'product'       => __( 'Show on certain products', 'kadence-woo-extras' ),
						'sale'         => __( 'Show on products that are on sale', 'kadence-woo-extras' ),
						'notsale'         => __( 'Show on products that are not on sale', 'kadence-woo-extras' ),
						'outofstock'         => __( 'Show on products that are out of stock', 'kadence-woo-extras' ),
						'lowstock'         => __( 'Show on products that are low stock', 'kadence-woo-extras' ),
						'backorder'         => __( 'Show on products that are on backorder', 'kadence-woo-extras' ),
						'featured'         => __( 'Show on featured products', 'kadence-woo-extras' ),
						'category'      => __( 'Show on products in certain categories', 'kadence-woo-extras' ),
						'tag'      => __( 'Show on products with certain tags', 'kadence-woo-extras' ),
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'      => __( 'Choose which products', 'kadence-woo-extras' ),
					'id'        => $prefix . 'badge_products',
					'type'      => 'pw_multiselect',
					'options_cb'     => 'kt_get_post_options',
					'get_posts_args' => array(
						'post_type'   => 'product',
					),
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_visibility_product',
						'data-kadence-condition-value' => 'product',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'      => __( 'Choose which tags', 'kadence-woo-extras' ),
					'id'        => $prefix . 'badge_tags',
					'type'      => 'pw_multiselect',
					'options_cb'     => 'kt_get_term_options',
					'get_terms_args' => array(
						'taxonomy'   => 'product_tag',
						'hide_empty' => false,
					),
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_visibility_product',
						'data-kadence-condition-value' => 'tag',
					),
				)
			);
			$kt_woo_badge->add_field(
				array(
					'name'      => __( 'Choose which categories', 'kadence-woo-extras' ),
					'id'        => $prefix . 'badge_categories',
					'type'      => 'pw_multiselect',
					'options_cb'     => 'kt_get_term_options',
					'get_terms_args' => array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
					),
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'badge_visibility_product',
						'data-kadence-condition-value' => 'category',
					),
				)
			);

			$kt_woo_badge_preview = new_cmb2_box(
				array(
					'id'            => $prefix . 'badge_preview',
					'title'         => __( 'Badge Preview', 'kadence-woo-extras' ),
					'object_types'  => array( 'kt_woo_badge' ), // Post type
					'context' => 'side',
					'priority' => 'low',
				)
			);
		}

		/**
		 * Add the css for badges meta boxes.
		 */
		public function kt_woo_badges_custom_css_for_metabox() {
			?>
				<style type="text/css" media="screen">
					.cmb-type-group .cmb2-wrap>.cmb-field-list>.cmb-row, .postbox-container .cmb2-wrap>.cmb-field-list>.cmb-row {
						margin-bottom: 0;
						padding: 8px 0;
					}
					.postbox-container .cmb2-wrap>.cmb-field-list>.cmb-row.cmb-type-title {
						background: #f9f9f9;
						margin: 0px;
						text-align: center;
						padding: 6px 10px;
						font-weight: bold;
						border-bottom: 2px solid #999;
					}
					.postbox-container .cmb2-wrap>.cmb-field-list>.cmb-row.cmb-type-title h5 {
						font-size: 20px;
						font-weight: bold;
					}
					</style>
				<?php
		}

		/**
		 * Adds badges to the woocommerce product grid block.
		 *
		 * @param string $html the html for the product block.
		 * @param object $data block product object.
		 * @param object $product block product object.
		 * @return string updated html.
		 */
		public function add_badges_to_product_grid_block( $html, $data, $the_product ) {
			global $product;
			$the_product = $the_product ? $the_product : ( $product ? $product : null );
			if ( $the_product && ! is_cart() ) {
				$the_product_id = $the_product->get_id();
				$badge_html = $this->kt_woo_badges( $the_product_id, 'woocommerce_blocks_product_grid', true );
				if ( $badge_html ) {
					$html = str_replace( '</li>', $badge_html . '</li>', $html );

					// $html .= $badge_html;
				}
			}
			return $html;
		}

		/**
		 * Adds custom classes to indicate the button size for the single products.
		 *
		 * @param array $classes Classes for the body element.
		 * @return array Filtered body classes.
		 */
		public function add_body_class( $classes ) {
			$shopkit_settings = get_option( 'kt_woo_extras' );
			if ( ! is_array( $shopkit_settings ) ) {
				$shopkit_settings = json_decode( $shopkit_settings, true );
			}

			if ( isset( $shopkit_settings['kt_product_badges_disable_sale_flash'] ) && $shopkit_settings['kt_product_badges_disable_sale_flash'] ) {
				$classes[] = 'kadence-woo-disable-woo-sale-flash';
			}
			return $classes;
		}

	}
	new Kadence_Badges();
}

