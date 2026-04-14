<?php
/**
 * Class Kadence_Woo_Block_Editor_Templates
 *
 * @package Kadence Shop Kit
 */

/**
 * Class managing the template areas post type.
 */
class Kadence_Woo_Block_Editor_Templates {

	const SLUG = 'kadence_wootemplate';
	const TYPE_SLUG = 'wootemplate_type';
	const TYPE_META_KEY = '_kad_wootemplate_type';

	/**
	 * Current single override
	 *
	 * @var null
	 */
	public static $single_override = null;

	/**
	 * Current single override
	 *
	 * @var null
	 */
	public static $archive_override = null;

	/**
	 * Current single override
	 *
	 * @var null
	 */
	public static $loop_override = null;

	/**
	 * Current single override
	 *
	 * @var null
	 */
	public static $loop_override_array = array();

	/**
	 * Current condition
	 *
	 * @var null
	 */
	public static $current_condition = null;

	/**
	 * Current user
	 *
	 * @var null
	 */
	public static $current_user = null;

	/**
	 * Instance Control
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning instances of the class is Forbidden', 'kadence-woo-extras' ), '1.0' );
	}

	/**
	 * Disable un-serializing of the class.
	 *
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of the class is forbidden', 'kadence-woo-extras' ), '1.0' );
	}

	/**
	 * Instance Control.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor function.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ), 1 );
		add_filter( 'user_has_cap', array( $this, 'filter_post_type_user_caps' ) );
		add_action( 'init', array( $this, 'plugin_register' ), 20 );
		add_action( 'init', array( $this, 'register_meta' ), 20 );
		add_action( 'init', array( $this, 'setup_content_filter' ), 9 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'script_enqueue' ) );
		add_action( 'init', array( $this, 'register_wootemplate_blocks' ), 2 );
		add_action( 'wp', array( $this, 'init_frontend_hooks' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_enqueue_admin_scripts' ) );
		add_filter( 'kadence_post_layout', array( $this, 'wootemplates_single_layout' ), 99 );
		add_action( 'kadence_woocommerce_template_product_override', array( $this, 'add_wc_notices' ), );
		add_action( 'kadence_woocommerce_template_product_override', array( $this, 'get_product_content' ) );
		add_action( 'kadence_woocommerce_template_product_override', array( $this, 'get_product_schema' ), 20 );
		add_action( 'kadence_woocommerce_template_product_loop_override', array( $this, 'get_loop_product_content' ) );
		add_action( 'kadence_woocommerce_template_product_archive_override', array( $this, 'add_wc_notices' ), 5 );
		add_action( 'kadence_woocommerce_template_product_archive_override', array( $this, 'get_archive_product_content' ) );
		add_action( 'kadence_woocommerce_template_before_product', array( $this, 'add_wc_core_before_product_hook' ) );
		add_action( 'rest_api_init', array( $this, 'register_wootemplate_routes' ) );
		add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
		add_action( 'kadence_woocommerce_template_include_header', array( $this, 'get_header' ) );
		add_action( 'kadence_woocommerce_template_include_footer', array( $this, 'get_footer' ) );
		$slug = self::SLUG;
		add_filter(
			"manage_{$slug}_posts_columns",
			function( array $columns ) : array {
				return $this->filter_post_type_columns( $columns );
			}
		);
		add_action(
			"manage_{$slug}_posts_custom_column",
			function( string $column_name, int $post_id ) {
				$this->render_post_type_column( $column_name, $post_id );
			},
			10,
			2
		);
		// Add tabs for wootemplate "types". Here is where that happens.
		add_filter( 'views_edit-' . self::SLUG, array( $this, 'admin_print_tabs' ) );
		add_action( 'pre_get_posts', array( $this, 'admin_filter_results' ) );
		add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 10, 2 );
		add_action( 'init', array( $this, 'product_loop_short_description' ) );
		add_action( 'wp_ajax_kadence_wootemplate_change_status', array( $this, 'ajax_change_status' ) );
		if ( class_exists( 'Kadence_Woo_Duplicate_Post' ) ) {
			new Kadence_Woo_Duplicate_Post( self::SLUG );
		}
	}
	/**
	 * Load the header in a custom template.
	 */
	public function get_header() {
		$block_theme = ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ? true : false );
		if ( $block_theme ) {
			// Hack to allow Block styles to load.
			ob_start();
			block_header_area();
			$header_blocks = ob_get_contents();
			ob_end_clean();
			ob_start();
			block_footer_area();
			$footer_blocks = ob_get_contents();
			ob_end_clean();
			?>
			<!doctype html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<?php wp_head(); ?>
			</head>

			<body <?php body_class(); ?>>
			<?php wp_body_open(); ?>
			<div class="wp-site-blocks">
			<?php
			echo $header_blocks;
		} else {
			get_header( 'shop' );
		}
	}
	/**
	 * Load the footer in a custom template.
	 */
	public function get_footer() {
		$block_theme = ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ? true : false );
		if ( $block_theme ) {
			block_footer_area();
			?>
			</div>
			<?php wp_footer(); ?>

			</body>
			</html>
			<?php
		} else {
			get_footer( 'shop' );
		}
	}
	/**
	 * Enqueues a script that adds sticky for single products
	 *
	 * @param object $post the post object.
	 */
	public function add_wc_core_before_product_hook( $post ) {
		if ( class_exists( 'Kadence\Theme' ) ) {
			$kadence_theme_class = Kadence\Theme::instance();
			remove_action( 'woocommerce_before_single_product', array( $kadence_theme_class->components['woocommerce'], 'output_product_above' ), 20 );
			remove_action( 'woocommerce_before_single_product', array( $kadence_theme_class->components['woocommerce'], 'single_product_layout' ), 20 );
		}
		remove_action( 'woocommerce_before_single_product', 'woocommerce_output_all_notices', 10 );
		global $product;
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $post->ID );
		}
		do_action( 'woocommerce_before_single_product', $post );
	}
	/**
	 * Enqueues a script that adds sticky for single products
	 */
	public function action_enqueue_admin_scripts() {
		$current_page = get_current_screen();
		if ( 'edit-' . self::SLUG === $current_page->id ) {
			// Enqueue the post styles.
			wp_enqueue_style( 'kadence-wootemplates-admin', KADENCE_WOO_EXTRAS_URL . 'lib/templates/assets/css/wootemplate-post-admin.css', false, KADENCE_WOO_EXTRAS_VERSION );
			wp_enqueue_script( 'kadence_wootemplate-admin', KADENCE_WOO_EXTRAS_URL . 'lib/templates/assets/js/wootemplate-post-admin.js', array( 'jquery' ), KADENCE_WOO_EXTRAS_VERSION, true );
			wp_localize_script(
				'kadence_wootemplate-admin',
				'kadence_wootemplate_params',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'kadence_wootemplate-ajax-verification' ),
					'draft' => esc_attr__( 'Draft', 'kadence-woo-extras' ),
					'publish' => esc_attr__( 'Published', 'kadence-woo-extras' ),
				)
			);
		}
	}
	/**
	 * Change the post status
	 * @param number $post_id - The ID of the post you'd like to change.
	 * @param string $status -  The post status publish|pending|draft|private|static|object|attachment|inherit|future|trash.
	 */
	public function change_post_status( $post_id, $status ) {
		if ( 'publish' === $status || 'draft' === $status ) {
			$current_post = get_post( $post_id );
			$current_post->post_status = $status;
			return wp_update_post( $current_post );
		} else {
			return false;
		}
	}
	/**
	 * Ajax callback function.
	 */
	public function ajax_change_status() {
		check_ajax_referer( 'kadence_wootemplate-ajax-verification', 'security' );

		if ( ! isset ( $_POST['post_id'] ) || ! isset( $_POST['post_status'] ) ) {
			wp_send_json_error( __( 'Error: No post information was retrieved.', 'kadence-woo-extras' ) );
		}
		$post_id = empty( $_POST['post_id'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['post_id'] ) );
		$post_status = empty( $_POST['post_status'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['post_status'] ) );
		$response = false;
		if ( 'publish' === $post_status ) {
			$response = $this->change_post_status( $post_id, 'draft' );
		} else if ( 'draft' === $post_status ) {
			$response = $this->change_post_status( $post_id, 'publish' );
		}
		if ( ! $response ) {
			$error = new WP_Error( '001', 'Post Status invalid.' );
			wp_send_json_error( $error );
		}
		wp_send_json_success();
	}
	/**
	 * Add custom styles for template.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'kadence_wootemplate', KADENCE_WOO_EXTRAS_URL . 'lib/templates/assets/css/kadence-product-template.css', false, KADENCE_WOO_EXTRAS_VERSION );
	}
	/**
	 * Add Ace Block For Elements.
	 */
	public function register_wootemplate_routes() {
		$dynamic_controller = new Kadence_Woo_Block_Editor_Content_Controller();
		$dynamic_controller->register_routes();
	}
	/**
	 * Add fitler for product loop short description.
	 */
	public function product_loop_short_description() {
		add_filter( 'kadence_woocommerce_loop_short_description', 'wptexturize', 10);
		add_filter( 'kadence_woocommerce_loop_short_description', 'wpautop', 10);
		add_filter( 'kadence_woocommerce_loop_short_description', 'shortcode_unautop', 10);
		add_filter( 'kadence_woocommerce_loop_short_description', 'do_shortcode', 11 );
	}
	/**
	 * Add woo Blocks For templates.
	 */
	public function register_wootemplate_blocks() {
		// Check if this is the intended custom post type.
		$block_type = 'wootemplate-blocks';
		if ( is_admin() ) {
			global $pagenow;
			$typenow = '';
			if ( 'post-new.php' === $pagenow ) {
				if ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) {
					$typenow = $_REQUEST['post_type'];
				};
			} elseif ( 'post.php' === $pagenow ) {
				if ( isset( $_GET['post'] ) && isset( $_POST['post_ID'] ) && (int) $_GET['post'] !== (int) $_POST['post_ID'] ) {
					// Do nothing
				} elseif ( isset( $_GET['post'] ) ) {
					$post_id = (int) $_GET['post'];
				} elseif ( isset( $_POST['post_ID'] ) ) {
					$post_id = (int) $_POST['post_ID'];
				}

				if ( $post_id ) {
					$post = get_post( $post_id );
					$typenow = $post->post_type;
				}
			}
			if ( $typenow != self::SLUG ) {
				$block_type = 'wootemplate-child-blocks';
			}
		}
		// Register the blocks.
		$path = KADENCE_WOO_EXTRAS_URL . 'build/';
		$asset_file = $this->get_asset_file( $block_type );
		wp_register_script(
			'kadence-wootemplate-blocks',
			$path . $block_type . '.js',
			$asset_file['dependencies'],
			$asset_file['version']
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'kadence-wootemplate-blocks', 'kadence-woo-extras' );
		}
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		$variation_label = false;
		if ( isset( $shopkit_settings['variation_swatches'] ) && true == $shopkit_settings['variation_swatches'] && isset( $shopkit_settings['variation_label'] ) && true == $shopkit_settings['variation_label'] ) {
			$variation_label = true;
		}
		$loop_btn = 'normal';
		$size = 'normal';
		if ( class_exists( 'Kadence\Theme' ) ) {
			$cart_element = Kadence\kadence()->option( 'product_content_element_add_to_cart' );
			if ( isset( $cart_element ) && is_array( $cart_element ) && isset( $cart_element['button_size'] ) && ! empty( $cart_element['button_size'] ) ) {
				$size = $cart_element['button_size'];
			} else if ( Kadence\kadence()->option( 'product_large_cart_button' ) ) {
				$size = 'large';
			}
			$loop_btn = Kadence\kadence()->option( 'product_archive_button_style' );
		}
		wp_localize_script(
			'kadence-wootemplate-blocks',
			'kadenceWooBlockParams',
			array(
				'isKadence'          => ( class_exists( 'Kadence\Theme' ) ? true : false ),
				'cartBtnStyle'       => $size,
				'loopBtnStyle'       => $loop_btn,
				// 'authors'            => $this->get_author_options(),
				// 'display_single'     => $this->get_single_display_options(),
				// 'display_loop'       => $this->get_loop_display_options(),
				// 'display_loop_specific' => $this->get_loop_specific_display_options(),
				// 'display_archive'    => $this->get_archive_display_options(),
				// 'user'               => $this->get_user_options(),
				// 'languageSettings'   => $this->get_language_options(),
				// 'restBase'           => esc_url_raw( get_rest_url() ),
				// 'gallerySettings'    => $gallery,
				'variationLabel'     => $variation_label,
				'productDataEndpoint' => '/kwt-content/v1/get',
				// 'prebuilt'           => $prebuilt_data,
				// 'taxonomies'         => $this->get_product_taxonomies(),
				// 'previewPostID'      => apply_filters( 'kadence_wootemplates_dynamic_content_preview_post', $the_post_id ),
				// 'previewPostType'    => apply_filters( 'kadence_wootemplates_dynamic_content_preview_post_type', $the_post_type ),
			)
		);
		wp_register_style(
			'kadence-wootemplate-blocks',
			$path . 'wootemplate-blocks.css',
			array( 'wp-edit-blocks' ),
			$asset_file['version']
		);
		$add_to_cart_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_add_to_cart_block',
		);
		if ( $block_type === 'wootemplate-child-blocks' ) {
			$add_to_cart_block_args['ancestor'] = [ 'kadence/query-card' ];
			$add_to_cart_block_args['parent'] = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/add-to-cart',
			$add_to_cart_block_args
		);
		$title_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_title_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$title_block_args['ancestor'] = [ 'kadence/query-card' ];
			$title_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/title',
			$title_block_args
		);
		$price_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_price_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$price_block_args['ancestor'] = [ 'kadence/query-card' ];
			$price_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/price',
			$price_block_args
		);
		register_block_type(
			'kadence-wootemplate-blocks/gallery',
			array(
				'api_version' => 3,
				'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
				'editor_script' => 'kadence-wootemplate-blocks',
				'editor_style' => 'kadence-wootemplate-blocks',
				'render_callback' => 'kadence_wootemplate_render_gallery_block',
			)
		);
		$excerpt_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_excerpt_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$excerpt_block_args['ancestor'] = [ 'kadence/query-card' ];
			$excerpt_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/excerpt',
			$excerpt_block_args
		);
		$description_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_description_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$description_block_args['ancestor'] = [ 'kadence/query-card' ];
			$description_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/description',
			$description_block_args
		);
		$meta_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_meta_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$meta_block_args['ancestor'] = [ 'kadence/query-card' ];
			$meta_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/meta',
			$meta_block_args
		);
		register_block_type(
			'kadence-wootemplate-blocks/notice',
			array(
				'api_version' => 3,
				'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
				'editor_script' => 'kadence-wootemplate-blocks',
				'editor_style' => 'kadence-wootemplate-blocks',
				'render_callback' => 'kadence_wootemplate_render_notice_block',
			)
		);
		$hooks_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_hooks_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$hooks_block_args['ancestor'] = [ 'kadence/query-card' ];
			$hooks_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/hooks',
			$hooks_block_args
		);
		$brands_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_brands_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$brands_block_args['ancestor'] = [ 'kadence/query-card' ];
			$brands_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/brands',
			$brands_block_args
		);
		$rating_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_rating_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$rating_block_args['ancestor'] = [ 'kadence/query-card' ];
			$rating_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/rating',
			$rating_block_args
		);
		$image_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_image_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$image_block_args['ancestor'] = [ 'kadence/query-card' ];
			$image_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/image',
			$image_block_args
		);
		register_block_type(
			'kadence-wootemplate-blocks/tabs',
			array(
				'api_version' => 3,
				'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
				'editor_script' => 'kadence-wootemplate-blocks',
				'editor_style' => 'kadence-wootemplate-blocks',
				'render_callback' => 'kadence_wootemplate_render_tabs_block',
			)
		);
		register_block_type(
			'kadence-wootemplate-blocks/related',
			array(
				'api_version' => 3,
				'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
				'editor_script' => 'kadence-wootemplate-blocks',
				'editor_style' => 'kadence-wootemplate-blocks',
				'render_callback' => 'kadence_wootemplate_render_related_block',
			)
		);
		register_block_type(
			'kadence-wootemplate-blocks/reviews',
			array(
				'api_version' => 3,
				'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
				'editor_script' => 'kadence-wootemplate-blocks',
				'editor_style' => 'kadence-wootemplate-blocks',
				'render_callback' => 'kadence_wootemplate_render_reviews_block',
			)
		);
		$additional_block_args = array(
			'api_version' => 3,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-wootemplate-blocks',
			'editor_style' => 'kadence-wootemplate-blocks',
			'render_callback' => 'kadence_wootemplate_render_additional_information_block',
		);
		if ( 'wootemplate-child-blocks' === $block_type ) {
			$additional_block_args['ancestor'] = [ 'kadence/query-card' ];
			$additional_block_args['parent']   = [ 'kadence/query-card', 'kadence/column' ];
		}
		register_block_type(
			'kadence-wootemplate-blocks/additional-information',
			$additional_block_args
		);
		register_block_type(
			'kadence-wootemplate-blocks/breadcrumbs',
			array(
				'api_version' => 3,
				'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
				'editor_script' => 'kadence-wootemplate-blocks',
				'editor_style' => 'kadence-wootemplate-blocks',
				'render_callback' => 'kadence_wootemplate_render_breadcrumbs_block',
			)
		);
		register_block_type(
			'kadence-wootemplate-blocks/upsell',
			array(
				'api_version' => 3,
				'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
				'editor_script' => 'kadence-wootemplate-blocks',
				'editor_style' => 'kadence-wootemplate-blocks',
				'render_callback' => 'kadence_wootemplate_render_upsell_block',
			)
		);
		register_block_type(
			'kadence-wootemplate-blocks/products',
			array(
				'api_version' => 3,
				'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
				'editor_script' => 'kadence-wootemplate-blocks',
				'editor_style' => 'kadence-wootemplate-blocks',
				'render_callback' => 'kadence_wootemplate_render_products_block',
			)
		);
	}
	/**
	 * Get the asset file produced by wp scripts.
	 *
	 * @param string $filepath the file path.
	 * @return array
	 */
	public function get_asset_file( $filepath ) {
		$asset_path = KADENCE_WOO_EXTRAS_PATH . $filepath . '.asset.php';

		return file_exists( $asset_path )
			? include $asset_path
			: array(
				'dependencies' => array( 'lodash', 'react', 'react-dom', 'wp-block-editor', 'wp-blocks', 'wp-data', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-primitives', 'wp-api' ),
				'version'      => KADENCE_WOO_EXTRAS_VERSION,
			);
	}
	/**
	 * Add block category for Kadence Blocks.
	 *
	 * @param array  $categories the array of block categories.
	 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
	 */
	public function add_block_category( $categories, $block_editor_context ) {
		return array_merge(
			array(
				array(
					'slug'  => 'kadence-wootemplate-blocks',
					'title' => __( 'Woo Template Blocks', 'kadence-blocks' ),
				),
			),
			$categories
		);
	}
	/**
	 * Filter the post results if tabs selected.
	 *
	 * @param object $query An array of available list table views.
	 */
	public function admin_filter_results( $query ) {
		if ( ! ( is_admin() && $query->is_main_query() ) ) {
			return $query;
		}
		if ( ! ( isset( $query->query['post_type'] ) && 'kadence_wootemplate' === $query->query['post_type'] && isset( $_REQUEST[ self::TYPE_SLUG ] ) ) ) {
			return $query;
		}
		$screen = get_current_screen();
		if ( $screen->id == 'edit-kadence_wootemplate' ) {
			if ( isset( $_REQUEST[ self::TYPE_SLUG ] ) ) {
				$type_slug = sanitize_text_field( $_REQUEST[ self::TYPE_SLUG ] );
				if ( ! empty( $type_slug ) ) {
					$query->query_vars['meta_query'] = array(
						array(
							'key'   => self::TYPE_META_KEY,
							'value' => $type_slug,
						),
					);
				}
			}
		}
		return $query;
	}
	/**
	 * Print admin tabs.
	 *
	 * Used to output the conversion tabs with their labels.
	 *
	 *
	 * @param array $views An array of available list table views.
	 *
	 * @return array An updated array of available list table views.
	 */
	public function admin_print_tabs( $views ) {
		$current_type = '';
		$active_class = ' nav-tab-active';
		if ( ! empty( $_REQUEST[ self::TYPE_SLUG ] ) ) {
			$current_type = $_REQUEST[ self::TYPE_SLUG ];
			$active_class = '';
		}

		$url_args = [
			'post_type' => self::SLUG,
		];

		$baseurl = add_query_arg( $url_args, admin_url( 'edit.php' ) );
		?>
		<div id="kadence-element-tabs-wrapper" class="nav-tab-wrapper">
			<a class="nav-tab<?php echo esc_attr( $active_class ); ?>" href="<?php echo esc_url( $baseurl ); ?>">
				<?php echo esc_html__( 'All Woo Templates Items', 'kadence-woo-extras' ); ?>
			</a>
			<?php
			$types = array(
				'single' => array( 
					'label' => __( 'Single Product', 'kadence-woo-extras' ),
				),
				'loop' => array( 
					'label' => __( 'Product Catalog Loop Item', 'kadence-woo-extras' ),
				),
				'archive' => array( 
					'label' => __( 'Product Archive', 'kadence-woo-extras' ),
				),
			);
			foreach ( $types as $key => $type ) :
				$active_class = '';

				if ( $current_type === $key ) {
					$active_class = ' nav-tab-active';
				}

				$type_url = esc_url( add_query_arg( self::TYPE_SLUG, $key, $baseurl ) );
				$type_label = $type['label'];
				echo "<a class='nav-tab{$active_class}' href='{$type_url}'>{$type_label}</a>";
			endforeach;
			?>
		</div>
		<?php
		return $views;
	}

	/**
	 * Creates the plugin page and a submenu item in WP Appearance menu.
	 */
	public function create_admin_page() {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Woo Templates', 'kadence-woo-extras' ),
			__( 'Woo Templates', 'kadence-woo-extras' ),
			'edit_pages',
			'edit.php?post_type=' . self::SLUG
		);
	}
	/**
	 * Creates the plugin page and a submenu item in WP Appearance menu.
	 */
	public function add_wc_notices() {
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		if ( isset( $shopkit_settings['product_template_notices'] ) && true === $shopkit_settings['product_template_notices'] ) {
			if ( function_exists( 'wc_print_notices' ) ) {
				echo '<div class="woocommerce kadence-woo-messages-template-output woocommerce-notices-wrapper">';
				echo wc_print_notices( true );
				echo '</div>';
			}
		}
	}
	/**
	 * Add filters for element content output.
	 */
	public function setup_content_filter() {
		global $wp_embed;
		add_filter( 'kwootemplate_the_content', array( $wp_embed, 'run_shortcode' ), 8 );
		add_filter( 'kwootemplate_the_content', array( $wp_embed, 'autoembed'     ), 8 );
		add_filter( 'kwootemplate_the_content', 'do_blocks' );
		add_filter( 'kwootemplate_the_content', 'wptexturize' );
		// this creates an issue when outputing js in add to cart & gets converted. 
		// add_filter( 'kwootemplate_the_content', 'convert_chars' );
		// Don't use this unless classic editor add_filter( 'kwootemplate_the_content', 'wpautop' );
		add_filter( 'kwootemplate_the_content', 'shortcode_unautop' );
		add_filter( 'kwootemplate_the_content', 'do_shortcode', 11 );
		add_filter( 'kwootemplate_the_content', 'convert_smilies', 20 );
	}
	/**
	 * Loop through elements and hook items in where needed.
	 */
	public function init_frontend_hooks() {
		if ( is_admin() || is_singular( self::SLUG ) ) {
			return;
		}
		$args = array(
			'post_type'              => self::SLUG,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'post_status'            => 'publish',
			'numberposts'            => 333,
			'order'                  => 'ASC',
			'orderby'                => 'menu_order',
			'suppress_filters'       => false,
		);
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$meta = $this->get_post_meta_array( $post );
			if ( apply_filters( 'kadence_wootemplate_display', $this->check_woo_template_conditionals( $post, $meta ), $post, $meta ) ) {
				if ( 'single' === $meta['type'] ) {
					self::$single_override = $post->ID;
					add_filter( 'template_include', array( $this, 'single_product_page_template' ), 102, 3 );
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 70 );
					add_action( 'body_class', array( $this, 'add_body_class' ), 70 );
					$this->enqueue_template_styles( $post, $meta );
				} else if ( 'archive' === $meta['type'] ) {
					self::$archive_override = $post->ID;
					add_filter( 'template_include', array( $this, 'archive_page_template' ), 50 );
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 70 );
					$this->enqueue_template_styles( $post, $meta );
				} else if ( 'loop' === $meta['type'] ) {
					self::$loop_override = $post->ID;
					self::$loop_override_array[] = $post->ID;
					add_filter( 'wc_get_template_part', array( $this, 'loop_content_template_loader' ), 100, 3 );
					// Filter product blocks grid html.
					add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'custom_block_html' ), 10, 3 );
					add_filter( 'render_block', array( $this, 'custom_woo_product_block_html' ), 10, 3 );
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 70 );
					$this->enqueue_template_styles( $post, $meta );
				}
			}
		}
	}
	/**
	 * Adds custom classes to indicate the button size for the single products.
	 *
	 * @param array $classes Classes for the body element.
	 * @return array Filtered body classes.
	 */
	public function add_body_class( $classes ) {
		if ( is_product() ) {
			$classes[] = 'kadence-wootemplate-enabled';
		}
		return $classes;
	}
	/**
	 * Changes template for single product.
	 *
	 * @param string $template path to template.
	 */
	public function loop_content_template_loader( $template, $slug, $name ) {
		if ( 'content' === $slug && 'product' === $name ) {
			$template = KADENCE_WOO_EXTRAS_PATH . 'lib/templates/archive-product-loop.php';
		}
		return $template;
	}
	/**
	 * Add the dynamic content to blocks.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block info.
	 * @param object $wp_block The block class object.
	 */
	public function custom_woo_product_block_html( $block_content, $block, $wp_block ) {
		if ( is_admin() ) {
			return $block_content;
		}
		if ( 'woocommerce/all-products' === $block['blockName'] || 'woocommerce/product-new' === $block['blockName'] || 'woocommerce/product-on-sale' === $block['blockName'] || 'woocommerce/product-category' === $block['blockName'] || 'woocommerce/handpicked-products' === $block['blockName'] || 'woocommerce/product-tag' === $block['blockName'] || 'woocommerce/product-best-sellers' === $block['blockName'] || 'woocommerce/product-top-rated' === $block['blockName'] ) {
			$block_content = str_replace( '"wc-block-grid ', '"wc-block-grid woocommerce ', $block_content );
			$block_content = str_replace( 'wc-block-grid__products', 'wc-block-grid__products products', $block_content );
		}
		return $block_content;
	}
	/**
	 * Adds arrow icon to product action buttons.
	 *
	 * @param string $html the html for the product block.
	 * @param object $data block product object.
	 * @param object $product block product object.
	 * @return string updated html.
	 */
	public function custom_block_html( $html, $data, $the_product ) {
		global $product, $post;
		$temp_product = $product;
		$temp_post = $post;
		$product = $the_product;
		$post = get_post( $product->get_id() );
		do_action( 'kadence_woocommerce_template_before_block_loop' );
		ob_start();
		?>
		<li <?php wc_product_class( apply_filters( 'kadence_shop_kit_product_loop_classes', '' ), $product ); ?>>
			<?php
			/**
			 * Hook: kadence_woocommerce_template_after_loop hook.
			 */
			do_action( 'kadence_woocommerce_template_before_loop' );

			/**
			 * Hook: kadence_woocommerce_template_product_loop_override.
			 */
			do_action( 'kadence_woocommerce_template_product_loop_override', $post );

			/**
			 * Hook: kadence_woocommerce_template_after_loop hook.
			 */
			do_action( 'kadence_woocommerce_template_after_loop' );
			?>
		</li>
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		do_action( 'kadence_woocommerce_template_after_block_loop' );
		$product = $temp_product;
		$post = $temp_post;
		return $html;
	}
	/**
	 * Changes template for single product.
	 *
	 * @param string $template path to template.
	 */
	public function archive_page_template( $template ) {
		if ( is_embed() ) {
			return $template;
		}
		if ( is_archive() ) {
			$template = KADENCE_WOO_EXTRAS_PATH . 'lib/templates/archive-product.php';
		}
		return $template;
	}
	/**
	 * Changes template for single product.
	 *
	 * @param string $template path to template.
	 */
	public function single_product_page_template( $template ) {
		if ( is_embed() ) {
			return $template;
		}
		if ( is_singular( 'product' ) && self::$single_override ) {
			$template = KADENCE_WOO_EXTRAS_PATH . 'lib/templates/single-product.php';
		}
		return $template;
	}
	/**
	 * Load The product schema.
	 */
	public function get_product_schema() {
		WC()->structured_data->generate_product_data();
	}
	/**
	 * Prints the product content template.
	 *
	 * @param object $post the post object.
	 */
	public function get_product_content( $post ) {
		if ( self::$single_override ) {
			$template_post = get_post( self::$single_override );
			$this->output_woo_template( $template_post );
		}
	}
	/**
	 * Prints the product content template.
	 *
	 * @param object $post the post object.
	 */
	public function get_loop_product_content( $post ) {
		$is_output = false;
		if ( ! empty( self::$loop_override_array ) ) {
			foreach ( self::$loop_override_array as $override_id ) {
				if ( $this->check_woo_template_loop_conditionals( $override_id ) ) {
					$template_post = get_post( $override_id );
					$is_output = true;
					$this->output_woo_template( $template_post );
					break;
				}
			}
		}
		if ( ! $is_output ) {
			/**
			 * Hook: woocommerce_before_shop_loop_item.
			 *
			 * @hooked woocommerce_template_loop_product_link_open - 10
			 */
			do_action( 'woocommerce_before_shop_loop_item' );

			/**
			 * Hook: woocommerce_before_shop_loop_item_title.
			 *
			 * @hooked woocommerce_show_product_loop_sale_flash - 10
			 * @hooked woocommerce_template_loop_product_thumbnail - 10
			 */
			do_action( 'woocommerce_before_shop_loop_item_title' );

			/**
			 * Hook: woocommerce_shop_loop_item_title.
			 *
			 * @hooked woocommerce_template_loop_product_title - 10
			 */
			do_action( 'woocommerce_shop_loop_item_title' );

			/**
			 * Hook: woocommerce_after_shop_loop_item_title.
			 *
			 * @hooked woocommerce_template_loop_rating - 5
			 * @hooked woocommerce_template_loop_price - 10
			 */
			do_action( 'woocommerce_after_shop_loop_item_title' );

			/**
			 * Hook: woocommerce_after_shop_loop_item.
			 *
			 * @hooked woocommerce_template_loop_product_link_close - 5
			 * @hooked woocommerce_template_loop_add_to_cart - 10
			 */
			do_action( 'woocommerce_after_shop_loop_item' );
		}
		// if ( self::$loop_override ) {
		// 	$template_post = get_post( self::$loop_override );
		// 	$this->output_woo_template( $template_post );
		// }
	}
	/**
	 * Prints the product content template.
	 *
	 * @param object $post the post object.
	 */
	public function get_archive_product_content( $post ) {
		if ( self::$archive_override ) {
			$template_post = get_post( self::$archive_override );
			$this->output_woo_template( $template_post );
		}
	}
	/**
	 * Outputs the content of the wootemplate.
	 *
	 * @param object $post the post object.
	 * @param array  $meta the post meta.
	 */
	public function output_woo_template( $post, $meta = '' ) {
		$content = $post->post_content;
		if ( ! $content ) {
			return;
		}
		$content = apply_filters( 'kwootemplate_the_content', $content );
		if ( $content ) {
			echo '<!-- [wootemplate-' . esc_attr( $post->ID ) . '] -->';
			echo $content;
			echo '<!-- [/wootemplate-' . esc_attr( $post->ID ) . '] -->';
		}
	}
	/**
	 * Outputs the content of the element.
	 *
	 * @param object $post the post object.
	 * @param array  $meta the post meta.
	 * @param bool   $shortcode if the render is from a shortcode.
	 */
	public function enqueue_template_styles( $post, $meta, $shortcode = false ) {
		$content = $post->post_content;
		if ( ! $content ) {
			return;
		}
		$css_output = get_post_meta( $post->ID, '_kad_blocks_custom_css', true );
		$js_output = get_post_meta( $post->ID, '_kad_blocks_head_custom_js', true );
		if ( ! empty( $css_output ) || ! empty( $js_output ) ) {
			add_action(
				'wp_head',
				function() use( $post, $css_output, $js_output ) {
					if ( ! empty( $css_output ) ) {
						echo '<style id="kadence-blocks-post-custom-css-' . $post->ID . '">';
						echo $css_output;
						echo '</style>';
					}
					if ( ! empty( $js_output ) ) {
						echo $js_output;
					}
				},
				30
			);
		}
		$js_body_output = get_post_meta( $post->ID, '_kad_blocks_body_custom_js', true );
		if ( ! empty( $js_body_output ) ) {
			add_action(
				'wp_body_open',
				function() use( $js_body_output ) {
					echo $js_body_output;
				}, 
				10
			);
		}
		$js_footer_output = get_post_meta( $post->ID, '_kad_blocks_footer_custom_js', true );
		if ( ! empty( $js_footer_output ) ) {
			add_action(
				'wp_footer',
				function() use( $js_footer_output ) {
					echo $js_footer_output;
				}, 
				20
			);
		}
		if ( has_blocks( $content ) ) {
			$this->frontend_build_css( $post );
			if ( class_exists( 'Kadence_Blocks_Frontend' ) ) {
				$kadence_blocks = \Kadence_Blocks_Frontend::get_instance();
				if ( method_exists( $kadence_blocks, 'frontend_build_css' ) ) {
					$kadence_blocks->frontend_build_css( $post );
				}
				if ( class_exists( 'Kadence_Blocks_Pro_Frontend' ) ) {
					$kadence_blocks_pro = \Kadence_Blocks_Pro_Frontend::get_instance();
					if ( method_exists( $kadence_blocks_pro, 'frontend_build_css' ) ) {
						$kadence_blocks_pro->frontend_build_css( $post );
					}
				}
			}
			return;
		}
	}
	/**
	 * Outputs extra css for blocks.
	 *
	 * @param $post_object object of WP_Post.
	 */
	public function frontend_build_css( $post_object ) {
		if ( ! is_object( $post_object ) ) {
			return;
		}
		if ( ! method_exists( $post_object, 'post_content' ) ) {
			$blocks = parse_blocks( $post_object->post_content );
			if ( ! is_array( $blocks ) || empty( $blocks ) ) {
				return;
			}
			$block_slugs = array( 'kadence-wootemplate-blocks/add-to-cart', 'kadence-wootemplate-blocks/title', 'kadence-wootemplate-blocks/price', 'kadence-wootemplate-blocks/gallery', 'kadence-wootemplate-blocks/excerpt', 'kadence-wootemplate-blocks/description', 'kadence-wootemplate-blocks/meta', 'kadence-wootemplate-blocks/notice', 'kadence-wootemplate-blocks/hooks', 'kadence-wootemplate-blocks/brands', 'kadence-wootemplate-blocks/rating', 'kadence-wootemplate-blocks/tabs', 'kadence-wootemplate-blocks/related', 'kadence-wootemplate-blocks/reviews', 'kadence-wootemplate-blocks/additional-information', 'kadence-wootemplate-blocks/breadcrumbs', 'kadence-wootemplate-blocks/upsell', 'kadence-wootemplate-blocks/products' );
			foreach ( $blocks as $indexkey => $block ) {
				if ( ! is_object( $block ) && is_array( $block ) && isset( $block['blockName'] ) ) {
					if ( in_array( $block['blockName'], $block_slugs ) ) {
						if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
							call_user_func( 'kadence_wootemplate_render_' . str_replace( '-', '_', str_replace( 'kadence-wootemplate-blocks/', '', $block['blockName'] ) ) . '_output_css', $block['attrs'] );
						}
					}
				}
			}
		}
	}
	/**
	 * Gets and returns page conditions.
	 */
	public static function get_current_page_conditions() {
		if ( is_null( self::$current_condition ) ) {
			$condition   = array( 'general|site' );
			if ( is_search() ) {
				$condition[] = 'general|search';
				if ( class_exists( 'woocommerce' ) && function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
					$condition[] = 'general|product_search';
				}
			} elseif ( is_singular() ) {
				$condition[] = 'general|singular';
				$condition[] = 'singular|' . get_post_type();
			} elseif ( is_archive() ) {
				$queried_obj = get_queried_object();
				$condition[] = 'general|archive';
				if ( is_post_type_archive() && is_object( $queried_obj ) ) {
					$condition[] = 'post_type_archive|' . $queried_obj->name;
				} elseif ( is_tax() || is_category() || is_tag() ) {
					if ( is_object( $queried_obj ) ) {
						$condition[] = 'tax_archive|' . $queried_obj->taxonomy;
					}
				} elseif ( is_date() ) {
					$condition[] = 'general|date';
				} elseif ( is_author() ) {
					$condition[] = 'general|author';
				}
			}
			if ( is_paged() ) {
				$condition[] = 'general|paged';
			}
			if ( class_exists( 'woocommerce' ) ) {
				if ( ( is_archive() && function_exists( 'is_woocommerce' ) && is_woocommerce() ) || is_product_taxonomy() || is_post_type_archive( 'product' ) || is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) {
					$condition[] = 'archive|product';
				}
				if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
					$condition[] = 'general|woocommerce';
				}
			}
			self::$current_condition = $condition;
		}
		return self::$current_condition;
	}
	/**
	 * Tests if any of a post's assigned term are descendants of target term
	 *
	 * @param string $term_id The term id.
	 * @param string $tax The target taxonomy slug.
	 * @return bool True if at least 1 of the post's categories is a descendant of any of the target categories
	 */
	public function post_is_in_descendant_term( $term_id, $tax ) {
		$descendants = get_term_children( (int) $term_id, $tax );
		if ( ! is_wp_error( $descendants ) && is_array( $descendants ) ) {
			foreach ( $descendants as $child_id ) {
				if ( has_term( $child_id, $tax ) ) {
					return true;
				}
			}
		}
		return false;
	}
	/**
	 * Gets and returns page conditions.
	 */
	public static function get_current_loop_conditions() {
		global $post;
		$condition   = array( 'general|site' );
		$terms = get_the_terms( $post->ID, 'product_cat' );
		if ( $terms ) {
			$condition[] = 'tax_archive|product_cat';
			foreach ( $terms as $term ) {
				$condition[] = 'term|' . $term->term_id;
			}
		}
		return $condition;
	}
	/**
	 * Check if element should show in current page.
	 *
	 * @param object $post the current element to check.
	 * @return bool
	 */
	public function check_woo_template_loop_conditionals( $post_id ) {
		$the_post = get_post( $post_id );
		$meta = $this->get_post_meta_array( $the_post );
		$current_condition      = $this->get_current_loop_conditions();
		$rules_with_sub_rules   = array( 'tax_archive' );
		$show = true;
		if ( isset( $meta ) && isset( $meta['show_loop'] ) && is_array( $meta['show_loop'] ) && ! empty( $meta['show_loop'] ) ) {
			$show = false;
			foreach ( $meta['show_loop'] as $key => $rule ) {
				$rule_show = false;
				if ( isset( $rule['rule'] ) && in_array( $rule['rule'], $current_condition ) ) {
					$rule_split = explode( '|', $rule['rule'], 2 );
					if ( in_array( $rule_split[0], $rules_with_sub_rules ) ) {
						if ( ! isset( $rule['select'] ) || isset( $rule['select'] ) && 'all' === $rule['select'] ) {
							$show      = true;
							$rule_show = true;
						} else if ( isset( $rule['select'] ) && 'individual' === $rule['select'] ) {
							if ( isset( $rule['subSelection'] ) && is_array( $rule['subSelection'] ) ) {
								foreach ( $rule['subSelection'] as $sub_key => $selection ) {
									if ( isset( $selection['value'] ) && ! empty( $selection['value'] ) ) {
										if ( in_array( 'term|' . $selection['value'], $current_condition ) ) {
											$show      = true;
											$rule_show = true;
											break;
										}
									}
								}
							}
						}
					} else {
						$show      = true;
						$rule_show = true;
					}
				}
			}
		}
		return $show;
	}
	/**
	 * Check if element should show in current page.
	 *
	 * @param object $post the current element to check.
	 * @return bool
	 */
	public function check_woo_template_conditionals( $post, $meta ) {
		// Verify template match.
		if ( empty( $meta['type'] ) ) {
			return false;
		}
		if ( ( 'single' === $meta['type'] && ! is_product() ) || ( 'single' === $meta['type'] && ! is_null( self::$single_override ) ) ) {
			return false;
		}
		if ( ( 'archive' === $meta['type'] && ! is_archive() ) || ( 'archive' === $meta['type'] && ! is_null( self::$archive_override ) ) ) {
			return false;
		}
		// if ( 'loop' === $meta['type'] && ! is_null( self::$loop_override ) ) {
		// 	return false;
		// }
		$current_condition      = self::get_current_page_conditions();
		$rules_with_sub_rules   = array( 'singular', 'tax_archive' );
		$show = false;
		$all_must_be_true = ( isset( $meta ) && isset( $meta['all_show'] ) ? $meta['all_show'] : false );
		if ( isset( $meta ) && isset( $meta['show'] ) && is_array( $meta['show'] ) && ! empty( $meta['show'] ) ) {
			foreach ( $meta['show'] as $key => $rule ) {
				$rule_show = false;
				if ( isset( $rule['rule'] ) && in_array( $rule['rule'], $current_condition ) ) {
					$rule_split = explode( '|', $rule['rule'], 2 );
					if ( in_array( $rule_split[0], $rules_with_sub_rules ) ) {
						if ( ! isset( $rule['select'] ) || isset( $rule['select'] ) && 'all' === $rule['select'] ) {
							$show      = true;
							$rule_show = true;
						} else if ( isset( $rule['select'] ) && 'author' === $rule['select'] ) {
							if ( isset( $rule['subRule'] ) && $rule['subRule'] == get_post_field( 'post_author', get_queried_object_id() ) ) {
								$show      = true;
								$rule_show = true;
							}
						} else if ( isset( $rule['select'] ) && 'tax' === $rule['select'] ) {
							if ( isset( $rule['subRule'] ) && isset( $rule['subSelection'] ) && is_array( $rule['subSelection'] ) ) {
								foreach ( $rule['subSelection'] as $sub_key => $selection ) {
									if ( 'assigned_course' === $rule['subRule'] ) {
										$course_id = get_post_meta( get_queried_object_id(), 'course_id', true );
										if ( $selection['value'] == $course_id ) {
											$show      = true;
											$rule_show = true;
										} elseif ( isset( $rule['mustMatch'] ) && $rule['mustMatch'] ) {
											return false;
										}
									} elseif ( has_term( $selection['value'], $rule['subRule'] ) ) {
										$show      = true;
										$rule_show = true;
									} elseif ( $this->post_is_in_descendant_term( $selection['value'], $rule['subRule'] ) ) {
										$show      = true;
										$rule_show = true;
									} elseif ( isset( $rule['mustMatch'] ) && $rule['mustMatch'] ) {
										return false;
									}
								}
							}
						} else if ( isset( $rule['select'] ) && 'ids' === $rule['select'] ) {
							if ( isset( $rule['ids'] ) && is_array( $rule['ids'] ) ) {
								$current_id = get_the_ID();
								foreach ( $rule['ids'] as $sub_key => $sub_id ) {
									if ( $current_id === $sub_id ) {
										$show      = true;
										$rule_show = true;
									}
								}
							}
						} else if ( isset( $rule['select'] ) && 'individual' === $rule['select'] ) {
							if ( isset( $rule['subSelection'] ) && is_array( $rule['subSelection'] ) ) {
								$queried_obj = get_queried_object();
								$show_taxs   = array();
								foreach ( $rule['subSelection'] as $sub_key => $selection ) {
									if ( isset( $selection['value'] ) && ! empty( $selection['value'] ) ) {
										$show_taxs[] = $selection['value'];
									}
								}
								if ( in_array( $queried_obj->term_id, $show_taxs ) ) {
									$show      = true;
									$rule_show = true;
								}
							}
						}
					} else {
						$show      = true;
						$rule_show = true;
					}
				}
				if ( ! $rule_show && $all_must_be_true ) {
					return false;
				}
			}
		}
		// Exclude Rules.
		if ( $show ) {
			if ( isset( $meta ) && isset( $meta['hide'] ) && is_array( $meta['hide'] ) && ! empty( $meta['hide'] ) ) {
				foreach ( $meta['hide'] as $key => $rule ) {
					if ( isset( $rule['rule'] ) && in_array( $rule['rule'], $current_condition ) ) {
						$rule_split = explode( '|', $rule['rule'], 2 );
						if ( in_array( $rule_split[0], $rules_with_sub_rules ) ) {
							if ( ! isset( $rule['select'] ) || isset( $rule['select'] ) && 'all' === $rule['select'] ) {
								$show = false;
							} else if ( isset( $rule['select'] ) && 'author' === $rule['select'] ) {
								if ( isset( $rule['subRule'] ) && $rule['subRule'] == get_post_field( 'post_author', get_queried_object_id() ) ) {
									$show = false;
								}
							} else if ( isset( $rule['select'] ) && 'tax' === $rule['select'] ) {
								if ( isset( $rule['subRule'] ) && isset( $rule['subSelection'] ) && is_array( $rule['subSelection'] ) ) {
									foreach ( $rule['subSelection'] as $sub_key => $selection ) {
										if ( 'assigned_course' === $rule['subRule'] ) {
											$course_id = get_post_meta( get_queried_object_id(), 'course_id', true );
											if ( $selection['value'] == $course_id ) {
												$show = false;
											} elseif ( isset( $rule['mustMatch'] ) && $rule['mustMatch'] ) {
												$show = true;
												continue;
											}
										} elseif ( has_term( $selection['value'], $rule['subRule'] ) ) {
											$show = false;
										} elseif ( isset( $rule['mustMatch'] ) && $rule['mustMatch'] ) {
											$show = true;
											continue;
										}
									}
								}
							} else if ( isset( $rule['select'] ) && 'ids' === $rule['select'] ) {
								if ( isset( $rule['ids'] ) && is_array( $rule['ids'] ) ) {
									$current_id = get_the_ID();
									foreach ( $rule['ids'] as $sub_key => $sub_id ) {
										if ( $current_id === $sub_id ) {
											$show = false;
										}
									}
								}
							} else if ( isset( $rule['select'] ) && 'individual' === $rule['select'] ) {
								if ( isset( $rule['subSelection'] ) && is_array( $rule['subSelection'] ) ) {
									$queried_obj = get_queried_object();
									$show_taxs   = array();
									foreach ( $rule['subSelection'] as $sub_key => $selection ) {
										if ( isset( $selection['value'] ) && ! empty( $selection['value'] ) ) {
											$show_taxs[] = $selection['value'];
										}
									}
									if ( in_array( $queried_obj->term_id, $show_taxs ) ) {
										$show = false;
									}
								}
							}
						} else {
							$show = false;
						}
					}
				}
			}
		}
		if ( $show ) {
			if ( isset( $meta ) && isset( $meta['user'] ) && is_array( $meta['user'] ) && ! empty( $meta['user'] ) ) {
				$user_info  = self::get_current_user_info();
				$show_roles = array();
				foreach ( $meta['user'] as $key => $user_rule ) {
					if ( isset( $user_rule['role'] ) && ! empty( $user_rule['role'] ) ) {
						$show_roles[] = $user_rule['role'];
					}
				}
				$match = array_intersect( $show_roles, $user_info );
				if ( count( $match ) === 0 ) {
					$show = false;
				}
			}
		}
		if ( $show ) {
			if ( isset( $meta ) && isset( $meta['enable_expires'] ) && true == $meta['enable_expires'] && isset( $meta['expires'] ) && ! empty( $meta['expires'] ) ) {
				$expires = strtotime( get_date_from_gmt( $meta['expires'] ) );
				$now     = strtotime( get_date_from_gmt( current_time( 'Y-m-d H:i:s' ) ) );
				if ( $expires < $now ) {
					$show = false;
				}
			}
		}
		// Language.
		if ( $show ) {
			if ( ! empty( $meta['language'] ) ) {
				if ( function_exists( 'pll_current_language' ) ) {
					$language_slug = pll_current_language( 'slug' );
					if ( $meta['language'] !== $language_slug ) {
						$show = false;
					}
				}
				if ( $current_lang = apply_filters( 'wpml_current_language', NULL ) ) {
					if ( $meta['language'] !== $current_lang ) {
						$show = false;
					}
				}
			}
		}
		return $show;
	}
	/**
	 * Get current user information.
	 */
	public static function get_current_user_info() {
		if ( is_null( self::$current_user ) ) {
			$user_info = array( 'public' );
			if ( is_user_logged_in() ) {
				$user_info[] = 'logged_in';
				$user = wp_get_current_user();
				$user_info = array_merge( $user_info, $user->roles );
			} else {
				$user_info[] = 'logged_out';
			}

			self::$current_user = $user_info;
		}
		return self::$current_user;
	}
	/**
	 * Get an array of post meta.
	 *
	 * @param object $post the current element to check.
	 * @return array
	 */
	public function get_post_meta_array( $post ) {
		$meta = array(
			'show'           => array(),
			'all_show'       => false,
			'hide'           => array(),
			'user'           => array(
				array(
					'role' => 'public',
				),
			),
			'enable_expires' => false,
			'expires'        => '',
			'type'           => 'single',
			'language'       => '',
			'show_loop'      => '',
		);
		if ( get_post_meta( $post->ID, '_kad_wootemplate_type', true ) ) {
			$meta['type'] = get_post_meta( $post->ID, '_kad_wootemplate_type', true );
		}
		if ( get_post_meta( $post->ID, '_kad_wootemplate_show_conditionals', true ) ) {
			$meta['show'] = json_decode( get_post_meta( $post->ID, '_kad_wootemplate_show_conditionals', true ), true );
		}
		if ( get_post_meta( $post->ID, '_kad_wootemplate_show_loop_conditionals', true ) ) {
			$meta['show_loop'] = json_decode( get_post_meta( $post->ID, '_kad_wootemplate_show_loop_conditionals', true ), true );
		}
		if ( get_post_meta( $post->ID, '_kad_wootemplate_all_show', true ) ) {
			$meta['all_show'] = boolval( get_post_meta( $post->ID, '_kad_wootemplate_all_show', true ) );
		}
		if ( get_post_meta( $post->ID, '_kad_wootemplate_hide_conditionals', true ) ) {
			$meta['hide'] = json_decode( get_post_meta( $post->ID, '_kad_wootemplate_hide_conditionals', true ), true );
		}
		if ( get_post_meta( $post->ID, '_kad_wootemplate_user_conditionals', true ) ) {
			$meta['user'] = json_decode( get_post_meta( $post->ID, '_kad_wootemplate_user_conditionals', true ), true );
		}
		if ( get_post_meta( $post->ID, '_kad_wootemplate_enable_expires', true ) ) {
			$meta['enable_expires'] = get_post_meta( $post->ID, '_kad_wootemplate_enable_expires', true );
		}
		if ( get_post_meta( $post->ID, '_kad_wootemplate_expires', true ) ) {
			$meta['expires'] = get_post_meta( $post->ID, '_kad_wootemplate_expires', true );
		}
		if ( get_post_meta( $post->ID, '_kad_wootemplate_language', true ) ) {
			$meta['language'] = get_post_meta( $post->ID, '_kad_wootemplate_language', true );
		}
		return $meta;
	}
	/**
	 * Enqueue Script for Meta options
	 */
	public function script_enqueue() {
		$post_type = get_post_type();
		if ( self::SLUG !== $post_type ) {
			return;
		}
		$path = KADENCE_WOO_EXTRAS_URL . 'inc/assets/';
		wp_enqueue_style( 'kadence-wootemplates-meta', $path . 'css/meta-controls.css', false, KADENCE_WOO_EXTRAS_VERSION );
		wp_enqueue_script( 'kadence-wootemplates-meta' );
		if ( get_post_meta( get_the_ID(), '_kad_wootemplate_preview_post', true ) ) {
			$the_post_id   = get_post_meta( get_the_ID(), '_kad_wootemplate_preview_post', true );
			$the_post_type = 'product';
		} else {
			$recent_posts  = wp_get_recent_posts( array( 'post_type' => 'product', 'numberposts' => '1' ) );
			$the_post_id   = array(
				'id'   => ( ! empty( $recent_posts[0]['ID'] ) ? $recent_posts[0]['ID'] : null ),
				'name' => ( ! empty( $recent_posts[0]['post_title'] ) ? $recent_posts[0]['post_title'] : __( 'Latest Product', 'kadence-woo-extras' ) ),
			);
			$the_post_id = wp_json_encode( $the_post_id );
			$the_post_type = 'product';
		}
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		$variation_label = false;
		if ( isset( $shopkit_settings['variation_swatches'] ) && true == $shopkit_settings['variation_swatches'] ) {
			if ( ( empty( $shopkit_settings['variation_label_placement'] ) && isset( $shopkit_settings['variation_label'] ) && true == $shopkit_settings['variation_label'] ) || ( ! empty( $shopkit_settings['variation_label_placement'] ) && 'above' === $shopkit_settings['variation_label_placement'] ) ) {
				$variation_label = true;
			}
		}
		$snackbar = isset( $shopkit_settings['snackbar_notices'] ) && true == $shopkit_settings['snackbar_notices'] ? true : false;
		$gallery = array(
			'custom_width'  => ( ! empty( $shopkit_settings['ga_image_width'] ) ? $shopkit_settings['ga_image_width'] : 465 ),
			'ratio'         => ( ! empty( $shopkit_settings['ga_image_ratio'] ) ? $shopkit_settings['ga_image_ratio'] : 'square' ),
			'layout'        => ( ! empty( $shopkit_settings['ga_slider_layout'] ) ? $shopkit_settings['ga_slider_layout'] : 'above' ),
			'thumb_width'   => ( ! empty( $shopkit_settings['ga_thumb_width'] ) ? $shopkit_settings['ga_thumb_width'] : '20' ),
			'is_custom'     => ( isset( $shopkit_settings['product_gallery_custom_size'] ) && false == $shopkit_settings['product_gallery_custom_size'] ? false : true ),
			'transtype'     => ( ! empty( $shopkit_settings['ga_trans_type'] ) && 'true' === $shopkit_settings['ga_trans_type'] ? true : false ),
			'show_caption'  => ( ! empty( $shopkit_settings['ga_show_caption'] ) && 'true' === $shopkit_settings['ga_show_caption'] ? true : false ),
			'autoplay'      => ( ! empty( $shopkit_settings['ga_slider_autoplay'] ) && 'true' === $shopkit_settings['ga_slider_autoplay'] ? true : false ),
			'pausetime'     => ( ! empty( $shopkit_settings['ga_slider_pausetime'] ) ? $shopkit_settings['ga_slider_pausetime'] : '7000' ),
			'transtime'     => ( ! empty( $shopkit_settings['ga_slider_transtime'] ) ? $shopkit_settings['ga_slider_transtime'] : '400' ),
			'zoomactive'    => ( ! empty( $shopkit_settings['ga_zoom'] ) ? $shopkit_settings['ga_zoom'] : false ),
			'zoomtype'      => ( ! empty( $shopkit_settings['ga_zoom_type'] ) ? $shopkit_settings['ga_zoom_type'] : 'window' ),
			'thumb_columns' => ( ! empty( $shopkit_settings['ga_thumb_columns'] ) ? $shopkit_settings['ga_thumb_columns'] : 7 ),
			'thumb_ratio'   => ( ! empty( $shopkit_settings['ga_thumb_image_ratio'] ) ? $shopkit_settings['ga_thumb_image_ratio'] : 'square' ),
			'arrows'        => ( ! empty( $shopkit_settings['ga_slider_arrows'] ) && 'true' === $shopkit_settings['ga_slider_arrows'] ? true : false ),
			'kadence_gallery_enabled' => ( isset( $shopkit_settings['product_gallery'] ) && $shopkit_settings['product_gallery'] ),
		);
		// Responsive Defaults.
		$gallery['layout_tablet'] = ( ! empty( $shopkit_settings['ga_slider_layout_tablet'] ) ? $shopkit_settings['ga_slider_layout_tablet'] : $gallery['layout'] );
		$gallery['layout_mobile'] = ( ! empty( $shopkit_settings['ga_slider_layout_mobile'] ) ? $shopkit_settings['ga_slider_layout_mobile'] : $gallery['layout_tablet'] );
		$gallery['thumb_width_tablet'] = ( ! empty( $shopkit_settings['ga_thumb_width_tablet'] ) ? $shopkit_settings['ga_thumb_width_tablet'] : $gallery['thumb_width'] );
		$gallery['thumb_width_mobile'] = ( ! empty( $shopkit_settings['ga_thumb_width_mobile'] ) ? $shopkit_settings['ga_thumb_width_mobile'] : $gallery['thumb_width_tablet'] );
		$gallery['thumb_columns_tablet'] = ( ! empty( $shopkit_settings['ga_thumb_columns_tablet'] ) ? $shopkit_settings['ga_thumb_columns_tablet'] : $gallery['thumb_columns'] );
		$gallery['thumb_columns_mobile'] = ( ! empty( $shopkit_settings['ga_thumb_columns_mobile'] ) ? $shopkit_settings['ga_thumb_columns_mobile'] : $gallery['thumb_columns_tablet'] );
		$loop_btn = 'normal';
		$size = 'normal';
		if ( class_exists( 'Kadence\Theme' ) ) {
			$cart_element = Kadence\kadence()->option( 'product_content_element_add_to_cart' );
			if ( isset( $cart_element ) && is_array( $cart_element ) && isset( $cart_element['button_size'] ) && ! empty( $cart_element['button_size'] ) ) {
				$size = $cart_element['button_size'];
			} else if ( Kadence\kadence()->option( 'product_large_cart_button' ) ) {
				$size = 'large';
			}
			$loop_btn = Kadence\kadence()->option( 'product_archive_button_style' );
		}
		ob_start();
		include KADENCE_WOO_EXTRAS_PATH . 'lib/templates/assets/kadence-wootemplates.json';
		$prebuilt_data = ob_get_clean();
		wp_localize_script(
			'kadence-wootemplates-meta',
			'kadenceWooTemplateParams',
			array(
				'post_type'          => $post_type,
				'isKadence'          => ( class_exists( 'Kadence\Theme' ) ? true : false ),
				'snackbarNotices'    => $snackbar,
				'tabStyle'           => ( class_exists( 'Kadence\Theme' ) ? Kadence\kadence()->option( 'product_tab_style' ) : 'normal' ),
				'variationStyle'     => ( class_exists( 'Kadence\Theme' ) ? Kadence\kadence()->option( 'product_tab_style' ) : 'normal' ),
				'cartBtnStyle'       => $size,
				'loopBtnStyle'       => $loop_btn,
				'authors'            => $this->get_author_options(),
				'display_single'     => $this->get_single_display_options(),
				'display_loop'       => $this->get_loop_display_options(),
				'display_loop_specific' => $this->get_loop_specific_display_options(),
				'plugin_url'         => KADENCE_WOO_EXTRAS_URL,
				'display_archive'    => $this->get_archive_display_options(),
				'user'               => $this->get_user_options(),
				'languageSettings'   => $this->get_language_options(),
				'restBase'           => esc_url_raw( get_rest_url() ),
				'gallerySettings'    => $gallery,
				'variationLabel'     => $variation_label,
				'postSelectEndpoint' => '/kwt-content/v1/post-select',
				'productDataEndpoint' => '/kwt-content/v1/get',
				'prebuilt'           => $prebuilt_data,
				'taxonomies'         => $this->get_product_taxonomies(),
				'previewPostID'      => apply_filters( 'kadence_wootemplates_dynamic_content_preview_post', $the_post_id ),
				'previewPostType'    => apply_filters( 'kadence_wootemplates_dynamic_content_preview_post_type', $the_post_type ),
			)
		);
	}
	/**
	 * Get all language Options
	 */
	public function get_language_options() {
		$languages_options = array();
		// Check for Polylang.
		if ( function_exists( 'pll_the_languages' ) ) {
			$languages = pll_the_languages( array( 'raw' => 1 ) );
			foreach ( $languages as $lang ) {
				$languages_options[] = array(
					'value' => $lang['slug'],
					'label' => $lang['name'],
				);
			}
		}
		// Check for WPML.
		if ( defined( 'WPML_PLUGIN_FILE' ) ) {
			$languages = apply_filters( 'wpml_active_languages', array() );
			foreach ( $languages as $lang ) {
				$languages_options[] = array(
					'value' => $lang['code'],
					'label' => $lang['native_name'],
				);
			}
		}
		return apply_filters( 'kadence_woo_template_display_languages', $languages_options );
	}
	/**
	 * Get all Display Options
	 */
	public function get_user_options() {
		$user_basic = array(
			array(
				'label' => esc_attr__( 'Basic', 'kadence-woo-extras' ),
				'options' => array(
					array(
						'value' => 'public',
						'label' => esc_attr__( 'All Users', 'kadence-woo-extras' ),
					),
					array(
						'value' => 'logged_out',
						'label' => esc_attr__( 'Logged out Users', 'kadence-woo-extras' ),
					),
					array(
						'value' => 'logged_in',
						'label' => esc_attr__( 'Logged in Users', 'kadence-woo-extras' ),
					),
				),
			),
		);
		$user_roles = array();
		$specific_roles = array();
		foreach ( get_editable_roles() as $role_slug => $role_info ) {
			$specific_roles[] = array(
				'value' => $role_slug,
				'label' => $role_info['name'],
			);
		}
		$user_roles[] = array(
			'label' => esc_attr__( 'Specific Role', 'kadence-woo-extras' ),
			'options' => $specific_roles,
		);
		$roles = array_merge( $user_basic, $user_roles );
		return apply_filters( 'kadence_woo_template_user_options', $roles );
	}

	/**
	 * Get all Display Options
	 */
	public function get_single_display_options() {
		$display_singular = array(
			array(
				'value' => 'singular|product',
				'label' => esc_attr__( 'Single Product', 'kadence-woo-extras' ),
			),
		);
		return apply_filters( 'kadence_woo_template_single_display_options', $display_singular );
	}
		/**
	 * Get all Display Options
	 */
	public function get_loop_specific_display_options() {
		$display = array(
			array(
				'value' => 'general|site',
				'label' => esc_attr__( 'All Product Loops', 'kadence-woo-extras' ),
			),
			array(
				'value' => 'tax_archive|product_cat',
				'label' => esc_attr__( 'Has Category Term', 'kadence-woo-extras' ),
			),
		);
		// $post_type_tax_objects = get_object_taxonomies( 'product', 'objects' );
		// foreach ( $post_type_tax_objects as $taxonomy_slug => $taxonomy ) {
		// 	if ( $taxonomy->public && $taxonomy->show_ui && 'post_format' !== $taxonomy_slug ) {
		// 		$display[] = array(
		// 			'value' => 'tax_archive|' . $taxonomy_slug,
		// 			/* translators: %1$s: taxonomy singular label.  */
		// 			'label' => sprintf( esc_attr__( 'Has %1$s Term', 'kadence-woo-extras' ), $taxonomy->labels->singular_name ),
		// 		);
		// 	}
		// }
		return apply_filters( 'kadence_woo_template_loop_specific_display_options', $display );
	}
	/**
	 * Get all Display Options
	 */
	public function get_loop_display_options() {
		$display_singular = array();
		$post_type_item  = get_post_type_object( 'product' );
		$post_type_name  = $post_type_item->name;
		$post_type_label = $post_type_item->label;
		$post_type_label_plural = $post_type_item->labels->name;
		// $post_type_options = array(
		// 	array(
		// 		'value' => 'singular|' . $post_type_name,
		// 		'label' => esc_attr__( 'Single', 'kadence-woo-extras' ) . ' ' . $post_type_label_plural,
		// 	),
		// );
		$post_type_options = array(
			array(
				'value' => 'general|site',
				'label' => esc_attr__( 'All Product Loops', 'kadence-woo-extras' ),
			),
		);
		$post_type_tax_objects = get_object_taxonomies( 'product', 'objects' );
		foreach ( $post_type_tax_objects as $taxonomy_slug => $taxonomy ) {
			if ( $taxonomy->public && $taxonomy->show_ui && 'post_format' !== $taxonomy_slug ) {
				$post_type_options[] = array(
					'value' => 'tax_archive|' . $taxonomy_slug,
					/* translators: %1$s: taxonomy singular label.  */
					'label' => sprintf( esc_attr__( '%1$s Archives', 'kadence-woo-extras' ), $taxonomy->labels->singular_name ),
				);
			}
		}
		if ( ! empty( $post_type_item->has_archive ) ) {
			$post_type_options[] = array(
				'value' => 'post_type_archive|' . $post_type_name,
				/* translators: %1$s: post type plural label  */
				'label' => sprintf( esc_attr__( '%1$s Archive', 'kadence-woo-extras' ), $post_type_label_plural ),
			);
		}
		if ( class_exists( 'woocommerce' ) && 'product' === $post_type_name ) {
			$post_type_options[] = array(
				'value' => 'general|product_search',
				/* translators: %1$s: post type plural label  */
				'label' => sprintf( esc_attr__( '%1$s Search', 'kadence-woo-extras' ), $post_type_label_plural ),
			);
		}
		$display_singular[] = array(
			'label' => $post_type_label,
			'options' => $post_type_options,
		);
		$display = $post_type_options;
		return apply_filters( 'kadence_woo_template_loop_display_options', $display );
	}
	/**
	 * Get all Display Options
	 */
	public function get_all_display_options() {
		$loop_options = $this->get_loop_display_options();
		$archive_options = $this->get_archive_display_options();
		$single_options = $this->get_single_display_options();
		$display_options = array_merge( $single_options, $archive_options );
		$display_options = array_merge( $loop_options, $display_options );
		return apply_filters( 'kadence_woo_template_all_display_options', $display_options );
	}
	/**
	 * Get archive Display Options
	 */
	public function get_archive_display_options() {
		$display_options = array();
		$display_options[] = array(
			'value' => 'archive|product',
			'label' => esc_attr__( 'All Product Archives', 'kadence-woo-extras' ),
		);
		$post_type_tax_objects = get_object_taxonomies( 'product', 'objects' );
		foreach ( $post_type_tax_objects as $taxonomy_slug => $taxonomy ) {
			if ( $taxonomy->public && $taxonomy->show_ui && 'post_format' !== $taxonomy_slug ) {
				$display_options[] = array(
					'value' => 'tax_archive|' . $taxonomy_slug,
					/* translators: %1$s: taxonomy singular label.  */
					'label' => sprintf( esc_attr__( '%1$s Archives', 'kadence-woo-extras' ), $taxonomy->labels->singular_name ),
				);
			}
		}
		$display_options[] = array(
			'value' => 'post_type_archive|product',
			'label' => esc_attr__( 'Shop Page', 'kadence-woo-extras' ),
		);
		$display_options[] = array(
			'value' => 'general|product_search',
			'label' => esc_attr__( 'Product Search', 'kadence-woo-extras' ),
		);
		return apply_filters( 'kadence_woo_template_archive_display_options', $display_options );
	}
	/**
	 * Get all Author Options
	 */
	public function get_author_options() {
		$roles__in = array();
		foreach ( wp_roles()->roles as $role_slug => $role ) {
			if ( ! empty( $role['capabilities']['edit_posts'] ) ) {
				$roles__in[] = $role_slug;
			}
		}
		$authors = get_users( array( 'roles__in' => $roles__in, 'fields' => array( 'ID', 'display_name' ) ) );
		$output = array();
		foreach ( $authors as $key => $author ) {
			$output[] = array(
				'value' => $author->ID,
				'label' => $author->display_name,
			);
		}
		return apply_filters( 'kadence_woo_template_display_authors', $output );
	}
	/**
	 * Get all product taxonomies
	 */
	public function get_product_taxonomies() {
		$output = array();
		$taxonomies = get_object_taxonomies( 'product', 'objects' );
		$taxs = array();
		$taxs_archive = array();
		foreach ( $taxonomies as $term_slug => $term ) {
			if ( ! $term->public || ! $term->show_ui ) {
				continue;
			}
			$taxs[ $term_slug ] = array(
				'name' => $term->name,
				'label' => $term->label,
			);
			$terms = get_terms( $term_slug );
			$term_items = array();
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term_key => $term_item ) {
					$term_items[] = array(
						'value' => $term_item->term_id,
						'label' => $term_item->name,
					);
				}
				$output['product']['terms'][ $term_slug ] = $term_items;
				$output['taxs'][ $term_slug ] = $term_items;
			}
		}
		$output['product']['taxonomy'] = $taxs;
		return apply_filters( 'kadence_woo_template_product_display_taxonomies', $output );
	}

	/**
	 * Register Script for Meta options
	 */
	public function plugin_register() {
		$path = KADENCE_WOO_EXTRAS_URL . 'build/';
		wp_register_script(
			'kadence-wootemplates-meta',
			$path . 'wootemplate.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element' ),
			KADENCE_WOO_EXTRAS_VERSION
		);
	}
	/**
	 * Register Post Meta options
	 */
	public function register_meta() {
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_type',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_show_conditionals',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_show_loop_conditionals',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_all_show',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'boolean',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_hide_conditionals',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_user_conditionals',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_enable_expires',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'boolean',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_expires',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_language',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_preview_post',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => '__return_true',
			)
		);
		register_post_meta(
			self::SLUG,
			'_kad_wootemplate_preview_width',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => '',
				'auth_callback' => '__return_true',
			)
		);
	}

	/**
	 * Registers the block areas post type.
	 *
	 * @since 0.1.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => __( 'Woo Templates', 'kadence-woo-extras' ),
			'singular_name'         => __( 'Woo Template', 'kadence-woo-extras' ),
			'menu_name'             => _x( 'Woo Templates', 'Admin Menu text', 'kadence-woo-extras' ),
			'add_new'               => _x( 'Add New', 'Woo Templates', 'kadence-woo-extras' ),
			'add_new_item'          => __( 'Add New Woo Templates', 'kadence-woo-extras' ),
			'new_item'              => __( 'New Woo Templates', 'kadence-woo-extras' ),
			'edit_item'             => __( 'Edit Woo Templates', 'kadence-woo-extras' ),
			'view_item'             => __( 'View Woo Templates', 'kadence-woo-extras' ),
			'all_items'             => __( 'All Woo Templatess', 'kadence-woo-extras' ),
			'search_items'          => __( 'Search Woo Templatess', 'kadence-woo-extras' ),
			'parent_item_colon'     => __( 'Parent Woo Templates:', 'kadence-woo-extras' ),
			'not_found'             => __( 'No Woo Templatess found.', 'kadence-woo-extras' ),
			'not_found_in_trash'    => __( 'No Woo Templatess found in Trash.', 'kadence-woo-extras' ),
			'archives'              => __( 'Woo Templates archives', 'kadence-woo-extras' ),
			'insert_into_item'      => __( 'Insert into Woo Templates', 'kadence-woo-extras' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Woo Templates', 'kadence-woo-extras' ),
			'filter_items_list'     => __( 'Filter Woo Templatess list', 'kadence-woo-extras' ),
			'items_list_navigation' => __( 'Woo Templatess list navigation', 'kadence-woo-extras' ),
			'items_list'            => __( 'Woo Templatess list', 'kadence-woo-extras' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Woo Templates to control woocommerce content.', 'kadence-woo-extras' ),
			'public'             => false,
			'publicly_queryable' => false,
			'has_archive'        => false,
			'exclude_from_search'=> true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'can_export'         => true,
			'show_in_rest'       => true,
			'rewrite'            => false,
			'rest_base'          => 'kadence_wootemplate',
			'capability_type'    => array( 'kadence_wootemplate', 'kadence_wootemplates' ),
			'map_meta_cap'       => true,
			'supports'           => array(
				'title',
				'editor',
				'custom-fields',
				'revisions',
			),
		);

		register_post_type( self::SLUG, $args );
	}

	/**
	 * Filters the capabilities of a user to conditionally grant them capabilities for managing Elements.
	 *
	 * Any user who can 'edit_theme_options' will have access to manage Elements.
	 *
	 * @param array $allcaps A user's capabilities.
	 * @return array Filtered $allcaps.
	 */
	public function filter_post_type_user_caps( $allcaps ) {
		if ( isset( $allcaps['edit_theme_options'] ) ) {
			$allcaps['edit_kadence_wootemplates']             = $allcaps['edit_theme_options'];
			$allcaps['edit_others_kadence_wootemplates']      = $allcaps['edit_theme_options'];
			$allcaps['edit_published_kadence_wootemplates']   = $allcaps['edit_theme_options'];
			$allcaps['edit_private_kadence_wootemplates']     = $allcaps['edit_theme_options'];
			$allcaps['delete_kadence_wootemplates']           = $allcaps['edit_theme_options'];
			$allcaps['delete_others_kadence_wootemplates']    = $allcaps['edit_theme_options'];
			$allcaps['delete_published_kadence_wootemplates'] = $allcaps['edit_theme_options'];
			$allcaps['delete_private_kadence_wootemplates']   = $allcaps['edit_theme_options'];
			$allcaps['publish_kadence_wootemplates']          = $allcaps['edit_theme_options'];
			$allcaps['read_private_kadence_wootemplates']     = $allcaps['edit_theme_options'];
		}

		return $allcaps;
	}

	/**
	 * Filters the block area post type columns in the admin list table.
	 *
	 * @since 0.1.0
	 *
	 * @param array $columns Columns to display.
	 * @return array Filtered $columns.
	 */
	private function filter_post_type_columns( array $columns ) : array {

		$add = array(
			'type'            => esc_html__( 'Type', 'kadence-woo-extras' ),
			'display'         => esc_html__( 'Display On', 'kadence-woo-extras' ),
			'user_visibility' => esc_html__( 'Visible To', 'kadence-woo-extras' ),
			'status'          => esc_html__( 'Status', 'kadence-woo-extras' ),
		);

		$new_columns = array();
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' == $key ) {
				$new_columns = array_merge( $new_columns, $add );
			}
		}

		return $new_columns;
	}
	/**
	 * Finds the label in an array.
	 *
	 * @param array  $data the array data.
	 * @param string $value the value field.
	 */
	public function get_item_label_in_array( $data, $value, $sub_key = false ) {
		if ( $sub_key ) {
			foreach ( $data as $key => $item ) {
				foreach ( $item['options'] as $sub_key => $sub_item ) {
					if ( $sub_item['value'] === $value ) {
						return $sub_item['label'];
					}
				}
			}
		} else {
			foreach ( $data as $key => $item ) {
				if ( isset( $item['value'] ) ) {
					if ( $item['value'] === $value ) {
						return $item['label'];
					}
				}
				// foreach ( $item['options'] as $sub_key => $sub_item ) {
				// 	if ( $sub_item['value'] === $value ) {
				// 		return $sub_item['label'];
				// 	}
				// }
			}
		}
		return false;
	}

	/**
	 * Renders column content for the block area post type list table.
	 *
	 * @param string $column_name Column name to render.
	 * @param int    $post_id     Post ID.
	 */
	private function render_post_type_column( string $column_name, int $post_id ) {
		if ( 'status' !== $column_name && 'display' !== $column_name && 'type' !== $column_name && 'user_visibility' !== $column_name ) {
			return;
		}
		$post = get_post( $post_id );
		$meta = $this->get_post_meta_array( $post );
		if ( 'status' === $column_name ) {
			if ( 'publish' === $post->post_status || 'draft' === $post->post_status ) {
				$title = ( 'publish' === $post->post_status ? __( 'Published', 'kadence-woo-extras' ) : __( 'Draft', 'kadence-woo-extras' ) );
				echo '<button class="kadence-status-toggle kadence-wootemplate-status kadence-status-' . esc_attr( $post->post_status ) . '" data-post-status="' . esc_attr( $post->post_status ) . '" data-post-id="' . esc_attr( $post_id ) . '"><span class="kadence-toggle"></span><span class="kadence-status-label">' . $title . '</span><span class="spinner"></span></button>';
			} else {
				echo '<div class="kadence-static-status-toggle">' . esc_html( $post->post_status ) . '</div>';
			}
		}
		if ( 'type' === $column_name ) {
			$type = ( isset( $meta['type'] ) && ! empty( $meta['type'] ) ? $meta['type'] : esc_html__( 'Single Product', 'kadence-woo-extras' ) );
			echo '<span class="woo-template-type woo-template-type-' . esc_attr( $type ) . '">';
			echo esc_html( $type );
			echo '</span>';
		}
		if ( 'display' === $column_name ) {
			if ( isset( $meta ) && isset( $meta['show'] ) && is_array( $meta['show'] ) && ! empty( $meta['show'] ) ) {
				foreach ( $meta['show'] as $key => $rule ) {
					$rule_split = explode( '|', $rule['rule'], 2 );
					if ( in_array( $rule_split[0], array( 'singular', 'tax_archive' ) ) ) {
						if ( ! isset( $rule['select'] ) || isset( $rule['select'] ) && 'all' === $rule['select'] ) {
							echo esc_html( 'All ' . $rule['rule'] );
							echo '<br>';
						} elseif ( isset( $rule['select'] ) && 'author' === $rule['select'] ) {
							$label = $this->get_item_label_in_array( $this->get_all_display_options(), $rule['rule'] );
							echo esc_html( $label . ' Author: ' );
							if ( isset( $rule['subRule'] ) ) {
								$user = get_userdata( $rule['subRule'] );
								if ( isset( $user ) && is_object( $user ) && $user->display_name ) {
									echo esc_html( $user->display_name );
								}
							}
							echo '<br>';
						} elseif ( isset( $rule['select'] ) && 'tax' === $rule['select'] ) {
							$label = $this->get_item_label_in_array( $this->get_all_display_options(), $rule['rule'] );
							echo esc_html( $label . ' Terms: ' );
							if ( isset( $rule['subRule'] ) && isset( $rule['subSelection'] ) && is_array( $rule['subSelection'] ) ) {
								foreach ( $rule['subSelection'] as $sub_key => $selection ) {
									echo esc_html( $selection['value'] . ', ' );
								}
							}
							echo '<br>';
						} elseif ( isset( $rule['select'] ) && 'ids' === $rule['select'] ) {
							$label = $this->get_item_label_in_array( $this->get_all_display_options(), $rule['rule'] );
							echo esc_html( $label . ' Items: ' );
							if ( isset( $rule['ids'] ) && is_array( $rule['ids'] ) ) {
								foreach ( $rule['ids'] as $sub_key => $sub_id ) {
									echo esc_html( $sub_id . ', ' );
								}
							}
							echo '<br>';
						} elseif ( isset( $rule['select'] ) && 'individual' === $rule['select'] ) {
							$label = $this->get_item_label_in_array( $this->get_all_display_options(), $rule['rule'] );
							echo esc_html( $label . ' Terms: ' );
							if ( isset( $rule['subSelection'] ) && is_array( $rule['subSelection'] ) ) {
								$show_taxs   = array();
								foreach ( $rule['subSelection'] as $sub_key => $selection ) {
									if ( isset( $selection['value'] ) && ! empty( $selection['value'] ) ) {
										$show_taxs[] = $selection['value'];
									}
								}
								echo implode( ', ', $show_taxs );
							}
							echo '<br>';
						}
					} else {
						$label = $this->get_item_label_in_array( $this->get_all_display_options(), $rule['rule'] );
						echo esc_html( $label ) . '<br>';
					}
				}
			} else {
				echo esc_html__( '[UNSET]', 'kadence-woo-extras' );
			}
		}
		if ( 'user_visibility' === $column_name ) {
			if ( isset( $meta ) && isset( $meta['user'] ) && is_array( $meta['user'] ) && ! empty( $meta['user'] ) ) {
				$show_roles = array();
				foreach ( $meta['user'] as $key => $user_rule ) {
					if ( isset( $user_rule['role'] ) && ! empty( $user_rule['role'] ) ) {
						$show_roles[] = $this->get_item_label_in_array( $this->get_user_options(), $user_rule['role'], true );
					}
				}
				if ( count( $show_roles ) !== 0 ) {
					echo esc_html__( 'Visible to:', 'kadence-woo-extras' );
					echo '<br>';
					echo implode( ', ', $show_roles );
				} else {
					echo esc_html__( 'All Users', 'kadence-woo-extras' );
				}
			} else {
				echo esc_html__( 'All Users', 'kadence-woo-extras' );
			}
		}
	}
	/**
	 * Renders the woo template single template on the front end.
	 *
	 * @param array $layout the layout array.
	 */
	public function wootemplates_single_layout( $layout ) {
		global $post;
		if ( is_singular( self::SLUG ) || ( is_admin() && is_object( $post ) && self::SLUG === $post->post_type ) ) {
			$layout = wp_parse_args(
				array(
					'layout'           => 'fullwidth',
					'boxed'            => 'unboxed',
					'feature'          => 'hide',
					'feature_position' => 'above',
					'comments'         => 'hide',
					'navigation'       => 'hide',
					'title'            => 'hide',
					'transparent'      => 'disable',
					'sidebar'          => 'disable',
					'vpadding'         => 'hide',
					'footer'           => 'disable',
					'header'           => 'disable',
					'content'          => 'enable',
				),
				$layout
			);
		}

		return $layout;
	}
}
Kadence_Woo_Block_Editor_Templates::get_instance();
