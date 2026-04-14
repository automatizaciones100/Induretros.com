<?php
/**
 * Display single product reviews (comments)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product-reviews.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     3.2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product, $kt_reviews, $kt_woo_extras;

if ( ! comments_open() ) {
	return;
}
$args         = array();
$the_reviews  = $kt_reviews->kt_get_product_reviews( $product->get_id() );
$review_count = count( $the_reviews );
$check_unapproved = false;
if ( is_user_logged_in() ) {
	$unapproved_args = array();
	$unapproved_args['meta_query'] = array(
		'relation' => 'AND',
		array(
			'key'     => $kt_reviews->review_meta_product_id,
			'value'   => $product->get_id(),
			'compare' => '=',
			'type'    => 'numeric',
		),
		array(
			'key'     => $kt_reviews->review_meta_approved,
			'value'   => 0,
			'compare' => '=',
			'type'    => 'numeric',
		),
		array(
			'key'     => $kt_reviews->review_meta_review_user_id,
			'value'   => get_current_user_id(),
			'compare' => '=',
			'type'    => 'numeric',
		),
	);
	$check_unapproved = $kt_reviews->kt_check_unapproved( $product->get_id(), $unapproved_args );
} else {
	$unapproved_email = wp_get_unapproved_comment_author_email();
	if ( $unapproved_email ) {
		$unapproved_args = array();
		$unapproved_args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key'     => $kt_reviews->review_meta_product_id,
				'value'   => $product->get_id(),
				'compare' => '=',
				'type'    => 'numeric',
			),
			array(
				'key'     => $kt_reviews->review_meta_approved,
				'value'   => 0,
				'compare' => '=',
				'type'    => 'numeric',
			),
			array(
				'key'     => $kt_reviews->review_meta_review_author_email,
				'value'   => $unapproved_email,
				'compare' => '=',
			),
		);
		$check_unapproved = $kt_reviews->kt_check_unapproved( $product->get_id(), $unapproved_args );
	}
}


	do_action( 'kt_before_reviews' ); ?>

<div id="reviews" class="woocommerce-Reviews">
	<div id="comments">
		<h2 class="woocommerce-Reviews-title">
		<?php
		if ( get_option( 'woocommerce_enable_review_rating' ) === 'yes' && ( $review_count ) ) {
			/* translators: 1: reviews count 2: product name */
			$reviews_title = sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $review_count, 'kadence-woo-extras' ) ), esc_html( $review_count ), '<span>' . get_the_title() . '</span>' );
			echo apply_filters( 'woocommerce_reviews_title', $reviews_title, $review_count, $product ); // WPCS: XSS ok.
		} else {
			esc_html_e( 'Reviews', 'kadence-woo-extras' );
		}
		?>
		</h2>

		<?php
		if ( $review_count || $check_unapproved ) :
			if ( $review_count ) {
				do_action( 'kt_before_review_list', $product, $review_count );
			}
			?>
			<ol class="commentlist">
				<?php
				if ( isset( $kt_woo_extras['kt_reviews_featured'] ) && 1 == $kt_woo_extras['kt_reviews_featured'] ) {
					$featured_args = array();
					$args['meta_query'] = array(
						'relation' => 'AND',
						array(
							'key'     => $kt_reviews->review_meta_product_id,
							'value'   => $product->get_id(),
							'compare' => '=',
							'type'    => 'numeric',
						),
						array(
							'key'     => $kt_reviews->review_meta_approved,
							'value'   => 1,
							'compare' => '=',
							'type'    => 'numeric',
						),
						array(
							'key'     => $kt_reviews->review_meta_featured,
							'value'   => 0,
							'compare' => '=',
							'type'    => 'numeric',
						),
						array(
							'key'     => $kt_reviews->review_meta_review_type,
							'value'   => 'comment',
							'compare' => '!=',
							'type'    => 'char',
						),
					);
					$featured_args['meta_query'] = array(
						'relation' => 'AND',
						array(
							'key'     => $kt_reviews->review_meta_product_id,
							'value'   => $product->get_id(),
							'compare' => '=',
							'type'    => 'numeric',
						),
						array(
							'key'     => $kt_reviews->review_meta_approved,
							'value'   => 1,
							'compare' => '=',
							'type'    => 'numeric',
						),
						array(
							'key'     => $kt_reviews->review_meta_featured,
							'value'   => 1,
							'compare' => '=',
							'type'    => 'numeric',
						),
						array(
							'key'     => $kt_reviews->review_meta_review_type,
							'value'   => 'comment',
							'compare' => '!=',
							'type'    => 'char',
						),
					);
					$kt_reviews->kt_reviews_list( $product->get_id(), $featured_args, false );
				}
				if ( isset( $kt_woo_extras['kt_reviews_limited'] ) && '1' == $kt_woo_extras['kt_reviews_limited'] ) {
					$args['numberposts'] = ( isset( $kt_woo_extras['kt_reviews_limited_count'] ) && ! empty( $kt_woo_extras['kt_reviews_limited_count'] ) ? $kt_woo_extras['kt_reviews_limited_count'] : 10 );
				}
				if ( $check_unapproved ) {
					foreach ( $check_unapproved as $check_unapproved_review ) {
						wc_get_template( 'kt-single-review.php', array( 'review' => $check_unapproved_review ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/reviews/' );
					}
				}
				if ( isset( $kt_woo_extras['kt_reviews_featured'] ) && 1 == $kt_woo_extras['kt_reviews_featured'] ) {
					$kt_reviews->kt_reviews_list( $product->get_id(), $args );
				} else {
					if ( $the_reviews ) {
						if ( isset( $args['numberposts'] ) && $review_count > $args['numberposts'] ) {
							$kt_reviews->kt_reviews_list( $product->get_id(), $args );
						} else {
							$kt_reviews->kt_reviews_list( $product->get_id() );
						}
					}
				}
				?>
			</ol>
			<?php
			if ( isset( $kt_woo_extras['kt_reviews_limited'] ) && '1' == $kt_woo_extras['kt_reviews_limited'] && $review_count > $args['numberposts'] ) {
				$readmore = ( isset( $kt_woo_extras['kt_reviews_limited_readmore'] ) && ! empty( $kt_woo_extras['kt_reviews_limited_readmore'] ) ? $kt_woo_extras['kt_reviews_limited_readmore'] : __( 'Read More Reviews' ) );
				echo '<div class="kt-ajax-load-more-reviews-container"><button class="submit kt-ajax-load-more-reviews" data-review-args="' . esc_attr( json_encode( $args ) ) . '" data-offset-count="' . esc_attr( $args['numberposts'] ) . '" data-review-count="' . esc_attr( $review_count ) . '" data-product-id="' . esc_attr( $product->get_id() ) . '">' . $readmore . '</button><div class="kt-review-load-more-loader kt-review-overlay"><div class="kt-ajax-bubbling"><span id="kt-ajax-bubbling_1"></span><span id="kt-ajax-bubbling_2"></span><span id="kt-ajax-bubbling_3"></span></div></div></div>';
			}
			?>

			<?php do_action( 'kt_after_review_list', $product, $review_count ); ?>

		<?php else : ?>

			<p class="woocommerce-noreviews"><?php _e( 'There are no reviews yet.', 'kadence-woo-extras' ); ?></p>

		<?php endif; ?>

	</div>

	<?php if ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) : ?>

		<div id="review_form_wrapper">
			<div id="review_form">
				<?php
					$commenter = wp_get_current_commenter();

					$comment_form = array(
						'title_reply'          => have_comments() ? __( 'Add a review', 'kadence-woo-extras' ) : sprintf( __( 'Be the first to review &ldquo;%s&rdquo;', 'kadence-woo-extras' ), get_the_title() ),
						'title_reply_to'       => __( 'Leave a Reply to %s', 'kadence-woo-extras' ),
						'comment_notes_after'  => '',
						'fields'               => array(
							'author' => '<p class="comment-form-author">' . '<label for="author">' . __( 'Name', 'kadence-woo-extras' ) . ' <span class="required">*</span></label> ' .
										'<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" aria-required="true" required /></p>',
							'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email', 'kadence-woo-extras' ) . ' <span class="required">*</span></label> ' .
										'<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30" aria-required="true" required /></p>',
						),
						'label_submit'  => __( 'Submit', 'kadence-woo-extras' ),
						'logged_in_as'  => '',
						'comment_field' => '',
					);

					if ( $account_page_url = wc_get_page_permalink( 'myaccount' ) ) {
						$comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf( __( 'You must be <a href="%s">logged in</a> to post a review.', 'kadence-woo-extras' ), esc_url( $account_page_url ) ) . '</p>';
					}
					do_action( 'kt_add_reveiw_form_top' );
					if ( get_option( 'woocommerce_enable_review_rating' ) === 'yes' ) {
						$comment_form['comment_field'] = '<p class="comment-form-rating"><label for="rating">' . __( 'Your Rating', 'kadence-woo-extras' ) . ' <span class="required">*</span></label><select name="rating" id="rating" aria-required="true" required>
                            <option value="">' . __( 'Rate&hellip;', 'kadence-woo-extras' ) . '</option>
                            <option value="5">' . __( 'Perfect', 'kadence-woo-extras' ) . '</option>
                            <option value="4">' . __( 'Good', 'kadence-woo-extras' ) . '</option>
                            <option value="3">' . __( 'Average', 'kadence-woo-extras' ) . '</option>
                            <option value="2">' . __( 'Not that bad', 'kadence-woo-extras' ) . '</option>
                            <option value="1">' . __( 'Very Poor', 'kadence-woo-extras' ) . '</option>
                        </select></p>';
					}
					if ( isset( $kt_woo_extras['kt_review_title'] ) && $kt_woo_extras['kt_review_title'] == 1 ) {
						$comment_form['comment_field'] .= '<p class="comment-form-title"><label for="title">' . __( 'Review title', 'kadence-woo-extras' ) . '</label><input type="text" style="width:100%;" name="title" id="title"/></p>';
					}
					if ( isset( $kt_woo_extras['kt_review_consent'] ) && $kt_woo_extras['kt_review_consent'] == 1 ) {

						if ( function_exists( 'the_privacy_policy_link' ) ) {
							$privacy_link = get_the_privacy_policy_link();
						}
						if ( isset( $privacy_link ) && ! empty( $privacy_link ) ) {
							$consent_label = sprintf( __( 'Please check to consent to our %s', 'kadence-woo-extras' ), $privacy_link );
						} else {
							$consent_label = __( 'Please check to consent to our privacy policy', 'kadence-woo-extras' );
						}
						$comment_form['comment_field'] .= '<p class="comment-form-consent"><input type="checkbox" name="consent" id="review-consent-input" aria-required="true" required/><label for="review-consent-input">' . $consent_label . ' <span class="required">*</span></label></p>';
					}
					$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . __( 'Your Review', 'kadence-woo-extras' ) . ' <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true" required></textarea></p>';

					comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
					?>
							</div>
		</div>

	<?php else : ?>

		<p class="woocommerce-verification-required"><?php _e( 'Only logged in customers who have purchased this product may write a review.', 'kadence-woo-extras' ); ?></p>

	<?php endif; ?>

	<div class="clear"></div>
	<?php if ( ! is_user_logged_in() && $kt_woo_extras['vote_loggedin_only'] == 1 ) { ?>
		<div class="modal fade kt-review-loggin-modal" id="kt-modal-review-login" tabindex="-1" role="dialog" aria-labelledby="#kt-modal-label-review" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="kt-modal-label-review"><?php echo __( 'Login', 'kadence-woo-extras' ); ?></h4>
					</div>
					<div class="modal-body"">
						<?php
						wp_login_form();

						if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) {
							echo '<p>';
							echo __( "Don't have an account?", 'kadence-woo-extras' );
							echo ' <a href="' . wc_get_page_permalink( 'myaccount' ) . '" class="kt-review-vote-signup">' . __( 'Sign Up', 'kadence-woo-extras' ) . '</a>';
							echo '</p>';
						}
						?>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
</div>
<?php
	do_action( 'kt_after_reviews' );
