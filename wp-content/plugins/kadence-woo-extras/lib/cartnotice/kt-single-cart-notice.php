<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $woocommerce;
$message = get_post_meta($notice->ID, '_kt_woo_cart_notice_text', true );
$btn_text = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_text', true );
$btn_type = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_type', true );
$btn_url = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_link', true );
$btn_remove_product = get_post_meta($notice->ID, '_kt_woo_cart_notice_valid_product', true );
$btn_add_product = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_add_product', true );
$btn_coupon = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_coupon', true );
$btn_add_product_variation = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_add_product_variation', true );
$background = get_post_meta($notice->ID, '_kt_woo_cart_notice_bg', true );
$border = get_post_meta($notice->ID, '_kt_woo_cart_notice_border', true );
$border_color = get_post_meta($notice->ID, '_kt_woo_cart_notice_border_color', true );
$radius = get_post_meta($notice->ID, '_kt_woo_cart_notice_radius', true );
$text_color = get_post_meta($notice->ID, '_kt_woo_cart_notice_text_color', true );
$btn_text_color = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_txt_color', true );
$btn_text_hover_color = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_txt_color_hover', true );
$btn_bg_color = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_background_color', true );
$btn_bg_hover_color = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_background_color_hover', true );
$btn_border = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_border', true );
$btn_radius = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_radius', true );
$btn_border_color = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_border_color', true );
$btn_border_hover_color = get_post_meta($notice->ID, '_kt_woo_cart_notice_btn_border_color_hover', true );
$display = get_post_meta( $notice->ID, '_kt_woo_cart_notice_display_type', true );
$leverage_price = get_post_meta( $notice->ID, '_kt_woo_cart_notice_valid_price', true );
if ( 'price' === $display && $leverage_price ) {
	$float_total = ( wc_prices_include_tax() ? WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() : WC()->cart->get_cart_contents_total() );
	$message = str_replace( '{cart_difference}', wc_price( $leverage_price - $float_total ), $message );
}
if(!empty($btn_type)) {
	$btn_type = $btn_type;
} else {
	$btn_type = 'link';
}
if ( get_option('permalink_structure') ) {
	$starturl = '?';
} else {
	$starturl = '&';
}
if($btn_type == 'add_product') {
	$btn_url = wc_get_cart_url().$starturl.'kt-add-to-cart='.$btn_add_product.$btn_add_product_variation;
} else if($btn_type == 'coupon' && !empty($btn_coupon) && $btn_coupon != '0'){
	$coupon_code = get_the_title($btn_coupon);
	$btn_url = wc_get_cart_url().$starturl.'coupon_code='.$coupon_code;
} else if($btn_type == 'product_coupon' && !empty($btn_coupon) && $btn_coupon != '0'){
	$coupon_code = get_the_title($btn_coupon);
	$btn_url = wc_get_cart_url().$starturl.'kt-add-to-cart='.$btn_add_product.$btn_add_product_variation.'&coupon_code='.$coupon_code;
} else if($btn_type == 'upgrade') {
	$btn_url = wc_get_cart_url().$starturl.'replace_item='.$btn_remove_product.'&kt-add-to-cart='.$btn_add_product.$btn_add_product_variation;
} else if($btn_type == 'upgrade_coupon'  && !empty($btn_coupon) && $btn_coupon != '0') {
	$coupon_code = get_the_title($btn_coupon);
	$btn_url = wc_get_cart_url().$starturl.'replace_item='.$btn_remove_product.'&kt-add-to-cart='.$btn_add_product.$btn_add_product_variation.'&coupon_code='.$coupon_code;
}
if(!empty($background)) {
    $background = 'background-color:'.$background.';';
} else {
    $background = '';
}
if(!empty($radius)) {
    $radius = 'border-radius:'.$radius.'px;';
} else {
    $radius = '';
}
if(!empty($border)) {
    $border = 'border-width:'.$border.'px; border-style:solid;';
} else {
    $border = '';
}
if(!empty($border_color)) {
    $border_color = 'border-color:'.$border_color.';';
} else {
    $border_color = '';
}
if(!empty($text_color)) {
    $text_color = 'color:'.$text_color.';';
} else {
    $text_color = '';
}
$js_out = 'onMouseOver="';
if(!empty($btn_bg_hover_color)) {
    $js_out .= 'this.style.background=\''.$btn_bg_hover_color.'\'';
}
if(!empty($btn_bg_hover_color) && (!empty($btn_text_hover_color) || !empty($btn_border_hover_color) ) ){
     $js_out .= ',';
}
if(!empty($btn_text_hover_color)) { 
    $js_out .= 'this.style.color=\''.$btn_text_hover_color.'\'';
}
if(!empty($btn_text_hover_color) && !empty($btn_border_hover_color) ){
     $js_out .= ',';
}
if(!empty($btn_border_hover_color)) { 
    $js_out .= 'this.style.borderColor=\''.$btn_border_hover_color.'\'';
}
$js_out .='" onMouseOut="';
if(!empty($btn_bg_color)){
    $js_out .= 'this.style.background=\''.$btn_bg_color.'\'';
    $btn_bg_color = $btn_bg_color;
} else {
    $js_out .= 'this.style.background=\'#777\'';
    $btn_bg_color = '#777';
} 
if(!empty($btn_text_color)) { 
    $js_out .= ',this.style.color=\''.$btn_text_color.'\'';
    $btn_text_color = $btn_text_color;
} else {
    $js_out .= ',this.style.color=\'#fff\'';
    $btn_text_color= '#fff';
}
if(!empty($btn_border_color)) { 
    $js_out .= ',this.style.borderColor=\''.$btn_border_color.'\'';
    $btn_border_color = $btn_border_color;
} else {
    $js_out .= ',this.style.borderColor=\'transparent\'';
    $btn_border_color = 'transparent';
}
$js_out .='"';
if(!empty($btn_border)) {
    $btn_border_style = 'border-width:'.$btn_border.'px; border-style:solid;';
    $message_border = 'border-top:'.$btn_border.'px solid transparent; border-bottom:'.$btn_border.'px solid transparent;';
} else {
    $btn_border = '';
    $btn_border_style = '';
    $message_border = '';
}
if(!empty($btn_radius)) {
    $btn_radius = 'border-radius:'.$btn_radius.'px;';
} else {
    $btn_radius = '';
}

$message = str_replace( '{cart_total}', WC()->cart->get_cart_total(), $message );

?>
<div id="kt-woo-cart-notice-<?php echo esc_attr( $notice->ID );?>" class="kt-woo-cart-notice" style="<?php echo $background.' '.$border.' '.$border_color.' '.$text_color.' '.$radius;?>">
    <?php 
    	echo '<span class="kt-woo-cart-notice-message" style="' . esc_attr( $message_border ) . '">' . do_shortcode( $message ) . '</span>'; 
    	if(!empty($btn_text)) {
    		echo  '<a href="'.$btn_url.'" class="button" '.$js_out.' style="background-color:'.esc_attr($btn_bg_color).'; color:'.esc_attr($btn_text_color).'; border-color:'.esc_attr($btn_border_color).'; '.esc_attr($btn_border_style).' '.esc_attr($btn_radius).'">'.$btn_text.'</a>';
    	}?>
</div>