(function ($) {
    "use strict";

    /**
     * Navgoco Init Function
     */
    function wpbInitNavgoco($wrapper) {
        $wrapper.each(function () {
            var $this = $(this);

            // Prevent Double Init
            if ($this.hasClass("navgoco-initialized")) {
                return;
            }

            var accordion = $this.data("accordion"),
                indicator_icon = $this.data("indicator_icon"),
                iconclass = $this.data("iconclass"),
                caretHtml = iconclass
                    ? '<i class="' + iconclass + '"></i>'
                    : indicator_icon;

            $this.find(".wpb_category_n_menu_accordion_list").navgoco({
                caretHtml: caretHtml,
                accordion: accordion,
                openClass: "wpb-submenu-indicator-minus",
                save: true,
                cookie: {
                    name: "navgoco",
                    expires: false,
                    path: "/",
                },
                slide: {
                    duration: 400,
                    easing: "swing",
                },
            });

            $this.addClass("navgoco-initialized");
        });
    }

    /**
     * Global Init for Non-Elementor Popup Menus
     */
    wpbInitNavgoco(
        $(".wpb_category_n_menu_accordion").filter(function () {
            return (
                $(this).closest(".wpb-wamc-elementor-widget-show-in-popup")
                    .length === 0
            );
        })
    );

    /**
     * Elementor Specific Init
     */
    var WPB_Accordion_Menu_Elementor = function ($scope, $) {
        var $wrapper = $scope.find(".wpb_category_n_menu_accordion");

        wpbInitNavgoco($wrapper);

        $(".wpb-submenu-indicator").click(function (e) {
            e.preventDefault();
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/wpb-accordion-categories.default",
            WPB_Accordion_Menu_Elementor
        );
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/wpb-accordion-menu.default",
            WPB_Accordion_Menu_Elementor
        );
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/wpb-accordion-menu-or-category-pro.default",
            WPB_Accordion_Menu_Elementor
        );
    });

    /**
     * Keyboard Accessibility
     */

    $('document').ready(function(){
        // Adding the focus class to the parest li
        $( '.wpb_wmca_keyboard_accessibility_yes a' ).on( 'focus', function(){
            $( '.wpb_wmca_keyboard_accessibility_yes li' ).removeClass( 'wpb-wmca-focus' );
            $(this).closest( 'li' ).addClass( 'wpb-wmca-focus' );
        } );

        $(window).click(function() {
            //remove the focus is click outside
            $( '.wpb_wmca_keyboard_accessibility_yes li' ).removeClass( 'wpb-wmca-focus' );
            $( '.wpb_wmca_keyboard_accessibility_yes' ).removeClass( 'wpb_wmca_link_tabbed' );
        });

        // Opening and closing the accordion on keyboard enter
        $('.wpb_wmca_keyboard_accessibility_yes').each(function(index){

            const wrapper = $(this);
            const link    = $(this).find('.cat-item-have-child > a, .menu-item-has-children > a');

            $('.wpb_wmca_keyboard_accessibility_yes').keyup(function (e) {
                wrapper.removeClass('wpb_wmca_link_tabbed');
                if ( link.is(":focus") ) {
                    wrapper.addClass('wpb_wmca_link_tabbed');
                }else{
                    wrapper.removeClass('wpb_wmca_link_tabbed');
                }
            });
            
            link.on( 'click', function( e ){
                
                var link       = $(this),
                    closest_li = link.closest( 'li' ),
                    child_ul   = closest_li.find('ul').first(),
                    a_href     = link.attr('href'),
                    wrapper     = link.closest('.wpb_wmca_keyboard_accessibility_yes');

                    if ( a_href.indexOf('#') === -1 && wrapper.hasClass('wpb_wmca_link_tabbed') && closest_li.hasClass('wpb-wmca-focus') ) {
                        e.preventDefault();

                        if( child_ul.is(':hidden') ){
                            child_ul.slideDown();
                            closest_li.addClass('wpb-submenu-indicator-minus');
                        }else{
                            child_ul.slideUp();
                            closest_li.removeClass('wpb-submenu-indicator-minus');
                        }
                    }   
            });
        });
    });
})(jQuery);