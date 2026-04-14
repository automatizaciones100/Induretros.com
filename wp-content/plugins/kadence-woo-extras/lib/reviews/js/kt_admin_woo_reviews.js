/* globals ajaxurl, kadenceAdminReviews */
function KadenceConvertButton() {
	$button = jQuery( '#kt-review-convert' );
	if ( $button.prop( "disabled" ) ) {
		return;
	}
	$button.prop( "disabled", true );
	$button.find(".spinner-item").addClass( 'spinner' );
	$button.find(".spinner-item").addClass( 'is-active' );
	var data = {
		action: 'kt_review_convert',
		security: kadenceAdminReviews.ajax_nonce,
	};
	jQuery.post( ajaxurl, data, function(response) {
		$button.find(".spinner-item").removeClass( 'spinner' );
		$button.find(".spinner-item").removeClass( 'is-active' );
		jQuery(".convert-info").append( response.value );
		$button.prop( "disabled", false );
	} );
	// $( '#kt-review-convert' ).on( 'click', function() {
	// 	$button = $( this );
	// 	if ( $button.prop( "disabled" ) ) {
	// 		return;
	// 	}
	// 	$button.prop( "disabled", true );
	// 	$button.find(".spinner-item").addClass( 'spinner' );
	// 	$button.find(".spinner-item").addClass( 'is-active' );
	// 	var data = {
	// 		action: 'kt_review_convert',
	// 		security: kadenceAdminReviews.ajax_nonce,
	// 	};
	// 	jQuery.post( ajaxurl, data, function(response) {
	// 		$button.find(".spinner-item").removeClass( 'spinner' );
	// 		$button.find(".spinner-item").removeClass( 'is-active' );
	// 		jQuery(".convert-info p").append( response.value );
	// 		$button.prop( "disabled", false );
	// 	} );
	// });
}
jQuery( function( $ ) {
	function readyConvertButton() {
		$( '#kadence-convert-info' ).html( '<button id="kt-review-convert" class="button-primary kt-review-convert" onClick="KadenceConvertButton();"style="margin:10px 0;">Convert Reviews<span class="spinner-item"></span></button><p></p>' );
	}
	setTimeout(function(){
		readyConvertButton();
	}, 300);
});

