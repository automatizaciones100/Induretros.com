; (function ($) {

    const { __, _x, _n, _nx } = wp.i18n;

    /*  
    * Form Condition
    */
    function wpbean_sb_form_condition(){
        $(document).find('.wpb-sb-item-edit-page-content > .wpbean-sb-shortcodes-list-item-wrapper > .wpbean-sb-shortcodes-list-item').each( function(){
            var form = $(this);

            form.find( '.wpbean-sb-form-group ' ).each(function() {
                var form_group = $(this);

                if( form_group.data('condition-field') && form_group.data('condition-value') ){

                    id = form.find('#' + form_group.data('condition-field'));
        
                    $( id ).conditions( {
                        conditions: {
                            element:  id,
                            type:     'value',
                            operator:  '=',
                            condition: form_group.data('condition-value')
                        },
                        actions: {
                            if: {
                                element: form_group,
                                action:  'show'
                            },
                            else: {
                                element: form_group,
                                action:  'hide'
                            }
                        },
                        effect: 'slide'
                    } );
                }
            });
        });
    }

    /**
     * jQuery tab
     */

    function wpb_sb_data_tabs() {
        $('.wpb-sb-tabs').each(function(){
            var tab_nav_item           = $(this).find('.wpb-sb-tabs-nav ul li'),
                tab_nav_first_item     = $(this).find('.wpb-sb-tabs-nav ul li:first-child'),
                tab_content            = $(this).find('.wpb-sb-tab-content'),
                tab_content_first_item = $(this).find('.wpb-sb-tab-content:first');

            // Show the first tab and hide the rest
            tab_nav_first_item.addClass('wpb-sb-nav-active');
            tab_content.hide();
            tab_content_first_item.show();

            // Click function
            tab_nav_item.click(function ( e ) {
                e.preventDefault();
                tab_nav_item.removeClass('wpb-sb-nav-active');
                $(this).addClass('wpb-sb-nav-active');
                tab_content.hide();

                var activeTab = $(this).find('a').attr('href');
                $(activeTab).fadeIn();
                return false;
            });
        });
    }

    $(document).ajaxComplete(function( event,xhr,options ){
        wpbean_sb_form_condition();
    });

    $( document ).ready(function() {
        wpb_sb_data_tabs();
        wpbean_sb_form_condition();
        wpbean_sb_checkbox_uncheck_iusse_fix();
        wpbean_sb_image_select_radio();
        $('.wpbean-sb-select-buttons').togglebutton();
    });

    /**
     * ShortCode Popup 
     * using sweetalert2 v11.3.10
     */
    $( 'body' ).on( 'click', '.wpb-sb-shortcode-popup-trigger', function( e ) {
        e.preventDefault();
        var t = $(this);

        params = {
            shortcode_id: t.data('id'),
            nonce       : t.data('nonce'),
        };

        $(this).addClass( 'wpb-sb-loading' );

        request = wp.ajax.post( 'wpb-fire-shortcode-popup', params );

        request.done(function (response) {
            alertify.alert( response.title, response.content ).setting( {
                padding: false,
                defaultFocusOff: true,
            } );
            $('.wpb-sb-shortcode-popup-trigger').removeClass( 'wpb-sb-loading' );
        });
        request.fail(function (response) {
            alertify.alert( response.error_title, response.error_message ).setting( {
                padding: false,
                defaultFocusOff: true,
            } );
            $('.wpb-sb-shortcode-popup-trigger').removeClass( 'wpb-sb-loading' );
        });
        request.always( function( data ) {
            wpb_sb_data_tabs();
        });
    });

    /**
     * Duplicate ShortCode
     */
    $( 'body' ).on( 'click', '.shortcodes-list-item-duplicate', function( e ) {
        e.preventDefault();
        var t = $(this);

        params = {
            shortcode_id: t.data('id'),
            nonce       : t.data('nonce'),
        };

        $(this).addClass( 'wpb-sb-loading' );

        request = wp.ajax.post( 'wpb-fire-duplicate-shortcode', params );

        request.done(function (response) {
            t.closest( '.wpb-sb-shortcodes-list-items' ).prepend( response.content );
            alertify.success( response.success_message );
            $('.shortcodes-list-item-duplicate').removeClass( 'wpb-sb-loading' );
        });

        request.fail(function (response) {
            alertify.error( response.error_message, 2 );
            $('.shortcodes-list-item-duplicate').removeClass( 'wpb-sb-loading' );
        });
    });

    /**
     * Delete ShortCode.
     */
    $( 'body' ).on( 'click', '.shortcodes-list-item-delete', function( e ) {
        e.preventDefault();
        var t = $(this);

        params = {
            shortcode_id: t.data('id'),
            nonce       : t.data('nonce'),
        };

        $(this).addClass( 'wpb-sb-loading' );

        alertify.confirm( __( 'Are you sure you want to delete this?', 'wpb-accordion-menu-or-category' ), __( 'This is a permanent action. There is no going back once you have removed it.', 'wpb-accordion-menu-or-category' ),
        function(){
            request = wp.ajax.post( 'wpb-fire-delete-shortcode', params );

            request.done(function (response) {
                $("div[data-id='"+t.data('id')+"']").remove();
                alertify.success( response );
                $('.shortcodes-list-item-delete').removeClass( 'wpb-sb-loading' );
            });

            request.fail(function (response) {
                alertify.error( __( 'Error', 'wpb-accordion-menu-or-category' ) );
                $('.shortcodes-list-item-delete').removeClass( 'wpb-sb-loading' );
            });
        },
        function(){
            $('.shortcodes-list-item-delete').removeClass( 'wpb-sb-loading' );
        } );
    });

    /**
     * Add ShortCode.
     */
    $( 'body' ).on( 'click', '.wpb-sb-add-new', function( e ) {
        e.preventDefault();
        var t = $(this);

        params = {
            nonce       : t.data('nonce'),
        };

        $(this).addClass( 'wpb-sb-loading' );

        alertify.prompt(
            __( 'Add New Accordion', 'wpb-accordion-menu-or-category' ),
            __( 'Accordion Name', 'wpb-accordion-menu-or-category' ),
            __( 'Add Accordion Name Here', 'wpb-accordion-menu-or-category' ),
            function(evt, value ){
                request = wp.ajax.post( 'wpb-fire-add-shortcode', { nonce : t.data('nonce'), title: value } );

                request.done(function (response) {
                    $('.wpb-sb-add-new').removeClass( 'wpb-sb-loading' );
                    alertify.success( __( 'Accordion Added Successfully.', 'wpb-accordion-menu-or-category' ) );
                    $('.wpb-sb-shortcodes-list-items').prepend(response);
                });

                request.fail(function (response) {
                    alertify.error( __( 'Error', 'wpb-accordion-menu-or-category' ) );
                    $('.wpb-sb-add-new').removeClass( 'wpb-sb-loading' );
                });
            },
            function(){
                $('.wpb-sb-add-new').removeClass( 'wpb-sb-loading' );
            }
        );
    });

    /**
     * Copy to clipboard
     */
    $( 'body' ).on( 'click', '.wpb-sb-copy-shortcode', function( e ) {
        var range = document.createRange();
        var sel   = window.getSelection();
        range.setStartBefore(this.firstChild);
        range.setEndAfter(this.lastChild);
        sel.removeAllRanges();
        sel.addRange(range);

        try {  
            var successful = document.execCommand( 'copy' );  
        } catch( err ) {  
            console.error( __( 'Unable to copy', 'wpb-accordion-menu-or-category' )  ); 
        } 		
    } );

    /* Checkbox meta uncheck save issue fix */
    function wpbean_sb_checkbox_uncheck_iusse_fix(){
        $('.wpbean-sb-fieldset input[type=checkbox]').each(function(){
            var self     = $(this),
                name     = self.attr('name'),
                fieldset = self.closest('.wpbean-sb-fieldset').find('fieldset');

            self.click(function() {
                if (self.is(":checked") == true) {
                    fieldset.find('input[type=hidden]').remove();
                } else {
                    fieldset.prepend('<input type="hidden" name="'+name+'" value="off" />');
                }
            });
        });
    }

    /**
     * image select radio
     */

    function wpbean_sb_image_select_radio(){
        $('.wpb-radio-image img').on( 'click', function(){
            $(this).next('input[type="radio"]').prop('checked', true);
        });

        $('.wpb-radio-image').on( 'click', function(){
            var self       = $(this),
                wrapper    = self.closest('.wpb-image-select'),
                image_item = wrapper.find('.wpb-radio-image ');

            image_item.removeClass('wpb-radio-image-active');
            self.addClass('wpb-radio-image-active');
        });
    }

    /**
     * Save ShortCode Meta
     */
    $(document).on( 'click', '.wpb-sb-save-meta-data', function(e) {
        e.preventDefault();

        var btn        = $(this),
            title      = btn.closest('.wpb-sb-list-items-header').find('#wpb_sb_shortcode_title').val();
            forms      = $(document).find('.wpb-sb-item-edit-page-content > .wpbean-sb-shortcodes-list-item-wrapper > .wpbean-sb-shortcodes-list-item'),
            forms_data = [];

            forms.each(function(i) {

                if( typeof tinyMCE != 'undefined'){
                    tinyMCE.triggerSave();
                }

                var form           = $(this),
                    post_id        = form.data('id'),
                    form_serialize = form.serializeArray(),
                    indexed_array  = {};

                $.map(form_serialize, function(n, i){

                    indexed_array['post_id'] = post_id;

                    if(indexed_array[n['name']] !== undefined){
                        indexed_array[n['name']].push(n['value']);
                    } else if(n['name'] !== undefined && n['name'].indexOf('[]') > -1){
                        indexed_array[n['name']] = new Array(n['value']);
                    } else {
                        indexed_array[n['name']] = n['value'];
                    }
                });

                var output_array = (!$.isEmptyObject(indexed_array) ? JSON.stringify(indexed_array, null, 2) : '');

                forms_data[i] = output_array;
            });

            //console.log(forms_data);

            wp.ajax.send( {
                data: {
                    action                 : 'wpb_am_fire_save_shortcode',
                    _wpb_am_save_meta_nonce: WPBean_Accordion_Menu_Vars._wpbean_accordion_menu_nonce,
                    _wpb_am_forms_data     : forms_data,
                    _wpb_am_shortcode_title: title,
                },
                beforeSend : function ( xhr ) {
                    btn.addClass('wpb-sb-loading');
                    btn.attr('disabled', true);
                },
                success: function( res ) {
                    btn.removeClass('wpb-sb-loading');
                    btn.removeAttr('disabled');
                    alertify.success( res );
                },
                error: function(error) {
                    alert( error );
                }
            });
            
    });

})(jQuery);