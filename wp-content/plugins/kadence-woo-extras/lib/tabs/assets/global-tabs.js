/**
 * Add anchor support to open global tabs.
 */
 jQuery( function( $ ) {
	$(document).ready(function() {
		var hash  = window.location.hash;
		if ( hash && hash.toLowerCase().indexOf( 'tab-' ) >= 0 && hash !== '#tab-reviews' && hash !== '#tab-additional_information' ) {
			$( '.wc-tabs li a[href="' + hash + '"]' ).trigger( 'click' );
		}
	} );
} );