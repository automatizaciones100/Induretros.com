<?php

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * WPB Category Accordion ShortCode [ Deprecated - 1.4.8 ]
 *
 * @param array $atts    array of ShortCode attributes.
 * @return string
 */
function wpb_wmca_category_shortcode_function($atts)
{

	$attributes = shortcode_atts(
		array(
			'taxonomy'   => 'category',
			'orderby'    => 'name',
			'order'      => 'ASC',
			'show_count' => 'no', // yes.
			'hide_empty' => 'yes', // no.
			'icon'       => '+',
			'iconclass'  => '',
			'accordion'  => 'no', // yes.
		),
		$atts
	);

	ob_start();
?>

	<div class="wpb_category_n_menu_accordion wpb_wmca_accordion_wrapper_theme_dark" data-accordion="<?php echo esc_attr('yes' === $attributes['accordion'] ? 'true' : 'false'); ?>" data-indicator_icon="<?php echo esc_attr($attributes['icon']); ?>" data-iconclass="<?php echo esc_attr($attributes['iconclass']); ?>">
		<ul class="wpb_category_n_menu_accordion_list">
			<?php
			$args = array(
				'show_option_all'  => '',
				'orderby'          => $attributes['orderby'],
				'order'            => $attributes['order'],
				'style'            => 'list',
				'show_count'       => ('yes' === $attributes['show_count'] ? 1 : 0),
				'hide_empty'       => ('yes' === $attributes['hide_empty'] ? 1 : 0),
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
				'taxonomy'         => $attributes['taxonomy'],
				'walker'           => new WPB_WCMA_Category_Walker(),
			);
			wp_list_categories($args);
			?>
		</ul>
	</div>

<?php
	return ob_get_clean();
}

add_shortcode('wpb_category_accordion', 'wpb_wmca_category_shortcode_function');

/**
 * WPB Menu Accordion ShortCode [ Deprecated - 1.4.8 ].
 *
 * @param array $atts    array of ShortCode attributes.
 * @return string
 */
function wpb_wmca_menu_shortcode_function($atts)
{

	$attributes = shortcode_atts(
		array(
			'theme_location' => '', // menu theme location.
			'menu'           => '', // (optional) The menu that is desired; accepts (matching in order) id, slug, name.
			'icon'           => '+',
			'iconclass'      => '',
			'accordion'      => 'no', // yes.
		),
		$atts
	);

	ob_start();
?>
	<div class="wpb_category_n_menu_accordion wpb_wmca_accordion_wrapper_theme_dark" data-accordion="<?php echo esc_attr('yes' === $attributes['accordion'] ? 'true' : 'false'); ?>" data-indicator_icon="<?php echo esc_attr($attributes['icon']); ?>" data-iconclass="<?php echo esc_attr($attributes['iconclass']); ?>">
		<?php
		$options = array(
			'theme_location'  => $attributes['theme_location'],
			'menu'            => $attributes['menu'],
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
			'depth'           => 0,
			'walker'          => '',
		);
		wp_nav_menu($options);
		?>
	</div>
<?php
	return ob_get_clean();
}

add_shortcode('wpb_menu_accordion', 'wpb_wmca_menu_shortcode_function');
