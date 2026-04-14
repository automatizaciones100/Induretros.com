<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Product short description
 */
class kwt_size_chart_widget extends WP_Widget {
	private static $instance = 0;
	public function __construct() {
		$widget_ops = array(
			'classname' => 'widget_kwt_sizechart', 
			'description' => __('Use this widget to add a size cart button', 'kadence-woo-extras')
		);
		parent::__construct('widget_kwt_size_chart', __('Woo Product: Size Chart', 'kadence-woo-extras'), $widget_ops);
	}

	public function widget( $args, $instance ) {
		extract( $args );
		echo $before_widget;
		if ( is_product() ) {
			do_action('kadence_woocommerce_builder_sizechart');
		}
		echo $after_widget;
	}

}