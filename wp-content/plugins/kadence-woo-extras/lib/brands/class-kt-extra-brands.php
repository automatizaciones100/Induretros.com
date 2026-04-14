<?php
/**
 * Product Brands
 *
 * @package Kadence Woo Extras
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Start up brands on plugins loaded
 */
function kt_extra_brands_plugin_loaded() {
	/**
	 * Brand class
	 *
	 * @category class.
	 */
	class KT_Extra_Brands {
		/**
		 * Static Name
		 *
		 * @var null
		 */
		public static $name = null;
		/**
		 * Static Name Plural
		 *
		 * @var null
		 */
		public static $name_plural = null;
		/**
		 * Static Slug
		 *
		 * @var null
		 */
		public static $slug_name = null;
		/**
		 * Instance Control
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
		 * Construct Class.
		 */
		public function __construct() {
			// Get required files.
			require_once KADENCE_WOO_EXTRAS_PATH . 'lib/brands/class-kt-product-brand-widget.php';
			require_once KADENCE_WOO_EXTRAS_PATH . 'lib/brands/class-kt-filter-by-brand-widget.php';
			require_once KADENCE_WOO_EXTRAS_PATH . 'lib/brands/class-kt-active-filter-widget.php';

			add_action( 'pre_get_posts', array( $this, 'product_brand_filter' ) );
			add_filter( 'manage_edit-product_brands_columns', array( $this, 'add_brand_image_columns' ) );
			add_filter( 'manage_product_brands_custom_column', array( $this, 'kt_custom_kt_gallery_column' ), 10, 3 );
			add_action( 'init', array( $this, 'product_brands' ) );
			add_action( 'init', array( $this, 'hook_brands_into_products' ) );
			add_action( 'widgets_init', array( $this, 'init_brand_widgets' ) );
			add_action( 'admin_init', array( $this, 'kt_extra_brands_meta' ) );
			add_filter( 'woocommerce_product_filters', array( $this, 'brands_to_product_filters' ), 10 );
			add_shortcode( 'kt_product_brands', array( $this, 'output_shortcode' ) );
			add_shortcode( 'kt_product_brands_archive', array( $this, 'output_archive_shortcode' ) );
			// Export.
			add_filter( 'woocommerce_product_export_column_names', array( $this, 'add_import_export_columns' ) );
			add_filter( 'woocommerce_product_export_product_default_columns', array( $this, 'add_import_export_columns' ) );
			add_filter( 'woocommerce_product_export_product_column_product_brands', array( $this, 'export_taxonomy' ), 10, 2 );
			// Import.
			add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'map_columns' ) );
			add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array( $this, 'add_columns_to_mapping_screen' ) );
			add_filter( 'woocommerce_product_importer_parsed_data', array( $this, 'parse_taxonomy_json' ), 10, 2 );
			add_filter( 'woocommerce_product_import_inserted_product_object', array( $this, 'set_taxonomy' ), 10, 2 );
			add_filter( 'woocommerce_widget_get_current_page_url', array( $this, 'set_widget_link' ), 10, 2 );
			add_filter( 'kadence_pro_woo_active_filters_widget', array( $this, 'change_active_filters' ), 10 );
			add_action( 'woocommerce_delete_product_transients', array( $this, 'clear_brand_filter_transients' ), 10 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		}
		/**
		 * Clear brand filter transients.
		 */
		public function clear_brand_filter_transients() {
			// Clear.
			delete_transient( 'wc_layered_nav_counts_product_brands' );
		}
		/**
		 * Change Active filters widget in Kadence.
		 *
		 * @param  string $class_name the widget class name.
		 * @return string
		 */
		public function change_active_filters( $class_name ) {
			$class_name = 'KT_Active_Filters_Widget';
			return $class_name;
		}
		/**
		 * Set widget filter link.
		 *
		 * @param  string $link the product object.
		 * @param  object $widget_class the widget class.
		 * @return string
		 */
		public function set_widget_link( $link, $widget_class ) {
			if ( isset( $_GET['p_brands_filter'] ) ) {
				$link = add_query_arg( 'p_brands_filter', wc_clean( wp_unslash( $_GET['p_brands_filter'] ) ), $link );
			}
			return $link;
		}
		/**
		 * Set taxonomy.
		 *
		 * @param  object $product the product object.
		 * @param  array  $data the import data.
		 * @return array
		 */
		public function set_taxonomy( $product, $data ) {

			if ( is_a( $product, 'WC_Product' ) ) {

				if ( ! empty( $data['product_brands'] ) && is_array( $data['product_brands'] ) ) {
					wp_set_object_terms( $product->get_id(), $data['product_brands'], 'product_brands' );
				}
			}

			return $product;
		}
		/**
		 * Decode data items and parse JSON IDs.
		 *
		 * @param  array                   $parsed_data the data array.
		 * @param  WC_Product_CSV_Importer $importer the import object.
		 * @return array
		 */
		public function parse_taxonomy_json( $parsed_data, $importer ) {

			if ( ! empty( $parsed_data['product_brands'] ) ) {

				$data = $parsed_data['product_brands'];

				unset( $parsed_data['product_brands'] );

				$row_terms  = $this->explode_values( $data );

				$parsed_data['product_brands'] = array();

				foreach ( $row_terms as $row_term ) {
					$parent = null;
					$row_term = str_replace( '&gt;', '>', $row_term );
					$_terms = array_map( 'trim', explode( '>', $row_term ) );
					$total  = count( $_terms );

					foreach ( $_terms as $index => $_term ) {
						// Check if category exists. Parent must be empty string or null if doesn't exists.
						$term = term_exists( $_term, 'product_brands', $parent );
						if ( is_array( $term ) ) {
							$term_id = $term['term_id'];
							// Don't allow users without capabilities to create new categories.
						} elseif ( ! current_user_can( 'manage_product_terms' ) ) {
							break;
						} else {
							$term = wp_insert_term( $_term, 'product_brands', array( 'parent' => intval( $parent ) ) );

							if ( is_wp_error( $term ) ) {
								break; // We cannot continue if the term cannot be inserted.
							}

							$term_id = $term['term_id'];
						}

						// Only requires assign the last category.
						if ( ( 1 + $index ) === $total ) {
							$parsed_data['product_brands'][] = absint( $term_id );
						} else {
							// Store parent to be able to insert or query categories based in parent ID.
							$parent = $term_id;
						}
					}
				}
			}

			return $parsed_data;
		}
		/**
		 * Explode CSV cell values using commas by default, and handling escaped
		 * separators.
		 *
		 * @since  3.2.0
		 * @param  string $value     Value to explode.
		 * @param  string $separator Separator separating each value. Defaults to comma.
		 * @return array
		 */
		protected function explode_values( $value, $separator = ',' ) {
			$value  = str_replace( '\\,', '::separator::', $value );
			$values = explode( $separator, $value );
			$values = array_map( array( $this, 'explode_values_formatter' ), $values );

			return $values;
		}
		/**
		 * Remove formatting and trim each value.
		 *
		 * @since  3.2.0
		 * @param  string $value Value to format.
		 * @return string
		 */
		protected function explode_values_formatter( $value ) {
			return trim( str_replace( '::separator::', ',', $value ) );
		}
		/**
		 * Add automatic mapping support for custom columns.
		 *
		 * @param  array $columns the columns in table.
		 * @return array  $columns
		 */
		public function add_columns_to_mapping_screen( $columns ) {

			$columns[ self::get_name_plural() ] = 'product_brands';

			return $columns;
		}
		/**
		 * Register the 'Custom Column' column in the importer.
		 *
		 * @param  array $columns the columns in table.
		 * @return array  $columns
		 */
		public function map_columns( $columns ) {
			$columns['product_brands'] = self::get_name_plural();
			return $columns;
		}
		/**
		 * MnM contents data column content.
		 *
		 * @param  mixed      $value the export value.
		 * @param  WC_Product $product the product object.
		 * @return mixed      $value
		 */
		public function export_taxonomy( $value, $product ) {

			$terms = get_terms( array( 'object_ids' => $product->get_ID(), 'taxonomy' => 'product_brands' ) );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {

				$data = array();

				foreach ( (array) $terms as $term ) {
					$formatted_term = array();
					$ancestor_ids   = array_reverse( get_ancestors( $term->term_id, 'product_brands' ) );

					foreach ( $ancestor_ids as $ancestor_id ) {
						$parent_term = get_term( $ancestor_id, 'product_brands' );
						if ( $parent_term && ! is_wp_error( $parent_term ) ) {
							$formatted_term[] = $parent_term->name;
						}
					}

					$formatted_term[] = $term->name;

					$data[] = implode( ' > ', $formatted_term );
				}

				$value = $this->implode_values( $data );

			}

			return $value;
		}
		/**
		 * Implode CSV cell values using commas by default, and wrapping values
		 * which contain the separator.
		 *
		 * @since  3.2.0
		 * @param  array $values Values to implode.
		 * @return string
		 */
		protected function implode_values( $values ) {
			$values_to_implode = array();

			foreach ( $values as $value ) {
				$value               = (string) is_scalar( $value ) ? $value : '';
				$values_to_implode[] = str_replace( ',', '\\,', $value );
			}

			return implode( ', ', $values_to_implode );
		}
		/**
		 * Add CSV columns for exporting extra data.
		 *
		 * @param  array $columns the columns in table.
		 * @return array $columns
		 */
		public function add_import_export_columns( $columns ) {
			$columns['product_brands'] = self::get_name_plural();
			return $columns;
		}
		/**
		 * Set and return Name.
		 */
		public static function get_name() {
			// Define panels.
			if ( is_null( self::$name ) ) {
				$shopkit_settings = get_option( 'kt_woo_extras' );
				if ( ! is_array( $shopkit_settings ) ) {
					$shopkit_settings = json_decode( $shopkit_settings, true );
				}
				if ( isset( $shopkit_settings['product_brands_singular'] ) && ! empty( $shopkit_settings['product_brands_singular'] ) ) {
					self::$name = $shopkit_settings['product_brands_singular'];
				} else {
					self::$name = __( 'Product Brand', 'kadence-woo-extras' );
				}
			}
			// Return panels.
			return self::$name;
		}
		/**
		 * Set and return Name plural.
		 */
		public static function get_name_plural() {
			// Define panels.
			if ( is_null( self::$name_plural ) ) {
				$shopkit_settings = get_option( 'kt_woo_extras' );
				if ( ! is_array( $shopkit_settings ) ) {
					$shopkit_settings = json_decode( $shopkit_settings, true );
				}
				if ( isset( $shopkit_settings['product_brands_plural'] ) && ! empty( $shopkit_settings['product_brands_plural'] ) ) {
					self::$name_plural = $shopkit_settings['product_brands_plural'];
				} else {
					self::$name_plural = __( 'Product Brands', 'kadence-woo-extras' );
				}
			}
			// Return panels.
			return self::$name_plural;
		}
		/**
		 * Set and return name slug.
		 */
		public static function get_name_slug() {
			// Define panels.
			if ( is_null( self::$slug_name ) ) {
				$shopkit_settings = get_option( 'kt_woo_extras' );
				if ( ! is_array( $shopkit_settings ) ) {
					$shopkit_settings = json_decode( $shopkit_settings, true );
				}
				if ( isset( $shopkit_settings['product_brands_slug'] ) && ! empty( $shopkit_settings['product_brands_slug'] ) ) {
					self::$slug_name = sanitize_title_with_dashes( $shopkit_settings['product_brands_slug'] );
				} else {
					self::$slug_name = 'product-brands';
				}
			}
			// Return panels.
			return self::$slug_name;
		}
		/**
		 * Set and return name slug.
		 */
		public function init_brand_widgets() {
			register_widget( 'KT_Product_Brand_Widget' );
			register_widget( 'KT_Filter_By_Brand_Widget' );
			register_widget( 'KT_Active_Filters_Widget' );
		}
		/**
		 * Filter setup.
		 */
		public function product_brand_filter( $query ) {

			if ( ! empty( $_GET['kt_pb_filter'] ) ) {

				$terms_array = explode( ',', $_GET['kt_pb_filter'] );

				// remove invalid terms (security).
				for ( $i = 0; $i < count( $terms_array ); $i++ ) {
					if ( ! term_exists( $terms_array[ $i ], 'product_brands' ) ) {
						unset( $terms_array[ $i ] );
					}
				}

				$filterable_product = false;
				if ( is_product_category() || is_shop() ) {
					$filterable_product = true;
				}

				if ( $filterable_product && $query->is_main_query() ) {

					$query->set( 'tax_query', array(
						array(
							'taxonomy' => 'product_brands',
							'field'    => 'slug',
							'terms'    => $terms_array,
						),
					) );
					add_filter( 'woocommerce_is_filtered', '__return_true' );
				}
			}
			if ( ! empty( $_GET['p_brands_filter'] ) ) {

				$terms_array = explode( ',', $_GET['p_brands_filter'] );

				// remove invalid terms (security).
				for ( $i = 0; $i < count( $terms_array ); $i++ ) {
					if ( ! term_exists( $terms_array[ $i ], 'product_brands' ) ) {
						unset( $terms_array[ $i ] );
					}
				}

				$filterable_product = false;
				if ( is_product_category() || is_shop() ) {
					$filterable_product = true;
				}

				if ( $filterable_product && $query->is_main_query() ) {

					$query->set( 'tax_query', array(
						array(
							'taxonomy' => 'product_brands',
							'field'    => 'slug',
							'terms'    => $terms_array,
						),
					) );
					add_filter( 'woocommerce_is_filtered', '__return_true' );
				}
			}
		}
		/**
		 * Output Shortcode
		 */
		public function output_shortcode( $atts ) {
			$atts = shortcode_atts(
				array(
					'id' => '',
				),
				$atts
			);
			ob_start();
			$this->product_brand_output( $atts['id'] );
			$output = ob_get_clean();
			return '<div class="kt_product_brand_shortcode">' . $output . '</div>';
		}
		/**
		 * Output Shortcode
		 */
		public function output_archive_shortcode( $atts ) {
			$atts = shortcode_atts(
				array(
					'id' => '',
				),
				$atts
			);
			ob_start();
			$this->product_brand_output_archive( $atts['id'] );
			$output = ob_get_clean();
			return '<div class="kt_product_brand_archive_shortcode">' . $output . '</div>';
		}
		/**
		 * Where to hook brands in.
		 */
		public function hook_brands_into_products() {
			$shopkit_settings = get_option( 'kt_woo_extras' );
			if ( ! is_array( $shopkit_settings ) ) {
				$shopkit_settings = json_decode( $shopkit_settings, true );
			}
			if ( isset( $shopkit_settings['product_brands_single_output'] ) && ! empty( $shopkit_settings['product_brands_single_output'] ) && 'none' != $shopkit_settings['product_brands_single_output'] ) {
				if ( 'above_title' == $shopkit_settings['product_brands_single_output'] ) {
					$position = '2';
				} else if ( 'above_price' == $shopkit_settings['product_brands_single_output'] ) {
					$position = '6';
				} else if ( 'above_excerpt' == $shopkit_settings['product_brands_single_output'] ) {
					$position = '12';
				} else if ( 'above_addtocart' == $shopkit_settings['product_brands_single_output'] ) {
					$position = '22';
				} else if ( 'above_meta' == $shopkit_settings['product_brands_single_output'] ) {
					$position = '32';
				} else {
					$position = '42';
				}
				add_action( 'woocommerce_single_product_summary', array( $this, 'product_brand_output' ), $position );
			}
			if ( isset( $shopkit_settings['product_brands_archive_output'] ) && ! empty( $shopkit_settings['product_brands_archive_output'] ) && 'none' != $shopkit_settings['product_brands_archive_output'] ) {
				if ( 'above_image' == $shopkit_settings['product_brands_archive_output'] ) {
					$position = '10';
					$hook = 'woocommerce_before_shop_loop_item';
				} else if ( 'above_title' == $shopkit_settings['product_brands_archive_output'] ) {
					$position = '60';
					$hook = 'woocommerce_before_shop_loop_item_title';
				} else if ( 'above_excerpt' == $shopkit_settings['product_brands_archive_output'] ) {
					$position = '70';
					$hook = 'woocommerce_shop_loop_item_title';
				} else if ( 'above_price' == $shopkit_settings['product_brands_archive_output'] ) {
					$position = '2';
					$hook = 'woocommerce_after_shop_loop_item_title';
				} else if ( 'above_addtocart' == $shopkit_settings['product_brands_archive_output'] ) {
					$position = '7';
					$hook = 'woocommerce_after_shop_loop_item';
				} else {
					$position = '70';
					$hook = 'woocommerce_after_shop_loop_item';
				}
				add_action( $hook, array( $this, 'product_brand_output_archive' ), $position );
			}
		}
		/**
		 * Product brand output.
		 */
		public function product_brand_output( $post_id = '' ) {
			global $post, $kt_woo_extras;
			if ( empty( $post_id ) ) {
				$post_id = $post->ID;
			}
			$terms = wp_get_post_terms( $post->ID, 'product_brands' );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				if ( isset( $kt_woo_extras['product_brands_single_output_width'] ) && ! empty( $kt_woo_extras['product_brands_single_output_width'] ) ) {
					$width = $kt_woo_extras['product_brands_single_output_width'];
				} else {
					$width = '200';
				}
				if ( isset( $kt_woo_extras['product_brands_single_output_cropped'] ) && '1' == $kt_woo_extras['product_brands_single_output_cropped'] ) {
					$crop = true;
				} else {
					$crop = false;
				}
				if ( isset( $kt_woo_extras['product_brands_single_link'] ) && '1' == $kt_woo_extras['product_brands_single_link'] ) {
					$link = true;
				} else {
					$link = false;
				}
				if ( isset( $kt_woo_extras['product_brands_single_output_height'] ) && ! empty( $kt_woo_extras['product_brands_single_output_height'] ) && '1' == $kt_woo_extras['product_brands_single_output_cropped'] ) {
					$height = $kt_woo_extras['product_brands_single_output_height'];
				} else {
					$height = null;
				}
				if ( isset( $kt_woo_extras['product_brands_single_output_style'] ) && ! empty( $kt_woo_extras['product_brands_single_output_style'] ) && 'text' == $kt_woo_extras['product_brands_single_output_style'] ) {
					$style = 'text';
				} else {
					$style = 'image';
				}
				if ( 'text' == $style ) {
					echo '<div class="product-brand-wrapper">';
					echo '<span class="product-brand-label">';
					if ( count( $terms ) >= 2 ) {
						echo esc_html( self::get_name_plural() ) . ': ';
					} else {
						echo esc_html( self::get_name() ) . ': ';
					}
					echo '</span>';
					$i = 1;
					foreach ( $terms as $term ) {
						if ( 1 != $i ) {
							echo ', ';
						}
						if ( $link ) {
							echo '<a href="'.esc_url( get_term_link( $term->term_id ) ) . '" class="product-brand-text-link">';
						}
							echo esc_html( $term->name );
						if ( $link ) {
							echo '</a>';
						}
						$i ++;
					}
					echo '</div>';
				} else {
					// Old Data.
					$meta = get_option( 'product_brand_image_info' );
					if ( empty( $meta ) ) {
						$meta = array();
					}
					if ( ! is_array( $meta ) ) {
						$meta = (array) $meta;
					}
					foreach ( $terms as $term ) {
						$image_array = get_term_meta( $term->term_id, '_kt_woo_extras_brand_image', true );
						if ( ! isset( $image_array[0] ) ) {
							$data = isset( $meta[ $term->term_id ] ) ? $meta[ $term->term_id ] : array();
							$image_array = ( ! empty( $data['kt_woo_extras_brand_image'] ) ? $data['kt_woo_extras_brand_image'] : '' );
						}
						if ( isset( $image_array[0] ) ) {
							$img = kt_woo_get_image_array( $width, $height, $crop, 'attachment-shop-single', $term->name, $image_array[0] );
							echo '<div class="product-brand-image-wrapper">';
							if ( $link ) {
								echo '<a href="' . esc_url( get_term_link( $term->term_id ) ) . '" class="product-brand-link">';
							}
							echo '<img src="' . esc_url( $img['src'] ) . '" class="product-brand-image" style="max-width:' . esc_attr( $width ) . 'px" width="' . esc_attr( $img['width'] ) . '" height="' . esc_attr( $img['height'] ) . '" alt="' . esc_attr( $img['alt'] ) . '" ' . $img['srcset'] . ' />';
							if ( $link ) {
								echo '</a>';
							}
							echo '</div>';
						}
					}
				}
			}
		}
		/**
		 * Product brand archive.
		 */
		public function product_brand_output_archive( $post_id = '' ) {
			global $post, $kt_woo_extras;
			if ( empty( $post_id ) ) {
				$post_id = $post->ID;
			}
			$terms = wp_get_post_terms( $post_id, 'product_brands' );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				if ( isset( $kt_woo_extras['product_brands_archive_output_width'] ) && ! empty( $kt_woo_extras['product_brands_archive_output_width'] ) ) {
					$width = $kt_woo_extras['product_brands_archive_output_width'];
				} else {
					$width = '200';
				}
				if ( isset( $kt_woo_extras['product_brands_archive_output_cropped'] ) && '1' == $kt_woo_extras['product_brands_archive_output_cropped'] ) {
					$crop = true;
				} else {
					$crop = false;
				}
				if ( isset( $kt_woo_extras['product_brands_archive_link'] ) && '1' == $kt_woo_extras['product_brands_archive_link'] ) {
					$link = true;
				} else {
					$link = false;
				}
				if ( isset( $kt_woo_extras['product_brands_archive_output_height'] ) && ! empty( $kt_woo_extras['product_brands_archive_output_height'] ) && '1' == $kt_woo_extras['product_brands_archive_output_cropped'] ) {
					$height = $kt_woo_extras['product_brands_archive_output_height'];
				} else {
					$height = null;
				}
				if ( isset( $kt_woo_extras['product_brands_archive_output_style'] ) && ! empty( $kt_woo_extras['product_brands_archive_output_style'] ) && 'text' == $kt_woo_extras['product_brands_archive_output_style'] ) {
					$style = 'text';
				} else {
					$style = 'image';
				}
				if ( 'text' == $style ) {
					echo '<div class="product-brand-wrapper">';
					echo '<span class="product-brand-label">';
					if ( count( $terms ) >= 2 ) {
						echo esc_html( self::get_name_plural() ) . ': ';
					} else {
						echo esc_html( self::get_name() ) . ': ';
					}
					echo '</span>';
					$i = 1;
					foreach ( $terms as $term ) {
						if ( 1 != $i ) {
							echo ', ';
						}
						if ( $link ) {
							echo '<a href="' . esc_url( get_term_link( $term->term_id ) ) . '" class="product-brand-text-link">';
						}
							echo esc_html( $term->name );
						if ( $link ) {
							echo '</a>';
						}
						$i ++;
					}
					echo '</div>';
				} else {
					$meta = get_option( 'product_brand_image_info' );
					if ( empty( $meta ) ) {
						$meta = array();
					}
					if ( ! is_array( $meta ) ) {
						$meta = (array) $meta;
					}
					foreach ( $terms as $term ) {
						$image_array = get_term_meta( $term->term_id, '_kt_woo_extras_brand_image', true );
						if ( ! isset( $image_array[0] ) ) {
							$data = isset( $meta[ $term->term_id ] ) ? $meta[ $term->term_id ] : array();
							$image_array = ( ! empty( $data['kt_woo_extras_brand_image'] ) ? $data['kt_woo_extras_brand_image'] : '' );
						}
						if ( isset( $image_array[0] ) ) {
							$img = kt_woo_get_image_array( $width, $height, $crop, 'attachment-shop-single', $term->name, $image_array[0] );
							echo '<div class="product-brand-image-wrapper">';
							if ( $link ) {
								echo '<a href="' . esc_url( get_term_link( $term->term_id ) ) . '" class="product-brand-link">';
							}
							echo '<img src="' . esc_url( $img['src'] ) . '" class="product-brand-image" style="max-width:' . esc_attr( $width ) . 'px" width="' . esc_attr( $img['width'] ) . '" height="' . esc_attr( $img['height'] ) . '" alt="' . esc_attr( $img['alt'] ) . '" ' . $img['srcset'] . ' />';
							if ( $link ) {
								echo '</a>';
							}
							echo '</div>';
						}
					}
				}
			}
		}
		/**
		 * Product brand image columns.
		 *
		 * @param string $columns of admin table.
		 */
		public function add_brand_image_columns( $columns ) {
			return ( empty( $columns ) ) ? $columns : array_merge( array( 'cb' => $columns['cb'], 'kt_brand_thumbnail' => __( 'Image', 'kadence-woo-extras' ) ), $columns );
		}
		/**
		 * Product brand gallery columns.
		 *
		 * @param string $columns of admin table.
		 * @param string $column of admin table.
		 * @param string $id of brand.
		 */
		public function kt_custom_kt_gallery_column( $columns, $column, $id ) {
			if ( 'kt_brand_thumbnail' == $column ) {
				$meta = get_option( 'product_brand_image_info' );
				if ( empty( $meta ) ) {
					$meta = array();
				}
				if ( ! is_array( $meta ) ) {
					$meta = (array) $meta;
				}
				$meta = isset( $meta[ $id ] ) ? $meta[ $id ] : array();
				$image_array = get_term_meta( $id, '_kt_woo_extras_brand_image', true );
				if ( ! isset( $image_array[0] ) ) {
					$data = isset( $meta[ $id ] ) ? $meta[ $id ] : array();
					$image_array = ( ! empty( $data['kt_woo_extras_brand_image'] ) ? $data['kt_woo_extras_brand_image'] : '' );
				}
				if ( isset( $image_array[0] ) ) {
					$src = wp_get_attachment_image_src( $image_array[0], 'thumbnail' );
					if ( ! empty( $src ) ) {
						$columns .= '<img src="' . esc_url( $src[0] ) . '" alt="' . esc_attr__( 'Thumbnail', 'kadence-woo-extras' ) . '" class="wp-post-image" height="48" width="48" />';
					}
				}
			}
			return $columns;
		}
		/**
		 * Register product brands
		 */
		public function product_brands() {
			$labels = array(
				'name'                       => self::get_name_plural(),
				'singular_name'              => self::get_name(),
				'menu_name'                  => self::get_name_plural(),
				// translators: %s name of brand taxonomy plural name.
				'all_items'                  => sprintf( __( 'All %s', 'kadence-woo-extras' ), self::get_name_plural() ),
				// translators: %s name of brand taxonomy name.
				'parent_item'                => sprintf( __( 'Parent %s', 'kadence-woo-extras' ), self::get_name() ),
				// translators: %s name of brand taxonomy name.
				'parent_item_colon'          => sprintf( __( 'Parent %s :', 'kadence-woo-extras' ), self::get_name() ),
				// translators: %s name of brand taxonomy name.
				'new_item_name'              => sprintf( __( 'New %s Name', 'kadence-woo-extras' ), self::get_name() ),
				// translators: %s name of brand taxonomy name.
				'add_new_item'               => sprintf( __( 'Add New %s', 'kadence-woo-extras' ), self::get_name() ),
				// translators: %s name of brand taxonomy name.
				'edit_item'                  => sprintf( __( 'Edit %s', 'kadence-woo-extras' ), self::get_name() ),
				// translators: %s name of brand taxonomy name.
				'update_item'                => sprintf( __( 'Update %s', 'kadence-woo-extras' ), self::get_name() ),
				// translators: %s name of brand taxonomy name.
				'view_item'                  => sprintf( __( 'View %s', 'kadence-woo-extras' ), self::get_name() ),
				// translators: %s name of brand taxonomy name.
				'separate_items_with_commas' => sprintf( __( 'Separate %s With Commas', 'kadence-woo-extras' ), self::get_name() ),
				// translators: %s name of brand taxonomy name.
				'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'kadence-woo-extras' ), self::get_name() ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'kadence-woo-extras' ),
				// translators: %s name of brand taxonomy name.
				'popular_items'              => sprintf( __( 'Popular %s', 'kadence-woo-extras' ), self::get_name() ),
				// translators: %s name of brand taxonomy name.
				'search_items'               => sprintf( __( 'Search %s', 'kadence-woo-extras' ), self::get_name() ),
				'not_found'                  => __( 'Not Found', 'kadence-woo-extras' ),
			);
			$labels = apply_filters( 'kadence_woo_extras_brands_taxonomy_labels', $labels );

			$rewrite = array(
				'slug'         => self::get_name_slug(),
				'with_front'   => true,
				'hierarchical' => true,
			);

			$args = array(
				'labels'            => $labels,
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tagcloud'     => true,
				'show_in_rest'      => true,
				'rewrite'           => $rewrite,
			);

			$args = apply_filters( 'kadence_woo_extras_brands_taxonomy_args', $args );

			register_taxonomy( 'product_brands', array( 'product' ), $args );

		}
		/**
		 * Product brand meta.
		 */
		public function kt_extra_brands_meta() {
			if ( ! class_exists( 'KT_WOO_EXTRAS_Taxonomy_Meta' ) ) {
				return;
			}

			$meta_sections   = array();
			$prefix          = 'kt_woo_extras_';
			$meta_sections[] = array(
				'title'      => __( 'Brand Image', 'kadence-woo-extras' ),
				'taxonomies' => array( 'product_brands' ),
				'id'         => 'product_brand_image_info',
				'fields'     => array(
					array(
						'name' => __( 'Brand Image', 'kadence-woo-extras' ),
						'id'   => $prefix . 'brand_image',
						'type' => 'image',
					),
				),
			);

			foreach ( $meta_sections as $meta_section ) {
				new KT_WOO_EXTRAS_Taxonomy_Meta( $meta_section );
			}
		}
		/**
		 * Add admin filter by products
		 *
		 * @param string $output of current filters.
		 */
		public function brands_to_product_filters( $output ) {
			ob_start();
				global $wp_query;
				wp_dropdown_categories( array(
					'pad_counts'         => 1,
					'show_count'         => 1,
					'hierarchical'       => 1,
					'hide_empty'         => 1,
					'show_uncategorized' => 0,
					'orderby'            => 'name',
					'selected'           => isset( $wp_query->query_vars['product_brands'] ) ? $wp_query->query_vars['product_brands'] : '',
					'menu_order'         => false,
					// translators: %s name of brand taxonomy.
					'show_option_none'   => sprintf( __( 'Filter by %s', 'kadence-woo-extras' ), KT_Extra_Brands::get_name() ),
					'option_none_value'  => '',
					'value_field'        => 'slug',
					'taxonomy'           => 'product_brands',
					'name'               => 'product_brands',
					'class'              => 'dropdown_product_brands',
				) );
			$output .= ob_get_clean();

			return $output;
		}

		/**
		 * Load script for admin.
		 */
		public function enqueue_admin_scripts() {
			wp_enqueue_style( 'kadence_brands_css', KADENCE_WOO_EXTRAS_URL . 'lib/brands/css/kt_brands.css', false, KADENCE_WOO_EXTRAS_VERSION );
		}

	}
	KT_Extra_Brands::get_instance();

}
add_action( 'plugins_loaded', 'kt_extra_brands_plugin_loaded' );
