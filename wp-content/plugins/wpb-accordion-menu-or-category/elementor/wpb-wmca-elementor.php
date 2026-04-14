<?php
namespace WpbWMCAFree;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Plugin
 *
 * Main Plugin class
 *
 * @since 1.2.0
 */
class WPB_WMCA_Elementor_Widgets {

	/**
	 * Instance
	 *
	 * @since 1.2.0
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
	 * @since 1.2.0
	 * @access public
	 *
	 * @return Plugin An instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Widget Scripts
	 *
	 * Load required plugin core files.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function widget_scripts() {
		wp_register_script( 'wpb_wmca_jquery_cookie', plugins_url( '../assets/js/jquery.cookie.js', __FILE__ ), array( 'jquery' ), '1.4.1', true );
		wp_register_script( 'wpb_wmca_accordion_script', plugins_url( '../assets/js/jquery.navgoco.min.js', __FILE__ ), array( 'jquery' ), '1.0', true );
		wp_register_script( 'wpb_wmca_accordion_init', plugins_url( '../assets/js/accordion-init.js', __FILE__ ), array( 'jquery', 'wpb_wmca_accordion_script' ), '1.0', true );
	}

	/**
	 * Widget Styles
	 *
	 * Load required plugin core files.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function widget_styles() {
		wp_enqueue_style( 'wpb_wmca_accordion_style', plugins_url( '../assets/css/wpb_wmca_style.css', __FILE__ ), '', '1.0' );
	}

	/**
	 * Include Widgets files
	 *
	 * Load widgets files
	 *
	 * @since 1.2.0
	 * @access private
	 */
	private function include_widgets_files() {
		require_once __DIR__ . '/widgets/accordion-categories.php';
		require_once __DIR__ . '/widgets/accordion-menu.php';
	}

	/**
	 * Register new Elementor widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		// Its is now safe to include Widgets files.
		$this->include_widgets_files();

		// Register Widgets.
		$widgets_manager->register( new Widgets\Wpb_Accordion_Categories() );
		$widgets_manager->register( new Widgets\Wpb_Accordion_Menu() );
	}

	/**
	 *  Plugin class constructor
	 *
	 * Register plugin action hooks and filters
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function __construct() {
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'widget_scripts' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'widget_styles' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}
}

// Instantiate Plugin Class.
WPB_WMCA_Elementor_Widgets::instance();
