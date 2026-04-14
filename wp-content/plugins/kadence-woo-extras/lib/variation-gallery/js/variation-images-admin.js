jQuery( function( $ ) {
    'use strict';
    /**
	 * Variations actions
	 */
	var kadence_variation_images = {
		/**
		 * Initialize variations actions
		 */
		 init: function() {
			$('#woocommerce-product-data').on('woocommerce_variations_loaded', function () {
				$( '.woocommerce_variation' ).each( function () {
					var $gallery = $( this );
					kadence_variation_images.single_gallery_init( $gallery );
				});
				kadence_variation_images.init_upload();
			});
			$('#variable_product_options').on('woocommerce_variations_added', function () {
				$( '.woocommerce_variation' ).each(function () {
					var variation_wrap = $( this );
					kadence_variation_images.single_gallery_init( variation_wrap );
				});
				kadence_variation_images.init_upload();
			});
		},
		/**
		 * Initialize variations actions
		 */
		 init_upload: function() {
			var variation_product_gallery_frame = {};
			$( '.add_variation_images' ).on( 'click', 'a', function( event ) {
				var $el = $( this );
				var variation_id = $el.data( 'product_variation_id' );
				var $current_gallery = $el.closest('.kwsv-variations-images-wrapper');
				var $product_images    = $current_gallery.find( 'ul.kwsv-gallery-list' );
				var $image_gallery_ids = $current_gallery.find( '.kwsv_gallery_images' );
				event.preventDefault();
	
				// If the media frame already exists, reopen it.
				if ( variation_product_gallery_frame[ variation_id ] ) {
					variation_product_gallery_frame[ variation_id ].open();
					return;
				}
	
				// Create the media frame.
				variation_product_gallery_frame[ variation_id ] = wp.media.frames.product_gallery = wp.media({
					// Set the title of the modal.
					title: $el.data( 'choose' ),
					button: {
						text: $el.data( 'update' )
					},
					states: [
						new wp.media.controller.Library({
							title: $el.data( 'choose' ),
							filterable: 'all',
							multiple: true
						})
					]
				});
	
				// When an image is selected, run a callback.
				variation_product_gallery_frame[ variation_id ].on( 'select', function() {
					var selection = variation_product_gallery_frame[ variation_id ].state().get( 'selection' );
					var attachment_ids = $image_gallery_ids.val();
	
					selection.map( function( attachment ) {
						attachment = attachment.toJSON();
	
						if ( attachment.id ) {
							attachment_ids   = attachment_ids ? attachment_ids + ',' + attachment.id : attachment.id;
							var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
	
							$product_images.append(
								'<li class="image" data-attachment-id="' + attachment.id + '"><img src="' + attachment_image +
								'" /><ul class="actions"><li><a href="#" class="delete" title="' + $el.data('delete') + '">' +
								$el.data('text') + '</a></li></ul></li>'
							);
						}
					});
	
					$image_gallery_ids.val( attachment_ids );
					kadence_variation_images.variation_changed( $current_gallery );
				});
	
				// Finally, open the modal.
				variation_product_gallery_frame[ variation_id ].open();
			});
		},
		single_gallery_init: function ( variation_wrap ) {
			var optionsWrapper = variation_wrap.find( '.options:first' );
			var galleryWrapper = variation_wrap.find( '.kwsv-variations-images-wrapper' );
			galleryWrapper.insertBefore( optionsWrapper );
			var product_images = galleryWrapper.find( 'ul.kwsv-gallery-list' );
			var image_gallery_ids = galleryWrapper.find( '.kwsv_gallery_images' );
			product_images.sortable({
				items: 'li.image',
				cursor: 'move',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				forceHelperSize: false,
				helper: 'clone',
				opacity: 0.65,
				placeholder: 'wc-metabox-sortable-placeholder',
				start: function( event, ui ) {
					ui.item.css( 'background-color', '#f6f6f6' );
				},
				stop: function( event, ui ) {
					ui.item.removeAttr( 'style' );
				},
				update: function() {
					var attachment_ids = '';
	
					galleryWrapper.find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
						var attachment_id = $( this ).attr( 'data-attachment-id' );
						console.log( attachment_id );
						attachment_ids = ( attachment_ids ? attachment_ids + ',' + attachment_id : attachment_id );
					});
					image_gallery_ids.val( attachment_ids );
					kadence_variation_images.variation_changed( galleryWrapper );
				}
			});
			// Remove images.
			galleryWrapper.on( 'click', 'a.delete', function() {
				$( this ).closest( 'li.image' ).remove();
				console.log( $( this ) );
				var attachment_ids = '';

				product_images.find( 'li.image' ).css( 'cursor', 'default' ).each( function() {
					var attachment_id = $( this ).attr( 'data-attachment-id' );
					attachment_ids = ( attachment_ids ? attachment_ids + ',' + attachment_id : attachment_id );
				});
				console.log( attachment_ids );
				image_gallery_ids.val( attachment_ids );
				kadence_variation_images.variation_changed( galleryWrapper );
				return false;
			});
		},
		variation_changed: function ( variation_el ) {
			$( variation_el ).closest('.woocommerce_variation').addClass( 'variation-needs-update' );
			$( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr('disabled');
			$('#variable_product_options').trigger('woocommerce_variations_input_changed');
		}
    };
	kadence_variation_images.init();
});
