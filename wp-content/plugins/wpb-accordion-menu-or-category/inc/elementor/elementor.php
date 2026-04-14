<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Elementor
 *
 * Main Elementor class
 *
 * @since 1.0
 */
class WPBean_Accordion_Menu_Elementor {

	/**
	 * Instance
	 *
	 * @since 1.2.0
	 * @access private
	 * @static
	 *
	 * @var Elementor The single instance of the class.
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
	 * @return Elementor An instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register Widgets
	 *
	 * Register new Elementor widgets.
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @param Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		// Its is now safe to include Widgets files.
		require_once __DIR__ . '/widgets/accordion.php';

		// Register Widgets.
		$widgets_manager->register( new WPBean_Accordion_Menu_Elementor_Widget() );
	}

	/**
	 *  Elementor class constructor
	 *
	 * Register Elementor action hooks and filters
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function __construct() {
		add_action( 'elementor/init', array( $this, 'init' ) );
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
		wp_register_script( 'wpb_wmca_accordion_init', plugins_url( '../assets/js/accordion-init.js', __FILE__ ), array( 'jquery' ), '1.0', true );
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
		wp_register_style( 'wpb_wmca_accordion_style', plugins_url( '../assets/css/wpb_wmca_style.css', __FILE__ ), '', '1.0' );
	}

	/**
	 * Initialize
	 *
	 * Load the addons functionality only after Elementor is initialized.
	 *
	 * Fired by `elementor/init` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'widget_scripts' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'widget_styles' ) );
	}
}
