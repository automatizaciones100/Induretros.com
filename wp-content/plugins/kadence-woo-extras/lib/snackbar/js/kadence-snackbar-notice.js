/* global kadence_wsb */
/**
 * File kb-table-of-contents.js.
 * Gets the table of contents links and smoothscroll working.
 */
(function () {
	"use strict";
	window.kadenceWSB = {
		/**
		 * Instigate dismiss.
		 */
		initClose: function () {
			var notice_items = document.querySelectorAll(
				".woocommerce-notices-wrapper > *:not(.woocommerce):not(.kwsb-snackbar-notice)"
			);
			if (!notice_items.length) {
				return;
			}
			for (let n = 0; n < notice_items.length; n++) {
				notice_items[n].classList.add("kwsb-snackbar-notice");
				var btn = document.createElement("BUTTON");
				btn.classList.add("kwsb-close");
				btn.setAttribute("aria-label", kadence_wsb.close);
				notice_items[n].appendChild(btn);
				btn.onclick = () => {
					notice_items[n].classList.add("kwsb-hide-notice");
					setTimeout(() => {
						notice_items[n].classList.add("kwsb-hidden-notice");
					}, 500);
				};
			}
		},
		// Initiate when the DOM loads.
		init: function () {
			window.kadenceWSB.initClose();
		},
	};
	if ("loading" === document.readyState) {
		// The DOM has not yet been loaded.
		document.addEventListener("DOMContentLoaded", window.kadenceWSB.init);
	} else {
		// The DOM has already been loaded.
		window.kadenceWSB.init();
	}
})();
if (window.jQuery) {
	jQuery(function ($) {
		// Common scroll to element code.
		$.scroll_to_notices = function (scrollElement) {
			console.log(scrollElement);
			if (scrollElement.length) {
				if (
					scrollElement.hasClass("kwsb-snackbar-notice") ||
					scrollElement.hasClass("woocommerce-notices-wrapper")
				) {
					return;
				} else {
					$("html, body").animate(
						{
							scrollTop: scrollElement.offset().top - 100,
						},
						1000
					);
				}
			}
		};
	});
}
