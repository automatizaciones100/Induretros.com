<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action( 'plugins_loaded', 'kt_affiliate_options_plugin_loaded' );

function kt_affiliate_options_plugin_loaded() {

	class kt_affiliate_options {

		public function __construct() {
			$shopkit_settings = get_option( 'kt_woo_extras' );
			if ( ! is_array( $shopkit_settings ) ) {
				$shopkit_settings = json_decode( $shopkit_settings, true );
			}
			if ( isset( $shopkit_settings[ 'kt_aa_image_link' ] ) && 1 == $shopkit_settings[ 'kt_aa_image_link' ] ) {
				add_action( 'init', array( $this, 'kt_archive_image_link' ), 50 );
			}
			if ( isset( $shopkit_settings['kt_aa_action_link_target'] ) && 1 == $shopkit_settings[ 'kt_aa_action_link_target' ] ) {
				add_action( 'init', array( $this, 'filter_in_archive_button_link' ), 50 );
			}
			if ( isset( $shopkit_settings[ 'kt_single_aa_image_link' ] ) && 1 == $shopkit_settings[ 'kt_single_aa_image_link' ] ) {
				add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'kt_single_image_link' ), 10, 2);
			}
			if ( isset( $shopkit_settings[ 'kt_single_aa_action_link_target' ] ) && 1 == $shopkit_settings[ 'kt_single_aa_action_link_target' ] ) {
				remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
				add_action( 'woocommerce_external_add_to_cart', array( $this, 'kt_single_external_add_to_cart' ), 30 );
			}
		}
		public function filter_in_archive_button_link() {
			add_filter( 'woocommerce_loop_add_to_cart_link',  array( $this, 'kt_archive_loop_add_to_cart' ), 50, 2);
		}
		public function kt_archive_image_link() {
			$shopkit_settings = get_option( 'kt_woo_extras' );
			if ( ! is_array( $shopkit_settings ) ) {
				$shopkit_settings = json_decode( $shopkit_settings, true );
			}
			if ( function_exists( 'flatsome_template' ) ) {
				add_action( 'wp_footer', array( $this, 'wooextras_flatsome_image_link_js' ), 10 );
				add_action( 'flatsome_woocommerce_shop_loop_images', array( $this, 'wooextras_flatsome_image_link' ), 10 );
			} else {
				// Remove Woocommerce
				remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
				remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
				// Remove kad 
				remove_action( 'woocommerce_before_shop_loop_item_title', 'kad_woocommerce_image_link_open', 5 );
				remove_action( 'woocommerce_before_shop_loop_item_title', 'kad_woocommerce_image_link_close', 50 );
				// Remove Ascend 
				remove_action( 'woocommerce_before_shop_loop_item_title', 'ascend_woocommerce_image_link_open', 2 );
				remove_action( 'woocommerce_before_shop_loop_item_title', 'ascend_woocommerce_image_link_close', 50 );
				// Remove Virtue
				remove_action( 'woocommerce_before_shop_loop_item_title', 'virtue_woocommerce_image_link_open', 5 );
				remove_action( 'woocommerce_before_shop_loop_item_title', 'virtue_woocommerce_image_link_close', 50 );
				// Remove Pinnacle 
				remove_action( 'woocommerce_before_shop_loop_item_title', 'pinnacle_woocommerce_image_link_open', 5 );
				remove_action( 'woocommerce_before_shop_loop_item_title', 'pinnacle_woocommerce_image_link_close', 50 );
				// Remove Kadence Theme
				if ( class_exists( 'Kadence\Theme' ) ) {
					$kadence_theme_class = Kadence\Theme::instance();
					remove_action( 'woocommerce_before_shop_loop_item_title', array( $kadence_theme_class->components['woocommerce'], 'archive_loop_image_link_open' ), 5 );
					remove_action( 'woocommerce_before_shop_loop_item_title', array( $kadence_theme_class->components['woocommerce'], 'archive_loop_image_link_close' ), 50 );
				}

				// Add Woo Extras.
				add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'kt_archive_image_link_open' ), 2 );
				add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'kt_archive_image_link_close' ), 50 );
				add_filter( 'kadence_woocommerce_loop_product_image_link', array( $this, 'filter_the_link_only' ), 10 );
				if ( isset( $shopkit_settings[ 'kt_aa_image_link_target' ] ) && 1 == $shopkit_settings[ 'kt_aa_image_link_target' ] ) {
					add_filter( 'woocommerce_shop_loop_image_link_target', '__return_true' );
				}
			}
			if ( isset( $shopkit_settings[ 'kt_aa_title_link' ] ) && 1 == $shopkit_settings[ 'kt_aa_title_link' ] ) {
				// Remove Title links
				if ( function_exists( 'flatsome_template' ) ) {
					remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
					add_action( 'woocommerce_shop_loop_item_title', array( $this, 'wooextras_flatsome_template_loop_product_title' ), 10 );
				} else {
					// Remove Ascend 
					remove_action( 'woocommerce_shop_loop_item_title', 'ascend_woocommerce_archive_title_link_start', 7 );
					remove_action( 'woocommerce_shop_loop_item_title', 'ascend_woocommerce_archive_title_link_end', 15 );

					// Remove Virtue
					remove_action( 'woocommerce_shop_loop_item_title', 'virtue_woocommerce_archive_title_link_start', 7 );
					remove_action( 'woocommerce_shop_loop_item_title', 'virtue_woocommerce_archive_title_link_end', 15 );
					if ( class_exists( 'Kadence\Theme' ) ) {
						$kadence_theme_class = Kadence\Theme::instance();
						remove_action( 'woocommerce_shop_loop_item_title', array( $kadence_theme_class->components['woocommerce'], 'archive_title_with_link' ) );
						add_action( 'woocommerce_shop_loop_item_title',  array( $this, 'wooextras_kadence_archive_title_with_link' ) );
					}

					// Add Woo Extras Title Links
					add_action( 'woocommerce_shop_loop_item_title', array( $this, 'kt_archive_title_link_open' ), 7 );
					add_action( 'woocommerce_shop_loop_item_title', array( $this, 'kt_archive_title_link_close' ), 15 );
					add_filter( 'woocommerce_shop_loop_title_link', array( $this, 'kt_archive_link_open_external' ), 10 );

					// Add target to custom archive titles.
					add_action( 'kadence_woocommerce_template_before_loop', array( $this, 'kt_archive_title_link_target' ), 10 );
				}
			}

		}
		public function kt_archive_link_open_external( $link ) {
			global $product, $kt_woo_extras;
			if ( $product->is_type( 'external' ) ) {
				$link = $product->get_product_url();
			}
			return $link;
		}
		public function wooextras_kadence_archive_title_with_link() {
			echo '<h2 class="' . esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' ) ) . '">' . get_the_title() . '</h2>';
		}
		public function wooextras_flatsome_image_link() {
			global $product, $kt_woo_extras;
			if( $product->is_type( 'external' ) ) {
				if( isset( $kt_woo_extras[ 'kt_aa_image_link_target' ] ) && 1 == $kt_woo_extras[ 'kt_aa_image_link_target' ] ) {
					echo  '<meta class="external-link-meta" data-external-link-target="_blank" data-external-link="'.esc_url( $product->get_product_url() ).'"/>';
				} else {
					echo  '<meta class="external-link-meta" data-external-link-target="_self" data-external-link="'.esc_url( $product->get_product_url() ).'"/>';
				}
			}
		}
		public function wooextras_flatsome_image_link_js() {
			echo '<script type="text/javascript">';
			echo 'jQuery(document).ready(function ($) {';
			echo '$(".col.product.product-type-external .box-image a").each(function() {';
			echo 'var meta = $(this).find(".external-link-meta");';
			echo 'var link = meta.attr("data-external-link");';
			echo 'var target = meta.attr("data-external-link-target");';
			echo '$(this).attr("href", link );';
			echo '$(this).attr("target", target );';
			echo '});';
			echo '});';
			echo '</script>';
		}
		public function wooextras_flatsome_template_loop_product_title() {
			global $product, $kt_woo_extras;
			echo '<p class="name product-title">';
			if( $product->is_type( 'external' ) ) {
				if( isset( $kt_woo_extras[ 'kt_aa_title_link_target' ] ) && 1 == $kt_woo_extras[ 'kt_aa_title_link_target' ] ) {
					echo  '<a href="'.esc_url( $product->get_product_url() ).'" class="product_item_title_link" target="_blank">';
				} else {
					echo  '<a href="'.esc_url( $product->get_product_url() ).'" class="product_item_title_link">';
				}
			} else {
				echo  '<a href="'.esc_url( get_the_permalink() ).'" class="product_item_title_link">';
			}
			echo get_the_title() . '</a></p>';
		}
		public function kt_single_external_add_to_cart() {
			global $product;

			if ( ! $product->add_to_cart_url() ) {
				return;
			}
			if ( ! class_exists( 'Virtue_Get_Image' ) ) {
				$extra_classes = 'kad_add_to_cart kad-btn kad-btn-primary headerfont';
			} else {
				$extra_classes = '';
			}
			if ( version_compare( WC_VERSION, '3.4', '<' ) ) {
				do_action( 'woocommerce_before_add_to_cart_button' ); ?>

				<p class="cart">
					<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" target="_blank" rel="nofollow" class="<?php echo esc_attr( $extra_classes );?> single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></a>
				</p>

				<?php do_action( 'woocommerce_after_add_to_cart_button' ); 
			} else {
				$product_url = $product->add_to_cart_url();
				do_action( 'woocommerce_before_add_to_cart_form' );
				?>

				<form class="cart" action="<?php echo esc_url( $product_url ); ?>" method="get" target="_blank">
					<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?> 

					<button type="submit" name="add-to-cart" class="kad_add_to_cart single_add_to_cart_button headerfont kad-btn kad-btn-primary button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

					<?php wc_query_string_form_fields( $product_url ); ?>

					<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
				</form>

				<?php
				do_action( 'woocommerce_after_add_to_cart_form' ); 

			}

		}
		public function kt_single_image_link( $html, $id ) {
			if ( empty( $id ) ) {
				return $html;
			}
			global $product, $kt_woo_extras;
			if( $product->is_type( 'external' ) ) {
				$image = wp_get_attachment_image_src( $id, 'full' );
				if( isset( $kt_woo_extras[ 'kt_single_aa_image_link_target' ] ) && 1 == $kt_woo_extras[ 'kt_single_aa_image_link_target' ] ) {
					$html = str_replace('href="'.$image[0].'"', 'href="'.$product->get_product_url().'" target="_blank"', $html);
				} else {
					$html = str_replace('href="'.$image[0].'"', 'href="'.$product->get_product_url().'"', $html);
				}
			} 
			return $html;
		}
		public function filter_the_link_only( $link ) {
			global $product;
			if ( $product->is_type( 'external' ) ) {
				$link = $product->get_product_url();
			}
			return $link;
		}
		public function kt_archive_image_link_open() {
			global $product, $kt_woo_extras;
			if( $product->is_type( 'external' ) ) {
				if( isset( $kt_woo_extras[ 'kt_aa_image_link_target' ] ) && 1 == $kt_woo_extras[ 'kt_aa_image_link_target' ] ) {
					echo  '<a href="'.esc_url( $product->get_product_url() ).'" class="product_item_link product_img_link" target="_blank">';
				} else {
					echo  '<a href="'.esc_url( $product->get_product_url() ).'" class="product_item_link product_img_link">';
				}
			} else {
				echo  '<a href="'.esc_url( get_the_permalink() ).'" class="product_item_link product_img_link">';
			}
		}
		public function kt_archive_image_link_close() {
			echo  '</a>';
		}
		public function kt_archive_title_link_target() {
			global $product, $kt_woo_extras;
			if ( isset( $kt_woo_extras[ 'kt_aa_title_link_target' ] ) && 1 == $kt_woo_extras[ 'kt_aa_title_link_target' ] ) {
				add_filter( 'woocommerce_shop_loop_title_link_target', array( $this, 'kt_archive_title_link_target_add' ) );
			}
		}
		public function kt_archive_title_link_target_add( $target ) {
			global $product;
			if ( $product->is_type( 'external' ) ) {
				return true;
			}
			return $target;
		}
		public function kt_archive_title_link_open() {
			global $product, $kt_woo_extras;
			if( $product->is_type( 'external' ) ) {
				if( isset( $kt_woo_extras[ 'kt_aa_title_link' ] ) && 1 == $kt_woo_extras[ 'kt_aa_title_link' ] ) {
					if( isset( $kt_woo_extras[ 'kt_aa_title_link_target' ] ) && 1 == $kt_woo_extras[ 'kt_aa_title_link_target' ] ) {
						echo  '<a href="'.esc_url( $product->get_product_url() ).'" class="product_item_link product_title_link" target="_blank">';
					} else {
						echo  '<a href="'.esc_url( $product->get_product_url() ).'" class="product_item_link product_title_link">';
					}
				} else {
					echo  '<a href="'.esc_url( get_the_permalink() ).'" class="product_item_link product_title_link">';
				}
			} else {
				echo  '<a href="'.esc_url( get_the_permalink() ).'" class="product_item_link product_title_link">';
			}
		}
		public function kt_archive_title_link_close() {
			echo  '</a>';
		}

		public function kt_archive_loop_add_to_cart( $link, $product ) {
			if ( $product->is_type( 'external' ) ) {
				$args = array();
				$defaults = array(
					'quantity' => 1,
					'class'    => implode( ' ', array_filter( array(
							'button',
							'product_type_' . $product->get_type(),
							$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
							$product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : '',
					) ) ),
				);

				$args = apply_filters( 'woocommerce_loop_add_to_cart_args', wp_parse_args( $args, $defaults ), $product );

				$link = sprintf( '<a rel="nofollow" href="%s" data-quantity="%s" data-product_id="%s" data-product_sku="%s" class="%s" target="_blank">%s</a>',
					esc_url( $product->add_to_cart_url() ),
					esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
					esc_attr( $product->get_id() ),
					esc_attr( $product->get_sku() ),
					esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
					esc_html( $product->add_to_cart_text() )
				);
			} 
			return $link;
		}

	}
	$GLOBALS['kt_affiliate_options'] = new kt_affiliate_options();
}

