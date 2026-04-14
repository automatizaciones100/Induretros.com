/**
 * Conditional logic for CMB2 library
 * @author    Awran5 <github.com/awran5>
 * @version   1.1.0
 * @license   under GPL v2.0 (https://github.com/awran5/CMB2-conditional-logic/blob/master/LICENSE)
 * @copyright © 2018 Awran5. All rights reserved.
 *
 */ !(function (e) {
  "use strict";
  function t() {
    e("[data-kadence-condition-id], #wp-_kt_woo_badge_html-wrap").each(
      (t, o) => {
        let a =
            "wp-_kt_woo_badge_html-wrap" == o.id
              ? "_kt_woo_badge_type"
              : o.dataset.kadenceConditionId,
          n =
            "wp-_kt_woo_badge_html-wrap" == o.id
              ? "html"
              : o.dataset.kadenceConditionValue,
          c = !!o.dataset.conditionalInvert,
          i = o.closest(".cmb-row"),
          d = i.classList.contains("cmb-repeat-group-field"),
          r = !0 === c ? "show" : "hide";
        if (d) {
          let h;
          a = `${i
            .closest(".cmb-repeatable-group")
            .getAttribute("data-groupid")}[${i
            .closest(".cmb-repeatable-grouping")
            .getAttribute("data-iterator")}][${a}]`;
        }
        function u(e) {
          let t = n.includes(e) && "" !== e;
          return !0 === c && (t = !t), t;
        }
        function l(e, t) {
          ("hide" == t && !1 === c) || ("hide" != t && !1 !== c)
            ? e.hide()
            : e.show();
        }
        function s(e) {
          return (!!e.checked || !1 !== c) && (!e.checked || !1 === c);
        }
        e('[name="' + a + '"]').each(function (t, o) {
          "select-one" === o.type
            ? (u(o.value) || l(e(i), r),
              e(o).on("change", function (t) {
                u(t.target.value) ? l(e(i), "show") : l(e(i), "hide");
              }))
            : "radio" === o.type
            ? (!u(o.value) && s(o) && l(e(i), r),
              e(o).on("change", function (t) {
                u(t.target.value) ? l(e(i), "show") : l(e(i), "hide");
              }))
            : "checkbox" === o.type &&
              (s(o) || l(e(i), r),
              e(o).on("change", function (t) {
                t.target.checked ? l(e(i), "show") : l(e(i), "hide");
              }));
        });
      }
    );
  }
  t(),
    e(".cmb2-wrap > .cmb2-metabox").on("cmb2_add_row", function () {
      t();
    });
})(jQuery);
