/**
 * The admin class for the badges admin page.
 * global: kadenceAdminBadges
 */
class KTBadgeAdmin {
  /**
   * The previewButton.
   */
  previewButton;

  /**
   * The previewFrame.
   */
  previewFrame;

  /**
   * The previewContainer.
   */
  previewContainer;

  /**
   * The previewContainerInner.
   */
  previewContainerInner;

  /**
   * The main constructor.
   */
  constructor() {
    const self = this;
    this.previewContainer = document.querySelector("#_kt_woo_badge_preview");
    this.previewContainerInner = this.previewContainer.querySelector(".inside");
    this.previewFrame = document.createElement("div");
    this.previewFrame.classList.add("kt-woo-badge-preview-frame");
    this.previewButton = document.createElement("button");
    this.previewButton.classList.add(
      "kt-woo-badge-preview-button",
      "button",
      "button-primary",
      "button-large"
    );
    this.previewButton.textContent = "Preview";
    this.previewContainerInner.appendChild(this.previewFrame);
    // this.previewContainerInner.appendChild(this.previewButton);
    // this.previewButton.addEventListener("click", this.previewClick.bind(self));
    this.preview();
  }
  previewClick(e) {
    const self = this;
    e.preventDefault();
    this.preview();
  }

  preview() {
    const self = this;

    this.getPreviewHtml().then(
      function (data) {
        if (data && data.success && data?.data?.html) {
          self.previewFrame.innerHTML = data.data.html;
        }
      },
      function (error) {
        console.log("error", error);
      }
    );
  }

  async getPreviewHtml() {
    const settings = {
      method: "GET",
    };

    try {
      const restEndpoint =
        window.kadenceAdminBadges.ajax_url +
        "?action=kt_woo_get_badge_html&_ajax_nonce=" +
        kadenceAdminBadges.ajax_nonce +
        "&post_id=" +
        window.kadenceAdminBadges.post_id;
      const response = await fetch(restEndpoint, settings);

      if (response.status == 200) {
        const data = await response.json();
        return data;
      }
    } finally {
    }
  }
}

const init = () => {
  window.KTBadgeAdminInstance = new KTBadgeAdmin();
};

if ("loading" === document.readyState) {
  // The DOM has not yet been loaded.
  document.addEventListener("DOMContentLoaded", init);
} else {
  // The DOM has already been loaded.
  init();
}
