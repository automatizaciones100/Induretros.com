<?php
/**
 * Plugin Name: Kadence Shop Kit
 * Plugin URI: https://www.kadencewp.com/product/kadence-woo-extras/
 * Description: This plugin adds extra features for WooCommerce to help improve your online shops.
 * Version: 2.4.15.1
 * Author: Kadence WP
 * Author URI: https://kadencewp.com/
 * License: GPLv2 or later
 * Text Domain: kadence-woo-extras
 * WC requires at least: 7.0.0
 * WC tested up to: 9.3
 *
 * @package Kadence WooCommerce Extras
 */

// Useful global constants.
define( 'KADENCE_WOO_EXTRAS_PATH', realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
define( 'KADENCE_WOO_EXTRAS_URL', plugin_dir_url( __FILE__ ) );
define( 'KADENCE_WOO_EXTRAS_VERSION', '2.4.15.1' );

require_once KADENCE_WOO_EXTRAS_PATH . 'vendor/vendor-prefixed/autoload.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'vendor/autoload.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'inc/uplink/Helper.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'inc/uplink/Connect.php';

require_once KADENCE_WOO_EXTRAS_PATH . 'classes/kadence-woo-extras-plugin-check.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/class-kadence-image-processing.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/class-kadence-woo-get-image.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/custom_functions.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/class-kadence-woo-duplicate-post.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'inc/class-kwe-options.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'inc/class-kadence-woo-extras-settings.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/cmb/init.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/class-kadence-woo-css.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/class-kadence-woo-google-fonts.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/cmb2-conditionals/cmb2-conditionals.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/cmb2_select2/cmb_select2.php';
require_once KADENCE_WOO_EXTRAS_PATH . 'classes/cmb2-radio-image/cmb2-radio-image.php';

use KadenceWP\KadenceShopKit\StellarWP\Telemetry\Config;
use KadenceWP\KadenceShopKit\StellarWP\Telemetry\Core as Telemetry;
use KadenceWP\KadenceShopKit\Container;
/**
 * Initalize Plugin
 */
function init_kadence_woo_extras()
{
    if (kadence_woo_extras_is_woo_active() ) {
        $shopkit_settings = get_option('kt_woo_extras');
        if (! is_array($shopkit_settings) ) {
            $shopkit_settings = json_decode($shopkit_settings, true);
        }
        include_once KADENCE_WOO_EXTRAS_PATH . 'lib/variations/kt-variations-price.php';
        if (isset($shopkit_settings['snackbar_notices']) && $shopkit_settings['snackbar_notices'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/snackbar/class-snackbar-notices.php';
        }
        if (isset($shopkit_settings['variation_swatches']) && $shopkit_settings['variation_swatches'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/swatches/kt-variations-swatches.php';
        }
        if (isset($shopkit_settings['product_templates']) && $shopkit_settings['product_templates'] ) {
            if (! kadence_woo_extras_is_kadence_blocks_active() ) {
                add_action('admin_notices', 'kadence_woo_extras_admin_notice_need_kadence_blocks');
                add_action('admin_enqueue_scripts', 'kadence_woo_extras_admin_notice_scripts');
            } else {
                // Blocks.
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/add-to-cart-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/title-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/notice-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/hooks-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/price-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/gallery-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/excerpt-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/description-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/rating-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/tabs-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/image-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/meta-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/reviews-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/additional-information-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/related-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/upsell-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/breadcrumbs-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/size-chart-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/brands-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/products-block.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/woo-block-editor-content-controller.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/woo-block-build-css-helpers.php';
                include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/woo-block-editor-templates.php';
            }
        }
        if (isset($shopkit_settings['product_gallery']) && $shopkit_settings['product_gallery'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/gallery/class-product-gallery.php';
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/variation-gallery/kadence-variation-gallery.php';
        }
        if (isset($shopkit_settings['size_charts']) && $shopkit_settings['size_charts'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/sizechart/kt-size-chart.php';
        }
        if (isset($shopkit_settings['kt_add_to_cart_text']) && $shopkit_settings['kt_add_to_cart_text'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/add_to_cart_text/kt-add-to-cart-text.php';
        }
        if (isset($shopkit_settings['kt_reviews']) && $shopkit_settings['kt_reviews'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/reviews/reviews.php';
        }
        if (isset($shopkit_settings['kt_cart_notice']) && $shopkit_settings['kt_cart_notice'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/cartnotice/kt-cart-notice.php';
        }
        if (isset($shopkit_settings['kt_product_badges_options']) && $shopkit_settings['kt_product_badges_options'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/badges/kt-badges.php';
        }
        if (isset($shopkit_settings['product_quickview']) && $shopkit_settings['product_quickview'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/quickview/class-quickview.php';
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/quickview/class-quickview-rest-controller.php';

            // Template Blocks.
            // we dont need to register them, but their render functions are needed for quickview default template
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/add-to-cart-block.php';
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/title-block.php';
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/price-block.php';
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/gallery-block.php';
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/excerpt-block.php';
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/rating-block.php';

            // Quickview blocks. 
            // These we will register and have available
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/templates/blocks/quickview-button-block.php';
        }
        if (isset($shopkit_settings['kt_extra_cat']) && $shopkit_settings['kt_extra_cat'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/extracatdesc/kt-extra-cat-desc.php';
        }
        if (isset($shopkit_settings['kt_checkout_editor']) && $shopkit_settings['kt_checkout_editor'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/checkout_editor/kt-checkout-editor.php';
            add_action(
                'before_woocommerce_init', function () {
                    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil') ) {
                        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, false);
                    }
                } 
            );
        }
        if (isset($shopkit_settings['kt_affiliate_options']) && $shopkit_settings['kt_affiliate_options'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/affiliate/kt-affiliate-options.php';
        }
        if (isset($shopkit_settings['kt_product_brands_options']) && $shopkit_settings['kt_product_brands_options'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/brands/class-kt-extra-brands.php';
        }
        if (isset($shopkit_settings['kt_coupon_modal_checkout']) && $shopkit_settings['kt_coupon_modal_checkout'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/checkout_coupon/kt-checkout-coupon.php';
        }
        if (isset($shopkit_settings['kt_global_tabs']) && $shopkit_settings['kt_global_tabs'] ) {
            include_once KADENCE_WOO_EXTRAS_PATH . 'lib/tabs/class-kadence-global-tabs.php';
        }
        /**
         * Telemetry.
         */
        Config::set_container(new Container());
        Config::set_server_url('https://telemetry.stellarwp.com/api/v1');
        Config::set_hook_prefix('kadence-shop-kit');
        Config::set_stellar_slug('kadence-shop-kit');
        Telemetry::instance()->init(__FILE__);
    }
}
add_action('plugins_loaded', 'init_kadence_woo_extras', 1);
/**
 * Function to output admin scripts.
 *
 * @param object $hook page hook.
 */
function kadence_woo_extras_admin_notice_scripts( $hook )
{
    wp_register_script('kt-woo-blocks-install', KADENCE_WOO_EXTRAS_URL . 'admin/admin-blocks-activate.js', false, KADENCE_WOO_EXTRAS_VERSION);
    wp_enqueue_style('kt-woo-blocks-install', KADENCE_WOO_EXTRAS_URL . 'admin/admin-blocks-activate.css', false, KADENCE_WOO_EXTRAS_VERSION);
}
/**
 * Admin Notice
 */
function kadence_woo_extras_admin_notice_need_kadence_blocks()
{
    if (get_transient('kadence_woo_extras_free_plugin_notice') || ! current_user_can('manage_options') ) {
        return;
    }
    $installed_plugins = get_plugins();
    if (! isset($installed_plugins['kadence-blocks/kadence-blocks.php']) ) {
        $button_label = esc_html__('Install Kadence Blocks', 'kadence-woo-extras');
        $data_action  = 'install';
    } else {
        $button_label = esc_html__('Activate Kadence Blocks', 'kadence-woo-extras');
        $data_action  = 'activate';
    }
    $install_link    = wp_nonce_url(
        add_query_arg(
            array(
                'action' => 'install-plugin',
                'plugin' => 'kadence-blocks',
            ),
            network_admin_url('update.php')
        ),
        'install-plugin_kadence-blocks'
    );
    $activate_nonce  = wp_create_nonce('activate-plugin_kadence-blocks/kadence-blocks.php');
    $activation_link = self_admin_url('plugins.php?_wpnonce=' . $activate_nonce . '&action=activate&plugin=kadence-blocks%2Fkadence-blocks.php');
    echo '<div class="notice notice-error is-dismissible kt-woo-extras-notice-wrapper">';
    // translators: %s is a link to kadence block plugin.
    echo '<p>' . sprintf(esc_html__('Woocommerce templating requires %s to be active to work.', 'kadence-woo-extras') . '</p>', '<a target="_blank" href="https://wordpress.org/plugins/kadence-blocks/">Kadence Blocks</a>');
    echo '<p class="submit">';
    echo '<a class="button button-primary kt-woo-install-blocks-btn" data-redirect-url="' . esc_url(admin_url('options-general.php?page=kadence_blocks')) . '" data-activating-label="' . esc_attr__('Activating...', 'kadence-woo-extras') . '" data-activated-label="' . esc_attr__('Activated', 'kadence-woo-extras') . '" data-installing-label="' . esc_attr__('Installing...', 'kadence-woo-extras') . '" data-installed-label="' . esc_attr__('Installed', 'kadence-woo-extras') . '" data-action="' . esc_attr($data_action) . '" data-install-url="' . esc_attr($install_link) . '" data-activate-url="' . esc_attr($activation_link) . '">' . esc_html($button_label) . '</a>';
    echo '</p>';
    echo '</div>';
    wp_enqueue_script('kt-blocks-install');
}

/**
 * Taxonomy Meta
 */
function kt_woo_extras_tax_class()
{
    if (class_exists('KT_WOO_EXTRAS_Taxonomy_Meta') ) {
        return;
    }
    include_once KADENCE_WOO_EXTRAS_PATH . 'classes/taxonomy-meta-class.php';
}
add_action('after_setup_theme', 'kt_woo_extras_tax_class', 1);

/**
 * Load Text Domain
 */
function kt_woo_extras_textdomain()
{
    load_plugin_textdomain('kadence-woo-extras', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'kt_woo_extras_textdomain');

add_action(
    'before_woocommerce_init', function () {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    } 
);
