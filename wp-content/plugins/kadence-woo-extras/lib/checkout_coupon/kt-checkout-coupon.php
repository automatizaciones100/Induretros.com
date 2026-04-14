<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action( 'plugins_loaded', 'kt_checkout_coupone_plugin_loaded' );

function kt_checkout_coupone_plugin_loaded() {

	class Kadence_Checkout_Coupon_Modal {

		public function __construct() {
				add_action( 'woocommerce_checkout_order_review', array( $this, 'add_before_order_review' ), 5 );
				add_action( 'woocommerce_review_order_before_order_total', array( $this, 'add_review_table_before_total' ), 10 );
				add_action( 'woocommerce_review_order_after_order_total', array( $this, 'add_review_table_after_total' ), 10 );
				add_action( 'woocommerce_checkout_order_review', array( $this, 'add_between_order_review_payment' ), 15 );
				add_action( 'woocommerce_checkout_order_review', array( $this, 'add_after_payment' ), 40 );
				add_action( 'wp_enqueue_scripts', array( $this, 'modal_checkout_scripts' ), 200 );
				add_action( 'woocommerce_before_checkout_form', array( $this, 'custom_checkout_form' ), 20 );
				// Remove the normal checkout form
				remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		}

		public function add_coupon_field() {
			if ( wc_coupons_enabled() ) {
				global $kt_woo_extras;
				$showcoupon_pre = ( ! empty( $kt_woo_extras['checkout_coupon_pre'] ) ? $kt_woo_extras['checkout_coupon_pre'] : __( 'Have a promo code?', 'kadence-woo-extras' ) );
				$showcoupon_link = ( ! empty( $kt_woo_extras['checkout_coupon_link'] ) ? $kt_woo_extras['checkout_coupon_link'] : __( 'Click here to enter your code.', 'kadence-woo-extras' ) );
					?>
					<p style="margin-bottom:0;" class="coupon_inner_checkout"><?php echo esc_html( $showcoupon_pre ); ?> <a class="coupon-modal-link"><?php echo esc_html( $showcoupon_link ); ?></a></p>
		<?php }
		}
		public function add_before_order_review() {
			global $kt_woo_extras;
			if ( isset( $kt_woo_extras['checkout_coupon_link_placement'] ) && 'before_review' === $kt_woo_extras['checkout_coupon_link_placement'] ) {
				$this->add_coupon_field();
			}
		}
		public function add_review_table_before_total() {
			global $kt_woo_extras;
			if ( isset( $kt_woo_extras['checkout_coupon_link_placement'] ) && 'before_table_total' === $kt_woo_extras['checkout_coupon_link_placement'] ) {
				echo '<tr class="kt-coupon-checkout">';
				echo '<td colspan="2">';
					$this->add_coupon_field();
				echo '</td>';
				echo '</tr>';
			}
		}
		public function add_review_table_after_total() {
			global $kt_woo_extras;
			if ( isset( $kt_woo_extras['checkout_coupon_link_placement'] ) && 'after_table_total' === $kt_woo_extras['checkout_coupon_link_placement'] ) {
				echo '<tr class="kt-coupon-checkout">';
				echo '<td colspan="2">';
					$this->add_coupon_field();
				echo '</td>';
				echo '</tr>';
			}
		}
		public function add_between_order_review_payment() {
			global $kt_woo_extras;
			if ( isset( $kt_woo_extras['checkout_coupon_link_placement'] ) && 'between_review_payment' === $kt_woo_extras['checkout_coupon_link_placement'] ) {
				$this->add_coupon_field();
			}
		}
		public function add_after_payment() {
			global $kt_woo_extras;
			if ( isset( $kt_woo_extras['checkout_coupon_link_placement'] ) && 'after_payment' === $kt_woo_extras['checkout_coupon_link_placement'] ) {
				$this->add_coupon_field();
			}
		}
		public function custom_checkout_form() {
			global $kt_woo_extras;
			$modal_body = ( ! empty(  $kt_woo_extras['checkout_coupon_desc'] ) ? $kt_woo_extras['checkout_coupon_desc'] : __( 'If you have a promo code, please apply it below.', 'kadence-woo-extras' ) );
			$placeholder = ( ! empty(  $kt_woo_extras['checkout_coupon_placeholder'] ) ? $kt_woo_extras['checkout_coupon_placeholder'] : __( 'Promo code', 'kadence-woo-extras' ) );
			$apply = ( ! empty(  $kt_woo_extras['checkout_coupon_apply'] ) ? $kt_woo_extras['checkout_coupon_apply'] : __( 'Apply Code', 'kadence-woo-extras' ) );
			?>
			<div id="kadence-coupon-modal" class="kadence-coupone-pro-modal kt-m-animate-in-fadeup kt-m-animate-out-fadeout" aria-hidden="true">
					<div id="kt-coupon-modal-overlay" class="kt-coupon-modal-overlay" tabindex="-1">
						<div class="kt-modal-container kt-modal-height-auto kt-close-position-inside" role="dialog" aria-modal="true">
							<button class="kt-coupon-modal-close" aria-label="Close Modal" data-modal-close="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" strokewidth="2" strokelinecap="round" strokelinejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
							<div id="kadence-coupon-modal-content" class="kt-modal-content">
								<form class="kt_checkout_coupon woocommerce-form-coupon" method="post">
								<p><?php echo esc_html( $modal_body ); ?></p>

								<p class="form-row form-row-first">
									<input type="text" name="coupon_code" class="input-text" placeholder="<?php echo esc_attr( $placeholder ); ?>" id="coupon_code" value="" />
								</p>

								<p class="form-row form-row-last">
									<button type="submit" class="button" name="apply_coupon" value="<?php echo esc_attr( $apply ); ?>"><?php echo esc_html( $apply ); ?></button>
								</p>

								<div class="clear"></div>
							</form>
							</div>
						</div>
					</div>
				</div>
			<?php
		}

		public function modal_checkout_scripts() {
			 if ( is_checkout() ) {
				wp_enqueue_style( 'kadence-coupon-modal-css', KADENCE_WOO_EXTRAS_URL . 'lib/checkout_coupon/css/kt-coupon-modal.css', false, KADENCE_WOO_EXTRAS_VERSION );
				wp_enqueue_script( 'kadence-coupon-modal', KADENCE_WOO_EXTRAS_URL . 'lib/checkout_coupon/js/kt-coupon-modal.js', array( 'jquery' ), KADENCE_WOO_EXTRAS_VERSION, true );
			}
		}

	}
	$GLOBALS['Kadence_Checkout_Coupon_Modal'] = new Kadence_Checkout_Coupon_Modal();
}

