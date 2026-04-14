<?php
/**
 * Review Comments Template
 *
 * @package Kadence Woo Extras
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $kt_woo_extras;

$kadence_reviews = Kadence_Advanced_Reviews::get_instance();
$rating                  = $kadence_reviews->kt_get_meta_rating( $review->ID );
$approved                = $kadence_reviews->kt_get_meta_approved( $review->ID );
$product_id              = $kadence_reviews->kt_get_meta_product_id( $review->ID );
$upvotes                 = $kadence_reviews->kt_get_meta_upvotes_count( $review->ID );
$downvotes               = $kadence_reviews->kt_get_meta_downvotes_count( $review->ID );
$comment_id              = $kadence_reviews->kt_get_meta_comment_id( $review->ID );
// $review_type             = $kadence_reviews->kt_get_meta_review_type( $review->ID );
// $review_parent           = $kadence_reviews->kt_get_meta_review_parent( $review->ID );
// $review_comment_parent   = $kadence_reviews->kt_get_meta_review_comment_parent( $review->ID );
$totalvotes              = floor( $upvotes + $downvotes );
$review_date             = mysql2date( get_option( 'date_format' ), $review->post_date );
$author                  = $kadence_reviews->kt_get_meta_author( $review->ID );
$user                    = isset( $author['review_user_id'] ) ? get_userdata( $author['review_user_id'] ) : null;
if ( isset( $kt_woo_extras['kt_reviews_featured'] ) && 1 == $kt_woo_extras['kt_reviews_featured'] ) {
	$featured = $kadence_reviews->kt_get_meta_featured( $review->ID );
} else {
	$featured = 0;
}
$reviewclasses = array();
if ( 1 == $featured ) {
	$reviewclasses[] = 'kt-featured-comment';
}
if ( $user ) {
	$author_name = $user->display_name;
} else if ( isset( $author['review_user_id'] ) ) {
	$author_name = $author['review_author'];
} else {
	$author_name = __( 'Anonymous', 'kadence-woo-extras' );
}

$author_name = apply_filters( 'kt_reviews_author_name', $author_name, $author, $user, $review );

?>
<li <?php post_class( $reviewclasses, $review->ID ); ?> id="li-comment-<?php echo esc_attr( $review->ID ); ?>">

	<div id="comment-<?php echo esc_attr( $comment_id ); ?>" class="comment_container">

	<?php
		do_action( 'kt_review_before', $review );
		if ( $user ) {
			echo get_avatar( $user->ID, apply_filters( 'woocommerce_review_gravatar_size', '60' ), '', __( 'Reviewer profile image', 'kadence-woo-extras' ) );
		} else {
			echo get_avatar( $author['review_author_email'], apply_filters( 'woocommerce_review_gravatar_size', '60' ), '', __( 'Reviewer profile image', 'kadence-woo-extras' ) );
		}
		if ( 1 == $featured ) {
			echo '<div class="kt-featured-review" data-toggle="tooltip" data-placement="top" data-original-title="' . __( 'Featured Review', 'kadence-woo-extras' ) . '"><i class="kt-reviews-icon-star-full"></i></div>';
		}
		?>

		<div class="comment-text">

			<?php
			do_action( 'kt_review_before_comment_meta', $review );
			if ( $rating && get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) { ?>

				<div class="star-rating" title="<?php echo sprintf( esc_attr__( 'Rated %d out of 5', 'kadence-woo-extras' ), esc_attr( $rating ) ) ?>">
					<span style="width:<?php echo ( esc_attr( $rating ) / 5 ) * 100; ?>%"><strong><?php echo esc_attr( $rating ); ?></strong> <?php esc_attr_e( 'out of 5', 'kadence-woo-extras' ); ?></span>
				</div>

			<?php }

			do_action( 'kt_review_meta', $review );
			if ( $approved == '0' ) : ?>

				<p class="meta"><em><?php _e( 'Your comment is waiting for approval', 'kadence-woo-extras' ); ?></em></p>

			<?php else : ?>

				<p class="meta">
					<strong><?php echo esc_html($author_name); ?></strong> <?php

					if ( $user && get_option( 'woocommerce_review_rating_verification_label' ) === 'yes' ) {
						if ( wc_customer_bought_product( $user->user_email, $user->ID, $product_id ) ) {
							echo '<em class="verified">(' . __( 'verified owner', 'kadence-woo-extras' ) . ')</em> ';
						}
					}
					?>
					&ndash; <time datetime="<?php echo esc_attr( mysql2date( 'c', $review_date ) ); ?>"><?php echo esc_html( $review_date ); ?></time>
				</p>

			<?php endif; 

			do_action( 'kt_review_before_comment_text', $review );
			do_action( 'kt_review_comment_text', $review );
			echo '<div itemprop="description" class="description">';
				if ( isset( $kt_woo_extras['kt_review_title'] ) && $kt_woo_extras['kt_review_title'] == 1 ) {
					echo '<h5 class="kt_review_title"><b>'.apply_filters( 'kt_reviews_review_title', $review->post_title ).'</b></h5>';
				}
				echo apply_filters( 'kt_reviews_review_content', $review->post_content );
			echo '</div>';
			if ( ! isset( $kt_woo_extras['kt_review_voting'] ) || ( isset( $kt_woo_extras['kt_review_voting'] ) && $kt_woo_extras['kt_review_voting'] != 0 ) ) {
				echo '<div class="kt-review-vote-area">';
					if($totalvotes != 0) {
						echo  '<div class="kt-review-helpful">'.sprintf(__('%d of %s found this helpful', 'kadence-woo-extras'), $upvotes, $totalvotes).'</div>';
					} else {
						echo '<div class="kt-review-helpful"></div>';
					}
					if(!is_user_logged_in() && $kt_woo_extras['vote_loggedin_only'] == 1) {
						$data = 'data-toggle="modal" data-target="#kt-modal-review-login"';
					} else {
						$data = 'data-vote="review"';
					}
					echo  '<div class="kt-review-vote-container">';
						echo '<a href="#" data-comment-id="'.esc_attr($review->ID).'" '.$data.' class="kt-review-vote kt-vote-up" data-toggle="tooltip" data-placement="top" data-original-title="'.__('Upvote if this was helpful.', 'kadence-woo-extras').'"><i class="kt-reviews-icon-thumbs-up"></i></a><a href="#" data-comment-id="'.esc_attr($review->ID).'" '.$data.' class="kt-review-vote kt-vote-down" data-toggle="tooltip" data-placement="top" data-original-title="'.__('Downvote if this was not helpful.', 'kadence-woo-extras').'"><i class="kt-reviews-icon-thumbs-down"></i></a>';
						echo '</div>';
				echo '</div>';
			}

			do_action( 'kt_review_after_comment_text', $review ); ?>

		</div>
		<div class="kt-review-overlay"><div class="kt-ajax-bubbling"><span id="kt-ajax-bubbling_1"></span><span id="kt-ajax-bubbling_2"></span><span id="kt-ajax-bubbling_3"></span></div></div>
		</div>
</li>