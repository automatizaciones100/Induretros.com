<?php

/**
 * Plugin Name:       WPB Accordion Menu or Category
 * Plugin URI:        https://wpbean.com/downloads/wpb-accordion-menu-category-pro/
 * Description:       You may display the WordPress menu or any categories in a nice accordion with submenu and subcategory support by using the WPB Accordion Menu or Category Plugin. For WooCommerce websites, it can be really helpful.
 * Version:           1.8.4
 * Author:            WPBean
 * Author URI:        https://wpbean.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpb-accordion-menu-or-category
 * Domain Path:       /languages
 *
 * @package WPB Accordion Menu or Category
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * The main plugin class
 */
final class WpBean_Accordion_Menu
{
	/**
	 * Form Popup version.
	 *
	 * @var string
	 */
	public $version = '1.8.4';

	/**
	 * Instance
	 *
	 * @access private
	 * @static
	 *
	 * @var Plugin The single instance of the class.
	 */
	private static $instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @access public
	 *
	 * @return Plugin An instance of the class.
	 */
	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Class Constructor.
	 */
	private function __construct()
	{
		$this->define_constants();

		if (! defined('WPB_WAMC_PREMIUM')) {
			add_action('plugins_loaded', array($this, 'plugin_init'));
		}
		register_activation_hook(__FILE__, array($this, 'plugin_activation'));
		register_deactivation_hook(plugin_basename(__FILE__), array($this, 'plugin_deactivation'));
	}

	/**
	 * Define plugin Constants.
	 */
	public function define_constants()
	{
		define('WPB_WAMC_FREE_VERSION', $this->version);
		define('WPB_WAMC_FREE_INIT', plugin_basename(__FILE__));
		define('WPB_WAMC_PLUGIN_PATH', trailingslashit(plugin_dir_path(__FILE__)));
		define('WPB_WAMC_TEMPLATE_PATH', WPB_WAMC_PLUGIN_PATH . '/templates/');
		define('WPB_WAMC_THEME_DIR_PATH', 'wpb-accordion-menu/');

		define('WPB_WAMC_FILE', __FILE__);
		define('WPB_WAMC_URL', plugins_url('', WPB_WAMC_FILE));
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function plugin_init()
	{
		$this->include_files();
		$this->init_classes();
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
		add_action('after_setup_theme', array($this, 'classic_menu_support'));
	}

	/**
	 * Include the files.
	 *
	 * @return void
	 */
	public function include_files()
	{
		require_once __DIR__ . '/inc/functions.php';
		require_once __DIR__ . '/inc/helper/class.category-walker.php';
		require_once __DIR__ . '/inc/helper/class.posts-walker.php';
		require_once __DIR__ . '/inc/helper/class.woocommerce.php';
		require_once __DIR__ . '/inc/wpb-wmca-shortcodes.php';
		require_once __DIR__ . '/inc/widgets/class.widgets-register.php';
		require_once __DIR__ . '/inc/widgets/class.accordion-widget.php';
		require_once __DIR__ . '/inc/elementor/elementor.php';
		require_once __DIR__ . '/inc/blocks/accordion.php';
		require_once __DIR__ . '/frontend/shortcode.php';

		if (did_action('elementor/loaded')) {
			require_once __DIR__ . '/elementor/wpb-wmca-elementor.php';
		}
		if (is_admin()) {
			require_once __DIR__ . '/admin/MetaAPI/MetaAPI.php';
			require_once __DIR__ . '/admin/shortcodebuilder/class.shortcode-cpt.php';
			require_once __DIR__ . '/admin/shortcodebuilder/class.shortcode-meta.php';
			require_once __DIR__ . '/admin/class.admin-page.php';
			require_once __DIR__ . '/admin/class.discount-notice.php';
		}
	}

	/**
	 * Initialize the classes.
	 *
	 * @return void
	 */
	public function init_classes()
	{
		new WPB_Accordion_Menu_ShortCode();
		new WPB_Accordion_Menu_Widget_Register();
		if (is_admin()) {
			new WPBean_Accordion_Menu_Discount_Notice();
			new WPBean_Accordion_ShortCode_CPT();
			new WPBean_Accordion_ShortCode_Meta();
			new WPBean_Accordion_Menu_Admin_Page();
		}
		if (did_action('elementor/loaded')) {
			WPBean_Accordion_Menu_Elementor::instance();
		}
		WPBean_Accordion_Menu_Block::instance();
		new WPB_WCMA_WooCommerce();
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts()
	{
		$cookie = apply_filters('wpb_wmca_jquery_cookie', true);
		if (true === $cookie) {
			wp_enqueue_script('wpb_wmca_jquery_cookie', plugins_url('assets/js/jquery.cookie.js', __FILE__), array('jquery'), '1.0', true);
		}
		wp_enqueue_script('wpb_wmca_accordion_script', plugins_url('assets/js/jquery.navgoco.min.js', __FILE__), array('jquery'), '1.0', true);
		wp_enqueue_script('wpb_wmca_accordion_init', plugins_url('assets/js/accordion-init.js', __FILE__), array('jquery', 'wpb_wmca_accordion_script'), '1.0', true);
		wp_enqueue_style('wpb_wmca_accordion_style', plugins_url('assets/css/wpb_wmca_style.css', __FILE__), '', '1.0');
	}

	/**
	 * Add plugin action links
	 *
	 * @param array $links  An array of plugin action links.
	 * @return array
	 */
	public function plugin_action_links($links)
	{

		$links[] = sprintf('<a href="%s">%s</a>', esc_url(admin_url('admin.php?page=wpb_wmca_shortcodes-items')), esc_html__('Accordions', 'wpb-accordion-menu-or-category'));
		$links[] = '<a href="https://docs.wpbean.com/docs/wpb-accordion-menu-or-category-pro-new-version/installing/" target="_blank">' . esc_html__('Docs', 'wpb-accordion-menu-or-category') . '</a>';
		$links[] = '<a href="https://wpbean.com/downloads/wpb-accordion-menu-category-pro/?utm_content=WPB+Accordion+Menu+Pro&utm_campaign=adminlink&utm_medium=action-link&utm_source=FreeVersion" target="_blank" style="color: #39b54a; font-weight: 700;">' . esc_html__('Go Pro', 'wpb-accordion-menu-or-category') . '</a>';

		return $links;
	}

	/**
	 * Add classic navigation menu support if needed.
	 *
	 * @return void
	 */
	public function classic_menu_support() {
	    add_theme_support('menus');
	}

	/**
	 * Plugin Activation.
	 *
	 * @return void
	 */
	public function plugin_activation() {}

	/**
	 * Plugin Deactivation.
	 *
	 * @return void
	 */
	public function plugin_deactivation()
	{
		$user_id = get_current_user_id();
		if (get_user_meta($user_id, 'wpb_wmca_pro_discount_dismissed')) {
			delete_user_meta($user_id, 'wpb_wmca_pro_discount_dismissed');
		}
	}
}

/**
 * Initialize the main plugin.
 *
 * @return \WpBean_Accordion_Menu
 */

WpBean_Accordion_Menu::instance();