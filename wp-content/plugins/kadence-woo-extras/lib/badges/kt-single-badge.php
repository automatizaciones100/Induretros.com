<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//preset $page_type;
//preset $badge;

global $woocommerce;
global $post;

$the_post_id = get_the_id();

$badge_type = get_post_meta( $badge->ID, '_kt_woo_badge_type', true ) ? get_post_meta( $badge->ID, '_kt_woo_badge_type', true ) : 'premade';

$is_singular = Kadence_Badges::is_singular( $page_type );

$badge_max_width = get_post_meta( $badge->ID, '_kt_woo_badge_max_width', true );
$badge_max_width_loop = get_post_meta( $badge->ID, '_kt_woo_badge_max_width_loop', true );
$badge_margin = get_post_meta( $badge->ID, '_kt_woo_badge_margin', true );
$badge_padding = get_post_meta( $badge->ID, '_kt_woo_badge_padding', true );


$badge_text = Kadence_Badges::do_string_replacements( get_post_meta( $badge->ID, '_kt_woo_badge_text', true ) );
$badge_image = get_post_meta( $badge->ID, '_kt_woo_badge_image', true );
$badge_image_custom = get_post_meta( $badge->ID, '_kt_woo_badge_image_custom', true );
$badge_image_custom_id = get_post_meta( $badge->ID, '_kt_woo_badge_image_custom_id', true );
$badge_html = Kadence_Badges::do_string_replacements( get_post_meta( $badge->ID, '_kt_woo_badge_html', true ) );
$badge_text_color = get_post_meta( $badge->ID, '_kt_woo_badge_text_color', true );
$badge_background = get_post_meta( $badge->ID, '_kt_woo_badge_background', true );
$badge_border_radius = get_post_meta( $badge->ID, '_kt_woo_badge_border_radius', true );
$badge_border_width = get_post_meta( $badge->ID, '_kt_woo_badge_border_width', true );
$badge_border_color = get_post_meta( $badge->ID, '_kt_woo_badge_border_color', true );
$badge_font_size = get_post_meta( $badge->ID, '_kt_woo_badge_font_size', true );
$badge_font_style = get_post_meta( $badge->ID, '_kt_woo_badge_font_style', true );
$badge_font_weight = get_post_meta( $badge->ID, '_kt_woo_badge_font_weight', true );
$badge_text_align = get_post_meta( $badge->ID, '_kt_woo_badge_text_align', true );
$badge_position = get_post_meta( $badge->ID, '_kt_woo_badge_position', true ) ? get_post_meta( $badge->ID, '_kt_woo_badge_position', true ) : 'top-right';
$badge_id = esc_attr( $badge->ID );

$badge_image_info = Kadence_Badges::get_badge_image( $badge_image );
$badge_image_src = $badge_image_info['src'];
$badge_image_alt = $badge_image_info['alt'];

//hide badges by default, css/js will reveal, here so badges will be hidden untill css/js enqueues
$style_string = ".kt-woo-badge{";
$style_string .= "display: none;";
//dynamic styles
$style_string .= "}";

$style_string .= "#kt-woo-badge-{$badge_id}{";
if ( isset( $badge_margin ) && $badge_margin != '' ) {
	$style_string .= "margin: {$badge_margin}px;";
}
if ( isset( $badge_padding ) && $badge_padding != '' ) {
	$style_string .= "padding: {$badge_padding}px;";
}
if ( $badge_background ) {
	$style_string .= "background: {$badge_background};";
}
if ( isset( $badge_border_radius ) && $badge_border_radius != '' ) {
	$style_string .= "border-radius: {$badge_border_radius}px;";
}
if ( isset( $badge_border_width ) && $badge_border_width != '' ) {
	$style_string .= "border: {$badge_border_width}px solid {$badge_border_color};";
}

if ( $badge_type == 'text' ) {
	if ( $badge_text_color ) {
		$style_string .= "color: {$badge_text_color};";
	}
	if ( ! empty( $badge_font_size ) ) {
		$style_string .= "font-size: {$badge_font_size}px;";
	}
	if ( $badge_font_style ) {
		$style_string .= "font-style: {$badge_font_style};";
	}
	if ( $badge_font_weight ) {
		$style_string .= "font-weight: {$badge_font_weight};";
	}
	if ( ! empty( $badge_text_align ) ) {
		$style_string .= "text-align: {$badge_text_align};";
	}
}
$style_string .= "}";

$style_string .= 
"#kt-woo-badge-{$badge_id} svg:not(.first-fill) path:not([fill='white']),
#kt-woo-badge-{$badge_id} svg.first-fill path:not([fill='white']):first-of-type,
#kt-woo-badge-{$badge_id} .fill-this {";
if ( $badge_type == 'premade' ) {
	if ( $badge_text_color ) {
		$style_string .= "fill: {$badge_text_color};";
	}
}
$style_string .= "}";


$style_string .= "#kt-woo-badge-{$badge_id}.kt-woo-badge-singular{";
if ( ! empty( $badge_max_width ) ) {
	$style_string .= "max-width: {$badge_max_width}px;";
}
$style_string .= "}";

$style_string .= "#kt-woo-badge-{$badge_id}.kt-woo-badge-loop{";
if ( ! empty( $badge_max_width_loop ) ) {
	$style_string .= "max-width: {$badge_max_width_loop}px;";
}
$style_string .= "}";

$extra_classes[] = "kt-woo-badge-type-{$badge_type}";
$extra_classes[] = "kt-woo-badge-position-{$badge_position}";
$extra_classes[] = "kt-woo-badge-page-type-{$page_type}";
$extra_classes[] = $is_singular ? "kt-woo-badge-singular" : "kt-woo-badge-loop";
?>
<div 
	id="kt-woo-badge-<?php echo $badge_id; ?>" 
	class="kt-woo-badge <?php echo implode( ' ', $extra_classes); ?>" 
	data-attached-post-id="<?php echo $the_post_id; ?>"
	data-page-type="<?php echo $page_type; ?>"
>
	<?php if ( $badge_type == 'text' ) : ?>
		<?php echo wp_kses_post( $badge_text ); ?>
	<?php elseif ( $badge_type == 'image-custom' ) : ?>
		<?php echo wp_get_attachment_image( $badge_image_custom_id, 'medium' ); ?>
	<?php elseif ( $badge_type == 'html' ) : ?>
		<?php echo wp_kses_post( do_shortcode( $badge_html ) ); ?>
	<?php else : ?>
		<!-- <img alt="<?php echo esc_attr( $badge_image_alt ); ?>" src="<?php echo esc_attr( $badge_image_src ); ?>" /> -->
		<?php echo file_get_contents( esc_attr( $badge_image_src ) ); ?>
	<?php endif; ?>
	<style><?php echo wp_kses_post( $style_string ); ?></style>
</div>
