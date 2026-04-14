<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Discount notice class
 */
class WPBean_Accordion_Menu_Discount_Notice {

	/**
	 * Class Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'discount_admin_notice' ) );
		add_action( 'admin_init', array( $this, 'discount_admin_notice_dismissed' ) );
	}

	/**
	 * Pro version discount notice
	 *
	 * @return void
	 */
	public function discount_admin_notice() {
		$user_id = get_current_user_id();
		if ( ! get_user_meta( $user_id, 'wpb_wmca_pro_discount_dismissed' ) ) {
			printf(
				'<div class="wpb-accordion-menu-discount-notice updated" style="padding: 30px 20px;border-left-color: #27ae60;border-left-width: 5px;margin-top: 20px;"><p style="font-size: 18px;line-height: 32px">%s <a target="_blank" href="%s">%s</a>! %s <b>%s</b></p><a href="%s">%s</a></div>',
				esc_html__( 'Get a 10% exclusive discount on the premium version of the', 'wpb-accordion-menu-or-category' ),
				'https://wpbean.com/downloads/wpb-accordion-menu-category-pro/?utm_content=WPB+Accordion+Menu+Pro&utm_campaign=adminlink&utm_medium=discount-notie&utm_source=FreeVersion',
				esc_html__( 'WPB Accordion Menu or Category', 'wpb-accordion-menu-or-category' ),
				esc_html__( 'Use discount code - ', 'wpb-accordion-menu-or-category' ),
				'NewCustomer',
				esc_url(
					add_query_arg(
						array(
							'wpb-wmca-pro-discount-admin-notice-dismissed' => 'true',
							'_wpnonce' => wp_create_nonce( 'wpbean_accordion_menu_discount_notice_dismissed' ),
						)
					)
				),
				esc_html__( 'Dismiss', 'wpb-accordion-menu-or-category' )
			);
		}
	}

	/**
	 * Pro version discount notice
	 *
	 * @return void
	 */
	public function discount_admin_notice_dismissed() {

		$nonce     = ( ! empty( $_GET['_wpnonce'] ) ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		$dismissed = ( ! empty( $_GET['wpb-wmca-pro-discount-admin-notice-dismissed'] ) ) ? sanitize_text_field( wp_unslash( $_GET['wpb-wmca-pro-discount-admin-notice-dismissed'] ) ) : '';

		if ( ! empty( $dismissed ) && ! wp_verify_nonce( $nonce, 'wpbean_accordion_menu_discount_notice_dismissed' ) ) {
			die( esc_html__( 'Nonce Error!!!', 'wpb-accordion-menu-or-category' ) );
		}

		$user_id = get_current_user_id();

		//delete_user_meta($user_id, 'wpb_wmca_pro_discount_dismissed');

		if ( isset( $_GET['wpb-wmca-pro-discount-admin-notice-dismissed'] ) ) {
			add_user_meta( $user_id, 'wpb_wmca_pro_discount_dismissed', 'true', true );
		}
	}
}
