<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_action( 'plugins_loaded', 'kt_extra_cat_desc_plugin_loaded' );

function kt_extra_cat_desc_plugin_loaded() {

	class kt_extra_cat_desc {

		public function __construct() {
			add_action( 'admin_init', array( $this, 'kt_extra_cat_desc_meta' ) );
			add_action( 'woocommerce_after_shop_loop', array( $this, 'kt_output_extra_cat_desc' ), 30 );
		}
		public function kt_output_extra_cat_desc() {
			if ( is_product_category() ) {
				$cat_term_id = get_queried_object()->term_id;
				$meta = get_option( 'product_cat_extra_desc' );
				if ( empty( $meta ) ) {
					$meta = array();
				}
				if ( ! is_array( $meta ) ) {
					$meta = (array) $meta;
				}
				$meta = isset( $meta[ $cat_term_id ] ) ? $meta[ $cat_term_id ] : array();
				$desc_content = get_term_meta( $cat_term_id, '_cat_bottom_desc', true );
				if ( empty( $desc_content ) ) {
					$data = isset( $meta[ $cat_term_id ] ) ? $meta[ $cat_term_id ] : array();
					$desc_content = ( ! empty( $data['cat_bottom_desc'] ) ? $data['cat_bottom_desc'] : '' );
				}
				if ( ! empty( $desc_content ) ) { 
					echo '<div class="clearfix" style="clear:both;">' . do_shortcode( $desc_content ) . '</div>'; 
				}
			}
		}

		public function kt_extra_cat_desc_meta() {
			if ( !class_exists( 'KT_WOO_EXTRAS_Taxonomy_Meta' ) )
				return;

			$meta_sections = array();

			$meta_sections[] = array(
				'title'      => __( 'Extra Product Category Description Box', 'kadence-woo-extras' ),
				'taxonomies' => array('product_cat'),
				'id'         => 'product_cat_extra_desc',

				'fields' => array(
					array(
						'name' => __( 'Product Category Bottom Description', 'kadence-woo-extras' ),
						'desc' => __( 'Add Text here to show in the bottom of your Category page', 'kadence-woo-extras' ),
						'id'   => 'cat_bottom_desc',
						'type' => 'textarea',
					),
				),
			);

			foreach ( $meta_sections as $meta_section ) {
				new KT_WOO_EXTRAS_Taxonomy_Meta( $meta_section );
			}
		}

	}
	$GLOBALS['kt_extra_cat_desc'] = new kt_extra_cat_desc();
}

