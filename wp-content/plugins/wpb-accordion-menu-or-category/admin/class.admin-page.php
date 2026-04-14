<?php

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Admin page class
 */
class WPBean_Accordion_Menu_Admin_Page
{
	/**
	 * ShortCode Tag
	 *
	 * @var [type] String
	 */
	private $shortcode_tag = 'wpb_wmca_accordion_pro';

	/**
	 * Meta API
	 *
	 * @var [type] void
	 */
	private $meta_api;

	/**
	 * Class Constructor.
	 */
	public function __construct()
	{
		add_action('init', function () {
			$this->meta_api = new WPBean_Accordion_Menu_MetaAPI();
			$this->meta_api->set_sections($this->get_meta_sections());
		});

		add_action('admin_menu', array($this, 'admin_menu'), 10);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		if (defined('DOING_AJAX') && DOING_AJAX) {
			add_action('wp_ajax_wpb-fire-shortcode-popup', array($this, 'shortcode_popup_body'));
			add_action('wp_ajax_wpb-fire-duplicate-shortcode', array($this, 'duplicate_shortcode'));
			add_action('wp_ajax_wpb-fire-delete-shortcode', array($this, 'delete_shortcode'));
			add_action('wp_ajax_wpb-fire-add-shortcode', array($this, 'add_shortcode'));
			add_action('wp_ajax_wpb_am_fire_save_shortcode', array($this, 'save_shortcodes_meta'));
		}
		add_filter('admin_footer_text', array($this, 'admin_footer_text'));
	}

	/**
	 * Get taxonomies for the meta options.
	 *
	 * @return array
	 */
	public function wpb_get_taxonomies()
	{
		$options    = array();
		$texonomies = get_taxonomies(array('public' => true));

		if (array_key_exists('post_format', $texonomies)) {
			unset($texonomies['post_format']);
		}
		if (array_key_exists('product_shipping_class', $texonomies)) {
			unset($texonomies['product_shipping_class']);
		}

		if (! is_wp_error($texonomies) && ! empty($texonomies)) {
			foreach ($texonomies as $taxonomy) {
				$options[esc_attr($taxonomy)] = esc_attr($taxonomy);
			}
		}

		return $options;
	}

	/**
	 * Get posts type for the meta options.
	 *
	 * @return array
	 */
	public function wpb_get_post_types()
	{
		$options    = array();
		$post_types = get_post_types(array('public' => true));

		if (array_key_exists('e-landing-page', $post_types)) {
			unset($post_types['e-landing-page']);
		}
		if (array_key_exists('elementor_library', $post_types)) {
			unset($post_types['elementor_library']);
		}
		if (array_key_exists('attachment', $post_types)) {
			unset($post_types['attachment']);
		}

		if (! is_wp_error($post_types) && ! empty($post_types)) {
			foreach ($post_types as $post_type) {
				$post_type_obj                     = get_post_type_object($post_type);
				$options[esc_attr($post_type)] = esc_attr($post_type_obj->label);
			}
		}

		return $options;
	}

	/**
	 * Get hierarchical posts type for the meta options.
	 *
	 * @return array
	 */
	public function wpb_get_hierarchical_post_types()
	{
		$options    = array();
		$post_types = $this->wpb_get_post_types();

		if (! is_wp_error($post_types) && ! empty($post_types)) {

			foreach ($post_types as $key => $post_type) {
				if (is_post_type_hierarchical($key)) {
					$options[esc_attr($key)] = esc_attr($post_type);
				}
			}
		}

		return $options;
	}

	/**
	 * Returns all the meta sections
	 *
	 * @return array meta sections
	 */
	public function get_meta_sections()
	{
		$sections = array(
			array(
				'id'    => 'wpbean_accordion_menu_data_settings',
				'title' => esc_html__('Data', 'wpb-accordion-menu-or-category'),
				'icon'  => 'data:image/svg+xml;base64,' . base64_encode(
					'<svg width="800px" height="800px" viewBox="0 0 1024 1024" fill="#000000" class="icon"  version="1.1" xmlns="http://www.w3.org/2000/svg"><path d="M512 0C250.733601 0 46.039591 111.952387 46.039591 254.906974v514.053564c0 142.954587 204.69401 254.906974 465.960409 254.906974s465.960409-111.952387 465.960409-254.906974v-514.053564C977.827921 111.952387 773.266399 0 512 0zM125.53241 508.224091c0-20.535645 9.009186-41.07129 26.762583-61.209471 5.696985-6.359426 8.74421-14.176219 9.671626-21.993013 84.792341 52.465261 209.066115 85.057317 350.033381 85.057317 139.642386 0 263.121232-32.062104 347.781084-83.599949 1.589856 6.491914 4.504593 12.586363 9.274162 17.885885 19.475741 21.065597 29.279855 42.528658 29.279856 63.991719 0 82.937508-158.720662 175.414154-386.46759 175.414155S125.53241 591.161599 125.53241 508.224091zM512 79.492819c227.746927 0 386.46759 92.476646 386.46759 175.414155S739.746927 430.453616 512 430.453616 125.53241 337.97697 125.53241 254.906974 284.253073 79.492819 512 79.492819z m0 865.014362C284.253073 944.507181 125.53241 852.030534 125.53241 768.960538c0-20.535645 9.009186-41.07129 26.762583-61.209471 7.41933-8.479234 10.599043-19.210765 9.804114-29.544831 84.792341 52.332773 209.066115 84.924829 349.900893 84.924829 139.112434 0 262.061327-31.797128 346.72118-82.937508 0.529952 8.876698 3.842153 17.753396 10.334066 24.775262 19.475741 21.065597 29.279855 42.528658 29.279856 63.991719 0 83.069996-158.588174 175.546643-386.335102 175.546643z" /></svg>'
				), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			),
			array(
				'id'    => 'wpbean_accordion_menu_accordion_settings',
				'title' => esc_html__('Accordion', 'wpb-accordion-menu-or-category'),
				'icon'  => 'data:image/svg+xml;base64,' . base64_encode(
					'<svg width="800px" height="800px" viewBox="0 0 16 16" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path fill="#444" d="M0 4v8h16v-8h-16zM15 11h-14v-4h14v4z"></path><path fill="#444" d="M0 0h16v3h-16v-3z"></path><path fill="#444" d="M0 13h16v3h-16v-3z"></path></svg>'
				), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			),
			array(
				'id'    => 'wpbean_accordion_menu_style_settings',
				'title' => esc_html__('Style Settings', 'wpb-accordion-menu-or-category'),
				'icon'  => 'data:image/svg+xml;base64,' . base64_encode(
					'<svg fill="#000000" width="800px" height="800px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g data-name="Layer 2"><g data-name="color-palette"><rect width="24" height="24" opacity="0"/><path d="M19.54 5.08A10.61 10.61 0 0 0 11.91 2a10 10 0 0 0-.05 20 2.58 2.58 0 0 0 2.53-1.89 2.52 2.52 0 0 0-.57-2.28.5.5 0 0 1 .37-.83h1.65A6.15 6.15 0 0 0 22 11.33a8.48 8.48 0 0 0-2.46-6.25zm-12.7 9.66a1.5 1.5 0 1 1 .4-2.08 1.49 1.49 0 0 1-.4 2.08zM8.3 9.25a1.5 1.5 0 1 1-.55-2 1.5 1.5 0 0 1 .55 2zM11 7a1.5 1.5 0 1 1 1.5-1.5A1.5 1.5 0 0 1 11 7zm5.75.8a1.5 1.5 0 1 1 .55-2 1.5 1.5 0 0 1-.55 2z"/></g></g></svg>'
				), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			),
		);

		return apply_filters('wpbean_accordion_menu_shortcode_builder_sections', $sections);
	}

	/**
	 * Returns all the meta fields
	 *
	 * @return array meta fields
	 */
	public function get_meta_fields()
	{
		$fields = array();

		/**
		 * Data options.
		 */
		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_data_socure',
			'label'             => esc_html__('Data Source', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Choose the accordion type that you want.', 'wpb-accordion-menu-or-category'),
			'type'              => 'select',
			'size'              => 'wpbean-sb-select-buttons',
			'default'           => 'menu',
			'sanitize_callback' => 'sanitize_text_field',
			'options'           => array(
				'menu'     => esc_html__('Menu', 'wpb-accordion-menu-or-category'),
				'taxonomy' => esc_html__('Taxonomy', 'wpb-accordion-menu-or-category'),
				'posts'    => esc_html__('Hierarchical Posts', 'wpb-accordion-menu-or-category'),
			),
		);

		// Data options for menu.
		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_menu_id',
			'label'             => esc_html__('Select a Menu', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Create a menu and return here if you haven\'t already.', 'wpb-accordion-menu-or-category'),
			'type'              => 'select',
			'sanitize_callback' => 'sanitize_text_field',
			'options'           => wp_list_pluck(get_terms('nav_menu'), 'name', 'term_id'),
			'condition'         => array('wpb_wmca_data_socure', 'menu'),
		);

		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_menu_depth',
			'label'             => esc_html__('Menu Depth', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('How many levels of the hierarchy are to be included. 0 means all. Default 0.', 'wpb-accordion-menu-or-category'),
			'type'              => 'number',
			'placeholder'       => esc_html__('1', 'wpb-accordion-menu-or-category'),
			'size'              => 'small',
			'sanitize_callback' => 'absint',
			'condition'         => array('wpb_wmca_data_socure', 'menu'),
		);

		// Data options for taxonomy.
		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_taxonomy',
			'label'             => esc_html__('Select a Taxonomy', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Choose a taxonomy to display in the accordion.', 'wpb-accordion-menu-or-category'),
			'type'              => 'select',
			'sanitize_callback' => 'sanitize_text_field',
			'options'           => $this->wpb_get_taxonomies(),
			'condition'         => array('wpb_wmca_data_socure', 'taxonomy'),
		);

		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_tax_orderby',
			'label'             => esc_html__('Orderby', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Choose a taxonomy orderby option.', 'wpb-accordion-menu-or-category'),
			'type'              => 'select',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'name',
			'options'           => array(
				'id'          => esc_html__('ID', 'wpb-accordion-menu-or-category'),
				'term_id'     => esc_html__('Term ID', 'wpb-accordion-menu-or-category'),
				'name'        => esc_html__('Name', 'wpb-accordion-menu-or-category'),
				'description' => esc_html__('Description', 'wpb-accordion-menu-or-category'),
				'parent'      => esc_html__('Parent', 'wpb-accordion-menu-or-category'),
				'count'       => esc_html__('Count', 'wpb-accordion-menu-or-category'),
				'include'     => esc_html__('Include', 'wpb-accordion-menu-or-category'),
				'slug'        => esc_html__('Slug', 'wpb-accordion-menu-or-category'),
				'term_group'  => esc_html__('Term Group', 'wpb-accordion-menu-or-category'),
				'term_order'  => esc_html__('Term Order', 'wpb-accordion-menu-or-category'),
				'menu_order'  => esc_html__('Menu Order', 'wpb-accordion-menu-or-category'),
			),
			'condition'         => array('wpb_wmca_data_socure', 'taxonomy'),
		);

		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_tax_order',
			'label'             => esc_html__('Order', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Choose a taxonomy order option.', 'wpb-accordion-menu-or-category'),
			'type'              => 'select',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'ASC',
			'options'           => array(
				'DESC' => esc_html__('DESC', 'wpb-accordion-menu-or-category'),
				'ASC'  => esc_html__('ASC', 'wpb-accordion-menu-or-category'),
			),
			'condition'         => array('wpb_wmca_data_socure', 'taxonomy'),
		);

		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_tax_show_count',
			'checkbox_label'    => esc_html__('Yes Please!', 'wpb-accordion-menu-or-category'),
			'label'             => esc_html__('Show Count', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Toggles the display of the current count of posts in each category.', 'wpb-accordion-menu-or-category'),
			'type'              => 'checkbox',
			'default'           => 'off',
			'sanitize_callback' => 'sanitize_text_field',
			'condition'         => array('wpb_wmca_data_socure', 'taxonomy'),
		);

		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_tax_hide_empty',
			'checkbox_label'    => esc_html__('Yes Please!', 'wpb-accordion-menu-or-category'),
			'label'             => esc_html__('Hide Empty', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Toggles the display of categories with no posts.', 'wpb-accordion-menu-or-category'),
			'type'              => 'checkbox',
			'default'           => 'off',
			'sanitize_callback' => 'sanitize_text_field',
			'condition'         => array('wpb_wmca_data_socure', 'taxonomy'),
		);

		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_tax_hide_out_of_stock',
			'checkbox_label'    => esc_html__('Yes Please!', 'wpb-accordion-menu-or-category'),
			'label'             => esc_html__('Hide Out of Stock', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Toggles the display of categories with out of stock products (Only for WooCommerce categories).', 'wpb-accordion-menu-or-category'),
			'type'              => 'checkbox',
			'default'           => 'off',
			'sanitize_callback' => 'sanitize_text_field',
			'condition'         => array('wpb_wmca_data_socure', 'taxonomy'),
		);

		// Data options for posts.
		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_hierarchical_post_type',
			'label'             => esc_html__('Select a Post Types', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('The post types must have a hierarchical structure. The accordion will not display post types that are not hierarchical.', 'wpb-accordion-menu-or-category'),
			'type'              => 'select',
			'sanitize_callback' => 'sanitize_text_field',
			'options'           => $this->wpb_get_hierarchical_post_types(),
			'condition'         => array('wpb_wmca_data_socure', 'posts'),
		);

		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_hierarchical_post_depth',
			'label'             => esc_html__('Posts Depth', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Number of levels in the hierarchy of posts to show. Accepts -1 (any depth), 0 (all pages), 1 (top-level pages only), and n (pages to the given n depth). Default 0.', 'wpb-accordion-menu-or-category'),
			'type'              => 'number',
			'placeholder'       => esc_html__('1', 'wpb-accordion-menu-or-category'),
			'default'           => 0,
			'size'              => 'small',
			'sanitize_callback' => 'absint',
			'condition'         => array('wpb_wmca_data_socure', 'posts'),
		);

		$fields['wpbean_accordion_menu_data_settings'][] = array(
			'name'              => 'wpb_wmca_hierarchical_post_orderby',
			'label'             => esc_html__('Posts Orderby', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Default: post_title.', 'wpb-accordion-menu-or-category'),
			'type'              => 'select',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'post_title',
			'options'           => array(
				'post_title'        => esc_html__('Post Title', 'wpb-accordion-menu-or-category'),
				'ID'                => esc_html__('ID', 'wpb-accordion-menu-or-category'),
				'post_author'       => esc_html__('Post Author', 'wpb-accordion-menu-or-category'),
				'post_date'         => esc_html__('Post Date', 'wpb-accordion-menu-or-category'),
				'post_name'         => esc_html__('Post Name', 'wpb-accordion-menu-or-category'),
				'post_modified'     => esc_html__('Post Modified', 'wpb-accordion-menu-or-category'),
				'post_modified_gmt' => esc_html__('Post Modified GMT', 'wpb-accordion-menu-or-category'),
				'menu_order'        => esc_html__('Menu Order', 'wpb-accordion-menu-or-category'),
				'post_parent'       => esc_html__('Post Parent', 'wpb-accordion-menu-or-category'),
				'rand'              => esc_html__('Random', 'wpb-accordion-menu-or-category'),
				'comment_count'     => esc_html__('Comment Count', 'wpb-accordion-menu-or-category'),
			),
			'condition'         => array('wpb_wmca_data_socure', 'posts'),
		);

		/**
		 * Accordion Options.
		 */
		$fields['wpbean_accordion_menu_accordion_settings'][] = array(
			'name'              => 'wpb_wmca_collapse_previous',
			'checkbox_label'    => esc_html__('Yes Please!', 'wpb-accordion-menu-or-category'),
			'label'             => esc_html__('Collapse Previously Expanded Accordion', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('When expanding a new item, collapse the previously enlarged accordion.', 'wpb-accordion-menu-or-category'),
			'type'              => 'checkbox',
			'default'           => 'on',
			'sanitize_callback' => 'sanitize_text_field',
		);

		$fields['wpbean_accordion_menu_accordion_settings'][] = array(
			'name'              => 'wpb_wmca_keyboard_accessibility',
			'checkbox_label'    => esc_html__('Yes Please!', 'wpb-accordion-menu-or-category'),
			'label'             => esc_html__('Keyboard Accessibility', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Accordion keyboard accessibility support.', 'wpb-accordion-menu-or-category'),
			'type'              => 'checkbox',
			'sanitize_callback' => 'sanitize_text_field',
		);

		/**
		 * Style Options.
		 */
		$fields['wpbean_accordion_menu_style_settings'][] = array(
			'name'              => 'wpb_wmca_accordion_skin',
			'label'             => esc_html__('Choose a Skin', 'wpb-accordion-menu-or-category'),
			'desc'              => esc_html__('Choose a predetermined designed skin. Default: Drak.', 'wpb-accordion-menu-or-category'),
			'type'              => 'image_select',
			'size'              => 'large',
			'default'           => 'dark',
			'sanitize_callback' => 'sanitize_text_field',
			'options'           => array(
				'dark'        => WPB_WAMC_URL . '/admin/assets/images/skins/dark.png',
				'transparent' => WPB_WAMC_URL . '/admin/assets/images/skins/transparent.png',
			),
		);

		return apply_filters('wpbean_accordion_menu_shortcode_builder_fields', $fields);
	}

	/**
	 * Add Admin menu.
	 *
	 * @return void
	 */
	public function admin_menu()
	{
		add_menu_page(
			esc_html('WPB Accordion Menu'),
			esc_html('WPB Accordions'),
			apply_filters('wpbean_accordion_menu_shortcodes_list_page_capability', 'manage_options'),
			'wpb_wmca_shortcodes-items',
			array($this, 'shortcodes_admin_page'),
			'data:image/svg+xml;base64,' . base64_encode(
				'<svg width="20px" height="20px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill="#555" d="M18.1818182,0 C19.1859723,0 20,0.814027728 20,1.81818182 L20,18.1818182 C20,19.1859723 19.1859723,20 18.1818182,20 L1.81818182,20 C0.814027728,20 0,19.1859723 0,18.1818182 L0,1.81818182 C0,0.814027728 0.814027728,0 1.81818182,0 L18.1818182,0 Z M14.4598225,7.6248997 C14.1970199,7.35521364 13.7653526,7.34963347 13.4956665,7.61243605 L13.4956665,7.61243605 L10.0046519,11.0143458 L6.58558122,7.6170873 C6.31846398,7.35167415 5.8867628,7.35305558 5.62134964,7.62017282 C5.35593648,7.88729006 5.35731792,8.31899125 5.62443516,8.58440441 L5.62443516,8.58440441 L9.51942697,12.4545479 C9.78350334,12.7169396 10.2092302,12.71901 10.4758462,12.4591992 L10.4758462,12.4591992 L14.4473588,8.58905566 C14.7170449,8.32625309 14.7226251,7.89458576 14.4598225,7.6248997 Z"/></svg>'
			), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			apply_filters('shortcodes_list_page_position', 30),
		);

		add_submenu_page(
			'wpb_wmca_shortcodes-items',
			esc_html('WPB Accordions'),
			esc_html('WPB Accordions'),
			apply_filters('shortcodes_list_page_capability', 'manage_options'),
			'wpb_wmca_shortcodes-items',
			array($this, 'shortcodes_admin_page'),
			apply_filters('wpbean_accordion_menu_admin_page_position', 30),
		);

		// Add upgrade to pro submenu page.
		add_submenu_page(
			'wpb_wmca_shortcodes-items',
			esc_html('Upgrade to Pro'),
			esc_html('Upgrade to Pro'),
			apply_filters('shortcodes_list_page_capability', 'manage_options'),
			'wpb_wmca_upgrade-to-pro',
			array($this, 'upgrade_to_pro'),
			apply_filters('wpbean_accordion_menu_admin_page_position', 30),
		);

		// Add upgrade to pro submenu page.
		add_submenu_page(
			'wpb_wmca_shortcodes-items',
			esc_html('Get Help'),
			esc_html('Get Help'),
			apply_filters('shortcodes_list_page_capability', 'manage_options'),
			'wpb_wmca_get_help',
			array($this, 'get_help'),
			apply_filters('wpbean_accordion_menu_admin_page_position', 30),
		);

		// REMOVE THE SUBMENU CREATED BY add_menu_page.
		global $submenu;
		unset($submenu['wpb_wmca_shortcodes-items'][0]);
	}

	/**
	 * Upgrade to Pro page.
	 */
	public function upgrade_to_pro()
	{
		add_filter('allowed_redirect_hosts', function ($hosts) {
			$hosts[] = 'wpbean.com';
			return $hosts;
		});

		wp_safe_redirect('https://wpbean.com/downloads/wpb-accordion-menu-category-pro/');
		exit;
	}

	/**
	 * Get help page.
	 */
	public function get_help()
	{
		add_filter('allowed_redirect_hosts', function ($hosts) {
			$hosts[] = 'wpbean.com';
			return $hosts;
		});

		wp_safe_redirect('https://wpbean.com/support/');
		exit;
	}

	/**
	 * Admin scripts and styles.
	 *
	 * @param string $hook Action Hook.
	 * @return void
	 */
	public function enqueue_admin_scripts($hook)
	{
		if ('toplevel_page_wpb_wmca_shortcodes-items' !== $hook) {
			return;
		}
		wp_enqueue_style('wpb-accordion-menu-alertify', plugins_url('/assets/alertifyjs/css/alertify.min.css', __FILE__), array(), '1.0');
		wp_enqueue_style('wpb-accordion-menu-alertify-theme', plugins_url('/assets/alertifyjs/css/themes/default.min.css', __FILE__), array(), '1.0');
		wp_enqueue_style('wpb-accordion-menu-admin-page', plugins_url('/assets/css/admin-page.css', __FILE__), array(), '1.0');
		wp_enqueue_script('wpb-accordion-menu-alertify', plugins_url('/assets/alertifyjs/alertify.min.js', __FILE__), array('jquery'), '1.0', true);
		wp_enqueue_script('wpb-accordion-conditions', plugins_url('/assets/js/conditions.min.js', __FILE__), array('jquery'), '1.0', true);
		wp_enqueue_script('wpb-accordion-select-togglebutton', plugins_url('/assets/js/select-togglebutton.js', __FILE__), array('jquery'), '1.0', true);
		wp_enqueue_script('wpb-accordion-menu-admin-page', plugins_url('/assets/js/admin-page.js', __FILE__), array('jquery', 'wp-util', 'wp-i18n'), '1.0', true);
		wp_localize_script(
			'wpb-accordion-menu-admin-page',
			'WPBean_Accordion_Menu_Vars',
			array(
				'_wpbean_accordion_menu_nonce' => wp_create_nonce('wpbean-accordion-menu-admin-page-nonce'),
			)
		);

		wp_set_script_translations('wpb-accordion-menu-admin-page', 'wpb-accordion-menu-or-category');
	}

	/**
	 * ShortCodes admin menu page.
	 *
	 * @return void
	 */
	public function shortcodes_admin_page()
	{       ?>
		<div class="wpb-sb-shortcode-list-page-content">
			<div class="wpb-sb-list-header">
				<div class="wpb-sb-logo-and-page-title">
					<h3><?php echo esc_html__('WPB Accordion Menu', 'wpb-accordion-menu-or-category'); ?></h3>
				</div>
				<div class="wpb-sb-pro-and-version">
					<span class="wpb-sb-plan-status">
						<span class="wpb-sb-label"><?php echo esc_html__('You are on the', 'wpb-accordion-menu-or-category'); ?></span>
						<span class="wpb-sb-text"><?php echo esc_html__('Free Plan', 'wpb-accordion-menu-or-category'); ?></span>
					</span>
					<span class="wpb-sb-version">
						<span class="wpb-sb-label"><?php echo esc_html__('Version', 'wpb-accordion-menu-or-category'); ?></span>
						<span class="wpb-sb-text"><?php echo esc_html(WPB_WAMC_FREE_VERSION); ?></span>
					</span>
				</div>
			</div>
			<div class="wpb-sb-list-content">
				<div class="wpb-sb-list-content-left">
					<div class="wpb-sb-list-wrapper">
						<?php
						$nonce  = (! empty($_GET['_wpnonce'])) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
						$action = (! empty($_GET['action'])) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
						$id     = (! empty($_GET['id'])) ? absint(wp_unslash($_GET['id'])) : '';

						if (isset($id) && '' !== $id) {
							if (! wp_verify_nonce($nonce, 'wpb_accordion_menu_edit_shortcode_' . esc_attr($id))) {
								return;
							}
						}

						if ($action && 'edit' === $action) {
							$this->edit_shortcode_page_content();
						} else {
							$this->shortcodes_page_content();
						}
						?>
					</div>
				</div>
				<div class="wpb-sb-list-content-right">
					<div class="wpb-sb-list-additional-informations">
						<?php require_once __DIR__ . '/shortcodebuilder/additional-informations.php'; ?>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * ShortCodes page default content.
	 *
	 * @return void
	 */
	public function shortcodes_page_content()
	{
	?>
		<div class="wpb-sb-section-header wpb-sb-list-items-header">
			<h3>
				<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 64 64" style="enable-background:new 0 0 64 64;" xml:space="preserve">
					<g>
						<path d="M15.8,13.7H61c1,0,1.8-0.8,1.8-1.8S62,10.2,61,10.2H15.8c-1,0-1.8,0.8-1.8,1.8S14.9,13.7,15.8,13.7z"></path>
						<path d="M61,30.3H15.8c-1,0-1.8,0.8-1.8,1.8s0.8,1.8,1.8,1.8H61c1,0,1.8-0.8,1.8-1.8S62,30.3,61,30.3z"></path>
						<path d="M61,50.3H15.8c-1,0-1.8,0.8-1.8,1.8s0.8,1.8,1.8,1.8H61c1,0,1.8-0.8,1.8-1.8S62,50.3,61,50.3z"></path>
						<path d="M5.8,9.1C4.2,9.1,3,10.4,3,11.9s1.2,2.8,2.8,2.8s2.8-1.2,2.8-2.8S7.3,9.1,5.8,9.1z"></path>
						<path d="M5.8,29.2C4.2,29.2,3,30.5,3,32c0,1.5,1.2,2.8,2.8,2.8s2.8-1.2,2.8-2.8C8.6,30.5,7.3,29.2,5.8,29.2z"></path>
						<path d="M5.8,49.3c-1.5,0-2.8,1.2-2.8,2.8s1.2,2.8,2.8,2.8s2.8-1.2,2.8-2.8S7.3,49.3,5.8,49.3z"></path>
					</g>
				</svg><?php echo esc_html__('List of Accordion ShortCodes', 'wpb-accordion-menu-or-category'); ?>
			</h3>
			<a class="button button-primary wpb-sb-add-new" data-nonce="<?php echo esc_attr(wp_create_nonce('the_shortcode_add_nonce')); ?>"><?php echo esc_html__('Add New', 'wpb-accordion-menu-or-category'); ?>
				<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 64 64" style="enable-background:new 0 0 64 64;" xml:space="preserve">
					<path d="M61,30.3H33.8V3c0-1-0.8-1.8-1.8-1.8S30.3,2,30.3,3v27.3H3c-1,0-1.8,0.8-1.8,1.8S2,33.8,3,33.8h27.3V61c0,1,0.8,1.8,1.8,1.8s1.8-0.8,1.8-1.8V33.8H61c1,0,1.8-0.8,1.8-1.8S62,30.3,61,30.3z"></path>
				</svg>
			</a>
		</div>
	<?php
		$this->shortcodes_list_items();
	}

	/**
	 * Edit shorcode page content.
	 *
	 * @return void
	 */
	public function edit_shortcode_page_content()
	{
		$nonce = (! empty($_GET['_wpnonce'])) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
		$id    = (! empty($_GET['id'])) ? absint(wp_unslash($_GET['id'])) : '';

		if (! wp_verify_nonce($nonce, 'wpb_accordion_menu_edit_shortcode_' . esc_attr($id))) {
			return;
		}
	?>
		<div class="wpb-sb-section-header wpb-sb-list-items-header">
			<div class="wpbean-sb-section-header-left">
				<span class="wpbean-sb-back-to-shortcodes-page" title="<?php esc_html_e('Back', 'wpb-accordion-menu-or-category'); ?>"><a class="button button-secondary" href="<?php echo esc_url(remove_query_arg(array('id'), admin_url('admin.php?page=wpb_wmca_shortcodes-items'))); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M9 18l6-6-6-6" />
						</svg></a></span>
				<input type="text" name="wpb_sb_shortcode_title" size="30" value="<?php echo esc_html(get_the_title($id)); ?>" id="wpb_sb_shortcode_title">
			</div>
			<div class="wpbean-sb-section-header-right">
				<a class="button button-primary wpb-sb-save-meta-data" data-id="<?php echo esc_attr($id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wpb_sb_shortcode_save_nonce_' . esc_attr($id))); ?>"><?php echo esc_html__('Save Changes', 'wpb-accordion-menu-or-category'); ?></a>
				<button class="button button-secondary wpbean-sb-shortcode-shortcode-popup wpb-sb-shortcode-popup-trigger" title="<?php esc_html_e('Accordion ShortCode', 'wpb-accordion-menu-or-category'); ?>" data-id="<?php echo esc_attr($id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('the_shortcode_popup_nonce_' . esc_attr($id))); ?>" data-cancel="<?php echo esc_html__('Cancel', 'wpb-accordion-menu-or-category'); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="16 18 22 12 16 6"></polyline>
						<polyline points="8 6 2 12 8 18"></polyline>
					</svg><?php esc_html_e('ShortCode', 'wpb-accordion-menu-or-category'); ?></button>
			</div>
		</div>

		<div class="wpb-sb-item-edit-page-content">
			<?php $this->get_shortcode_item($id); ?>
		</div>
		<?php
	}

	/**
	 * Get a ShortCode Item Header + Body HTML
	 *
	 * @param int $shortcode_id The ShortCode post ID here.
	 * @return void
	 */
	public function get_shortcode_item($shortcode_id)
	{
		$title = get_the_title($shortcode_id);
		if ($shortcode_id) {
		?>
			<div class="wpbean-sb-shortcodes-list-item-wrapper" data-id="<?php echo esc_attr($shortcode_id); ?>">
				<form class="wpb-sb-tabs wpbean-sb-shortcodes-list-item wpbean-sb-shortcodes-list-item-<?php echo esc_attr($shortcode_id); ?>" data-id="<?php echo esc_attr($shortcode_id); ?>">
					<?php
					$this->meta_api->show_navigation($shortcode_id);
					$this->meta_api->show_fields($this->get_meta_fields(), $shortcode_id);
					?>
				</form>
			</div>
		<?php
		}
	}

	/**
	 * ShortCodes Items List.
	 *
	 * @return void
	 */
	public function shortcodes_list_items()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = (! empty($_GET['paged'])) ? absint(wp_unslash($_GET['paged'])) : '';

		$args = array(
			'post_type'   => 'wpb_wmca_shortcodes',
			'numberposts' => 10,
			'paged'       => (isset($paged) ? $paged : 1),
			'post_status' => 'publish',
		);

		$wp_query = new \WP_Query($args);
		echo '<div class="wpb-sb-shortcodes-list-items">';
		if ($wp_query->have_posts()) :
			while ($wp_query->have_posts()) :
				$wp_query->the_post();
				$this->shortcodes_list_item(get_the_ID());
			endwhile;

			$total_pages = $wp_query->max_num_pages;

			if ($total_pages > 1) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo paginate_links(
					array(
						'base'         => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
						'total'        => isset($wp_query->max_num_pages) ? $wp_query->max_num_pages : 1,
						'current'      => max(1, (isset($paged) ? $paged : 1)),
						'format'       => '?paged=%#%',
						'show_all'     => false,
						'type'         => 'list',
						'end_size'     => 2,
						'mid_size'     => 1,
						'prev_next'    => true,
						'prev_text'    => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>',
						'next_text'    => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>',
						'add_args'     => false,
						'add_fragment' => '',
					)
				);
			}

			wp_reset_postdata();
		endif;
		echo '</div>';
	}

	/**
	 * ShortCodes List Item.
	 *
	 * @param int $id Post ID.
	 * @return void
	 */
	public function shortcodes_list_item($id)
	{
		$edit_url = add_query_arg(
			array(
				'id'       => esc_attr($id),
				'action'   => 'edit',
				'_wpnonce' => wp_create_nonce('wpb_accordion_menu_edit_shortcode_' . $id),
			),
			admin_url('admin.php?page=wpb_wmca_shortcodes-items')
		);

		?>
		<div class="shortcodes-list-item" data-id="<?php echo esc_attr($id); ?>">
			<div class="shortcodes-list-item-details">
				<div class="shortcodes-list-item-id"><?php echo esc_html($id); ?></div>
				<div class="shortcodes-list-item-title">
					<h3><a href="<?php echo esc_url($edit_url); ?>" title="<?php esc_html_e('Edit ShortCode', 'wpb-accordion-menu-or-category'); ?>"><?php echo esc_html(get_the_title($id)); ?></a></h3>
					<span><?php echo esc_html(get_the_date('M j, Y g:i a', $id)); ?></span>
				</div>
			</div>
			<div class="shortcodes-list-item-actions">
				<span class="shortcodes-list-item-delete" title="<?php esc_html_e('Delete Accordion', 'wpb-accordion-menu-or-category'); ?>" data-id="<?php echo esc_attr($id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('the_shortcode_delete_nonce_' . esc_attr($id))); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="3 6 5 6 21 6"></polyline>
						<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
						<line x1="10" y1="11" x2="10" y2="17"></line>
						<line x1="14" y1="11" x2="14" y2="17"></line>
					</svg></span>
				<span class="shortcodes-list-item-duplicate" title="<?php esc_html_e('Duplicate Accordion', 'wpb-accordion-menu-or-category'); ?>" data-id="<?php echo esc_attr($id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('the_shortcode_duplicate_nonce_' . esc_attr($id))); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
						<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
					</svg></span>
				<span class="shortcodes-list-item-shortcode-popup wpb-sb-shortcode-popup-trigger" title="<?php esc_html_e('Accordion ShortCode', 'wpb-accordion-menu-or-category'); ?>" data-id="<?php echo esc_attr($id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('the_shortcode_popup_nonce_' . esc_attr($id))); ?>" data-cancel="<?php echo esc_html__('Cancel', 'wpb-accordion-menu-or-category'); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="16 18 22 12 16 6"></polyline>
						<polyline points="8 6 2 12 8 18"></polyline>
					</svg></span>
				<span class="shortcodes-list-item-details" title="<?php esc_html_e('Edit Accordion', 'wpb-accordion-menu-or-category'); ?>"><a href="<?php echo esc_url($edit_url); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M9 18l6-6-6-6" />
						</svg></a></span>
			</div>
		</div>
	<?php
	}

	/**
	 * ShortCode Popup Content.
	 *
	 * @return void
	 */
	public function shortcode_popup_body()
	{
		$shortcode_id = (! empty($_POST['shortcode_id'])) ? absint($_POST['shortcode_id']) : '';
		$nonce        = (! empty($_POST['nonce'])) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		$tabs         = array('ShortCode', 'PHP Code', 'Widget', 'Gutenberg', 'Elementor');

		if (! wp_verify_nonce($nonce, 'the_shortcode_popup_nonce_' . esc_attr($shortcode_id))) {
			wp_send_json_error(
				array(
					'error_title'   => esc_html__('Oops...', 'wpb-accordion-menu-or-category'),
					'error_message' => esc_html__('Nonce verification failed.', 'wpb-accordion-menu-or-category'),
				)
			);
		}

		ob_start();
	?>
		<div class="wpb-sb-shortcode-popup wpb-sb-shortcode-popup-wrapper">
			<?php if (isset($tabs) && ! empty($tabs)) : ?>
				<div class="wpb-sb-tabs">
					<div class="wpb-sb-tabs-nav">
						<ul>
							<?php
							foreach ($tabs as $tab) {
								printf('<li><a href="#wpb-sb-tab-%s">%s</a></li>', esc_attr(sanitize_title($tab)), esc_html($tab));
							}
							?>
						</ul>
					</div>

					<div class="wpb-sb-tabs-content-wrapper">
						<?php foreach ($tabs as $tab) : ?>
							<div id="wpb-sb-tab-<?php echo esc_attr(sanitize_title($tab)); ?>" class="wpb-sb-tab-content">
								<?php
								switch (sanitize_title($tab)) {
									case 'shortcode':
										printf('<p>%s</p>', esc_html__('Copy the ShortCode below and paste it anywhere you want it to appear.', 'wpb-accordion-menu-or-category'));
										printf('<div class="wpb-sb-copy-shortcode-wrapper"><div class="wpb-sb-copy-shortcode"><div class="wpb-sb-copy-shortcode-text">[%s id="%s"]</div><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(0,0,0,0.2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg></div></div>', esc_attr($this->shortcode_tag), esc_attr($shortcode_id));
										break;

									case 'php-code':
										printf('<p>%s</p>', esc_html__('Copy the PHP Code below and paste it anywhere you want it to appear.', 'wpb-accordion-menu-or-category'));
										printf('<div class="wpb-sb-copy-shortcode-wrapper"><div class="wpb-sb-copy-shortcode"><div class="wpb-sb-copy-shortcode-text"> &#60;?php echo do_shortcode(\'[%s id="%s"]\'); ?&#62; </div><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(0,0,0,0.2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg></div></div>', esc_attr($this->shortcode_tag), esc_attr($shortcode_id));
										break;

									case 'widget':
										printf('<p>%s</p>', esc_html__('Navigate to the widgets page and drag and drop the plugin\'s widget into the desired spot.', 'wpb-accordion-menu-or-category'));
										printf('<a href="%s" class="button button-secondary button-block">%s</a>', esc_url(admin_url('widgets.php')), esc_html__('Go to the Widgets Page', 'wpb-accordion-menu-or-category'));
										break;

									case 'elementor':
										printf('<p>%s</p>', esc_html__('Navigate to the Elementor editor page and drag and drop the plugin\'s widgets into the desired spot.', 'wpb-accordion-menu-or-category'));
										break;

									case 'gutenberg':
										printf('<p>%s</p>', esc_html__('Navigate to the Gutenberg editor page and drag and drop the plugin\'s block into the desired spot.', 'wpb-accordion-menu-or-category'));
										break;

									default:
										break;
								}
								?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
		</div>
	<?php endif; ?>
	</div>
<?php
		$content = ob_get_clean();
		wp_send_json_success(
			array(
				'title'   => '#' . esc_html($shortcode_id) . ' - ' . esc_html(get_the_title($shortcode_id)),
				'content' => $content,
			)
		);
	}

	/**
	 * Duplicate ShortCode.
	 *
	 * @return void
	 */
	public function duplicate_shortcode()
	{
		global $wpdb;
		$shortcode_id = (! empty($_POST['shortcode_id'])) ? absint($_POST['shortcode_id']) : '';
		$nonce        = (! empty($_POST['nonce'])) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

		if (! wp_verify_nonce($nonce, 'the_shortcode_duplicate_nonce_' . esc_attr($shortcode_id))) {
			wp_send_json_error(
				array(
					'error_title'   => esc_html__('Oops...', 'wpb-accordion-menu-or-category'),
					'error_message' => esc_html__('Nonce verification failed.', 'wpb-accordion-menu-or-category'),
				)
			);
		}

		$post = get_post($shortcode_id);
		$data = get_post_custom($shortcode_id);

		/*
		* if you don't want current user to be the new post author,
		* then change next couple of lines to this: $new_post_author = $post->post_author;
		*/
		$current_user    = wp_get_current_user();
		$new_post_author = $current_user->ID;

		// if post data exists (I am sure it is, but just in a case), create the post duplicate.
		if ($post) {

			$args = array(
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => 'publish',
				'post_title'     => $post->post_title . esc_html__(' - Copy', 'wpb-accordion-menu-or-category'),
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order,
			);

			// Insert the post by wp_insert_post() function.
			$new_post_id = wp_insert_post($args);

			// Duplicate all post meta.
			foreach ($data as $key => $values) {
				foreach ($values as $value) {
					add_post_meta($new_post_id, $key, $value);
				}
			}

			// Set content for new post and send it through wp_send_json_success. After that show it just before the original post item.
			ob_start();
			$this->shortcodes_list_item($new_post_id);
			$content = ob_get_clean();
		} else {
			wp_send_json_error(
				array(
					'error_message' => esc_html__('Oops, Operation Failed.', 'wpb-accordion-menu-or-category'),
				)
			);
		}

		if (isset($content) && '' !== $content) {
			wp_send_json_success(
				array(
					'content'         => $content,
					'success_message' => esc_html__('Duplication Completed.', 'wpb-accordion-menu-or-category'),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'error_message' => esc_html__('Oops, Operation Failed.', 'wpb-accordion-menu-or-category'),
				)
			);
		}
	}

	/**
	 * Delete ShortCode.
	 *
	 * @return void
	 */
	public function delete_shortcode()
	{
		$shortcode_id = (! empty($_POST['shortcode_id'])) ? absint($_POST['shortcode_id']) : '';
		$nonce        = (! empty($_POST['nonce'])) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

		if (! wp_verify_nonce($nonce, 'the_shortcode_delete_nonce_' . esc_attr($shortcode_id))) {
			wp_send_json_error(
				array(
					'error_title'   => esc_html__('Oops...', 'wpb-accordion-menu-or-category'),
					'error_message' => esc_html__('Nonce verification failed.', 'wpb-accordion-menu-or-category'),
				)
			);
		}

		if ($shortcode_id) {
			wp_delete_post($shortcode_id, true);

			wp_send_json_success(esc_html__('Accordion Deleted Successfully.', 'wpb-accordion-menu-or-category'));
		}
	}

	/**
	 * Add ShortCode.
	 *
	 * @return void
	 */
	public function add_shortcode()
	{
		$nonce = (! empty($_POST['nonce'])) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		$title = (! empty($_POST['title'])) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';

		if (! wp_verify_nonce($nonce, 'the_shortcode_add_nonce')) {
			wp_send_json_error(
				array(
					'error_title'   => esc_html__('Oops...', 'wpb-accordion-menu-or-category'),
					'error_message' => esc_html__('Nonce verification failed.', 'wpb-accordion-menu-or-category'),
				)
			);
		}

		$post = array(
			'post_status' => 'publish',
			'post_type'   => 'wpb_wmca_shortcodes',
			'post_title'  => esc_html($title),
		);

		$shortcode_id = wp_insert_post($post);

		if ($shortcode_id) {
			ob_start();
			$this->shortcodes_list_item($shortcode_id);
			$response = ob_get_clean();
			wp_send_json_success($response);
		}
	}

	/**
	 * Clean variables using sanitize_text_field and wp_kses_post. Arrays are cleaned recursively.
	 * Non-scalar values are ignored.
	 *
	 * @param string|array $data Data to sanitize.
	 * @return string|array
	 */
	public function wpbean_clean($data)
	{
		if (is_array($data)) {
			return array_map(array($this, 'wpbean_clean'), $data);
		} elseif (wp_strip_all_tags($data) !== $data) {
			return $data;
		} else {
			return is_scalar($data) ? sanitize_text_field($data) : $data;
		}
	}

	/**
	 * Return meta field sanitize callback.
	 *
	 * @param string $name The meta field name.
	 * @param array  $sections The meta field section.
	 * @return string
	 */
	public function get_sanitize_callback($name, $sections)
	{
		if (is_array($sections) && isset($sections) && ! empty($sections)) {
			foreach ($sections as $section) {
				if (is_array($section) && isset($section) && ! empty($section)) {
					foreach ($section as $feild) {
						if ('group' !== $feild['type']) {
							if ($feild['name'] === $name) {
								return $feild['sanitize_callback'];
							}
						} else {
							foreach ($feild['options'] as $group_feild) {
								if ($group_feild['name'] === $name) {
									return $group_feild['sanitize_callback'];
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Save ShortCode.
	 *
	 * @return void
	 */
	public function save_shortcodes_meta()
	{
		check_ajax_referer('wpbean-accordion-menu-admin-page-nonce', '_wpb_am_save_meta_nonce'); // Verify the nonce.

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$forms_data      = isset($_POST['_wpb_am_forms_data']) ? array_map(array($this, 'wpbean_clean'), (array) wp_unslash($_POST['_wpb_am_forms_data'])) : array();
		$shortcode_title = isset($_POST['_wpb_am_shortcode_title']) ? sanitize_text_field(wp_unslash($_POST['_wpb_am_shortcode_title'])) : '';

		foreach ($forms_data as $form_data) {

			$form_data = json_decode($form_data);

			$post_id = $form_data->post_id;

			// Update the shortcode title.
			if ($shortcode_title && '' !== $shortcode_title) {
				$post = array(
					'ID'         => $post_id,
					'post_title' => esc_html($shortcode_title),
				);

				if (! is_wp_error($post)) {
					wp_update_post($post);
				}
			}

			foreach ($form_data as $meta_key => $meta_value) {

				$old_value = get_post_meta(absint($post_id), $meta_key, true);

				if (is_array($meta_value)) {
					$array_meta_key = str_replace(array('[', ']'), '', $meta_key);
					$array_sanitize = $this->get_sanitize_callback($array_meta_key, $this->get_meta_fields());
					$value          = isset($meta_value) ? $array_sanitize(wp_unslash($meta_value)) : '';
				} else {
					if ('post_id' === $meta_key) {
						$sanitize = 'absint';
					} else {
						$sanitize = $this->get_sanitize_callback($meta_key, $this->get_meta_fields());
					}
					$value = isset($meta_value) && '' !== $meta_value ? $sanitize(wp_unslash($meta_value)) : '';
				}

				if ($old_value !== $value) {
					update_post_meta($post_id, $meta_key, $value);
				}
			}
		}

		wp_send_json_success(esc_html__('Saved Successfully!', 'wpb-accordion-menu-or-category'));
	}

	/**
	 * Admin Page Footer Text.
	 *
	 * @return string
	 */
	public function admin_footer_text()
	{
		$screen = get_current_screen();
		if ($screen && 'toplevel_page_wpb_wmca_shortcodes-items' === $screen->id) {
			return sprintf(
				'<span class="wpb-footer-thankyou">%1$s <strong>%2$s</strong>? %3$s <a href="%4$s" target="_blank">★★★★★</a> %5$s</span>',
				esc_html__('Enjoyed', 'wpb-accordion-menu-or-category'),
				esc_html__('WPB Accordion Menu', 'wpb-accordion-menu-or-category'),
				esc_html__('Please leave us a', 'wpb-accordion-menu-or-category'),
				esc_url('https://wordpress.org/support/plugin/wpb-accordion-menu-or-category/reviews/?rate=5#new-post'),
				esc_html__('rating. We really appreciate your support!', 'wpb-accordion-menu-or-category'),
			);
		}
	}
}
