jQuery(document).ready(function ($) {
		var bootstrap_enabled = (typeof $().modal == 'function');
		if(bootstrap_enabled == false){
	         $(document).on('click','.kt-review-vote[data-toggle="modal"]', function (e) {
	         	e.preventDefault();
			    var $this   = $(this);
			    var $target = $($this.attr('data-target'));
			    if($target.hasClass('kt-modal-open')) {
			    	$target.removeClass('kt-modal-open');
			    } else {
			    	$target.addClass('kt-modal-open');
				}
			     
			  });
	         $(document).on('click','.kt-modal-open .close', function (e) {
	         	e.preventDefault();
			    $(this).parents('.kt-modal-open').removeClass('kt-modal-open');			     
			  });
	     }
        $(document).on('click', '.kt-review-vote[data-vote="review"]', function(e) {
	        e.preventDefault();
	        if ($(this).hasClass("kt-vote-review-selected")) {
	            return;
	        }
	        var comment_id = $(this).data('comment-id');
	        var vote = $(this).hasClass("kt-vote-down") ? "negative" : "positive";
	        var container = $(this).parents('.comment_container');
	        	container.find('.kt-review-overlay').fadeIn();
		    var data = {
				action: 'kt_review_vote',
				comment_id: comment_id,
				user_id: kt_product_reviews.user_id,
				vote: vote,
				wpnonce: kt_product_reviews.nonce
			};
			$(this).siblings('.kt-vote-review-selected').removeClass('kt-vote-review-selected');
			$(this).addClass('kt-vote-review-selected');

			$.post(woocommerce_params.ajax_url, data, function(response) {
	           	if( jQuery.trim(response) == 0 ) {
	           		container.find('.kt-review-helpful').empty().append(kt_product_reviews.error);
	           	} else {
	           		container.find('.kt-review-helpful').empty().append(response.value);
	           	}
                container.find('.kt-review-overlay').fadeOut();
        	});
        });
		$(document).on('click', '.kt-ajax-load-more-reviews', function(e) {
			e.preventDefault();
			var button = $(this);
			var args = button.data('review-args');
			var productid = button.data('product-id');
			var reviewcount = button.data('review-count');
			var offset = button.attr('data-offset-count');
			var container = button.parents('#comments');
			container.find('.kt-review-load-more-loader').fadeIn();
			var data = {
				action: 'kt_review_readmore',
				args: args,
				product_id: productid,
				offset: offset,
				wpnonce: kt_product_reviews.nonce
			};
			$.post( woocommerce_params.ajax_url, data, function(response) {
				if( jQuery.trim( response ) == 0 ) {
					container.find('.kt-ajax-load-more-reviews-container').append( kt_product_reviews.error );
				} else if( jQuery.trim( response ) == '' ) {
					container.find('.kt-ajax-load-more-reviews-container').append( kt_product_reviews.nomoreviews );
					button.fadeOut();
					setTimeout(function(){
						container.find('.kt-ajax-load-more-reviews-container').fadeOut();
					}, 2000 );
				} else {
					button.attr('data-offset-count', Math.floor( +offset + +args.numberposts ) );
					container.find('.commentlist').append( response.value );
					if ( Math.floor( +offset + +args.numberposts ) >=  reviewcount ) {
						button.fadeOut();
					}
				}
				container.find('.kt-review-load-more-loader').fadeOut();
			});
		});
		$(document).on('click', '#respond #submit', function() {
			var $consent = $( this ).closest( '#respond' ).find( '#review-consent-input' );
			if ( $consent.length > 0 && ! $consent.is( ':checked' ) ) {
				window.alert( kt_product_reviews.required_consent_text );

				return false;
			}
		} );
});


