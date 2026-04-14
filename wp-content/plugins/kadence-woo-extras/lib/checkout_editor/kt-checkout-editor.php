<?php
// This overrides woocommerce
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'kt_checkout_editor_loaded' );

function kt_checkout_editor_loaded() {

	class kt_checkout_editor {

		protected static $_instance = null;

		/**
		 * Store local fields.
		 */
		public $locale_fields = array();

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function __construct() {
			$this->locale_fields = array(
				'billing_address_1',
				'billing_address_2',
				'billing_state',
				'billing_postcode',
				'billing_city',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_state',
				'shipping_postcode',
				'shipping_city',
				'order_comments',
			);
			// Admin page
			add_action( 'admin_menu', array( $this, 'kt_checkout_admin_menu' ) );
			add_filter( 'woocommerce_screen_ids', array( $this, 'kt_add_screen_id' ) );

			// Add other fields
			add_filter( 'woocommerce_checkout_fields', array( $this, 'kt_woo_checkout_fields' ), 99, 1 );

			add_filter( 'woocommerce_get_country_locale_default', array( $this, 'kt_woo_prepare_country_locale' ) );
			add_filter( 'woocommerce_get_country_locale_base', array( $this, 'kt_woo_prepare_country_locale' ) );

			add_filter( 'woocommerce_get_country_locale', array( $this, 'kt_woo_woo_get_country_locale' ) );
			// Admin Filters
			add_filter( 'woocommerce_default_address_fields', array( $this, 'kt_woo_default_address_fields' ) );


			add_filter( 'woocommerce_admin_billing_fields', array( $this, 'admin_view_add_custom_billing_fields' ), 100, 1 );
			add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'admin_view_add_custom_shipping_fields' ), 100, 1 );

			// Frontend order filters
			add_filter( 'woocommerce_billing_fields', array( $this, 'kt_woo_admin_billing_fields' ), 100, 1 );
			add_filter( 'woocommerce_shipping_fields', array( $this, 'kt_woo_admin_shipping_fields' ), 100, 1 );
			// Admin Additional fields in order overview.
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'kt_woo_order_details_after_customer_details' ), 20, 1 );
			// Order Details view - Additional fields.
			add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'kt_woo_order_details_after_customer_details' ), 20, 1 );
			// Order Details view - Billing fields.
			add_filter( 'woocommerce_order_get_formatted_billing_address', array( $this, 'order_billing_address_details_on_account_page' ), 20, 3 );
			// Order Details view - Shipping fields.
			add_filter( 'woocommerce_order_get_formatted_shipping_address', array( $this, 'order_shipping_address_details_on_account_page' ), 20, 3 );

			add_filter( 'woocommerce_enable_order_notes_field', array( $this, 'kt_woo_enable_order_notes_field' ), 1000 );

			// Add Additional fields to Email
			add_action( 'woocommerce_email_order_meta_fields', array( $this, 'kt_woo_email_fields' ), 10, 3 );

			// Add Fields to order meta
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'kt_woo_admin_other_fields_meta' ), 10, 2 );

			// validate fields
			// add_action( 'woocommerce_after_checkout_validation', array($this, 'validate_fields' ), 10, 1 );
		}

		function kt_woo_enable_order_notes_field( $fields ) {
			$additional_fields = get_option( 'kt_custom_checkout_additional_options', array() );
			if ( ! is_array( $additional_fields ) || empty( $additional_fields ) ) {
				return $fields;
			}
			if ( is_array( $additional_fields ) ) {
				$enabled = 0;
				foreach ( $additional_fields as $field ) {
					if ( $field['enabled'] ) {
						$enabled++;
					}
				}
				return $enabled > 0 ? true : false;
			}
			return true;
		}
		function kt_woo_default_address_fields( $original ) {
			$sname = apply_filters( 'kt_woo_address_field_override_with', 'billing' );
			$address_fields = get_option( 'kt_custom_checkout_' . $sname . '_options' );
			if ( is_array( $address_fields ) && ! empty( $address_fields ) && ! empty( $original ) ) {
				$override_required = apply_filters( 'kt_woo_address_field_override_required', true );

				foreach ( $original as $name => $ofield ) {
					$fname = $sname . '_' . $name;

					if ( $this->kt_woo_is_locale_field( $fname ) && $override_required ) {
						$new_field = isset( $address_fields[ $fname ] ) ? $address_fields[ $fname ] : false;

						if ( $new_field && ! ( isset( $new_field['enabled'] ) && $new_field['enabled'] == false ) ) {
							$required = isset( $new_field['required'] ) && $new_field['required'] ? true : false;
							if ( ! $original[ $name ]['required'] && $required ) {
								$original[ $name ]['required'] = $required;
							}
						}
					}
				}
			}

			return $original;
		}
		function kt_woo_prepare_country_locale( $fields ) {
			if ( is_array( $fields ) ) {
				foreach ( $fields as $key => $props ) {
					$override_ph = apply_filters( 'kt_woo_address_field_override_placeholder', true );
					$override_label = apply_filters( 'kt_woo_address_field_override_label', true );
					$override_required = apply_filters( 'kt_woo_address_field_override_required', false );

					if ( $override_ph && isset( $props['placeholder'] ) ) {
						unset( $fields[ $key ]['placeholder'] );
					}
					if ( $override_label && isset( $props['label'] ) ) {
						unset( $fields[ $key ]['label'] );
					}
					if ( $override_required && isset( $props['required'] ) ) {
						unset( $fields[ $key ]['required'] );
					}

					if ( isset( $props['priority'] ) ) {
						unset( $fields[ $key ]['priority'] );
					}
				}
			}
			return $fields;
		}
		function kt_woo_woo_get_country_locale( $locale ) {
			if ( is_array( $locale ) ) {
				foreach ( $locale as $country => $fields ) {
					$locale[ $country ] = $this->kt_woo_prepare_country_locale( $fields );
				}
			}
			return $locale;
		}
		function kt_woo_is_locale_field( $field_name ) {
			if ( ! empty( $field_name ) && in_array(
				$field_name,
				array(
					'billing_address_1',
					'billing_address_2',
					'billing_state',
					'billing_postcode',
					'billing_city',
					'shipping_address_1',
					'shipping_address_2',
					'shipping_state',
					'shipping_postcode',
					'shipping_city',
				)
			) ) {
				return true;
			}
			return false;
		}
		function kt_woo_email_fields( $keys, $sent_to_admin, $order ) {
			$custom_keys = array();
			$fields = $this->get_fields( 'additional' );
			if ( is_object( $order ) ) {
				$order_id = $order->get_id();
			} else {
				$order_id = null;
			}
			// Loop through all custom fields to see if it should be added
			foreach ( $fields as $name => $options ) {
				$meta_key = '_' . $name;
				$enabled = ( isset( $options['enabled'] ) && $options['enabled'] == false ) ? false : true;
				$is_custom_field = ( isset( $options['custom'] ) && $options['custom'] == true ) ? true : false;
				if ( isset( $options['show_in_email'] ) && $options['show_in_email'] && $enabled && $is_custom_field ) {
					if ( is_a( $order, 'WC_Order' ) ) {
						$value = $order->get_meta( $name );
						if ( empty( $value ) ) {
							$value = $order->get_meta( $meta_key );
						}
					} else {
						$value = get_post_meta( $order_id, $name, true );
						if ( empty( $value ) ) {
							$value = get_post_meta( $order_id, $meta_key, true );
						}
					}
					if ( ! empty( $value ) ) {
						$options['label'] = apply_filters( 'wpml_translate_single_string', $options['label'], 'Kadence_Checkout_Editor', $name . '_label' );
						if ( isset( $options['options'] ) && is_array( $options['options'] ) ) {
							foreach ( $options['options'] as $key => $item_value ) {
								if ( $value === $key ) {
									$value = apply_filters( 'wpml_translate_single_string', $value, 'Kadence_Checkout_Editor', $name . '_options_' . $key );
									break;
								}
							}
						}
						$custom_keys[ $name ] = array(
							'label' => $options['label'],
							'value' => $value,
						);
					}
				}
			}

			return array_merge( $keys, $custom_keys );
		}
		/**
		 * Output the billing with the billing.
		 *
		 * @param string $address the html address.
		 * @param array  $raw_address the array of address fields.
		 * @param object $order the address order object.
		 */
		public function order_billing_address_details_on_account_page( $address, $raw_address, $order ) {
			if ( is_admin() ) {
				$order_id = $order->get_id();
				$fields   = $this->get_fields( 'billing' );
				foreach ( $fields as $name => $options ) {
					$meta_key = '_' . $name;
					$enabled = ( isset( $options['enabled'] ) && $options['enabled'] == false ) ? false : true;
					$is_custom_field = ( isset( $options['custom'] ) && $options['custom'] == true ) ? true : false;

					if ( isset( $options['show_in_order'] ) && $options['show_in_order'] && $enabled && $is_custom_field ) {
						if ( is_a( $order, 'WC_Order' ) ) {
							$value = $order->get_meta( $name );
							if ( empty( $value ) ) {
								$value = $order->get_meta( $meta_key );
							}
						} else {
							$value = get_post_meta( $order_id, $name, true );
							if ( empty( $value ) ) {
								$value = get_post_meta( $order_id, $meta_key, true );
							}
						}

						if ( ! empty( $value ) ) {
							$label = isset( $options['label'] ) && ! empty( $options['label'] ) ? __( $options['label'], 'woocommerce' ) : $name;
							$label = apply_filters( 'wpml_translate_single_string', $label, 'Kadence_Checkout_Editor', $name . '_label' );
							if ( isset( $options['options'] ) && is_array( $options['options'] ) ) {
								foreach ( $options['options'] as $key => $item_value ) {
									if ( $value === $key ) {
										$value = apply_filters( 'wpml_translate_single_string', $value, 'Kadence_Checkout_Editor', $name . '_options_' . $key );
										break;
									}
								}
							}
							$address .= '<br>' . esc_attr( $label ) . ': ' . wptexturize( $value );
						}
					}
				}
			} else {
				$order_id = $order->get_id();
				$fields   = $this->get_fields( 'billing' );
				foreach ( $fields as $name => $options ) {
					$meta_key = '_' . $name;
					$enabled = ( isset( $options['enabled'] ) && $options['enabled'] == false ) ? false : true;
					$is_custom_field = ( isset( $options['custom'] ) && $options['custom'] == true ) ? true : false;

					if ( isset( $options['show_in_order'] ) && $options['show_in_order'] && $enabled && $is_custom_field ) {
						if ( is_a( $order, 'WC_Order' ) ) {
							$value = $order->get_meta( $name );
							if ( empty( $value ) ) {
								$value = $order->get_meta( $meta_key );
							}
						} else {
							$value = get_post_meta( $order_id, $name, true );
							if ( empty( $value ) ) {
								$value = get_post_meta( $order_id, $meta_key, true );
							}
						}

						if ( ! empty( $value ) ) {
							$label = isset( $options['label'] ) && ! empty( $options['label'] ) ? __( $options['label'], 'woocommerce' ) : $name;
							$label = apply_filters( 'wpml_translate_single_string', $label, 'Kadence_Checkout_Editor', $name . '_label' );
							if ( isset( $options['options'] ) && is_array( $options['options'] ) ) {
								foreach ( $options['options'] as $key => $item_value ) {
									if ( $value === $key ) {
										$value = apply_filters( 'wpml_translate_single_string', $value, 'Kadence_Checkout_Editor', $name . '_options_' . $key );
										break;
									}
								}
							}
							$address .= '<br><span class="woocommerce-customer-details--' . esc_attr( $name ) . '">' . esc_attr( $label ) . ': ' . wptexturize( $value ) . '</span>';
						}
					}
				}
			}
			return $address;
		}
		/**
		 * Output the shipping with the shipping.
		 *
		 * @param string $address the html address.
		 * @param array  $raw_address the array of address fields.
		 * @param object $order the address order object.
		 */
		public function order_shipping_address_details_on_account_page( $address, $raw_address, $order ) {
			if ( is_admin() ) {
				$order_id = $order->get_id();
				$fields   = $this->get_fields( 'shipping' );
				foreach ( $fields as $name => $options ) {
					$meta_key = '_' . $name;
					$enabled = ( isset( $options['enabled'] ) && $options['enabled'] == false ) ? false : true;
					$is_custom_field = ( isset( $options['custom'] ) && $options['custom'] == true ) ? true : false;

					if ( isset( $options['show_in_order'] ) && $options['show_in_order'] && $enabled && $is_custom_field ) {
						if ( is_a( $order, 'WC_Order' ) ) {
							$value = $order->get_meta( $name );
							if ( empty( $value ) ) {
								$value = $order->get_meta( $meta_key );
							}
						} else {
							$value = get_post_meta( $order_id, $name, true );
							if ( empty( $value ) ) {
								$value = get_post_meta( $order_id, $meta_key, true );
							}
						}

						if ( ! empty( $value ) ) {
							$label = isset( $options['label'] ) && ! empty( $options['label'] ) ? __( $options['label'], 'woocommerce' ) : $name;
							$label = apply_filters( 'wpml_translate_single_string', $label, 'Kadence_Checkout_Editor', $name . '_label' );
							if ( isset( $options['options'] ) && is_array( $options['options'] ) ) {
								foreach ( $options['options'] as $key => $item_value ) {
									if ( $value === $key ) {
										$value = apply_filters( 'wpml_translate_single_string', $value, 'Kadence_Checkout_Editor', $name . '_options_' . $key );
										break;
									}
								}
							}
							$address .= '<br>' . esc_attr( $label ) . ': ' . wptexturize( $value );
						}
					}
				}
			} else {
				$order_id = $order->get_id();
				$fields   = $this->get_fields( 'shipping' );
				foreach ( $fields as $name => $options ) {
					$meta_key = '_' . $name;
					$enabled = ( isset( $options['enabled'] ) && $options['enabled'] == false ) ? false : true;
					$is_custom_field = ( isset( $options['custom'] ) && $options['custom'] == true ) ? true : false;

					if ( isset( $options['show_in_order'] ) && $options['show_in_order'] && $enabled && $is_custom_field ) {
						if ( is_a( $order, 'WC_Order' ) ) {
							$value = $order->get_meta( $name );
							if ( empty( $value ) ) {
								$value = $order->get_meta( $meta_key );
							}
						} else {
							$value = get_post_meta( $order_id, $name, true );
							if ( empty( $value ) ) {
								$value = get_post_meta( $order_id, $meta_key, true );
							}
						}

						if ( ! empty( $value ) ) {
							$label = isset( $options['label'] ) && ! empty( $options['label'] ) ? __( $options['label'], 'woocommerce' ) : $name;
							$label = apply_filters( 'wpml_translate_single_string', $label, 'Kadence_Checkout_Editor', $name . '_label' );
							if ( isset( $options['options'] ) && is_array( $options['options'] ) ) {
								foreach ( $options['options'] as $key => $item_value ) {
									if ( $value === $key ) {
										$value = apply_filters( 'wpml_translate_single_string', $value, 'Kadence_Checkout_Editor', $name . '_options_' . $key );
										break;
									}
								}
							}
							$address .= '<br><span class="woocommerce-customer-details--' . esc_attr( $name ) . '">' . esc_attr( $label ) . ': ' . wptexturize( $value ) . '</span>';
						}
					}
				}
			}
			return $address;
		}
		function kt_woo_order_details_after_customer_details( $order ) {
			$order_id = $order->get_id();

			$fields = $this->get_fields( 'additional' );

			// Loop through all custom fields to see if it should be added
			foreach ( $fields as $name => $options ) {
				$meta_key = '_' . $name;
				$enabled = ( isset( $options['enabled'] ) && $options['enabled'] == false ) ? false : true;
				$is_custom_field = ( isset( $options['custom'] ) && $options['custom'] == true ) ? true : false;

				if ( isset( $options['show_in_order'] ) && $options['show_in_order'] && $enabled && $is_custom_field ) {
					if ( is_a( $order, 'WC_Order' ) ) {
						$value = $order->get_meta( $name );
						if ( empty( $value ) ) {
							$value = $order->get_meta( $meta_key );
						}
					} else {
						$value = get_post_meta( $order_id, $name, true );
						if ( empty( $value ) ) {
							$value = get_post_meta( $order_id, $meta_key, true );
						}
					}

					if ( ! empty( $value ) ) {
						$label = isset( $options['label'] ) && ! empty( $options['label'] ) ? __( $options['label'], 'woocommerce' ) : $name;
						$label = apply_filters( 'wpml_translate_single_string', $label, 'Kadence_Checkout_Editor', $name . '_label' );
						if ( isset( $options['options'] ) && is_array( $options['options'] ) ) {
							foreach ( $options['options'] as $key => $item_value ) {
								if ( $value === $key ) {
									$value = apply_filters( 'wpml_translate_single_string', $value, 'Kadence_Checkout_Editor', $name . '_options_' . $key );
									break;
								}
							}
						}
						echo '<p class="woocommerce-customer-details--' . esc_attr( $name ) . '">' . esc_attr( $label ) . ': ' . wptexturize( $value ) . '</p>';
					}
				}
			}
		}
		function kt_woo_admin_other_fields_meta( $order_id, $posted ) {
			//used to do all three field types, but this lead to a situation where billing and shipping fields were being saved without and without a leading underscore
			//now just additional fields
			// $types = array( 'billing', 'shipping', 'additional' );
			$types = array( 'additional' );
			$order = wc_get_order( $order_id );
			foreach ( $types as $type ) {
				$fields = $this->get_fields( $type );

				foreach ( $fields as $name => $field ) {
					if ( isset( $field['custom'] ) && $field['custom'] && isset( $posted[ $name ] ) ) {
						$value = wc_clean( $posted[ $name ] );
						if ( $value ) {
							if ( is_a( $order, 'WC_Order' ) ) {
								$order->update_meta_data( $name, $value );
								$order->save();
							} else {
								update_post_meta( $order_id, $name, $value );
							}
						}
					}
				}
			}
		}
		function kt_woo_checkout_fields( $fields ) {
			$additional_fields = get_option( 'kt_custom_checkout_additional_options', array() );
			if ( ! is_array( $additional_fields ) || empty( $additional_fields ) ) {
				return $fields;
			}

			if ( isset( $fields['order'] ) && is_array( $fields['order'] ) ) {
				$fields['order'] = $additional_fields + $fields['order'];
			}

			// check if order_comments is enabled/disabled
			if ( isset( $additional_fields ) && ! $additional_fields['order_comments']['enabled'] ) {
				unset( $fields['order']['order_comments'] );
			}

			if ( isset( $fields['order'] ) && is_array( $fields['order'] ) ) {
				$fields['order'] = $this->kt_woo_prepare_checkout_fields( $fields['order'], false );
			}

			return $fields;
		}
		public function admin_view_add_custom_billing_fields( $original_fields ) {
			$fields = get_option( 'kt_custom_checkout_billing_options', array() );
			if ( ! is_array( $fields ) || empty( $fields ) ) {
				return $original_fields;
			}
			return $this->kt_woo_prepare_admin_view_checkout_fields( $fields, $original_fields );
		}
		public function admin_view_add_custom_shipping_fields( $original_fields ) {
			$fields = get_option( 'kt_custom_checkout_shipping_options', array() );
			if ( ! is_array( $fields ) || empty( $fields ) ) {
				return $original_fields;
			}
			return $this->kt_woo_prepare_admin_view_checkout_fields( $fields, $original_fields );
		}
		/**
		 * Check if the string starts with 'billing_' and remove it if it does.
		 */
		public function remove_billing_prefix( $string ) {
			// Check if the string starts with 'billing_'.
			if ( substr( $string, 0, 8 ) === 'billing_' ) {
				// Remove 'billing_' from the start.
				return substr( $string, 8 );
			}
		
			// Return the original string if it doesn't start with 'billing_'
			return $string;
		}
		/**
		 * Check if the string starts with 'billing_' and remove it if it does.
		 */
		public function remove_shipping_prefix( $string ) {
			// Check if the string starts with 'shipping_'.
			if ( substr( $string, 0, 9 ) === 'shipping_' ) {
				// Remove 'shipping_' from the start.
				return substr( $string, 9 );
			}
		
			// Return the original string if it doesn't start with 'shipping_'
			return $string;
		}
		/**
		 * Merge fields together 'shipping_' and remove it if it does.
		 *
		 * @param string $string The string to check.
		 * @return string The string without the 'shipping_' prefix.
		 */
		public function kt_woo_prepare_admin_view_checkout_fields( $fields, $original_fields ) {
			$merged_fields = array();
			foreach ( $fields as $name => $field ) {

				if ( isset( $field['enabled'] ) && $field['enabled'] == false ) {
					unset( $fields[ $name ] );
				} else {
					$slug = $this->remove_billing_prefix( $name );
					$slug = $this->remove_shipping_prefix( $slug );
					$is_new_field = true;
					if ( $original_fields && isset( $original_fields[ $slug ] ) ) {
						$is_new_field = false;
						$new_field = $original_fields[ $slug ];

						$new_field['label'] = isset( $field['label'] ) ? $field['label'] : $new_field['label'];
					} else {
						$new_field = array();
						$new_field['label'] = isset( $field['label'] ) ? $field['label'] : '';
						if ( isset( $field['type'] ) ) {
							$new_field['type'] = $field['type'];
						}
						$new_field['show'] = true;

						if ( isset( $new_field['label'] ) ) {
							$new_field['label'] = apply_filters( 'wpml_translate_single_string', $new_field['label'], 'Kadence_Checkout_Editor', $name . '_label' );
							$new_field['label'] = __( $new_field['label'], 'woocommerce' );
						}
						if ( isset( $field ['options'] ) && is_array( $field ['options'] ) ) {
							foreach ( $field ['options'] as $key => $value ) {
								$new_field['options'][ $key ] = apply_filters( 'wpml_translate_single_string', $value, 'Kadence_Checkout_Editor', $name . '_options_' . $key );
							}
						}
					}

					$merged_fields[ ( $slug ) ] = $new_field;
				}
			}
			return $merged_fields;
		}
		function kt_woo_admin_billing_fields( $original_fields ) {
			$fields = get_option( 'kt_custom_checkout_billing_options', array() );

			if ( ! is_array( $fields ) || empty( $fields ) ) {
				return $original_fields;
			}
			return $this->kt_woo_prepare_checkout_fields( $fields, $original_fields );
		}
		function kt_woo_admin_shipping_fields( $original_fields ) {
			$fields = get_option( 'kt_custom_checkout_shipping_options', array() );

			if ( ! is_array( $fields ) || empty( $fields ) ) {
				return $original_fields;
			}
			return $this->kt_woo_prepare_checkout_fields( $fields, $original_fields );
		}
		
		function kt_woo_prepare_checkout_fields( $fields, $original_fields ) {
			foreach ( $fields as $name => $field ) {

				if ( isset( $field['enabled'] ) && $field['enabled'] == false ) {
					unset( $fields[ $name ] );
				} else {
					$new_field = false;
					if ( $original_fields && isset( $original_fields[ $name ] ) ) {
						$new_field = $original_fields[ $name ];

						$new_field['label'] = isset( $field['label'] ) ? $field['label'] : '';
						$new_field['placeholder'] = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

						$new_field['class'] = isset( $field['class'] ) && is_array( $field['class'] ) ? $field['class'] : array();
						$new_field['label_class'] = isset( $field['label_class'] ) && is_array( $field['label_class'] ) ? $field['label_class'] : array();
						$new_field['validate'] = isset( $field['validate'] ) && is_array( $field['validate'] ) ? $field['validate'] : array();
						$required_change = isset( $field['required'] ) && $field['required'] ? true : false;
						$current_required = isset( $new_field['required'] ) && $new_field['required'] ? true : false;
						if ( $this->is_default_field_name( $name ) && ( $current_required !== $required_change ) ) {
							if ( $required_change && in_array( $name, array( 'billing_city', 'billing_state', 'billing_postcode', 'shipping_city', 'shipping_state', 'shipping_postcode' ) ) ) {
								// Do nothing because these fields can't be set to "required" because they can be hidden based on country.
							} else {
								$new_field['required'] = $required_change;
							}
						}
						$new_field['clear'] = isset( $field['clear'] ) ? $field['clear'] : 0;
					} else {
						$new_field = $field;
					}

					$new_field['order'] = isset( $field['order'] ) && is_numeric( $field['order'] ) ? $field['order'] : 0;
					if ( isset( $new_field['order'] ) && is_numeric( $new_field['order'] ) ) {
						$new_field['priority'] = $new_field['order'];
					}

					if ( isset( $new_field['label'] ) ) {
						$new_field['label'] = apply_filters( 'wpml_translate_single_string', $new_field['label'], 'Kadence_Checkout_Editor', $name . '_label' );
						$new_field['label'] = __( $new_field['label'], 'woocommerce' );
					}
					if ( isset( $new_field['placeholder'] ) ) {
						$new_field['placeholder'] = apply_filters( 'wpml_translate_single_string', $new_field['placeholder'], 'Kadence_Checkout_Editor', $name . '_placeholder' );
						$new_field['placeholder'] = __( $new_field['placeholder'], 'woocommerce' );
					}
					if ( isset( $new_field['options'] ) && is_array( $new_field['options'] ) ) {
						foreach ( $new_field['options'] as $key => $value ) {
							$new_field['options'][ $key ] = apply_filters( 'wpml_translate_single_string', $value, 'Kadence_Checkout_Editor', $name . '_options_' . $key );
						}
					}

					$fields[ $name ] = $new_field;
				}
			}
			return $fields;
		}
		function kt_add_screen_id( $ids ) {
			$ids[] = 'woocommerce_page_kt-checkout-manager';
			$ids[] = strtolower( __( 'WooCommerce', 'kadence-woo-extras' ) ) . '_page_kt-checkout-manager';

			return $ids;
		}
		public function kt_checkout_admin_menu() {
			$page = add_submenu_page(
				'woocommerce',
				__( 'Checkout Manager', 'kadence-woo-extras' ),
				__( 'Checkout Manager', 'kadence-woo-extras' ),
				'manage_woocommerce',
				'kt-checkout-manager',
				array( $this, 'kt_checkout_admin_page' )
			);

			add_action( 'admin_print_scripts-' . $page, array( $this, 'kt_checkout_admin_scripts' ) );
			add_action( 'admin_print_styles-' . $page, array( $this, 'kt_checkout_admin_css' ) );
		}
		function render_checkout_fields_heading_row() {
			?>
			<th class="sort"></th>
			<th class="check-column"></th>
			<th class="name">Name</th>
			<th class="id">Type</th>
			<th>Label</th>
			<th>Placeholder</th>
			<th>Validation Rules</th>
			<th class="status">Required</th>
			<th class="status">Clear Row</th>
			<th class="status">Enabled</th>	
			<th class="status">Edit</th>	
			<?php
		}
		function render_actions_row( $section ) {
			?>
			<th colspan="7">
				<button type="button" class="button button-primary" onclick="openNewFieldForm('<?php echo $section; ?>')"><?php _e( '+ Add field', 'kadence-woo-extras' ); ?></button>
				<button type="button" class="button" onclick="removeSelectedFields()"><?php _e( 'Remove', 'kadence-woo-extras' ); ?></button>
				<button type="button" class="button" onclick="enableSelectedFields()"><?php _e( 'Enable', 'kadence-woo-extras' ); ?></button>
				<button type="button" class="button" onclick="disableSelectedFields()"><?php _e( 'Disable', 'kadence-woo-extras' ); ?></button>
			</th>
			<th colspan="4">
				<input type="submit" name="kt_woo_checkout_settings_save" class="button-primary" value="<?php _e( 'Save changes', 'kadence-woo-extras' ); ?>" style="float:right" />
				<input type="submit" name="kt_woo_checkout_settings_reset" class="button" value="<?php _e( 'Reset to default fields', 'kadence-woo-extras' ); ?>" style="float:right; margin-right: 5px;" />
			</th>  
			<?php
		}
		public function kt_checkout_admin_page() {
			?>
			<div class="wrap woocommerce">
				<h2><?php _e( 'Checkout Manager', 'kadence-woo-extras' ); ?></h2>
				<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
					<?php
						$section = ( ! empty( $_GET['section'] ) ) ? $_GET['section'] : 'billing';
						$sections = array(
							'billing'  => __( 'Billing Fields', 'kadence-woo-extras' ),
							'shipping' => __( 'Shipping Fields', 'kadence-woo-extras' ),
							'additional'    => __( 'Extra Fields', 'kadence-woo-extras' ),
						);
						foreach ( $sections as $stab => $title ) {
							$class = ( $stab == $section ) ? ' nav-tab nav-tab-active' : 'nav-tab';
							echo '<a href="' . admin_url( 'admin.php?page=kt-checkout-manager&amp;section=' . esc_attr( $stab ) ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $title ) . '</a>  ';
						}
						?>
				</nav>
				<?php
				if ( isset( $_POST['kt_woo_checkout_settings_save'] ) && check_admin_referer( 'kt_woo_checkout_settings', 'kt_woo_checkout_noncename' ) ) {
					echo $this->save_checkout_fields( $section );
				}

				if ( isset( $_POST['kt_woo_checkout_settings_reset'] ) && check_admin_referer( 'kt_woo_checkout_settings', 'kt_woo_checkout_noncename' ) ) {
					$this->reset_checkout_fields();
					?>
						<div id="message" class="updated fade">
							<p><?php echo sprintf( __( '%1$s Settings Reset', 'kadence-woo-extras' ), ucfirst( $section ) ); ?></p>
						</div>
						
						<?php
				}
				switch ( $section ) {
					case 'billing':
						?>
								<h2><?php _e( 'Billing Settings', 'kadence-woo-extras' ); ?></h2>
							<?php
						break;

					case 'shipping':
						?>
								<h2><?php _e( 'Shipping Settings', 'kadence-woo-extras' ); ?></h2>
							<?php
						break;
					case 'additional':
						?>
								<h2><?php _e( 'Extra Fields', 'kadence-woo-extras' ); ?></h2>
							<?php
						break;
				}
				?>
					<form method="post" id="kt_checkout_fields_form" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>">
						<?php wp_nonce_field( 'kt_woo_checkout_settings', 'kt_woo_checkout_noncename' ); ?>
						<input type="hidden" name="kt_woo_section" value="<?php echo esc_attr( $section ); ?>"/>
						<table id="kt_checkout_fields" class="wc_gateways widefat" cellspacing="0">
							<thead>
								<tr><?php $this->render_actions_row( $section ); ?></tr>
								<tr><?php $this->render_checkout_fields_heading_row(); ?></tr>						
							</thead>
							<tfoot>
								<tr><?php $this->render_checkout_fields_heading_row(); ?></tr>
								<tr><?php $this->render_actions_row( $section ); ?></tr>
							</tfoot>
							<tbody class="ui-sortable">
							<?php
							$i = 0;
							foreach ( $this->get_fields( $section ) as $name => $options ) :
								if ( isset( $options['custom'] ) && $options['custom'] == 1 ) {
									$options['custom'] = '1';
								} else {
									$options['custom'] = '0';
								}

								if ( ! isset( $options['label'] ) ) {
									$options['label'] = '';
								}

								if ( ! isset( $options['placeholder'] ) ) {
									$options['placeholder'] = '';
								}

								if ( isset( $options['options'] ) && is_array( $options['options'] ) ) {
									$options['options'] = implode( '|', $options['options'] );
								} else {
									$options['options'] = '';
								}

								if ( isset( $options['class'] ) && is_array( $options['class'] ) ) {
									$options['class'] = implode( ',', $options['class'] );
								} else {
									$options['class'] = '';
								}

								if ( isset( $options['label_class'] ) && is_array( $options['label_class'] ) ) {
									$options['label_class'] = implode( ',', $options['label_class'] );
								} else {
									$options['label_class'] = '';
								}

								if ( isset( $options['validate'] ) && is_array( $options['validate'] ) ) {
									$options['validate'] = implode( ',', $options['validate'] );
								} else {
									$options['validate'] = '';
								}

								if ( isset( $options['required'] ) && $options['required'] == 1 ) {
									$options['required'] = '1';
								} else {
									$options['required'] = '0';
								}

								if ( isset( $options['clear'] ) && $options['clear'] == 1 ) {
									$options['clear'] = '1';
								} else {
									$options['clear'] = '0';
								}

								if ( ! isset( $options['enabled'] ) || $options['enabled'] == 1 ) {
									$options['enabled'] = '1';
								} else {
									$options['enabled'] = '0';
								}

								if ( ! isset( $options['type'] ) ) {
									$options['type'] = 'text';
								}

								if ( isset( $options['show_in_email'] ) && $options['show_in_email'] == 1 ) {
									$options['show_in_email'] = '1';
								} else {
									$options['show_in_email'] = '0';
								}

								if ( isset( $options['show_in_order'] ) && $options['show_in_order'] == 1 ) {
									$options['show_in_order'] = '1';
								} else {
									$options['show_in_order'] = '0';
								}
								?>
								<tr class="row_<?php echo $i; ?><?php echo( $options['enabled'] == 1 ? '' : ' kt-woo-disabled' ); ?>">
									<td width="1%" class="sort ui-sortable-handle">
										<span class="dashicons dashicons-move"></span>
										<input type="hidden" name="f_custom[<?php echo $i; ?>]" class="f_custom" value="<?php echo $options['custom']; ?>" />
										<input type="hidden" name="f_order[<?php echo $i; ?>]" class="f_order" value="<?php echo $i; ?>" />
																										
										<input type="hidden" name="f_name[<?php echo $i; ?>]" class="f_name" value="<?php echo esc_attr( $name ); ?>" />
										<input type="hidden" name="f_name_new[<?php echo $i; ?>]" class="f_name_new" value="" />
										<input type="hidden" name="f_type[<?php echo $i; ?>]" class="f_type" value="<?php echo $options['type']; ?>" />                                
										<input type="hidden" name="f_label[<?php echo $i; ?>]" class="f_label" value="<?php echo $options['label']; ?>" />
										<input type="hidden" name="f_placeholder[<?php echo $i; ?>]" class="f_placeholder" value="<?php echo $options['placeholder']; ?>" />
										<input type="hidden" name="f_options[<?php echo $i; ?>]" class="f_options" value="<?php echo( $options['options'] ); ?>" />
										
										<input type="hidden" name="f_class[<?php echo $i; ?>]" class="f_class" value="<?php echo $options['class']; ?>" />
										<input type="hidden" name="f_label_class[<?php echo $i; ?>]" class="f_label_class" value="<?php echo $options['label_class']; ?>" />
																		
										<input type="hidden" name="f_required[<?php echo $i; ?>]" class="f_required" value="<?php echo( $options['required'] ); ?>" />
										<input type="hidden" name="f_clear[<?php echo $i; ?>]" class="f_clear" value="<?php echo( $options['clear'] ); ?>" />
																		
										<input type="hidden" name="f_enabled[<?php echo $i; ?>]" class="f_enabled" value="<?php echo( $options['enabled'] ); ?>" />
										<input type="hidden" name="f_validation[<?php echo $i; ?>]" class="f_validation" value="<?php echo( $options['validate'] ); ?>" />
										<input type="hidden" name="f_show_in_email[<?php echo $i; ?>]" class="f_show_in_email" value="<?php echo( $options['show_in_email'] ); ?>" />
										<input type="hidden" name="f_show_in_order[<?php echo $i; ?>]" class="f_show_in_order" value="<?php echo( $options['show_in_order'] ); ?>" />
										<input type="hidden" name="f_deleted[<?php echo $i; ?>]" class="f_deleted" value="0" />
										
									</td>
									<td class="td_select"><input type="checkbox" name="select_field"/></td>
									<td class="td_name"><?php echo esc_attr( $name ); ?></td>
									<td class="td_type"><?php echo $options['type']; ?></td>
									<td class="td_label"><?php echo $options['label']; ?></td>
									<td class="td_placeholder"><?php echo $options['placeholder']; ?></td>
									<td class="td_validate"><?php echo $options['validate']; ?></td>
									<td class="td_required status"><?php echo( $options['required'] == 1 ? '<span class="status-enabled tips">Yes</span>' : '-' ); ?></td>
									<td class="td_clear status"><?php echo( $options['clear'] == 1 ? '<span class="status-enabled tips">Yes</span>' : '-' ); ?></td>
									<td class="td_enabled status"><?php echo( $options['enabled'] == 1 ? '<span class="status-enabled tips">Yes</span>' : '-' ); ?></td>
									<td class="td_edit">
										<button type="button" class="f_edit_btn" <?php echo( $options['enabled'] == 1 ? '' : 'disabled' ); ?> 
										onclick="openEditFieldForm(this,<?php echo $i; ?>)"><?php _e( 'Edit', 'kadence-woo-extras' ); ?></button>
									</td>
								</tr>
								<?php
								$i++;
endforeach;
							?>
							</tbody>
						</table> 
					</form>
				<?php
				$this->kt_woo_new_checkout_field_form();
				$this->kt_woo_edit_field_form();
				?>
			</div>
			<?php
		}
		function get_field_types() {
			return array(
				'text' => 'Text',
				'password' => 'Password',
				'textarea' => 'Textarea',
				'checkbox' => 'Checkbox',
				'radio' => 'Radio',
				'select' => 'Select',
			);
		}
		function kt_woo_new_checkout_field_form() {
			$field_types = $this->get_field_types();
			?>
			<div id="kt_woo_new_field_form" title="New Checkout Field" class="kt_woo_popup_wrapper">
			  <form>
				  <table>
					<tr>                
						<td colspan="2" class="err_msgs"></td>
					</tr>
					<tr>                    
						<td width="40%">Type</td>
						<td>
							<select name="ftype" style="width:150px;" onchange="fieldTypeChangeListner(this)">
							<?php foreach ( $field_types as $value => $label ) { ?>
								<option value="<?php echo trim( $value ); ?>"><?php echo $label; ?></option>
							<?php } ?>
							</select>
						</td>
					</tr>
					<tr>                
						<td>Name</td>
						<td><input type="text" name="fname" style="width:250px;"/></td>
					</tr>                
					<tr>
						<td>Label</td>
						<td><input type="text" name="flabel" style="width:250px;"/></td>
					</tr>
					<tr class="rowPlaceholder">                    
						<td>Placeholder</td>
						<td><input type="text" name="fplaceholder" style="width:250px;"/></td>
					</tr>
					<tr class="rowOptions">                    
						<td>Options</td>
						<td><input type="text" name="foptions" placeholder="Seperate options with pipe(|)" style="width:250px;"/></td>
					</tr>
					<tr class="rowClass">
						<td>Class</td>
						<td><input type="text" name="fclass" placeholder="Seperate classes with comma" style="width:250px;"/></td>
					</tr>
					<!--<tr class="rowLabelClass">
						<td>Label Class</td>
						<td><input type="text" name="flabelclass" placeholder="Seperate classes with comma" style="width:250px;"/></td>
					</tr>-->                                   
					<tr class="rowValidate">                    
						<td>Validation</td>
						<td>
							<select multiple="multiple" name="fvalidate" placeholder="Select validations" class="kt-enhanced-multi-select" 
							style="width: 250px; height:30px;">
								<option value="email">Email</option>
								<option value="phone">Phone</option>
							</select>
						</td>
					</tr>  
					<tr class="rowRequired">
						<td>&nbsp;</td>                     
						<td>                    	
							<input type="checkbox" name="frequired" value="yes" checked/>
							<label>Required</label><br/>
													
							<input type="checkbox" name="fclearRow" value="yes" checked/>
							<label>Clear Row</label><br/>
													
							<input type="checkbox" name="fenabled" value="yes" checked/>
							<label>Enabled</label>
						</td>
					</tr>      
					<tr class="rowShowInEmail"> 
						<td>&nbsp;</td>                   
						<td>                    	
							<input type="checkbox" name="fshowinemail" value="email" checked/>
							<label>Display in Emails</label>
						</td>
					</tr> 
					<tr class="rowShowInOrder"> 
						<td>&nbsp;</td>                   
						<td>                    	
							<input type="checkbox" name="fshowinorder" value="order-review" checked/>
							<label>Display in Order Detail Pages</label>
						</td>
					</tr>                           
				</table>
			  </form>
			</div>
			<?php
		}
		function kt_woo_edit_field_form() {
			$field_types = $this->get_field_types();
			?>
			<div id="kt_woo_edit_field_form" title="Edit Checkout Field" class="kt_woo_popup_wrapper">
			  <form>
				  <table>
					<tr>                
						<td colspan="2" class="err_msgs"></td>
					</tr>
					<tr>                
						<td width="40%">Name</td>
						<td>
							<input type="hidden" name="rowId"/>
							<input type="hidden" name="fname"/>
							<input type="text" name="fnameNew" style="width:250px;"/>
						</td>
					</tr>
					<tr>                   
						<td>Type</td>
						<td>
							<select name="ftype" style="width:150px;" onchange="fieldTypeChangeListner(this)">
							<?php foreach ( $field_types as $value => $label ) { ?>
								<option value="<?php echo trim( $value ); ?>"><?php echo $label; ?></option>
							<?php } ?>
							</select>
						</td>
					</tr>                
					<tr>
						<td>Label</td>
						<td><input type="text" name="flabel" style="width:250px;"/></td>
					</tr>
					<tr class="rowPlaceholder">                    
						<td>Placeholder</td>
						<td><input type="text" name="fplaceholder" style="width:250px;"/></td>
					</tr>
					<tr class="rowOptions">                    
						<td>Options</td>
						<td><input type="text" name="foptions" placeholder="Seperate options with pipe(|)" style="width:250px;"/></td>
					</tr>                
					<tr class="rowClass">
						<td>Class</td>
						<td><input type="text" name="fclass" placeholder="Seperate classes with comma" style="width:250px;"/></td>
					</tr>
					<!--<tr class="rowLabelClass">
						<td>Label Class</td>
						<td><input type="text" name="flabelclass" placeholder="Seperate classes with comma" style="width:250px;"/></td>
					</tr>-->                                   
					<tr class="rowValidate">                    
						<td>Validation</td>
						<td>
							<select multiple="multiple" name="fvalidate" placeholder="Select validations" class="kt-enhanced-multi-select" 
							style="width: 250px; height:30px;">
								<option value="email">Email</option>
								<option value="phone">Phone</option>
							</select>
						</td>
					</tr>  
					<tr class="rowRequired">  
						<td>&nbsp;</td>                     
						<td>                    	
							<input type="checkbox" name="frequired" value="yes" checked/>
							<label>Required</label><br/>
													
							<input type="checkbox" name="fclearRow" value="yes" checked/>
							<label>Clear Row</label><br/>
													
							<input type="checkbox" name="fenabled" value="yes" checked/>
							<label>Enabled</label>
						</td>                    
					</tr>  
					<tr class="rowShowInEmail"> 
						<td>&nbsp;</td>                   
						<td>                    	
							<input type="checkbox" name="fshowinemail" value="email" checked/>
							<label>Display in Emails</label>
						</td>
					</tr> 
					<tr class="rowShowInOrder"> 
						<td>&nbsp;</td>                   
						<td>                    	
							<input type="checkbox" name="fshowinorder" value="order-review" checked/>
							<label>Display in Order Detail Pages</label>
						</td>
					</tr> 
				</table>
			  </form>
			</div>
			<?php
		}
		public function reset_checkout_fields() {
			$section = isset( $_POST['kt_woo_section'] ) ? $_POST['kt_woo_section'] : '';
			update_option( 'kt_custom_checkout_' . $section . '_options', array() );
		}
		public function get_fields( $key ) {
			$fields = array_filter( get_option( 'kt_custom_checkout_' . $key . '_options', array() ) );
			if ( empty( $fields ) || sizeof( $fields ) == 0 ) {
				if ( $key === 'billing' || $key === 'shipping' ) {
					$fields = WC()->countries->get_address_fields( WC()->countries->get_base_country(), $key . '_' );

				} else if ( $key === 'additional' ) {
					$fields = array(
						'order_comments' => array(
							'type'        => 'textarea',
							'class'       => array( 'notes' ),
							'label'       => __( 'Order notes', 'woocommerce' ),
							'placeholder' => _x( 'Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce' ),
						),
					);
				}
			}
			return $fields;
		}
		public function sort_fields_by_order( $a, $b ) {
			if ( ! isset( $a['order'] ) || $a['order'] == $b['order'] ) {
				return 0;
			}
			return ( $a['order'] < $b['order'] ) ? -1 : 1;
		}
		public function save_checkout_fields( $section ) {
			$o_fields      = $this->get_fields( $section );
			$fields        = $o_fields;

			$f_order       = ! empty( $_POST['f_order'] ) ? $_POST['f_order'] : array();

			$f_names       = ! empty( $_POST['f_name'] ) ? $_POST['f_name'] : array();
			$f_names_new   = ! empty( $_POST['f_name_new'] ) ? $_POST['f_name_new'] : array();
			$f_types       = ! empty( $_POST['f_type'] ) ? $_POST['f_type'] : array();
			$f_labels      = ! empty( $_POST['f_label'] ) ? $_POST['f_label'] : array();
			$f_placeholder = ! empty( $_POST['f_placeholder'] ) ? $_POST['f_placeholder'] : array();
			$f_options     = ! empty( $_POST['f_options'] ) ? $_POST['f_options'] : array();

			$f_class       = ! empty( $_POST['f_class'] ) ? $_POST['f_class'] : array();
			$f_label_class = ! empty( $_POST['f_label_class'] ) ? $_POST['f_label_class'] : array();

			$f_required    = ! empty( $_POST['f_required'] ) ? $_POST['f_required'] : array();
			$f_clear       = ! empty( $_POST['f_clear'] ) ? $_POST['f_clear'] : array();
			$f_enabled     = ! empty( $_POST['f_enabled'] ) ? $_POST['f_enabled'] : array();

			$f_show_in_email = ! empty( $_POST['f_show_in_email'] ) ? $_POST['f_show_in_email'] : array();
			$f_show_in_order = ! empty( $_POST['f_show_in_order'] ) ? $_POST['f_show_in_order'] : array();

			$f_validation  = ! empty( $_POST['f_validation'] ) ? $_POST['f_validation'] : array();
			$f_deleted     = ! empty( $_POST['f_deleted'] ) ? $_POST['f_deleted'] : array();

			$f_position        = ! empty( $_POST['f_position'] ) ? $_POST['f_position'] : array();
			$f_display_options = ! empty( $_POST['f_display_options'] ) ? $_POST['f_display_options'] : array();
			$max               = max( array_map( 'absint', array_keys( $f_names ) ) );

			for ( $i = 0; $i <= $max; $i ++ ) {
				$name     = empty( $f_names[ $i ] ) ? '' : urldecode( sanitize_title( wc_clean( stripslashes( $f_names[ $i ] ) ) ) );
				$new_name = empty( $f_names_new[ $i ] ) ? '' : urldecode( sanitize_title( wc_clean( stripslashes( $f_names_new[ $i ] ) ) ) );

				if ( ! empty( $f_deleted[ $i ] ) && $f_deleted[ $i ] == 1 ) {
					unset( $fields[ $name ] );
					continue;
				}

				// Check reserved names
				if ( $this->is_default_field_name( $new_name ) ) {
					continue;
				}

				// if update field
				if ( $name && $new_name && $new_name !== $name ) {
					if ( isset( $fields[ $name ] ) ) {
						$fields[ $new_name ] = $fields[ $name ];
					} else {
						$fields[ $new_name ] = array();
					}

					unset( $fields[ $name ] );
					$name = $new_name;
				} else {
					$name = $name ? $name : $new_name;
				}

				if ( ! $name ) {
					continue;
				}

				// if new field
				if ( ! isset( $fields[ $name ] ) ) {
					$fields[ $name ] = array();
				}

				$o_type  = isset( $o_fields[ $name ]['type'] ) ? $o_fields[ $name ]['type'] : 'text';

				// $o_class = isset( $o_fields[$name]['class'] ) ? $o_fields[$name]['class'] : array();
				// $classes = array_diff( $o_class, array( 'form-row-first', 'form-row-last', 'form-row-wide' ) );

				$fields[ $name ]['type']        = empty( $f_types[ $i ] ) ? $o_type : wc_clean( $f_types[ $i ] );
				$fields[ $name ]['label']       = empty( $f_labels[ $i ] ) ? '' : wp_kses_post( trim( stripslashes( $f_labels[ $i ] ) ) );
				$fields[ $name ]['placeholder'] = empty( $f_placeholder[ $i ] ) ? '' : wc_clean( stripslashes( $f_placeholder[ $i ] ) );
				$fields[ $name ]['options']     = empty( $f_options[ $i ] ) ? array() : array_map( 'wc_clean', explode( '|', trim( stripslashes( $f_options[ $i ] ) ) ) );

				$fields[ $name ]['class']       = empty( $f_class[ $i ] ) ? array() : array_map( 'wc_clean', explode( ',', $f_class[ $i ] ) );
				$fields[ $name ]['label_class'] = empty( $f_label_class[ $i ] ) ? array() : array_map( 'wc_clean', explode( ',', $f_label_class[ $i ] ) );

				$fields[ $name ]['required']    = empty( $f_required[ $i ] ) ? false : true;
				$fields[ $name ]['clear']       = empty( $f_clear[ $i ] ) ? false : true;

				$fields[ $name ]['enabled']     = empty( $f_enabled[ $i ] ) ? false : true;
				$fields[ $name ]['order']       = empty( $f_order[ $i ] ) ? '' : wc_clean( $f_order[ $i ] );

				if ( ! empty( $fields[ $name ]['options'] ) ) {
					$fields[ $name ]['options'] = array_combine( $fields[ $name ]['options'], $fields[ $name ]['options'] );
				}

				if ( ! in_array( $name, $this->locale_fields ) ) {
					$fields[ $name ]['validate'] = empty( $f_validation[ $i ] ) ? array() : explode( ',', $f_validation[ $i ] );
				}

				if ( ! $this->is_default_field_name( $name ) ) {
					$fields[ $name ]['custom'] = true;
					$fields[ $name ]['show_in_email'] = empty( $f_show_in_email[ $i ] ) ? false : true;
					$fields[ $name ]['show_in_order'] = empty( $f_show_in_order[ $i ] ) ? false : true;
				} else {
					$fields[ $name ]['custom'] = false;
				}

				$fields[ $name ]['label']       = __( $fields[ $name ]['label'], 'woocommerce' );
				$fields[ $name ]['placeholder'] = __( $fields[ $name ]['placeholder'], 'woocommerce' );

				do_action( 'wpml_register_single_string', 'Kadence_Checkout_Editor', $name . '_label', $fields[ $name ]['label'] );
				do_action( 'wpml_register_single_string', 'Kadence_Checkout_Editor', $name . '_placeholder', $fields[ $name ]['placeholder'] );
				if ( ! empty( $fields[ $name ]['options'] ) && is_array( $fields[ $name ]['options'] ) ) {
					foreach ( $fields[ $name ]['options'] as $key => $value ) {
						do_action( 'wpml_register_single_string', 'Kadence_Checkout_Editor', $name . '_options_' . $key, $value );
					}
				}
			}

			uasort( $fields, array( $this, 'sort_fields_by_order' ) );
			$result = update_option( 'kt_custom_checkout_' . $section . '_options', $fields );

			if ( $result == true ) {
				?>
				<div id="message" class="updated fade">
					<p><?php echo sprintf( __( '%1$s Settings Updated', 'kadence-woo-extras' ), ucfirst( $section ) ); ?></p>
				</div>
			<?php } else { ?>
				<div id="message" class="error fade">
					<p><?php echo sprintf( __( '%1$s settings could not be updated or no changes were made. Please try again.', 'kadence-woo-extras' ), ucfirst( $section ) ); ?></p>
				</div>
				<?php
			}
		}

		public function kt_checkout_admin_scripts() {
			wp_enqueue_script( 'kt_woo_checkout_admin_js', KADENCE_WOO_EXTRAS_URL . '/lib/checkout_editor/js/kt_woo_checkout_admin.js', array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-sortable', 'woocommerce_admin', 'select2', 'jquery-tiptip' ), KADENCE_WOO_EXTRAS_VERSION, true );

		}

		public function kt_checkout_admin_css() {
			wp_enqueue_style( 'kt_woo_checkout_admin_css', KADENCE_WOO_EXTRAS_URL . '/lib/checkout_editor/css/kt_woo_checkout_admin.css', false, KADENCE_WOO_EXTRAS_VERSION );
		}

		public function is_default_field_name( $field_name ) {
			if ( $field_name && in_array(
				$field_name,
				array(
					'billing_first_name',
					'billing_last_name',
					'billing_company',
					'billing_address_1',
					'billing_address_2',
					'billing_city',
					'billing_state',
					'billing_country',
					'billing_postcode',
					'billing_phone',
					'billing_email',
					'shipping_first_name',
					'shipping_last_name',
					'shipping_company',
					'shipping_address_1',
					'shipping_address_2',
					'shipping_city',
					'shipping_state',
					'shipping_country',
					'shipping_postcode',
					'customer_note',
					'order_comments',
				)
			) ) {
				return true;
			}
			return false;
		}
	}

	$GLOBALS['kt_checkout_editor'] = kt_checkout_editor::instance();
}
