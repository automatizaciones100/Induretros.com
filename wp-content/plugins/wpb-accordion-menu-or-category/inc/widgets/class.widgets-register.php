<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Widgets.
 */
class WPB_Accordion_Menu_Widget_Register {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register the widget.
	 *
	 * @return void
	 */
	public function register_widgets() {
		register_widget( 'WPB_Accordion_Menu_Widget' );
	}
}
