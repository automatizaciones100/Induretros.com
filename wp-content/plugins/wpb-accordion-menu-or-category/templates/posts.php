<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $args['id'] ) ) {
	return;
}

$the_post_type = get_post_meta( $args['id'], 'wpb_wmca_hierarchical_post_type', true );
$depth         = get_post_meta( $args['id'], 'wpb_wmca_hierarchical_post_depth', true );
$posts_orderby = get_post_meta( $args['id'], 'wpb_wmca_hierarchical_post_orderby', true );

?>
<ul class="wpb_category_n_menu_accordion_list">
	<?php
		$default_options = array(
			'echo'        => 1,
			'title_li'    => '',
			'depth'       => intval( $depth ? $depth : 0 ),
			'sort_column' => $posts_orderby,
			'post_type'   => $the_post_type,
			'walker'      => new WPB_WCMA_Posts_Walker(),
		);

		$options = apply_filters( 'wpb_wcma_wp_list_pages_args', $default_options );

		wp_list_pages( $options );
		?>
</ul>
<?php