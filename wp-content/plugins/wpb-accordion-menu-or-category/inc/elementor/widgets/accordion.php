<?php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // If this file is called directly, abort.
}

/**
 * WPB Accordion Elementor Widget
 *
 * @since 1.0.0
 */
class WPBean_Accordion_Menu_Elementor_Widget extends Widget_Base {

	/**
	 * ShortCode Post Type.
	 *
	 * @var string
	 */
	private $shortcode_post_type = 'wpb_wmca_shortcodes';

	/**
	 * Accordion ShortCode Tag.
	 *
	 * @var string
	 */
	private $shortcode_tag = 'wpb_wmca_accordion_pro';

	/**
	 * Retrieve the posts.
	 *
	 * @param string $post_type The posts type.
	 * @return array posts
	 */
	private function get_all_posts( $post_type = 'any' ) {
		$posts = get_posts(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);

		if ( ! empty( $posts ) ) {
			return wp_list_pluck( $posts, 'post_title', 'ID' );
		}

		return array();
	}

	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'wpb-accordion-menu-or-category-pro';
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget scripts dependencies.
	 */
	public function get_script_depends() {
		return array( 'wpb_wmca_jquery_cookie', 'wpb_wmca_accordion_script', 'wpb_wmca_accordion_init' );
	}

	/**
	 * Retrieve the list of styles the widget depended on.
	 *
	 * Used to set styles dependencies required to run the widget.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget styles dependencies.
	 */
	public function get_style_depends() {
		return array( 'wpb_wmca_accordion_style' );
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'WPB Accordion', 'wpb-accordion-menu-or-category' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-accordion';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the currency widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'accordion', 'category', 'menu', 'posts', 'products', 'accordion menu', 'list', 'taxonomy' );
	}

	/**
	 * Register accordion widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Content', 'wpb-accordion-menu-or-category' ),
			)
		);

		$this->add_control(
			'shortcode_id',
			array(
				'label'   => esc_html__( 'Select an Accordion', 'wpb-accordion-menu-or-category' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_all_posts( $this->shortcode_post_type ),
			)
		);

		$this->add_control(
			'show_in_popup',
			array(
				'label'        => esc_html__( 'Using in Elementor Popup', 'wpb-accordion-menu-or-category' ),
				'description'  => esc_html__( 'Check this if this accordion is using in a Elementor popup.', 'wpb-accordion-menu-or-category' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wpb-accordion-menu-or-category' ),
				'label_off'    => esc_html__( 'No', 'wpb-accordion-menu-or-category' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		if ( $settings['shortcode_id'] && '' !== $settings['shortcode_id'] ) {
			printf( '<div class="wpb-wamc-elementor-widget%s">', ( 'yes' === $settings['show_in_popup'] ? ' wpb-wamc-elementor-widget-show-in-popup' : '' ) );
				echo do_shortcode( '[' . $this->shortcode_tag . ' id="' . esc_attr( $settings['shortcode_id'] ) . '"]' );
			echo '</div>';
		}
	}
}
