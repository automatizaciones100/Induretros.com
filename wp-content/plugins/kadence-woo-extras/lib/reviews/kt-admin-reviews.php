<?php
/**
 * Review Comments Template
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
global $kt_reviews;
?>
<div class="wrap">

    <h2><?php _e( 'Product reviews', 'kadence-woo-extras' ) ?></h2>

    <?php $product_reviews->views(); ?>

    <form id="kt-reviews" method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php $product_reviews->search_box( __( 'Search reviews', 'kadence-woo-extras' ), 'kadence-woo-extras' ); ?>
        <?php $product_reviews->display(); ?>
    </form>
</div>