<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post;
/**
 * Hook to pull in the product header.
 */
do_action( 'kadence_woocommerce_template_include_header' );
/**
 * Hook for product template builder.
 */
do_action( 'kadence_woocommerce_template_before_product', $post );
if ( post_password_required() ) {
	echo get_the_password_form();
} else {
	?>
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>

			<div id="product-<?php the_ID(); ?>" <?php post_class( 'kadence-woo-template-wrap' ); ?>>
				<?php
					/**
					 * Hook for product template builder.
					 * kadence_woocommerce_template_product_override
					 *
					 * @hooked Kadence_Woo_Block_Editor_Templates-> get_product_content() - 10.
					 * @hooked Kadence_Woo_Block_Editor_Templates-> get_product_schema() - 20.
					 */
					do_action( 'kadence_woocommerce_template_product_override', $post );
					?>
			</div><!-- #product-<?php the_ID(); ?> -->

		<?php
		endwhile; // end of the loop.
}
/**
 * Hook for product template builder.
 */
do_action( 'kadence_woocommerce_template_after_product', $post );
do_action( 'woocommerce_after_single_product' );

/**
 * Hook to pull in the product footer.
 */
do_action( 'kadence_woocommerce_template_include_footer' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
