<?php
/**
 * Quickview
 *
 * @package Kadence Woo Extras
 */

if (! defined('ABSPATH') ) {
    exit;
}

/**
 * Class to build out quickview.
 *
 * @category class
 */
class Kadence_Shop_Kit_Quickview
{

    /**
     * Instance of this class
     *
     * @var null
     */
    private static $instance = null;

    /**
     * Gallery settings.
     *
     * @var null
     */
    private static $quickview_args = null;

    /**
     * Gallery settings.
     *
     * @var null
     */
    private static $quickviews_active = array();

    /**
     * Gallery settings.
     *
     * @var null
     */
    private static $has_output_button_styles = false;

    /**
     * Instance Control
     */
    public static function get_instance()
    {
        if (is_null(self::$instance) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Build Quickview.
     */
    public function __construct()
    {
        add_action('init', array( $this, 'init_me' ));
        add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));

        // Respond to quickview requests from the frontend.
        add_action('wp_ajax_kt_woo_get_quickview_html', array( $this, 'quickview_ajax' ));
        add_action('wp_ajax_nopriv_kt_woo_get_quickview_html', array( $this, 'quickview_ajax' ));
        add_action('rest_api_init', array( $this, 'register_rest_routes' ));

        // Build the quickview content via action hooks.
        // add_action('kt_quickview_summary', array( $this, 'modal_part_sale_flash' ), 5, 3);
        add_action('kt_quickview_summary', array( $this, 'modal_part_title' ), 10, 3);
        add_action('kt_quickview_summary', array( $this, 'modal_part_rating' ), 15, 3);
        add_action('kt_quickview_summary', array( $this, 'modal_part_price' ), 20, 3);
        add_action('kt_quickview_summary', array( $this, 'modal_part_excerpt' ), 25, 3);
        add_action('kt_quickview_summary', array($this, 'modal_part_add_to_cart'), 30, 3);
        // add_action('kt_quickview_summary', array( $this, 'modal_part_meta' ), 35, 3);
        // add_action('kt_quickview_images', array( $this, 'modal_part_styles' ), 5, 3);
        add_action('kt_quickview_images', array( $this, 'modal_part_images' ), 10, 3);
        // add_action( 'jck_qv_after_summary', array( $this, 'modal_part_close' ), 5, 3 );
        // add_action( 'jck_qv_after_summary', array( $this, 'modal_part_adding_to_cart' ), 10, 3 );


		add_shortcode( 'kt_quickview_button', array( $this, 'output_shortcode' ) );

        if( $this->get_quickview_args()['in_blocks'] ) {
			add_action( 'init', array( $this, 'register_quickview_blocks' ), 2 );
		}

        //TODO polish placement
    }

	/**
	 * Add Blocks For Quickview.
	 */
	public function register_quickview_blocks() {
		// Register the blocks.
		$path = KADENCE_WOO_EXTRAS_URL . 'build/';
		$asset_file = $this->get_asset_file( 'quickview-blocks' );
		wp_register_script(
			'kadence-quickview-blocks',
			$path . 'quickview-blocks.js',
			$asset_file['dependencies'],
			$asset_file['version']
		);
		wp_register_style(
			'kadence-quickview-blocks',
			$path . 'quickview-blocks.css',
			[],
			$asset_file['version']
		);

		wp_localize_script(
			'kadence-quickview-blocks',
			'kadenceWooQuickviewBlockParams',
			array(
				'isKadence'          => ( class_exists( 'Kadence\Theme' ) ? true : false ),
				'quickviewButtonHtml' => $this->get_the_quickview_button_html(null, true, false),
			)
		);

		$quickview_button_block_args = array(
			'api_version' => 2,
			'uses_context' => [ 'postId', 'postType', 'queryId', 'templateType' ],
			'editor_script' => 'kadence-quickview-blocks',
			'editor_style' => 'kadence-quickview-blocks',
			'render_callback' => 'kadence_wootemplate_render_quickview_button_block',
		);
		register_block_type(
			'kadence-wootemplate-blocks/quickview-button',
			$quickview_button_block_args
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
     * Initialize.
     */
    public function init_me()
    {
        if(! is_admin() ) {
            $this->hook_quickview_buttons();
        }
    }

    /**
     * respond to an ajax request to get the quickview content.
     */
    public function quickview_ajax()
    {
        check_ajax_referer('kadence-quickview-nonce');
        $product_id = absint(sanitize_text_field(isset($_GET['product_id']) ? $_GET['product_id'] : ''));
        $data = array();
        if ($product_id ) {
            $data['html'] = $this->get_the_quickview($product_id);
            wp_send_json_success($data);
        } else {
            wp_send_json_error();
        }
    }

    /**
     * Add in the gallery scripts and styles.
     */
    public function enqueue_scripts()
    {
        global $post;

        //if quickview could be active on this page load
        if( $this->is_quickview_going_to_be_active( true ) ) {
            // Lightbox.
            wp_register_script('kadence-glightbox', KADENCE_WOO_EXTRAS_URL . 'inc/assets/js/glightbox.min.js', array(), KADENCE_WOO_EXTRAS_VERSION, true);
            wp_enqueue_style('kadence-glightbox', KADENCE_WOO_EXTRAS_URL . 'inc/assets/css/glightbox.min.css', false, KADENCE_WOO_EXTRAS_VERSION);

            // Our scripts/styles.
            wp_enqueue_script('kadence-quickview', KADENCE_WOO_EXTRAS_URL . 'lib/quickview/js/min/kadence-quickview.min.js', array( 'kadence-glightbox' ), KADENCE_WOO_EXTRAS_VERSION, true);
            wp_enqueue_style('kadence-quickview', KADENCE_WOO_EXTRAS_URL . 'lib/quickview/css/kadence-quickview.css', false, KADENCE_WOO_EXTRAS_VERSION);

            wp_enqueue_style( 'kadence-product-gallery', KADENCE_WOO_EXTRAS_URL . 'lib/gallery/css/kadence-product-gallery.css', false, KADENCE_WOO_EXTRAS_VERSION );
			wp_enqueue_style( 'kadence-kb-splide', KADENCE_WOO_EXTRAS_URL . 'lib/gallery/css/kadence-splide.css', false, KADENCE_WOO_EXTRAS_VERSION );
            wp_enqueue_script( 'kadence-update-splide', KADENCE_WOO_EXTRAS_URL . 'lib/gallery/js/splide.min.js', array(), KADENCE_WOO_EXTRAS_VERSION, true );
            wp_enqueue_script('kadence_product_gallery', KADENCE_WOO_EXTRAS_URL . 'lib/gallery/js/kadence-product-gallery.js', array( 'kadence-update-splide', 'jquery' ), KADENCE_WOO_EXTRAS_VERSION, true);
            $gallery_translation_array = array(
                'plyr_js'          => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/js/min/plyr.js',
                'plyr_css'         => KADENCE_WOO_EXTRAS_URL . 'lib/gallery/css/plyr.css',
                'ajax_nonce'       => wp_create_nonce( 'kwsv' ),
                'ajax_url'         => admin_url( 'admin-ajax.php' ),
                'lightbox'         => false,
                'lightbox_style'   => 'kadence-dark',
            );
            wp_localize_script( 'kadence_product_gallery', 'kadence_pg', $gallery_translation_array );

            $args = $this->get_quickview_args();

            $variables = array(
                'ajax_nonce'       => wp_create_nonce('wp_rest'),
                'ajax_url'         => rest_url('kwqv-content/v1/quickview'),
                'ajax_loader'      => apply_filters('kadence_woo_extras_quickview_ajax_loader', KADENCE_WOO_EXTRAS_URL . 'inc/assets/images/ajax-loader.gif'),
                'slides'           => $args['slides'] ? true : false,
            );
            wp_localize_script('kadence-quickview', 'kadenceQuickview', $variables);

            //pretend like we're rendering a quickview on page load to enqueue all the appropriate block styles
            //don't need to actually put the html from this anywhere
            add_action('wp_footer', array( $this, 'get_the_quickview' ), 10, 3);
        }
    }

    /**
     * Check if the quickview is going to be active.
     */
    public function is_quickview_going_to_be_active( $for_enqueue = false ) {
        $check_result = false;
        $args = $this->get_quickview_args();

        $single_check = is_singular('product');
        $admin_check = ! is_admin();
        $base_check = ( 
            is_product_taxonomy() || 
            is_post_type_archive( 'product' ) || 
            is_page( wc_get_page_id( 'shop' ) ) || 
            is_shop() || 
            apply_filters('kadence_woo_extras_is_quickview_page', false) 
        );

        if ( $args['in_blocks'] && $for_enqueue ) {
            $check_result = $admin_check;
        } else if ( $args['auto_in_single'] ) {
            $check_result = $admin_check && ($single_check || $base_check);
        } else {
            $check_result = $admin_check && $base_check;
        }

        return $check_result;
    }

    /**
     * Get the Quickview content.
     */
    public function get_the_quickview( $the_product = null )
    {
        return $this->the_quickview($the_product, true);
    }

    /**
     * Render the quickview content.
     */
    public function the_quickview( $the_product = null, $return = false )
    {
        global $product, $post, $in_quickview;
        $the_product = $the_product ? $the_product : $product;
        $product = wc_get_product($the_product);
        $content = '';

        $temp = current_filter();
        $temp2 = current_action();

        if( $product ) {
            $old_post = $post;
            $post = get_post($product->get_id());
            setup_postdata( $post );
            $in_quickview = true;

            $post_id              = get_the_ID();
            $post_type            = get_post_type();
            $filter_block_context = static function( $context ) use ( $post_id, $post_type ) {
                $context['postType'] = $post_type;
                $context['postId']   = $post_id;
                return $context;
            };
            add_filter( 'render_block_context', $filter_block_context );

            $args = $this->get_quickview_args();

            if( $args['use_template'] && $args['template'] ){
                $content = get_the_content(null, false, $args['template']);
                if( $content ) {
                    $content = $GLOBALS['wp_embed']->run_shortcode($content);
                    $content = $GLOBALS['wp_embed']->autoembed($content);
                    $content = do_blocks($content);
                    $content = wptexturize($content);
                    $content = shortcode_unautop($content);
                    $content = do_shortcode($content);
                    $content = convert_smilies($content);
                }
            } else {
                $content = wc_get_template_html('kt-quickview.php', array( 'args' => $args ), '', KADENCE_WOO_EXTRAS_PATH . 'lib/quickview/templates/');
            }
            $content = apply_filters('kadence_woo_extras_quickview', $content, $args);
        }

        if ($return ) {
            return $content;
        } else {
            echo esc_html($content);
        }
        
		remove_filter( 'render_block_context', $filter_block_context );
        $in_quickview = false;
        wp_reset_postdata();
        $post = $old_post;
    }

    /**
     * Render the quickview button.
     */
    public function hook_quickview_buttons()
    {
        global $product;
        $args = $this->get_quickview_args();

        if ( $args['auto'] ) {
            switch ( $args['auto_placement'] ) {
            // switch ( 'above-price' ) {
            case 'before-item':
                add_action('woocommerce_before_shop_loop_item', array( $this, 'the_quickview_button' ));
                break;
            case 'after-item':
                add_action('woocommerce_after_shop_loop_item', array( $this, 'the_quickview_button' ));
                break;
            case 'above-title':
                add_action('kadence_woocommerce_template_loop_product_title_before', array( $this, 'the_quickview_button' ), 5);
                add_action('woocommerce_shop_loop_item_title', array( $this, 'the_quickview_button' ), 5);
                break;
            case 'below-title':
                add_action('kadence_woocommerce_template_loop_product_title_after', array( $this, 'the_quickview_button' ), 5);
                add_action('woocommerce_shop_loop_item_title', array( $this, 'the_quickview_button' ), 10);
                break;
            case 'above-price':
                add_action('kadence_woocommerce_template_loop_product_price_before', array( $this, 'the_quickview_button' ), 5);
                add_action('woocommerce_after_shop_loop_item_title', array( $this, 'the_quickview_button' ), 8);
                break;
            case 'below-price':
                add_action('kadence_woocommerce_template_loop_product_price_after', array( $this, 'the_quickview_button' ), 5);
                add_action('woocommerce_after_shop_loop_item_title', array( $this, 'the_quickview_button' ), 15);
                break;
            case 'before-add' :
                add_filter('woocommerce_loop_add_to_cart_link', array( $this, 'output_quickview_button_before_add' ), 10, 3);
                break;
            default:
                //after add
                add_filter('woocommerce_loop_add_to_cart_link', array( $this, 'output_quickview_button_after_add' ), 10, 3);
                break;
            }
        }
    }

    /**
     * Output the button in the before add to cart hook.
     */
    public function output_quickview_button_before_add( $add_to_cart_html, $product, $args )
    {
        $the_button_html = $this->get_the_quickview_button();
        return $the_button_html . $add_to_cart_html;
    }

    /**
     * Output the button in the after add to cart hook.
     */
    public function output_quickview_button_after_add( $add_to_cart_html, $product, $args )
    {
        $the_button_html = $this->get_the_quickview_button();
        return $add_to_cart_html . $the_button_html;
    }


    /**
     * Get the Button.
     */
    public function get_the_quickview_button( $the_product = null )
    {
        $this->enqueue_scripts();
        return $this->the_quickview_button($the_product, true);
    }


    /**
     * Output the Button.
     */
    public function the_quickview_button( $the_product_id = null, $return = false )
    {
        global $product, $in_quickview;
        $the_product = $the_product_id ? wc_get_product( $the_product_id ) : $product;
        $content = '';

        if( ! $in_quickview && $this->is_quickview_going_to_be_active() ) {
            if ( $the_product ) {
                $content = $this->get_the_quickview_button_html( $the_product, $return );
            }
        }

        if ( $return ) {
            return $content;
        } else {
            echo $content;
        }
    }

    /**
     * Get the Quickview button html.
     * 
     * @param object $the_product The product object.
     * @param bool $return Whether to return the html or echo it.
     * @return string The html for the quickview button.
     */
    public function get_the_quickview_button_html( $the_product = null, $return = false, $frontend = true )
    {
        $content = '';
        $style_tag = '';

        $args = $this->get_quickview_args();

        $base_selector = '.kt-woo-quickview-button.kt-woo-quickview-button.kt-woo-quickview-button.kt-woo-quickview-button.kt-woo-quickview-button.kt-woo-quickview-button';
        $css = new Kadence_Woo_CSS();
        $css->set_selector($base_selector);
        if ( $args['button_color'] ) {
            $css->add_property('color', $args['button_color']);
        }
        if ( $args['button_background'] ) {
            $css->add_property('background-color', $args['button_background']);
        }
        if ( $args['button_border_color'] ) {
            $css->add_property('border-color', $args['button_border_color']);
        }
        if ( $args['button_border_width'] ) {
            $css->add_property('border-width', $args['button_border_width'] . 'px');
            $css->add_property('border-style', 'solid');
        }
        if ( $args['button_border_radius'] ) {
            $css->add_property('border-radius', $args['button_border_radius'] . 'px');
        }
        if ( $args['button_reveal_hover'] ) {
            $css->add_property('display', 'none');
            $css->add_property('opacity', '0');

            $css->set_selector('.loop-entry.product:hover ' . $base_selector);
            $css->add_property('display', 'flex');
            $css->add_property('opacity', '1');
        }
        $css->set_selector($base_selector . ' img');
        if ( $args['button_icon'] && $args['button_icon_color'] ) {
            $css->add_property('filter',  'drop-shadow(0px 1000px 0 ' . $args['button_icon_color'] . ')');
            $css->add_property('transform', 'translateY(-1000px)');
        }
        $css->set_selector($base_selector . ':hover');
        if ( $args['button_color_hover'] ) {
            $css->add_property('color', $args['button_color_hover']);
        }
        if ( $args['button_background_hover'] ) {
            $css->add_property('background-color', $args['button_background_hover']);
        }
        if ( $args['button_border_color_hover'] ) {
            $css->add_property('border-color', $args['button_border_color_hover']);
        }
        $css->set_selector($base_selector . ':hover img');
        if ( $args['button_icon'] && $args['button_icon_color_hover'] ) {
            $css->add_property('filter',  'drop-shadow(0px 1000px 0 ' . $args['button_icon_color_hover'] . ')');
            $css->add_property('transform', 'translateY(-1000px)');
        }

        $styles = $css->css_output();
        if ( ! empty($styles ) ) {
            if ( ! self::$has_output_button_styles ) {
                $style_tag = '<style>' . $styles . '</style>';
                self::$has_output_button_styles = true;
            }
        }

        $product_id = $the_product ? $the_product->get_id() : 0;
        $classes = array( 
            'kt-woo-quickview-button', 
            'kt-woo-quickview-button-' . $product_id,
            'wp-element-button'
        );

        if( $frontend ){
            $classes[] = 'button';
        }

        if( $args['button_background'] || $args['button_border_width'] ){
            $classes[] = 'has-bg-styling';
        }

        $button_label = '';

        if ( $args['show_label'] ) {
            $button_label = apply_filters('kadence_woo_extras_quickview_button_label', $args['label'], $args);
        }

        $button_icon = '';
        if ( $args['button_icon'] ) {
            if ( $args['button_icon_choice'] == 'play' ) {
                $button_icon = '<img src="' . KADENCE_WOO_EXTRAS_URL . 'lib/quickview/images/play.png"/>';
            } elseif ( $args['button_icon_choice'] == 'click' ) {
                $button_icon = '<img src="' . KADENCE_WOO_EXTRAS_URL . 'lib/quickview/images/click.png"/>';
            } else {
                $button_icon = '<img src="' . KADENCE_WOO_EXTRAS_URL . 'lib/quickview/images/eye.png"/>';
            }
        }

        if ( $button_label || $button_icon ) {
            $content = apply_filters('kadence_woo_extras_quickview_button', '<button class="' . esc_attr(implode(' ', $classes)) . '" data-product-id="' . $product_id . '">' . $button_icon . $button_label . '</button>', $args);
            self::$quickviews_active[] = $product_id;
        }

        if ( $return ) {
            return $style_tag . $content;
        } else {
            return $style_tag . wp_kses_post( $content );
        }
    }

    /**
     * Get Quickview args.
     */
    public function get_quickview_args( $args = array() )
    {
        $shopkit_settings = get_option('kt_woo_extras');
        if (! is_array($shopkit_settings) ) {
            $shopkit_settings = json_decode($shopkit_settings, true);
        }
        $defaults = array(
        'auto'  => ( isset($shopkit_settings['product_quickview_auto']) ? $shopkit_settings['product_quickview_auto'] : true ),
        'auto_placement'         => ( ! empty($shopkit_settings['product_quickview_auto_placement']) ? $shopkit_settings['product_quickview_auto_placement'] : '' ),
        'auto_in_single'  => ( isset($shopkit_settings['product_quickview_auto_in_single']) ? $shopkit_settings['product_quickview_auto_in_single'] : false ),
        'in_blocks'  => ( isset($shopkit_settings['product_quickview_in_blocks']) ? $shopkit_settings['product_quickview_in_blocks'] : false ),
        'use_template'         => ( isset($shopkit_settings['product_quickview_use_template']) ? $shopkit_settings['product_quickview_use_template'] : false ),
        'template'         => ( ! empty($shopkit_settings['product_quickview_template']) ? $shopkit_settings['product_quickview_template'] : '' ),
        'button_icon'         => ( isset($shopkit_settings['product_quickview_button_icon']) ? $shopkit_settings['product_quickview_button_icon'] : false ),
        'button_icon_choice'         => ( ! empty($shopkit_settings['product_quickview_button_icon_choice']) ? $shopkit_settings['product_quickview_button_icon_choice'] : '' ),
        'button_reveal_hover'         => ( isset($shopkit_settings['product_quickview_button_reveal_hover']) ? $shopkit_settings['product_quickview_button_reveal_hover'] : '' ),
        'show_label'         => ( isset($shopkit_settings['product_quickview_show_label']) ? $shopkit_settings['product_quickview_show_label'] : true ),
        'label'         => ( ! empty($shopkit_settings['product_quickview_label']) ? $shopkit_settings['product_quickview_label'] : 'quickview' ),
        'button_color'         => ( ! empty($shopkit_settings['product_quickview_button_color']) ? $shopkit_settings['product_quickview_button_color'] : '' ),
        'button_color_hover'         => ( ! empty($shopkit_settings['product_quickview_button_color_hover']) ? $shopkit_settings['product_quickview_button_color_hover'] : '' ),
        'button_background'         => ( ! empty($shopkit_settings['product_quickview_button_background']) ? $shopkit_settings['product_quickview_button_background'] : '' ),
        'button_background_hover'         => ( ! empty($shopkit_settings['product_quickview_button_background_hover']) ? $shopkit_settings['product_quickview_button_background_hover'] : '' ),
        'button_border_width'         => ( ! empty($shopkit_settings['product_quickview_button_border_width']) ? $shopkit_settings['product_quickview_button_border_width'] : '' ),
        'button_border_color'         => ( ! empty($shopkit_settings['product_quickview_button_border_color']) ? $shopkit_settings['product_quickview_button_border_color'] : '' ),
        'button_border_color_hover'         => ( ! empty($shopkit_settings['product_quickview_button_border_color_hover']) ? $shopkit_settings['product_quickview_button_border_color_hover'] : '' ),
        'button_border_radius'         => ( ! empty($shopkit_settings['product_quickview_button_border_radius']) ? $shopkit_settings['product_quickview_button_border_radius'] : '' ),
        'button_icon_color'         => ( ! empty($shopkit_settings['product_quickview_button_icon_color']) ? $shopkit_settings['product_quickview_button_icon_color'] : '' ),
        'button_icon_color_hover'         => ( ! empty($shopkit_settings['product_quickview_button_icon_color_hover']) ? $shopkit_settings['product_quickview_button_icon_color_hover'] : '' ),
        'slides'         => ( isset($shopkit_settings['product_quickview_slides']) ? $shopkit_settings['product_quickview_slides'] : true ),
        );
        // Responsive Defaults.
        // $defaults['layout_tablet'] = ( ! empty( $shopkit_settings['ga_slider_layout_tablet'] ) ? $shopkit_settings['ga_slider_layout_tablet'] : $defaults['layout'] );
        // $defaults['layout_mobile'] = ( ! empty( $shopkit_settings['ga_slider_layout_mobile'] ) ? $shopkit_settings['ga_slider_layout_mobile'] : $defaults['layout_tablet'] );

        // Generate Final Size Settings.
        $quickview_args = wp_parse_args($args, $defaults);

        self::$quickview_args = $quickview_args;
        return $quickview_args;
    }

    /**
     * Frontend: Modal Part: Add to Cart
     */
    public function modal_part_add_to_cart()
    {
        global $product;

        $block = new StdClass();
        $block->context = array('postId' => $product->get_id());
        $block->blockName = 'kadence-wootemplate-blocks/add-to-cart';

        $attributes= array(
            "uniqueID"=> uniqid(),
            "showQuantity" => true,
            "fullBtn" =>false,
        );

        echo kadence_wootemplate_render_add_to_cart_block($attributes, '', $block, true);
    }

    /**
     * Frontend: Modal Part: Title
     */
    public function modal_part_title()
    {
        global $product;

        $block = new StdClass();
        $block->context = array('postId' => $product->get_id());
        $block->blockName = 'kadence-wootemplate-blocks/title';

        $attributes= array(
            "uniqueID"=> uniqid(),
        );

        echo kadence_wootemplate_render_title_block($attributes, '', $block, true);
    }

    /**
     * Frontend: Modal Part: Excerpt
     */
    public function modal_part_excerpt()
    {
        global $product;

        $block = new StdClass();
        $block->context = array('postId' => $product->get_id());
        $block->blockName = 'kadence-wootemplate-blocks/excerpt';

        $attributes= array(
            "uniqueID"=> uniqid(),
        );

        echo kadence_wootemplate_render_excerpt_block($attributes, '', $block, true);
    }

    /**
     * Frontend: Modal Part: Rating
     */
    public function modal_part_rating()
    {
        global $product;

        $block = new StdClass();
        $block->context = array('postId' => $product->get_id());
        $block->blockName = 'kadence-wootemplate-blocks/rating';

        $attributes= array(
            "uniqueID"=> uniqid(),
        );

        echo kadence_wootemplate_render_rating_block($attributes, '', $block, true);
    }

    /**
     * Frontend: Modal Part: Price
     */
    public function modal_part_price()
    {
        global $product;

        $block = new StdClass();
        $block->context = array('postId' => $product->get_id());
        $block->blockName = 'kadence-wootemplate-blocks/price';

        $attributes= array(
            "uniqueID"=> uniqid(),
            "isLink"=> true,
            "rel"=> '',
            "linkTarget"=> false,
        );

        echo kadence_wootemplate_render_price_block($attributes, '', $block, true);
    }

    /**
     * Frontend: Modal Part: Images
     */
    public function modal_part_images()
    {
        $this->output_gallery();
    }

    /**
     * Output the Gallery.
     */
    public function output_gallery( $args = array() )
    {
        global $product;

        $block = new StdClass();
        $block->context = array('postId' => $product->get_id());
        $block->blockName = 'kadence-wootemplate-blocks/gallery';

        $attributes= array(
            "uniqueID"=> uniqid(),
            "type"=> array( "slider", "", "" ),
            "thumbWidth"=> array( "", "", "" ),
            "thumbColumns"=> array( "", "", "" ),
            "showSale"=> false,
            "thumbGap"=> array( "", "", "" ),
            "thumbGridGap"=> array( "", "", "" ),
            "arrows"=> true,
            "pagination"=> false
        );

        echo kadence_wootemplate_render_gallery_block($attributes, '', $block, true, true);
    }

    /**
     * Output Shortcode
     *
     * @param array $atts shortcode attributes
     */
    public function output_shortcode( $atts ) {
        global $product;

        $quickview = shortcode_atts(
            array(
                'id' => '',
            ),
            $atts
        );

        $the_product_id = null;
        if($quickview['id']) {
            $the_product_id = $quickview['id'];
        } else if ($product) {
            $the_product_id = $product->get_id();
        }

        if($the_product_id) {
            return $this->get_the_quickview_button($the_product_id);
        }
        return '';
    }
    
	/**
	 * Add rest routes for the quickview response.
	 */
	public function register_rest_routes() {
		$dynamic_controller = new Kadence_Shop_Kit_Quickview_Rest_Controller();
		$dynamic_controller->register_routes();
	}
}
$GLOBALS['kt_quickview'] = Kadence_Shop_Kit_Quickview::get_instance();
