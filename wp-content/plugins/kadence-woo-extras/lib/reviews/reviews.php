<?php
/**
 * Class to build advanced Reviews.
 * Credit to free yith advanced reviews, lots of code based from that.
 *
 * @package Kadence Shop Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Advanced Reviews class.
 */
class Kadence_Advanced_Reviews {
	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

	private static $review_query = null;
	private static $review_children_query = null;
	private static $review_avg_query = null;
	private $children_reviews_container = array();
	public $review_meta_rating = '_kt_review_rating';
	public $review_meta_product_id = '_kt_review_product_id';
	public $review_meta_imported = '_kt_review_imported';
	public $review_meta_approved = '_kt_review_approved';
	public $review_meta_comment_id = '_kt_review_comment_id';
	public $review_meta_upvotes_count = '_kt_review_upvotes';
	public $review_meta_downvotes_count = '_kt_review_downvotes';
	public $review_meta_votes = '_kt_review_votes';
	public $review_meta_voters = '_kt_review_voters';
	public $review_meta_voter_guests = '_kt_review_voter_guests';
	public $review_meta_featured = '_kt_review_is_featured';
	public $review_meta_stop_reply = '_kt_review_is_reply_blocked';
	public $review_meta_review_user_id = '_kt_review_user_id';
	public $review_meta_review_author = '_kt_review_author';
	public $review_meta_review_author_email = '_kt_review_author_email';
	public $review_meta_review_author_url = '_kt_review_author_url';
	public $review_meta_review_author_ip = '_kt_review_author_IP';
	public $review_meta_review_consent = '_kt_review_consent';
	public $review_meta_review_type = '_kt_review_type';
	public $review_meta_review_parent = '_kt_review_parent';
	public $review_meta_review_comment_parent = '_kt_review_comment_parent';

	/**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Class Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'kt_add_menu_item' ) );
		add_action( 'init', array( $this, 'kt_woo_reviews_post' ), 10 );
		if ( is_admin() ) {
			add_action( 'do_meta_boxes', array( $this, 'kt_woo_remove_revolution_slider_meta_boxes' ), 10 );
		}
		add_action( 'admin_init', array( $this, 'kt_check_import_actions' ) );
		add_action( 'wp_ajax_kt_review_convert', array( $this, 'convert_reviews_callback' ) );

		add_action( 'wp_ajax_nopriv_kt_review_vote', array( $this, 'kt_vote_callback_guest' ) );
		add_action( 'wp_ajax_kt_review_vote', array( $this, 'kt_vote_callback' ) );

		add_action( 'wp_ajax_kt_review_readmore', array( $this, 'kt_review_readmore' ) );
		add_action( 'wp_ajax_nopriv_kt_review_readmore', array( $this, 'kt_review_readmore' ) );

		add_filter( 'comments_template', array( $this, 'kt_reviews_template' ), 99 );
		add_action( 'kt_before_review_list', array( $this, 'kt_reviews_summary' ) );
		add_filter( 'wc_get_template', array( $this, 'kt_wc_get_template' ), 99, 5 );
		add_action( 'comment_post', array( $this, 'kt_submit_review' ) );
		add_filter( 'kt_reviews_review_content', 'wpautop' );
		add_filter( 'cmb2_admin_init', array( $this, 'add_metaboxes' ) );
		add_filter( 'woocommerce_product_tabs', array( $this, 'kt_update_tab_reviews_count' ), 20 );
		// add_filter( 'woocommerce_product_get_rating_html', array( $this, 'kt_get_product_rating_html' ), 99, 2 );

		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		if ( isset( $shopkit_settings['kt_reviews_multi'] ) && 1 == $shopkit_settings['kt_reviews_multi'] ) {
			add_filter( 'kadence_woo_advanced_review_arg_default', array( $this, 'kt_add_polylang_support' ), 99, 2 );
			add_filter( 'kadence_woo_advanced_review_arg_average_query', array( $this, 'kt_add_polylang_average_support' ), 99, 2 );
		}

		add_action( 'admin_action_approve-review', array( $this, 'kt_update_review_attributes' ) );
		add_action( 'admin_action_unapprove-review', array( $this, 'kt_update_review_attributes' ) );
		add_action( 'admin_action_untrash-review', array( $this, 'kt_update_review_attributes' ) );
		add_action( 'admin_action_trash-review', array( $this, 'kt_update_review_attributes' ) );

		add_filter( 'post_class', array( $this, 'kt_add_review_table_class' ), 10, 3 );
		add_filter( 'kt_reviews_row_actions', array( $this, 'kt_add_review_actions' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_import_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'script_enqueue' ), 80 );
	}
	/**
	 * Add Frontend Scripts.
	 */
	public function script_enqueue() {
		global $post;

		if ( is_singular( 'product' ) || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) {
			wp_enqueue_style( 'kadence_reviews_css', KADENCE_WOO_EXTRAS_URL . 'lib/reviews/css/kt_woo_reviews.css', false, KADENCE_WOO_EXTRAS_VERSION );
			global $product;
			wp_register_script( 'kt_woo_reviews', KADENCE_WOO_EXTRAS_URL . 'lib/reviews/js/min/kt_woo_reviews-min.js', array( 'jquery' ), KADENCE_WOO_EXTRAS_VERSION, true );
			$translation_array = array(
				'is_user_logged_in' => is_user_logged_in(),
				'user_id' => get_current_user_id(),
				'product_id' => get_the_ID(),
				'nonce' => wp_create_nonce( 'kt_reviews' ),
				'error' => __( 'An error occurred, please try again later', 'kadence-woo-extras' ),
				'nomoreviews' => __( 'There are no more reviews', 'kadence-woo-extras' ),
				'required_consent_text' => __( 'Please check required consent checkbox', 'kadence-woo-extras' ),
			);
			wp_localize_script( 'kt_woo_reviews', 'kt_product_reviews', $translation_array );
			wp_enqueue_script( 'kt_woo_reviews' );

		}
	}

	public function kt_add_menu_item() {

		$args = apply_filters(
			'kt_wc_product',
			array(
				'page_title' => __( 'Reviews', 'kadence-woo-extras' ),
				'menu_title' => __( 'Reviews', 'kadence-woo-extras' ),
				'capability' => 'edit_products',
				'menu_slug'  => __( 'kt_reviews', 'kadence-woo-extras' ),
				'function'   => array( $this, 'show_reviews_table' ),
				'icon'       => 'dashicons-star-filled',
				'position'   => '26.3',
			)
		);

		extract( $args );
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon, $position );
	}
	public function show_reviews_table() {
		if ( ! class_exists( 'WP_Posts_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php' );
		}

		require_once( KADENCE_WOO_EXTRAS_PATH . '/lib/reviews/kt-admin-reviews-list.php' );

		$product_reviews = new KT_Admin_Reviews_List();
		$product_reviews->kt_prepare_items();

		// wp_enqueue_script( 'admin-comments' );
		// enqueue_comment_hotkeys_js();

		wc_get_template( 'kt-admin-reviews.php', array( 'product_reviews' => $product_reviews ), KADENCE_WOO_EXTRAS_PATH . 'lib/reviews/', KADENCE_WOO_EXTRAS_PATH . '/lib/reviews/' );
		// wp_comment_reply( '-1', true, 'detail' );
		// wp_comment_trashnotice();
	}

	// Add Review post type
	public function kt_woo_reviews_post() {
			$reviewlabels = array(
			'name'               => __( 'Reviews', 'kadence-woo-extras' ),
			'singular_name'      => __( 'Review', 'kadence-woo-extras' ),
			'add_new'            => __( 'Add Review', 'kadence-woo-extras' ),
			'add_new_item'       => __( 'Add New Review', 'kadence-woo-extras' ),
			'edit_item'          => __( 'Edit Review', 'kadence-woo-extras' ),
			'new_item'           => __( 'New Review', 'kadence-woo-extras' ),
			'all_items'          => __( 'All Reviews', 'kadence-woo-extras' ),
			'view_item'          => __( 'View Review', 'kadence-woo-extras' ),
			'search_items'       => __( 'Search Review', 'kadence-woo-extras' ),
			'not_found'          => __( 'No Review found', 'kadence-woo-extras' ),
			'not_found_in_trash' => __( 'No Reviews found in Trash', 'kadence-woo-extras' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Reviews', 'kadence-woo-extras' ),
			);

			$reviewargs = array(
				'labels' => $reviewlabels,
				'public' => true,
				'publicly_queryable' => false,
				'show_ui' => true,
				'exclude_from_search' => true,
				'show_in_menu' => false,
				'query_var' => false,
				'rewrite'  => false,
				'has_archive' => false,
				'capability_type' => 'page',
				'hierarchical' => false,
				'menu_position' => null,
				'menu_icon' => 'dashicons-star-filled',
				'supports' => array( 'title', 'editor' ),
			);

			register_post_type( 'kt_reviews', $reviewargs );
	}
	// Remove annoying revslider from it.
	public function kt_woo_remove_revolution_slider_meta_boxes() {
		remove_meta_box( 'mymetabox_revslider_0', 'kt_reviews', 'normal' );
	}

	/**
	 * Create new Review post type when a comment is saved to database
	*/
	public function kt_submit_review( $comment_id ) {
		if ( ! isset( $_POST ) ) {
			return;
		}
		$comment = get_comment( $comment_id );

		if ( get_post_type( $comment->comment_post_ID ) != 'product' ) {
			return;
		}

		$review_title = isset( $_POST['title'] ) ? wp_strip_all_tags( $_POST['title'] ) : '';

		$review_consent = isset( $_POST['consent'] ) ? wp_strip_all_tags( $_POST['consent'] ) : '';

		$post_parent = apply_filters( 'kt_reviews_post_parent', $_POST['comment_parent'] );

		// Create post object
		$review_post = array(
			'post_author'           => $comment->user_id,
			'post_title'            => $review_title,
			'post_content'          => $comment->comment_content,
			'post_status'           => 'publish',
			'post_author'           => get_current_user_id(),
			'post_type'             => 'kt_reviews',
			'post_parent'           => $post_parent,
			'review_comment_id'     => $comment_id,
			'review_user_id'        => $comment->user_id,
			'review_rating'         => ( isset( $_POST['rating'] ) ? $_POST['rating'] : 0 ),
			'review_product_id'     => $comment->comment_post_ID,
			'review_approved'       => $comment->comment_approved,
			'review_author'         => $comment->comment_author,
			'review_author_email'   => $comment->comment_author_email,
			'review_author_url'     => $comment->comment_author_url,
			'review_author_IP'      => $comment->comment_author_IP,
			'review_consent'        => $review_consent,
			'review_type'           => $comment->comment_type,
			'review_parent'         => '',
			'review_comment_parent' => $comment->comment_parent,
		);

		$review_id = $this->kt_add_review( $review_post );
		update_comment_meta( $comment->comment_ID, $this->review_meta_imported, 1 );

	}

	public function kt_add_review( $args ) {
		// Create post object.
		$defaults = array(
			'post_title'                 => '',
			'post_content'               => '',
			'post_status'                => 'publish',
			'post_author'                => 0,
			'post_type'                  => 'kt_reviews',
			'post_parent'                => 0,
			'review_user_id'             => 0,
			'review_approved'            => 1,
			'review_rating'              => 0,
			'review_product_id'          => 0,
			'review_comment_id'          => 0,
			'review_upvotes'             => 0,
			'review_downvotes'           => 0,
			'review_votes'               => 0,
			'review_voters'              => array(),
			'review_voter_guests'        => array(),
			'review_is_featured'         => 0,
			'review_is_reply_blocked'    => 1,
			'review_author'              => '',
			'review_author_email'        => '',
			'review_author_url'          => '',
			'review_author_IP'           => '',
			'review_consent'             => '',
			'review_type'                => '',
			'review_parent'              => '',
			'review_comment_parent'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$review_id = wp_insert_post( $args );

		if ( 0 != $args['post_parent'] ) {
			update_post_meta( $review_id, $this->review_meta_rating, 0 );
		} else {
			update_post_meta( $review_id, $this->review_meta_rating, $args['review_rating'] );
		}

		update_post_meta( $review_id, $this->review_meta_rating, $args['review_rating'] );
		update_post_meta( $review_id, $this->review_meta_approved, $args['review_approved'] );
		update_post_meta( $review_id, $this->review_meta_product_id, $args['review_product_id'] );
		update_post_meta( $review_id, $this->review_meta_comment_id, $args['review_comment_id'] );

		update_post_meta( $review_id, $this->review_meta_upvotes_count, $args['review_upvotes'] );
		update_post_meta( $review_id, $this->review_meta_downvotes_count, $args['review_downvotes'] );
		update_post_meta( $review_id, $this->review_meta_votes, $args['review_votes'] );
		update_post_meta( $review_id, $this->review_meta_voters, $args['review_voters'] );

		update_post_meta( $review_id, $this->review_meta_featured, $args['review_is_featured'] );
		update_post_meta( $review_id, $this->review_meta_stop_reply, $args['review_is_reply_blocked'] );

		update_post_meta( $review_id, $this->review_meta_review_user_id, $args['review_user_id'] );
		update_post_meta( $review_id, $this->review_meta_review_author, $args['review_author'] );
		update_post_meta( $review_id, $this->review_meta_review_author_email, $args['review_author_email'] );
		update_post_meta( $review_id, $this->review_meta_review_author_url, $args['review_author_url'] );
		update_post_meta( $review_id, $this->review_meta_review_author_ip, $args['review_author_IP'] );
		update_post_meta( $review_id, $this->review_meta_review_consent, $args['review_consent'] );
		update_post_meta( $review_id, $this->review_meta_review_type, $args['review_type'] );
		update_post_meta( $review_id, $this->review_meta_review_parent, $args['review_parent'] );
		update_post_meta( $review_id, $this->review_meta_review_comment_parent, $args['review_comment_parent'] );
		return $review_id;
	}
	/**
	 * Ajax Call build post loop
	 */
	public function kt_review_readmore() {
		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'kt_reviews' ) ) {
			return false;
		}
		if ( ! isset( $_POST['args'] ) ) {
			return false;
		} else {
			$args = wp_unslash( $_POST['args'] );
		}
		if ( ! isset( $_POST['product_id'] ) ) {
			return false;
		} else {
			$product_id = absint( wp_unslash( $_POST['product_id'] ) );
		}
		if ( ! isset( $_POST['offset'] ) ) {
			return false;
		} else {
			$offset = absint( wp_unslash( $_POST['offset'] ) );
		}
		$args['offset'] = $offset;
		ob_start();
		$this->kt_reviews_list( $product_id, $args );
		$output = ob_get_contents();
		ob_end_clean();

		wp_send_json( array( 'value' => $output ) );
	}

	public function kt_reviews_template( $template ) {

		if ( get_post_type() === 'product' ) {
			return wc_locate_template( 'kt-product-reviews.php', '', KADENCE_WOO_EXTRAS_PATH . 'lib/reviews/' );
		}

		return $template;
	}
	/**
	 * Default Query Args
	 */
	public function kt_review_query_args( $product_id, $top_level_only = true ) {
		global $kt_woo_extras;
		if ( isset( $kt_woo_extras['kt_reviews_order'] ) && 'desc' == $kt_woo_extras['kt_reviews_order'] ) {
			$orderby = 'post_date';
			$order   = 'DESC';
		} else if ( isset( $kt_woo_extras['kt_reviews_order'] ) && 'asc' == $kt_woo_extras['kt_reviews_order'] ) {
			$orderby = 'post_date';
			$order   = 'ASC';
		} else {
			$orderby = 'meta_value_num post_date';
			$order   = 'DESC';
		}

		if ( $top_level_only ) {
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => $this->review_meta_product_id,
					'value'   => $product_id,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'key'     => $this->review_meta_approved,
					'value'   => 1,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'key'     => $this->review_meta_review_type,
					'value'   => 'comment',
					'compare' => '!=',
					'type'    => 'char',
				),
			);
		} else {
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => $this->review_meta_product_id,
					'value'   => $product_id,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'key'     => $this->review_meta_approved,
					'value'   => 1,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'key'     => $this->review_meta_review_type,
					'value'   => 'comment',
					'compare' => '=',
					'type'    => 'char',
				),
			);
		}

		$args = array(
			'numberposts' => -1,
			'offset'      => 0,
			'lang'        => '',
			'orderby'     => $orderby,
			'meta_key'    => '_kt_review_votes',
			'order'       => $order,
			'post_type'   => 'kt_reviews',
			'post_parent' => '0',
			'post_status' => 'publish',
			'meta_query'  => $meta_query
		);
		return $args;
	}

	//build out a container that has reviews linked to their parnet id
	public function build_children_reviews_container( $args_children ) {
		//fill up the children container for these args for use later when displaying the top level reviews
		$children_reviews_container  = array();

		$reviews_children = get_posts( $args_children );
		if ( $reviews_children ) {
			foreach ( $reviews_children as $review_child ) {
				$parent_comment_id = $this->kt_get_meta_review_comment_parent( $review_child->ID );
				if ( ! empty( $parent_comment_id ) ) {
					$children_reviews_container[ $parent_comment_id ][] = $review_child;
				}
			}
		}

		return $children_reviews_container;
	}

	//Get reviews for a product. Top level reviews (not replies) are the main array returned
	//That's what should determine the overall review count, how many are displayed in pagination, etc
	//Children reviews (replies) are populated into a keyed array attached to $this
	//This array will get used later if a top level comment needs to display it's children
	public function kt_get_product_reviews( $product_id = null, $args_in = null, $args_in_children = null ) {
		$defaults_top_level = apply_filters( 'kadence_woo_advanced_review_arg_default', $this->kt_review_query_args( $product_id ), $product_id );
		$defaults_children = apply_filters( 'kadence_woo_advanced_review_arg_default_children', $this->kt_review_query_args( $product_id, false ), $product_id );
		$args_top_level = wp_parse_args( $args_in, $defaults_top_level );
		$args_children = wp_parse_args( $args_in_children, $defaults_children );

		if ( is_null( $args_in ) && ! is_null( $product_id ) ) {
			if ( ! is_null( self::$review_query ) && isset( self::$review_query[ $product_id ] ) && ! empty( self::$review_query[ $product_id ] ) ) {
				if ( ! is_null( self::$review_children_query ) && isset( self::$review_children_query[ $product_id ] ) && ! empty( self::$review_children_query[ $product_id ] ) ) {
					$this->children_reviews_container = self::$review_children_query[ $product_id ];
				}
				return self::$review_query[ $product_id ];
			}
			if ( is_null( self::$review_query ) ) {
				self::$review_query = array();
			}
			self::$review_children_query[ $product_id ] = $this->build_children_reviews_container( $args_children );
			self::$review_query[ $product_id ] = get_posts( $args_top_level );

			$this->children_reviews_container = self::$review_children_query[ $product_id ];
			return self::$review_query[ $product_id ];
		}

		if ( is_null( $product_id ) ) {
			unset( $args_top_level['meta_query'] );
			unset( $args_children['meta_query'] );
		}

		$this->children_reviews_container = $this->build_children_reviews_container( $args_children );
		return get_posts( $args_top_level );
	}

	public function kt_check_unapproved( $product_id = null, $args = null ) {
		$defaults = apply_filters( 'kadence_woo_advanced_review_arg_unapproved', $this->kt_review_query_args( $product_id ), $product_id );
		$args = wp_parse_args( $args, $defaults );

		if ( is_null( $product_id ) ) {
			unset( $args['meta_query'] );
		}

		return get_posts( $args );
	}
	public function kt_add_polylang_support( $args, $product_id ) {
		if ( function_exists( 'pll_get_post_translations' ) && ! empty( $product_id ) ) {
			$get_all_ids = pll_get_post_translations( $product_id );
			$ids = array();
			foreach ( $get_all_ids as $key => $each_id ) {
				$ids[] = $each_id;
			}
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => $this->review_meta_approved,
					'value'   => 1,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'key'     => $this->review_meta_product_id,
					'value'   => $ids,
					'compare' => 'IN',
					'type'    => 'numeric',
				),
			);
		} else if ( class_exists( 'SitePress' ) && ! empty( $product_id ) ) {
			$trid = apply_filters( 'wpml_element_trid', null, $product_id, 'post_product' );
			$get_all_ids = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_product' );
			$ids = array();
			foreach ( $get_all_ids as $key => $each_id ) {
				$ids[] = $each_id->element_id;
			}
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => $this->review_meta_approved,
					'value'   => 1,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'key'     => $this->review_meta_product_id,
					'value'   => $ids,
					'compare' => 'IN',
					'type'    => 'numeric',
				),
			);
		}
		return $args;
	}
	public function kt_add_polylang_average_support( $query, $product_id ) {
		if ( function_exists( 'pll_get_post_translations' ) && ! empty( $product_id ) ) {

			$get_all_ids = pll_get_post_translations( $product_id );
			$ids = array();
			foreach ( $get_all_ids as $key => $each_id ) {
				$ids[] = $each_id;
			}
			$ids_format_string = rtrim( str_repeat( '%d,', count( $ids ) ), ',' );
			global $wpdb;
			$query = $wpdb->prepare(
				"
			select avg(meta_value)
			from {$wpdb->prefix}postmeta pm
			where meta_key = '{$this->review_meta_rating}' and post_id in
				(select post_id from {$wpdb->prefix}postmeta where meta_key = '{$this->review_meta_product_id}' and meta_value IN ({$ids_format_string}) and post_id IN
					(select post_id from {$wpdb->prefix}postmeta where meta_key = '{$this->review_meta_approved}' and meta_value = 1 and post_id IN (select post_id from {$wpdb->prefix}postmeta where meta_key = '{$this->review_meta_rating}' and meta_value > 0)))",
				$ids
			);
		} else if ( class_exists( 'SitePress' ) && ! empty( $product_id ) ) {
			$trid = apply_filters( 'wpml_element_trid', null, $product_id, 'post_product' );
			$get_all_ids = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_product' );
			$ids = array();
			foreach ( $get_all_ids as $key => $each_id ) {
				$ids[] = $each_id->element_id;
			}
			$ids_format_string = rtrim( str_repeat( '%d,', count( $ids ) ), ',' );
			global $wpdb;
			$query = $wpdb->prepare(
				"
			select avg(meta_value)
			from {$wpdb->prefix}postmeta pm
			where meta_key = '{$this->review_meta_rating}' and post_id in
				(select post_id from {$wpdb->prefix}postmeta where meta_key = '{$this->review_meta_product_id}' and meta_value IN ({$ids_format_string}) and post_id IN
					(select post_id from {$wpdb->prefix}postmeta where meta_key = '{$this->review_meta_approved}' and meta_value = 1 and post_id IN (select post_id from {$wpdb->prefix}postmeta where meta_key = '{$this->review_meta_rating}' and meta_value > 0)))",
				$ids
			);
		}

		return $query;
	}

	public function kt_get_product_reviews_by_rating( $product_id, $rating = 0 ) {
		$args = $this->kt_review_query_args( $product_id );
		if ( $rating > 0 ) {
			$args['meta_query'][] = array(
				'key'     => $this->review_meta_rating,
				'value'   => $rating,
				'compare' => '=',
				'type'    => 'numeric',
			);
		}

		return $this->kt_get_product_reviews( $product_id, $args );
	}

	//display a list of reviews. If they have any children: nest, recurse, and display children.
	public function kt_reviews_list_display( $reviews, $show_children = true ) {
		foreach ( $reviews as $review ) {
			wc_get_template( 'kt-single-review.php', array( 'review' => $review ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/reviews/' );

			if ( $show_children ) {
				$review_comment_id = $this->kt_get_meta_comment_id( $review->ID );

				if ( ! empty( $this->children_reviews_container[ $review_comment_id ] ) ) {
					echo '<ul class="children">';
					$this->kt_reviews_list_display( $this->children_reviews_container[ $review_comment_id ] );
					echo '</ul>';
				}
			}
		}
	}

	public function kt_reviews_list( $product_id, $args = null, $show_children = true ) {
		//kt_get_product_reviews gets top level reviews and populates children
		//to kick things off we only need to loop over the top level reviews. kt_reviews_list_display will recurse through children

		$top_level_reviews = $this->kt_get_product_reviews( $product_id, $args );
		$this->kt_reviews_list_display( $top_level_reviews, $show_children );
	}

	public function kt_get_meta_rating( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_rating, true );
	}
	public function kt_get_meta_approved( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_approved, true );
	}
	public function kt_get_meta_product_id( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_product_id, true );
	}
	public function kt_get_meta_featured( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_featured, true );
	}
	public function kt_get_meta_votes( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_votes, true );
	}
	public function kt_get_meta_upvotes_count( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_upvotes_count, true );
	}
	public function kt_get_meta_downvotes_count( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_downvotes_count, true );
	}
	public function kt_get_meta_comment_id( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_comment_id, true );
	}
	public function kt_get_meta_review_type( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_review_type, true );
	}
	public function kt_get_meta_review_parent( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_review_parent, true );
	}
	public function kt_get_meta_review_comment_parent( $review_id ) {
		return get_post_meta( $review_id, $this->review_meta_review_comment_parent, true );
	}
	function kt_get_meta_author( $review_id ) {
		return array(
			'review_user_id'      => get_post_meta( $review_id, $this->review_meta_review_user_id, true ),
			'review_author'       => get_post_meta( $review_id, $this->review_meta_review_author, true ),
			'review_author_email' => get_post_meta( $review_id, $this->review_meta_review_author_email, true ),
			'review_author_url'   => get_post_meta( $review_id, $this->review_meta_review_author_url, true ),
			'review_author_IP'    => get_post_meta( $review_id, $this->review_meta_review_author_ip, true ),
		);
	}
	public function kt_wc_get_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( 'single-product/rating.php' != $template_name ) {
			return $located;
		}

		$located = wc_locate_template( 'kt-rating.php', $template_path, $default_path );

		if ( file_exists( $located ) ) {
			return $located;
		}

		return KADENCE_WOO_EXTRAS_PATH . 'lib/reviews/kt-rating.php';
	}
	public function kt_get_average_rating( $product_id, $num = 2 ) {
		if ( ! is_null( self::$review_avg_query ) && isset( self::$review_avg_query[ $product_id ] ) && ! empty( self::$review_avg_query[ $product_id ] ) ) {
			return self::$review_avg_query[ $product_id ];
		}
		global $wpdb;
		$query = $wpdb->prepare(
			"
			select avg(meta_value)
			from {$wpdb->prefix}postmeta pm
			where meta_key = '{$this->review_meta_rating}' and post_id in
				(select post_id from {$wpdb->prefix}postmeta where meta_key = '{$this->review_meta_product_id}' and meta_value = %d and post_id IN
					(select post_id from {$wpdb->prefix}postmeta where meta_key = '{$this->review_meta_approved}' and meta_value = 1 and post_id IN (select post_id from {$wpdb->prefix}postmeta where meta_key = '{$this->review_meta_rating}' and meta_value > 0)))",
			$product_id
		);

		$query = apply_filters( 'kadence_woo_advanced_review_arg_average_query', $query, $product_id );

		$count = $wpdb->get_var( $query );

		if ( is_null( self::$review_avg_query ) ) {
			self::$review_avg_query = array();
		}
		self::$review_avg_query[ $product_id ] = ! empty( $count ) ? number_format( $count, $num ) : 0;

		return self::$review_avg_query[ $product_id ];

	}

	public function kt_update_tab_reviews_count( $tabs ) {
		global $product;

		if ( isset( $tabs['reviews'] ) ) {
			$tabs['reviews']['title'] = sprintf( __( 'Reviews(%d)', 'kadence-woo-extras' ), count( $this->kt_get_product_reviews( $product->get_id() ) ) );
		}

		return $tabs;
	}
	public function kt_get_product_rating_html( $rating_html, $rating ) {
		if ( is_product() ) {
			global $product;
			$rating_html = '';
			$rating = $this->kt_get_average_rating( $product->get_id() );

			if ( $rating > 0 ) {

				$rating_html = '<div class="star-rating" title="' . sprintf( __( 'Rated %s out test of 5', 'kadence-woo-extras' ), $rating ) . '">';

				$rating_html .= '<span style="width:' . ( ( $rating / 5 ) * 100 ) . '%"><strong class="rating">' . $rating . '</strong> ' . __( 'out of 5', 'kadence-woo-extras' ) . '</span>';

				$rating_html .= '</div>';
			}
		}

		return $rating_html;
	}
	public function kt_add_reviews_average_info( $product ) {

		if ( get_option( 'woocommerce_enable_review_rating' ) === 'no' ) {
			return;
		}

		global $product;

		$average = $this->kt_get_average_rating( $product->get_id() );

		$count = count( $this->kt_get_product_reviews( $product->get_id() ) );

		if ( $count > 0 ) {
			echo '<div class="woocommerce-product-rating">
				<div class="star-rating" title="' . sprintf( __( 'Rated %s out of 5', 'kadence-woo-extras' ), $average ) . '">
					<span  style="width:' . ( ( $average / 5 ) * 100 ) . '%"></span>
				</div>
				<span class="kt_review_count">' . sprintf( '%d %s', $count, _n( ' review', ' reviews', $count, 'kadence-woo-extras' ) ) . '</span><span class="review-rating-value"> ' . esc_html( $average ) . ' ' . __( 'out of 5 stars', 'kadence-woo-extras' ) . '</span>
			</div>';
		}
	}

	public function kt_review_stats( $product ) {
		$args = $this->kt_review_query_args( $product->get_id() );
		$args['meta_query'][] = array(
			'key'     => $this->review_meta_rating,
			'value'   => 0,
			'compare' => '!=',
			'type'    => 'numeric',
		);
		$review_stats = array(
			'1'     => count( $this->kt_get_product_reviews_by_rating( $product->get_id(), 1 ) ),
			'2'     => count( $this->kt_get_product_reviews_by_rating( $product->get_id(), 2 ) ),
			'3'     => count( $this->kt_get_product_reviews_by_rating( $product->get_id(), 3 ) ),
			'4'     => count( $this->kt_get_product_reviews_by_rating( $product->get_id(), 4 ) ),
			'5'     => count( $this->kt_get_product_reviews_by_rating( $product->get_id(), 5 ) ),
			'total' => count( $this->kt_get_product_reviews( $product->get_id(), $args ) ),
		);
		return $review_stats;
	}

	public function kt_reviews_summary( $product ) {
		global $kt_woo_extras;
		if ( isset( $kt_woo_extras['kt_review_overview'] ) && 1 == $kt_woo_extras['kt_review_overview'] ) {
			wc_get_template( 'kt-product-reviews-overview.php', array( 'product'   => $product ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/reviews/' );
		}
	}

	public function kt_update_review_attributes() {
		if ( ! isset( $_GET['review_id'] ) ) {
			return;
		}

		$review_id = $_GET['review_id'];

		$current_filter = current_filter();

		switch ( $current_filter ) {
			case 'admin_action_approve-review':
				$comment_id = get_post_meta( $review_id, $this->review_meta_comment_id, true );
				update_post_meta( $review_id, $this->review_meta_approved, 1 );
				$comment = get_comment( $comment_id );
				if ( $comment != null ) {
					$status = 'approve';
					wp_set_comment_status( $comment_id, $status );
				}

				break;

			case 'admin_action_unapprove-review':
				update_post_meta( $review_id, $this->review_meta_approved, 0 );
				$comment_id = get_post_meta( $review_id, $this->review_meta_comment_id, true );
				$comment = get_comment( $comment_id );
				if ( $comment != null ) {
					$status = 'hold';
					wp_set_comment_status( $comment_id, $status );
				}

				break;

			case 'admin_action_untrash-review':
				$my_post = array(
					'ID'          => $review_id,
					'post_status' => 'publish',
				);

				wp_update_post( $my_post );

				$approve = get_post_meta( $review_id, $this->kt_reviews->review_meta_approved, true );
				$comment_id = get_post_meta( $review_id, $this->kt_reviews->review_meta_comment_id, true );
				$comment = get_comment( $comment_id );
				if ( $comment != null ) {
					if ( $approve == 1 ) {
						$status = 'approve';
					} else {
						$status = 'hold';
					}
					wp_set_comment_status( $comment_id, $status );
				}

				break;
			case 'admin_action_trash-review':
				$my_post = array(
					'ID'          => $review_id,
					'post_status' => 'trash',
				);

				wp_update_post( $my_post );

				$comment_id = get_post_meta( $review_id, $this->review_meta_comment_id, true );
				$comment = get_comment( $comment_id );
				if ( $comment != null ) {
					$status = 'trash';
					wp_set_comment_status( $comment_id, $status );
				}

				break;
			case 'admin_action_delete-review':
				$comment_id = get_post_meta( $review_id, $this->review_meta_comment_id, true );
				$comment = get_comment( $comment_id );
				if ( $comment != null ) {
					wp_delete_comment( $comment_id, true );
				}
				wp_delete_post( $review_id );

				break;
		}

		wp_redirect( esc_url_raw( remove_query_arg( array( 'action', 'action2' ), $_SERVER['HTTP_REFERER'] ) ) );
	}
	public function kt_add_review_table_class( $classes, $class, $post_id ) {

		if ( 'kt_reviews' != get_post_type( $post_id ) ) {
			return $classes;
		}

		unset( $classes['review-unapproved'] );
		unset( $classes['review-approved'] );

		$approve = get_post_meta( $post_id, $this->review_meta_approved, true );

		if ( 1 == $approve ) {
			$classes[] = 'review-approved';
		} elseif ( 0 == $approve ) {
			$classes[] = 'review-unapproved';
		}

		return apply_filters( 'kt_reviews_table_class', $classes, $post_id );
	}
	/**
	 * Add admin styling.
	 */
	public function enqueue_admin_styles_scripts( $hook ) {
		if ( isset( $hook ) && ! empty( $hook ) && 'toplevel_page_kt_reviews' === $hook ) {
			wp_enqueue_style( 'kadence_admin_reviews_css', KADENCE_WOO_EXTRAS_URL . 'lib/reviews/css/kt_woo_admin_reviews.css', false, KADENCE_WOO_EXTRAS_VERSION );
		}
	}
	/**
	 * Set a maximum execution time
	 *
	 * @param $seconds time in seconds
	 */
	private function set_time_limit( $seconds ) {
		// phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		$check_safe_mode = ini_get( 'safe_mode' );
		if ( ( ! $check_safe_mode ) || ( 'OFF' == strtoupper( $check_safe_mode ) ) ) {
			@set_time_limit( $seconds );
		}
	}

	public function review_action_url( $action, $post_id ) {
		return admin_url( "admin.php?action=$action&post_type=kt_reviews&review_id=$post_id" );
	}

	public function untrash_review_url( $review ) {
		return $this->review_action_url( 'untrash-review', $review->ID );
	}
	public function trash_review_url( $review ) {
		return $this->review_action_url( 'trash-review', $review->ID );
	}
	public function delete_review_url( $review ) {
		return $this->review_action_url( 'delete-review', $review->ID );
	}

	public function approve_review_url( $review ) {
		return $this->review_action_url( 'approve-review', $review->ID );
	}

	public function unapprove_review_url( $review ) {
		return $this->review_action_url( 'unapprove-review', $review->ID );
	}

	public function kt_add_review_actions( $actions, $post ) {

		if ( $post->post_type != 'kt_reviews' ) {
			return $actions;
		}
		$approved = $this->kt_get_meta_approved( $post->ID );
		// $comment_id = $this->kt_get_meta_comment_id( $post->ID );
		// $product_id = $this->kt_get_meta_product_id( $post->ID );

		unset( $actions['view'] );
		unset( $actions['inline hide-if-no-js'] );

		if ( ! $approved ) {
			$actions['approve-review'] = '<a href="' . $this->approve_review_url( $post ) . '" title="' . esc_attr( __( 'Approve review', 'kadence-woo-extras' ) ) . '" rel="permalink">' . __( 'Approve', 'kadence-woo-extras' ) . '</a>';
		} elseif ( $approved ) {
			$actions['unapprove-review'] = '<a href="' . $this->unapprove_review_url( $post ) . '" title="' . esc_attr( __( 'Unapprove review', 'kadence-woo-extras' ) ) . '" rel="permalink">' . __( 'Unapprove', 'kadence-woo-extras' ) . '</a>';
		}
		// if ('trash' != $post->post_status) {
		// 	// $actions['reply'] = "<a class='submitreply' title='" . esc_attr__('Reply to this item', 'kadence-woo-extras') . "' href='" . $this->kt_reviews->reply_review_url($post) . "'>" . __('Reply', 'kadence-woo-extras') . "</a>";

		// 	$format = '<button type="button" data-comment-id="%d" data-post-id="%d" data-action="%s" class="%s button-link" aria-expanded="false" aria-label="%s">%s</button>';

		// 	$actions['reply'] = sprintf(
		// 		$format,
		// 		esc_attr( $comment_id ),
		// 		esc_attr( $product_id ),
		// 		'replyto',
		// 		'vim-r comment-inline',
		// 		esc_attr__( 'Reply to this review', 'kadence-woo-extras' ),
		// 		esc_html__( 'Reply', 'kadence-woo-extras' )
		// 	);
		// }

		return apply_filters( 'kt_reviews_review_actions', $actions, $post );
	}
	/**
	 * Read and convert previous reviews into new advanced reviews using custom post type
	 */
	public function kt_import_previous_reviews() {
		global $wpdb;

		$review_converted = 0;
		// Set my lookup args.
		$setup_args = array(
			'post_type' => 'product',
		);
		// Now fetch my reviews.
		$results = get_comments( $setup_args );
		// Bail with a 'no-reviews' string to look for later.
		if ( empty( $results ) ) {
			return $review_converted;
		}
		// Manage parent relationship and update all reviews when import ends
		$review_ids    = array();
		$parent_review = array();

		foreach ( $results as $comment ) {

			// Check if comment_meta exists for previous reviews and is not still imported.
			if ( '1' === get_comment_meta( $comment->comment_ID, $this->review_meta_imported, true ) ) {
				// comment still imported, update author data and comment type / parent.

				// Retrieve review(post) id linked to current comment.
				$args    = array(
					'post_type'  => 'kt_reviews',
					'meta_query' => array(
						array(
							'key'     => $this->review_meta_comment_id,
							'value'   => $comment->comment_ID,
							'compare' => '=',
							'type'    => 'numeric',
						),
					),
				);
				$reviews = get_posts( $args );

				if ( ! empty( $reviews ) ) {
					$review = $reviews[0];

					// Update review meta.
					update_post_meta( $review->ID, $this->review_meta_review_user_id, $comment->user_id );
					update_post_meta( $review->ID, $this->review_meta_review_author, $comment->comment_author );
					update_post_meta( $review->ID, $this->review_meta_review_author_email, $comment->comment_author_email );
					update_post_meta( $review->ID, $this->review_meta_review_author_url, $comment->comment_author_url );
					update_post_meta( $review->ID, $this->review_meta_review_author_ip, $comment->comment_author_IP );

					update_post_meta( $review->ID, $this->review_meta_review_type, $comment->comment_type );
					update_post_meta( $review->ID, $this->review_meta_review_comment_parent, $comment->comment_parent );
				}
				continue;
			}

			// Set execution time.
			$this->set_time_limit( 30 );

			$val    = get_comment_meta( $comment->comment_ID, 'rating', true );
			$rating = $val ? $val : 0;

			// Create post object.
			$args = array(
				'post_author'             => $comment->user_id,
				'post_date'               => $comment->comment_date,
				'post_date_gmt'           => $comment->comment_date_gmt,
				'post_content'            => $comment->comment_content,
				'post_title'              => '',
				'review_user_id'          => $comment->user_id,
				'review_approved'         => $comment->comment_approved,
				'review_product_id'       => $comment->comment_post_ID,
				'review_comment_id'       => $comment->comment_ID,
				'review_rating'           => $rating,
				'review_is_reply_blocked' => 1,
				'review_voters'           => array(),
				'review_voter_guests'     => array(),
				'review_votes'            => 0,
				'review_upvotes'          => 0,
				'review_downvotes'        => 0,
				'review_author'           => $comment->comment_author,
				'review_author_email'     => $comment->comment_author_email,
				'review_author_url'       => $comment->comment_author_url,
				'review_author_IP'        => $comment->comment_author_IP,
				'review_type'             => $comment->comment_type,
				'review_parent'           => '',
				'review_comment_parent'   => $comment->comment_parent,
			);

			// Insert the post into the database.
			$review_id = $this->kt_add_review( $args );

			$review_ids[ $comment->comment_ID ] = $review_id;

			// If current comment have parent, store it and update all relationship when the import ends.
			if ( $comment->comment_parent > 0 ) {
				$parent_review[ $review_id ] = $comment->comment_parent;
			}

			// set current comment as imported.
			update_comment_meta( $comment->comment_ID, $this->review_meta_imported, 1 );
			$review_converted ++;
		}

		return $review_converted;
	}
	/**
	 * Build Reviews Metaboxes.
	 */
	public function add_metaboxes() {

		$prefix = '_kt_review_';
		$kt_woo_reviews = new_cmb2_box(
			array(
				'id'            => $prefix . 'reviews',
				'title'         => __( 'Review Settings', 'kadence-woo-extras' ),
				'object_types'  => array( 'kt_reviews' ), // Post type
			)
		);
		$kt_woo_reviews->add_field(
			array(
				'name'          => __( 'Author Name (if not a site user, else shows site user display name)', 'kadence-woo-extras' ),
				'id'            => $prefix . 'author',
				'type'          => 'Text',
			)
		);
		$kt_woo_reviews->add_field(
			array(
				'name'          => __( 'Rating', 'kadence-woo-extras' ),
				'id'            => $prefix . 'rating',
				'type'          => 'select',
				'options'          => array(
					'1'     => __( '1 Star', 'kadence-woo-extras' ),
					'2'     => __( '2 Stars', 'kadence-woo-extras' ),
					'3'     => __( '3 Stars', 'kadence-woo-extras' ),
					'4'     => __( '4 Stars', 'kadence-woo-extras' ),
					'5'     => __( '5 Stars', 'kadence-woo-extras' ),
				),
			)
		);
		$kt_woo_reviews->add_field(
			array(
				'name'          => __( 'Review Approved', 'kadence-woo-extras' ),
				'id'            => $prefix . 'approved',
				'type'          => 'select',
				'options'          => array(
					'0'     => __( 'Not Approved', 'kadence-woo-extras' ),
					'1'     => __( 'Approved', 'kadence-woo-extras' ),
				),
			)
		);
		$kt_woo_reviews->add_field(
			array(
				'name'          => __( 'Up Votes', 'kadence-woo-extras' ),
				'id'            => $prefix . 'upvotes',
				'default'       => '0',
				'type'          => 'text_vote_up',
			)
		);
		$kt_woo_reviews->add_field(
			array(
				'name'          => __( 'Down Votes', 'kadence-woo-extras' ),
				'id'            => $prefix . 'downvotes',
				'default'       => '0',
				'type'          => 'text_vote_down',
			)
		);
		$kt_woo_reviews->add_field(
			array(
				'name'          => __( 'Review is Featured', 'kadence-woo-extras' ),
				'id'            => $prefix . 'is_featured',
				'type'          => 'select',
				'options'          => array(
					'0'     => __( 'False', 'kadence-woo-extras' ),
					'1'     => __( 'True', 'kadence-woo-extras' ),
				),
			)
		);
	}
	/**
	 * Load script for importing.
	 *
	 * @param string $hook the page hook.
	 */
	public function enqueue_admin_import_scripts( $hook ) {
		//'toplevel_page_kt_reviews' === $hook
		if ( isset( $hook ) && ! empty( $hook ) && 'toplevel_page_kadence-shop-kit-settings' === $hook ) {
			wp_enqueue_script( 'kadence-admin-reviews-import-js', KADENCE_WOO_EXTRAS_URL . 'lib/reviews/js/kt_admin_woo_reviews.js', array( 'jquery' ), KADENCE_WOO_EXTRAS_VERSION, true );
			wp_localize_script(
				'kadence-admin-reviews-import-js',
				'kadenceAdminReviews',
				array(
					'ajax_url'       => admin_url( 'admin-ajax.php' ),
					'ajax_nonce'     => wp_create_nonce( 'kadence-admin-reviews-ajax-verification' ),
					'reviewButton' => '<div class="convert-info"><a class="button-primary kt-review-convert" style="margin:10px 0;" href="#">' .  __( 'Convert Reviews', 'kadence-woo-extras' ) . '</a><p></p></div>',
				)
			);
		}

	}
	/**
	 * Convert comments to reviews.
	 */
	public function kt_check_import_actions() {
		if ( isset( $_GET['kt-convert-reviews'] ) ) {

			$response = $this->kt_import_previous_reviews();
			add_settings_error( 'update_converts', 'update_converts', sprintf( __( 'Converted %d Reviews', 'kadence-woo-extras' ), $response ), 'updated' );

			wp_redirect( esc_url( remove_query_arg( 'kt-convert-reviews' ) ) );
		}
	}
	/**
	 * Convert comments to reviews.
	 */
	public function convert_reviews_callback() {
		check_ajax_referer( 'kadence-admin-reviews-ajax-verification', 'security' );
		$review_converted = $this->kt_import_previous_reviews();
		$response         = '';

		if ( $review_converted ) {
			$response = sprintf( __( 'Completed. %d reviews have been processed and converted.', 'kadence-woo-extras' ), $review_converted );
		} else {
			$response = __( 'Completed. No reviews needing to be converted have been found.', 'kadence-woo-extras' );
		}

		wp_send_json( array( 'value' => $response ) );
	}
	/**
	 * Convert comments to reviews.
	 */
	public function kt_vote_callback() {
		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'kt_reviews' ) ) {
			return false;
		}
		if ( ! isset( $_POST['comment_id'] ) ) {
			return false;
		} else {
			$review_id = $_POST['comment_id'];
		}
		if ( ! isset( $_POST['user_id'] ) || empty( $_POST['user_id'] ) ) {
			return false;
		} else {
			$user_id = $_POST['user_id'];
		}
		if ( ! isset( $_POST['vote'] ) ) {
			$vote = 'positive';
		} else {
			$vote = $_POST['vote'];
		}
		$vote_users = get_post_meta( $review_id, $this->review_meta_voters, true );
		if ( in_array( $user_id, $vote_users ) ) {
			$same_user = true;
		} else {
			$same_user = false;
		}
		if ( $same_user == false ) {
			if ( $vote == 'positive' ) {
				$upvotes = get_post_meta( $review_id, $this->review_meta_upvotes_count, true );
				$votes = get_post_meta( $review_id, $this->review_meta_votes, true );
				$upvotes++;
				$votes++;
				update_post_meta( $review_id, $this->review_meta_upvotes_count, $upvotes );
				update_post_meta( $review_id, $this->review_meta_votes, $votes );
			} else {
				$downvotes = get_post_meta( $review_id, $this->review_meta_downvotes_count, true );
				$votes = get_post_meta( $review_id, $this->review_meta_votes, true );
				$votes--;
				$downvotes++;
				update_post_meta( $review_id, $this->review_meta_downvotes_count, $downvotes );
				update_post_meta( $review_id, $this->review_meta_votes, $votes );
			}
			$vote_users[] = $user_id;
			update_post_meta( $review_id, $this->review_meta_voters, $vote_users );
			$helpful = get_post_meta( $review_id, $this->review_meta_upvotes_count, true );
			$total = $helpful + get_post_meta( $review_id, $this->review_meta_downvotes_count, true );

			$response = sprintf( __( '%1$d of %2$s found this helpful', 'kadence-woo-extras' ), $helpful, $total );
		} else {
			$response = __( 'You have already voted', 'kadence-woo-extras' );
		}

		wp_send_json( array( 'value' => $response ) );
	}
	public function kt_vote_callback_guest() {
		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'kt_reviews' ) ) {
			return false;
		}
		if ( ! isset( $_POST['comment_id'] ) ) {
			return false;
		} else {
			$review_id = $_POST['comment_id'];
		}
		if ( ! isset( $_POST['vote'] ) ) {
			$vote = 'positive';
		} else {
			$vote = $_POST['vote'];
		}
		$vote_ips = get_post_meta( $review_id, $this->review_meta_voter_guests, true );
		if ( empty( $vote_ips ) ) {
			$vote_ips = array();
		}
		$user_ip = $_SERVER['REMOTE_ADDR'];
		if ( in_array( $user_ip, $vote_ips ) ) {
			$same_user = true;
		} else {
			$same_user = false;
		}
		if ( $same_user == false ) {
			if ( $vote == 'positive' ) {
				$upvotes = get_post_meta( $review_id, $this->review_meta_upvotes_count, true );
				$votes = get_post_meta( $review_id, $this->review_meta_votes, true );
				$upvotes++;
				$votes++;
				update_post_meta( $review_id, $this->review_meta_upvotes_count, $upvotes );
				update_post_meta( $review_id, $this->review_meta_votes, $votes );
			} else {
				$downvotes = get_post_meta( $review_id, $this->review_meta_downvotes_count, true );
				$votes = get_post_meta( $review_id, $this->review_meta_votes, true );
				$votes--;
				$downvotes++;
				update_post_meta( $review_id, $this->review_meta_downvotes_count, $downvotes );
				update_post_meta( $review_id, $this->review_meta_votes, $votes );
			}
			$vote_ips[] = $user_ip;
			update_post_meta( $review_id, $this->review_meta_voter_guests, $vote_ips );
			$helpful = get_post_meta( $review_id, $this->review_meta_upvotes_count, true );
			$total = $helpful + get_post_meta( $review_id, $this->review_meta_downvotes_count, true );
			$response = sprintf( __( '%1$d of %2$s found this helpful', 'kadence-woo-extras' ), $helpful, $total );
		} else {
			$response = __( 'You have already voted', 'kadence-woo-extras' );
		}

		wp_send_json( array( 'value' => $response ) );
	}
	public function kt_review_dash() {
		if ( current_user_can( 'publish_shop_orders' ) ) {
			remove_meta_box( 'woocommerce_dashboard_recent_reviews', 'dashboard', 'normal' );
			wp_add_dashboard_widget( 'kt_woocommerce_dashboard_recent_reviews', __( 'WooCommerce Recent Reviews', 'woocommerce' ), array( $this, 'kt_recent_reviews_widget' ) );
		}

	}
	public function kt_recent_reviews_widget() {
		$args = array(
			'numberposts' => 5,
			'offset'      => 0,
			'orderby'     => 'post_date',
			'meta_key'    => '_kt_review_votes',
			'order'       => 'DESC',
			'post_type'   => 'kt_reviews',
			'post_parent' => '0',
			'post_status' => 'publish',
		);
		$reviews = get_posts( $args );

		if ( $reviews ) {
			echo '<ul>';
			foreach ( $reviews as $review ) {

				echo '<li>';
				$author = $this->kt_get_meta_author( $review->ID );
				$user = isset( $author['review_user_id'] ) ? get_userdata( $author['review_user_id'] ) : null;
				echo get_avatar( $user->ID, '32' );

				$rating = $this->kt_get_meta_rating( $review->ID );

				echo '<div class="star-rating" title="' . esc_attr( $rating ) . '">
					<span style="width:' . ( $rating * 20 ) . '%">' . $rating . ' ' . __( 'out of 5', 'kadence-woo-extras' ) . '</span></div>';

				echo '<h4 class="meta">' . esc_html( apply_filters( 'kt_woocommerce_admin_dashboard_recent_reviews', $review->post_title, $review ) ) . ' ' . __( 'reviewed by', 'woocommerce' ) . ' ' . esc_html( $user->display_name ) . '</h4>';
				echo '<blockquote>' . wp_kses_data( $review->post_content ) . ' [...]</blockquote></li>';

			}
			echo '</ul>';
		} else {
			echo '<p>' . __( 'There are no product reviews yet.', 'woocommerce' ) . '</p>';
		}
	}
}
/**
 * Get the reviews class loaded.
 */
function kadence_reviews_plugin_loaded() {
	$GLOBALS['kt_reviews'] = Kadence_Advanced_Reviews::get_instance();
}
add_action( 'plugins_loaded', 'kadence_reviews_plugin_loaded' );