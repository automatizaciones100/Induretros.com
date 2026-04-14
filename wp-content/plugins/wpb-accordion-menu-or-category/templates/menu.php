<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $args['id'] ) ) {
	return;
}

$menu_id    = get_post_meta( $args['id'], 'wpb_wmca_menu_id', true );
$menu_depth = get_post_meta( $args['id'], 'wpb_wmca_menu_depth', true );

$default_options = array(
	'menu'            => $menu_id,
	'container'       => '',
	'container_class' => '',
	'container_id'    => '',
	'menu_class'      => 'wpb_category_n_menu_accordion_list',
	'menu_id'         => '',
	'echo'            => true,
	'before'          => '',
	'after'           => '',
	'link_before'     => '',
	'link_after'      => '',
	'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
	'depth'           => intval( $menu_depth ? $menu_depth : 0 ),
);

$options = apply_filters( 'wpb_wcma_wp_nav_menu_args', $default_options );

wp_nav_menu( $options );