<?php

namespace WpbWMCAFree\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor WPB Advanced FAQ
 *
 * Elementor widget for WPB Advanced FAQ.
 *
 * @since 1.0.0
 */
class Wpb_Accordion_Categories extends Widget_Base
{

	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'wpb-accordion-categories';
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
	public function get_title()
	{
		return esc_html__('WPB Accordion Categories [Deprecated]', 'wpb-accordion-menu-or-category');
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
	public function get_icon()
	{
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
	public function get_categories()
	{
		return array('general');
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
	public function get_script_depends()
	{
		return array('wpb_wmca_jquery_cookie', 'wpb_wmca_accordion_script', 'wpb_wmca_accordion_init');
	}

	/**
	 * Get post type taxonomies
	 */
	private function get_all_taxonies()
	{

		$options    = array('category' => 'category');
		$taxonomies = get_taxonomies(
			array(
				'public'   => true,
				'_builtin' => false,
			)
		);

		if (! empty($taxonomies)) {

			foreach ($taxonomies as $taxonomy) {
				$options[$taxonomy] = $taxonomy;
			}
		}

		return $options;
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_controls()
	{
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__('Content', 'wpb-accordion-menu-or-category'),
			)
		);

		$this->add_control(
			'title',
			array(
				'label' => esc_html__('Title', 'wpb-accordion-menu-or-category'),
				'type'  => Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'taxonomy',
			array(
				'label'   => esc_html__('Taxonomy', 'wpb-accordion-menu-or-category'),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_all_taxonies(),
				'default' => 'category',
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__('Order By', 'wpb-accordion-menu-or-category'),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'ID'         => 'ID',
					'name'       => 'name',
					'slug'       => 'slug',
					'count'      => 'count',
					'term_group' => 'term_group',
					'menu_order' => 'menu_order',
				),
				'default' => 'name',
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__('Order', 'wpb-accordion-menu-or-category'),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'ASC'  => 'ASC',
					'DESC' => 'DESC',
				),
				'default' => 'ASC',
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'        => esc_html__('Show Post Count', 'wpb-accordion-menu-or-category'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'wpb-accordion-menu-or-category'),
				'label_off'    => esc_html__('Hide', 'wpb-accordion-menu-or-category'),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'        => esc_html__('Hide Empty Categories', 'wpb-accordion-menu-or-category'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'wpb-accordion-menu-or-category'),
				'label_off'    => esc_html__('Hide', 'wpb-accordion-menu-or-category'),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'accordion',
			array(
				'label'        => esc_html__('Close Previously Opened Accordion Item', 'wpb-accordion-menu-or-category'),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'wpb-accordion-menu-or-category'),
				'label_off'    => esc_html__('No', 'wpb-accordion-menu-or-category'),
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
	protected function render()
	{
		$settings = $this->get_settings_for_display();

?>

		<div class="wpb-wmca-elementor-widget">
			<?php
			if ($settings['title']) {
				printf('<h3>%s</h3>', esc_html($settings['title']));
			}

			echo do_shortcode('[wpb_category_accordion taxonomy="' . $settings['taxonomy'] . '" orderby="' . $settings['orderby'] . '" order="' . $settings['order'] . '" show_count="' . $settings['show_count'] . '" hide_empty="' . $settings['hide_empty'] . '" accordion="' . $settings['accordion'] . '"]');
			?>
		</div>

<?php
	}
}
