<?php

/**
 * The Template for displaying accordion categories.
 *
 * This template can be overridden by copying it to yourtheme/wpb-accordion-menu/taxonomy.php.
 *
 * @version 1.1
 */

if (! defined('ABSPATH')) {
	exit;
}

if (! isset($args['id'])) {
	return;
}

$the_taxonomy   = get_post_meta($args['id'], 'wpb_wmca_taxonomy', true);
$tax_orderby    = get_post_meta($args['id'], 'wpb_wmca_tax_orderby', true);
$tax_order      = get_post_meta($args['id'], 'wpb_wmca_tax_order', true);
$tax_show_count = get_post_meta($args['id'], 'wpb_wmca_tax_show_count', true);
$tax_hide_empty = get_post_meta($args['id'], 'wpb_wmca_tax_hide_empty', true);
?>

<ul class="wpb_category_n_menu_accordion_list">
	<?php
	$categories_args = array(
		'show_option_all'  => '',
		'orderby'          => $tax_orderby,
		'order'            => $tax_order,
		'style'            => 'list',
		'show_count'       => ('on' === $tax_show_count ? 1 : 0),
		'hide_empty'       => ('on' === $tax_hide_empty ? 1 : 0),
		'exclude'          => '',
		'exclude_tree'     => '',
		'include'          => '',
		'hierarchical'     => 1,
		'title_li'         => '',
		'show_option_none' => '',
		'number'           => null,
		'echo'             => 1,
		'depth'            => 0,
		'current_category' => 0,
		'pad_counts'       => 0,
		'taxonomy'         => $the_taxonomy,
		'shortcode_id'     => esc_attr($args['id']),
		'walker'           => new WPB_WCMA_Category_Walker(),
	);

	wp_list_categories(apply_filters('wpb_wcma_wp_list_categories_args', $categories_args, $args['id']));
	?>
</ul>