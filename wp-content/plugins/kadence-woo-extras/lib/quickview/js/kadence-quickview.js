/**
 * The class for quickview frontend.
 * global: kadenceQuickview
 * global: GLightbox
 */
class KTQuickview {
  /**
   * The quickviewButtons.
   */
  quickviewButtons = [];

  /**
   * The lightbox instance.
   */
  lightbox = null;

  /**
   * The lightbox instance.
   */
  initialSlides = [];

  /**
   * The main constructor.
   */
  constructor() {
    const self = this;
    this.initQuickview();
  }

  initQuickview(e) {
    const self = this;
    // console.log("frontend init");
    this.quickviewButtons = document.querySelectorAll(
      ".kt-woo-quickview-button"
    );
    var initAnyButtons = false;
    var i = 0;
    this.quickviewButtons.forEach((quickviewButton) => {
      initAnyButtons = true;
      // console.log("initializing a button");

      const productId = quickviewButton.dataset?.["productId"];
      quickviewButton.dataset.index = i;

      self.attachListeners(quickviewButton);

      //these will be the initial slide contents. They are placeholders with attached product id's
      //when they're loaded, we'll use the attached id to load in the full quickview content
      self.initialSlides.push({
        content:
          '<div id="kt-woo-quickview-content-' +
          productId +
          '" class="kt-woo-quickview-content loading"><img src="' +
          window.kadenceQuickview.ajax_loader +
          '" /></div>',
        alt: String(productId),
      });

      i++;
    });

    if (initAnyButtons) {
      self.initLightbox();
    }
  }

  initLightbox() {
    const self = this;
    // console.log("init lighbox");

    //in case we're re-initalizing, destroy the old lighbox instance before starting a new one.
    if (this.lightbox) {
      this.lightbox.destroy();
    }

    const classes = "kadence-dark quickview-lightbox" + ( window.kadenceQuickview.slides ? '' : ' no-slides' );

    this.lightbox = GLightbox({
      touchNavigation: true,
      loop: true,
      openEffect: "fade",
      closeEffect: "fade",
      skin: classes,
      preload: false,
      elements: this.initialSlides,
      draggable: window.kadenceQuickview.slides ? true : false,
      touchNavigation: window.kadenceQuickview.slides ? true : false,
      keyboardNavigation: window.kadenceQuickview.slides ? true : false
    });

    this.lightbox.on("slide_before_load", (data) => {
      // console.log("slide data", data.slideNode, data.slideConfig.alt);
      const productId = parseInt(data.slideConfig.alt);

      this.quickview(productId);
      //   setTimeout(() => {
      //     console.log("after delay: ", data.slideConfig.alt);
      //     if (data.slideConfig.alt) {

      //       const contentFrame = document.getElementById(
      //         "kt-woo-quickview-content-" + productId
      //       );
      //       console.log("we found a frame: ", contentFrame);
      //       contentFrame.innerHTML = "loaded";
      //     }
      //   }, 2000);
    });
  }

  attachListeners(quickviewButton) {
    const self = this;

    quickviewButton.addEventListener(
      "click",
      this.handleQuickviewButtonClick.bind(self)
    );
  }

  handleQuickviewButtonClick(e) {
    const self = this;

    e.preventDefault();

    const quickviewButton = e.target || e.srcElement;

    const productId = quickviewButton.dataset?.["productId"];
    const index = quickviewButton.dataset?.["index"];
    // console.log("quickview button clicked: ", productId, index);

    //first load the ajax content for this product id

    this.lightbox.openAt(index);
  }

  quickview(productId) {
    const self = this;

    this.getQuickviewHtml(productId).then(
      function (data) {
        if (data && data.success && data?.html) {
          // console.log("response: ", data.html, productId);

          const contentFrame = document.getElementById(
            "kt-woo-quickview-content-" + productId
          );

          if (contentFrame) {
            contentFrame.innerHTML = data.html;
            contentFrame.classList.remove("loading");
            const event = new CustomEvent("kt-quickview-load", {
              bubbles: true,
            });
            document.dispatchEvent(event);
          }
        }
      },
      function (error) {
        console.log("error", error);
      }
    );
  }

  async getQuickviewHtml(productId) {
    const settings = {
      method: "GET",
			headers: {
				'X-WP-Nonce': window.kadenceQuickview.ajax_nonce,
			},
    };

    try {
      const restEndpoint =
        window.kadenceQuickview.ajax_url +
        "?product_id=" +
        productId;
      const response = await fetch(restEndpoint, settings);

      if (response.status == 200) {
        const data = await response.json();
        return data;
      } else {
        return false;
      }
    } finally {
    }
  }
}

const initQuickview = () => {
  window.KTQuickviewInstance = new KTQuickview();

  document.addEventListener("kadenceJSInitReload", function () {
    window.KTQuickviewInstance.initQuickview();
  });
  document.addEventListener("kb-query-loaded", function () {
    window.KTQuickviewInstance.initQuickview();
  });
};

if ("loading" === document.readyState) {
  // The DOM has not yet been loaded.
  document.addEventListener("DOMContentLoaded", initQuickview);
} else {
  // The DOM has already been loaded.
  initQuickview();
}
