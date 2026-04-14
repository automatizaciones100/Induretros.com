(function ($) {
	"use strict";
	var combinators = [" ", ">", "+", "~"];
	var fraternisers = ["+", "~"];
	var complexTypes = ["ATTR", "PSEUDO", "ID", "CLASS"];
	function grok(msobserver) {
		if (!$.find.tokenize) {
			msobserver.isCombinatorial = true;
			msobserver.isFraternal = true;
			msobserver.isComplex = true;
			return;
		}
		msobserver.isCombinatorial = false;
		msobserver.isFraternal = false;
		msobserver.isComplex = false;
		var token = $.find.tokenize(msobserver.selector);
		for (var i = 0; i < token.length; i++) {
			for (var j = 0; j < token[i].length; j++) {
				if (combinators.indexOf(token[i][j].type) != -1)
					msobserver.isCombinatorial = true;
				if (fraternisers.indexOf(token[i][j].type) != -1)
					msobserver.isFraternal = true;
				if (complexTypes.indexOf(token[i][j].type) != -1)
					msobserver.isComplex = true;
			}
		}
	}
	var MutationSelectorObserver = function (selector, callback, options) {
		this.selector = selector.trim();
		this.callback = callback;
		this.options = options;
		grok(this);
	};
	var msobservers = [];
	msobservers.initialize = function (selector, callback, options) {
		var seen = [];
		var callbackOnce = function () {
			if (seen.indexOf(this) == -1) {
				seen.push(this);
				$(this).each(callback);
			}
		};
		$(options.target).find(selector).each(callbackOnce);
		var msobserver = new MutationSelectorObserver(
			selector,
			callbackOnce,
			options
		);
		this.push(msobserver);
		var observer = new MutationObserver(function (mutations) {
			var matches = [];
			for (var m = 0; m < mutations.length; m++) {
				if (mutations[m].type == "attributes") {
					if (mutations[m].target.matches(msobserver.selector))
						matches.push(mutations[m].target);
					if (msobserver.isFraternal)
						matches.push.apply(
							matches,
							mutations[m].target.parentElement.querySelectorAll(
								msobserver.selector
							)
						);
					else
						matches.push.apply(
							matches,
							mutations[m].target.querySelectorAll(
								msobserver.selector
							)
						);
				}
				if (mutations[m].type == "childList") {
					for (var n = 0; n < mutations[m].addedNodes.length; n++) {
						if (!(mutations[m].addedNodes[n] instanceof Element))
							continue;
						if (
							mutations[m].addedNodes[n].matches(
								msobserver.selector
							)
						)
							matches.push(mutations[m].addedNodes[n]);
						if (msobserver.isFraternal)
							matches.push.apply(
								matches,
								mutations[m].addedNodes[
									n
								].parentElement.querySelectorAll(
									msobserver.selector
								)
							);
						else
							matches.push.apply(
								matches,
								mutations[m].addedNodes[n].querySelectorAll(
									msobserver.selector
								)
							);
					}
				}
			}
			for (var i = 0; i < matches.length; i++)
				$(matches[i]).each(msobserver.callback);
		});
		var defaultObeserverOpts = {
			childList: true,
			subtree: true,
			attributes: msobserver.isComplex,
		};
		observer.observe(
			options.target,
			options.observer || defaultObeserverOpts
		);
		return observer;
	};
	$.fn.initialize = function (callback, options) {
		return msobservers.initialize(
			this.selector,
			callback,
			$.extend({}, $.initialize.defaults, options)
		);
	};
	$.initialize = function (selector, callback, options) {
		return msobservers.initialize(
			selector,
			callback,
			$.extend({}, $.initialize.defaults, options)
		);
	};
	$.initialize.defaults = {
		target: document.documentElement,
		observer: null,
	};
})(jQuery);

/* global kadence_wsb */
jQuery(function ($) {
	/**
	 * Object to handle notice UI.
	 */
	var kwsb = {
		/**
		 * Initialize notice UI events.
		 */
		init: function () {
			kwsb.rewrap_notices();
			var notice_items = document.querySelectorAll(
				".woocommerce-notices-wrapper > *:not(.cart-empty)"
			);
			if (notice_items.length) {
				for (let n = 0; n < notice_items.length; n++) {
					notice_items[n].classList.add("kwsb-snackbar-notice");
					this.add_dismiss(notice_items[n]);
				}
			}
			$(document).on("updated_wc_div", kwsb.reload_notices);
			$(document).on("applied_coupon", kwsb.reload_notices);
			$(document).on("update_checkout", kwsb.rewrap_notices);
			$(document).on("update_checkout", kwsb.reload_notices);
			$(document).on("init_checkout", kwsb.rewrap_notices);
			$(document).on("init_checkout", kwsb.reload_notices);
			$(document).on("checkout_error", kwsb.rewrap_notices);
			$(document).on("checkout_error", kwsb.reload_notices);
			$.initialize(
				".woocommerce-error",
				function () {
					kwsb.rewrap_notices();
					kwsb.reload_notices();
				},
				{
					target: $(
						".woocommerce > .woocommerce-notices-wrapper"
					).get(0),
				}
			);
		},
		rewrap_notices: function () {
			$(
				".woocommerce-checkout .woocommerce-NoticeGroup:not(.kwsb-snackbar-outer-wrap)"
			).each(function () {
				if ($(this).children().length != 0) {
					$(this).addClass("kwsb-snackbar-outer-wrap");
					$(this).wrapInner(
						"<div class='woocommerce-notices-wrapper'></div>"
					);
				}
			});
			$(
				".woocommerce-checkout .woocommerce-notices-wrapper ~ .woocommerce-error"
			).each(function () {
				$(this).wrap("<div class='woocommerce-notices-wrapper'></div>");
			});
			$(
				".woocommerce-checkout .woocommerce-notices-wrapper ~ .woocommerce-message"
			).each(function () {
				$(this).wrap("<div class='woocommerce-notices-wrapper'></div>");
			});
		},
		reload_notices: function () {
			var cart_empty_notice = document.querySelectorAll(
				".woocommerce-notices-wrapper .cart-empty"
			);
			if (cart_empty_notice.length) {
				for (let n = 0; n < cart_empty_notice.length; n++) {
					document
						.querySelector(".woocommerce-notices-wrapper")
						.after(cart_empty_notice[n]);
				}
			}
			var notice_items = document.querySelectorAll(
				".woocommerce-notices-wrapper > *:not(.kwsb-snackbar-notice):not(.cart-empty)"
			);
			if (notice_items.length) {
				for (let n = 0; n < notice_items.length; n++) {
					notice_items[n].classList.add("kwsb-snackbar-notice");
					kwsb.add_dismiss(notice_items[n]);
				}
			}
		},
		add_dismiss: function (element) {
			var btn = document.createElement("BUTTON");
			btn.classList.add("kwsb-close");
			btn.setAttribute("aria-label", kadence_wsb.close);
			element.appendChild(btn);
			btn.onclick = (e) => {
				e.preventDefault();
				element.classList.add("kwsb-hide-notice");
				setTimeout(() => {
					element.classList.add("kwsb-hidden-notice");
				}, 500);
				setTimeout(() => {
					if (element.classList.contains("woocommerce-error")) {
						while (element.firstChild) {
							element.removeChild(element.firstChild);
						}
						element.classList.remove("kwsb-hidden-notice");
						element.classList.remove("kwsb-hide-notice");
					}
				}, 1000);
			};
		},
	};
	kwsb.init();

	// Common scroll to element code.
	//wrapped in a timout to help it load after to override woocommerce default
	setTimeout(() => {
		$.scroll_to_notices = function (scrollElement) {
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
	}, 500);
});
