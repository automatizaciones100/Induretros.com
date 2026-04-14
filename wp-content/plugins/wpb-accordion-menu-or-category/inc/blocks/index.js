!(function() {
    "use strict";
    var t,
        e = window.wp.element,
        c = window.wp.i18n,
        l = window.wp.blocks,
        r = e.createElement( "svg", {
            width: "20px",
            height: "20px",
            viewBox: "0 0 20 20",
            xmlns: "http://www.w3.org/2000/svg"
          }, e.createElement("path", {
            fill: "#1E1E27",
            d: "M18.1818182,0 C19.1859723,0 20,0.814027728 20,1.81818182 L20,18.1818182 C20,19.1859723 19.1859723,20 18.1818182,20 L1.81818182,20 C0.814027728,20 0,19.1859723 0,18.1818182 L0,1.81818182 C0,0.814027728 0.814027728,0 1.81818182,0 L18.1818182,0 Z M14.4598225,7.6248997 C14.1970199,7.35521364 13.7653526,7.34963347 13.4956665,7.61243605 L13.4956665,7.61243605 L10.0046519,11.0143458 L6.58558122,7.6170873 C6.31846398,7.35167415 5.8867628,7.35305558 5.62134964,7.62017282 C5.35593648,7.88729006 5.35731792,8.31899125 5.62443516,8.58440441 L5.62443516,8.58440441 L9.51942697,12.4545479 C9.78350334,12.7169396 10.2092302,12.71901 10.4758462,12.4591992 L10.4758462,12.4591992 L14.4473588,8.58905566 C14.7170449,8.32625309 14.7226251,7.89458576 14.4598225,7.6248997 Z"
        } ) ),
        k = [
            (0, c.__)("accordion", "wpb-accordion-menu-or-category"),
            (0, c.__)("category", "wpb-accordion-menu-or-category"),
            (0, c.__)("menu", "wpb-accordion-menu-or-category"),
            (0, c.__)("posts", "wpb-accordion-menu-or-category"),
            (0, c.__)("products", "wpb-accordion-menu-or-category"),
            (0, c.__)("accordion menu", "wpb-accordion-menu-or-category"),
            (0, c.__)("list", "wpb-accordion-menu-or-category"),
            (0, c.__)("taxonomy", "wpb-accordion-menu-or-category"),
        ],
        a = window.wp.compose,
        o = window.wp.components,
        n = {
            from: [{
                type: "shortcode",
                tag: "wpb_wmca_accordion_pro",
                attributes: {
                    id: {
                        type: "integer",
                        shortcode: (t) => {
                            let {
                                named: {
                                    id: e
                                },
                            } = t;
                            return parseInt(e);
                        },
                    },
                    title: {
                        type: "string",
                        shortcode: (t) => {
                            let {
                                named: {
                                    title: e
                                },
                            } = t;
                            return e;
                        },
                    },
                },
            }, ],
            to: [{
                type: "block",
                blocks: ["core/shortcode"],
                transform: (t) => (0, l.createBlock)("core/shortcode", {
                    text: `[wpb_wmca_accordion_pro id="${t.id}" title="${t.title}"]`
                })
            }],
        };
    (window.wpbwmca = null !== (t = window.wpbwmca) && void 0 !== t ? t : {
        ShortCodes: []
    }),
    (0, l.registerBlockType)("wpb-accordion-menu-or-category-pro/wpb-wmca-shortcode-selector", {
        title: (0, c.__)("WPB Accordion", "wpb-accordion-menu-or-category"),
        description: (0, c.__)("Display WPB Accordion Menu or Categories.", "wpb-accordion-menu-or-category"),
        category: "widgets",
        attributes: {
            id: {
                type: "integer"
            },
            title: {
                type: "string"
            }
        },
        icon: r,
        keywords: k,
        transforms: n,
        edit: function t(l) {
            let {
                attributes: r,
                setAttributes: n
            } = l;
            const i = new Map();
            //console.log( window.wpbwmca.ShortCodes );
            
            if (
                (Object.entries(window.wpbwmca.ShortCodes).forEach((t) => {
                        let [e, c] = t;
                        i.set(c.id, c);
                    }),
                    !i.size && !r.id)
            )
                return (0, e.createElement)("div", {
                    className: "components-placeholder"
                }, (0, e.createElement)("p", null, (0, c.__)("No ShortCodes were found. Create an accordion ShortCode first.", "wpb-accordion-menu-or-category")));
            const s = Array.from(i.values(), (t) => ({
                value: t.id,
                label: t.title
            }));
            if (r.id) s.length || s.push({
                value: r.id,
                label: r.title
            });
            else {
                const t = s[0];
                r = {
                    id: parseInt(t.value),
                    title: t.label
                };
            }
            const m = `wpb-wmca-shortcode-selector-${(0, a.useInstanceId)(t)}`;
            return (0, e.createElement)(
                "div", {
                    className: "components-placeholder"
                },
                (0, e.createElement)("label", {
                    htmlFor: m,
                    className: "components-placeholder__label"
                }, (0, c.__)("Select an Accordion ShortCode:", "wpb-accordion-menu-or-category")),
                (0, e.createElement)(o.SelectControl, {
                    id: m,
                    options: s,
                    value: r.id,
                    onChange: (t) => n({
                        id: parseInt(t),
                        title: i.get(parseInt(t)).title
                    })
                })
            );
        },
        save: (t) => {
            var c, l, r, a;
            let {
                attributes: o
            } = t;
            return (
                (o = {
                    id: null !== (c = o.id) && void 0 !== c ? c : null === (l = window.wpbwmca.ShortCodes[0]) || void 0 === l ? void 0 : l.id,
                    title: null !== (r = o.title) && void 0 !== r ? r : null === (a = window.wpbwmca.ShortCodes[0]) || void 0 === a ? void 0 : a.title,
                }),
                (0, e.createElement)("div", null, '[wpb_wmca_accordion_pro id="', o.id, '" title="', o.title, '"]')
            );
        },
    });
})();