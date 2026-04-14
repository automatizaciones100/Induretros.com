<?php
/**
 * Class Kadence_Woo_Block_Editor_Content_Controller
 *
 * @package Kadence Shop Kit
 */

/**
 * Class managing the rest end point for getting product data.
 */
class Kadence_Woo_Block_Editor_Content_Controller extends WP_REST_Controller {


	/**
	 * Query property name.
	 */
	const PROP_SOURCE = 'source';
/**
	 * Type property name.
	 */
	const PROP_TYPE = 'type';
	/**
	 * Per page property name.
	 */
	const PROP_PER_PAGE = 'per_page';
	/**
	 * Page property name.
	 */
	const PROP_PAGE = 'page';
	/**
	 * Query property name.
	 */
	const PROP_FIELD = 'field';
	/**
	 * Query property name.
	 */
	const PROP_TEMPLATE = 'template';
		/**
	 * Search property name.
	 */
	const PROP_SEARCH = 'search';

	/**
	 * Include property name.
	 */
	const PROP_INCLUDE = 'include';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'kwt-content/v1';
		$this->base = 'get';
		$this->select_base = 'post-select';
	}
	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->select_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_search_items_permission_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_content' ),
					'permission_callback' => array( $this, 'get_permission_check' ),
					'args'                => $this->get_render_params(),
				),
			)
		);
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$search    = $request->get_param( self::PROP_SEARCH );
		$include   = $request->get_param( self::PROP_INCLUDE );
		$prop_type = $request->get_param( self::PROP_TYPE );

		if ( empty( $prop_type ) ) {
			return array();
		}

		$query_args = array(
			'post_type'      => $request->get_param( self::PROP_TYPE ),
			'posts_per_page' => $request->get_param( self::PROP_PER_PAGE ),
			'paged'          => $request->get_param( self::PROP_PAGE ),
			'tax_query'      => array(),
			'filter_bundles' => true,
		);

		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		foreach ( $this->get_allowed_tax_filters() as $taxonomy ) {
			$base  = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
			$query = $request->get_param( $base );
			if ( ! empty( $query ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy'         => $taxonomy->name,
					'field'            => 'term_id',
					'terms'            => $query,
					'include_children' => false,
				);
			}
		}

		if ( $include ) {
			$query_args['post__in'] = $include;
			$query_args['orderby']  = 'post__in';
		}

		$query = new WP_Query( $query_args );
		$posts = array();

		foreach ( $query->posts as $post ) {
			$posts[] = $this->prepare_item_for_response( $post, $request );
		}

		$response = rest_ensure_response( $posts );

		$total_posts = $query->found_posts;
		$max_pages   = ceil( $total_posts / (int) $query->query_vars['posts_per_page'] );

		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		return $response;
	}
	/**
	 * Prepares a single result for response.
	 *
	 * @param int             $id      ID of the item to prepare.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $post, $request ) {

		$data = array(
			'id' => $post->ID,
			'title' => array(
				'raw'      => $post->post_title,
				'rendered' => get_the_title( $post->ID ),
			),
			'type' => $post->post_type,
			'date' => $this->prepare_date_response( $post->post_date_gmt, $post->post_date ),
			'slug' => $post->post_name,
			'status' => $post->post_status,
			'link' => get_permalink( $post->ID ),
			'author' => absint( $post->post_author ),
		);
		if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
			$post_date_gmt = get_gmt_from_date( $post->post_date );
		} else {
			$post_date_gmt = $post->post_date_gmt;
		}

		$data['date_gmt'] = $this->prepare_date_response( $post_date_gmt );

		return $data;
	}
	/**
	 * Checks the post_date_gmt or modified_gmt and prepare any post or
	 * modified date for single post output.
	 *
	 * @param string      $date_gmt GMT publication time.
	 * @param string|null $date     Optional. Local publication time. Default null.
	 * @return string|null ISO8601/RFC3339 formatted datetime.
	 */
	protected function prepare_date_response( $date_gmt, $date = null ) {
		// Use the date if passed.
		if ( isset( $date ) ) {
			return mysql2date( 'Y-m-d\TH:i:s', $date, false );
		}

		// Return null if $date_gmt is empty/zeros.
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}

		// Return the formatted datetime.
		return mysql2date( 'Y-m-d\TH:i:s', $date_gmt, false );
	}
	/**
	 * Checks if a given request has access to search content.
	 *
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has search access, WP_Error object otherwise.
	 */
	public function get_search_items_permission_check( $request ) {
		$prop_type = $request->get_param( self::PROP_TYPE );
		if ( is_array( $prop_type ) && ! empty( $prop_type[0] ) ) {
			$prop_type = $prop_type[0];
		}
		$post_type_object = get_post_type_object( $prop_type );
		$cap = 'edit_posts';
		if ( $post_type_object && isset( $post_type_object->cap->edit_posts ) ) {
			$cap = $post_type_object->cap->edit_posts;
		}
		return current_user_can( $cap );
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_content( $request ) {
		$source        = $request->get_param( self::PROP_SOURCE );
		$field         = $request->get_param( self::PROP_FIELD );
		$template      = $request->get_param( self::PROP_TEMPLATE );
		if ( empty( $field ) ) {
			return rest_ensure_response( esc_html__( 'No Content', 'kadence-woo-extras' ) );
		}
		$response = '';
		global $product, $post;
		$product = wc_get_product( $source );
		$post    = get_post( $source );
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		if ( is_object( $product ) ) {
			switch ( $field ) {
				case 'add_to_cart_text':
					if ( $template === 'single' ) {
						$response = $product->single_add_to_cart_text();
					} elseif ( $template === 'loop' ) {
						$response = $product->add_to_cart_text();
					}
					break;
				case 'add_to_cart':
					if ( $template === 'single' ) {
						add_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
						add_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
						add_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
						add_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
						add_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
						add_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
						ob_start();
						woocommerce_template_single_add_to_cart();
						$response = ob_get_contents();
						ob_end_clean();
					} elseif ( $template === 'loop' ) {
						ob_start();
						woocommerce_template_loop_add_to_cart();
						$response = ob_get_contents();
						ob_end_clean();
					}
					break;
				case 'price':
					$response = $product->get_price_html();
					break;
				case 'onsale':
					$response = $product->is_on_sale();
					break;
				case 'meta':
					$response = array();
					if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) {
						$sku = $product->get_sku() ? $product->get_sku() : esc_html__( 'N/A', 'kadence-woo-extras' );
						$response['sku'] = esc_html__( 'SKU:', 'kadence-woo-extras' ) . ' <span class="sku">' . $sku . '</span>';
					} else {
						$response['sku'] = esc_html__( 'SKU:', 'kadence-woo-extras' ) . ' <span class="sku">' . esc_html__( 'N/A', 'kadence-woo-extras' ) . '</span>';
					}
					$response['categories'] = wc_get_product_category_list( $product->get_id(), ', ', _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'kadence-woo-extras' ) . ' ', '' );
					$response['tags'] = wc_get_product_tag_list( $product->get_id(), ', ', _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'kadence-woo-extras' ) . ' ', '' );
					break;
				case 'products':
					if ( $template === 'archive' ) {
						if ( class_exists( 'Kadence\Theme' ) ) {
							//add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
							add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 5 );
							add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
							//add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
							add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
							add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
							//add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
							add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
						} else {
							add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
							add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 5 );
							add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
							add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
							add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
							add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
							add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
							add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
						}
						$columns = wc_get_default_products_per_row();
						$limit = apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() );
						ob_start();
						echo do_shortcode( '[products limit="' . esc_attr( $limit ) . '" columns="' . esc_attr( $columns ) . '" orderby="menu_order" order="ASC" visibility="visible"]' );
						$response = ob_get_contents();
						ob_end_clean();
					}
					break;
				case 'brand':
					if ( isset( $shopkit_settings['kt_product_brands_options'] ) && $shopkit_settings['kt_product_brands_options'] && class_exists( 'KT_Extra_Brands' ) ) {
						$brands_class = KT_Extra_Brands::get_instance();
						if ( $template === 'single' ) {
							ob_start();
							$brands_class->product_brand_output( $product->get_id() );
							$response = ob_get_contents();
							ob_end_clean();
						} else if ( $template === 'loop' ) {
							ob_start();
							$brands_class->product_brand_output_archive( $product->get_id() );
							$response = ob_get_contents();
							ob_end_clean();
						}
					}
					break;
				case 'reviews':
					if ( $template === 'single' && comments_open( $post ) ) {
						// Reviews tab - shows comments.
						if ( isset( $shopkit_settings['kt_reviews'] ) && $shopkit_settings['kt_reviews'] ) {
							ob_start();
							wc_get_template( 'kt-product-reviews.php', '', '', KADENCE_WOO_EXTRAS_PATH . 'lib/reviews/' );
							$response = ob_get_contents();
							ob_end_clean();
						} else {
							ob_start();
							wc_get_template( 'single-product-reviews.php' );
							$response = ob_get_contents();
							ob_end_clean();
						}
					}
					break;
				case 'breadcrumbs':
					if ( $template === 'single' ) {
						$breadcrumbs   = array();
						$breadcrumbs[] = array(
							__( 'Home', 'kadence-woo-extras' ),
							home_url(),
						);
						$shop_page_id  = wc_get_page_id( 'shop' );
						$breadcrumbs[] = array(
							get_the_title( $shop_page_id ),
							get_permalink( $shop_page_id ),
						);
						$terms = wc_get_product_terms(
							$post->ID,
							'product_cat',
							apply_filters(
								'woocommerce_breadcrumb_product_terms_args',
								array(
									'orderby' => 'parent',
									'order'   => 'DESC',
								)
							)
						);
						if ( $terms ) {
							$main_term = apply_filters( 'woocommerce_breadcrumb_main_term', $terms[0], $terms );
							$breadcrumbs[] = array(
								$terms[0]->name,
								get_term_link( $terms[0] ),
							);
						}
						$breadcrumbs[] = array(
							get_the_title( $post ),
							get_permalink( $post ),
						);
						$delimiter = '&nbsp;&#47;&nbsp';
						ob_start();
						echo '<nav class="woocommerce-breadcrumb">';
						foreach ( $breadcrumbs as $key => $crumb ) {
							if ( ! empty( $crumb[1] ) && sizeof( $breadcrumbs ) !== $key + 1 ) {
								echo '<a href="' . esc_url( $crumb[1] ) . '">' . esc_html( $crumb[0] ) . '</a>';
							} else {
								echo '<span class="bc-title">' . esc_html( $crumb[0] ) . '</span>';
							}
							if ( sizeof( $breadcrumbs ) !== $key + 1 ) {
								echo $delimiter;
							}
						}
						echo '</nav>';
						$response = ob_get_contents();
						ob_end_clean();
					}
					break;
				case 'rating':
					if ( $template === 'single' ) {
						ob_start();
						woocommerce_template_single_rating();
						$response = ob_get_contents();
						ob_end_clean();
					} elseif ( $template === 'loop' ) {
						$response = wc_get_rating_html( $product->get_average_rating() );
					}
					break;
				case 'social':
					ob_start();
					do_action( 'woocommerce_share' );
					$response = ob_get_contents();
					ob_end_clean();
					break;
				case 'upsell':
					if ( class_exists( 'Kadence\Theme' ) ) {
						//add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
						add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 5 );
						add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
						//add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
						add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
						add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
						//add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
						add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
					} else {
						add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
						add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 5 );
						add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
						add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
						add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
						add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
						add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
						add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
					}
					ob_start();
					woocommerce_upsell_display();
					$response = ob_get_contents();
					ob_end_clean();
					break;
				case 'related':
					if ( class_exists( 'Kadence\Theme' ) ) {
						//add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
						add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 5 );
						add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
						//add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
						add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
						add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
						//add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
						add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
					} else {
						add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
						add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 5 );
						add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
						add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
						add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
						add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
						add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
						add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
					}
					ob_start();
					woocommerce_output_related_products();
					$response = ob_get_contents();
					ob_end_clean();
					break;
				case 'additional_information':
					// Additional information tab - shows attributes.
					if ( $product && ( $product->has_attributes() || apply_filters( 'wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions() ) ) ) {
						ob_start();
						wc_display_product_attributes( $product );
						$response = ob_get_contents();
						ob_end_clean();
					}
					break;
				case 'tabs':
					$response = array();
					// Description tab - shows product content.
					if ( $post->post_content ) {
						ob_start();
						woocommerce_product_description_tab();
						$description_content = ob_get_contents();
						ob_end_clean();
						$response['description'] = array(
							'title'    => __( 'Description', 'kadence-woo-extras' ),
							'name'     => 'description',
							'priority' => 10,
							'content'  => $description_content,
						);
					}

					// Additional information tab - shows attributes.
					if ( $product && ( $product->has_attributes() || apply_filters( 'wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions() ) ) ) {
						ob_start();
						$heading = apply_filters( 'woocommerce_product_additional_information_heading', __( 'Additional information', 'kadence-woo-extras' ) );
						?>
						<?php if ( $heading ) : ?>
							<h2><?php echo esc_html( $heading ); ?></h2>
						<?php endif;
						wc_display_product_attributes( $product );
						$additional_information_content = ob_get_contents();
						ob_end_clean();
						$response['additional_information'] = array(
							'title'    => __( 'Additional information', 'kadence-woo-extras' ),
							'name'     => 'additional_information',
							'priority' => 20,
							'content'  => $additional_information_content,
						);
					}

					// Reviews tab - shows comments.
					if ( comments_open( $post ) ) {
						if ( isset( $shopkit_settings['kt_reviews'] ) && $shopkit_settings['kt_reviews'] ) {
							ob_start();
							wc_get_template( 'kt-product-reviews.php', '', '', KADENCE_WOO_EXTRAS_PATH . 'lib/reviews/' );
							$reviews_content = ob_get_contents();
							ob_end_clean();
						} else {
							ob_start();
							wc_get_template( 'single-product-reviews.php' );
							$reviews_content = ob_get_contents();
							ob_end_clean();
						}
						$response['reviews'] = array(
							/* translators: %s: reviews count */
							'title'    => sprintf( __( 'Reviews (%d)', 'kadence-woo-extras' ), $product->get_review_count() ),
							'name'     => 'reviews',
							'priority' => 30,
							'content'  => $reviews_content,
						);
					}
					$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );
					if ( ! empty( $product_tabs ) ) {
						foreach ( $product_tabs as $key => $product_tab ) {
							if ( $key === 'description' || $key === 'reviews' || $key === 'additonal_information' ) {
								continue;
							}
							ob_start();
							call_user_func( $product_tab['callback'], $key, $product_tab );
							$extra_content = ob_get_contents();
							ob_end_clean();
							$response[$key] = array(
								'title'    => wp_specialchars_decode( $product_tab['title'] ),
								'name'     => $key,
								'priority' => $product_tab['priority'],
								'content'  => $extra_content,
							);
						}
					}
					break;
				case 'image':
					if ( $template === 'single' ) {
						$image_size = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
						$post_thumbnail_id = $product->get_image_id();
						if ( $post_thumbnail_id ) {
							$html = '<div class="woocommerce-product-gallery__image">';
							$html .= wp_get_attachment_image( $post_thumbnail_id, $image_size, false );
							$html .= '</div>';
						} else {
							$html  = '<div class="woocommerce-product-gallery__image--placeholder">';
							$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
							$html .= '</div>';
						}
						ob_start();
						echo $html;
						$response = ob_get_contents();
						ob_end_clean();
					} elseif ( $template === 'loop' ) {
						ob_start();
						woocommerce_show_product_loop_sale_flash();
						woocommerce_template_loop_product_thumbnail();
						$response = ob_get_contents();
						ob_end_clean();
					}
					break;
				case 'gallery':
					if ( ( isset( $shopkit_settings['product_gallery'] ) && $shopkit_settings['product_gallery'] ) || $template === 'quickview' ) {
						if ( $product->get_image_id() ) {
							if( ! class_exists('Kadence_Shop_Kit_Product_Gallery')){
								require_once KADENCE_WOO_EXTRAS_PATH . 'lib/gallery/class-product-gallery.php';
							}
							$kskpg = Kadence_Shop_Kit_Product_Gallery::get_instance();
							$args  = $kskpg->get_gallery_args();
							$attachment_ids = $product->get_gallery_image_ids();
							if ( $attachment_ids ) {
								$images = array_merge( array( $product->get_image_id() ), $attachment_ids );
							} else {
								$images = array( $product->get_image_id() );
							}
							$response = array();
							foreach ( $images as $image ) {
								if ( ! $args['is_custom'] ) {
									$woo_image_size = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
									$woo_img        = wp_get_attachment_image_src( $image, $woo_image_size );
									$woo_meta       = wp_get_attachment_metadata( $image );
									$full_src       = wp_get_attachment_image_src( $image, 'full' );
									$img_srcset     = wp_calculate_image_srcset( array( $woo_img[1], $woo_img[2] ), $woo_img[0], $woo_meta, $image );
									$img            = array(
										'full' => esc_url( $full_src[0] ),
										'full_width' => $full_src[1],
										'full_height' => $full_src[2],
										'src' => esc_url( $woo_img[0] ),
										'width' => $woo_img[1],
										'height' => $woo_img[2],
										'alt' => 'product image',
										'srcset' => $img_srcset ? 'srcset="' . esc_attr( $img_srcset ) . '" sizes="(max-width: ' . esc_attr( $woo_img[1] ) . 'px) 100vw, ' . esc_attr( $woo_img[1] ) . 'px"' : '',
										'src_set' => $img_srcset ? $img_srcset : '',
										'sizes' => $img_srcset ? '(max-width: ' . esc_attr( $woo_img[1] ) . 'px) 100vw, ' . esc_attr( $woo_img[1] ) . 'px' : '',
									);
								} else {
									$img = kt_woo_get_image_array( $args['width'], $args['height'], true, 'attachment-shop-single', 'product image', $image, false, 'woocommerce_single' );
								}
								$thumbnail = kt_woo_get_image_array( $args['thumb_img_width'], $args['thumb_img_height'], true, 'attachment-shop-single', 'product image', $image, false, 'thumbnail' );
								$response[] = array(
									'src'           => $img['src'],
									'src_set'       => $img['src_set'],
									'sizes'         => $img['sizes'],
									'height'        => $img['height'],
									'width'         => $img['width'],
									'full'          => $img['full'],
									'full'          => $img['alt'],
									'thumb'         => $thumbnail['src'],
									'thumb_src_set' => $thumbnail['src_set'],
									'thumb_sizes'   => $thumbnail['sizes'],
									'thumb_height'  => $thumbnail['height'],
									'thumb_width'   => $thumbnail['width'],
								);
							}
						}
					} else {
						add_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
						ob_start();
						woocommerce_show_product_sale_flash();
						woocommerce_show_product_images();
						$response = ob_get_contents();
						ob_end_clean();
						remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
					}
					break;
			}
		}
		return rest_ensure_response( $response );
	}
	/**
	 * Checks if a given request has access to search content.
	 *
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has search access, WP_Error object otherwise.
	 */
	public function get_permission_check( $request ) {
		return current_user_can( 'edit_products' );
	}
	/**
	 * Retrieves the query params for the search results collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_render_params() {
		$query_params  = parent::get_collection_params();
		$query_params[ self::PROP_SOURCE ] = array(
			'description' => __( 'The source of the content.', 'kadence-woo-extras' ),
			'type'        => 'string',
		);
		$query_params[ self::PROP_FIELD ] = array(
			'description' => __( 'The content field', 'kadence-woo-extras' ),
			'type'        => 'string',
		);
		$query_params[ self::PROP_TEMPLATE ] = array(
			'description' => __( 'The template type', 'kadence-woo-extras' ),
			'type'        => 'string',
		);
		return $query_params;
	}
	/**
	 * Sanitizes the list of subtypes, to ensure only subtypes of the passed type are included.
	 *
	 * @param string|array    $subtypes  One or more subtypes.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @param string          $parameter Parameter name.
	 * @return array|WP_Error List of valid subtypes, or WP_Error object on failure.
	 */
	public function sanitize_post_types( $post_types, $request ) {
		$allowed_types = $this->get_allowed_post_types();
		return array_unique( array_intersect( $post_types, $allowed_types ) );
	}
	/**
	 * Validates the list of subtypes, to ensure it's an array.
	 *
	 * @param array    $value  One or more subtypes.
	 * @return bool    true or false.
	 */
	public function validate_post_types( $value ) {
		return is_array( $value );
	}
	/**
	 * Sanitizes the perpage, to ensure it's only a number.
	 *
	 * @param integer  $val number page page.
	 * @return integer a number
	 */
	public function sanitize_post_perpage( $val ) {
		return min( absint( $val ), 100 );
	}
	/**
	 * Sanitizes the list of ids, to ensure it's only numbers.
	 *
	 * @param array    $ids  One or more post ids.
	 * @return array   array of numbers
	 */
	public function sanitize_post_ids( $ids ) {
		return array_map( 'absint', $ids );
	}
	/**
	 * Validates the list of ids, to ensure it's not empty.
	 *
	 * @param array    $ids  One or more post ids.
	 * @return bool    true or false.
	 */
	public function validate_post_ids( $ids ) {
		return count( $ids ) > 0;
	}
	/**
	 * Get allowed post types.
	 *
	 * By default this is only post types that have show_in_rest set to true.
	 * You can filter this to support more post types if required.
	 *
	 * @return array
	 */
	public function get_allowed_post_types() {
		$allowed_types = array_values(
			get_post_types(
				array(
					'show_in_rest'       => true,
					'public'             => true,
				)
			)
		);
		$key = array_search( 'attachment', $allowed_types, true );

		if ( false !== $key ) {
			unset( $allowed_types[ $key ] );
		}

		/**
		 * Filter the allowed post types.
		 *
		 * Note that if you allow this for posts that are not otherwise public,
		 * this data will be accessible using this endpoint for any logged in user with the edit_post capability.
		 */
		return apply_filters( 'kadence_shop_kit_post_select_allowed_post_types', $allowed_types );
	}
	/**
	 * Sanitizes the page number, to ensure it's only a number.
	 *
	 * @param integer  $val number page page.
	 * @return integer a number
	 */
	public function sanitize_results_page_number( $val ) {
		return absint( $val );
	}
	/**
	 * Get allowed tax filters.
	 *
	 * @return array
	 */
	public function get_allowed_tax_filters() {
		$taxonomies = array();

		foreach ( $this->get_allowed_post_types() as $post_type ) {
			$taxonomies = array_merge(
				$taxonomies,
				wp_list_filter( get_object_taxonomies( $post_type, 'objects' ), array( 'show_in_rest' => true ) )
			);
		}

		return $taxonomies;
	}
	/**
	 * Retrieves the query params for the search results collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params  = parent::get_collection_params();
		$allowed_types = $this->get_allowed_post_types();

		$query_params[ self::PROP_TYPE ] = array(
			'description' => __( 'Limit results to items of an object type.', 'kadence-blocks-pro' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'string',
			),
			'sanitize_callback' => array( $this, 'sanitize_post_types' ),
			'validate_callback' => array( $this, 'validate_post_types' ),
			'default' => $allowed_types,
		);

		$query_params[ self::PROP_SEARCH ] = array(
			'description' => __( 'Limit results to items that match search query.', 'kadence-blocks-pro' ),
			'type'        => 'string',
		);

		$query_params[ self::PROP_INCLUDE ] = array(
			'description' => __( 'Include posts by ID.', 'kadence-blocks-pro' ),
			'type'        => 'array',
			'validate_callback' => array( $this, 'validate_post_ids' ),
			'sanitize_callback' => array( $this, 'sanitize_post_ids' ),
		);

		$query_params[ self::PROP_PER_PAGE ] = array(
			'description' => __( 'Number of results to return.', 'kadence-blocks-pro' ),
			'type'        => 'number',
			'sanitize_callback' => array( $this, 'sanitize_post_perpage' ),
			'default' => 25,
		);

		$query_params[ self::PROP_PAGE ] = array(
			'description' => __( 'Page of results to return.', 'kadence-blocks-pro' ),
			'type'        => 'number',
			'sanitize_callback' => array( $this, 'sanitize_results_page_number' ),
			'default' => 1,
		);

		foreach ( $this->get_allowed_tax_filters() as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			$query_params[ $base ] = array(
				/* translators: %s: taxonomy name */
				'description' => sprintf( __( 'Limit result set to all items that have the specified term assigned in the %s taxonomy.' ), $base ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
		}

		return $query_params;
	}
}
