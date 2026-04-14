<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The New ShortCode Class for Accordion.
 */
class WPB_Accordion_Menu_ShortCode {

	/**
	 * Class Constructor.
	 */
	public function __construct() {
		add_shortcode( 'wpb_wmca_accordion_pro', array( $this, 'render_accordion_shortcode' ) );
	}

	/**
	 * ShortCode handler class
	 *
	 * @param  array $atts The shortcode attributes.
	 *
	 * @return string
	 */
	public function render_accordion_shortcode( $atts ) {

		// It's a default value.
		$default = apply_filters(
			'wpb_wmca_accordion_pro_shortcode_atts',
			array(
				'id' => '', // the shortcode id.
			)
		);

		// You will pass default value after that user define values.
		$accordion_attrs = shortcode_atts( $default, $atts );

		if ( ! array_key_exists( 'id', $accordion_attrs ) || '' === $accordion_attrs['id'] ) {
			return;
		}

		$id          	= $accordion_attrs['id'];
		$data_socure 	= get_post_meta( $id, 'wpb_wmca_data_socure', true );
		$skin 		 	= get_post_meta( $id, 'wpb_wmca_accordion_skin', true );
		$accessibility 	= get_post_meta( $id, 'wpb_wmca_keyboard_accessibility', true );

		$wrapper_classes = array(
			'wpb_category_n_menu_accordion',
			'wpb_accordion_free_version',
			'wpb_wmca_accordion_wrapper_theme_' . esc_attr($skin ? $skin : 'dark'),
			'wpb_wmca_keyboard_accessibility_' . esc_attr('on' === $accessibility ? 'yes' : 'no')
		);

		if ( 'taxonomy' === $data_socure ) {
			$wrapper_classes[] = 'wpb_the_category_accordion';
		}

		if ( 'menu' === $data_socure ) {
			$wrapper_classes[] = 'wpb_the_menu_accordion';
		}

		if ( 'posts' === $data_socure ) {
			$wrapper_classes[] = 'wpb_the_posts_accordion';
		}

		$data_attributes = array(
			'shortcode_id'   => $id,
			'accordion'      => ( 'on' === get_post_meta( $id, 'wpb_wmca_collapse_previous', true ) ? 'true' : 'false' ),
			'indicator_icon' => '+',
			'iconclass'      => '',
		);

		ob_start();
		?>
			<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" <?php echo wp_kses_data( wpb_wmca_data_atts( $data_attributes ) ); ?>>
				<?php do_action( 'wpb_wmca_before_accordion', $id, $accordion_attrs ); ?>
				<?php wpb_wamc_get_template( $data_socure . '.php', $accordion_attrs ); ?>
				<?php do_action( 'wpb_wmca_after_accordion', $id, $accordion_attrs ); ?>
			</div>
		<?php
		return ob_get_clean();
	}
}