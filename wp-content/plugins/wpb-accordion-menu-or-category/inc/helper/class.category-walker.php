<?php

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * WPB Category Walker Class
 */
class WPB_WCMA_Category_Walker extends Walker_Category
{

	/**
	 * Starts the element output.
	 *
	 * @since 2.1.0
	 * @since 5.9.0 Renamed `$category` to `$data_object` and `$id` to `$current_object_id`
	 *              to match parent class for PHP 8 named parameter support.
	 *
	 * @see Walker::start_el()
	 *
	 * @param string  $output            Used to append additional content (passed by reference).
	 * @param WP_Term $category          Category data object.
	 * @param int     $depth             Optional. Depth of category in reference to parents. Default 0.
	 * @param array   $args              Optional. An array of arguments. See wp_list_categories().
	 *                                   Default empty array.
	 * @param int     $id                Optional. ID of the current category. Default 0.
	 */
	public function start_el(&$output, $category, $depth = 0, $args = array(), $id = 0)
	{
		/** This filter is documented in wp-includes/category-template.php */
		$cat_name = apply_filters(
			'list_cats',
			esc_attr($category->name),
			$category
		);

		// Don't generate an element if the category name is empty.
		if (! $cat_name) {
			return;
		}

		$wpb_wmca_cat_count = '';

		if (! empty($args['show_count'])) {
			$wpb_wmca_cat_count = '<span class="wpb-wmca-cat-count">' . esc_html(number_format_i18n($category->count)) . '</span>';
		}

		$link = '<a href="' . esc_url(get_term_link($category)) . '" ';
		if ($args['use_desc_for_title'] && ! empty($category->description)) {
			$link .= 'title="' . esc_attr(wp_strip_all_tags(apply_filters('category_description', $category->description, $category))) . '"';
		}

		$link .= '>';
		$link .= $cat_name . $wpb_wmca_cat_count . '</a>';

		if ('list' === $args['style']) {
			$output     .= "\t<li";
			$css_classes = array(
				'cat-item',
				'cat-item-' . $category->term_id,
			);

			$termchildren = get_term_children($category->term_id, $category->taxonomy);

			if (count($termchildren) > 0) {
				$css_classes[] = 'cat-item-have-child';
			}

			if (! empty($args['current_category'])) {
				$_current_category = get_term($args['current_category'], $category->taxonomy);
				if ($category->term_id === $args['current_category']) {
					$css_classes[] = 'current-cat';
				} elseif ($category->term_id === $_current_category->parent) {
					$css_classes[] = 'wpb-wmca-current-cat-parent';
				}
			}

			$css_classes = implode(' ', apply_filters('category_css_class', $css_classes, $category, $depth, $args));

			$output .= ' class="' . $css_classes . '"';
			$output .= ">$link\n";
		} else {
			$output .= "\t$link<br />\n";
		}
	}
}
