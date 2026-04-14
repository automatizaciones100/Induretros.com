<?php

if (! defined('ABSPATH')) {
    exit;
}

class WPB_WCMA_WooCommerce
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        add_filter('wpb_wcma_wp_list_categories_args', array($this, 'filter_out_of_stock'), 10, 2);
    }

    /**
     * Filter out of stock categories.
     *
     * @param array $args Arguments for wp_list_categories.
     * @param int   $id   The ID of the current category.
     *
     * @return array
     */

    public function filter_out_of_stock($args, $id)
    {
        $tax_hide_out_of_stock = get_post_meta($id, 'wpb_wmca_tax_hide_out_of_stock', true);

        if ('product_cat' === $args['taxonomy'] && 'on' === $tax_hide_out_of_stock) {
            $terms = get_terms(array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
            ));

            $exclude_ids = array();

            foreach ($terms as $term) {
                $products = wc_get_products(array(
                    'status'       => 'publish',
                    'stock_status' => 'instock',
                    'limit'        => 1,
                    'category'     => array($term->slug),
                ));

                // If no in-stock products, exclude this category
                if (empty($products)) {
                    $exclude_ids[] = $term->term_id;
                }
            }

            if (! empty($exclude_ids)) {
                $args['exclude'] = $exclude_ids;
            }
        }

        return $args;
    }
}
