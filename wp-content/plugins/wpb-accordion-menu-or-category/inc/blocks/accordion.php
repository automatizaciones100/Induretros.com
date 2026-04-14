<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Accordion
 *
 * Main Accordion class
 *
 * @since 1.0
 */
class WPBean_Accordion_Menu_Block {

	/**
	 * Instance
	 *
	 * @since 1.2.0
	 * @access private
	 * @static
	 *
	 * @var Accordion The single instance of the class.
	 */
	private static $instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @return Accordion An instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register block editor assets
	 */
	public function block_editor_assets() {
		wp_register_script(
			'wpb-wmca-accordion-block',
			plugins_url( 'index.js', __FILE__ ),
			array(
				'wp-api-fetch',
				'wp-components',
				'wp-compose',
				'wp-blocks',
				'wp-element',
				'wp-i18n',
			),
			'1.0',
			true
		);

		wp_set_script_translations(
			'wpb-wmca-accordion-block',
			'wpb-accordion-menu-or-category' // text-domain.
		);

		register_block_type(
			'wpb-accordion-menu-or-category-pro/wpb-wmca-shortcode-selector',
			array(
				'editor_script' => 'wpb-wmca-accordion-block',
			)
		);

		$shortcode_items = array_map(
			function ( $post ) {
				return array(
					'id'    => $post->ID,
					'title' => $post->post_title,
				);
			},
			get_posts(
				array(
					'post_type'      => 'wpb_wmca_shortcodes',
					'post_status'    => 'publish',
					'posts_per_page' => '-1',
				)
			)
		);

		wp_add_inline_script(
			'wpb-wmca-accordion-block',
			sprintf(
				'window.wpbwmca = {ShortCodes:%s};',
				wp_json_encode( $shortcode_items )
			),
			'before'
		);
	}

	/**
	 *  Accordion class constructor
	 *
	 * Register Accordion action hooks and filters
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'block_editor_assets' ), 10, 0 );
	}
}
