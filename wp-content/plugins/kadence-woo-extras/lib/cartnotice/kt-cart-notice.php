<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_action( 'plugins_loaded', 'kt_cart_notice_plugin_loaded' );

function kt_cart_notice_plugin_loaded() {

	class Kadence_Cart_Notice {

		public function __construct() {
				add_action( 'init', array( $this, 'kt_cart_notice_post' ), 10 );
				add_action( 'admin_menu', array( $this, 'kt_cart_notice_menu' ) );
				add_action( 'woocommerce_before_cart_contents', array( $this, 'kt_custom_cart_notices' ) );
				add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before', array( $this, 'kt_custom_cart_notices' ) );
				add_action( 'wp_head', array( $this, 'kt_woo_coupon_links_update_url' ) );
				add_action( 'woocommerce_add_to_cart', array( $this, 'kt_woo_add_coupon_by_link' ), 30 );
				add_filter( 'woocommerce_ajax_get_endpoint', array( $this, 'kt_woo_coupon_links_ajax_endpoint' ) );
				add_action( 'wp_head', array( $this, 'kt_woo_remove_product_links_update_url' ) );
				add_filter( 'woocommerce_ajax_get_endpoint', array( $this, 'kt_woo_remove_product_links_ajax_endpoint' ) );
				add_action( 'wp_head', array( $this, 'kt_woo_add_product_links_update_url' ) );
				add_filter( 'woocommerce_ajax_get_endpoint', array( $this, 'kt_woo_add_product_links_ajax_endpoint' ) );
				add_action( 'wp_loaded', array( $this, 'kt_woo_add_coupon_by_link' ), 40 );
				add_action( 'wp_loaded', array( $this, 'kt_woo_add_product_by_link' ), 30 );
				add_action( 'wp_loaded', array( $this, 'kt_woo_remove_product_by_link' ), 20 );
				add_action( 'wp_loaded', array( $this, 'kt_woo_clear_cart_by_link' ), 10 );
				add_action( 'wp_head', array( $this, 'kt_woo_remove_clear_cart_links_update_url' ) );

				add_filter( 'cmb2_admin_init', array( $this, 'kt_woo_cart_notice_metaboxes' ) );
				add_action( 'cmb2_after_post_form__kt_woo_cart_notice', array( $this, 'kt_cart_notice_custom_css_for_metabox' ), 10 );
				add_action( 'wp_enqueue_scripts', array( $this, 'kt_cart_notice_enqueue_scripts' ) );

		}
		public function kt_cart_notice_enqueue_scripts() {
			if ( is_cart() ) {
				wp_enqueue_style( 'kadence_cart_notice_css', KADENCE_WOO_EXTRAS_URL . 'lib/cartnotice/css/kt_cart_notice.css', false, KADENCE_WOO_EXTRAS_VERSION );
			}
		}

		public function kt_custom_cart_notices() {
			$args = array(
				'post_type'      => 'kt_cart_notice',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'suppress_filters' => false,
			);
			$notices = get_posts( $args );
			foreach ( $notices as $notice ) {
				$display_type = get_post_meta( $notice->ID, '_kt_woo_cart_notice_display_type', true );
				if ( $display_type == 'price_min' ) {
					if ( $this->kt_if_valid_date( $notice->ID ) && $this->kt_if_valid_button( $notice->ID ) && $this->kt_if_valid_min_cart_total( $notice->ID ) ) {
						wc_get_template( 'kt-single-cart-notice.php', array( 'notice' => $notice ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/cartnotice/' );
					}
				} elseif ( $display_type == 'price' ) {
					if ( $this->kt_if_valid_date( $notice->ID ) && $this->kt_if_valid_button( $notice->ID ) && $this->kt_if_valid_max_cart_total( $notice->ID ) ) {
						wc_get_template( 'kt-single-cart-notice.php', array( 'notice' => $notice ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/cartnotice/' );
					}
				} elseif ( $display_type == 'weight_min' ) {
					if ( $this->kt_if_valid_date( $notice->ID ) && $this->kt_if_valid_button( $notice->ID ) && $this->kt_if_valid_min_weight( $notice->ID ) ) {
						wc_get_template( 'kt-single-cart-notice.php', array( 'notice' => $notice ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/cartnotice/' );
					}
				} elseif ( $display_type == 'weight' ) {
					if ( $this->kt_if_valid_date( $notice->ID ) && $this->kt_if_valid_button( $notice->ID ) && $this->kt_if_valid_max_weight( $notice->ID ) ) {
						wc_get_template( 'kt-single-cart-notice.php', array( 'notice' => $notice ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/cartnotice/' );
					}
				} elseif ( $display_type == 'product' ) {
					if ( $this->kt_if_valid_date( $notice->ID ) && $this->kt_if_valid_button( $notice->ID ) && $this->kt_if_valid_product_in_cart( $notice->ID ) ) {
						wc_get_template( 'kt-single-cart-notice.php', array( 'notice' => $notice ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/cartnotice/' );
					}
				} elseif ( $display_type == 'category' ) {
					if ( $this->kt_if_valid_date( $notice->ID ) && $this->kt_if_valid_button( $notice->ID ) && $this->kt_if_valid_category_in_cart( $notice->ID ) ) {
						wc_get_template( 'kt-single-cart-notice.php', array( 'notice' => $notice ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/cartnotice/' );
					}
				} else {
					if ( $this->kt_if_valid_date( $notice->ID ) && $this->kt_if_valid_button( $notice->ID ) ) {
						wc_get_template( 'kt-single-cart-notice.php', array( 'notice' => $notice ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/cartnotice/' );
					}
				}
			}
		}
		public function kt_cart_notice_menu() {
			add_submenu_page(
				'woocommerce',
				__( 'Cart Banners', 'kadence-woo-extras' ),
				__( 'Cart Banners', 'kadence-woo-extras' ),
				'manage_woocommerce',
				'edit.php?post_type=kt_cart_notice',
				false
			);
		}
		public function kt_if_valid_date( $id ) {
			$expire = get_post_meta( $id, '_kt_woo_cart_notice_expiration', true );
			if ( empty( $expire ) ) {
				return true;
			}
			$datetime = time();
			if ( $expire > $datetime ) {
				return true;
			}
			return false;
		}
		public function kt_if_valid_product_in_cart( $id ) {
			$valid_product = get_post_meta( $id, '_kt_woo_cart_notice_valid_product', true );
			if ( empty( $valid_product ) || $valid_product == '0' ) {
				return false;
			}
			if ( is_cart() || is_checkout() ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$_product = $values['data'];
					if ( isset( $_product->variation_id ) ) {

						$p_id = $_product->get_parent_id();
					} else {
						$p_id = $_product->get_id();
					}
					if ( $valid_product == $p_id ) {
						return true;
					}
				}
				return false;
			}
			return false;
		}
		public function kt_if_valid_category_in_cart( $id ) {
			$valid_category = get_post_meta( $id, '_kt_woo_cart_notice_valid_category', true );
			if ( empty( $valid_category ) || $valid_category == '0' ) {
				return false;
			}
			if ( is_cart() || is_checkout() ) {
				foreach ( WC()->cart->cart_contents as $key => $product_in_cart ) {
					if ( has_term( $valid_category, 'product_cat', $product_in_cart['product_id'] ) ) {
						return true;
					}
				}
				return false;
			}
			return false;
		}
		public function kt_if_valid_min_weight( $id ) {
			$min_weight = get_post_meta( $id, '_kt_woo_cart_notice_valid_weight', true );
			if ( empty( $min_weight ) ) {
				return false;
			}

			if ( is_cart() || is_checkout() ) {
				if ( $min_weight <= WC()->cart->cart_contents_weight ) {
					return true;
				}
			}
			return false;
		}
		public function kt_if_valid_max_weight( $id ) {
			$max_weight = get_post_meta( $id, '_kt_woo_cart_notice_valid_weight', true );
			if ( empty( $max_weight ) ) {
				return false;
			}
			if ( is_cart() || is_checkout() ) {
				if ( $max_weight >= WC()->cart->cart_contents_weight ) {
					return true;
				}
			}
			return false;
		}
		public function kt_if_valid_min_cart_total( $id ) {
			$min_price = get_post_meta( $id, '_kt_woo_cart_notice_valid_price', true );
			if ( empty( $min_price ) ) {
				return false;
			}
			if ( is_cart() || is_checkout() ) {
				if ( WC()->cart->subtotal_ex_tax >= $min_price ) {
					return true;
				}
			}
			return false;
		}
		public function kt_if_valid_max_cart_total( $id ) {
			$max_price = get_post_meta( $id, '_kt_woo_cart_notice_valid_price', true );
			if ( empty( $max_price ) ) {
				return false;
			}
			if ( is_cart() || is_checkout() ) {
				if ( WC()->cart->subtotal_ex_tax <= $max_price ) {
					return true;
				}
			}
			return false;
		}
		public function kt_if_valid_button( $id ) {
			$btn_type = get_post_meta( $id, '_kt_woo_cart_notice_btn_type', true );
			if ( empty( $btn_type ) || $btn_type == 'link' ) {
				return true;
			}
			if ( $btn_type == 'add_product' || $btn_type == 'upgrade' ) {
				$add_product = get_post_meta( $id, '_kt_woo_cart_notice_btn_add_product', true );
				$add_product_variation_data = get_post_meta( $id, '_kt_woo_cart_notice_btn_add_product_variation', true );
				parse_str( $add_product_variation_data, $add_product_variation_data_parsed );

				$add_product_variation_id = intval( $add_product_variation_data_parsed['variation_id'] ?? 0 );

				if ( empty( $add_product ) || $add_product == '0' ) {
					return true;
				}
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$_product = $values['data'];
					$temp2 = $_product->get_id();
					if ( $add_product == $_product->get_id() || $add_product_variation_id == $_product->get_id() ) {
						return false;
					}
				}
				return true;
			} else if ( $btn_type == 'coupon' ) {
				$coupon_id = get_post_meta( $id, '_kt_woo_cart_notice_btn_coupon', true );
				if ( empty( $coupon_id ) || $coupon_id == '0' ) {
					return true;
				}
				$coupon_code = get_the_title( $coupon_id );
				if ( is_cart() || is_checkout() ) {
					if ( WC()->cart->has_discount( $coupon_code ) ) {
						return false;
					}
				}
			} else if ( $btn_type == 'product_coupon' || $btn_type == 'upgrade_coupon' ) {
				// Check for product
				$add_product = get_post_meta( $id, '_kt_woo_cart_notice_btn_add_product', true );
				if ( empty( $add_product ) || $add_product == '0' ) {
					return true;
				}
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$_product = $values['data'];
					if ( $add_product == $_product->get_id() ) {
						return false;
					}
				}
				// Check for coupon
				$coupon_id = get_post_meta( $id, '_kt_woo_cart_notice_btn_coupon', true );
				if ( empty( $coupon_id ) || $coupon_id == '0' ) {
					return true;
				}
				$coupon_code = get_the_title( $coupon_id );
				if ( WC()->cart->has_discount( $coupon_code ) ) {
					return false;
				}
			}
			return true;
		}
		public function kt_woo_add_coupon_by_link() {
			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				return;
			}
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}
			$query_var = apply_filters( 'kt_woo_coupon_link_query_var', 'coupon_code' );
			if ( empty( $_GET[ $query_var ] ) ) {
				return;
			}
			WC()->session->set_customer_session_cookie( true );
			if ( ! WC()->cart->has_discount( $_GET[ $query_var ] ) ) {
				WC()->cart->add_discount( $_GET[ $query_var ] );
			}
		}
		function kt_woo_coupon_links_update_url() {
			$query_var = apply_filters( 'kt_woo_coupon_link_query_var', 'coupon_code' );
			if ( ! isset( $_GET[ $query_var ] ) ) {
				return;
			}
			?>
			<script>
			(function() {
				var queryVar = '<?php echo esc_js( $query_var ); ?>',
					queryParams = window.location.search.substr( 1 ).split( '&' ),
					url = window.location.href.split( '?' ).shift();
				for ( var i = queryParams.length; i-- > 0; ) {
					if ( 0 === queryParams[ i ].indexOf( queryVar + '=' ) ) {
						queryParams.splice( i, 1 );
					}
				}
				if ( queryParams.length > 0 ) {
					url += '?' + queryParams.join( '&' );
				}
				url += window.location.hash;
				if ( window.history.replaceState ) {
					window.history.replaceState( null, null, url );
				}
			})();
			</script>
			<?php
		}
		function kt_woo_coupon_links_ajax_endpoint( $endpoint ) {
			$query_var = apply_filters( 'kt_woo_coupon_link_query_var', 'coupon_code' );
			$token = 'kt-woo-coupon-links-url-safe-token';
			$endpoint = str_replace( '%%endpoint%%', $token, $endpoint );
			$endpoint = remove_query_arg( $query_var, $endpoint );
			return str_replace( $token, '%%endpoint%%', $endpoint );
		}
		public function kt_woo_clear_cart_by_link() {
			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				return;
			}
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}
			if ( isset( $_GET['clear-cart'] ) ) {
				WC()->cart->empty_cart();
			}
		}
		public function kt_woo_remove_clear_cart_links_update_url() {
			$query_var = 'clear-cart';
			if ( ! isset( $_GET[ $query_var ] ) ) {
				return;
			}
			?>
			<script>
			(function() {
				var queryVar = '<?php echo esc_js( $query_var ); ?>',
					queryParams = window.location.search.substr( 1 ).split( '&' ),
					url = window.location.href.split( '?' ).shift();
				for ( var i = queryParams.length; i-- > 0; ) {
					if ( 0 === queryParams[ i ].indexOf( queryVar + '=' ) ) {
						queryParams.splice( i, 1 );
					}
				}
				if ( queryParams.length > 0 ) {
					url += '?' + queryParams.join( '&' );
				}
				url += window.location.hash;
				if ( window.history.replaceState ) {
					window.history.replaceState( null, null, url );
				}
			})();
			</script>
			<?php
		}
		public function kt_woo_remove_product_by_link() {
			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				return;
			}
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}
			$query_var = apply_filters( 'kt_woo_remove_item_link_query_var', 'replace_item' );
			if ( empty( $_GET[ $query_var ] ) ) {
				return;
			}
			$remove_item = filter_var( $_GET[ $query_var ], FILTER_SANITIZE_NUMBER_INT );
			foreach ( WC()->cart->cart_contents as $prod_in_cart ) {
				$prod_id = ( isset( $prod_in_cart['variation_id'] ) && $prod_in_cart['variation_id'] != 0 ) ? $prod_in_cart['variation_id'] : $prod_in_cart['product_id'];
				if ( $remove_item == $prod_id ) {
					$prod_unique_id = WC()->cart->generate_cart_id( $prod_id );
					unset( WC()->cart->cart_contents[ $prod_unique_id ] );
				}
			}
		}
		function kt_woo_remove_product_links_update_url() {
			$query_var = apply_filters( 'kt_woo_remove_item_link_query_var', 'replace_item' );
			if ( ! isset( $_GET[ $query_var ] ) ) {
				return;
			}
			?>
			<script>
			(function() {
				var queryVar = '<?php echo esc_js( $query_var ); ?>',
					queryParams = window.location.search.substr( 1 ).split( '&' ),
					url = window.location.href.split( '?' ).shift();
				for ( var i = queryParams.length; i-- > 0; ) {
					if ( 0 === queryParams[ i ].indexOf( queryVar + '=' ) ) {
						queryParams.splice( i, 1 );
					}
				}
				if ( queryParams.length > 0 ) {
					url += '?' + queryParams.join( '&' );
				}
				url += window.location.hash;
				if ( window.history.replaceState ) {
					window.history.replaceState( null, null, url );
				}
			})();
			</script>
			<?php
		}
		function kt_woo_remove_product_links_ajax_endpoint( $endpoint ) {
			$query_var = apply_filters( 'kt_woo_remove_item_link_query_var', 'replace_item' );
			$token = 'kt-woo-remove-product-links-url-safe-token';
			$endpoint = str_replace( '%%endpoint%%', $token, $endpoint );
			$endpoint = remove_query_arg( $query_var, $endpoint );
			return str_replace( $token, '%%endpoint%%', $endpoint );
		}
		public function kt_woo_add_product_by_link() {
			if ( empty( $_REQUEST['kt-add-to-cart'] ) || ! is_numeric( $_REQUEST['kt-add-to-cart'] ) ) {
				return;
			}

			$product_id          = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['kt-add-to-cart'] ) );
			$was_added_to_cart   = false;
			$adding_to_cart      = wc_get_product( $product_id );

			if ( ! $adding_to_cart ) {
				return;
			}

			$add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->get_type(), $adding_to_cart );

			// Variable product handling
			if ( 'variable' === $add_to_cart_handler ) {
				$was_added_to_cart = false;
				// $adding_to_cart     = wc_get_product( $product_id );
				$variation_id       = empty( $_REQUEST['variation_id'] ) ? '' : absint( $_REQUEST['variation_id'] );
				$quantity           = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_REQUEST['quantity'] );
				$missing_attributes = array();
				$variations         = array();
				$attributes         = $adding_to_cart->get_attributes();

				// If no variation ID is set, attempt to get a variation ID from posted attributes.
				if ( empty( $variation_id ) ) {
					$product_data_store = new WC_Product_Data_Store_CPT();
					$variation_id = $product_data_store->find_matching_product_variation( $adding_to_cart, wp_unslash( $_POST ) );
				}

				$variation_data = wc_get_product_variation_attributes( $variation_id );

				// Verify all attributes
				foreach ( $attributes as $attribute ) {
					if ( ! $attribute['is_variation'] ) {
						continue;
					}

					$taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );

					if ( isset( $_REQUEST[ $taxonomy ] ) ) {

						// Get value from post data
						if ( $attribute['is_taxonomy'] ) {
							// Don't use wc_clean as it destroys sanitized characters
							$value = sanitize_title( wp_unslash( $_REQUEST[ $taxonomy ] ) );
						} else {
							$value = html_entity_decode( wc_clean( wp_unslash( $_REQUEST[ $taxonomy ] ) ), ENT_QUOTES, get_bloginfo( 'charset' ) ); // WPCS: sanitization ok.
						}
						// Get valid value from variation
						$valid_value = isset( $variation_data[ $taxonomy ] ) ? $variation_data[ $taxonomy ] : '';

						if ( $valid_value === $value ) {
							$variations[ $taxonomy ] = $value;
						} elseif ( '' === $valid_value && in_array( $value, $attribute->get_slugs() ) ) {
							// If valid values are empty, this is an 'any' variation so get all possible values.
							$variations[ $taxonomy ] = $value;
						}
					} else {
						$missing_attributes[] = wc_attribute_label( $attribute['name'] );
					}
				}

				if ( ! empty( $missing_attributes ) ) {
					wc_add_notice( sprintf( _n( '%s is a required field', '%s are required fields', sizeof( $missing_attributes ), 'woocommerce' ), wc_format_list_of_items( $missing_attributes ) ), 'error' );
				} elseif ( empty( $variation_id ) ) {
					wc_add_notice( __( 'Please choose product options&hellip;', 'woocommerce' ), 'error' );
				} else {
					// Add to cart validation
					$passed_validation  = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations );

					if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations ) !== false ) {
						wc_add_to_cart_message( array( $product_id => $quantity ), true );
						$was_added_to_cart = true;
					}
				}

				// Grouped Products
			} elseif ( 'grouped' === $add_to_cart_handler ) {
				$was_added_to_cart = false;
				$added_to_cart     = array();

				if ( ! empty( $_REQUEST['quantity'] ) && is_array( $_REQUEST['quantity'] ) ) {
					$quantity_set = false;

					foreach ( $_REQUEST['quantity'] as $item => $quantity ) {
						if ( $quantity <= 0 ) {
							continue;
						}
						$quantity_set = true;

						// Add to cart validation
						$passed_validation  = apply_filters( 'woocommerce_add_to_cart_validation', true, $item, $quantity );

						if ( $passed_validation && WC()->cart->add_to_cart( $item, $quantity ) !== false ) {
							$was_added_to_cart = true;
							$added_to_cart[ $item ] = $quantity;
						}
					}

					if ( ! $was_added_to_cart && ! $quantity_set ) {
						wc_add_notice( __( 'Please choose the quantity of items you wish to add to your cart&hellip;', 'woocommerce' ), 'error' );
					} elseif ( $was_added_to_cart ) {
						wc_add_to_cart_message( $added_to_cart );
						$was_added_to_cart = true;
					}
				} elseif ( $product_id ) {
					/* Link on product archives */
					wc_add_notice( __( 'Please choose a product to add to your cart&hellip;', 'woocommerce' ), 'error' );
				}

				// Custom Handler
			} elseif ( has_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler ) ) {
				do_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler, $url );

				// Simple Products
			} else {
				$was_added_to_cart = false;
				$quantity           = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_REQUEST['quantity'] );
				$passed_validation  = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

				if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity ) !== false ) {
					wc_add_to_cart_message( array( $product_id => $quantity ), true );
					$was_added_to_cart = true;
				}
			}
		}
		function kt_woo_add_product_links_update_url() {
			$query_var = 'kt-add-to-cart';
			if ( ! isset( $_GET[ $query_var ] ) ) {
				return;
			}
			?>
			<script>
			(function() {
				var queryVar = '<?php echo esc_js( $query_var ); ?>',
					queryParams = window.location.search.substr( 1 ).split( '&' ),
					url = window.location.href.split( '?' ).shift();
				for ( var i = queryParams.length; i-- > 0; ) {
					if ( 0 === queryParams[ i ].indexOf( queryVar + '=' ) ) {
						queryParams.splice( i, 1 );
					}
				}
				if ( queryParams.length > 0 ) {
					url += '?' + queryParams.join( '&' );
				}
				url += window.location.hash;
				if ( window.history.replaceState ) {
					window.history.replaceState( null, null, url );
				}
			})();
			</script>
			<?php
		}
		function kt_woo_add_product_links_ajax_endpoint( $endpoint ) {
			$query_var = 'kt-add-to-cart';
			$token = 'kt-woo-add-product-links-url-safe-token';
			$endpoint = str_replace( '%%endpoint%%', $token, $endpoint );
			$endpoint = remove_query_arg( $query_var, $endpoint );
			return str_replace( $token, '%%endpoint%%', $endpoint );
		}
		public function kt_cart_notice_post() {
			$cartnoticelabels = array(
				'name' => __( 'Cart Notice', 'kadence-woo-extras' ),
				'singular_name' => __( 'Cart Notice Item', 'kadence-woo-extras' ),
				'add_new' => __( 'Add New Cart Notice', 'kadence-woo-extras' ),
				'add_new_item' => __( 'Add New Cart Notice', 'kadence-woo-extras' ),
				'edit_item' => __( 'Edit Cart Notice', 'kadence-woo-extras' ),
				'new_item' => __( 'New Cart Notice', 'kadence-woo-extras' ),
				'all_items' => __( 'All Cart Notices', 'kadence-woo-extras' ),
				'view_item' => __( 'View Cart Notice', 'kadence-woo-extras' ),
				'search_items' => __( 'Search Cart Notices', 'kadence-woo-extras' ),
				'not_found' => __( 'No Cart Notice found', 'kadence-woo-extras' ),
				'not_found_in_trash' => __( 'No Cart Notices found in Trash', 'kadence-woo-extras' ),
				'parent_item_colon' => '',
				'menu_name' => __( 'Cart Notice', 'kadence-woo-extras' ),
			);

			$cartnoticeargs = array(
				'labels' => $cartnoticelabels,
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'exclude_from_search' => true,
				'show_in_menu' => false,
				'query_var' => true,
				'rewrite'  => false,
				'has_archive' => false,
				'capability_type' => 'post',
				'hierarchical' => false,
				'menu_position' => null,
				'supports' => array( 'title' ),
			);

			register_post_type( 'kt_cart_notice', $cartnoticeargs );
		}
		public function kt_woo_cart_notice_metaboxes() {
			$prefix = '_kt_woo_';
			$kt_woo_cart_notice = new_cmb2_box(
				array(
					'id'            => $prefix . 'cart_notice',
					'title'         => __( 'Cart Notice Settings', 'kadence-woo-extras' ),
					'object_types'  => array( 'kt_cart_notice' ), // Post type
				)
			);

			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Display Type', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_display_type',
					'type'          => 'pw_select',
					'options'          => array(
						'simple'        => __( 'Always Show', 'kadence-woo-extras' ),
						'product'       => __( 'Show when a certain product is in cart', 'kadence-woo-extras' ),
						'category'      => __( 'Show when a certain category is in cart', 'kadence-woo-extras' ),
						'price'         => __( 'Show when the cart total price is Equal or LESS than a certain amount', 'kadence-woo-extras' ),
						'price_min'     => __( 'Show when the cart total price is Equal or MORE than a certain amount', 'kadence-woo-extras' ),
						'weight'        => __( 'Show when the cart total weight is LESS than a certain amount', 'kadence-woo-extras' ),
						'weight_min'    => __( 'Show when the cart total weight is MORE than a certain amount', 'kadence-woo-extras' ),
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Choose which product', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_valid_product',
					'type'          => 'pw_select',
					'default'       => '0',
					'options_cb'    => 'kt_woo_product_posts_options',
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'cart_notice_display_type',
						'data-kadence-condition-value' => 'product',
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'      => __( 'Choose which category', 'kadence-woo-extras' ),
					'id'        => $prefix . 'cart_notice_valid_category',
					'type'      => 'pw_select',
					'options_cb'     => 'kt_get_term_options',
					'get_terms_args' => array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
					),
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'cart_notice_display_type',
						'data-kadence-condition-value' => 'category',
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Choose the price', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_valid_price',
					'default'       => '0',
					'type'          => 'kt_woo_text_number',
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'cart_notice_display_type',
						'data-kadence-condition-value' => 'price,price_min',
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Choose the weight', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_valid_weight',
					'default'       => '0',
					'type'          => 'kt_woo_text_number',
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'cart_notice_display_type',
						'data-kadence-condition-value' => 'weight,weight_min',
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Cart Notice Message', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_text',
					'type'          => 'textarea_small',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Cart Notice Button', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_text',
					'type'          => 'text',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Button Type', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_type',
					'type'          => 'pw_select',
					'options'          => array(
						'link'              => __( 'Custom Link', 'kadence-woo-extras' ),
						'add_product'       => __( 'Add a product to cart', 'kadence-woo-extras' ),
						'coupon'            => __( 'Add a coupon code', 'kadence-woo-extras' ),
						'product_coupon'    => __( 'Add product and coupon code', 'kadence-woo-extras' ),
						'upgrade'           => __( '"upgrade" Only for product display type - remove product and add new product', 'kadence-woo-extras' ),
						'upgrade_coupon'    => __( '"upgrade + coupon" Only for product display type - remove product and add new product with coupon code', 'kadence-woo-extras' ),
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Cart Notice Button Link', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_link',
					'type'          => 'text',
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'cart_notice_btn_type',
						'data-kadence-condition-value' => 'link',
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Product to add to cart', 'kadence-woo-extras' ),
					'desc'          => __( 'Also note that the notice will not show if this product is already in cart.', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_add_product',
					'type'          => 'pw_select',
					'default'       => '0',
					'options_cb'    => 'kt_woo_product_posts_options',
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'cart_notice_btn_type',
						'data-kadence-condition-value' => 'upgrade_coupon,upgrade,add_product,product_coupon',
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'If Product Type Variation', 'kadence-woo-extras' ),
					'desc'          => __( 'Add variation selection eg: &variation_id=117&attribute_size=Small&attribute_color=Black', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_add_product_variation',
					'type'          => 'text',
					'attributes'   => array(
						'data-kadence-condition-id'    => $prefix . 'cart_notice_btn_type',
						'data-kadence-condition-value' => 'upgrade_coupon,upgrade,add_product,product_coupon',
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Coupon to add', 'kadence-woo-extras' ),
					'desc'          => __( 'Note that the notice will not show if this coupon is already applied.', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_coupon',
					'type'          => 'pw_select',
					'default'       => '0',
					'options_cb'    => 'kt_woo_coupon_posts_options',
					'attributes' => array(
						'data-kadence-condition-id'    => $prefix . 'cart_notice_btn_type',
						'data-kadence-condition-value' => 'upgrade_coupon,coupon,product_coupon',
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Cart Notice Expiration (optional)', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_expiration',
					'type'          => 'text_datetime_timestamp',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Cart Notice Design Settings', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_design_info',
					'type'          => 'title',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Background Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_bg',
					'type'          => 'colorpicker',
					'default'       => 'transparent',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Border Radius', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_radius',
					'default'       => '0',
					'type'          => 'kt_woo_text_number',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Border Width', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_border',
					'default'       => '3',
					'type'          => 'kt_woo_text_number',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Border Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_border_color',
					'type'          => 'colorpicker',
					'default'       => '#aaaaaa',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Message Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_text_color',
					'type'          => 'colorpicker',
					'default'       => '#444444',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Button Text Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_txt_color',
					'type'          => 'colorpicker',
					'default'       => '#ffffff',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Button Hover Text Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_txt_color_hover',
					'type'          => 'colorpicker',
					'default'       => '#ffffff',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Button Background', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_background_color',
					'type'          => 'colorpicker',
					'default'       => '#999999',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Button Hover Background', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_background_color_hover',
					'type'          => 'colorpicker',
					'default'       => '#777777',
					'attributes' => array(
						'data-colorpicker' => json_encode(
							array(
								// Iris Options set here as values in the 'data-colorpicker' array
								'color' => true,
							)
						),
					),
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Button Border Radius', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_radius',
					'default'       => '0',
					'type'          => 'kt_woo_text_number',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Button Border Width', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_border',
					'default'       => '0',
					'type'          => 'kt_woo_text_number',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Button Border Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_border_color',
					'type'          => 'colorpicker',
				)
			);
			$kt_woo_cart_notice->add_field(
				array(
					'name'          => __( 'Notice Button Hover Border Color', 'kadence-woo-extras' ),
					'id'            => $prefix . 'cart_notice_btn_border_color_hover',
					'type'          => 'colorpicker',
				)
			);

		}
		public function kt_cart_notice_custom_css_for_metabox() {
			?>
				<style type="text/css" media="screen">
					.cmb-type-group .cmb2-wrap>.cmb-field-list>.cmb-row, .postbox-container .cmb2-wrap>.cmb-field-list>.cmb-row {
						margin-bottom: 0;
						padding: 8px 0;
					}
					.postbox-container .cmb2-wrap>.cmb-field-list>.cmb-row.cmb-type-title {
						background: #f9f9f9;
						margin: 0px;
						text-align: center;
						padding: 6px 10px;
						font-weight: bold;
						border-bottom: 2px solid #999;
					}
					.postbox-container .cmb2-wrap>.cmb-field-list>.cmb-row.cmb-type-title h5 {
						font-size: 20px;
						font-weight: bold;
					}
					</style>
				<?php
		}

	}
	new Kadence_Cart_Notice();
}

