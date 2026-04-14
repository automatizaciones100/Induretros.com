<?php
/**
 * Kadence Global Tabs
 *
 * @package Kadence Woo Extas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class to create global tabs.
 */
class Kadence_Global_Tabs {
	/**
	 * Instance Control
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
	 * Construction
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
	}
	/**
	 * Init on Plugins Loaded.
	 */
	public function on_plugins_loaded() {
		add_action( 'init', array( $this, 'tab_post_type' ), 10 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'script_enqueue' ) );
		add_action( 'admin_menu', array( $this, 'tabs_admin_menu' ) );
		add_action( 'add_meta_boxes', array( $this, 'remove_wp_seo_meta_box' ), 100 );
		add_filter( 'cmb2_admin_init', array( $this, 'tab_metaboxes' ) );
		add_filter( 'woocommerce_product_tabs', array( $this, 'global_product_tabs' ) );
		add_filter( 'kadence_tab_content', 'do_blocks', 9 );
		add_filter( 'kadence_tab_content', 'wptexturize' );
		add_filter( 'kadence_tab_content', 'convert_smilies', 20 );
		add_filter( 'kadence_tab_content', 'shortcode_unautop' );
		add_filter( 'kadence_tab_content', 'do_shortcode', 11 );
		add_filter( 'kadence_tab_content', 'prepend_attachment' );
		add_filter( 'kadence_post_layout', array( $this, 'tabs_single_layout' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ), 200 );
	}
	/**
	 * Add JS to support anchor for global tabs.
	 */
	public function frontend_scripts() {
		if ( is_product() ) {
			wp_enqueue_script( 'kadence-global-tabs', KADENCE_WOO_EXTRAS_URL . 'lib/tabs/assets/global-tabs.js', array( 'jquery' ), KADENCE_WOO_EXTRAS_VERSION, true );
		}
	}
	/**
	 * Add Tabs
	 *
	 * @param array $tabs the products tabs.
	 */
	public function global_product_tabs( $tabs ) {
		global $product;
		$product_cat_ids = array();
		$product_id      = method_exists( $product, 'get_id' ) === true ? $product->get_id() : $product->ID;
		$terms           = get_the_terms( $product_id, 'product_cat' );
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$product_cat_ids[] = $term->term_id;
			}
		}
		$args = array(
			'post_type'        => 'kt_product_tabs',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'suppress_filters' => false,
		);
		$global_tabs = get_posts( $args );
		foreach ( $global_tabs as $tab ) {
			$add_tab    = false;
			$show_type  = get_post_meta( $tab->ID, '_kt_woo_tabs_display_type', true );
			$categories = get_post_meta( $tab->ID, '_kt_woo_tabs_category', true );
			$products   = get_post_meta( $tab->ID, '_kt_woo_tabs_products', true );
			$override_title = get_post_meta( $tab->ID, '_kt_woo_tabs_title', true );
			if ( 'all' === $show_type ) {
				$add_tab = true;
			} elseif ( 'category' === $show_type && is_array( $categories ) && array_intersect( $categories, $product_cat_ids ) ) {
				$add_tab = true;
			} elseif ( 'products' === $show_type && is_array( $products ) && in_array( $product_id, $products ) ) {
				$add_tab = true;
			}
			if ( $add_tab ) {
				$priority = get_post_meta( $tab->ID, '_kt_woo_tab_priority', true );
				$priority = abs( $priority );
				if ( empty( $priority ) ) {
					$priority = 40;
				}
				if ( ! empty( $override_title ) ) {
					$title = $override_title;
				} else {
					$title = $tab->post_title;
				}
				// Make sure it's not empty.
				if ( empty( $title ) ) {
					$title = $tab->ID;
				}
				if ( ! empty( trim( $tab->post_content ) ) ) {
					$tabs[ 'global-tab-' . $tab->ID ] = array(
						'title'    => esc_attr( $title ),
						'priority' => $priority,
						'callback' => array( $this, 'global_product_tab_content' ),
						'content'  => $tab->post_content,
					);
				}
			}
		}
		return $tabs;
	}
	/**
	 * Add Tab Content
	 *
	 * @param string $key the product tab key.
	 * @param array  $tab the product tab array.
	 */
	public function global_product_tab_content( $key, $tab ) {
		global $wp_embed;
		if ( isset( $wp_embed ) ) {
			$tab['content'] = $wp_embed->autoembed( $tab['content'] );
		}
		echo apply_filters( 'kadence_tab_content', $tab['content'] );
	}
	/**
	 * Remove SEO Meta Box
	 */
	public function remove_wp_seo_meta_box() {
		remove_meta_box( 'wpseo_meta', 'kt_product_tabs', 'normal' );
	}
	/**
	 * Enqueue Script for Meta options
	 */
	public function script_enqueue() {
		$post_type = get_post_type();
		if ( 'kt_product_tabs' !== $post_type ) {
			return;
		}
		wp_enqueue_style( 'kadence-product-tabs-meta', KADENCE_WOO_EXTRAS_URL . 'lib/tabs/assets/admin-tab-styles.css', false, KADENCE_WOO_EXTRAS_VERSION );
	}
	/**
	 * Create Tabs Admin menu
	 */
	public function tabs_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=product',
			esc_html__( 'Product Tabs', 'kadence-woo-extras' ),
			esc_html__( 'Product Tabs', 'kadence-woo-extras' ),
			'manage_woocommerce',
			'edit.php?post_type=kt_product_tabs',
			false
		);
	}
	/**
	 * Create Tabs Meta Boxes
	 */
	public function tab_metaboxes() {
		$prefix      = '_kt_woo_';
		$kt_woo_tabs = new_cmb2_box(
			array(
				'id'           => $prefix . 'global_tabs',
				'title'        => __( 'Tab Settings', 'kadence-woo-extras' ),
				'object_types' => array( 'kt_product_tabs' ),
			)
		);
		$kt_woo_tabs->add_field(
			array(
				'name' => __( 'Tab Title', 'kadence-woo-extras' ),
				'id'   => $prefix . 'tabs_title',
				'type' => 'text',
				'desc' => __( 'Defaults to tab post title', 'kadence-woo-extras' ),
			)
		);
		$kt_woo_tabs->add_field(
			array(
				'name'    => __( 'Display Type', 'kadence-woo-extras' ),
				'id'      => $prefix . 'tabs_display_type',
				'type'    => 'select',
				'default' => 'all',
				'options' => array(
					'all'      => __( 'Add to every product', 'kadence-woo-extras' ),
					'category' => __( 'Add to products of a specific category', 'kadence-woo-extras' ),
					'products' => __( 'Add only to specific products', 'kadence-woo-extras' ),
				),
			)
		);
		$kt_woo_tabs->add_field(
			array(
				'name'           => __( 'Choose which category', 'kadence-woo-extras' ),
				'id'             => $prefix . 'tabs_category',
				'type'           => 'pw_multiselect',
				'default'        => '',
				'options_cb'     => 'kt_get_term_options',
				'get_terms_args' => array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
				),
				'attributes'     => array(
					'data-kadence-condition-id'    => $prefix . 'tabs_display_type',
					'data-kadence-condition-value' => 'category',
				),
			)
		);
		$kt_woo_tabs->add_field(
			array(
				'name'       => __( 'Choose which products', 'kadence-woo-extras' ),
				'id'         => $prefix . 'tabs_products',
				'type'       => 'pw_multiselect',
				'default'    => '',
				'options_cb' => 'kt_woo_product_posts_options_muiti',
				'attributes' => array(
					'data-kadence-condition-id'    => $prefix . 'tabs_display_type',
					'data-kadence-condition-value' => 'products',
				),
			)
		);
		$kt_woo_tabs->add_field(
			array(
				'name'    => __( 'Choose tab priority (0 - 100 )', 'kadence-woo-extras' ),
				'desc'    => __( '10 - Description | 20 - Additional Information | 30 - Reviews', 'kadence-woo-extras' ),
				'id'      => $prefix . 'tab_priority',
				'default' => 40,
				'type'    => 'kt_woo_text_number',
			)
		);
	}
	/**
	 * Create Tab Post Type
	 */
	public function tab_post_type() {
		$tablabels = array(
			'name'               => esc_html__( 'Product Tabs', 'kadence-woo-extras' ),
			'singular_name'      => esc_html__( 'Product Tab', 'kadence-woo-extras' ),
			'add_new'            => esc_html__( 'Add New Product Tab', 'kadence-woo-extras' ),
			'add_new_item'       => esc_html__( 'Add New Product Tab', 'kadence-woo-extras' ),
			'edit_item'          => esc_html__( 'Edit Product Tab', 'kadence-woo-extras' ),
			'new_item'           => esc_html__( 'New Product Tab', 'kadence-woo-extras' ),
			'all_items'          => esc_html__( 'All Product Tabs', 'kadence-woo-extras' ),
			'view_item'          => esc_html__( 'View Product Tab', 'kadence-woo-extras' ),
			'search_items'       => esc_html__( 'Search Product Tabs', 'kadence-woo-extras' ),
			'not_found'          => esc_html__( 'No Product Tab found', 'kadence-woo-extras' ),
			'not_found_in_trash' => esc_html__( 'No Product Tabs found in Trash', 'kadence-woo-extras' ),
			'parent_item_colon'  => '',
			'menu_name'          => esc_html__( 'Cart Notice', 'kadence-woo-extras' ),
		);

		$tabargs = array(
			'labels'              => $tablabels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'exclude_from_search' => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => false,
			'has_archive'         => false,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'menu_position'       => null,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor' ),
		);

		register_post_type( 'kt_product_tabs', $tabargs );
	}
	/**
	 * Renders the woo template single template on the front end.
	 *
	 * @param array $layout the layout array.
	 */
	public function tabs_single_layout( $layout ) {
		global $post;
		if ( is_singular( 'kt_product_tabs' ) || ( is_admin() && is_object( $post ) && 'kt_product_tabs' === $post->post_type ) ) {
			$layout = wp_parse_args(
				array(
					'layout'           => 'normal',
					'boxed'            => 'unboxed',
					'feature'          => 'hide',
					'feature_position' => 'above',
					'comments'         => 'hide',
					'navigation'       => 'hide',
					'title'            => 'hide',
					'transparent'      => 'disable',
					'sidebar'          => 'disable',
					'vpadding'         => 'hide',
					'footer'           => 'disable',
					'header'           => 'disable',
					'content'          => 'enable',
				),
				$layout
			);
		}

		return $layout;
	}
}
Kadence_Global_Tabs::get_instance();

