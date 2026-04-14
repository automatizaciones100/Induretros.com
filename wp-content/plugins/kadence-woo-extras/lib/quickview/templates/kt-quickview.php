<?php
/**
 * Single Quickview Content
 *
 * @version 9.0.0
 */

//preset $args;

global $product;

if (! defined('ABSPATH') ) {
    exit;
}
if($product) {
    $classes = array('kt-quickview-content', 'kt-quickview-content-' . esc_attr($product->get_id()));

    do_action('kt_quickview_before_content');
    echo '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        echo '<div class="kt-quickview-content-left">';
            do_action('kt_quickview_images');
        echo '</div>';
        echo '<div class="kt-quickview-content-right">';
            do_action('kt_quickview_summary');
        echo '</div>';
    echo '</div>';
    do_action('kt_quickview_after_content');
} else {
    echo '<div>' . __("couldn't find product data", "kadence-woo-extras") . '</div>';
}
