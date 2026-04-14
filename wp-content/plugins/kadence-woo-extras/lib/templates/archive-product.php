<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook to pull in the product header.
 */
do_action( 'kadence_woocommerce_template_include_header' );

/**
 * Hook for product template builder.
 */
do_action( 'kadence_woocommerce_template_before_archive' );

/**
 * Kadence_woocommerce_template_product_archive_override
 */
do_action( 'kadence_woocommerce_template_product_archive_override' );

/**
 * Hook for product template builder.
 */
do_action( 'kadence_woocommerce_template_after_archive' );

/**
 * Hook to pull in the product footer.
 */
do_action( 'kadence_woocommerce_template_include_footer' );
