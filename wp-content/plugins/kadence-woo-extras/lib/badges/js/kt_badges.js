/**
 * The admin class for the badges admin page.
 */
class KTBadges {
	/**
	 * The badges.
	 */
	badges = [];

	/**
	 * The main constructor.
	 */
	constructor() {
		const self = this;
		this.initBadges();
	}

	initBadges(e) {
		const self = this;
		this.badges = document.querySelectorAll(".kt-woo-badge");
		this.badges.forEach((badge) => {
			const attachedPostId = badge.dataset?.["attachedPostId"];

			self.placeBadge(badge);
		});
	}

	placeBadge(badge) {
		if (badge) {
			const pagetype = badge.dataset?.["pageType"];
			if (pagetype == "loop") {
				const loopWrapper = badge.closest(
					".product.has-post-thumbnail"
				);
				if (loopWrapper) {
					const imgContainer = loopWrapper.querySelector(
						".woocommerce-loop-product__link, .wc-block-grid__product-image > a, .product_item_link"
					);
					if (imgContainer) {
						imgContainer.appendChild(badge);
						badge.classList.add("kt-woo-badge-active");
					}
				}
			} else if (pagetype == "kadence-wootemplate-blocks-gallery") {
				const wrapper = badge.previousElementSibling;
				if (wrapper) {
					const imgContainer =
						wrapper.querySelector(".product_image");
					if (imgContainer) {
						imgContainer.appendChild(badge);
						badge.classList.add("kt-woo-badge-active");
					}
				}
			} else if (pagetype == "kadence-wootemplate-blocks-image") {
				const wrapper = badge.previousElementSibling;
				if (wrapper) {
					const imgContainer = wrapper.querySelector(
						".woocommerce-product-gallery__image, .woocommerce-loop-image-link"
					);
					if (imgContainer) {
						imgContainer.appendChild(badge);
						badge.classList.add("kt-woo-badge-active");
					}
				}
			} else if (pagetype == "woocommerce-single-product") {
				const wrapper = badge.previousElementSibling;
				if (wrapper) {
					const imgContainer = wrapper.querySelector(
						".wc-block-components-product-image"
					);
					if (imgContainer) {
						imgContainer.appendChild(badge);
						badge.classList.add("kt-woo-badge-active");
					}
				}
			} else if (pagetype == "woocommerce_blocks_product_grid") {
				const loopWrapper = badge.closest(
					".loop-entry, .wc-block-grid__product"
				);
				if (loopWrapper) {
					const imgContainer = loopWrapper.querySelector(
						".woocommerce-loop-image-link, .wc-block-grid__product-image"
					);
					if (imgContainer) {
						imgContainer.appendChild(badge);
						badge.classList.add("kt-woo-badge-active");
					}
				}
			} else if (pagetype == "woocommerce-product-image") {
				const loopWrapper = badge.closest(".product");
				if (loopWrapper) {
					const imgContainer = loopWrapper.querySelector(
						".woocommerce-loop-product__link, .wc-block-grid__product-image > a"
					);
					if (imgContainer) {
						imgContainer.appendChild(badge);
						badge.classList.add("kt-woo-badge-active");
					}
				}
			} else {
				const wrapper = document.querySelector(".product");
				if (wrapper) {
					const imgContainer = wrapper.querySelector(
						".product_image, .woocommerce-product-gallery__image"
					);
					if (imgContainer) {
						imgContainer.appendChild(badge);
						badge.classList.add("kt-woo-badge-active");
					}
				}
			}
		}
	}
}

const init = () => {
	window.KTBadgeAdminInstance = new KTBadges();

	document.addEventListener("kadenceJSInitReload", function () {
		window.KTBadgeAdminInstance.initBadges();
	});
	document.addEventListener("kb-query-loaded", function () {
		window.KTBadgeAdminInstance.initBadges();
	});
	document.addEventListener("kt-quickview-load", function () {
		window.KTBadgeAdminInstance.initBadges();
	});
};

if ("loading" === document.readyState) {
	// The DOM has not yet been loaded.
	document.addEventListener("DOMContentLoaded", init);
} else {
	// The DOM has already been loaded.
	init();
}
