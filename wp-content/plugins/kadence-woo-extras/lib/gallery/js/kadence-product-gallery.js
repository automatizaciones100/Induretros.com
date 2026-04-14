/*global jQuery*/
/*global kadence_pg*/
/*global Splide*/
/*global GLightbox*/
jQuery(function ($) {
	var KadenceProductGalleryisMobile = {
		Android: function () {
			return navigator.userAgent.match(/Android/i);
		},
		BlackBerry: function () {
			return navigator.userAgent.match(/BlackBerry/i);
		},
		iOS: function () {
			return navigator.userAgent.match(/iPhone|iPad|iPod/i);
		},
		Opera: function () {
			return navigator.userAgent.match(/Opera Mini/i);
		},
		Windows: function () {
			return navigator.userAgent.match(/IEMobile/i);
		},
		any: function () {
			return (
				KadenceProductGalleryisMobile.Android() ||
				KadenceProductGalleryisMobile.BlackBerry() ||
				KadenceProductGalleryisMobile.iOS() ||
				KadenceProductGalleryisMobile.Opera() ||
				KadenceProductGalleryisMobile.Windows()
			);
		},
	};
	/**
	 * Product gallery class.
	 */
	var KadenceProductGallery = function ($gallery) {
		const self = this;
		self.$gallery = $gallery;
		var splide_speed = $gallery.data("speed"),
			product_id = $gallery.data("product-id"),
			splide_animation = $gallery.data("animation"),
			splide_animation_speed = $gallery.data("animation-speed"),
			slick_arrows = $gallery.data("arrows"),
			slick_pagination = $gallery.data("pagination"),
			layout = $gallery.data("layout"),
			mdLayout = $gallery.data("md-layout"),
			smLayout = $gallery.data("sm-layout"),
			items_count = $gallery.data("gallery-items"),
			splide_thumbs_show = $gallery.data("thumb-show"),
			splide_thumbs_tablet_show = $gallery.data("md-thumb-show"),
			splide_thumbs_mobile_show = $gallery.data("sm-thumb-show"),
			splide_thumbs_width = $gallery.data("thumb-width"),
			splide_thumbs_tablet_width = $gallery.data("md-thumb-width"),
			splide_thumbs_mobile_width = $gallery.data("sm-thumb-width"),
			splide_thumbs_gap = $gallery.data("thumb-gap"),
			splide_thumbs_tablet_gap = $gallery.data("md-thumb-gap"),
			splide_thumbs_mobile_gap = $gallery.data("sm-thumb-gap"),
			splide_thumbs_center = $gallery.data("thumb-center"),
			splide_auto = $gallery.data("auto"),
			splide_auto_height = $gallery.data("auto-height"),
			visible_captions = $gallery.data("visible-captions"),
			$zoom = $gallery.data("zoom-active"),
			$zoom_type = $gallery.data("zoom-type"),
			splide_thumbs_on_hover = $gallery.data("thumb-hover"),
			//$items = $gallery.data('gallery-items'),
			fixedHeight = $gallery.find(".splide__track").height(),
			pauseonhover = false,
			thumbnails_have_changed = false,
			sliderDirection = "ltr";
		var $datathumbs = false;
		var original_items_count = items_count;
		self.$galleryThumbnails = $gallery
			.closest(".ksk-gallery")
			.find("#pg-thumbnails-" + product_id);
		self.$galleryExtra = $gallery
			.closest(".ksk-gallery")
			.siblings("#pg-extra-" + product_id);

		if (self.$galleryExtra.length) {
			$datathumbs = self.$galleryExtra.data("product_variation_images");
			if (
				$datathumbs["is_ajax"] === false &&
				$datathumbs["has_variation_images"] === false
			) {
				$datathumbs = false;
			}
		}
		if ($('html[dir="rtl"]').length) {
			sliderDirection = "rtl";
		}
		if (
			$zoom &&
			!KadenceProductGalleryisMobile.any() &&
			$(window).width() > 790
		) {
			pauseonhover = true;
		}
		$gallery.addClass("splide-initial");
		var current_layout = layout;
		var current_thumb_width = splide_thumbs_width;
		var current_thumbs_show = splide_thumbs_show;
		if ($(window).width() < 769) {
			current_layout = smLayout;
			current_thumb_width = splide_thumbs_mobile_width;
			current_thumbs_show = splide_thumbs_mobile_show;
		} else if ($(window).width() < 1024) {
			current_layout = mdLayout;
			current_thumb_width = splide_thumbs_tablet_width;
			current_thumbs_show = splide_thumbs_tablet_show;
		}
		if (
			current_layout === "tiles" ||
			current_layout === "grid" ||
			current_layout === "list"
		) {
			splide_auto = false;
		}
		var splideMain = new Splide(self.$gallery[0], {
			type: splide_animation ? "fade" : "slide",
			autoplay: splide_auto === true ? true : false,
			lazyLoad: "nearby",
			rewind: true,
			pagination: slick_pagination ? true : false,
			arrows: slick_arrows ? true : false,
			perPage: 1,
			pauseOnHover: pauseonhover === false ? false : true,
			direction: sliderDirection,
			interval: undefined !== splide_speed ? splide_speed : 7000,
			speed:
				undefined !== splide_animation_speed
					? splide_animation_speed
					: 400,
			easing:
				undefined !== splide_animation_speed &&
				splide_animation_speed > 1000
					? "linear"
					: "cubic-bezier(0.25, 1, 0.5, 1)",
		});
		splideMain.on("mounted", function () {
			$gallery.addClass("kt-product-gal-loaded");
		});
		if (
			splide_auto_height === true &&
			(current_layout === "above" || current_layout === "slider")
		) {
			var updateSlideHeight = (newIndex) => {
				var slide = splideMain.Components.Slides.getAt(
					typeof newIndex == "number" ? newIndex : splideMain.index
				).slide;
				slide.parentElement.style.height =
					slide.firstChild.offsetHeight + "px";
			};
			splideMain.on("mounted move resize", updateSlideHeight);
			var updateSlideHeightRefresh = (newIndex) => {
				var slide = splideMain.Components.Slides.getAt(
					typeof newIndex == "number" ? newIndex : splideMain.index
				).slide;
				setTimeout(() => {
					slide.parentElement.style.height =
						slide.firstChild.offsetHeight + "px";
				}, 100);
			};
			splideMain.on("refresh", updateSlideHeightRefresh);
		}
		self.kt_thumb_slideHeight = () => {
			self.$gallery.css("height", "auto");
			setTimeout(function () {
				var imageheight = self.$gallery.find(".splide__track").height();
				self.$galleryThumbnails.css("height", imageheight);
				self.$gallery.css("height", imageheight);
			}, 30);
		};
		if (current_layout === "left" || current_layout === "right") {
			self.kt_thumb_slideHeight();
			splideMain.on("mounted", function () {
				setTimeout(function () {
					self.kt_thumb_slideHeight();
				}, 100);
			});
		}
		self.clear_slide_layout = (
			the_layout,
			the_width,
			the_columns,
			the_count
		) => {
			if (the_layout === "left") {
				setTimeout(function () {
					self.$galleryThumbnails.css("width", the_width + "%");
					self.$galleryThumbnails
						.find(".thumb-wrapper")
						.css("maxWidth", "");
					self.$gallery.css("margin-left", the_width + "%");
					self.$gallery.css("margin-right", "");
				}, 30);
				setTimeout(function () {
					//this is a backup in case the first timeout is still too early.
					self.$gallery.css("margin-left", the_width + "%");
					self.$gallery.css("margin-right", "");
				}, 300);
				self.kt_thumb_slideHeight();
			} else if (the_layout === "right") {
				setTimeout(function () {
					self.$galleryThumbnails.css("width", the_width + "%");
					self.$galleryThumbnails
						.find(".thumb-wrapper")
						.css("maxWidth", "");
					self.$gallery.css("margin-right", the_width + "%");
					self.$gallery.css("margin-left", "");
				}, 30);
				setTimeout(function () {
					//this is a backup in case the first timeout is still too early.
					self.$gallery.css("margin-right", the_width + "%");
					self.$gallery.css("margin-left", "");
				}, 300);
				self.kt_thumb_slideHeight();
			} else {
				self.$gallery.css("margin-left", "");
				self.$gallery.css("margin-right", "");
				self.$gallery.css("height", "");
				self.$galleryThumbnails.css("height", "");
				self.$galleryThumbnails.css("width", "");
				if (the_count < the_columns) {
					self.$galleryThumbnails
						.find(".thumb-wrapper")
						.css(
							"maxWidth",
							Math.floor((100 / the_columns) * the_count) + "%"
						);
				} else {
					self.$galleryThumbnails
						.find(".thumb-wrapper")
						.css("maxWidth", "none");
				}
			}
		};
		self.clear_slide_layout(
			current_layout,
			current_thumb_width,
			current_thumbs_show,
			items_count
		);
		if (self.$galleryThumbnails.length) {
			var thumbnails = new Splide(self.$galleryThumbnails[0], {
				direction:
					layout === "left" || layout === "right"
						? "ttb"
						: sliderDirection,
				focus:
					splide_thumbs_center &&
					((current_layout !== "left" &&
						current_layout !== "right") ||
						(items_count > splide_thumbs_show &&
							(current_layout === "left" ||
								current_layout === "right")))
						? "center"
						: undefined,
				height: "100%",
				perPage:
					items_count < splide_thumbs_show &&
					layout !== "left" &&
					layout !== "right"
						? items_count
						: splide_thumbs_show,
				gap: splide_thumbs_gap,
				rewind: true,
				pagination: false,
				isNavigation: true,
				drag: false,
				arrows: true,
				breakpoints: {
					767: {
						direction:
							smLayout === "left" || smLayout === "right"
								? "ttb"
								: sliderDirection,
						perPage:
							items_count < splide_thumbs_mobile_show &&
							smLayout !== "left" &&
							smLayout !== "right"
								? items_count
								: splide_thumbs_mobile_show,
						gap: splide_thumbs_mobile_gap,
					},
					1024: {
						direction:
							mdLayout === "left" || mdLayout === "right"
								? "ttb"
								: sliderDirection,
						perPage:
							items_count < splide_thumbs_tablet_show &&
							mdLayout !== "left" &&
							mdLayout !== "right"
								? items_count
								: splide_thumbs_tablet_show,
						gap: splide_thumbs_tablet_gap,
					},
				},
			});

			splideMain.sync(thumbnails);
			splideMain.mount();
			thumbnails.mount();
		} else {
			splideMain.mount();
		}
		self.galleryChangeLayout = () => {
			// Mobile.
			if ($(window).width() < 769) {
				self.clear_slide_layout(
					smLayout,
					splide_thumbs_mobile_width,
					splide_thumbs_mobile_show,
					items_count
				);
			} else if ($(window).width() < 1024) {
				self.clear_slide_layout(
					mdLayout,
					splide_thumbs_tablet_width,
					splide_thumbs_tablet_show,
					items_count
				);
			} else {
				self.clear_slide_layout(
					layout,
					splide_thumbs_width,
					splide_thumbs_show,
					items_count
				);
			}
		};
		var galleryLayoutResizeTimer;
		$(window).on("resize", function () {
			clearTimeout(galleryLayoutResizeTimer);
			galleryLayoutResizeTimer = setTimeout(
				self.galleryChangeLayout(),
				100
			);
		});
		self.galleryUpdateThumbHover = () => {
			var galleryHoverClickTimer;
			self.$galleryThumbnails.find(".splide__slide").hover(function () {
				$thumb = $(this);
				clearTimeout(galleryHoverClickTimer);
				galleryHoverClickTimer = setTimeout(function () {
					$thumb.trigger("click");
				}, 100);
			});
		};
		if (splide_thumbs_on_hover && thumbnails) {
			self.galleryUpdateThumbHover();
		}

		self.init_product_zoom = () => {
			$(".zoomContainer").remove();
			self.$gallery.find(".is-active img").removeData("elevateZoom");
			self.$gallery.find(".is-active img").removeData("zoomImage");
			self.$gallery.find(".is-active img").elevateZoom({
				zoomType: $zoom_type,
				cursor: "crosshair",
				zoomWindowFadeIn: 300,
				zoomWindowFadeOut: 300,
			});
		};
		if (
			$zoom &&
			!KadenceProductGalleryisMobile.any() &&
			$(window).width() > 790
		) {
			splideMain.on("move", function () {
				$(".zoomContainer").remove();
			});
			splideMain.on("moved", function () {
				setTimeout(function () {
					self.init_product_zoom();
				}, 100);
			});
			var ktwoozoomresizeTimer;
			$(window).on("resize", function () {
				clearTimeout(ktwoozoomresizeTimer);
				ktwoozoomresizeTimer = setTimeout(self.init_product_zoom, 200);
			});
			self.init_product_zoom();
		}
		self.init_product_lightbox = () => {
			if (kadence_pg.lightbox) {
				return GLightbox({
					selector:
						"#pg-main-" +
						product_id +
						' .splide__slide a[data-rel^="lightbox"]:not([target="_blank"])',
					touchNavigation: true,
					skin: kadence_pg.lightbox_style,
					loop: true,
					openEffect: "fade",
					closeEffect: "fade",
					autoplayVideos: true,
					plyr: {
						css: kadence_pg.plyr_css,
						js: kadence_pg.plyr_js,
						config: {
							hideControls: true,
						},
					},
				});
			}
		};
		var glightbox = self.init_product_lightbox();
		self.reload_product_lightbox = () => {
			if (kadence_pg.lightbox) {
				if (glightbox) {
					glightbox.reload();
				}
			}
		};
		$(".variations_form")
			.find(".single_variation")
			.on("show_variation", function (event, variation, purchasable) {
				var $product_img_wrap = self.$gallery.find(
					".woocommerce-main-image"
				);
				var $product_img = kadence_pg.lightbox
					? $product_img_wrap.find("img")
					: self.$gallery.find(".woo-main-slide img");
				var $product_thumb_wrap = self.$galleryThumbnails.find(
					".woocommerce-main-image-thumb"
				);
				var $product_thumb_img = $product_thumb_wrap.find("img");
				// Change zoom image
				if (
					variation &&
					variation.image.src &&
					variation.image.src.length > 1
				) {
					// See if the gallery has an image with the same original src as the image we want to switch to.
					var galleryHasImage =
						self.$gallery.find(
							'.splide__slide img[data-o_src="' +
								variation.image.src +
								'"]'
						).length > 0;
					// If the gallery has the image, reset the images. We'll scroll to the correct one.
					if (galleryHasImage) {
						self.kt_wc_variations_image_reset();
					}
					// See if gallery has a matching image we can slide to.
					var gal_non_retina =
						variation.image.gallery_thumbnail_src.replace(
							variation.image.gallery_thumbnail_src_w +
								"x" +
								variation.image.gallery_thumbnail_src_h,
							Math.floor(
								variation.image.gallery_thumbnail_src_w / 2
							) +
								"x" +
								Math.floor(
									variation.image.gallery_thumbnail_src_h / 2
								)
						);
					var slideToImage = self.$galleryThumbnails.find(
						'.splide__slide img[src="' + gal_non_retina + '"]'
					);
					if (slideToImage.length == 0) {
						slideToImage = self.$galleryThumbnails.find(
							'.splide__slide img[src="' +
								variation.image.gallery_thumbnail_src +
								'"]'
						);
					}

					if (slideToImage.length > 0 && !$datathumbs) {
						//var galIndex = slideToImage.parent().data( 'slick-index' ) .trigger( 'click' );
						slideToImage.parent().trigger("click");
						if (
							self.$gallery.data("zoom-active") &&
							!KadenceProductGalleryisMobile.any()
						) {
							$(".zoomContainer").remove();
							self.$gallery
								.find(".is-active img")
								.removeData("elevateZoom");
							self.$gallery
								.find(".is-active img")
								.removeData("zoomImage");
							self.$gallery.find(".is-active img").elevateZoom({
								zoomType: $zoom_type,
								cursor: "crosshair",
								zoomWindowFadeIn: 300,
								zoomWindowFadeOut: 300,
							});
						}
						return;
					}

					// Go to first slide
					var slideCount = splideMain.length;
					if (1 < slideCount) {
						splideMain.go(0);
					}
					$product_img.kt_wc_set_variation_attr(
						"data-zoom-image",
						variation.image.full_src
					);
					$product_img.kt_wc_set_variation_attr(
						"src",
						variation.image.src
					);
					if (variation.image.srcset) {
						$product_img.kt_wc_set_variation_attr(
							"srcset",
							variation.image.srcset
						);
					} else {
						$product_img.kt_wc_set_variation_attr("srcset", "");
					}
					if (variation.image.title) {
						$product_img.kt_wc_set_variation_attr(
							"title",
							variation.image.title
						);
					} else {
						$product_img.kt_wc_set_variation_attr("title", "");
					}
					if (variation.image.caption) {
						$product_img.kt_wc_set_variation_attr(
							"data-caption",
							variation.image.caption
						);
					} else {
						$product_img.kt_wc_set_variation_attr(
							"data-caption",
							""
						);
					}
					if (visible_captions === true) {
						if (variation.image.caption) {
							$product_img_wrap
								.find(".sp-gal-image-caption")
								.html(variation.image.caption);
							$product_img_wrap
								.find(".sp-gal-image-caption")
								.fadeIn();
						} else {
							$product_img_wrap
								.find(".sp-gal-image-caption")
								.fadeOut();
						}
					}
					if ($product_img_wrap.hasClass("kt-woo-video-link")) {
						$product_img_wrap.find(".kt-woo-play-btn").hide();
						$product_thumb_wrap.find(".kt-woo-play-btn").hide();
						$product_img_wrap.removeClass("kt-woo-video-link");
						$product_img_wrap.addClass("kt-woo-orig-video-link");
					}
					$product_img.kt_wc_set_variation_attr(
						"alt",
						variation.image.alt
					);
					$product_img_wrap.kt_wc_set_variation_attr(
						"href",
						variation.image.full_src
					);
					$product_thumb_img.kt_wc_set_variation_attr(
						"src",
						variation.image.gallery_thumbnail_src
					);
					if (variation.image.gallery_thumbnail_srcset) {
						$product_thumb_img.kt_wc_set_variation_attr(
							"srcset",
							variation.image.gallery_thumbnail_srcset
						);
					} else {
						$product_thumb_img.kt_wc_set_variation_attr(
							"srcset",
							""
						);
					}
					$product_thumb_img.kt_wc_set_variation_attr(
						"alt",
						variation.image.alt
					);
					if ($datathumbs) {
						if ($datathumbs["is_ajax"]) {
							self.kt_wc_variations_run_ajax_call(
								variation.variation_id
							);
						} else {
							self.kt_wc_variations_run_thumbnails(
								variation.variation_id
							);
						}
					}
					splideMain.refresh();
					if (
						self.$gallery.data("zoom-active") &&
						!KadenceProductGalleryisMobile.any()
					) {
						$(".zoomContainer").remove();
						self.$gallery
							.find(".is-active img")
							.removeData("elevateZoom");
						self.$gallery
							.find(".is-active img")
							.removeData("zoomImage");
						self.$gallery.find(".is-active img").elevateZoom({
							zoomType: $zoom_type,
							cursor: "crosshair",
							zoomWindowFadeIn: 300,
							zoomWindowFadeOut: 300,
						});
					}
					self.reload_product_lightbox();
				} else {
					self.kt_wc_variations_image_reset();
				}
			});
		self.kt_wc_variations_original_thumbnails = () => {
			if (thumbnails_have_changed) {
				splideMain.remove(".splide__slide:not(.woo-main-slide)");
				splideMain.add($datathumbs["original"]["images"]);
				items_count = original_items_count;
				if (thumbnails) {
					thumbnails.remove(
						".splide__slide:not(.woocommerce-main-image-thumb)"
					);
					self.$galleryThumbnails.css("height", "auto");
					if (
						items_count < current_thumbs_show &&
						current_layout !== "left" &&
						current_layout !== "right"
					) {
						thumbnails.options.perPage = items_count;
					} else if (
						thumbnails.options.perPage < current_thumbs_show
					) {
						thumbnails.options.perPage = current_thumbs_show;
						if (splide_thumbs_center) {
							thumbnails.options.focus =
								(current_layout !== "left" &&
									current_layout !== "right") ||
								(items_count > current_thumbs_show &&
									(current_layout === "left" ||
										current_layout === "right"))
									? "center"
									: undefined;
						}
					} else if (
						thumbnails.options.perPage >= current_thumbs_show
					) {
						if (splide_thumbs_center) {
							thumbnails.options.focus =
								(current_layout !== "left" &&
									current_layout !== "right") ||
								(items_count > current_thumbs_show &&
									(current_layout === "left" ||
										current_layout === "right"))
									? "center"
									: undefined;
						}
					}
					thumbnails.add($datathumbs["original"]["thumbnails"]);
					if (splide_thumbs_on_hover) {
						self.galleryUpdateThumbHover();
					}
				}
				self.clear_slide_layout(
					current_layout,
					current_thumb_width,
					current_thumbs_show,
					original_items_count
				);
				self.reload_product_lightbox();
				thumbnails_have_changed = false;
			}
		};
		self.kt_wc_variations_run_thumbnails = (variation_id) => {
			if (false === $datathumbs[variation_id]) {
				self.kt_wc_variations_original_thumbnails();
			} else {
				self.kt_wc_variations_switch_thumbnails(
					$datathumbs[variation_id]
				);
			}
		};
		self.kt_wc_variations_switch_thumbnails = (thumbnails_data) => {
			thumbnails_have_changed = true;
			splideMain.remove(".splide__slide:not(.woo-main-slide)");
			splideMain.add(thumbnails_data["images"]);
			items_count = thumbnails_data["thumbnails"].length + 1;
			if (thumbnails) {
				thumbnails.remove(
					".splide__slide:not(.woocommerce-main-image-thumb)"
				);
				self.$galleryThumbnails.css("height", "auto");
				if (
					items_count < current_thumbs_show &&
					current_layout !== "left" &&
					current_layout !== "right"
				) {
					thumbnails.options.perPage = items_count;
				} else if (thumbnails.options.perPage < current_thumbs_show) {
					thumbnails.options.perPage = current_thumbs_show;
					if (splide_thumbs_center) {
						thumbnails.options.focus =
							(current_layout !== "left" &&
								current_layout !== "right") ||
							(items_count > current_thumbs_show &&
								(current_layout === "left" ||
									current_layout === "right"))
								? "center"
								: undefined;
					}
				} else if (thumbnails.options.perPage >= current_thumbs_show) {
					if (splide_thumbs_center) {
						thumbnails.options.focus =
							(current_layout !== "left" &&
								current_layout !== "right") ||
							(items_count > current_thumbs_show &&
								(current_layout === "left" ||
									current_layout === "right"))
								? "center"
								: undefined;
					}
				}
				thumbnails.add(thumbnails_data["thumbnails"]);
				if (splide_thumbs_on_hover) {
					self.galleryUpdateThumbHover();
				}
			}
			self.clear_slide_layout(
				current_layout,
				current_thumb_width,
				current_thumbs_show,
				items_count
			);
			self.reload_product_lightbox();
		};
		self.kt_wc_variations_run_ajax_call = (variation_id) => {
			var $data = {
				action: "kadence_variation_images_load_frontend_ajax",
				ajaxkwsvNonce: kadence_pg.ajax_nonce,
				variation_id: variation_id,
				post_id: product_id,
			};
			$.post(kadence_pg.ajax_url, $data, function (response) {
				if (response.length) {
					response = JSON.parse(response);
					// replace with new image set
					self.kt_wc_variations_switch_thumbnails(response);
				} else {
					// replace with original image set
					self.kt_wc_variations_original_thumbnails();
				}
			});
		};
		self.kt_wc_variations_image_reset = () => {
			var $product_img_wrap = self.$gallery.find(
				".woocommerce-main-image"
			);
			var $product_img = kadence_pg.lightbox
				? $product_img_wrap.find("img")
				: self.$gallery.find(".woo-main-slide img");
			var $product_thumb_wrap = self.$galleryThumbnails.find(
				".woocommerce-main-image-thumb"
			);
			var $product_thumb_img = $product_thumb_wrap.find("img");
			$product_img.kt_wc_reset_variation_attr("src");
			$product_img.kt_wc_reset_variation_attr("srcset");
			$product_img.kt_wc_reset_variation_attr("data-caption");
			$product_img.kt_wc_reset_variation_attr("alt");
			if (visible_captions === true) {
				if (undefined !== $product_img.attr("data-o_data-caption")) {
					$product_img_wrap
						.find(".sp-gal-image-caption")
						.html($product_img.attr("data-o_data-caption"));
					$product_img_wrap.find(".sp-gal-image-caption").fadeIn();
				} else {
					$product_img_wrap.find(".sp-gal-image-caption").fadeOut();
				}
			}
			if ($product_img_wrap.hasClass("kt-woo-orig-video-link")) {
				$product_img_wrap.addClass("kt-woo-video-link");
				$product_img_wrap.find(".kt-woo-play-btn").show();
				$product_thumb_wrap.find(".kt-woo-play-btn").show();
				$product_img_wrap.removeClass("kt-woo-orig-video-link");
			}
			$product_img.kt_wc_reset_variation_attr("title");
			$product_img.kt_wc_reset_variation_attr("data-zoom-image");
			$product_thumb_img.kt_wc_reset_variation_attr("src");
			$product_thumb_img.kt_wc_reset_variation_attr("srcset");
			$product_thumb_img.kt_wc_reset_variation_attr("alt");
			$product_img_wrap.kt_wc_reset_variation_attr("href");
			self.kt_wc_variations_original_thumbnails();
			if (
				self.$gallery.data("zoom-active") &&
				!KadenceProductGalleryisMobile.any()
			) {
				var slideCount = splideMain.length;
				if (1 < slideCount) {
					splideMain.go(0);
				}
				$(".zoomContainer").remove();
				self.$gallery.find(".is-active img").removeData("elevateZoom");
				self.$gallery.find(".is-active img").removeData("zoomImage");
				self.$gallery.find(".is-active img").elevateZoom({
					zoomType: $zoom_type,
					cursor: "crosshair",
					zoomWindowFadeIn: 300,
					zoomWindowFadeOut: 300,
				});
			}
			splideMain.refresh();
			self.reload_product_lightbox();
		};
		$.fn.kt_wc_set_variation_attr = function (attr, value) {
			if (undefined === this.attr("data-o_" + attr)) {
				this.attr(
					"data-o_" + attr,
					!this.attr(attr) ? "" : this.attr(attr)
				);
			}
			this.attr(attr, value);
		};
		$.fn.kt_wc_reset_variation_attr = function (attr) {
			if (undefined !== this.attr("data-o_" + attr)) {
				this.attr(attr, this.attr("data-o_" + attr));
			}
		};
		$(".variations_form").on("click", ".reset_variations", function () {
			self.kt_wc_variations_image_reset();
		});
	};
	/**
	 * Function to call kt_wc_product_gallery on jquery selector.
	 */
	$.fn.kt_wc_product_gallery = function () {
		var $gallery = this;
		if (!$gallery.hasClass("splide-initial")) {
			if (typeof Splide === "function") {
				new KadenceProductGallery($gallery);
			} else {
				// eslint-disable-next-line vars-on-top
				var initLoadDelay = setInterval(function () {
					if (typeof Splide === "function") {
						new KadenceProductGallery($gallery);
						clearInterval(initLoadDelay);
					}
				}, 200);
			}
		}
		return this;
	};
	// $.extend(true, $.magnificPopup.defaults, {
	// 	tClose: '',
	// 	tLoading: kadence_pg.loading, // Text that is displayed during loading. Can contain %curr% and %total% keys
	// 	gallery: {
	// 		tPrev: '', // Alt text on left arrow
	// 		tNext: '', // Alt text on right arrow
	// 		tCounter: kadence_pg.of // Markup for "1 of 7" counter
	// 	},
	// 	image: {
	// 		tError: kadence_pg.error, // Error message when image could not be loaded
	// 		titleSrc: function(item) {
	// 			return item.el.find('img').attr('alt');
	// 			}
	// 		}
	// });
	/*
	 * Initialize all galleries on page.
	 */
	window.initKaenceProductGalleries = () => {
		$(".kadence-ga-splide-init").each(function () {
			$(this).kt_wc_product_gallery();
		});
	};

	window.initKaenceProductGalleries();

	document.addEventListener("kb-query-loaded", function () {
		window.initKaenceProductGalleries();
	});
	document.addEventListener("kt-quickview-load", function () {
		window.initKaenceProductGalleries();
	});
});
