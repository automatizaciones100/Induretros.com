<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin page class
 */
class WPBean_Accordion_ShortCode_CPT {

	/**
	 * ShortCode CPT post type.
	 *
	 * @var string
	 */
	private $shortcode_post_type = 'wpb_wmca_shortcodes';

	/**
	 * Class Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Custom Post Type.
	 *
	 * @return void
	 */
	public function register_post_type() {

		$labels = array(
			'name'                  => esc_html_x( 'WPB Accordions', 'Post Type General Name', 'wpb-accordion-menu-or-category' ),
			'singular_name'         => esc_html_x( 'WPB Accordion', 'Post Type Singular Name', 'wpb-accordion-menu-or-category' ),
			'menu_name'             => esc_html__( 'WPB Accordions', 'wpb-accordion-menu-or-category' ),
			'name_admin_bar'        => esc_html__( 'WPB Accordion', 'wpb-accordion-menu-or-category' ),
			'archives'              => esc_html__( 'WPB Accordion Archives', 'wpb-accordion-menu-or-category' ),
			'attributes'            => esc_html__( 'WPB Accordion Attributes', 'wpb-accordion-menu-or-category' ),
			'parent_item_colon'     => esc_html__( 'Parent WPB Accordion:', 'wpb-accordion-menu-or-category' ),
			'all_items'             => esc_html__( 'All WPB Accordions', 'wpb-accordion-menu-or-category' ),
			'add_new_item'          => esc_html__( 'Add New WPB Accordion', 'wpb-accordion-menu-or-category' ),
			'add_new'               => esc_html__( 'Add New', 'wpb-accordion-menu-or-category' ),
			'new_item'              => esc_html__( 'New WPB Accordion', 'wpb-accordion-menu-or-category' ),
			'edit_item'             => esc_html__( 'Edit WPB Accordion', 'wpb-accordion-menu-or-category' ),
			'update_item'           => esc_html__( 'Update WPB Accordion', 'wpb-accordion-menu-or-category' ),
			'view_item'             => esc_html__( 'View WPB Accordion', 'wpb-accordion-menu-or-category' ),
			'view_items'            => esc_html__( 'View WPB Accordions', 'wpb-accordion-menu-or-category' ),
			'search_items'          => esc_html__( 'Search WPB Accordion', 'wpb-accordion-menu-or-category' ),
			'not_found'             => esc_html__( 'Not found', 'wpb-accordion-menu-or-category' ),
			'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'wpb-accordion-menu-or-category' ),
			'featured_image'        => esc_html__( 'Featured Image', 'wpb-accordion-menu-or-category' ),
			'set_featured_image'    => esc_html__( 'Set featured image', 'wpb-accordion-menu-or-category' ),
			'remove_featured_image' => esc_html__( 'Remove featured image', 'wpb-accordion-menu-or-category' ),
			'use_featured_image'    => esc_html__( 'Use as featured image', 'wpb-accordion-menu-or-category' ),
			'insert_into_item'      => esc_html__( 'Insert into item', 'wpb-accordion-menu-or-category' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this WPB Accordion', 'wpb-accordion-menu-or-category' ),
			'items_list'            => esc_html__( 'WPB Accordions list', 'wpb-accordion-menu-or-category' ),
			'items_list_navigation' => esc_html__( 'WPB Accordions list navigation', 'wpb-accordion-menu-or-category' ),
			'filter_items_list'     => esc_html__( 'Filter WPB Accordions list', 'wpb-accordion-menu-or-category' ),
		);

		$args = array(
			'label'               => esc_html__( 'WPB Accordion', 'wpb-accordion-menu-or-category' ),
			'description'         => esc_html__( 'WPB Accordions Post Type', 'wpb-accordion-menu-or-category' ),
			'labels'              => apply_filters( $this->shortcode_post_type . '_post_type_labels', $labels ),
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => $this->shortcode_post_type . '-items',
			'menu_position'       => 5,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false, // disable single post view.
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);

		register_post_type( $this->shortcode_post_type, $args );
	}
}
