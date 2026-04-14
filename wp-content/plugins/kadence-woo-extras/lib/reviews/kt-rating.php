<?php
/**
 * Single Product Rating
 * @version 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $product;

if ( get_option( 'woocommerce_enable_review_rating' ) === 'no' ) {
	return;
}
if ( ! is_object( $product ) ) {
	return;
}
$kadence_reviews = Kadence_Advanced_Reviews::get_instance();
$rating_count    = count( $kadence_reviews->kt_get_product_reviews( $product->get_id() ) );
$average         = $kadence_reviews->kt_get_average_rating( $product->get_id() );

if ( $rating_count > 0 ) : ?>
	<div class="woocommerce-product-rating">
		<div class="star-rating" title="<?php printf( __( 'Rated %s out of 5', 'kadence-woo-extras' ), $average ); ?>">
			<span style="width:<?php echo( ( $average / 5 ) * 100 ); ?>%">
			<strong class="rating"><?php echo esc_html( $average ); ?></strong> <?php printf( __( 'out of %s5%s', 'kadence-woo-extras' ), '<span>', '</span>' ); ?>
				<?php printf( _n( 'based on %s customer rating', 'based on %s customer ratings', $rating_count, 'kadence-woo-extras' ), '<span class="rating">' . $rating_count . '</span>' ); ?>
			</span>
		</div>

		<?php if ( comments_open() ) : ?><a href="#reviews" class="woocommerce-review-link" rel="nofollow">
            (<?php printf( _n( '%s customer review', '%s customer reviews', $rating_count, 'kadence-woo-extras' ), '<span class="count">' . $rating_count . '</span>' ); ?>
            )</a><?php endif ?>
    </div>

<?php endif; ?>