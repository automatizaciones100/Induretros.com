<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load KT Variation Swatches
 */
function kt_variation_swatches_plugin_loaded() {
	$GLOBALS['kt_variation_swatches'] = Kadence_Variation_Swatches::get_instance();
}
add_action( 'plugins_loaded', 'kt_variation_swatches_plugin_loaded' );

/**
 * KT Variation Swatches
 */
class Kadence_Variation_Swatches {
	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;
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
	 * Constructor
	 */
	public function __construct() {
		require KADENCE_WOO_EXTRAS_PATH . '/lib/swatches/kt-variations-swatches-output.php';
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'variation_swatches_panel_tabs' ), 99 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'swatches_panel_output' ), 99 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'swatches_process_meta' ), 1, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 101 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'register_taxonomy_meta_boxes' ) );

		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'variations_form_wrap' ) );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'variations_form_wrap_end' ) );

		add_filter( 'product_attributes_type_selector', array( $this, 'add_attribute_select_types' ), 100 );
		add_action( 'woocommerce_product_option_terms', array( $this, 'manage_attribute_options'), 20, 3 );
		// Archive Swatches.
		$shopkit_settings = get_option( 'kt_woo_extras' );
		if ( ! is_array( $shopkit_settings ) ) {
			$shopkit_settings = json_decode( $shopkit_settings, true );
		}
		if ( isset( $shopkit_settings['variation_archive_swatches'] ) && true == $shopkit_settings['variation_archive_swatches'] ) {
			$hook = isset( $shopkit_settings['variation_archive_swatches_placement'] ) ? $shopkit_settings['variation_archive_swatches_placement'] : 'below_price';
			switch ( $hook ) {
				case 'above_title':
					add_action( 'kadence_woocommerce_template_loop_product_title_before', [ $this, 'archive_swatches_output' ], 5 );
					add_action( 'woocommerce_shop_loop_item_title', [ $this, 'archive_swatches_output' ], 5 );
					break;
				case 'below_title':
					add_action( 'kadence_woocommerce_template_loop_product_title_after', [ $this, 'archive_swatches_output' ], 5 );
					add_action( 'woocommerce_shop_loop_item_title', [ $this, 'archive_swatches_output' ], 5 );
					break;
				case 'above_price':
					add_action( 'kadence_woocommerce_template_loop_product_price_before', [ $this, 'archive_swatches_output' ], 5 );
					add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'archive_swatches_output' ], 8 );
					break;
				default:
					add_action( 'kadence_woocommerce_template_loop_product_price_after', [ $this, 'archive_swatches_output' ], 5 );
					add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'archive_swatches_output' ], 15 );
					break;
			}
			add_filter( 'woocommerce_loop_add_to_cart_args', [ $this, 'variable_swatch_add_to_cart_button_args' ], 10, 2 );
		}
		add_action( 'wp_ajax_nopriv_ksk_catalog_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_ksk_catalog_add_to_cart', array( $this, 'add_to_cart' ) );

		// Export.
		add_filter( 'woocommerce_product_export_column_names', array( $this, 'add_import_export_columns' ) );
		add_filter( 'woocommerce_product_export_product_default_columns', array( $this, 'add_import_export_columns' ) );
		add_filter( 'woocommerce_product_export_product_column_kt_variation_swatch_type', array( $this, 'export_swatch_type' ), 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_kt_variation_swatch_type_options', array( $this, 'export_swatch_type_options' ), 10, 2 );

		//import
		add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'map_columns' ) );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array( $this, 'add_columns_to_mapping_screen' ) );
		//add_filter( 'woocommerce_product_importer_parsed_data', array( $this, 'parse_taxonomy_json' ), 10, 2 );
		add_filter( 'woocommerce_product_import_inserted_product_object', array( $this, 'set_swatch_meta' ), 10, 2 );
		add_action( 'fkcart_quick_after_view_content', array( $this, 'activate_swatch_on_funnelkit_cart' ) );
	}
	/**
	 * AJAX add to cart.
	 */
	public static function add_to_cart() {
		ob_start();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['product_id'] ) ) {
			return;
		}

		$product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_POST['product_id'] ) );
		$product           = wc_get_product( $product_id );
		$quantity          = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) );
		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
		$product_status    = get_post_status( $product_id );
		$variation_id      = 0;
		$variation         = array();

		if ( $product && 'variation' === $product->get_type() ) {
			$variation_id = $product_id;
			$product_id   = $product->get_parent_id();
			$variation    = $product->get_variation_attributes();
			foreach ( $variation as $key => $value ) {
				if ( empty( $value ) ) {
					$variation[ $key ] = ( isset( $_POST[ $key ] ) && ! empty( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '' );
				}
			}
		}

		if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) && 'publish' === $product_status ) {

			do_action( 'woocommerce_ajax_added_to_cart', $product_id );
			do_action( 'kadence_single_product_ajax_added_to_cart', $product_id );

			// if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			// 	wc_add_to_cart_message( array( $product_id => $quantity ), true );
			// }

			WC_AJAX::get_refreshed_fragments();

		} else {

			// If there was an error adding to the cart, redirect to the product page to show any errors.
			$data = array(
				'error'       => true,
				'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id ),
			);

			wp_send_json( $data );
		}
		// phpcs:enable
	}
	/**
	 * Display the attribute on the shop page.
	 */
	public function archive_swatches_output() {
		global $product, $kt_woo_extras, $in_quickview;
		
		if ( ! $product ) {
			return;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		if ( ! $product->get_available_variations() ) {
			return;
		}

		// Check if we're in quickview context
		$is_quickview = isset( $in_quickview ) && true === $in_quickview;
	
		// If not in quickview, check if variation_archive_swatches_quickview is enabled
		// If disabled, don't show swatches in the loop
		if ( ! $is_quickview ) {
			$quickview_setting = isset( $kt_woo_extras['variation_archive_swatches_quickview'] ) ? $kt_woo_extras['variation_archive_swatches_quickview'] : false;
			if ( ! $quickview_setting ) {
				return;
			}
		}
		wp_enqueue_script( 'kadence_catalog_variation_swatches' );
		wp_enqueue_style( 'kadence_variation_swatches_css' );
		// Get Available variations?
		$get_variations       = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		$available_variations = $get_variations ? $product->get_available_variations() : false;
		$attributes           = $product->get_variation_attributes();

		$attribute_keys  = array_keys( $attributes );
		$variations_json = wp_json_encode( $available_variations );
		$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
		$hide_label = false;
		$extra_classes = '';
		if ( ( ! isset( $kt_woo_extras['variation_archive_label_placement'] ) ) || ( ! empty( $kt_woo_extras['variation_archive_label_placement'] ) && 'hidden' === $kt_woo_extras['variation_archive_label_placement'] ) ) {
			$hide_label = true;
			$extra_classes = 'kadence-sk-variation-label-hidden';
		} elseif ( ! empty( $kt_woo_extras['variation_archive_label_placement'] ) && 'above' === $kt_woo_extras['variation_archive_label_placement'] ) {
			$hide_label = true;
			$extra_classes = 'kadence-sk-variation-label-above';
		}
		if ( $hide_label ) {
			echo '<div class="kadence-sk-variation-wrap kadence-sk-variation-wrap-hide-label ' . esc_attr( $extra_classes ) . '">';
		}
		$catalog_mode = ! empty( $kt_woo_extras['variation_archive_swatches_mode'] ) && 'catalog' === $kt_woo_extras['variation_archive_swatches_mode'] ? true : false;
		$catelog_attribute = ! empty( $kt_woo_extras['variation_archive_swatches_catalog_attribute'] ) ? $kt_woo_extras['variation_archive_swatches_catalog_attribute'] : '';
		$i = 1;
		?>
		<form class="kadence_catalog_variations_form kadence_cart_variations_form variations_form" data-variation-catalog-mode="<?php echo ( $catalog_mode ? 'true' : 'false' ); ?>" data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
		<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
			<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'kadence-woo-extras' ) ) ); ?></p>
		<?php else : ?>
			<table class="variations" cellspacing="0" role="presentation">
				<tbody>
					<?php
					foreach ( $attributes as $attribute_name => $options ) :
						if ( $catalog_mode && ! empty( $catelog_attribute ) && 'pa_' . $catelog_attribute !== $attribute_name ) {
							continue;
						}
						?>
						<tr>
							<th class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) . '-' . $product->get_id() ); ?>"><?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?></label></th>
							<td class="value">
								<?php
									kt_archive_variation_swatches_attribute_options(
										array(
											'options'   => $options,
											'attribute' => $attribute_name,
											'product'   => $product,
											'id'        => esc_attr( sanitize_title( $attribute_name ) . '-' . $product->get_id() ),
										)
									);
									if ( apply_filters( 'kadence_sk_display_archive_reset_variations', false ) ) {
										echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'kadence-woo-extras' ) . '</a>' ) ) : '';
									}
								?>
							</td>
						</tr>
					<?php
					if ( $i == 1 && $catalog_mode && empty( $catelog_attribute ) ) {
						break;
					}
					$i++;
					endforeach;
					?>
				</tbody>
			</table>
		<?php endif; ?>
			</form>
		<?php
		if ( $hide_label ) {
			echo '</div>';
		}
	}
	/**
	 * Add custom class and data to the variable product loop button.
	 *
	 * @param array $args Arguments for the button.
	 * @param WC_Product $product Product object.
	 */
	public function variable_swatch_add_to_cart_button_args( $args, $product ) {
		if ( $product->is_type( 'variable' ) ) {
			$add_to_cart_text              = __( 'Add to cart', 'kadence-woo-extras' );
			$add_to_cart_text              = apply_filters( 'woocommerce_product_add_to_cart_text', $add_to_cart_text, $product );
			$add_to_cart_text              = apply_filters( 'kadence_shop_kit_swatches_add_to_cart_text', $add_to_cart_text, $product );
			$select_options_text           = $product->add_to_cart_text();
			$select_options_text           = apply_filters( 'woocommerce_product_add_to_cart_text', $select_options_text, $product );

			$args['class']                 .= ' ksk_catalog_variations_button';
			$args['attributes']['data-add_to_cart_text'] = esc_attr( $add_to_cart_text );
			$args['attributes']['data-select_options_text'] = esc_attr( $select_options_text );
		}
		return $args;
	}
	/**
	 * Manage attribute options.
	 */
	public function manage_attribute_options( $attribute_taxonomy, $i, $attribute ) {
		if ( 'select' !== $attribute_taxonomy->attribute_type ) {
			?>
			<select multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select terms', 'kadence-woo-extras' ); ?>" class="multiselect attribute_values wc-enhanced-select" name="attribute_values[<?php echo esc_attr( $i ); ?>][]">
				<?php
				$args      = array(
					'orderby'    => ! empty( $attribute_taxonomy->attribute_orderby ) ? $attribute_taxonomy->attribute_orderby : 'name',
					'hide_empty' => 0,
				);
				$all_terms = get_terms( $attribute->get_taxonomy(), apply_filters( 'woocommerce_product_attribute_terms', $args ) );
				if ( $all_terms ) {
					foreach ( $all_terms as $term ) {
						$options = $attribute->get_options();
						$options = ! empty( $options ) ? $options : array();
						echo '<option value="' . esc_attr( $term->term_id ) . '"' . wc_selected( $term->term_id, $options ) . '>' . esc_html( apply_filters( 'woocommerce_product_attribute_term_name', $term->name, $term ) ) . '</option>';
					}
				}
				?>
			</select>
			<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'kadence-woo-extras' ); ?></button>
			<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'kadence-woo-extras' ); ?></button>
			<button class="button fr plus add_new_attribute"><?php esc_html_e( 'Add new', 'kadence-woo-extras' ); ?></button>
			<?php
		}
	}
	/**
	 * Add the variation swatches types to the product data panel.
	 */
	public function add_attribute_select_types( $types ) {
		$types = array(
			'select' => __( 'Default', 'kadence-woo-extras' ),
			'dropdown' => __( 'Dropdown Select', 'kadence-woo-extras' ),
			'radio_box' => __( 'Radio Boxes', 'kadence-woo-extras' ),
			'color_image' => __( 'Image and Color swatches', 'kadence-woo-extras' ),
		);
		return $types;
	}
	/**
	 * Adds custom wrap with classes to indicate the labels should be hidden.
	 */
	public function variations_form_wrap() {
		global $product, $kt_woo_extras;
		if ( ! $product ) {
			return;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$hide_label = false;
		$extra_classes = '';
		if ( ( empty( $kt_woo_extras['variation_label_placement'] ) && isset( $kt_woo_extras['variation_label'] ) && true == $kt_woo_extras['variation_label'] ) || ( ! empty( $kt_woo_extras['variation_label_placement'] ) && 'above' === $kt_woo_extras['variation_label_placement'] ) ) {
			$hide_label = true;
			$extra_classes = 'kadence-sk-variation-label-above';
		} elseif ( ! empty( $kt_woo_extras['variation_label_placement'] ) && 'hidden' === $kt_woo_extras['variation_label_placement'] ) {
			$hide_label = true;
			$extra_classes = 'kadence-sk-variation-label-hidden';
		}
		if ( $hide_label ) {
			echo '<div class="kadence-sk-variation-wrap kadence-sk-variation-wrap-hide-label ' . esc_attr( $extra_classes ) . '">';
		}
	}
	/**
	 * Adds custom wrap with classes to indicate the labels should be hidden.
	 */
	public function variations_form_wrap_end() {
		global $product, $kt_woo_extras;
		if ( ! $product ) {
			return;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$hide_label = false;
		if ( ( empty( $kt_woo_extras['variation_label_placement'] ) && isset( $kt_woo_extras['variation_label'] ) && true == $kt_woo_extras['variation_label'] ) || ( ! empty( $kt_woo_extras['variation_label_placement'] ) && 'above' === $kt_woo_extras['variation_label_placement'] ) ) {
			$hide_label = true;
		} elseif ( ! empty( $kt_woo_extras['variation_label_placement'] ) && 'hidden' === $kt_woo_extras['variation_label_placement'] ) {
			$hide_label = true;
		}
		if ( $hide_label ) {
			echo '</div>';
		}
	}
	/**
	 * Add the variation swatches tab to the product data panel.
	 */
	public function variation_swatches_panel_tabs() {
		?>
		<li class="kt-variation-swatches-tab show_if_variable"><a href="#kt_swatches"><span><?php echo esc_html__( 'Variation Swatches', 'kadence-woo-extras' ); ?></span></a></li>
		<?php
	}
	/**
	 * Output the variation swatches panel.
	 */
	public function swatches_panel_output() {
		?>
		<div id="kt_swatches" class="panel kt-variation-swatches-content woocommerce_options_panel wc-metaboxes-wrapper">
			<div class="kt_swatches_container">
				<div class="kt_swatches_label">
					<?php echo esc_html__( 'Product variation attributes', 'kadence-woo-extras' ); ?> 
				</div>
				<?php $this->swatches_tab_output(); ?>
			</div>
		</div>
		<?php
	}
	/**
	 * Output the variation swatches tab.
	 */
	public function swatches_tab_output() {
		global $woocommerce, $post, $kt_woo_extras;
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post->ID );
		} else {
			$product = new WC_Product( $post->ID );
		}
		if ( ! $product->is_type( 'variable' ) && ! $product->is_type( 'variable-subscription' ) ) {
			return;
		}
		$kt_variation_swatch_type           = get_post_meta( $post->ID, '_kt_variation_swatch_type', true );
		$kt_variation_swatch_type_options   = get_post_meta( $post->ID, '_kt_variation_swatch_type_options', true );

		if ( ! $kt_variation_swatch_type_options ) {
			$kt_variation_swatch_type_options = array();
		}

		if ( ! $kt_variation_swatch_type ) {
			$kt_variation_swatch_type = array();
		}

		$var_product = new WC_Product_Variable( $post->ID );

		$attributes = $var_product->get_variation_attributes();

		if ( $attributes && count( $attributes ) ) :
			// Start foreach with the product attributres
			$attribute_names = array_keys( $attributes );
			foreach ( $attribute_names as $attribute_name ) {
				$key = md5( sanitize_title( $attribute_name ) );
				if ( isset( $kt_variation_swatch_type[ $key ]['display_type'] ) ) {
					$value = $kt_variation_swatch_type[ $key ]['display_type'];
				} else {
					$value = 'default';
				}
				if ( isset( $kt_variation_swatch_type[ $key ]['display_label'] ) ) {
					$label = $kt_variation_swatch_type[ $key ]['display_label'];
				} else {
					$label = 'default';
				}
				if ( isset( $kt_variation_swatch_type[ $key ]['display_size'] ) ) {
					$size = $kt_variation_swatch_type[ $key ]['display_size'];
				} else {
					$size = 'default';
				}
				if ( isset( $kt_woo_extras['swatches_type'] ) ) {
					$kt_default_type = $kt_woo_extras['swatches_type'];
				} else {
					$kt_default_type = 'dropdown';
				}
				if ( isset( $kt_woo_extras['swatches_size'] ) ) {
					$kt_default_size = $kt_woo_extras['swatches_size'];
				} else {
					$kt_default_size = '50';
				}
				// echo Each attribute label and swatch type.
				echo '<div class="kt_swatches_attribute_panel" data-default-type="' . $kt_default_type . '" data-default-size="' . $kt_default_size . '">';
					echo '<a class="kt_attribute_label">' . wc_attribute_label( $attribute_name ) . '</a>';
					echo '<div class="kt_attribute_panel">';
						woocommerce_wp_select(
							array(
								'id'      => '_kt_variation_swatch_type[' . $key . '][display_type]',
								'class'   => 'select short kt_select_swatches_type',
								'label'   => __( 'Variation Style', 'kadence-woo-extras' ),
								'options' => array(
									'default'       => __( 'Default', 'kadence-woo-extras' ),
									'dropdown'      => __( 'Dropdown', 'kadence-woo-extras' ),
									'radio_box'     => __( 'Radio Box', 'kadence-woo-extras' ),
									'color_image'   => __( 'Color and image swatches', 'kadence-woo-extras' ),
									'taxonomy'      => __( 'Taxonomy defined', 'kadence-woo-extras' ),
								),
								'value' => $value,
							)
						);
						echo '<div class="kt_attribute_extra_settings ';
				if ( $value != 'default' ) {
					echo $value == 'color_image' ? 'panel_open' : ' ';
				} else {
					echo $kt_default_type == 'color_image' ? 'panel_open' : ' ';
				}
						echo '">';
							woocommerce_wp_select(
								array(
									'id'      => '_kt_variation_swatch_type[' . $key . '][display_label]',
									'class'   => 'select short kt_select_swatches_label',
									'label'   => __( 'Display Label', 'kadence-woo-extras' ),
									'options' => array(
										'default'       => __( 'Default', 'kadence-woo-extras' ),
										'false'         => __( 'No label', 'kadence-woo-extras' ),
										'above'         => __( 'Show above', 'kadence-woo-extras' ),
										'below'         => __( 'Show below', 'kadence-woo-extras' ),
										'tooltip'       => __( 'Show above on hover', 'kadence-woo-extras' ),
									),
									'value' => $label,
								)
							);
							woocommerce_wp_select(
								array(
									'id'      => '_kt_variation_swatch_type[' . $key . '][display_size]',
									'label'   => __( 'Swatch Size', 'kadence-woo-extras' ),
									'class'   => 'select short kt_select_swatches_size',
									'options' => array(
										'default'       => __( 'Default', 'kadence-woo-extras' ),
										'16'            => __( '16x16px', 'kadence-woo-extras' ),
										'30'            => __( '30x30px', 'kadence-woo-extras' ),
										'40'            => __( '40x40px', 'kadence-woo-extras' ),
										'50'            => __( '50x50px', 'kadence-woo-extras' ),
										'60'            => __( '60x60px', 'kadence-woo-extras' ),
										'75'            => __( '75x75px', 'kadence-woo-extras' ),
										'90'            => __( '90x90px', 'kadence-woo-extras' ),
										'120'           => __( '120x120px', 'kadence-woo-extras' ),
										'150'           => __( '150x150px', 'kadence-woo-extras' ),
									),
									'value' => $size,
								)
							);
						echo '</div>';
						// Get all attribute terms selected
						$attribute_terms = array();
						if ( taxonomy_exists( $attribute_name ) ) {
							$terms = get_terms( $attribute_name, array( 'hide_empty' => false ) );
							$selected_terms = isset( $attributes[ $attribute_name ] ) ? $attributes[ $attribute_name ] : array();
							foreach ( $terms as $term ) {
								if ( in_array( $term->slug, $selected_terms ) ) {
									$attribute_terms[] = array(
										'id' => md5( $term->slug ),
										'label' => $term->name,
									);
								}
							}
						} else {
							foreach ( $attributes[ $attribute_name ] as $term ) {
								$attribute_terms[] = array(
									'id' => ( md5( sanitize_title( strtolower( $term ) ) ) ),
									'label' => esc_html( $term ),
								);
							}
						}
						echo '<div class="kt_swatches_attribute_table kt_swatches_clearfix ';
						if ( $value != 'default' ) {
							echo $value == 'color_image' ? 'panel_open' : ' ';
						} else {
							echo $kt_default_type == 'color_image' ? 'panel_open' : ' ';
						}
						echo '">';
							echo '<div class="kt_swatches_attribute_table_head kt_swatches_clearfix">';
								echo '<div class="kt_sa_preview">' . __( 'Preview', 'kadence-woo-extras' ) . '</div>';
								echo '<div class="kt_sa_name">' . __( 'Attribute', 'kadence-woo-extras' ) . '</div>';
								echo '<div class="kt_sa_type">' . __( 'Type', 'kadence-woo-extras' ) . '</div>';
							echo '</div>';
							echo '<div class="kt_swatches_attribute_table_body kt_swatches_clearfix">';
								// Loop through terms
						foreach ( $attribute_terms as $attribute_term ) :
							$attribute_term['id'] = ( $attribute_term['id'] );
							// Get image
							if ( isset( $kt_variation_swatch_type_options[ $key ][ $attribute_term['id'] ]['image'] ) ) {
								$this_attribute_image = $kt_variation_swatch_type_options[ $key ][ $attribute_term['id'] ]['image'];
							} else {
								$this_attribute_image = wc_placeholder_img_src();
							}
							// get ID
							if ( isset( $kt_variation_swatch_type_options[ $key ][ $attribute_term['id'] ]['image_id'] ) ) {
								$this_attribute_image_id = $kt_variation_swatch_type_options[ $key ][ $attribute_term['id'] ]['image_id'];
							} else {
								$this_attribute_image_id = null;
							}
							// Get thumbnail
							if ( isset( $this_attribute_image_id ) && ! empty( $this_attribute_image_id ) ) {
								$this_attribute_image_thumb = wp_get_attachment_image_src( $this_attribute_image_id, 'thumbnail' );
								$this_attribute_image_thumb = $this_attribute_image_thumb[0];
							} else {
								$this_attribute_image_thumb = wc_placeholder_img_src();
							}
							// Get color
							if ( isset( $kt_variation_swatch_type_options[ $key ][ $attribute_term['id'] ]['color'] ) ) {
								$this_attribute_color = $kt_variation_swatch_type_options[ $key ][ $attribute_term['id'] ]['color'];
							} else {
								$this_attribute_color = '#ffffff';
							}
							// Get type
							if ( isset( $kt_variation_swatch_type_options[ $key ][ $attribute_term['id'] ]['type'] ) ) {
								$this_attribute_type = $kt_variation_swatch_type_options[ $key ][ $attribute_term['id'] ]['type'];
							} else {
								$this_attribute_type = 'color';
							}
							// Get type label
							if ( $this_attribute_type == 'image' ) {
								$this_attribute_type_label = __( 'Image', 'kadence-woo-extras' );
							} else {
								$this_attribute_type_label = __( 'Color', 'kadence-woo-extras' );
							}
							echo '<div class="kt_swatches_attribute_single">';
								echo '<div class="kt_swatches_attribute_table_subhead kt_swatches_clearfix"';
							if ( $size == 'default' ) {
								if ( isset( $kt_woo_extras['swatch_size'] ) ) {
									echo 'style="line-height:' . esc_attr( $kt_woo_extras['swatch_size'] ) . 'px"';
								} else {
									// do nothing
								}
							} else {
								echo 'style="line-height:' . esc_attr( $size ) . 'px"';
							}
								echo '">';
									echo '<div class="kt_sas_preview">';
										echo '<div class="kt_sas_preview_item"';
							if ( $this_attribute_type == 'image' ) {
								echo 'style="background-image:url(' . $this_attribute_image_thumb . ');';
							} else {
								echo 'style="background-color:' . $this_attribute_color . ';';
							}
							if ( $size == 'default' ) {
								if ( isset( $kt_woo_extras['swatches_size'] ) ) {
									echo 'width:' . esc_attr( $kt_woo_extras['swatches_size'] ) . 'px;';
									echo 'height:' . esc_attr( $kt_woo_extras['swatches_size'] ) . 'px;';
								} else {
									// do nothing
								}
							} else {
								echo 'width:' . esc_attr( $size ) . 'px;';
								echo 'height:' . esc_attr( $size ) . 'px;';
							}
										echo '"></div>';
											echo '</div>';
											echo '<div class="kt_sas_name">' . $attribute_term['label'] . '</div>';
											echo '<div class="kt_sas_type">' . $this_attribute_type_label . '</div>';
											echo '</div>';
											echo '<div class="kt_swatches_attribute_single_options kt_swatches_clearfix">';
											echo '<div class="kt_swatches_attribute_single_row kt_sas_option_type">';
										woocommerce_wp_select(
											array(
												'id'      => '_kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][type]',
												'class'   => 'select short kt_select_swatches_type_single',
												'label'   => __( 'Attribute Color or Image', 'kadence-woo-extras' ),
												'options' => array(
													'color'         => __( 'Color', 'kadence-woo-extras' ),
													'image'         => __( 'Image', 'kadence-woo-extras' ),
												),
												'value' => $this_attribute_type,
											)
										);
									echo '</div>';
									echo '<div class="kt_swatches_attribute_single_row kt_sas_option_color" style="';
										echo $this_attribute_type == 'color' ? '' : 'display:none';
									echo '">';
										echo '<p class="form-field _kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][color]_field">';
											echo '<label for="_kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][color]">' . __( 'Color', 'kadence-woo-extras' ) . '</label>';
											echo '<input class="kt_swatch_color" id="_kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][color]" type="text" class="text" name="_kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][color]" value="' . $this_attribute_color . '" />';
										echo '</p>';
									echo '</div>';
									echo '<div class="kt_swatches_attribute_single_row kt_sas_option_image" style="';
										echo $this_attribute_type == 'image' ? '' : 'display:none';
									echo '">';
										echo '<p class="form-field _kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][image]_field">';
											echo '<label for="_kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][image]">' . __( 'Image', 'kadence-woo-extras' ) . '</label>';
											echo '<input class="kt_swatch_image" id="_kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][image]" type="text" class="text" name="_kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][image]" value="' . $this_attribute_image . '" />';
											echo '<input class="kt_swatch_image_id" id="_kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][image_id]" type="hidden" class="text" name="_kt_variation_swatch_type_options[' . $key . '][' . $attribute_term['id'] . '][image_id]" value="' . $this_attribute_image_id . '" />';
											echo '<a class="kt_swatches_upload_button button">' . __( 'Upload/Add image', 'kadence-woo-extras' ) . '</a>';
										echo '</p>';
									echo '</div>';
								echo '</div>';
							echo '</div>';
								endforeach;
							echo '</div>';
						echo '</div>';
						echo '</div>';
						echo '</div>';

			}

		endif;
	}
	/**
	 * Save the variation swatches data.
	 */
	public function swatches_process_meta( $post_id, $post ) {
		if ( isset( $_POST['_kt_variation_swatch_type'] ) ) {
			update_post_meta( $post_id, '_kt_variation_swatch_type', $_POST['_kt_variation_swatch_type'] );
		}
		if ( isset( $_POST['_kt_variation_swatch_type_options'] ) ) {
			update_post_meta( $post_id, '_kt_variation_swatch_type_options', $_POST['_kt_variation_swatch_type_options'] );
		}
	}
	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		global $kt_woo_extras;
		global $post;

		wp_register_style( 'kadence_variation_swatches_css', KADENCE_WOO_EXTRAS_URL . 'inc/assets/css/kt-variation-swatches.min.css', false, KADENCE_WOO_EXTRAS_VERSION );
		wp_register_script( 'kadence_variation_swatches', KADENCE_WOO_EXTRAS_URL . 'inc/assets/js/kt-variation-swatches.min.js', array( 'jquery', 'wc-add-to-cart-variation' ), KADENCE_WOO_EXTRAS_VERSION, true );
		wp_register_script( 'kadence_catalog_variation_swatches', KADENCE_WOO_EXTRAS_URL . 'inc/assets/js/kt-catalog-variation-swatches.min.js', array( 'jquery', 'wc-add-to-cart-variation' ), KADENCE_WOO_EXTRAS_VERSION, true );
		wp_localize_script(
			'kadence_catalog_variation_swatches',
			'ksk_catalog_params',
			[
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'unavailable_text'       => __( 'The selected item variant is unavailable.', 'kadence-woo-extras' ),
				'cart_url'                => apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null ),
				'cart_redirect_after_add' => get_option( 'woocommerce_cart_redirect_after_add' ),
				'i18n_view_cart'          => esc_attr__( 'View cart', 'kadence-woo-extras' ),
			]
		);
		if ( ( is_product() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) && apply_filters( 'kadence_shop_kit_swatches_scripts_enqueue', true ) ) {
			// Support Kadence classic themes.
			if ( wp_script_is( 'kt-wc-add-to-cart-variation', 'enqueued' ) ) {
				wp_dequeue_script( 'kt-wc-add-to-cart-variation' );
			}
			if ( wp_script_is( 'kt-add-to-cart-variation-radio', 'enqueued' ) ) {
				wp_dequeue_script( 'kt-add-to-cart-variation-radio' );
			}
			wp_deregister_script( 'kt-add-to-cart-variation-radio' );

			// Enqueue the scripts.
			wp_enqueue_style( 'kadence_variation_swatches_css' );
			wp_enqueue_script( 'kadence_variation_swatches' );
		} elseif ( isset( $kt_woo_extras['variation_archive_swatches'] ) && true == $kt_woo_extras['variation_archive_swatches'] && ( is_shop() || is_product_taxonomy() ) ) {
			wp_enqueue_style( 'kadence_variation_swatches_css' );
			wp_enqueue_script( 'kadence_catalog_variation_swatches' );
		}
	}
	/**
	 * Enqueue admin scripts and styles.
	 */
	public function admin_enqueue_scripts() {
		global $pagenow;
		if ( is_admin() && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' || $pagenow == 'edit-tags.php' ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'kadence_admin_swatches_css', KADENCE_WOO_EXTRAS_URL . 'inc/assets/css/kt-admin-swatches.min.css', false, KADENCE_WOO_EXTRAS_VERSION );
			wp_enqueue_script( 'kadence_admin_swatches', KADENCE_WOO_EXTRAS_URL . 'inc/assets/js/kt-admin-swatches.min.js', array( 'jquery', 'wp-color-picker' ), KADENCE_WOO_EXTRAS_VERSION, true );
		}
	}
	/**
	 * Add the swatches meta to taxonomies.
	 */
	public function register_taxonomy_meta_boxes() {
		if ( ! class_exists( 'KT_WOO_EXTRAS_Taxonomy_Meta' ) ) {
			return;
		}

		$meta_sections = array();
		$prefix = 'kt_woo_extras_';
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			$attributes = wc_get_attribute_taxonomies();
			$attribute_array = array();
			foreach ( $attributes as $tax ) {
				$attribute_array[] = 'pa_' . $tax->attribute_name;
			}
			// First meta section
			$meta_sections[] = array(
				'title'      => 'Swatch Taxonomy Image or Color Setup',
				'taxonomies' => $attribute_array,
				'id'         => 'kt_woo_extras_tax_swatch_type',

				'fields' => array(
					array(
						'name'    => __( 'Swatch Type', 'kadence-woo-extras' ),
						'id'      => $prefix . 'swatch_type',
						'type'    => 'select',
						'options' => array(
							'color' => __( 'Color', 'kadence-woo-extras' ),
							'image' => __( 'Image', 'kadence-woo-extras' ),
						),
					),
					array(
						'name' => __( 'Swatch Color', 'kadence-woo-extras' ),
						'id'   => $prefix . 'swatch_color',
						'type' => 'color',
					),
					array(
						'name' => __( 'Swatch Image', 'kadence-woo-extras' ),
						'id' => $prefix . 'swatch_image',
						'type' => 'image',
					),
				),
			);

			foreach ( $meta_sections as $meta_section ) {
				new KT_WOO_EXTRAS_Taxonomy_Meta( $meta_section );
			}
		}
	}


	/**
	 * Set swatch meta.
	 *
	 * @param  object $product the product object.
	 * @param  array  $data the import data.
	 * @return array
	 */
	public function set_swatch_meta( $product, $data ) {
		if ( is_a( $product, 'WC_Product' ) ) {
			if ( ! empty( $data['kt_variation_swatch_type'] ) ) {
				$unserialized = maybe_unserialize($data['kt_variation_swatch_type']);
				update_post_meta( $product->get_id(), '_kt_variation_swatch_type', $unserialized );
			}
			if ( ! empty( $data['kt_variation_swatch_type_options'] ) ) {
				$unserialized = maybe_unserialize($data['kt_variation_swatch_type_options']);
				update_post_meta( $product->get_id(), '_kt_variation_swatch_type_options', $unserialized );
			}
		}

		return $product;
	}
	/**
	 * Add automatic mapping support for custom columns.
	 *
	 * @param  array $columns the columns in table.
	 * @return array  $columns
	 */
	public function add_columns_to_mapping_screen( $columns ) {
		$columns[__( 'Product Swatch Type', 'kadence-woo-extras' )] = 'kt_variation_swatch_type';
		$columns[__( 'Product Swatch Type Options', 'kadence-woo-extras' )] = 'kt_variation_swatch_type_options';

		return $columns;
	}
	/**
	 * Register the 'Custom Column' column in the importer.
	 *
	 * @param  array $columns the columns in table.
	 * @return array  $columns
	 */
	public function map_columns( $columns ) {
		$columns['kt_variation_swatch_type'] = __( 'Product Swatch Type', 'kadence-woo-extras' );
		$columns['kt_variation_swatch_type_options'] = __( 'Product Swatch Type Options', 'kadence-woo-extras' );
		return $columns;
	}
	/**
	 * MnM contents data column content.
	 *
	 * @param  mixed      $value the export value.
	 * @param  WC_Product $product the product object.
	 * @return mixed      $value
	 */
	public function export_swatch_type( $value, $product ) {
		$kt_variation_swatch_type = get_post_meta( $product->id, '_kt_variation_swatch_type', true );

		return maybe_serialize( $kt_variation_swatch_type );
	}

	/**
	 * MnM contents data column content.
	 *
	 * @param  mixed      $value the export value.
	 * @param  WC_Product $product the product object.
	 * @return mixed      $value
	 */
	public function export_swatch_type_options( $value, $product ) {
		$kt_variation_swatch_type_options = get_post_meta( $product->id, '_kt_variation_swatch_type_options', true );

		return maybe_serialize( $kt_variation_swatch_type_options );
	}

	/**
	 * Add CSV columns for exporting extra data.
	 *
	 * @param  array $columns the columns in table.
	 * @return array $columns
	 */
	public function add_import_export_columns( $columns ) {
		$columns['kt_variation_swatch_type'] = __( 'Product Swatch Type', 'kadence-woo-extras' );
		$columns['kt_variation_swatch_type_options'] = __( 'Product Swatch Type Options', 'kadence-woo-extras' );
		return $columns;
	}

	/**
	 * Adds a trigger script to the funnelkit sidecart quick view that initializes swatches.
	 */
	public function activate_swatch_on_funnelkit_cart () {
		echo '<script>window.dispatchEvent(new Event("kt_fkcart_quick_view_loaded"))</script>';
	}

}
