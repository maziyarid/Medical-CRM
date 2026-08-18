import { r as h, j as e, R as fe, a as Ne } from "./vendor-react-CfDz6BDZ.js";
import {
  u as ve,
  L as m,
  O as we,
  a as ae,
  B as ye,
  R as Ce,
  b as x,
} from "./vendor-router-YUMSJXsu.js";
import { u as P, m as b, A } from "./vendor-motion-nN19JxQh.js";
import {
  N as K,
  I,
  Y as ke,
  a as De,
  T as Ee,
  D as y,
  E as Se,
  b as Fe,
  c as ze,
  F as Be,
  P as T,
  d as Le,
  J as C,
  L as le,
  e as re,
  S as O,
  f as ie,
  C as L,
  g as M,
  h as ne,
  i as oe,
  j as Me,
  H as S,
  k as f,
  l as ce,
  G as R,
  m as E,
  B as v,
  n as Ie,
  o as de,
  p as Ae,
  q as Re,
  r as _e,
} from "./chunk-legal-BS3zghI-.js?v=4.3.1";
import {
  l as $,
  P as u,
  X as G,
  x as Pe,
  j as F,
  n as q,
  y as xe,
  z as Te,
  D as Oe,
  m as j,
  J as $e,
  r as N,
  E as We,
  e as Q,
  f as me,
  h as W,
  C as D,
  K as H,
  S as he,
  v as Ge,
  N as X,
  o as qe,
  g as U,
  O as Qe,
  Q as Z,
  R as He,
  V as pe,
  H as be,
  T as Ue,
  Y as Ve,
  B as Ye,
  _ as Ke,
  $ as Xe,
  F as Ze,
} from "./vendor-lucide-BPdX3gUv.js";
import {
  B as Je,
  a as es,
  b as ss,
  c as ts,
  d as as,
  e as ls,
  f as rs,
  g as is,
  h as ns,
  i as os,
  j as cs,
} from "./chunk-blog-OEKuWYr9.js?v=4.3.1";
import {
  S as ds,
  a as xs,
  b as ms,
  c as hs,
  d as ps,
  e as bs,
  f as gs,
  g as us,
  h as js,
  i as fs,
  j as Ns,
} from "./chunk-services-DErEHQ9P.js?v=4.3.1";
(function () {
  const r = document.createElement("link").relList;
  if (r && r.supports && r.supports("modulepreload")) return;
  for (const t of document.querySelectorAll('link[rel="modulepreload"]')) n(t);
  new MutationObserver((t) => {
    for (const i of t)
      if (i.type === "childList")
        for (const a of i.addedNodes) a.tagName === "LINK" && a.rel === "modulepreload" && n(a);
  }).observe(document, { childList: !0, subtree: !0 });
  function l(t) {
    const i = {};
    return (
      t.integrity && (i.integrity = t.integrity),
      t.referrerPolicy && (i.referrerPolicy = t.referrerPolicy),
      t.crossOrigin === "use-credentials"
        ? (i.credentials = "include")
        : t.crossOrigin === "anonymous"
          ? (i.credentials = "omit")
          : (i.credentials = "same-origin"),
      i
    );
  }
  function n(t) {
    if (t.ep) return;
    t.ep = !0;
    const i = l(t);
    fetch(t.href, i);
  }
})();
function vs() {
  return P()
    ? { hidden: {}, visible: {} }
    : {
        hidden: { opacity: 0, y: 24 },
        visible: { opacity: 1, y: 0, transition: { duration: 0.45, ease: [0.22, 1, 0.36, 1] } },
      };
}
function ge(s = 0.08) {
  return P()
    ? { hidden: {}, visible: {} }
    : { hidden: {}, visible: { transition: { staggerChildren: s } } };
}
function ue() {
  return P()
    ? { hidden: {}, visible: {} }
    : {
        hidden: { opacity: 0, scale: 0.95 },
        visible: {
          opacity: 1,
          scale: 1,
          transition: { duration: 0.25, ease: [0.34, 1.56, 0.64, 1] },
        },
        exit: { opacity: 0, scale: 0.95, transition: { duration: 0.15 } },
      };
}
function ws() {
  return P()
    ? { hidden: {}, visible: {} }
    : {
        hidden: { opacity: 0, x: -16, scale: 0.95 },
        visible: {
          opacity: 1,
          x: 0,
          scale: 1,
          transition: { duration: 0.25, ease: [0.22, 1, 0.36, 1] },
        },
      };
}
function ys() {
  const [s, r] = h.useState(!1),
    [l, n] = h.useState(!1),
    [t, i] = h.useState(!1),
    a = h.useRef(null),
    c = ve(),
    p = ue(),
    z = ge(0.04),
    k = vs();
  return (
    h.useEffect(() => {
      const d = () => i(window.scrollY > 50);
      return (
        window.addEventListener("scroll", d, { passive: !0 }),
        () => window.removeEventListener("scroll", d)
      );
    }, []),
    h.useEffect(() => {
      const d = (o) => {
        a.current && !a.current.contains(o.target) && n(!1);
      };
      return (
        document.addEventListener("mousedown", d),
        () => document.removeEventListener("mousedown", d)
      );
    }, []),
    h.useEffect(() => {
      (r(!1), n(!1));
    }, [c.pathname]),
    e.jsxs("nav", {
      role: "navigation",
      "aria-label": "ناوبری اصلی",
      className: `site-nav fixed top-0 left-0 right-0 z-[100] w-full transition-all duration-200 border-b backdrop-blur-md ${t ? "bg-white/95 backdrop-blur-md shadow-md border-[#DDE2DD]" : "bg-white border-[#DDE2DD]"}`,
      children: [
        e.jsxs("div", {
          className: "max-w-[1200px] mx-auto px-4 sm:px-6 flex items-center justify-between h-16",
          children: [
            e.jsxs(m, {
              to: "/",
              "aria-label": "صفحه اصلی — دکتر شاهین باستانی‌نژاد",
              className: "flex items-center gap-3 group flex-shrink-0",
              children: [
                e.jsx("img", {
                  src: "/wp-content/themes/drbastaninejad-theme/assets/dist6/logo.svg",
                  alt: "لوگو کلینیک دکتر باستانی‌نژاد",
                  className: "w-9 h-9 object-contain",
                  width: "36",
                  height: "36",
                }),
                e.jsxs("div", {
                  className: "leading-tight",
                  children: [
                    e.jsx("div", {
                      className: "text-sm font-bold text-[#25272C]",
                      children: "دکتر شاهین باستانی‌نژاد",
                    }),
                    e.jsx("div", {
                      className: "text-[10px] text-[#545B64]",
                      children: "جراح پلاستیک بینی · تهران",
                    }),
                  ],
                }),
              ],
            }),
            e.jsxs("div", {
              className: "hidden lg:flex items-center gap-0.5",
              ref: a,
              children: [
                [
                  { label: "خانه", href: "/" },
                  { label: "درباره دکتر", href: "/about" },
                ].map((d) =>
                  e.jsx(
                    m,
                    {
                      to: d.href,
                      "aria-current": c.pathname === d.href ? "page" : void 0,
                      className:
                        "px-3 py-2 rounded-lg text-sm text-[#545B64] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-all font-medium",
                      children: d.label,
                    },
                    d.label,
                  ),
                ),
                e.jsxs("div", {
                  className: "relative",
                  children: [
                    e.jsxs("button", {
                      onClick: () => n((d) => !d),
                      className: `flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-all ${l ? "bg-[#E4F0E4] text-[#28722C]" : "text-[#545B64] hover:text-[#28722C] hover:bg-[#E4F0E4]"}`,
                      "aria-expanded": l,
                      "aria-haspopup": "true",
                      children: [
                        "جراحی بینی",
                        e.jsx(b.span, {
                          animate: { rotate: l ? 180 : 0 },
                          transition: { duration: 0.2 },
                          children: e.jsx($, { size: 14, "aria-hidden": !0 }),
                        }),
                      ],
                    }),
                    e.jsx(A, {
                      children:
                        l &&
                        e.jsxs(b.div, {
                          className:
                            "absolute top-full right-0 mt-1 w-[600px] bg-white border border-[#DDE2DD] rounded-2xl shadow-xl p-4 grid grid-cols-3 gap-4",
                          role: "menu",
                          variants: p,
                          initial: "hidden",
                          animate: "visible",
                          exit: "exit",
                          children: [
                            K.map((d) =>
                              e.jsxs(
                                "div",
                                {
                                  children: [
                                    e.jsx("p", {
                                      className:
                                        "text-[10px] font-bold text-[#545B64] uppercase tracking-widest mb-2 px-2",
                                      children: d.group,
                                    }),
                                    d.items.map((o) => {
                                      const g = o.icon;
                                      return e.jsxs(
                                        m,
                                        {
                                          to: o.href,
                                          role: "menuitem",
                                          className:
                                            "flex items-start gap-2.5 p-2 rounded-xl hover:bg-[#E4F0E4] transition-colors group",
                                          onClick: () => n(!1),
                                          children: [
                                            e.jsx("span", {
                                              className:
                                                "w-7 h-7 rounded-lg bg-[#E4F0E4] flex items-center justify-center flex-shrink-0 group-hover:bg-[#28722C] transition-colors",
                                              children: e.jsx(g, {
                                                size: 13,
                                                className:
                                                  "text-[#28722C] group-hover:text-white transition-colors",
                                                "aria-hidden": !0,
                                              }),
                                            }),
                                            e.jsxs("span", {
                                              className: "min-w-0",
                                              children: [
                                                e.jsx("span", {
                                                  className:
                                                    "text-xs font-semibold text-[#25272C] block leading-tight",
                                                  children: o.label,
                                                }),
                                                e.jsx("span", {
                                                  className:
                                                    "text-[10px] text-[#545B64] leading-tight",
                                                  children: o.sub,
                                                }),
                                              ],
                                            }),
                                          ],
                                        },
                                        o.href,
                                      );
                                    }),
                                  ],
                                },
                                d.group,
                              ),
                            ),
                            e.jsxs("div", {
                              className:
                                "col-span-3 border-t border-[#DDE2DD] pt-3 mt-1 flex gap-3",
                              children: [
                                e.jsx(m, {
                                  to: "/services",
                                  className:
                                    "flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold text-[#28722C] bg-[#E4F0E4] rounded-lg hover:bg-[#28722C] hover:text-white transition-colors",
                                  children: "همه خدمات",
                                }),
                                e.jsx("a", {
                                  href: "/booking",
                                  className:
                                    "flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-semibold text-white bg-[#28722C] rounded-lg hover:bg-[#246b28] transition-colors",
                                  children: "رزرو نوبت",
                                }),
                              ],
                            }),
                          ],
                        }),
                    }),
                  ],
                }),
                [
                  { label: "گالری", href: "/gallery" },
                  { label: "مقالات", href: "/blog" },
                  { label: "سوالات", href: "/faq" },
                  { label: "تماس", href: "/contact" },
                ].map((d) =>
                  e.jsx(
                    m,
                    {
                      to: d.href,
                      "aria-current": c.pathname === d.href ? "page" : void 0,
                      className:
                        "px-3 py-2 rounded-lg text-sm text-[#545B64] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-all font-medium",
                      children: d.label,
                    },
                    d.label,
                  ),
                ),
              ],
            }),
            e.jsxs("div", {
              className: "flex items-center gap-2",
              children: [
                e.jsxs("a", {
                  href: "tel:02186087250",
                  className:
                    "hidden md:flex items-center gap-1.5 text-sm text-[#28722C] font-semibold hover:text-[#246b28] transition-colors",
                  "aria-label": "تماس تلفنی با کلینیک",
                  children: [e.jsx(u, { size: 13, "aria-hidden": !0 }), "۰۲۱۸۶۰۸۷۲۵۰"],
                }),
                e.jsx("a", {
                  href: "/booking",
                  className:
                    "hidden sm:inline-flex items-center gap-1.5 px-4 py-2 bg-[#28722C] text-white text-sm font-bold rounded-lg hover:bg-[#246b28] transition-colors",
                  children: "رزرو نوبت",
                }),
                e.jsx("button", {
                  onClick: () => r((d) => !d),
                  className: "lg:hidden p-2 rounded-lg hover:bg-[#E4F0E4] transition-colors",
                  "aria-label": s ? "بستن منو" : "باز کردن منو",
                  "aria-expanded": s,
                  "aria-controls": "mobile-menu",
                  children: s
                    ? e.jsx(G, { size: 20, "aria-hidden": !0 })
                    : e.jsx(Pe, { size: 20, "aria-hidden": !0 }),
                }),
              ],
            }),
          ],
        }),
        e.jsx(A, {
          children:
            s &&
            e.jsx(b.div, {
              id: "mobile-menu",
              className:
                "lg:hidden bg-white border-t border-[#DDE2DD] max-h-[80vh] overflow-y-auto",
              role: "dialog",
              "aria-modal": "true",
              "aria-label": "منوی موبایل",
              initial: { opacity: 0, y: -8 },
              animate: { opacity: 1, y: 0 },
              exit: { opacity: 0, y: -8 },
              transition: { duration: 0.22, ease: [0.22, 1, 0.36, 1] },
              children: e.jsxs(b.div, {
                className: "p-4 space-y-1",
                variants: z,
                initial: "hidden",
                animate: "visible",
                children: [
                  [
                    { href: "/", label: "خانه" },
                    { href: "/about", label: "درباره دکتر" },
                  ].map((d) =>
                    e.jsx(
                      b.div,
                      {
                        variants: k,
                        children: e.jsx(m, {
                          to: d.href,
                          "aria-current": c.pathname === d.href ? "page" : void 0,
                          className:
                            "flex items-center gap-3 py-3 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm font-medium text-[#25272C]",
                          children: d.label,
                        }),
                      },
                      d.href,
                    ),
                  ),
                  e.jsx("div", {
                    className:
                      "text-xs font-bold text-[#545B64] px-3 pt-3 pb-1 uppercase tracking-widest",
                    children: "جراحی بینی",
                  }),
                  K.flatMap((d) => d.items).map((d) =>
                    e.jsx(
                      b.div,
                      {
                        variants: k,
                        children: e.jsxs(m, {
                          to: d.href,
                          className:
                            "flex items-center gap-3 py-2.5 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm text-[#25272C]",
                          children: [
                            e.jsx("span", {
                              className:
                                "w-6 h-6 rounded-lg bg-[#E4F0E4] flex items-center justify-center flex-shrink-0",
                              children: e.jsx(d.icon, {
                                size: 12,
                                className: "text-[#28722C]",
                                "aria-hidden": !0,
                              }),
                            }),
                            d.label,
                          ],
                        }),
                      },
                      d.href,
                    ),
                  ),
                  e.jsx("div", {
                    className:
                      "text-xs font-bold text-[#545B64] px-3 pt-3 pb-1 uppercase tracking-widest",
                    children: "صفحات",
                  }),
                  [
                    { href: "/gallery", label: "گالری" },
                    { href: "/blog", label: "مقالات" },
                    { href: "/faq", label: "سوالات" },
                    { href: "/contact", label: "تماس" },
                  ].map((d) =>
                    e.jsx(
                      b.div,
                      {
                        variants: k,
                        children: e.jsx(m, {
                          to: d.href,
                          "aria-current": c.pathname === d.href ? "page" : void 0,
                          className:
                            "flex py-2.5 px-3 rounded-xl hover:bg-[#E4F0E4] text-sm text-[#25272C]",
                          children: d.label,
                        }),
                      },
                      d.href,
                    ),
                  ),
                ],
              }),
            }),
        }),
      ],
    })
  );
}
function Cs({ className: s }) {
  return e.jsx("svg", {
    viewBox: "0 0 24 24",
    fill: "currentColor",
    "aria-hidden": !0,
    className: s,
    children: e.jsx("path", {
      d: "M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z",
    }),
  });
}
function ks({ className: s }) {
  return e.jsx("svg", {
    viewBox: "0 0 24 24",
    fill: "currentColor",
    "aria-hidden": !0,
    className: s,
    children: e.jsx("path", {
      d: "M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z",
    }),
  });
}
function Ds({ className: s }) {
  return e.jsx("svg", {
    viewBox: "0 0 24 24",
    fill: "currentColor",
    "aria-hidden": !0,
    className: s,
    children: e.jsx("path", {
      d: "M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z",
    }),
  });
}
function Es({ className: s }) {
  return e.jsx("svg", {
    viewBox: "0 0 24 24",
    fill: "currentColor",
    "aria-hidden": !0,
    className: s,
    children: e.jsx("path", {
      d: "M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 2c5.523 0 10 4.477 10 10S17.523 22 12 22 2 17.523 2 12 6.477 2 12 2zm-1.5 5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9zm0 1.8a2.7 2.7 0 1 1 0 5.4 2.7 2.7 0 0 1 0-5.4zm4.8-.9a.9.9 0 1 0 0 1.8.9.9 0 0 0 0-1.8z",
    }),
  });
}
const Ss = [
    { label: "اینستاگرام", href: I, Icon: Cs, color: "hover:text-pink-400" },
    { label: "یوتیوب", href: ke, Icon: ks, color: "hover:text-red-400" },
    { label: "آپارات", href: De, Icon: Es, color: "hover:text-red-500" },
    { label: "تلگرام", href: Ee, Icon: Ds, color: "hover:text-sky-400" },
  ],
  Fs = [
    {
      name: "Google Maps",
      href: "https://maps.google.com/?q=35.758881,51.413824",
      icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/GoogleMap.webp",
    },
    {
      name: "نشان",
      href: "https://nshn.ir/gNbNgYZt_Rxg",
      icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/Neshan.webp",
    },
    {
      name: "بلد",
      href: "https://balad.ir/p/3cuwGGif58f8hT",
      icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/Balad.webp",
    },
    {
      name: "Waze",
      href: "https://waze.com/ul/htnke3vwqs",
      icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/Waze.webp",
    },
  ];
function zs() {
  return e.jsx("footer", {
    className: "bg-[#1a2318] text-white/80",
    role: "contentinfo",
    children: e.jsxs("div", {
      className: "max-w-[1200px] mx-auto px-4 sm:px-6 pt-8",
      children: [
        e.jsx("div", {
          className: "flex flex-wrap justify-center gap-6 pb-6 border-b border-white/10 mb-6",
          children: Ss.map(({ label: s, href: r, Icon: l, color: n }) =>
            e.jsxs(
              "a",
              {
                href: r,
                target: "_blank",
                rel: "noopener noreferrer",
                "aria-label": s,
                className: `flex items-center gap-2 text-sm text-white/70 transition-colors ${n}`,
                children: [
                  e.jsx(l, { className: "w-5 h-5 flex-shrink-0" }),
                  e.jsx("span", { children: s }),
                ],
              },
              s,
            ),
          ),
        }),
        e.jsxs("div", {
          className:
            "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 pb-8 text-center sm:text-right",
          children: [
            e.jsxs("div", {
              children: [
                e.jsxs("div", {
                  className: "flex items-center justify-center sm:justify-start gap-2 mb-3",
                  children: [
                    e.jsx("img", {
                      src: "/wp-content/themes/drbastaninejad-theme/assets/dist6/logo-white.svg",
                      alt: "لوگو دکتر باستانی‌نژاد",
                      className: "w-9 h-9 object-contain",
                      width: "36",
                      height: "36",
                    }),
                    e.jsx("span", { className: "font-bold text-sm text-white", children: y }),
                  ],
                }),
                e.jsxs("p", {
                  className: "text-xs leading-relaxed mb-3 text-center sm:text-right",
                  children: [
                    "جراح و متخصص گوش، گلو و بینی",
                    e.jsx("br", {}),
                    "جراح پلاستیک بینی — تهران",
                  ],
                }),
                e.jsxs("div", {
                  className: "flex gap-2 items-center justify-center sm:justify-start",
                  children: [
                    e.jsx("a", {
                      href: Se,
                      target: "_blank",
                      rel: "noopener",
                      "aria-label": "نماد اعتماد الکترونیکی",
                      className:
                        "rounded-xl overflow-hidden bg-white/10 border border-white/20 hover:bg-white/20 transition-colors",
                      children: e.jsx("img", {
                        src: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/enamad.webp",
                        alt: "نماد اعتماد",
                        className: "w-12 h-12 object-contain p-0.5",
                        loading: "lazy",
                      }),
                    }),
                    e.jsx("a", {
                      href: Fe,
                      target: "_blank",
                      rel: "noopener",
                      "aria-label": "انجمن جراحان گوش، گلو و بینی",
                      className:
                        "rounded-xl overflow-hidden bg-white/10 border border-white/20 hover:bg-white/20 transition-colors",
                      children: e.jsx("img", {
                        src: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/namad-n1.webp",
                        alt: "نشان انجمن",
                        className: "w-12 h-12 object-contain p-0.5",
                        loading: "lazy",
                      }),
                    }),
                  ],
                }),
              ],
            }),
            e.jsxs("nav", {
              "aria-label": "لینک‌های سریع",
              children: [
                e.jsx("h4", {
                  className: "text-sm font-bold text-white mb-4",
                  children: "لینک‌های سریع",
                }),
                e.jsx("ul", {
                  className: "space-y-2",
                  children: ze.map((s) =>
                    e.jsx(
                      "li",
                      {
                        children: e.jsx(m, {
                          to: s.href,
                          className: "text-xs text-white/60 hover:text-[#28722C] transition-colors",
                          children: s.label,
                        }),
                      },
                      s.href,
                    ),
                  ),
                }),
              ],
            }),
            e.jsxs("nav", {
              "aria-label": "خدمات",
              children: [
                e.jsx("h4", { className: "text-sm font-bold text-white mb-4", children: "خدمات" }),
                e.jsx("ul", {
                  className: "space-y-2",
                  children: Be.map((s) =>
                    e.jsx(
                      "li",
                      {
                        children: e.jsx(m, {
                          to: s.href,
                          className: "text-xs text-white/60 hover:text-[#28722C] transition-colors",
                          children: s.label,
                        }),
                      },
                      s.href,
                    ),
                  ),
                }),
              ],
            }),
            e.jsxs("div", {
              children: [
                e.jsx("h4", {
                  className: "text-sm font-bold text-white mb-4",
                  children: "تماس با ما",
                }),
                e.jsxs("div", {
                  className: "space-y-4",
                  children: [
                    e.jsxs("div", {
                      className:
                        "flex items-start gap-3 justify-center sm:justify-start text-right",
                      children: [
                        e.jsx("div", {
                          className:
                            "w-7 h-7 rounded-lg bg-[#28722C]/20 flex items-center justify-center flex-shrink-0 mt-0.5",
                          children: e.jsx(u, { size: 12, className: "text-[#28722C]" }),
                        }),
                        e.jsxs("div", {
                          children: [
                            e.jsx("p", {
                              className: "text-[10px] text-white/50 mb-0.5",
                              children: "تلفن کلینیک",
                            }),
                            T.slice(0, 2).map((s) =>
                              e.jsx(
                                "a",
                                {
                                  href: `tel:${s.replace(/\D/g, "")}`,
                                  className:
                                    "text-xs text-white/80 hover:text-[#28722C] block transition-colors",
                                  dir: "ltr",
                                  children: s,
                                },
                                s,
                              ),
                            ),
                          ],
                        }),
                      ],
                    }),
                    e.jsxs("div", {
                      className:
                        "flex items-start gap-3 justify-center sm:justify-start text-right",
                      children: [
                        e.jsx("div", {
                          className:
                            "w-7 h-7 rounded-lg bg-[#28722C]/20 flex items-center justify-center flex-shrink-0 mt-0.5",
                          children: e.jsx(F, { size: 12, className: "text-[#28722C]" }),
                        }),
                        e.jsxs("div", {
                          children: [
                            e.jsx("p", {
                              className: "text-[10px] text-white/50 mb-0.5",
                              children: "ساعت پذیرش",
                            }),
                            e.jsxs("p", {
                              className: "text-xs text-white/80",
                              children: ["شنبه تا سه‌شنبه", e.jsx("br", {}), "۱۵:۰۰ تا ۱۸:۰۰"],
                            }),
                          ],
                        }),
                      ],
                    }),
                    e.jsxs("div", {
                      className:
                        "flex items-start gap-3 justify-center sm:justify-start text-right",
                      children: [
                        e.jsx("div", {
                          className:
                            "w-7 h-7 rounded-lg bg-[#28722C]/20 flex items-center justify-center flex-shrink-0 mt-0.5",
                          children: e.jsx(q, { size: 12, className: "text-[#28722C]" }),
                        }),
                        e.jsxs("div", {
                          children: [
                            e.jsx("p", {
                              className: "text-[10px] text-white/50 mb-0.5",
                              children: "آدرس",
                            }),
                            e.jsx("p", {
                              className: "text-xs text-white/70 leading-relaxed",
                              children: Le,
                            }),
                            e.jsx("div", {
                              className: "flex gap-2 mt-2 flex-wrap",
                              children: Fs.map((s) =>
                                e.jsx(
                                  "a",
                                  {
                                    href: s.href,
                                    target: "_blank",
                                    rel: "noopener",
                                    "aria-label": s.name,
                                    className:
                                      "w-9 h-9 rounded-lg bg-white border border-white/40 hover:bg-[#E4F0E4] transition-colors overflow-hidden flex items-center justify-center shadow-sm",
                                    title: s.name,
                                    children: e.jsx("img", {
                                      src: s.icon,
                                      alt: s.name,
                                      className: "w-5 h-5 object-contain",
                                      loading: "lazy",
                                    }),
                                  },
                                  s.name,
                                ),
                              ),
                            }),
                          ],
                        }),
                      ],
                    }),
                  ],
                }),
              ],
            }),
          ],
        }),
        e.jsx("div", {
          className:
            "flex flex-wrap justify-center gap-4 py-3 border-t border-white/10 text-xs text-white/40",
          children: [
            { label: "سیاست حریم خصوصی", href: "/legal/privacy" },
            { label: "شرایط استفاده", href: "/legal/terms" },
            { label: "سیاست لغو", href: "/legal/cancellation" },
            { label: "نقشه سایت", href: "/sitemap" },
          ].map((s) =>
            e.jsx(
              m,
              {
                to: s.href,
                className: "hover:text-[#28722C] transition-colors",
                children: s.label,
              },
              s.href,
            ),
          ),
        }),
        e.jsxs("div", {
          className:
            "py-4 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-white/40",
          children: [
            e.jsxs("span", { children: ["© ۱۴۰۵ کلینیک ", y, " — تمامی حقوق محفوظ است."] }),
            e.jsxs("span", {
              children: [
                "طراحی توسط",
                " ",
                e.jsxs("a", {
                  href: "https://maziyarid.com",
                  target: "_blank",
                  rel: "noopener",
                  className: "text-[#28722C] hover:text-white transition-colors",
                  children: ["MA", e.jsx("span", { className: "font-black", children: "Z" })],
                }),
              ],
            }),
          ],
        }),
      ],
    }),
  });
}
const Bs = "/booking",
  Ls = [
    {
      type: "phone-1",
      label: "۰۲۱۸۶۰۸۷۲۵۰",
      href: "tel:02186087250",
      color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#1a4e1d] hover:bg-[#d0e8d1]",
      icon: e.jsx(u, { size: 12, "aria-hidden": !0 }),
    },
    {
      type: "phone-2",
      label: "۰۲۱۸۸۲۰۵۶۰۶",
      href: "tel:02188205606",
      color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#1a4e1d] hover:bg-[#d0e8d1]",
      icon: e.jsx(u, { size: 12, "aria-hidden": !0 }),
    },
    {
      type: "phone-3",
      label: "۰۹۹۱۲۴۹۶۶۵۹",
      href: "tel:09912496659",
      color: "bg-[#E4F0E4] border-[#28722C]/20 text-[#1a4e1d] hover:bg-[#d0e8d1]",
      icon: e.jsx(u, { size: 12, "aria-hidden": !0 }),
    },
    {
      type: "booking",
      label: "رزرو نوبت آنلاین",
      href: Bs,
      color: "bg-[#28722C] border-[#28722C] text-white hover:bg-[#246b28]",
      icon: e.jsx(Te, { size: 12, "aria-hidden": !0 }),
    },
  ];
function Ms() {
  const [s, r] = h.useState(!1),
    [l, n] = h.useState(!1),
    t = Ls;
  ue();
  const i = ge(0.06),
    a = ws();
  return e.jsxs("div", {
    className: "chaty",
    "aria-label": "ارتباط سریع",
    role: "complementary",
    children: [
      e.jsx(A, {
        children:
          s &&
          e.jsxs(b.div, {
            className: "chaty__panel",
            initial: { opacity: 0, x: 24, scale: 0.95 },
            animate: { opacity: 1, x: 0, scale: 1 },
            exit: { opacity: 0, x: 24, scale: 0.95 },
            transition: { type: "spring", stiffness: 380, damping: 28 },
            role: "dialog",
            "aria-modal": "true",
            "aria-label": "گزینه‌های تماس",
            children: [
              e.jsxs("div", {
                className: "p-3 border-b border-[#DDE2DD]",
                children: [
                  e.jsx("p", {
                    className: "text-xs font-bold text-[#25272C]",
                    children: "ارتباط سریع",
                  }),
                  e.jsx("p", {
                    className: "text-[10px] text-[#545B64]",
                    children: "ارتباط با مشاوران",
                  }),
                ],
              }),
              e.jsx(b.div, {
                className: "p-2 space-y-1.5",
                variants: i,
                initial: "hidden",
                animate: "visible",
                children: t.map((c) =>
                  e.jsxs(
                    b.a,
                    {
                      href: c.href,
                      variants: a,
                      target: c.href.startsWith("http") ? "_blank" : void 0,
                      rel: c.href.startsWith("http") ? "noopener noreferrer" : void 0,
                      className: `flex items-center gap-2 px-3 py-2 rounded-xl border text-xs font-semibold transition-colors ${c.color}`,
                      children: [c.icon, c.label],
                    },
                    c.type,
                  ),
                ),
              }),
            ],
          }),
      }),
      e.jsxs(b.button, {
        onClick: () => {
          (r((c) => !c), n(!0));
        },
        className: "chaty__trigger",
        "aria-label": s ? "بستن گزینه‌های تماس" : "باز کردن گزینه‌های تماس",
        "aria-expanded": s,
        "aria-haspopup": "dialog",
        whileHover: { scale: 1.06 },
        whileTap: { scale: 0.95 },
        transition: { type: "spring", stiffness: 400, damping: 17 },
        children: [
          e.jsx(A, {
            mode: "wait",
            initial: !1,
            children: s
              ? e.jsx(
                  b.span,
                  {
                    initial: { rotate: -90, opacity: 0 },
                    animate: { rotate: 0, opacity: 1 },
                    exit: { rotate: 90, opacity: 0 },
                    transition: { duration: 0.15 },
                    children: e.jsx(G, { size: 20, className: "text-white" }),
                  },
                  "x",
                )
              : e.jsx(
                  b.span,
                  {
                    initial: { rotate: 90, opacity: 0 },
                    animate: { rotate: 0, opacity: 1 },
                    exit: { rotate: -90, opacity: 0 },
                    transition: { duration: 0.15 },
                    children: e.jsx(xe, { size: 22, className: "text-white" }),
                  },
                  "msg",
                ),
          }),
          !s &&
            !l &&
            e.jsx(b.span, {
              className:
                "absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full text-[10px] text-white flex items-center justify-center font-bold",
              "aria-label": "۱ پیام جدید",
              animate: { scale: [1, 1.15, 1], opacity: [1, 0.85, 1] },
              transition: { duration: 1.6, repeat: 1 / 0, ease: "easeInOut" },
              children: "۱",
            }),
        ],
      }),
    ],
  });
}
function Is() {
  const [s, r] = h.useState(!1);
  return (
    h.useEffect(() => {
      const l = () => r(window.scrollY > 400);
      return (window.addEventListener("scroll", l), () => window.removeEventListener("scroll", l));
    }, []),
    s
      ? e.jsx("button", {
          onClick: () => window.scrollTo({ top: 0, behavior: "smooth" }),
          className:
            "fixed bottom-24 left-4 sm:bottom-6 sm:left-6 z-40 w-10 h-10 bg-white border border-[#DDE2DD] rounded-xl shadow-md flex items-center justify-center hover:bg-[#E4F0E4] hover:border-[#28722C] transition-all",
          "aria-label": "بازگشت به بالا",
          children: e.jsx(Oe, { size: 16, className: "text-[#28722C]" }),
        })
      : null
  );
}
function As() {
  const drbLocale = window.__DRB_I18N__ || window.__DRB_WP__ || {};
  return e.jsxs("div", {
    dir: drbLocale.dir || "rtl",
    lang: drbLocale.htmlLang || drbLocale.lang || "fa-IR",
    className: "drb-react-root min-h-screen bg-[#F9F6F1] text-[#25272C] ",
    style: { fontFamily: "'Irancell', 'Tahoma', Arial, system-ui, sans-serif" },
    children: [
      e.jsx(ys, {}),
      e.jsx("main", { className: "pt-16", children: e.jsx(we, {}) }),
      e.jsx(zs, {}),
      e.jsx(Ms, {}),
      e.jsx(Is, {}),
    ],
  });
}
function je({ before: s, after: r, fallback: l }) {
  const [n, t] = h.useState(50),
    i = h.useRef(null),
    a = h.useRef(!1),
    c = (o) => {
      if (!i.current) return;
      const g = i.current.getBoundingClientRect(),
        w = Math.max(8, Math.min(92, ((o - g.left) / g.width) * 100));
      t(w);
    },
    p = (o) => {
      var V, Y;
      if (!i.current) return;
      const g = i.current.getBoundingClientRect(),
        w = ((o.clientX - g.left) / g.width) * 100;
      Math.abs(w - n) > 18 ||
        ((a.current = !0),
        (Y = (V = o.target).setPointerCapture) == null || Y.call(V, o.pointerId));
    },
    z = (o) => {
      a.current && c(o.clientX);
    },
    k = () => {
      a.current = !1;
    },
    d = (o) => {
      o.target.src = l;
    };
  return e.jsxs("div", {
    ref: i,
    className:
      "relative w-full aspect-[3/4] rounded-2xl overflow-hidden bg-muted shadow-md ring-1 ring-black/5 touch-pan-y",
    onPointerDown: p,
    onPointerMove: z,
    onPointerUp: k,
    onPointerCancel: k,
    role: "img",
    "aria-label": "مقایسه تصویر قبل و بعد از عمل — برای دیدن بهتر، دستگیره وسط را بکشید",
    children: [
      e.jsx("img", {
        src: r,
        alt: "بعد از جراحی",
        className: "absolute inset-0 w-full h-full object-cover pointer-events-none",
        loading: "lazy",
        decoding: "async",
        onError: d,
      }),
      e.jsx("div", {
        className: "absolute inset-0 overflow-hidden pointer-events-none",
        style: { clipPath: `inset(0 ${100 - n}% 0 0)` },
        children: e.jsx("img", {
          src: s,
          alt: "قبل از جراحی",
          className: "absolute inset-0 w-full h-full object-cover",
          loading: "lazy",
          decoding: "async",
          onError: d,
        }),
      }),
      e.jsxs("div", {
        className:
          "absolute top-0 bottom-0 z-10 w-12 -ml-6 flex items-center justify-center cursor-ew-resize touch-none",
        style: { left: `${n}%` },
        onPointerDown: (o) => {
          var g, w;
          (o.stopPropagation(),
            (a.current = !0),
            (w = (g = o.target).setPointerCapture) == null || w.call(g, o.pointerId),
            c(o.clientX));
        },
        onPointerMove: z,
        onPointerUp: k,
        children: [
          e.jsx("div", { className: "w-0.5 h-full bg-white shadow-[0_0_12px_rgba(0,0,0,0.35)]" }),
          e.jsxs("div", {
            className:
              "absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white shadow-xl flex items-center justify-center gap-0.5 ring-2 ring-primary/25",
            children: [
              e.jsx(j, { className: "w-3.5 h-3.5 text-primary" }),
              e.jsx($e, { className: "w-3.5 h-3.5 text-primary" }),
            ],
          }),
        ],
      }),
      e.jsx("span", {
        className:
          "absolute top-3 right-3 z-10 bg-black/55 backdrop-blur-sm text-white text-[11px] font-bold px-2.5 py-1 rounded-full pointer-events-none",
        children: "قبل",
      }),
      e.jsx("span", {
        className:
          "absolute top-3 left-3 z-10 bg-primary/90 text-white text-[11px] font-bold px-2.5 py-1 rounded-full pointer-events-none",
        children: "بعد",
      }),
      e.jsx("span", {
        className:
          "absolute bottom-3 inset-x-0 z-10 text-center text-[10px] text-white/90 pointer-events-none sm:hidden",
        children: "دستگیره را بکشید",
      }),
    ],
  });
}
const Rs = {
  "rhinoplasty-primary": e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("path", {
        d: "M20 8 C14 8 10 14 10 20 C10 26 14 30 20 30 C26 30 30 26 30 20",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
        fill: "none",
      }),
      e.jsx("path", {
        d: "M20 8 L22 6 L28 4 L30 8 L26 10 L20 8Z",
        fill: "currentColor",
        opacity: ".5",
      }),
      e.jsx("circle", { cx: "20", cy: "22", r: "3", fill: "currentColor" }),
      e.jsx("path", {
        d: "M28 4 L36 12",
        stroke: "currentColor",
        strokeWidth: "1.5",
        strokeLinecap: "round",
      }),
    ],
  }),
  "rhinoplasty-revision": e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("path", {
        d: "M20 9 C14 9 10 14 10 20",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
      }),
      e.jsx("path", {
        d: "M10 20 C10 26 14 31 20 31 C26 31 30 26 30 20",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
      }),
      e.jsx("path", {
        d: "M26 7 L30 11 L26 15",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
        strokeLinejoin: "round",
      }),
      e.jsx("path", {
        d: "M14 25 L10 29 L14 33",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
        strokeLinejoin: "round",
      }),
      e.jsx("circle", { cx: "20", cy: "20", r: "2.5", fill: "currentColor" }),
    ],
  }),
  "unavailable-thick-skin": e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("ellipse", {
        cx: "20",
        cy: "22",
        rx: "9",
        ry: "11",
        stroke: "currentColor",
        strokeWidth: "2",
        fill: "none",
      }),
      e.jsx("ellipse", {
        cx: "20",
        cy: "24",
        rx: "5",
        ry: "5",
        fill: "currentColor",
        opacity: ".3",
      }),
      e.jsx("path", {
        d: "M14 18 C14 13 26 13 26 18",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
      }),
    ],
  }),
  "rhinoplasty-bony": e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("path", {
        d: "M18 8 L16 14 L14 18 L17 22 L20 30 L23 22 L26 18 L24 14 L22 8 Z",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinejoin: "round",
        fill: "none",
      }),
      e.jsx("path", {
        d: "M17 14 L23 14",
        stroke: "currentColor",
        strokeWidth: "1.5",
        opacity: ".5",
      }),
      e.jsx("path", {
        d: "M15 18 L25 18",
        stroke: "currentColor",
        strokeWidth: "1.5",
        opacity: ".5",
      }),
    ],
  }),
  "rhinoplasty-natural": e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("path", {
        d: "M20 8 C19 10 18 14 18 18 C18 22 18 26 16 28 C18 30 22 30 24 28 C22 26 22 22 22 18 C22 14 21 10 20 8Z",
        stroke: "currentColor",
        strokeWidth: "2",
        fill: "none",
        strokeLinejoin: "round",
      }),
      e.jsx("path", {
        d: "M16 28 C16 31 24 31 24 28",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
      }),
      e.jsx("path", {
        d: "M20 8 L20 6",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
      }),
    ],
  }),
  "unavailable-stylized": e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("path", {
        d: "M20 10 C19 13 18 17 18 21 C18 25 17 28 15 30 C18 33 22 33 25 30 C23 28 22 25 22 21 C22 17 21 13 20 10Z",
        stroke: "currentColor",
        strokeWidth: "2",
        fill: "none",
      }),
      e.jsx("path", {
        d: "M18 10 L16 6 M20 10 L20 6 M22 10 L24 6",
        stroke: "currentColor",
        strokeWidth: "1.5",
        strokeLinecap: "round",
        opacity: ".6",
      }),
    ],
  }),
  "hump-removal": e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("path", {
        d: "M12 30 L15 20 L18 14 L22 14 L25 20 L28 30",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
        strokeLinejoin: "round",
      }),
      e.jsx("path", {
        d: "M18 14 C18 11 22 11 22 14",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeDasharray: "2 2",
        strokeLinecap: "round",
      }),
      e.jsx("path", {
        d: "M26 12 L30 8 M30 12 L26 8",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
      }),
    ],
  }),
  septoplasty: e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("path", {
        d: "M20 8 L20 32",
        stroke: "currentColor",
        strokeWidth: "2.5",
        strokeLinecap: "round",
      }),
      e.jsx("path", {
        d: "M10 15 Q15 13 20 15 Q25 17 30 15",
        stroke: "currentColor",
        strokeWidth: "1.5",
        strokeLinecap: "round",
        fill: "none",
      }),
      e.jsx("path", {
        d: "M10 22 Q15 24 20 22 Q25 20 30 22",
        stroke: "currentColor",
        strokeWidth: "1.5",
        strokeLinecap: "round",
        fill: "none",
      }),
      e.jsx("circle", { cx: "20", cy: "20", r: "2", fill: "currentColor", opacity: ".4" }),
    ],
  }),
  turbinoplasty: e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("path", {
        d: "M10 15 Q16 10 22 15 Q28 20 22 25 Q16 30 10 25 Q10 20 14 17",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
        fill: "none",
      }),
      e.jsx("path", {
        d: "M30 10 Q34 15 30 20",
        stroke: "currentColor",
        strokeWidth: "1.5",
        strokeLinecap: "round",
      }),
      e.jsx("path", {
        d: "M30 22 Q34 27 30 32",
        stroke: "currentColor",
        strokeWidth: "1.5",
        strokeLinecap: "round",
        opacity: ".5",
      }),
    ],
  }),
  "sinus-endoscopy": e.jsxs("svg", {
    viewBox: "0 0 40 40",
    fill: "none",
    "aria-hidden": !0,
    className: "w-6 h-6",
    children: [
      e.jsx("circle", {
        cx: "22",
        cy: "20",
        r: "9",
        stroke: "currentColor",
        strokeWidth: "2",
        fill: "none",
      }),
      e.jsx("circle", {
        cx: "22",
        cy: "20",
        r: "4",
        stroke: "currentColor",
        strokeWidth: "1.5",
        fill: "none",
      }),
      e.jsx("path", {
        d: "M8 20 L13 20",
        stroke: "currentColor",
        strokeWidth: "2",
        strokeLinecap: "round",
      }),
      e.jsx("circle", { cx: "22", cy: "20", r: "1.5", fill: "currentColor" }),
      e.jsx("path", {
        d: "M10 14 L14 17 M10 26 L14 23",
        stroke: "currentColor",
        strokeWidth: "1",
        opacity: ".4",
        strokeLinecap: "round",
      }),
    ],
  }),
};
function _({ slug: s, FallbackIcon: r, size: l = 20, className: n = "" }) {
  const t = Rs[s];
  return e.jsx("span", {
    className: `service-icon inline-flex items-center justify-center ${n}`,
    "aria-hidden": "true",
    children: t
      ? e.jsx("span", { className: "contents", children: t })
      : e.jsx(r, { size: l, "aria-hidden": !0 }),
  });
}
const _s =
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (1).webp",
  Ps =
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/DrShahinBastaninejadPortrait1.webp",
  Ts =
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (5).webp";
function Os() {
  return e.jsxs("section", {
    className: "relative min-h-0 sm:min-h-[min(100svh,900px)] flex items-center overflow-hidden",
    children: [
      e.jsxs("div", {
        className: "absolute inset-0",
        children: [
          e.jsx("img", {
            src: _s,
            alt: "کلینیک جراحی بینی",
            className: "w-full h-full object-cover object-[center_20%] sm:object-center",
            fetchPriority: "high",
            decoding: "async",
          }),
          e.jsx("div", {
            className: "absolute inset-0 bg-gradient-to-l from-black/85 via-black/65 to-black/40",
          }),
          e.jsx("div", {
            className:
              "absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20",
          }),
        ],
      }),
      e.jsx("div", {
        className:
          "relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 sm:pt-28 pb-12 sm:pb-20 w-full",
        children: e.jsxs("div", {
          className: "grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center",
          children: [
            e.jsxs("div", {
              className: "text-white space-y-5 sm:space-y-8 text-center lg:text-right",
              children: [
                e.jsxs("div", {
                  className:
                    "inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 text-xs sm:text-sm mx-auto lg:mx-0",
                  children: [
                    e.jsx("div", { className: "w-2 h-2 bg-green-400 rounded-full animate-pulse" }),
                    "جراح متخصص بینی در تهران",
                  ],
                }),
                e.jsxs("h1", {
                  className:
                    "text-[1.75rem] leading-snug sm:text-5xl lg:text-6xl font-black sm:leading-tight",
                  children: [
                    "بینی زیبا و",
                    " ",
                    e.jsx("span", {
                      className:
                        "text-transparent bg-clip-text bg-gradient-to-l from-green-300 to-emerald-200",
                      children: "طبیعی",
                    }),
                    e.jsx("br", { className: "hidden sm:block" }),
                    " ",
                    "بدون آسیب به تنفس",
                  ],
                }),
                e.jsxs("p", {
                  className:
                    "text-base sm:text-xl text-white/80 leading-relaxed max-w-lg mx-auto lg:mx-0",
                  children: [
                    y,
                    " — دانشیار دانشگاه علوم پزشکی تهران، با بیش از ۱۸ سال تجربه تخصصی و بیش از ۲۰۰۰ عمل موفق",
                  ],
                }),
                e.jsxs("div", {
                  className:
                    "flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 justify-center lg:justify-start",
                  children: [
                    e.jsxs(m, {
                      to: "/booking",
                      className:
                        "inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl transition-all shadow-xl shadow-primary/30 text-base sm:text-lg min-h-12",
                      children: [e.jsx(N, { className: "w-5 h-5" }), " رزرو نوبت مشاوره"],
                    }),
                    e.jsxs(m, {
                      to: "/gallery",
                      className:
                        "inline-flex items-center justify-center gap-2 bg-white text-primary hover:bg-white/90 border border-white/40 font-bold px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl transition-all text-base sm:text-lg min-h-12",
                      children: [e.jsx(We, { className: "w-5 h-5" }), " مشاهده گالری"],
                    }),
                  ],
                }),
                e.jsx("div", {
                  className:
                    "grid grid-cols-2 sm:flex sm:flex-wrap gap-3 sm:gap-8 pt-2 justify-center lg:justify-start",
                  children: O.map((s) =>
                    e.jsxs(
                      "div",
                      {
                        className: "text-center min-w-0",
                        children: [
                          e.jsx("p", {
                            className: "text-2xl sm:text-3xl font-black text-white",
                            children: s.val,
                          }),
                          e.jsx("p", {
                            className: "text-xs sm:text-sm text-white/70",
                            children: s.label,
                          }),
                        ],
                      },
                      s.label,
                    ),
                  ),
                }),
              ],
            }),
            e.jsx("div", {
              className: "hidden lg:flex justify-center",
              children: e.jsxs("div", {
                className: "flex flex-col items-center gap-4",
                children: [
                  e.jsxs("div", {
                    className:
                      "relative w-full max-w-xs mx-auto sm:w-80 h-[320px] sm:h-[400px] rounded-3xl overflow-hidden border-4 border-white/20 shadow-2xl",
                    children: [
                      e.jsx("img", { src: Ps, alt: y, className: "w-full h-full object-cover" }),
                      e.jsx("div", {
                        className:
                          "absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent",
                      }),
                      e.jsxs("div", {
                        className: "absolute bottom-0 inset-x-0 p-5 text-white",
                        children: [
                          e.jsx("p", { className: "font-bold text-base", children: y }),
                          e.jsx("p", {
                            className: "text-white/75 text-xs",
                            children: "متخصص گوش، حلق و بینی",
                          }),
                          e.jsx("p", {
                            className: "text-white/75 text-xs",
                            children: "دانشیار دانشگاه علوم پزشکی تهران",
                          }),
                        ],
                      }),
                    ],
                  }),
                  e.jsxs("div", {
                    className: "flex gap-3",
                    children: [
                      e.jsxs("div", {
                        className:
                          "bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl px-4 py-2.5 flex items-center gap-2.5",
                        children: [
                          e.jsx("div", {
                            className:
                              "w-8 h-8 bg-primary/20 rounded-lg flex items-center justify-center",
                            children: e.jsx(Q, { className: "w-4 h-4 text-primary" }),
                          }),
                          e.jsxs("div", {
                            children: [
                              e.jsx("p", {
                                className: "text-[10px] text-white/60",
                                children: "رتبه بورد",
                              }),
                              e.jsx("p", {
                                className: "font-bold text-sm text-white",
                                children: "رتبه ۴ کشوری",
                              }),
                            ],
                          }),
                        ],
                      }),
                      e.jsxs("div", {
                        className:
                          "bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl px-4 py-2.5 flex items-center gap-2.5",
                        children: [
                          e.jsx("div", {
                            className:
                              "w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center",
                            children: e.jsx(me, { className: "w-4 h-4 text-accent" }),
                          }),
                          e.jsxs("div", {
                            children: [
                              e.jsx("p", {
                                className: "text-[10px] text-white/60",
                                children: "رضایت بیمار",
                              }),
                              e.jsx("p", {
                                className: "font-bold text-sm text-white",
                                children: "۹۸٪ موفقیت",
                              }),
                            ],
                          }),
                        ],
                      }),
                    ],
                  }),
                ],
              }),
            }),
          ],
        }),
      }),
      e.jsx("div", {
        className: "absolute bottom-6 left-1/2 -translate-x-1/2 animate-bounce hidden sm:block",
        children: e.jsx($, { className: "w-8 h-8 text-white/60" }),
      }),
    ],
  });
}
function $s() {
  return e.jsx("section", {
    className: "bg-primary py-10",
    children: e.jsx("div", {
      className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
      children: e.jsx("div", {
        className: "grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6",
        children: ie.map((s, r) =>
          e.jsxs(
            "div",
            {
              className:
                "flex flex-row sm:flex-col items-center sm:text-center gap-3 group text-right sm:text-center",
              children: [
                e.jsx("div", {
                  className:
                    "w-11 h-11 sm:w-12 sm:h-12 bg-white/15 rounded-2xl flex items-center justify-center group-hover:bg-white/25 transition-colors flex-shrink-0",
                  children: e.jsx(s.icon, { className: "w-5 h-5 sm:w-6 sm:h-6 text-white" }),
                }),
                e.jsxs("div", {
                  className: "min-w-0",
                  children: [
                    e.jsx("p", {
                      className: "text-white font-bold text-xs sm:text-sm leading-snug",
                      children: s.title,
                    }),
                    e.jsx("p", {
                      className:
                        "text-white/60 text-[11px] sm:text-xs mt-0.5 sm:mt-1 leading-relaxed",
                      children: s.subtitle,
                    }),
                  ],
                }),
              ],
            },
            r,
          ),
        ),
      }),
    }),
  });
}
function Ws() {
  const [s, r] = h.useState("cosmetic"),
    [l, n] = h.useState(1),
    t = s === "cosmetic" ? L : M,
    i = t.find((a) => a.id === l) ?? t[0];
  return (
    h.useEffect(() => {
      n(t[0].id);
    }, [s]),
    e.jsx("section", {
      id: "services",
      className: "py-24 bg-background",
      children: e.jsxs("div", {
        className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
        children: [
          e.jsxs("div", {
            className: "text-center max-w-2xl mx-auto mb-16",
            children: [
              e.jsx("span", {
                className: "text-primary font-bold text-sm tracking-wider",
                children: "تخصص‌های ما",
              }),
              e.jsx("h2", {
                className: "text-4xl font-black text-foreground mt-2 mb-4",
                children: "خدمات جراحی بینی",
              }),
              e.jsx("p", {
                className: "text-muted-foreground text-lg",
                children:
                  "از راینوپلاستی زیبایی تا جراحی‌های درمانی، همه خدمات با بالاترین استاندارد",
              }),
            ],
          }),
          e.jsx("div", {
            className: "flex justify-center mb-10",
            children: e.jsx("div", {
              className: "bg-muted rounded-2xl p-1.5 flex gap-1",
              children: [
                { k: "cosmetic", l: "خدمات زیبایی" },
                { k: "functional", l: "خدمات درمانی" },
              ].map((a) =>
                e.jsx(
                  "button",
                  {
                    onClick: () => r(a.k),
                    className: `px-6 py-3 rounded-xl font-bold text-sm transition-all ${s === a.k ? "bg-primary text-white shadow-lg" : "text-muted-foreground hover:text-foreground"}`,
                    children: a.l,
                  },
                  a.k,
                ),
              ),
            }),
          }),
          e.jsxs("div", {
            className: "grid lg:grid-cols-3 gap-6",
            children: [
              e.jsx("div", {
                className: "flex flex-col gap-2",
                children: t.map((a) => {
                  const c = a.id === l;
                  return e.jsxs(
                    "button",
                    {
                      onClick: () => n(a.id),
                      className: `flex items-center gap-4 p-4 rounded-2xl text-right transition-all ${c ? "bg-primary text-white shadow-lg shadow-primary/20" : "bg-white hover:bg-[#E4F0E4] border border-border text-[#25272C]"}`,
                      children: [
                        e.jsx("div", {
                          className: `w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 [&>span>span>svg]:w-5 [&>span>span>svg]:h-5 [&>span>svg]:w-5 [&>span>svg]:h-5 ${c ? "bg-[#1e5a22]" : "bg-[#E4F0E4]"}`,
                          children: e.jsx(_, {
                            slug: a.slug,
                            FallbackIcon: a.icon,
                            size: 20,
                            className: c ? "text-white" : "text-[#28722C]",
                          }),
                        }),
                        e.jsxs("div", {
                          className: "flex-1",
                          children: [
                            e.jsx("p", { className: "font-bold text-sm", children: a.title }),
                            e.jsx("p", {
                              className: `text-xs mt-0.5 ${c ? "text-white/70" : "text-muted-foreground"}`,
                              children: a.subtitle,
                            }),
                          ],
                        }),
                        c && e.jsx(W, { className: "w-4 h-4 flex-shrink-0" }),
                      ],
                    },
                    a.id,
                  );
                }),
              }),
              e.jsx("div", {
                className: "lg:col-span-2",
                children: e.jsxs("div", {
                  className: "bg-white rounded-3xl border border-border p-8 h-full",
                  children: [
                    e.jsxs("div", {
                      className: "flex items-start gap-6 mb-6",
                      children: [
                        e.jsx("div", {
                          className:
                            "w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center flex-shrink-0 [&>span>span>svg]:w-8 [&>span>span>svg]:h-8 [&>span>svg]:w-8 [&>span>svg]:h-8",
                          children: e.jsx(_, { slug: i.slug, FallbackIcon: i.icon, size: 32 }),
                        }),
                        e.jsxs("div", {
                          children: [
                            e.jsx("h3", {
                              className: "text-2xl font-black text-foreground",
                              children: i.title,
                            }),
                            e.jsx("p", {
                              className: "text-primary font-medium text-sm mt-1",
                              children: i.subtitle,
                            }),
                          ],
                        }),
                      ],
                    }),
                    e.jsx("p", {
                      className: "text-muted-foreground leading-loose text-lg mb-8",
                      children: i.desc,
                    }),
                    e.jsx("div", {
                      className: "grid sm:grid-cols-2 gap-4 mb-8",
                      children: [
                        "مشاوره تخصصی",
                        "تکنیک‌های روز دنیا",
                        "بیهوشی ایمن",
                        "مراقبت پس از عمل",
                      ].map((a) =>
                        e.jsxs(
                          "div",
                          {
                            className: "flex items-center gap-3",
                            children: [
                              e.jsx(D, { className: "w-5 h-5 text-primary flex-shrink-0" }),
                              e.jsx("span", {
                                className: "text-foreground font-medium text-sm",
                                children: a,
                              }),
                            ],
                          },
                          a,
                        ),
                      ),
                    }),
                    e.jsxs("div", {
                      className:
                        "flex flex-col sm:flex-row flex-wrap gap-3 justify-center lg:justify-start",
                      children: [
                        e.jsxs(m, {
                          to: "/booking",
                          className:
                            "inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-6 py-3 rounded-2xl transition-all shadow-lg hover:shadow-primary/30",
                          children: [e.jsx(N, { className: "w-5 h-5" }), " رزرو مشاوره"],
                        }),
                        e.jsxs(m, {
                          to: `/services/${i.slug}`,
                          className:
                            "inline-flex items-center justify-center gap-2 border-2 border-[#28722C] bg-white text-[#28722C] font-bold px-6 py-3 rounded-2xl hover:bg-[#28722C] hover:text-white transition-all min-h-12",
                          children: ["اطلاعات بیشتر ", e.jsx(j, { className: "w-4 h-4" })],
                        }),
                      ],
                    }),
                  ],
                }),
              }),
            ],
          }),
        ],
      }),
    })
  );
}
function Gs() {
  return e.jsx("section", {
    className: "bg-gradient-to-l from-primary to-emerald-700 py-12 sm:py-16",
    children: e.jsx("div", {
      className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
      children: e.jsx("div", {
        className: "grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8",
        children: O.map((s) =>
          e.jsxs(
            "div",
            {
              className: "text-center px-2",
              children: [
                e.jsx("p", {
                  className: "text-3xl sm:text-5xl font-black text-white leading-none",
                  children: s.val,
                }),
                e.jsx("p", {
                  className: "text-sm sm:text-xl font-bold text-white mt-2",
                  children: s.label,
                }),
                e.jsx("p", {
                  className: "text-white/70 text-xs sm:text-sm mt-1 leading-relaxed",
                  children: s.sub,
                }),
              ],
            },
            s.label,
          ),
        ),
      }),
    }),
  });
}
function qs() {
  return e.jsx("section", {
    id: "about",
    className: "py-24 bg-secondary/30",
    children: e.jsx("div", {
      className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
      children: e.jsxs("div", {
        className: "grid lg:grid-cols-2 gap-16 items-start",
        children: [
          e.jsxs("div", {
            className: "relative",
            children: [
              e.jsxs("div", {
                className: "rounded-3xl overflow-hidden aspect-[4/5] shadow-2xl",
                children: [
                  e.jsx("img", { src: Ts, alt: y, className: "w-full h-full object-cover" }),
                  e.jsx("div", {
                    className:
                      "absolute inset-0 bg-gradient-to-t from-primary/70 via-transparent to-transparent",
                  }),
                ],
              }),
              e.jsxs("div", {
                className:
                  "absolute -bottom-6 -right-4 lg:-right-8 max-w-sm bg-white rounded-3xl p-6 shadow-2xl border border-border",
                children: [
                  e.jsx("div", {
                    className: "text-4xl text-primary/20 font-serif leading-none mb-2",
                    children: '"',
                  }),
                  e.jsx("blockquote", {
                    className: "text-foreground text-sm leading-loose font-medium",
                    children:
                      "مهمترین اصل در جراحی زیبایی بینی این است که از یک سادگی نامطبوع به پیچیدگی‌ای برسیم که ورای آن، سادگی مطبوعی حاصل شود… بینی عضوی است جهت نفس کشیدن.",
                  }),
                  e.jsxs("footer", {
                    className: "mt-4 flex items-center gap-3",
                    children: [
                      e.jsx("div", {
                        className:
                          "w-8 h-8 bg-primary rounded-full flex items-center justify-center",
                        children: e.jsx(me, { className: "w-4 h-4 text-white" }),
                      }),
                      e.jsx("p", { className: "text-xs text-muted-foreground", children: y }),
                    ],
                  }),
                ],
              }),
            ],
          }),
          e.jsxs("div", {
            className: "pt-8 lg:pt-0",
            children: [
              e.jsx("span", {
                className: "text-primary font-bold text-sm tracking-wider",
                children: "رزومه تخصصی",
              }),
              e.jsx("h2", {
                className: "text-4xl font-black text-foreground mt-2 mb-6",
                children: y,
              }),
              e.jsx("p", {
                className: "text-muted-foreground text-lg leading-loose mb-8",
                children:
                  "دانشیار دانشگاه علوم پزشکی تهران و عضو هیئت علمی بیمارستان امیراعلم، دبیر کمیته علمی رینولوژی انجمن جراحی پلاستیک بینی ایران. با بیش از ۱۸ سال تجربه در جراحی‌های بینی اولیه، ترمیمی و درمانی.",
              }),
              e.jsx("div", {
                className: "grid sm:grid-cols-2 gap-3 mb-10",
                children: [
                  "دانشگاه علوم پزشکی تهران",
                  "بیمارستان امیراعلم تهران",
                  "انجمن جراحی پلاستیک بینی ایران",
                  "انجمن متخصصان گوش، حلق و بینی ایران",
                ].map((s) =>
                  e.jsxs(
                    "div",
                    {
                      className:
                        "flex items-center gap-3 bg-white rounded-xl p-3 border border-border",
                      children: [
                        e.jsx(D, { className: "w-5 h-5 text-primary flex-shrink-0" }),
                        e.jsx("span", {
                          className: "text-sm font-medium text-foreground",
                          children: s,
                        }),
                      ],
                    },
                    s,
                  ),
                ),
              }),
              e.jsx("h3", {
                className: "text-lg font-bold text-foreground mb-6",
                children: "مسیر تحصیل و تجربه",
              }),
              e.jsxs("div", {
                className: "relative",
                children: [
                  e.jsx("div", {
                    className: "absolute right-5 top-0 bottom-0 w-0.5 bg-primary/20",
                  }),
                  e.jsx("div", {
                    className: "space-y-6",
                    children: ne.map((s, r) => {
                      const l = s.icon;
                      return e.jsxs(
                        "div",
                        {
                          className: "flex gap-5",
                          children: [
                            e.jsx("div", {
                              className: "relative flex-shrink-0",
                              children: e.jsx("div", {
                                className:
                                  "w-10 h-10 bg-primary rounded-full flex items-center justify-center shadow-md shadow-primary/30 z-10 relative",
                                children: e.jsx(l, { className: "w-5 h-5 text-white" }),
                              }),
                            }),
                            e.jsxs("div", {
                              className: "pb-2",
                              children: [
                                e.jsx("span", {
                                  className:
                                    "text-xs font-bold text-primary bg-primary/10 rounded-full px-3 py-1",
                                  children: s.year,
                                }),
                                e.jsx("p", {
                                  className: "font-bold text-foreground mt-2",
                                  children: s.title,
                                }),
                                e.jsx("p", {
                                  className: "text-muted-foreground text-sm mt-0.5",
                                  children: s.org,
                                }),
                              ],
                            }),
                          ],
                        },
                        r,
                      );
                    }),
                  }),
                ],
              }),
            ],
          }),
        ],
      }),
    }),
  });
}
function Qs() {
  return e.jsx("section", {
    id: "certificates",
    className: "py-24 bg-background",
    children: e.jsxs("div", {
      className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
      children: [
        e.jsxs("div", {
          className: "text-center max-w-2xl mx-auto mb-16",
          children: [
            e.jsx("span", {
              className: "text-primary font-bold text-sm tracking-wider",
              children: "گواهینامه‌ها",
            }),
            e.jsx("h2", {
              className: "text-4xl font-black text-foreground mt-2 mb-4",
              children: "مدارک و افتخارات",
            }),
            e.jsxs(m, {
              to: "/gallery",
              className:
                "inline-flex items-center gap-2 text-primary font-bold text-sm mt-2 hover:underline",
              children: ["مشاهده گالری نتایج ", e.jsx(j, { className: "w-4 h-4" })],
            }),
            e.jsx("p", {
              className: "text-muted-foreground text-lg text-center",
              className: "text-center",
              children: "تأییدیه‌ها و گواهینامه‌های معتبر ملی و بین‌المللی",
            }),
          ],
        }),
        e.jsx("div", {
          className: "grid sm:grid-cols-2 lg:grid-cols-3 gap-5",
          children: oe.map((s, r) =>
            e.jsx(
              "div",
              {
                className:
                  "group bg-white border border-border rounded-2xl p-6 hover:border-primary/30 hover:shadow-lg hover:shadow-primary/5 transition-all",
                children: e.jsxs("div", {
                  className: "flex items-start gap-4",
                  children: [
                    e.jsx("div", {
                      className:
                        "w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-accent/20 transition-colors",
                      children: e.jsx(Q, { className: "w-5 h-5 text-accent" }),
                    }),
                    e.jsx("p", {
                      className: "text-foreground font-medium text-sm leading-relaxed",
                      children: s,
                    }),
                  ],
                }),
              },
              r,
            ),
          ),
        }),
      ],
    }),
  });
}
function Hs() {
  if (!R.length)
    return e.jsx("section", {
      id: "gallery",
      className: "py-24 bg-muted/50",
      children: e.jsxs("div", {
        className: "max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center",
        children: [
          e.jsx("span", { className: "text-primary font-bold text-sm tracking-wider", children: "حریم خصوصی بیماران" }),
          e.jsx("h2", { className: "text-4xl font-black text-foreground mt-2 mb-4", children: "گالری قبل و بعد در حال بازبینی است" }),
          e.jsx("div", { className: "drb-gallery-empty", children: e.jsx("p", { children: "تا تکمیل روتوش، تأیید رضایت انتشار و بازبینی نهایی، هیچ تصویر خام، تأییدنشده یا تصویر پزشک در این بخش نمایش داده نمی‌شود." }) }),
        ],
      }),
    });
  const [s, r] = h.useState(0),
    l = 4,
    n = Math.ceil(R.length / l),
    t = R.slice(s * l, s * l + l);
  return e.jsx("section", {
    id: "gallery",
    className: "py-24 bg-muted/50",
    children: e.jsxs("div", {
      className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
      children: [
        e.jsxs("div", {
          className: "text-center max-w-2xl mx-auto mb-6",
          children: [
            e.jsx("span", {
              className: "text-primary font-bold text-sm tracking-wider",
              children: "نتایج واقعی",
            }),
            e.jsx("h2", {
              className: "text-4xl font-black text-foreground mt-2 mb-4",
              children: "گالری قبل و بعد",
            }),
            e.jsx("p", {
              className: "text-muted-foreground text-lg text-center text-center",
              className: "text-center",
              children: "+۱۷۸ نمونه واقعی از نتایج جراحی بینی دکتر باستانی‌نژاد",
            }),
          ],
        }),
        e.jsx("div", {
          className: "flex flex-wrap justify-center gap-6 mb-12",
          children: O.map((i) =>
            e.jsxs(
              "div",
              {
                className:
                  "flex items-center gap-3 bg-white rounded-2xl px-5 py-3 shadow-sm border border-border",
                children: [
                  e.jsx("p", { className: "text-2xl font-black text-primary", children: i.val }),
                  e.jsx("p", {
                    className: "text-sm font-medium text-muted-foreground",
                    children: i.label,
                  }),
                ],
              },
              i.label,
            ),
          ),
        }),
        e.jsx("div", {
          className: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8",
          children: t.map((i) =>
            e.jsx(je, { before: i.before, after: i.after, fallback: i.fallback }, i.id),
          ),
        }),
        e.jsxs("div", {
          className: "flex justify-center items-center gap-3",
          children: [
            e.jsx("button", {
              onClick: () => r((i) => Math.max(0, i - 1)),
              disabled: s === 0,
              className:
                "w-10 h-10 rounded-xl border border-border bg-white flex items-center justify-center hover:bg-secondary disabled:opacity-40 transition-colors",
              children: e.jsx(H, { className: "w-5 h-5" }),
            }),
            Array.from({ length: n }).map((i, a) =>
              e.jsx(
                "button",
                {
                  onClick: () => r(a),
                  className: `w-10 h-10 rounded-xl font-bold text-sm transition-all ${s === a ? "bg-primary text-white" : "bg-white border border-border text-foreground hover:bg-secondary"}`,
                  children: a + 1,
                },
                a,
              ),
            ),
            e.jsx("button", {
              onClick: () => r((i) => Math.min(n - 1, i + 1)),
              disabled: s === n - 1,
              className:
                "w-10 h-10 rounded-xl border border-border bg-white flex items-center justify-center hover:bg-secondary disabled:opacity-40 transition-colors",
              children: e.jsx(W, { className: "w-5 h-5" }),
            }),
          ],
        }),
        e.jsx("p", {
          className: "text-center text-xs text-muted-foreground mt-6 max-w-xl mx-auto",
          children:
            "تمام تصاویر با رضایت بیماران و رعایت کامل حریم خصوصی منتشر شده‌اند. نتایج ممکن است از فردی به فرد دیگر متفاوت باشد.",
        }),
        e.jsx("div", {
          className: "text-center mt-8",
          children: e.jsxs(m, {
            to: "/gallery",
            className:
              "inline-flex items-center justify-center gap-2 border-2 border-[#28722C] bg-white text-[#28722C] font-bold px-8 py-3 rounded-2xl hover:bg-[#28722C] hover:text-white transition-all min-h-12",
            children: ["مشاهده همه نمونه‌ها ", e.jsx(j, { className: "w-4 h-4" })],
          }),
        }),
      ],
    }),
  });
}
function Us() {
  const s = [xe, D, he, Ge];
  return e.jsx("section", {
    className: "py-24 bg-white",
    children: e.jsxs("div", {
      className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
      children: [
        e.jsxs("div", {
          className: "text-center max-w-2xl mx-auto mb-16",
          children: [
            e.jsx("span", {
              className: "text-primary font-bold text-sm tracking-wider",
              children: "مسیر درمان",
            }),
            e.jsx("h2", {
              className: "text-4xl font-black text-foreground mt-2 mb-4",
              children: "فرایند جراحی در کلینیک ما",
            }),
            e.jsx("p", {
              className: "text-muted-foreground text-lg",
              children: "از اولین تماس تا بهبودی کامل، در هر قدم کنارتان هستیم",
            }),
          ],
        }),
        e.jsx("div", {
          className: "grid sm:grid-cols-2 lg:grid-cols-4 gap-6",
          children: Me.map((r, l) => {
            const n = s[l];
            return e.jsxs(
              "div",
              {
                className:
                  "group bg-gradient-to-br from-white to-secondary/30 border border-border rounded-3xl p-6 hover:shadow-xl hover:shadow-primary/10 hover:border-primary/30 transition-all",
                children: [
                  e.jsxs("div", {
                    className: "flex items-center gap-3 mb-4",
                    children: [
                      e.jsx("span", {
                        className: "text-3xl font-black text-primary/15 leading-none",
                        children: r.num,
                      }),
                      e.jsx("div", {
                        className:
                          "w-10 h-10 bg-primary rounded-2xl flex items-center justify-center shadow-lg shadow-primary/30 group-hover:scale-110 transition-transform flex-shrink-0",
                        children: e.jsx(n, { className: "w-5 h-5 text-white" }),
                      }),
                    ],
                  }),
                  e.jsx("h3", {
                    className: "text-xl font-black text-foreground mb-3",
                    children: r.title,
                  }),
                  e.jsx("p", {
                    className: "text-muted-foreground text-sm leading-loose",
                    children: r.desc,
                  }),
                ],
              },
              l,
            );
          }),
        }),
        e.jsx("div", {
          className: "text-center mt-12",
          children: e.jsxs(m, {
            to: "/booking",
            className:
              "inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-10 py-4 rounded-2xl transition-all shadow-xl hover:shadow-primary/30 text-lg",
            children: [e.jsx(N, { className: "w-5 h-5" }), " شروع مسیر درمان — رزرو مشاوره"],
          }),
        }),
      ],
    }),
  });
}
function Vs() {
  const FAQ = window.__DRB_WP__?.faqs?.length ? window.__DRB_WP__.faqs : S;
  const [s, r] = h.useState(0),
    [l, n] = h.useState("همه"),
    t = ["همه", ...Array.from(new Set(FAQ.map((a) => a.cat)))],
    i = l === "همه" ? FAQ : FAQ.filter((a) => a.cat === l);
  return e.jsx("section", {
    id: "faq",
    className: "py-24 bg-secondary/30",
    children: e.jsxs("div", {
      className: "max-w-4xl mx-auto px-4 sm:px-6 lg:px-8",
      children: [
        e.jsxs("div", {
          className: "text-center mb-16",
          children: [
            e.jsx("span", {
              className: "text-primary font-bold text-sm tracking-wider",
              children: "پاسخ سؤالات شما",
            }),
            e.jsx("h2", {
              className: "text-4xl font-black text-foreground mt-2 mb-4",
              children: "سؤالات متداول",
            }),
            e.jsx("p", {
              className: "text-muted-foreground text-lg text-center",
              className: "text-center",
              children: "پاسخ جامع به رایج‌ترین سؤالات درباره جراحی بینی",
            }),
          ],
        }),
        e.jsx("div", {
          className: "flex flex-wrap gap-2 mb-10 justify-center content-center",
          "aria-label": "فیلتر سوالات",
          children: t.map((a) =>
            e.jsx(
              "button",
              {
                onClick: () => {
                  (n(a), r(null));
                },
                className: `px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap flex-shrink-0 ${l === a ? "bg-primary text-white" : "bg-white border border-border text-foreground hover:border-primary/30 hover:text-primary"}`,
                children: a,
              },
              a,
            ),
          ),
        }),
        e.jsx("div", {
          className: "space-y-3",
          children: i.map((a, c) =>
            e.jsxs(
              "div",
              {
                className: "bg-white rounded-2xl border border-border overflow-hidden",
                children: [
                  e.jsxs("button", {
                    onClick: () => r(s === c ? null : c),
                    className: "w-full flex items-center justify-between p-6 text-right gap-4",
                    children: [
                      e.jsxs("div", {
                        className: "flex items-start gap-4",
                        children: [
                          e.jsx("div", {
                            className:
                              "w-6 h-6 bg-primary/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5",
                            children: e.jsx("span", {
                              className: "text-primary text-xs font-black",
                              children: c + 1,
                            }),
                          }),
                          e.jsxs("div", {
                            children: [
                              e.jsx("span", {
                                className: "text-xs font-bold text-primary/60 mb-1 block",
                                children: a.cat,
                              }),
                              e.jsx("span", {
                                className: "font-bold text-foreground text-base leading-snug",
                                children: a.q,
                              }),
                            ],
                          }),
                        ],
                      }),
                      e.jsx($, {
                        className: `w-5 h-5 text-primary flex-shrink-0 transition-transform ${s === c ? "rotate-180" : ""}`,
                      }),
                    ],
                  }),
                  s === c &&
                    e.jsx("div", {
                      className: "px-6 pb-6",
                      children: e.jsx("div", {
                        className: "border-t border-border pt-5",
                        children: e.jsx("p", {
                          className: "text-muted-foreground leading-loose",
                          children: a.a,
                        }),
                      }),
                    }),
                ],
              },
              c,
            ),
          ),
        }),
        e.jsx("p", {
          className:
            "text-center text-xs text-muted-foreground mt-8 bg-white rounded-2xl border border-border p-4 mx-auto max-w-2xl",
          children:
            "اطلاعات ارائه‌شده صرفاً جنبه آموزشی دارند و جایگزین مشاوره پزشکی تخصصی نمی‌شوند.",
        }),
      ],
    }),
  });
}
function Ys() {
  const s = f.find((l) => l.featured),
    r = f.filter((l) => !l.featured);
  return e.jsx("section", {
    id: "blog",
    className: "py-24 bg-background",
    children: e.jsxs("div", {
      className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
      children: [
        e.jsxs("div", {
          className: "flex flex-col sm:flex-row sm:items-end justify-between mb-12 gap-4",
          children: [
            e.jsxs("div", {
              children: [
                e.jsx("span", {
                  className: "text-primary font-bold text-sm tracking-wider text-center block",
                  className: "text-center",
                  children: "دانش پزشکی",
                }),
                e.jsx("h2", {
                  className: "text-4xl font-black text-foreground mt-2 text-center",
                  className: "text-center",
                  children: "مقالات تخصصی",
                }),
              ],
            }),
            e.jsxs(m, {
              to: "/blog",
              className:
                "inline-flex items-center gap-2 text-primary font-bold hover:gap-3 transition-all",
              children: ["مشاهده همه مقالات ", e.jsx(j, { className: "w-4 h-4" })],
            }),
          ],
        }),
        e.jsxs("div", {
          className: "grid lg:grid-cols-3 gap-6",
          children: [
            e.jsx(m, {
              to: `/blog/${s.slug}`,
              className: "lg:col-span-2 group cursor-pointer",
              children: e.jsxs("div", {
                className: "relative rounded-3xl overflow-hidden aspect-[16/9]",
                children: [
                  e.jsx("img", {
                    src: s.img,
                    alt: s.title,
                    className:
                      "w-full h-full object-cover group-hover:scale-105 transition-transform duration-500",
                  }),
                  e.jsx("div", {
                    className:
                      "absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent",
                  }),
                  e.jsxs("div", {
                    className: "absolute bottom-0 inset-x-0 p-8 text-white",
                    children: [
                      e.jsx("span", {
                        className:
                          "text-xs font-bold bg-primary rounded-full px-3 py-1.5 mb-3 inline-block",
                        children: s.cat,
                      }),
                      e.jsx("h3", {
                        className: "text-2xl font-black leading-snug mb-2",
                        children: s.title,
                      }),
                      e.jsx("p", {
                        className: "text-white/75 text-sm line-clamp-2",
                        children: s.excerpt,
                      }),
                      e.jsx("p", { className: "text-white/50 text-xs mt-3", children: s.date }),
                    ],
                  }),
                ],
              }),
            }),
            e.jsx("div", {
              className: "flex flex-col gap-4",
              children: r
                .slice(0, 4)
                .map((l) =>
                  e.jsxs(
                    m,
                    {
                      to: `/blog/${l.slug}`,
                      className:
                        "group flex gap-4 bg-white border border-border rounded-2xl p-4 hover:shadow-md hover:border-primary/20 transition-all",
                      children: [
                        e.jsx("div", {
                          className: "w-20 h-20 rounded-xl overflow-hidden flex-shrink-0",
                          children: e.jsx("img", {
                            src: l.img,
                            alt: l.title,
                            className:
                              "w-full h-full object-cover group-hover:scale-110 transition-transform duration-300",
                          }),
                        }),
                        e.jsxs("div", {
                          className: "flex-1 min-w-0",
                          children: [
                            e.jsx("span", {
                              className: "text-xs font-bold text-primary",
                              children: l.cat,
                            }),
                            e.jsx("h4", {
                              className:
                                "text-sm font-bold text-foreground mt-1 leading-snug line-clamp-2",
                              children: l.title,
                            }),
                            e.jsx("p", {
                              className: "text-xs text-muted-foreground mt-1",
                              children: l.date,
                            }),
                          ],
                        }),
                      ],
                    },
                    l.id,
                  ),
                ),
            }),
          ],
        }),
      ],
    }),
  });
}
function Ks() {
  const s = [
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (2).webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (3).webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (4).webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (6).webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (7).webp",
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (8).webp",
  ];
  return e.jsx("section", {
    className: "py-20 bg-gradient-to-br from-purple-50 to-pink-50",
    children: e.jsxs("div", {
      className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
      children: [
        e.jsxs("div", {
          className: "text-center mb-12",
          children: [
            e.jsxs("div", {
              className: "inline-flex items-center gap-2 mb-4",
              children: [
                e.jsx(X, { className: "w-6 h-6 text-pink-500" }),
                e.jsx("a", {
                  href: I,
                  target: "_blank",
                  rel: "noopener noreferrer",
                  className:
                    "text-lg font-bold text-pink-600 hover:text-pink-700 transition-colors",
                  children: "@dr.shahin.bastaninejad",
                }),
              ],
            }),
            e.jsx("h2", {
              className: "text-3xl font-black text-foreground",
              children: "ما را در اینستاگرام دنبال کنید",
            }),
            e.jsx("p", {
              className: "text-muted-foreground mt-2 text-center",
              className: "text-center",
              children: "آخرین مطالب، نمونه‌کارها و ویدیوهای آموزشی",
            }),
          ],
        }),
        e.jsx("div", {
          className: "grid grid-cols-3 sm:grid-cols-6 gap-2 mb-8",
          children: s.map((r, l) =>
            e.jsx(
              "a",
              {
                href: I,
                target: "_blank",
                rel: "noopener noreferrer",
                className: "group aspect-square rounded-xl overflow-hidden block",
                children: e.jsx("img", {
                  src: r,
                  alt: "اینستاگرام",
                  className:
                    "w-full h-full object-cover group-hover:scale-110 transition-transform duration-300",
                }),
              },
              l,
            ),
          ),
        }),
        e.jsx("div", {
          className: "text-center",
          children: e.jsxs("a", {
            href: I,
            target: "_blank",
            rel: "noopener noreferrer",
            className:
              "inline-flex items-center gap-2 bg-gradient-to-l from-pink-500 to-purple-600 text-white text-white font-bold px-8 py-4 rounded-2xl hover:shadow-xl transition-all",
            children: [
              e.jsx(X, { className: "w-5 h-5 text-white" }),
              " ",
              e.jsx("span", { className: "text-white", children: "مشاهده پروفایل اینستاگرام" }),
            ],
          }),
        }),
      ],
    }),
  });
}
function Xs() {
  return e.jsxs("section", {
    className: "py-24 bg-primary relative overflow-hidden",
    children: [
      e.jsxs("div", {
        className: "absolute inset-0 opacity-10",
        children: [
          e.jsx("div", {
            className:
              "absolute top-0 right-0 w-96 h-96 bg-white rounded-full -translate-y-1/2 translate-x-1/2",
          }),
          e.jsx("div", {
            className:
              "absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full translate-y-1/2 -translate-x-1/2",
          }),
        ],
      }),
      e.jsxs("div", {
        className: "relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8",
        children: [
          e.jsxs("div", {
            className: "text-center mb-12",
            children: [
              e.jsx("h2", {
                className: "text-3xl sm:text-4xl font-black text-white mb-4",
                children: "اولویت با تکمیل فرم آنلاین",
              }),
              e.jsx("p", {
                className: "text-white/80 text-base sm:text-xl max-w-2xl mx-auto leading-relaxed",
                children:
                  "افرادی که فرم رزرو آنلاین را تکمیل می‌کنند در اولویت نوبت‌دهی قرار می‌گیرند. فرآیند سریع، ساده و بدون نیاز به تماس تلفنی است.",
              }),
            ],
          }),
          e.jsxs("div", {
            className: "max-w-lg mx-auto bg-white rounded-3xl p-6 sm:p-8 shadow-2xl text-right",
            children: [
              e.jsx("p", {
                className: "text-sm text-[#545B64] mb-4 text-center",
                children:
                  "نام و شماره خود را وارد کنید — در اولویت نوبت‌دهی قرار می‌گیرید و همکاران ما برای هماهنگی زمان تماس می‌گیرند.",
              }),
              e.jsxs("form", {
                className: "space-y-3",
                onSubmit: (s) => {
                  (s.preventDefault(), (window.location.href = "/booking"));
                },
                children: [
                  e.jsx("input", {
                    type: "text",
                    name: "name",
                    required: !0,
                    placeholder: "نام و نام خانوادگی",
                    className:
                      "w-full px-4 py-3 rounded-xl border border-[#DDE2DD] text-sm bg-[#F7F8F6] text-[#25272C] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30",
                  }),
                  e.jsx("input", {
                    type: "tel",
                    name: "phone",
                    required: !0,
                    placeholder: "شماره موبایل",
                    className:
                      "w-full px-4 py-3 rounded-xl border border-[#DDE2DD] text-sm bg-[#F7F8F6] text-[#25272C] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30",
                    dir: "ltr",
                  }),
                  e.jsx("button", {
                    type: "submit",
                    className:
                      "w-full py-3.5 bg-[#28722C] text-white font-bold rounded-xl hover:bg-[#246b28] transition-colors min-h-12",
                    children: "ثبت درخواست نوبت",
                  }),
                ],
              }),
              e.jsx("p", {
                className: "text-[11px] text-[#545B64] text-center mt-3",
                children: "بدون تأیید پیامکی — پس از ثبت، تیم کلینیک تماس می‌گیرد.",
              }),
            ],
          }),
        ],
      }),
    ],
  });
}
function Zs() {
  const [s, r] = h.useState(!1),
    [l, n] = h.useState({ name: "", phone: "", message: "" });
  return e.jsx("section", {
    id: "contact",
    className: "py-24 bg-muted/50",
    children: e.jsxs("div", {
      className: "max-w-7xl mx-auto px-4 sm:px-6 lg:px-8",
      children: [
        e.jsxs("div", {
          className: "text-center max-w-2xl mx-auto mb-16",
          children: [
            e.jsx("span", {
              className: "text-primary font-bold text-sm tracking-wider",
              children: "ارتباط با ما",
            }),
            e.jsx("h2", {
              className: "text-4xl font-black text-foreground mt-2 mb-4",
              children: "تماس با کلینیک",
            }),
            e.jsx("p", {
              className:
                "text-muted-foreground text-base sm:text-lg text-center w-full block mx-auto",
              className: "text-center",
              children: "آماده پاسخ‌گویی به سؤالات شما هستیم",
            }),
          ],
        }),
        e.jsxs("div", {
          className: "grid lg:grid-cols-5 gap-8",
          children: [
            e.jsxs("div", {
              className: "lg:col-span-2 space-y-5",
              children: [
                e.jsxs("div", {
                  className: "bg-white rounded-3xl border border-border p-6",
                  children: [
                    e.jsxs("h3", {
                      className: "font-bold text-foreground mb-4 flex items-center gap-2",
                      children: [e.jsx(u, { className: "w-5 h-5 text-primary" }), " تلفن تماس"],
                    }),
                    T.map((t) =>
                      e.jsxs(
                        "a",
                        {
                          href: `tel:${t.replace(/–/g, "")}`,
                          className:
                            "flex items-center gap-3 p-3 rounded-xl hover:bg-secondary transition-colors group",
                          children: [
                            e.jsx("div", {
                              className:
                                "w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center group-hover:bg-primary/20 transition-colors",
                              children: e.jsx(u, { className: "w-4 h-4 text-primary" }),
                            }),
                            e.jsx("span", { className: "font-bold text-foreground", children: t }),
                          ],
                        },
                        t,
                      ),
                    ),
                  ],
                }),
                e.jsxs("div", {
                  className: "bg-white rounded-3xl border border-border p-6",
                  children: [
                    e.jsxs("h3", {
                      className: "font-bold text-foreground mb-3 flex items-center gap-2",
                      children: [e.jsx(q, { className: "w-5 h-5 text-primary" }), " آدرس"],
                    }),
                    e.jsx("p", {
                      className: "text-muted-foreground text-sm leading-loose",
                      children: ce,
                    }),
                    e.jsx("div", {
                      className: "flex flex-wrap gap-2 mt-4",
                      children: [
                        {
                          name: "Google Maps",
                          href: "https://maps.google.com/?q=35.758881,51.413824",
                          icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/GoogleMap.webp",
                        },
                        { name: "نشان", href: "https://nshn.ir/gNbNgYZt_Rxg", icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/Neshan.webp" },
                        { name: "بلد", href: "https://balad.ir/p/3cuwGGif58f8hT", icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/Balad.webp" },
                        { name: "Waze", href: "https://waze.com/ul/htnke3vwqs", icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/Waze.webp" },
                      ].map((t) =>
                        e.jsx(
                          "a",
                          {
                            href: t.href,
                            target: "_blank",
                            rel: "noopener noreferrer",
                            className:
                              "text-xs bg-secondary hover:bg-primary/10 hover:text-primary text-foreground rounded-lg px-3 py-1.5 font-medium transition-colors",
                            children: [e.jsx("img", { src: t.icon, alt: "", width: 16, height: 16, className: "inline-block ml-1 rounded-sm" }), t.name],
                          },
                          t.name,
                        ),
                      ),
                    }),
                  ],
                }),
                e.jsxs("div", {
                  className: "bg-white rounded-3xl border border-border p-6",
                  children: [
                    e.jsxs("h3", {
                      className: "font-bold text-foreground mb-3 flex items-center gap-2",
                      children: [e.jsx(F, { className: "w-5 h-5 text-primary" }), " ساعات پذیرش"],
                    }),
                    e.jsxs("div", {
                      className:
                        "flex items-center justify-between bg-secondary rounded-xl px-4 py-3",
                      children: [
                        e.jsx("span", {
                          className: "text-sm font-medium text-foreground",
                          children: "شنبه تا سه‌شنبه",
                        }),
                        e.jsx("span", {
                          className: "text-sm font-bold text-primary",
                          children: "۱۵:۰۰ – ۱۸:۰۰",
                        }),
                      ],
                    }),
                    e.jsx("p", {
                      className: "text-xs text-muted-foreground mt-3",
                      children: "برای رزرو نوبت آنلاین ۲۴ ساعته در دسترس هستیم.",
                    }),
                  ],
                }),
              ],
            }),
            e.jsxs("div", {
              className: "lg:col-span-3 space-y-5",
              children: [
                e.jsx("div", {
                  className: "bg-white rounded-3xl border border-border p-8",
                  children: s
                    ? e.jsxs("div", {
                        className:
                          "flex flex-col items-center justify-center py-12 text-center gap-4",
                        children: [
                          e.jsx("div", {
                            className:
                              "w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center",
                            children: e.jsx(D, { className: "w-10 h-10 text-primary" }),
                          }),
                          e.jsx("h3", {
                            className: "text-2xl font-black text-foreground",
                            children: "پیام شما ارسال شد!",
                          }),
                          e.jsx("p", {
                            className: "text-muted-foreground max-w-sm",
                            children:
                              "همکاران ما به‌زودی با شما تماس خواهند گرفت. ممنون از اعتماد شما.",
                          }),
                          e.jsx("button", {
                            onClick: () => {
                              (r(!1), n({ name: "", phone: "", message: "" }));
                            },
                            className: "text-primary font-bold text-sm underline",
                            children: "ارسال پیام جدید",
                          }),
                        ],
                      })
                    : e.jsxs(e.Fragment, {
                        children: [
                          e.jsx("h3", {
                            className: "text-xl font-black text-foreground mb-6",
                            children: "ارسال پیام",
                          }),
                          e.jsxs("form", {
                            onSubmit: async (t) => {
                              t.preventDefault();
                              try {
                                const o = await fetch(window.__DRB_FORMS_API__.contact, {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json" },
                                    body: JSON.stringify({ ...l, website: "" }),
                                  }),
                                  d = await o.json();
                                if (!o.ok) throw new Error(d.message || "ارسال انجام نشد");
                                r(!0);
                              } catch (o) {
                                alert(o.message || "ارسال انجام نشد");
                              }
                            },
                            className: "space-y-5",
                            children: [
                              e.jsxs("div", {
                                className: "grid sm:grid-cols-2 gap-5",
                                children: [
                                  e.jsxs("div", {
                                    children: [
                                      e.jsxs("label", {
                                        className: "block text-sm font-bold text-foreground mb-2",
                                        children: [
                                          "نام و نام خانوادگی ",
                                          e.jsx("span", {
                                            className: "text-red-500",
                                            children: "*",
                                          }),
                                        ],
                                      }),
                                      e.jsx("input", {
                                        required: !0,
                                        value: l.name,
                                        onChange: (t) => n({ ...l, name: t.target.value }),
                                        placeholder: "نام خود را وارد کنید",
                                        className:
                                          "w-full bg-muted/50 border border-border rounded-xl px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all",
                                      }),
                                    ],
                                  }),
                                  e.jsxs("div", {
                                    children: [
                                      e.jsxs("label", {
                                        className: "block text-sm font-bold text-foreground mb-2",
                                        children: [
                                          "شماره تلفن ",
                                          e.jsx("span", {
                                            className: "text-red-500",
                                            children: "*",
                                          }),
                                        ],
                                      }),
                                      e.jsx("input", {
                                        required: !0,
                                        value: l.phone,
                                        onChange: (t) => n({ ...l, phone: t.target.value }),
                                        placeholder: "۰۹۱۲ *** ****",
                                        className:
                                          "w-full bg-muted/50 border border-border rounded-xl px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all",
                                        dir: "ltr",
                                      }),
                                    ],
                                  }),
                                ],
                              }),
                              e.jsxs("div", {
                                children: [
                                  e.jsxs("label", {
                                    className: "block text-sm font-bold text-foreground mb-2",
                                    children: [
                                      "پیام شما ",
                                      e.jsx("span", { className: "text-red-500", children: "*" }),
                                    ],
                                  }),
                                  e.jsx("textarea", {
                                    required: !0,
                                    value: l.message,
                                    onChange: (t) => n({ ...l, message: t.target.value }),
                                    placeholder: "سؤال یا درخواست خود را بنویسید...",
                                    rows: 5,
                                    className:
                                      "w-full bg-muted/50 border border-border rounded-xl px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all resize-none",
                                  }),
                                ],
                              }),
                              e.jsx("button", {
                                type: "submit",
                                className:
                                  "w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-2xl transition-all shadow-lg hover:shadow-primary/30 text-lg",
                                children: "ارسال پیام",
                              }),
                            ],
                          }),
                        ],
                      }),
                }),
                e.jsx("div", {
                  className: "rounded-3xl overflow-hidden border border-border shadow-lg h-64",
                  children: e.jsx("iframe", {
                    src: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3237.671371427505!2d51.4138246!3d35.758881300000006!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzXCsDQ1JzMyLjAiTiA1McKwMjQnNTAuMiJF!5e0!3m2!1sfa!2sir!4v1698000000000!5m2!1sfa!2sir",
                    width: "100%",
                    height: "100%",
                    style: { border: 0 },
                    allowFullScreen: !0,
                    loading: "lazy",
                    referrerPolicy: "no-referrer-when-downgrade",
                    title: "محل کلینیک دکتر باستانی‌نژاد",
                  }),
                }),
              ],
            }),
          ],
        }),
      ],
    }),
  });
}
function Js() {
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, { id: "jsonld-home", data: [le, re] }),
      e.jsx(Os, {}),
      e.jsx($s, {}),
      e.jsx(Ws, {}),
      e.jsx(Gs, {}),
      e.jsx(qs, {}),
      e.jsx(Qs, {}),
      e.jsx(Hs, {}),
      e.jsx(Us, {}),
      e.jsx(Vs, {}),
      e.jsx(Ys, {}),
      e.jsx(Ks, {}),
      e.jsx(Xs, {}),
      e.jsx(Zs, {}),
    ],
  });
}
const et =
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/DrShahinBastaninejadPortrait2.webp",
  st =
    "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Doctor/Dr Shahin Bastani Nejad (9).webp",
  J = [
    "7th international rhinoplasty n facial plastic surgery congress.webp",
    "7Th Master Dissection Course Rhinology Reconstructive Plastic Surgery.webp",
    "8th Intl Congress IR Rhinology Society.webp",
    "3rd SRIC Shiraz Rhinology Intl Course Certificate.webp",
    "TUMS Nasal Valve Reconstruction Certificate.webp",
    "Tajikistan Certificate.webp",
    "4th Intl Conference Iraqi Kurdistan Otorhinolaryngology Head-Neck Surgery.webp",
    "5th Intl Conference Exhibition Iraqi Kurdistan Otorhinolaryngology Head-Neck Surgery.webp",
    "Beauty treatments n health centres.webp",
  ],
  B = oe.map((s, r) => ({ title: s, img: `${window.__DRB_WP__?.themeUri || "/wp-content/themes/drbastaninejad-theme"}/assets/dist6/images/Certificates/${J[r] ?? J[0]}` }));
function tt() {
  const [s, r] = h.useState(null),
    l = B.length,
    n = [{ label: "خانه", href: "/" }, { label: "درباره دکتر" }];
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, { id: "jsonld-about", data: [E(n), re, le] }),
      e.jsx(v, { items: n }),
      e.jsx("section", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-20 px-4 sm:px-6",
        children: e.jsx("div", {
          className: "max-w-[1200px] mx-auto",
          children: e.jsxs("div", {
            className: "grid lg:grid-cols-2 gap-12 items-center",
            children: [
              e.jsxs("div", {
                children: [
                  e.jsx("span", {
                    className: "text-[#28722C] font-bold text-sm tracking-wider",
                    children: "رزومه تخصصی",
                  }),
                  e.jsx("h1", {
                    className: "text-4xl sm:text-5xl font-black mt-2 mb-6 leading-tight",
                    children: y,
                  }),
                  e.jsx("p", {
                    className: "text-white/75 text-lg leading-relaxed mb-8",
                    children:
                      "دانشیار دانشگاه علوم پزشکی تهران و عضو هیئت علمی بیمارستان امیراعلم، دبیر کمیته علمی رینولوژی انجمن جراحی پلاستیک بینی ایران. با بیش از ۱۸ سال تجربه تخصصی در جراحی‌های بینی اولیه، ترمیمی و درمانی.",
                  }),
                  e.jsxs("div", {
                    className: "flex flex-wrap gap-4",
                    children: [
                      e.jsxs(m, {
                        to: "/booking",
                        className:
                          "flex items-center gap-2 px-6 py-3 bg-[#28722C] text-white rounded-xl font-bold hover:bg-[#246b28] transition-colors",
                        children: [e.jsx(N, { size: 16 }), "رزرو مشاوره"],
                      }),
                      e.jsx(m, {
                        to: "/contact",
                        className:
                          "inline-flex items-center justify-center gap-2 px-6 py-3 border border-white/30 text-white rounded-xl font-bold hover:bg-white/10 transition-colors min-h-12",
                        children: "تماس با ما",
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs("div", {
                className: "relative",
                children: [
                  e.jsx("div", {
                    className:
                      "rounded-3xl overflow-hidden aspect-[4/5] shadow-2xl max-w-md mx-auto",
                    children: e.jsx("img", {
                      src: et,
                      alt: y,
                      className: "w-full h-full object-cover",
                    }),
                  }),
                  e.jsx("div", {
                    className: "absolute -bottom-4 -right-4 bg-white rounded-2xl p-4 shadow-xl",
                    children: e.jsx("div", {
                      className: "grid grid-cols-2 gap-4",
                      children: [
                        { val: "+۱۸", label: "سال تجربه" },
                        { val: "+۲۰۰۰", label: "عمل موفق" },
                      ].map((t) =>
                        e.jsxs(
                          "div",
                          {
                            className: "text-center",
                            children: [
                              e.jsx("p", {
                                className: "text-xl font-black text-[#28722C]",
                                children: t.val,
                              }),
                              e.jsx("p", { className: "text-xs text-gray-500", children: t.label }),
                            ],
                          },
                          t.label,
                        ),
                      ),
                    }),
                  }),
                ],
              }),
            ],
          }),
        }),
      }),
      e.jsx("section", {
        className: "bg-[#28722C] py-10",
        children: e.jsx("div", {
          className: "max-w-[1200px] mx-auto px-4 sm:px-6",
          children: e.jsx("div", {
            className: "grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6",
            children: ie.map((t, i) =>
              e.jsxs(
                "div",
                {
                  className: "flex flex-col items-center text-center gap-2",
                  children: [
                    e.jsx("div", {
                      className:
                        "w-10 h-10 bg-white/15 rounded-2xl flex items-center justify-center",
                      children: e.jsx(t.icon, { className: "w-5 h-5 text-white" }),
                    }),
                    e.jsx("p", {
                      className: "text-white font-bold text-xs leading-snug",
                      children: t.title,
                    }),
                    e.jsx("p", { className: "text-white/60 text-[10px]", children: t.subtitle }),
                  ],
                },
                i,
              ),
            ),
          }),
        }),
      }),
      e.jsx("section", {
        className: "py-20 px-4 sm:px-6 bg-white",
        children: e.jsx("div", {
          className: "max-w-[1200px] mx-auto",
          children: e.jsxs("div", {
            className: "grid lg:grid-cols-2 gap-12 items-start",
            children: [
              e.jsxs("div", {
                children: [
                  e.jsx("span", {
                    className: "text-[#28722C] font-bold text-sm tracking-wider",
                    children: "بیوگرافی",
                  }),
                  e.jsx("h2", {
                    className: "text-3xl font-black text-[#25272C] mt-2 mb-6",
                    children: "مسیر علمی و حرفه‌ای",
                  }),
                  e.jsxs("div", {
                    className: "space-y-4 text-[#545B64] leading-[2] text-sm",
                    children: [
                      e.jsx("p", {
                        children:
                          "دکتر شاهین باستانی‌نژاد پس از اخذ دکترای پزشکی عمومی از دانشگاه علوم پزشکی اصفهان، دوره تخصصی گوش، حلق و بینی را در دانشگاه علوم پزشکی تهران با رتبه ۴ بورد کشوری به پایان رساند.",
                      }),
                      e.jsx("p", {
                        children:
                          "ایشان فلوشیپ تخصصی رینوپلاستی را در بیمارستان امیراعلم تهران گذراند و از سال ۱۳۸۸ به عضویت هیئت علمی دانشگاه علوم پزشکی تهران درآمد.",
                      }),
                      e.jsx("p", {
                        children:
                          "دکتر باستانی‌نژاد در حال حاضر دبیر کمیته علمی رینولوژی انجمن جراحی پلاستیک بینی ایران بوده و در بیش از ۱۰ دوره سمینار تخصصی رینوپلاستی و آندوسکوپی سینوس به‌عنوان مدرس فعالیت داشته است.",
                      }),
                    ],
                  }),
                  e.jsx("div", {
                    className: "grid grid-cols-2 gap-3 mt-8",
                    children: [
                      "دانشگاه علوم پزشکی تهران",
                      "بیمارستان امیراعلم تهران",
                      "انجمن جراحی پلاستیک بینی ایران",
                      "انجمن متخصصان گوش، حلق و بینی ایران",
                    ].map((t) =>
                      e.jsxs(
                        "div",
                        {
                          className:
                            "flex items-center gap-2 bg-[#F7F8F6] rounded-xl p-3 border border-[#DDE2DD]",
                          children: [
                            e.jsx(D, { className: "w-4 h-4 text-[#28722C] flex-shrink-0" }),
                            e.jsx("span", {
                              className: "text-xs font-medium text-[#25272C]",
                              children: t,
                            }),
                          ],
                        },
                        t,
                      ),
                    ),
                  }),
                ],
              }),
              e.jsxs("div", {
                children: [
                  e.jsx("h3", {
                    className: "text-xl font-bold text-[#25272C] mb-6",
                    children: "مسیر تحصیل و تجربه",
                  }),
                  e.jsxs("div", {
                    className: "relative",
                    children: [
                      e.jsx("div", {
                        className: "absolute right-5 top-0 bottom-0 w-0.5 bg-[#28722C]/20",
                      }),
                      e.jsx("div", {
                        className: "space-y-6",
                        children: ne.map((t, i) => {
                          const a = t.icon;
                          return e.jsxs(
                            "div",
                            {
                              className: "flex gap-5",
                              children: [
                                e.jsx("div", {
                                  className: "relative flex-shrink-0",
                                  children: e.jsx("div", {
                                    className:
                                      "w-10 h-10 bg-[#28722C] rounded-full flex items-center justify-center shadow-md z-10 relative",
                                    children: e.jsx(a, { className: "w-5 h-5 text-white" }),
                                  }),
                                }),
                                e.jsxs("div", {
                                  className: "pb-2",
                                  children: [
                                    e.jsx("span", {
                                      className:
                                        "text-xs font-bold text-[#28722C] bg-[#E4F0E4] rounded-full px-3 py-1",
                                      children: t.year,
                                    }),
                                    e.jsx("p", {
                                      className: "font-bold text-[#25272C] mt-2",
                                      children: t.title,
                                    }),
                                    e.jsx("p", {
                                      className: "text-[#545B64] text-sm mt-0.5",
                                      children: t.org,
                                    }),
                                  ],
                                }),
                              ],
                            },
                            i,
                          );
                        }),
                      }),
                    ],
                  }),
                ],
              }),
            ],
          }),
        }),
      }),
      e.jsx("section", {
        className: "py-20 px-4 sm:px-6 bg-[#F7F8F6]",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto",
          children: [
            e.jsxs("div", {
              className: "text-center mb-12",
              children: [
                e.jsx("span", {
                  className: "text-[#28722C] font-bold text-sm tracking-wider",
                  children: "گواهینامه‌ها",
                }),
                e.jsx("h2", {
                  className: "text-3xl font-black text-[#25272C] mt-2",
                  children: "مدارک و افتخارات بین‌المللی",
                }),
              ],
            }),
            e.jsx("div", {
              className: "grid sm:grid-cols-2 lg:grid-cols-3 gap-5",
              children: B.map((t, i) =>
                e.jsxs(
                  "button",
                  {
                    onClick: () => r(i),
                    className:
                      "group bg-white border border-[#DDE2DD] rounded-2xl overflow-hidden hover:border-[#28722C]/40 hover:shadow-lg transition-all text-right focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#28722C]",
                    "aria-label": `مشاهده گواهینامه: ${t.title}`,
                    children: [
                      e.jsx("div", {
                        className: "aspect-[4/3] overflow-hidden bg-[#F0F4F0]",
                        children: e.jsx("img", {
                          src: t.img,
                          alt: t.title,
                          loading: "lazy",
                          className:
                            "w-full h-full object-cover group-hover:scale-105 transition-transform duration-300",
                          onError: (a) => {
                            a.currentTarget.style.display = "none";
                          },
                        }),
                      }),
                      e.jsxs("div", {
                        className: "p-4 flex items-start gap-3",
                        children: [
                          e.jsx("div", {
                            className:
                              "w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5",
                            children: e.jsx(Q, { className: "w-4 h-4 text-amber-600" }),
                          }),
                          e.jsx("p", {
                            className: "text-[#25272C] font-medium text-sm leading-relaxed",
                            children: t.title,
                          }),
                        ],
                      }),
                    ],
                  },
                  i,
                ),
              ),
            }),
          ],
        }),
      }),
      s !== null &&
        e.jsx("div", {
          role: "dialog",
          "aria-modal": "true",
          "aria-label": "مشاهده گواهینامه",
          className:
            "fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm px-4",
          onClick: () => r(null),
          children: e.jsxs("div", {
            className: "relative max-w-3xl w-full",
            onClick: (t) => t.stopPropagation(),
            children: [
              e.jsx("button", {
                onClick: () => r(null),
                "aria-label": "بستن",
                className:
                  "absolute -top-12 left-0 text-white hover:text-[#E4F0E4] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded-lg",
                children: e.jsx(G, { size: 28 }),
              }),
              e.jsx("img", {
                src: B[s].img,
                alt: B[s].title,
                className: "w-full rounded-2xl shadow-2xl",
              }),
              e.jsx("p", {
                className: "text-center text-white/90 mt-4 text-sm font-medium px-4",
                children: B[s].title,
              }),
              l > 1 &&
                e.jsxs("div", {
                  className: "flex justify-between mt-4",
                  children: [
                    e.jsxs("button", {
                      onClick: () => r((s + 1) % l),
                      "aria-label": "قبلی",
                      className:
                        "flex items-center gap-1 text-white/70 hover:text-white text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded-lg px-2 py-1",
                      children: [e.jsx(H, { size: 18 }), "بعدی"],
                    }),
                    e.jsxs("span", {
                      className: "text-white/50 text-xs self-center",
                      children: [s + 1, " / ", l],
                    }),
                    e.jsxs("button", {
                      onClick: () => r((s - 1 + l) % l),
                      "aria-label": "بعدی",
                      className:
                        "flex items-center gap-1 text-white/70 hover:text-white text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded-lg px-2 py-1",
                      children: ["قبلی", e.jsx(W, { size: 18 })],
                    }),
                  ],
                }),
            ],
          }),
        }),
      e.jsx("section", {
        className: "py-20 px-4 sm:px-6 bg-white",
        children: e.jsx("div", {
          className: "max-w-[1200px] mx-auto",
          children: e.jsxs("div", {
            className: "grid lg:grid-cols-2 gap-12 items-center",
            children: [
              e.jsxs("div", {
                children: [
                  e.jsx("span", {
                    className: "text-[#28722C] font-bold text-sm tracking-wider",
                    children: "کلینیک",
                  }),
                  e.jsx("h2", {
                    className: "text-3xl font-black text-[#25272C] mt-2 mb-6",
                    children: "محیطی ایمن، مجهز و آرام",
                  }),
                  e.jsx("p", {
                    className: "text-[#545B64] leading-[2] mb-6 text-sm",
                    children:
                      "کلینیک دکتر باستانی‌نژاد با تجهیزات به‌روز و رعایت استانداردهای پزشکی، محیطی امن برای انجام جراحی‌های تخصصی بینی فراهم کرده است.",
                  }),
                  e.jsx("div", {
                    className: "space-y-3",
                    children: [
                      "تجهیزات پیشرفته اتاق عمل",
                      "تیم بیهوشی متخصص",
                      "مجوز رسمی وزارت بهداشت",
                      "سیستم تهویه استاندارد جراحی",
                    ].map((t) =>
                      e.jsxs(
                        "div",
                        {
                          className: "flex items-center gap-3",
                          children: [
                            e.jsx(D, { className: "w-5 h-5 text-[#28722C] flex-shrink-0" }),
                            e.jsx("span", { className: "text-sm text-[#25272C]", children: t }),
                          ],
                        },
                        t,
                      ),
                    ),
                  }),
                ],
              }),
              e.jsx("div", {
                className: "rounded-3xl overflow-hidden aspect-video shadow-xl",
                children: e.jsx("img", {
                  src: st,
                  alt: "کلینیک دکتر باستانی‌نژاد",
                  className: "w-full h-full object-cover",
                }),
              }),
            ],
          }),
        }),
      }),
    ],
  });
}
function at() {
  const s = [{ label: "خانه", href: "/" }, { label: "همه خدمات" }];
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, { id: "jsonld-services", data: E(s) }),
      e.jsx(v, { items: s }),
      e.jsx("section", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto text-center",
          children: [
            e.jsx("span", {
              className: "text-[#28722C] font-bold text-sm tracking-wider",
              children: "تخصص‌های ما",
            }),
            e.jsx("h1", {
              className: "text-4xl sm:text-5xl font-black mt-2 mb-4",
              children: "خدمات جراحی بینی",
            }),
            e.jsx("p", {
              className: "text-white/70 text-lg max-w-xl mx-auto",
              children:
                "از راینوپلاستی زیبایی تا جراحی‌های درمانی — همه با بالاترین استانداردهای پزشکی",
            }),
          ],
        }),
      }),
      e.jsxs("div", {
        className: "max-w-[1200px] mx-auto px-4 sm:px-6 py-16",
        children: [
          e.jsxs("div", {
            className: "mb-16",
            children: [
              e.jsxs("div", {
                className: "flex items-center gap-3 mb-8",
                children: [
                  e.jsx("div", { className: "w-1 h-8 bg-[#28722C] rounded-full" }),
                  e.jsx("h2", {
                    className: "text-2xl font-black text-[#25272C]",
                    children: "خدمات زیبایی",
                  }),
                ],
              }),
              e.jsx("div", {
                className: "grid sm:grid-cols-2 lg:grid-cols-3 gap-5",
                children: L.map((r) =>
                  e.jsxs(
                    m,
                    {
                      to: `/services/${r.slug}`,
                      className:
                        "group bg-white border border-[#DDE2DD] rounded-2xl p-6 hover:border-[#28722C]/40 hover:shadow-lg hover:shadow-[#28722C]/5 transition-all",
                      children: [
                        e.jsx("div", {
                          className:
                            "w-12 h-12 bg-[#E4F0E4] rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[#28722C] transition-colors [&>span>span>svg]:w-6 [&>span>span>svg]:h-6 [&>span>svg]:w-6 [&>span>svg]:h-6",
                          children: e.jsx(_, {
                            slug: r.slug,
                            FallbackIcon: r.icon,
                            size: 24,
                            className: "text-[#28722C] group-hover:text-white transition-colors",
                          }),
                        }),
                        e.jsx("h3", {
                          className: "text-lg font-bold text-[#25272C] mb-1",
                          children: r.title,
                        }),
                        e.jsx("p", {
                          className: "text-sm text-[#28722C] font-medium mb-3",
                          children: r.subtitle,
                        }),
                        e.jsx("p", {
                          className: "text-sm text-[#545B64] leading-relaxed line-clamp-3",
                          children: r.desc,
                        }),
                        e.jsxs("div", {
                          className:
                            "flex items-center gap-1 mt-4 text-[#28722C] text-sm font-bold group-hover:gap-2 transition-all",
                          children: ["اطلاعات بیشتر ", e.jsx(j, { size: 14 })],
                        }),
                      ],
                    },
                    r.id,
                  ),
                ),
              }),
            ],
          }),
          e.jsxs("div", {
            className: "mb-16",
            children: [
              e.jsxs("div", {
                className: "flex items-center gap-3 mb-8",
                children: [
                  e.jsx("div", { className: "w-1 h-8 bg-blue-500 rounded-full" }),
                  e.jsx("h2", {
                    className: "text-2xl font-black text-[#25272C]",
                    children: "خدمات درمانی",
                  }),
                ],
              }),
              e.jsx("div", {
                className: "grid sm:grid-cols-2 lg:grid-cols-3 gap-5",
                children: M.map((r) =>
                  e.jsxs(
                    m,
                    {
                      to: `/services/${r.slug}`,
                      className:
                        "group bg-white border border-[#DDE2DD] rounded-2xl p-6 hover:border-blue-400/40 hover:shadow-lg transition-all",
                      children: [
                        e.jsx("div", {
                          className:
                            "w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-blue-500 transition-colors [&>span>span>svg]:w-6 [&>span>span>svg]:h-6 [&>span>svg]:w-6 [&>span>svg]:h-6",
                          children: e.jsx(_, {
                            slug: r.slug,
                            FallbackIcon: r.icon,
                            size: 24,
                            className: "text-blue-500 group-hover:text-white transition-colors",
                          }),
                        }),
                        e.jsx("h3", {
                          className: "text-lg font-bold text-[#25272C] mb-1",
                          children: r.title,
                        }),
                        e.jsx("p", {
                          className: "text-sm text-blue-600 font-medium mb-3",
                          children: r.subtitle,
                        }),
                        e.jsx("p", {
                          className: "text-sm text-[#545B64] leading-relaxed line-clamp-3",
                          children: r.desc,
                        }),
                        e.jsxs("div", {
                          className:
                            "flex items-center gap-1 mt-4 text-blue-600 text-sm font-bold group-hover:gap-2 transition-all",
                          children: ["اطلاعات بیشتر ", e.jsx(j, { size: 14 })],
                        }),
                      ],
                    },
                    r.id,
                  ),
                ),
              }),
            ],
          }),
          e.jsxs("div", {
            className:
              "bg-gradient-to-br from-[#28722C] to-[#1a4e1d] rounded-3xl p-10 text-white text-center",
            children: [
              e.jsx("h2", {
                className: "text-2xl font-black mb-3",
                children: "می‌خواهید بدانید کدام خدمت برای شما مناسب است؟",
              }),
              e.jsx("p", {
                className: "text-white/80 mb-6",
                children: "با دکتر باستانی‌نژاد مشاوره رایگان داشته باشید.",
              }),
              e.jsxs(m, {
                to: "/booking",
                className:
                  "inline-flex items-center gap-2 bg-white text-[#28722C] font-black px-8 py-4 rounded-2xl hover:bg-[#E4F0E4] transition-colors",
                children: [e.jsx(N, { size: 18 }), " رزرو نوبت مشاوره"],
              }),
            ],
          }),
        ],
      }),
    ],
  });
}
function lt() {
  const [s, r] = h.useState(0),
    l = 8,
    n = Math.ceil(R.length / l),
    t = R.slice(s * l, s * l + l),
    i = [{ label: "خانه", href: "/" }, { label: "گالری قبل و بعد" }];
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, { id: "jsonld-gallery", data: E(i) }),
      e.jsx(v, { items: i }),
      e.jsx("section", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto text-center",
          children: [
            e.jsx("span", {
              className: "text-[#28722C] font-bold text-sm tracking-wider",
              children: "نتایج واقعی",
            }),
            e.jsx("h1", {
              className: "text-4xl sm:text-5xl font-black mt-2 mb-4",
              children: "گالری قبل و بعد",
            }),
            e.jsx("p", {
              className: "text-white/70 text-lg text-center",
              children: R.length
                ? `${R.length} نمونه تأییدشده از نتایج جراحی بینی`
                : "نمونه تأییدشده‌ای برای انتشار ثبت نشده است.",
            }),
          ],
        }),
      }),
      null,
      e.jsxs("div", {
        className: "max-w-[1200px] mx-auto px-4 sm:px-6 py-12",
        children: [
          R.length
            ? e.jsx("div", {
                className: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8",
                children: t.map((a) =>
                  e.jsx(je, { before: a.before, after: a.after, fallback: "" }, a.id),
                ),
              })
            : e.jsx("div", {
                className:
                  "rounded-3xl border border-dashed border-[#DDE2DD] bg-white p-10 text-center text-[#545B64]",
                children: "گالری خالی است. نمونه‌ها پس از ثبت رضایت انتشار در مدیریت وردپرس نمایش داده می‌شوند.",
              }),
          R.length
            ? e.jsxs("div", {
            className: "flex justify-center items-center gap-3",
            children: [
              e.jsx("button", {
                onClick: () => r((a) => Math.max(0, a - 1)),
                disabled: s === 0,
                className:
                  "w-10 h-10 rounded-xl border border-[#DDE2DD] bg-white flex items-center justify-center hover:bg-[#E4F0E4] disabled:opacity-40 transition-colors",
                children: e.jsx(H, { className: "w-5 h-5" }),
              }),
              Array.from({ length: n }).map((a, c) =>
                e.jsx(
                  "button",
                  {
                    onClick: () => r(c),
                    className: `w-10 h-10 rounded-xl font-bold text-sm transition-all ${s === c ? "bg-[#28722C] text-white" : "bg-white border border-[#DDE2DD] text-[#25272C] hover:bg-[#E4F0E4]"}`,
                    children: c + 1,
                  },
                  c,
                ),
              ),
              e.jsx("button", {
                onClick: () => r((a) => Math.min(n - 1, a + 1)),
                disabled: s === n - 1,
                className:
                  "w-10 h-10 rounded-xl border border-[#DDE2DD] bg-white flex items-center justify-center hover:bg-[#E4F0E4] disabled:opacity-40 transition-colors",
                children: e.jsx(W, { className: "w-5 h-5" }),
              }),
            ],
              })
            : null,
          R.length
            ? e.jsx("p", {
            className: "text-center text-xs text-[#545B64] mt-6 max-w-xl mx-auto",
            children:
              "تمام تصاویر با رضایت کامل بیماران و رعایت حریم خصوصی منتشر شده‌اند. نتایج ممکن است متفاوت باشد.",
              })
            : null,
        ],
      }),
    ],
  });
}
function rt() {
  const [s, r] = h.useState("همه"),
    l = ["همه", ...Array.from(new Set(f.map((a) => a.cat)))],
    n = s === "همه" ? f : f.filter((a) => a.cat === s),
    t = n.find((a) => a.featured) ?? n[0],
    i = n.filter((a) => a.id !== t.id);
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(v, { items: [{ label: "خانه", href: "/" }, { label: "مقالات" }] }),
      e.jsx("section", {
        className:
          "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-12 sm:py-16 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto text-center",
          children: [
            e.jsx("span", {
              className: "text-[#28722C] font-bold text-sm tracking-wider text-center",
              className: "text-center",
              children: "دانش پزشکی",
            }),
            e.jsx("h1", {
              className: "text-3xl sm:text-5xl font-black mt-2 mb-3 text-center",
              className: "text-center",
              children: "مقالات تخصصی",
            }),
            e.jsx("p", {
              className: "text-white/70 text-base sm:text-lg",
              children: "آموزش، راهنمایی و اطلاعات علمی درباره جراحی بینی",
            }),
          ],
        }),
      }),
      e.jsxs("div", {
        className: "max-w-[1200px] mx-auto px-4 sm:px-6 py-8 sm:py-12",
        children: [
          e.jsx("div", {
            className: "flex flex-wrap gap-2 mb-8",
            children: l.map((a) =>
              e.jsx(
                "button",
                {
                  onClick: () => r(a),
                  className: `px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold transition-all ${s === a ? "bg-[#28722C] text-white" : "bg-white border border-[#DDE2DD] text-[#545B64] hover:border-[#28722C]/30 hover:text-[#28722C]"}`,
                  children: a,
                },
                a,
              ),
            ),
          }),
          e.jsxs(m, {
            to: `/blog/${t.slug}`,
            className: "block group mb-8 sm:mb-10",
            children: [
              e.jsxs("div", {
                className:
                  "sm:hidden bg-white border border-[#DDE2DD] rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow",
                children: [
                  e.jsxs("div", {
                    className: "relative h-48 overflow-hidden",
                    children: [
                      e.jsx("img", {
                        src: t.img,
                        alt: t.title,
                        className:
                          "w-full h-full object-cover group-hover:scale-105 transition-transform duration-500",
                      }),
                      e.jsx("div", {
                        className: "absolute inset-0 bg-gradient-to-t from-black/60 to-transparent",
                      }),
                      e.jsx("span", {
                        className:
                          "absolute top-3 right-3 bg-[#28722C] text-white text-xs font-bold px-3 py-1 rounded-full",
                        children: "مقاله ویژه",
                      }),
                    ],
                  }),
                  e.jsxs("div", {
                    className: "p-4",
                    children: [
                      e.jsx("span", {
                        className:
                          "text-xs font-bold text-[#28722C] bg-[#E4F0E4] px-2.5 py-1 rounded-full inline-block mb-2",
                        children: t.cat,
                      }),
                      e.jsx("h2", {
                        className:
                          "text-base font-black text-[#25272C] leading-snug mb-2 group-hover:text-[#28722C] transition-colors",
                        children: t.title,
                      }),
                      e.jsx("p", {
                        className: "text-sm text-[#545B64] line-clamp-2 mb-3",
                        children: t.excerpt,
                      }),
                      e.jsxs("div", {
                        className: "flex items-center justify-between text-xs text-[#545B64]",
                        children: [
                          e.jsxs("span", {
                            className: "flex items-center gap-1",
                            children: [e.jsx(F, { size: 11 }), "۱۲ دقیقه مطالعه"],
                          }),
                          e.jsxs("span", {
                            className: "flex items-center gap-1",
                            children: [e.jsx(N, { size: 11 }), t.date],
                          }),
                        ],
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs("div", {
                className:
                  "hidden sm:block relative rounded-3xl overflow-hidden aspect-[16/7] shadow-lg",
                children: [
                  e.jsx("img", {
                    src: t.img,
                    alt: t.title,
                    className:
                      "w-full h-full object-cover group-hover:scale-105 transition-transform duration-500",
                  }),
                  e.jsx("div", {
                    className:
                      "absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent",
                  }),
                  e.jsx("div", {
                    className: "absolute top-4 right-4",
                    children: e.jsx("span", {
                      className:
                        "bg-[#28722C] text-white text-xs font-bold px-3 py-1.5 rounded-full",
                      children: "مقاله ویژه",
                    }),
                  }),
                  e.jsxs("div", {
                    className: "absolute bottom-0 inset-x-0 p-6 sm:p-8 text-white",
                    children: [
                      e.jsx("span", {
                        className:
                          "text-xs font-bold bg-white/20 backdrop-blur-sm rounded-full px-3 py-1.5 mb-3 inline-block",
                        children: t.cat,
                      }),
                      e.jsx("h2", {
                        className: "text-xl sm:text-3xl font-black leading-snug mb-2",
                        children: t.title,
                      }),
                      e.jsx("p", {
                        className: "text-white/75 line-clamp-2 text-sm sm:text-base",
                        children: t.excerpt,
                      }),
                      e.jsxs("div", {
                        className: "flex items-center gap-4 mt-3 text-white/60 text-sm",
                        children: [
                          e.jsxs("span", {
                            className: "flex items-center gap-1",
                            children: [e.jsx(F, { size: 12 }), "۱۲ دقیقه"],
                          }),
                          e.jsx("span", { children: t.date }),
                        ],
                      }),
                    ],
                  }),
                ],
              }),
            ],
          }),
          e.jsx("div", {
            className: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6",
            children: i.map((a) =>
              e.jsx(
                "article",
                {
                  children: e.jsxs(m, {
                    to: `/blog/${a.slug}`,
                    className:
                      "group bg-white border border-[#DDE2DD] rounded-2xl overflow-hidden hover:shadow-md hover:border-[#28722C]/20 transition-all block h-full",
                    children: [
                      e.jsx("div", {
                        className: "overflow-hidden",
                        children: e.jsx("img", {
                          src: a.img,
                          alt: a.title,
                          className:
                            "w-full h-40 sm:h-48 object-cover group-hover:scale-105 transition-transform duration-500",
                        }),
                      }),
                      e.jsxs("div", {
                        className: "p-4",
                        children: [
                          e.jsx("span", {
                            className:
                              "text-xs font-bold text-[#28722C] bg-[#E4F0E4] px-2.5 py-0.5 rounded-full inline-block mb-2",
                            children: a.cat,
                          }),
                          e.jsx("h3", {
                            className:
                              "font-bold text-[#25272C] mb-2 leading-snug text-sm sm:text-base group-hover:text-[#28722C] transition-colors line-clamp-2",
                            children: a.title,
                          }),
                          e.jsx("p", {
                            className: "text-xs sm:text-sm text-[#545B64] line-clamp-2 mb-3",
                            children: a.excerpt,
                          }),
                          e.jsxs("div", {
                            className:
                              "flex items-center justify-between text-xs text-[#545B64] pt-2 border-t border-[#DDE2DD]/50",
                            children: [
                              e.jsxs("span", {
                                className: "flex items-center gap-1",
                                children: [
                                  e.jsx(F, { size: 10 }),
                                  e.jsx("span", { children: "۸ دقیقه" }),
                                ],
                              }),
                              e.jsxs("span", {
                                className: "flex items-center gap-1",
                                children: [
                                  e.jsx(N, { size: 10 }),
                                  e.jsx("span", { children: a.date }),
                                ],
                              }),
                            ],
                          }),
                        ],
                      }),
                    ],
                  }),
                },
                a.id,
              ),
            ),
          }),
          e.jsxs("div", {
            className: "mt-10 sm:mt-12 flex justify-center gap-2",
            children: [
              [1, 2, 3].map((a) =>
                e.jsx(
                  "button",
                  {
                    className: `w-10 h-10 rounded-xl font-bold text-sm ${a === 1 ? "bg-[#28722C] text-white" : "bg-white border border-[#DDE2DD] text-[#545B64] hover:border-[#28722C] hover:text-[#28722C]"}`,
                    children: a,
                  },
                  a,
                ),
              ),
              e.jsxs("button", {
                className:
                  "flex items-center gap-1 px-4 h-10 rounded-xl bg-white border border-[#DDE2DD] text-[#545B64] text-sm hover:border-[#28722C] hover:text-[#28722C]",
                children: ["بعدی ", e.jsx(j, { size: 13 })],
              }),
            ],
          }),
        ],
      }),
    ],
  });
}
function it() {
  const FAQ = window.__DRB_WP__?.faqs?.length ? window.__DRB_WP__.faqs : S;
  const [s, r] = h.useState(null),
    [l, n] = h.useState("همه"),
    t = ["همه", ...Array.from(new Set(FAQ.map((c) => c.cat)))],
    i = l === "همه" ? FAQ : FAQ.filter((c) => c.cat === l),
    a = [{ label: "خانه", href: "/" }, { label: "سوالات متداول" }];
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, {
        id: "jsonld-faq",
        data: [E(a), Ie(FAQ.map((c) => ({ question: c.q, answer: c.a })))],
      }),
      e.jsx(v, { items: a }),
      e.jsx("section", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto text-center",
          children: [
            e.jsx("span", {
              className: "text-[#28722C] font-bold text-sm tracking-wider",
              children: "پاسخ سؤالات شما",
            }),
            e.jsx("h1", {
              className: "text-4xl sm:text-5xl font-black mt-2 mb-4",
              children: "سؤالات متداول",
            }),
            e.jsx("p", {
              className: "text-white/70 text-lg text-center",
              className: "text-center",
              children: "پاسخ جامع به رایج‌ترین سؤالات درباره جراحی بینی",
            }),
          ],
        }),
      }),
      e.jsxs("div", {
        className: "max-w-4xl mx-auto px-4 sm:px-6 py-12",
        children: [
          e.jsx("div", {
            className: "mb-10 px-1",
            children: e.jsx("div", {
              className: "flex flex-wrap justify-center gap-2",
              children: t.map((c) =>
                e.jsx(
                  "button",
                  {
                    onClick: () => {
                      (n(c), r(null));
                    },
                    className: `flex-shrink-0 px-4 py-2 rounded-xl text-sm font-bold transition-all whitespace-nowrap ${l === c ? "bg-[#28722C] text-white" : "bg-white border border-[#DDE2DD] text-[#545B64] hover:border-[#28722C]/30 hover:text-[#28722C]"}`,
                    children: c,
                  },
                  c,
                ),
              ),
            }),
          }),
          e.jsx("div", {
            className: "space-y-3 mb-10",
            children: i.map((c, p) =>
              e.jsxs(
                "div",
                {
                  className: "bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden",
                  children: [
                    e.jsxs("button", {
                      onClick: () => r(s === p ? null : p),
                      className: "w-full flex items-center justify-between p-5 text-right gap-4",
                      children: [
                        e.jsxs("div", {
                          className: "flex items-start gap-3",
                          children: [
                            e.jsx("div", {
                              className:
                                "w-6 h-6 bg-[#E4F0E4] rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5",
                              children: e.jsx("span", {
                                className: "text-[#28722C] text-xs font-black",
                                children: p + 1,
                              }),
                            }),
                            e.jsxs("div", {
                              children: [
                                e.jsx("span", {
                                  className: "text-xs font-bold text-[#28722C]/70 mb-1 block",
                                  children: c.cat,
                                }),
                                e.jsx("span", {
                                  className: "font-bold text-[#25272C] text-sm leading-snug",
                                  children: c.q,
                                }),
                              ],
                            }),
                          ],
                        }),
                        e.jsx($, {
                          size: 16,
                          className: `text-[#28722C] flex-shrink-0 transition-transform ${s === p ? "rotate-180" : ""}`,
                        }),
                      ],
                    }),
                    s === p &&
                      e.jsx("div", {
                        className: "px-5 pb-5 border-t border-[#DDE2DD] pt-4",
                        children: e.jsx("p", {
                          className: "text-sm text-[#545B64] leading-[2]",
                          children: c.a,
                        }),
                      }),
                  ],
                },
                p,
              ),
            ),
          }),
          e.jsx("div", {
            className: "bg-amber-50 border border-amber-200 rounded-2xl p-5",
            children: e.jsx("p", {
              className: "text-xs text-amber-800 leading-relaxed text-center",
              children:
                "اطلاعات ارائه‌شده در این صفحه صرفاً جنبه آموزشی دارند و جایگزین مشاوره یا تشخیص پزشکی تخصصی نمی‌شوند. برای هر تصمیم پزشکی حتماً با جراح متخصص مجاز مشورت کنید.",
            }),
          }),
        ],
      }),
    ],
  });
}
function nt() {
  const [s, r] = h.useState(!1),
    [l, n] = h.useState({ name: "", phone: "", subject: "", message: "" });
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(v, { items: [{ label: "خانه", href: "/" }, { label: "تماس با ما" }] }),
      e.jsx("section", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto text-center",
          children: [
            e.jsx("span", {
              className: "text-[#28722C] font-bold text-sm tracking-wider",
              children: "ارتباط با ما",
            }),
            e.jsx("h1", {
              className: "text-4xl sm:text-5xl font-black mt-2 mb-4 text-white",
              children: "تماس با کلینیک",
            }),
            e.jsx("p", {
              className: "text-white/70 text-lg text-center",
              className: "text-center",
              children: "آماده پاسخ‌گویی به سؤالات شما هستیم",
            }),
          ],
        }),
      }),
      e.jsx("div", {
        className: "max-w-[1200px] mx-auto px-4 sm:px-6 py-16",
        children: e.jsxs("div", {
          className: "grid lg:grid-cols-5 gap-8",
          children: [
            e.jsxs("div", {
              className: "lg:col-span-2 space-y-5",
              children: [
                e.jsxs("div", {
                  className: "bg-white rounded-3xl border border-[#DDE2DD] p-6",
                  children: [
                    e.jsxs("h3", {
                      className: "font-bold text-[#25272C] mb-4 flex items-center gap-2",
                      children: [e.jsx(u, { className: "w-5 h-5 text-[#28722C]" }), " تلفن تماس"],
                    }),
                    T.map((t) =>
                      e.jsxs(
                        "a",
                        {
                          href: `tel:${t.replace(/–/g, "")}`,
                          className:
                            "flex items-center gap-3 p-3 rounded-xl hover:bg-[#E4F0E4] transition-colors group",
                          children: [
                            e.jsx("div", {
                              className:
                                "w-8 h-8 bg-[#E4F0E4] rounded-lg flex items-center justify-center group-hover:bg-[#28722C] transition-colors",
                              children: e.jsx(u, {
                                className:
                                  "w-4 h-4 text-[#28722C] group-hover:text-white transition-colors",
                              }),
                            }),
                            e.jsx("span", { className: "font-bold text-[#25272C]", children: t }),
                          ],
                        },
                        t,
                      ),
                    ),
                  ],
                }),
                e.jsxs("div", {
                  className: "bg-white rounded-3xl border border-[#DDE2DD] p-6",
                  children: [
                    e.jsxs("h3", {
                      className: "font-bold text-[#25272C] mb-3 flex items-center gap-2",
                      children: [e.jsx(q, { className: "w-5 h-5 text-[#28722C]" }), " آدرس"],
                    }),
                    e.jsx("p", { className: "text-[#545B64] text-sm leading-loose", children: ce }),
                    e.jsx("div", {
                      className: "flex flex-wrap gap-2 mt-4",
                      children: [
                        {
                          name: "Google Maps",
                          href: "https://maps.google.com/?q=35.758881,51.413824",
                          icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/GoogleMap.webp",
                        },
                        {
                          name: "نشان",
                          href: "https://nshn.ir/gNbNgYZt_Rxg",
                          icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/Neshan.webp",
                        },
                        {
                          name: "بلد",
                          href: "https://balad.ir/p/3cuwGGif58f8hT",
                          icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/Balad.webp",
                        },
                        {
                          name: "Waze",
                          href: "https://waze.com/ul/htnke3vwqs",
                          icon: "/wp-content/themes/drbastaninejad-theme/assets/dist6/images/Icons/Waze.webp",
                        },
                      ].map((t) =>
                        e.jsxs(
                          "a",
                          {
                            href: t.href,
                            target: "_blank",
                            rel: "noopener noreferrer",
                            className:
                              "inline-flex items-center gap-1.5 text-xs bg-white border border-[#DDE2DD] hover:border-[#28722C] text-[#25272C] rounded-lg px-3 py-2 font-medium transition-colors min-h-10",
                            children: [
                              e.jsx("img", {
                                src: t.icon,
                                alt: "",
                                className: "w-4 h-4 object-contain",
                                loading: "lazy",
                                width: 16,
                                height: 16,
                                onError: (i) => {
                                  i.target.style.display = "none";
                                },
                              }),
                              t.name,
                            ],
                          },
                          t.name,
                        ),
                      ),
                    }),
                  ],
                }),
                e.jsxs("div", {
                  className: "bg-white rounded-3xl border border-[#DDE2DD] p-6",
                  children: [
                    e.jsxs("h3", {
                      className: "font-bold text-[#25272C] mb-3 flex items-center gap-2",
                      children: [e.jsx(F, { className: "w-5 h-5 text-[#28722C]" }), " ساعات پذیرش"],
                    }),
                    e.jsxs("div", {
                      className:
                        "flex items-center justify-between bg-[#E4F0E4] rounded-xl px-4 py-3",
                      children: [
                        e.jsx("span", {
                          className: "text-sm font-medium text-[#25272C]",
                          children: "شنبه تا سه‌شنبه",
                        }),
                        e.jsx("span", {
                          className: "text-sm font-bold text-[#28722C]",
                          children: "۱۵:۰۰ – ۱۸:۰۰",
                        }),
                      ],
                    }),
                    e.jsx("p", {
                      className: "text-xs text-[#545B64] mt-3",
                      children: "برای رزرو نوبت آنلاین ۲۴ ساعته در دسترس هستیم.",
                    }),
                    e.jsx("a", {
                      href: de,
                      target: "_blank",
                      rel: "noopener noreferrer",
                      className:
                        "mt-3 flex items-center justify-center gap-2 w-full py-3 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors",
                      children: "رزرو نوبت آنلاین",
                    }),
                  ],
                }),
              ],
            }),
            e.jsxs("div", {
              className: "lg:col-span-3 space-y-5",
              children: [
                e.jsx("div", {
                  className: "bg-white rounded-3xl border border-[#DDE2DD] p-8",
                  children: s
                    ? e.jsxs("div", {
                        className:
                          "flex flex-col items-center justify-center py-12 text-center gap-4",
                        children: [
                          e.jsx("div", {
                            className:
                              "w-20 h-20 bg-[#E4F0E4] rounded-full flex items-center justify-center",
                            children: e.jsx(D, { className: "w-10 h-10 text-[#28722C]" }),
                          }),
                          e.jsx("h3", {
                            className: "text-2xl font-black text-[#25272C]",
                            children: "پیام شما ارسال شد!",
                          }),
                          e.jsx("p", {
                            className: "text-[#545B64] max-w-sm",
                            children:
                              "همکاران ما به‌زودی با شما تماس می‌گیرند. از اعتماد شما سپاسگزاریم.",
                          }),
                          e.jsx("button", {
                            onClick: () => {
                              (r(!1), n({ name: "", phone: "", subject: "", message: "" }));
                            },
                            className: "text-[#28722C] font-bold text-sm underline",
                            children: "ارسال پیام جدید",
                          }),
                        ],
                      })
                    : e.jsxs(e.Fragment, {
                        children: [
                          e.jsx("h3", {
                            className: "text-xl font-black text-[#25272C] mb-6",
                            children: "ارسال پیام",
                          }),
                          e.jsxs("form", {
                            onSubmit: (t) => {
                              (t.preventDefault(), r(!0));
                            },
                            className: "space-y-5",
                            children: [
                              e.jsxs("div", {
                                className: "grid sm:grid-cols-2 gap-5",
                                children: [
                                  e.jsxs("div", {
                                    children: [
                                      e.jsxs("label", {
                                        className: "block text-sm font-bold text-[#25272C] mb-2",
                                        children: [
                                          "نام و نام خانوادگی ",
                                          e.jsx("span", {
                                            className: "text-red-500",
                                            children: "*",
                                          }),
                                        ],
                                      }),
                                      e.jsx("input", {
                                        required: !0,
                                        value: l.name,
                                        onChange: (t) => n({ ...l, name: t.target.value }),
                                        placeholder: "نام خود را وارد کنید",
                                        className:
                                          "w-full bg-[#F7F8F6] border border-[#DDE2DD] rounded-xl px-4 py-3 text-[#25272C] placeholder:text-[#545B64] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 focus:border-[#28722C] transition-all",
                                      }),
                                    ],
                                  }),
                                  e.jsxs("div", {
                                    children: [
                                      e.jsxs("label", {
                                        className: "block text-sm font-bold text-[#25272C] mb-2",
                                        children: [
                                          "شماره تلفن ",
                                          e.jsx("span", {
                                            className: "text-red-500",
                                            children: "*",
                                          }),
                                        ],
                                      }),
                                      e.jsx("input", {
                                        required: !0,
                                        value: l.phone,
                                        onChange: (t) => n({ ...l, phone: t.target.value }),
                                        placeholder: "۰۹۱۲ *** ****",
                                        className:
                                          "w-full bg-[#F7F8F6] border border-[#DDE2DD] rounded-xl px-4 py-3 text-[#25272C] placeholder:text-[#545B64] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 focus:border-[#28722C] transition-all",
                                        dir: "ltr",
                                      }),
                                    ],
                                  }),
                                ],
                              }),
                              e.jsxs("div", {
                                children: [
                                  e.jsx("label", {
                                    className: "block text-sm font-bold text-[#25272C] mb-2",
                                    children: "موضوع",
                                  }),
                                  e.jsxs("select", {
                                    value: l.subject,
                                    onChange: (t) => n({ ...l, subject: t.target.value }),
                                    className:
                                      "w-full bg-[#F7F8F6] border border-[#DDE2DD] rounded-xl px-4 py-3 text-[#25272C] focus:outline-none focus:border-[#28722C] transition-all",
                                    children: [
                                      e.jsx("option", { value: "", children: "انتخاب موضوع" }),
                                      e.jsx("option", {
                                        children: "رینوپلاستی (جراحی بینی اولیه)",
                                      }),
                                      e.jsx("option", { children: "جراحی بینی ترمیمی" }),
                                      e.jsx("option", { children: "رفع قوز بینی" }),
                                      e.jsx("option", { children: "انحراف بینی (سپتوپلاستی)" }),
                                      e.jsx("option", { children: "سایر" }),
                                    ],
                                  }),
                                ],
                              }),
                              e.jsxs("div", {
                                children: [
                                  e.jsxs("label", {
                                    className: "block text-sm font-bold text-[#25272C] mb-2",
                                    children: [
                                      "پیام شما ",
                                      e.jsx("span", { className: "text-red-500", children: "*" }),
                                    ],
                                  }),
                                  e.jsx("textarea", {
                                    required: !0,
                                    value: l.message,
                                    onChange: (t) => n({ ...l, message: t.target.value }),
                                    placeholder: "سؤال یا درخواست خود را بنویسید...",
                                    rows: 5,
                                    className:
                                      "w-full bg-[#F7F8F6] border border-[#DDE2DD] rounded-xl px-4 py-3 text-[#25272C] placeholder:text-[#545B64] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 focus:border-[#28722C] transition-all resize-none",
                                  }),
                                ],
                              }),
                              e.jsxs("button", {
                                type: "submit",
                                className:
                                  "w-full bg-[#28722C] hover:bg-[#246b28] text-white font-bold py-4 rounded-2xl transition-all flex items-center justify-center gap-2",
                                children: [e.jsx(qe, { size: 16 }), " ارسال پیام"],
                              }),
                              e.jsxs("p", {
                                className:
                                  "text-xs text-[#545B64] text-center flex items-center justify-center gap-1",
                                children: [e.jsx(U, { size: 11 }), "اطلاعات شما محرمانه و امن است"],
                              }),
                            ],
                          }),
                        ],
                      }),
                }),
                e.jsx("div", {
                  className: "rounded-3xl overflow-hidden border border-[#DDE2DD] shadow-lg h-64",
                  children: e.jsx("iframe", {
                    src: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3237.671371427505!2d51.4138246!3d35.758881300000006!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzXCsDQ1JzMyLjAiTiA1McKwMjQnNTAuMiJF!5e0!3m2!1sfa!2sir!4v1698000000000!5m2!1sfa!2sir",
                    width: "100%",
                    height: "100%",
                    style: { border: 0 },
                    allowFullScreen: !0,
                    loading: "lazy",
                    referrerPolicy: "no-referrer-when-downgrade",
                    title: "محل کلینیک دکتر باستانی‌نژاد",
                  }),
                }),
              ],
            }),
          ],
        }),
      }),
    ],
  });
}
const ot = [...L.map((s) => s.title), ...M.map((s) => s.title), "مشاوره عمومی"],
  ct = { name: "", phone: "", age: "", procedure: "", previousSurgeryMonths: "", eligibilityConfirmed: !1, message: "" };
function dt() {
  const [s, r] = h.useState("form"),
    [l, n] = h.useState(ct),
    [t, i] = h.useState({}),
    [a, c] = h.useState(!1);
  function p(o, g) {
    (n((w) => ({ ...w, [o]: g })), i((w) => ({ ...w, [o]: "" })));
  }
  function z() {
    const o = {};
    return (
      l.name.trim() || (o.name = "لطفاً نام خود را وارد کنید"),
      l.phone.trim()
        ? /^\+?\d{7,15}$/.test(l.phone.trim()) ||
          (o.phone = "فرمت شماره اشتباه است (مثال: 09121234567)")
        : (o.phone = "لطفاً شماره تلفن را وارد کنید"),
      l.procedure || (o.procedure = "لطفاً نوع خدمت را انتخاب کنید"),
      Number(String(l.age).replace(/[۰-۹]/g, (g) => "۰۱۲۳۴۵۶۷۸۹".indexOf(g))) >= 18 && Number(String(l.age).replace(/[۰-۹]/g, (g) => "۰۱۲۳۴۵۶۷۸۹".indexOf(g))) <= 45 || (o.age = "پذیرش فقط برای بازه سنی ۱۸ تا ۴۵ سال انجام می‌شود"),
      l.procedure.includes("ترمیمی") && Number(String(l.previousSurgeryMonths).replace(/[۰-۹]/g, (g) => "۰۱۲۳۴۵۶۷۸۹".indexOf(g))) < 24 && (o.previousSurgeryMonths = "باید ۲۴ ماه کامل از جراحی قبلی گذشته باشد"),
      l.eligibilityConfirmed || (o.eligibilityConfirmed = "تأیید شرایط پذیرش الزامی است"),
      i(o),
      Object.keys(o).length === 0
    );
  }
  async function k(o) {
    o.preventDefault();
    if (!z()) return;
    c(!0);
    try {
      const g = await fetch(window.__DRB_FORMS_API__.appointment, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ...l, website: "" }),
        }),
        w = await g.json();
      if (!g.ok) throw new Error(w.message || "ثبت انجام نشد");
      r("done");
    } catch (g) {
      alert(g.message || "ثبت انجام نشد");
    } finally {
      c(!1);
    }
  }
  const d = [{ label: "خانه", href: "/" }, { label: "رزرو نوبت" }];
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, { id: "jsonld-booking", data: E(d) }),
      e.jsx(v, { items: d }),
      e.jsx("section", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-16 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto text-center",
          children: [
            e.jsx("span", {
              className: "text-[#28722C] font-bold text-sm tracking-wider",
              children: "رزرو نوبت آنلاین",
            }),
            e.jsx("h1", {
              className: "text-4xl sm:text-5xl font-black mt-2 mb-4",
              children: "رزرو نوبت",
            }),
            e.jsx("p", {
              className: "text-white/70 text-lg max-w-2xl mx-auto",
              children:
                "با تکمیل فرم آنلاین، درخواست شما زودتر بررسی می‌شود و در اولویت نوبت‌دهی قرار می‌گیرید. همکاران ما پس از ثبت، برای هماهنگی زمان تماس می‌گیرند.",
            }),
          ],
        }),
      }),
      e.jsx("div", {
        className: "max-w-[1200px] mx-auto px-4 sm:px-6 py-16",
        children: e.jsxs("div", {
          className: "grid lg:grid-cols-3 gap-8",
          children: [
            e.jsxs("aside", {
              className: "space-y-5",
              children: [
                e.jsxs("div", {
                  className: "bg-white rounded-2xl border border-[#DDE2DD] p-6",
                  children: [
                    e.jsx("h3", {
                      className: "font-bold text-[#25272C] mb-4",
                      children: "مراحل رزرو نوبت",
                    }),
                    e.jsx("div", {
                      className: "space-y-4",
                      children: [
                        {
                          step: "۱",
                          title: "پر کردن فرم",
                          desc: "نام، شماره تماس و موضوع درخواست را وارد کنید.",
                        },
                        {
                          step: "۲",
                          title: "ثبت درخواست",
                          desc: "درخواست شما در اولویت نوبت‌دهی قرار می‌گیرد.",
                        },
                        {
                          step: "۳",
                          title: "تماس تیم کلینیک",
                          desc: "همکاران ما برای هماهنگی زمان با شما تماس می‌گیرند.",
                        },
                      ].map((o) =>
                        e.jsxs(
                          "div",
                          {
                            className: "flex gap-3",
                            children: [
                              e.jsx("div", {
                                className:
                                  "w-8 h-8 bg-[#E4F0E4] rounded-xl flex items-center justify-center flex-shrink-0",
                                children: e.jsx("span", {
                                  className: "text-[#28722C] font-black text-sm",
                                  children: o.step,
                                }),
                              }),
                              e.jsxs("div", {
                                children: [
                                  e.jsx("p", {
                                    className: "font-semibold text-[#25272C] text-sm",
                                    children: o.title,
                                  }),
                                  e.jsx("p", {
                                    className: "text-xs text-[#545B64]",
                                    children: o.desc,
                                  }),
                                ],
                              }),
                            ],
                          },
                          o.step,
                        ),
                      ),
                    }),
                  ],
                }),
                e.jsxs("div", {
                  className: "bg-white rounded-2xl border border-[#DDE2DD] p-6",
                  children: [
                    e.jsxs("h3", {
                      className: "font-bold text-[#25272C] mb-4 flex items-center gap-2",
                      children: [
                        e.jsx(F, { size: 16, className: "text-[#28722C]" }),
                        " ساعات پذیرش",
                      ],
                    }),
                    e.jsxs("div", {
                      className: "bg-[#E4F0E4] rounded-xl px-4 py-3",
                      children: [
                        e.jsx("p", {
                          className: "text-sm font-bold text-[#25272C]",
                          children: "شنبه تا سه‌شنبه",
                        }),
                        e.jsx("p", {
                          className: "text-sm text-[#28722C] font-semibold",
                          children: "۱۵:۰۰ – ۱۸:۰۰",
                        }),
                      ],
                    }),
                  ],
                }),
                e.jsxs("div", {
                  className: "bg-white rounded-2xl border border-[#DDE2DD] p-6",
                  children: [
                    e.jsxs("h3", {
                      className: "font-bold text-[#25272C] mb-4 flex items-center gap-2",
                      children: [
                        e.jsx(u, { size: 16, className: "text-[#28722C]" }),
                        " تماس مستقیم",
                      ],
                    }),
                    T.map((o) =>
                      e.jsxs(
                        "a",
                        {
                          href: `tel:${o.replace(/[^0-9]/g, "")}`,
                          className:
                            "flex items-center gap-3 p-2 rounded-lg hover:bg-[#E4F0E4] transition-colors mb-1",
                          children: [
                            e.jsx(u, { size: 14, className: "text-[#28722C]" }),
                            e.jsx("span", {
                              className: "font-bold text-[#25272C] text-sm",
                              dir: "ltr",
                              children: o,
                            }),
                          ],
                        },
                        o,
                      ),
                    ),
                  ],
                }),
                e.jsxs("div", {
                  className: "bg-[#E4F0E4] rounded-2xl p-5",
                  children: [
                    e.jsx("p", {
                      className: "text-sm font-bold text-[#28722C] mb-3",
                      children: "مزیت رزرو آنلاین",
                    }),
                    [
                      "رزرو ۲۴ ساعته بدون معطلی",
                      "اولویت در نوبت‌دهی",
                      "تماس تیم کلینیک برای هماهنگی",
                      "محرمانه و امن",
                    ].map((o) =>
                      e.jsxs(
                        "div",
                        {
                          className: "flex items-center gap-2 text-sm text-[#25272C] mb-2",
                          children: [
                            e.jsx(D, { size: 13, className: "text-[#28722C] flex-shrink-0" }),
                            o,
                          ],
                        },
                        o,
                      ),
                    ),
                  ],
                }),
              ],
            }),
            e.jsx("div", {
              className: "lg:col-span-2",
              children: e.jsxs("div", {
                className: "bg-white rounded-3xl border border-[#DDE2DD] overflow-hidden shadow-lg",
                children: [
                  e.jsxs("div", {
                    className:
                      "bg-gradient-to-br from-[#28722C] to-[#1a4e1d] p-6 text-white text-center",
                    children: [
                      e.jsx(N, { size: 32, className: "mx-auto mb-3" }),
                      e.jsx("h2", {
                        className: "text-xl font-black mb-1",
                        children: "فرم رزرو نوبت",
                      }),
                      e.jsx("p", {
                        className: "text-white/80 text-sm",
                        children: "موارد ستاره‌دار الزامی هستند",
                      }),
                    ],
                  }),
                  e.jsxs("div", {
                    className: "p-8",
                    children: [
                      s === "form" &&
                        e.jsxs("form", {
                          onSubmit: k,
                          noValidate: !0,
                          className: "space-y-5",
                          children: [
                            e.jsxs("div", {
                              children: [
                                e.jsxs("label", {
                                  className: "block text-sm font-bold text-[#25272C] mb-1.5",
                                  children: [
                                    e.jsx(Qe, {
                                      size: 13,
                                      className: "inline ml-1 text-[#28722C]",
                                    }),
                                    "نام و نام‌خانوادگی ",
                                    e.jsx("span", { className: "text-red-500", children: "*" }),
                                  ],
                                }),
                                e.jsx("input", {
                                  type: "text",
                                  value: l.name,
                                  onChange: (o) => p("name", o.target.value),
                                  placeholder: "مثال: علی محمدی",
                                  className: `w-full px-4 py-3 rounded-xl border text-sm bg-[#F7F8F6] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all ${t.name ? "border-red-400" : "border-[#DDE2DD]"}`,
                                }),
                                t.name &&
                                  e.jsx("p", {
                                    className: "text-xs text-red-500 mt-1",
                                    children: t.name,
                                  }),
                              ],
                            }),
                            e.jsxs("div", {
                              children: [
                                e.jsxs("label", {
                                  className: "block text-sm font-bold text-[#25272C] mb-1.5",
                                  children: ["سن ", e.jsx("span", { className: "text-red-500", children: "*" })],
                                }),
                                e.jsx("input", {
                                  type: "number", min: 18, max: 45, value: l.age,
                                  onChange: (o) => p("age", o.target.value),
                                  placeholder: "۱۸ تا ۴۵ سال",
                                  className: `w-full px-4 py-3 rounded-xl border text-sm bg-[#F7F8F6] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 ${t.age ? "border-red-400" : "border-[#DDE2DD]"}`,
                                }),
                                t.age && e.jsx("p", { className: "text-xs text-red-500 mt-1", children: t.age }),
                              ],
                            }),
                            e.jsxs("div", {
                              children: [
                                e.jsxs("label", {
                                  className: "block text-sm font-bold text-[#25272C] mb-1.5",
                                  children: [
                                    e.jsx(Z, { size: 13, className: "inline ml-1 text-[#28722C]" }),
                                    "شماره موبایل ",
                                    e.jsx("span", { className: "text-red-500", children: "*" }),
                                  ],
                                }),
                                e.jsx("input", {
                                  type: "tel",
                                  value: l.phone,
                                  onChange: (o) => p("phone", o.target.value),
                                  placeholder: "09123456789",
                                  dir: "ltr",
                                  className: `w-full px-4 py-3 rounded-xl border text-sm bg-[#F7F8F6] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all ${t.phone ? "border-red-400" : "border-[#DDE2DD]"}`,
                                }),
                                t.phone &&
                                  e.jsx("p", {
                                    className: "text-xs text-red-500 mt-1",
                                    children: t.phone,
                                  }),
                              ],
                            }),
                            e.jsxs("div", {
                              children: [
                                e.jsxs("label", {
                                  className: "block text-sm font-bold text-[#25272C] mb-1.5",
                                  children: [
                                    e.jsx(N, { size: 13, className: "inline ml-1 text-[#28722C]" }),
                                    "نوع خدمت مورد نظر ",
                                    e.jsx("span", { className: "text-red-500", children: "*" }),
                                  ],
                                }),
                                e.jsxs("select", {
                                  value: l.procedure,
                                  onChange: (o) => p("procedure", o.target.value),
                                  className: `w-full px-4 py-3 rounded-xl border text-sm bg-[#F7F8F6] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all ${t.procedure ? "border-red-400" : "border-[#DDE2DD]"}`,
                                  children: [
                                    e.jsx("option", { value: "", children: "-- انتخاب کنید --" }),
                                    ot.map((o) => e.jsx("option", { value: o, children: o }, o)),
                                  ],
                                }),
                                t.procedure &&
                                  e.jsx("p", {
                                    className: "text-xs text-red-500 mt-1",
                                    children: t.procedure,
                                  }),
                              ],
                            }),
                            l.procedure.includes("ترمیمی") && e.jsxs("div", {
                              children: [
                                e.jsxs("label", {
                                  className: "block text-sm font-bold text-[#25272C] mb-1.5",
                                  children: ["چند ماه از جراحی قبلی گذشته است؟ ", e.jsx("span", { className: "text-red-500", children: "*" })],
                                }),
                                e.jsx("input", {
                                  type: "number", min: 24, value: l.previousSurgeryMonths,
                                  onChange: (o) => p("previousSurgeryMonths", o.target.value),
                                  placeholder: "حداقل ۲۴ ماه کامل",
                                  className: `w-full px-4 py-3 rounded-xl border text-sm bg-[#F7F8F6] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 ${t.previousSurgeryMonths ? "border-red-400" : "border-[#DDE2DD]"}`,
                                }),
                                t.previousSurgeryMonths && e.jsx("p", { className: "text-xs text-red-500 mt-1", children: t.previousSurgeryMonths }),
                              ],
                            }),
                            e.jsxs("div", {
                              children: [
                                e.jsxs("label", {
                                  className: "block text-sm font-bold text-[#25272C] mb-1.5",
                                  children: [
                                    e.jsx(He, {
                                      size: 13,
                                      className: "inline ml-1 text-[#28722C]",
                                    }),
                                    "توضیحات تکمیلی ",
                                    e.jsx("span", {
                                      className: "text-[#9CA3AF] font-normal text-xs",
                                      children: "(اختیاری)",
                                    }),
                                  ],
                                }),
                                e.jsx("textarea", {
                                  value: l.message,
                                  onChange: (o) => p("message", o.target.value),
                                  rows: 4,
                                  placeholder:
                                    "اگر سؤال یا توضیح بیشتری درباره وضعیت بینی‌تان دارید، همین‌جا بنویسید...",
                                  className:
                                    "w-full px-4 py-3 rounded-xl border border-[#DDE2DD] text-sm bg-[#F7F8F6] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all resize-none",
                                }),
                              ],
                            }),
                            e.jsxs("div", {
                              className: `rounded-xl border p-4 ${t.eligibilityConfirmed ? "border-red-400 bg-red-50" : "border-[#DDE2DD] bg-[#F7F8F6]"}`,
                              children: [
                                e.jsxs("label", { className: "flex items-start gap-3 text-sm text-[#25272C] cursor-pointer", children: [
                                  e.jsx("input", { type: "checkbox", checked: l.eligibilityConfirmed, onChange: (o) => p("eligibilityConfirmed", o.target.checked), className: "mt-1" }),
                                  e.jsx("span", { children: "تأیید می‌کنم ۱۸ تا ۴۵ سال دارم، دیابت یا فشار خون و بیماری زمینه‌ای/خودایمنی کنترل‌نشده ندارم، سنترال لب انجام نداده‌ام و می‌دانم جراحی ترمیمی فقط پس از ۲۴ ماه کامل بررسی می‌شود." }),
                                ] }),
                                t.eligibilityConfirmed && e.jsx("p", { className: "text-xs text-red-500 mt-2", children: t.eligibilityConfirmed }),
                              ],
                            }),
                            e.jsx("button", {
                              type: "submit",
                              disabled: a,
                              className:
                                "w-full py-4 bg-[#28722C] text-white font-black rounded-xl hover:bg-[#246b28] disabled:opacity-60 transition-colors flex items-center justify-center gap-2",
                              children: a
                                ? "در حال ثبت..."
                                : e.jsxs(e.Fragment, {
                                    children: [e.jsx(Z, { size: 16 }), "ثبت درخواست نوبت"],
                                  }),
                            }),
                            e.jsxs("div", {
                              className:
                                "flex items-center justify-center gap-1.5 text-xs text-[#545B64]",
                              children: [
                                e.jsx(U, { size: 11, className: "text-[#28722C]" }),
                                "اطلاعات شما کاملاً محرمانه است",
                              ],
                            }),
                          ],
                        }),
                      s === "done" &&
                        e.jsxs("div", {
                          className: "text-center py-8",
                          children: [
                            e.jsx("div", {
                              className:
                                "w-20 h-20 bg-[#E4F0E4] rounded-3xl flex items-center justify-center mx-auto mb-6",
                              children: e.jsx(D, { size: 40, className: "text-[#28722C]" }),
                            }),
                            e.jsx("h3", {
                              className: "text-2xl font-black text-[#25272C] mb-3",
                              children: "درخواست شما ثبت شد ✓",
                            }),
                            e.jsxs("p", {
                              className: "text-[#545B64] text-sm leading-relaxed mb-6",
                              children: [
                                "تیم کلینیک دکتر باستانی‌نژاد ظرف ",
                                e.jsx("strong", {
                                  className: "text-[#28722C]",
                                  children: "۲۴ ساعت",
                                }),
                                " با شماره",
                                " ",
                                e.jsx("span", {
                                  dir: "ltr",
                                  className: "font-bold text-[#25272C]",
                                  children: l.phone,
                                }),
                                " با شما تماس می‌گیرد.",
                              ],
                            }),
                            e.jsxs("div", {
                              className: "flex flex-col sm:flex-row gap-3 justify-center",
                              children: [
                                e.jsx(m, {
                                  to: "/",
                                  className:
                                    "inline-flex items-center justify-center gap-2 px-6 py-3 bg-[#28722C] text-white font-bold rounded-xl hover:bg-[#246b28] transition-colors",
                                  children: "بازگشت به صفحه اصلی",
                                }),
                                e.jsx(m, {
                                  to: "/services",
                                  className:
                                    "inline-flex items-center justify-center gap-2 px-6 py-3 border border-[#DDE2DD] text-[#25272C] font-bold rounded-xl hover:border-[#28722C]/30 transition-colors",
                                  children: "مشاهده خدمات",
                                }),
                              ],
                            }),
                          ],
                        }),
                    ],
                  }),
                ],
              }),
            }),
          ],
        }),
      }),
    ],
  });
}
function xt() {
  const [s, r] = h.useState(""),
    l = [...L, ...M],
    n = l.slice(0, 4),
    t = f.filter((a) => a.featured || a.id <= 3).slice(0, 3),
    i =
      s.trim().length > 1
        ? [
            ...l
              .filter((a) => a.title.includes(s) || a.desc.includes(s))
              .map((a) => ({ label: a.title, href: `/services/${a.slug}`, type: "خدمات" })),
            ...f
              .filter((a) => a.title.includes(s) || a.excerpt.includes(s))
              .map((a) => ({ label: a.title, href: `/blog/${a.slug}`, type: "مقاله" })),
          ].slice(0, 6)
        : [];
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, {
        data: E([{ label: "خانه", href: "/" }, { label: "صفحه پیدا نشد" }]),
        id: "404-schema",
      }),
      e.jsx(v, { items: [{ label: "خانه", href: "/" }, { label: "۴۰۴" }] }),
      e.jsx("section", {
        className:
          "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-20 px-4 sm:px-6 text-center",
        children: e.jsxs("div", {
          className: "max-w-xl mx-auto",
          children: [
            e.jsx("div", {
              className: "text-8xl sm:text-9xl font-black leading-none mb-4 select-none",
              style: { color: "rgba(40,114,44,0.25)", textShadow: "0 0 0 #28722C" },
              "aria-hidden": !0,
              children: "۴۰۴",
            }),
            e.jsx("h1", {
              className: "text-2xl sm:text-3xl font-bold mb-3",
              children: "صفحه پیدا نشد",
            }),
            e.jsx("p", {
              className: "text-white/70 text-sm sm:text-base leading-relaxed mb-8",
              children:
                "صفحه‌ای که دنبالش می‌گردید وجود ندارد یا آدرس آن تغییر کرده است. می‌توانید از جستجوی زیر یا لینک‌های پرطرفدار استفاده کنید.",
            }),
            e.jsxs("div", {
              className: "relative max-w-md mx-auto",
              children: [
                e.jsx(pe, {
                  size: 16,
                  className: "absolute right-4 top-1/2 -translate-y-1/2 text-white/40",
                  "aria-hidden": !0,
                }),
                e.jsx("input", {
                  type: "search",
                  value: s,
                  onChange: (a) => r(a.target.value),
                  placeholder: "جستجو در خدمات و مقالات...",
                  className:
                    "w-full pr-10 pl-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder:text-white/40 text-sm focus:outline-none focus:border-[#28722C] focus:ring-2 focus:ring-[#28722C]/30 transition-all",
                  "aria-label": "جستجو",
                }),
              ],
            }),
            i.length > 0 &&
              e.jsx("div", {
                className: "mt-3 bg-white rounded-xl overflow-hidden max-w-md mx-auto text-right",
                children: i.map((a, c) =>
                  e.jsxs(
                    m,
                    {
                      to: a.href,
                      className:
                        "flex items-center justify-between px-4 py-3 hover:bg-[#E4F0E4] transition-colors border-b border-[#DDE2DD] last:border-0",
                      children: [
                        e.jsx("span", { className: "text-sm text-[#25272C]", children: a.label }),
                        e.jsx("span", {
                          className: "text-xs text-[#28722C] bg-[#E4F0E4] px-2 py-0.5 rounded-full",
                          children: a.type,
                        }),
                      ],
                    },
                    c,
                  ),
                ),
              }),
          ],
        }),
      }),
      e.jsxs("section", {
        className: "max-w-[1200px] mx-auto px-4 sm:px-6 py-12",
        children: [
          e.jsx("h2", {
            className: "text-xl font-bold text-[#25272C] mb-6",
            children: "خدمات پرطرفدار",
          }),
          e.jsx("div", {
            className: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10",
            children: n.map((a) =>
              e.jsxs(
                m,
                {
                  to: `/services/${a.slug}`,
                  className:
                    "bg-white rounded-2xl border border-[#DDE2DD] p-5 hover:border-[#28722C]/30 hover:shadow-md transition-all group",
                  children: [
                    e.jsx("div", {
                      className:
                        "w-10 h-10 rounded-xl bg-[#E4F0E4] flex items-center justify-center mb-3 group-hover:bg-[#28722C] transition-colors",
                      children: e.jsx(a.icon, {
                        size: 18,
                        className: "text-[#28722C] group-hover:text-white transition-colors",
                      }),
                    }),
                    e.jsx("h3", {
                      className:
                        "font-semibold text-[#25272C] text-sm mb-1 group-hover:text-[#28722C] transition-colors",
                      children: a.title,
                    }),
                    e.jsx("p", {
                      className: "text-xs text-[#545B64] line-clamp-2",
                      children: a.subtitle,
                    }),
                  ],
                },
                a.id,
              ),
            ),
          }),
          e.jsx("h2", {
            className: "text-xl font-bold text-[#25272C] mb-6",
            children: "مقالات محبوب",
          }),
          e.jsx("div", {
            className: "grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10",
            children: t.map((a) =>
              e.jsxs(
                m,
                {
                  to: `/blog/${a.slug}`,
                  className:
                    "bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden group hover:shadow-md transition-shadow",
                  children: [
                    e.jsx("div", {
                      className: "h-36 bg-[#DDE2DD] overflow-hidden",
                      children: e.jsx("img", {
                        src: a.img,
                        alt: a.title,
                        className:
                          "w-full h-full object-cover group-hover:scale-105 transition-transform duration-500",
                        loading: "lazy",
                      }),
                    }),
                    e.jsxs("div", {
                      className: "p-4",
                      children: [
                        e.jsx("span", {
                          className:
                            "text-xs font-semibold text-[#28722C] bg-[#E4F0E4] px-2 py-0.5 rounded-full mb-2 inline-block",
                          children: a.cat,
                        }),
                        e.jsx("h3", {
                          className:
                            "font-semibold text-[#25272C] text-sm leading-relaxed group-hover:text-[#28722C] transition-colors",
                          children: a.title,
                        }),
                      ],
                    }),
                  ],
                },
                a.id,
              ),
            ),
          }),
          e.jsxs("div", {
            className:
              "bg-gradient-to-br from-[#28722C] to-[#1a4e1d] rounded-2xl p-8 text-center text-white",
            children: [
              e.jsx("h2", {
                className: "text-xl font-bold mb-2",
                children: "نیاز به مشاوره دارید؟",
              }),
              e.jsx("p", {
                className: "text-white/80 text-sm mb-6",
                children: "تیم ما آماده پاسخگویی است. همین حالا تماس بگیرید.",
              }),
              e.jsxs("div", {
                className: "flex flex-col sm:flex-row items-center justify-center gap-3",
                children: [
                  e.jsxs(m, {
                    to: "/",
                    className:
                      "flex items-center gap-2 px-5 py-3 bg-white text-[#28722C] rounded-xl font-bold text-sm hover:bg-[#E4F0E4] transition-colors",
                    children: [e.jsx(be, { size: 15 }), "بازگشت به خانه"],
                  }),
                  e.jsxs("a", {
                    href: "tel:02186087250",
                    className:
                      "flex items-center gap-2 px-5 py-3 bg-white/20 border border-white/30 text-white rounded-xl font-bold text-sm hover:bg-white/30 transition-colors",
                    children: [e.jsx(u, { size: 15 }), "۰۲۱۸۶۰۸۷۲۵۰"],
                  }),
                  e.jsxs("a", {
                    href: de,
                    rel: "noopener",
                    className:
                      "flex items-center gap-2 px-5 py-3 bg-white/20 border border-white/30 text-white rounded-xl font-bold text-sm hover:bg-white/30 transition-colors",
                    children: ["رزرو نوبت آنلاین ", e.jsx(j, { size: 14 })],
                  }),
                ],
              }),
            ],
          }),
        ],
      }),
    ],
  });
}
const mt = {
  "جراحی-بینی": {
    label: "جراحی بینی",
    description: "مقالات تخصصی درباره رینوپلاستی و انواع جراحی بینی",
  },
  "راهنمای-بیمار": {
    label: "راهنمای بیمار",
    description: "اطلاعات کاربردی برای بیماران قبل و بعد از عمل",
  },
  مراقبت‌ها: { label: "مراقبت‌ها", description: "نکات مراقبتی و ریکاوری پس از جراحی بینی" },
  آموزشی: { label: "آموزشی", description: "مقالات علمی و آموزشی درباره جراحی پلاستیک بینی" },
};
function ht() {
  const { slug: s = "" } = ae(),
    r = mt[s] || { label: s || "دسته‌بندی", description: "مقالات این دسته‌بندی" },
    l = f.filter((n) => n.cat.replace(/\s/g, "-") === s || n.cat === s);
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, {
        data: E([
          { label: "خانه", href: "/" },
          { label: "وبلاگ", href: "/blog" },
          { label: r.label },
        ]),
        id: "cat-schema",
      }),
      e.jsx(v, {
        items: [
          { label: "خانه", href: "/" },
          { label: "وبلاگ", href: "/blog" },
          { label: r.label },
        ],
      }),
      e.jsx("header", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-14 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto",
          children: [
            e.jsxs("span", {
              className:
                "inline-flex items-center gap-1.5 text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30 mb-4",
              children: [e.jsx(Ue, { size: 12 }), "دسته‌بندی"],
            }),
            e.jsx("h1", { className: "text-3xl sm:text-4xl font-bold mb-3", children: r.label }),
            e.jsx("p", {
              className: "text-white/70 text-base leading-relaxed max-w-xl",
              children: r.description,
            }),
            e.jsxs("p", {
              className: "text-white/50 text-sm mt-3",
              children: [l.length, " مقاله"],
            }),
          ],
        }),
      }),
      e.jsxs("section", {
        className: "max-w-[1200px] mx-auto px-4 sm:px-6 py-12",
        children: [
          l.length === 0
            ? e.jsxs("div", {
                className: "text-center py-16",
                children: [
                  e.jsx("p", {
                    className: "text-[#545B64] mb-4",
                    children: "هیچ مقاله‌ای در این دسته‌بندی یافت نشد.",
                  }),
                  e.jsxs(m, {
                    to: "/blog",
                    className:
                      "inline-flex items-center gap-1.5 px-4 py-2 bg-[#28722C] text-white rounded-xl text-sm font-bold hover:bg-[#246b28] transition-colors",
                    children: ["همه مقالات ", e.jsx(j, { size: 13 })],
                  }),
                ],
              })
            : e.jsx("div", {
                className: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6",
                children: l.map((n) =>
                  e.jsxs(
                    "article",
                    {
                      className:
                        "bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden group hover:shadow-md transition-shadow",
                      children: [
                        e.jsx("div", {
                          className: "h-48 bg-[#DDE2DD] overflow-hidden",
                          children: e.jsx("img", {
                            src: n.img,
                            alt: n.title,
                            className:
                              "w-full h-full object-cover group-hover:scale-105 transition-transform duration-500",
                            loading: "lazy",
                          }),
                        }),
                        e.jsxs("div", {
                          className: "p-5",
                          children: [
                            e.jsx("div", {
                              className: "flex items-center gap-2 mb-3",
                              children: e.jsx("span", {
                                className:
                                  "text-xs font-semibold text-[#28722C] bg-[#E4F0E4] px-2 py-0.5 rounded-full",
                                children: n.cat,
                              }),
                            }),
                            e.jsx("h2", {
                              className:
                                "font-bold text-[#25272C] leading-relaxed mb-2 group-hover:text-[#28722C] transition-colors",
                              children: n.title,
                            }),
                            e.jsx("p", {
                              className: "text-sm text-[#545B64] line-clamp-2 mb-4",
                              children: n.excerpt,
                            }),
                            e.jsxs("div", {
                              className: "flex items-center justify-between text-xs text-[#545B64]",
                              children: [
                                e.jsx("div", {
                                  className: "flex items-center gap-3",
                                  children: e.jsxs("span", {
                                    className: "flex items-center gap-1",
                                    children: [e.jsx(N, { size: 11 }), n.date],
                                  }),
                                }),
                                e.jsxs(m, {
                                  to: `/blog/${n.slug}`,
                                  className:
                                    "flex items-center gap-1 text-[#28722C] font-semibold hover:gap-2 transition-all",
                                  children: ["بیشتر ", e.jsx(j, { size: 12 })],
                                }),
                              ],
                            }),
                          ],
                        }),
                      ],
                    },
                    n.id,
                  ),
                ),
              }),
          l.length > 0 &&
            e.jsx("div", {
              className: "flex justify-center gap-2 mt-12",
              children: e.jsx("span", {
                className:
                  "px-4 py-2 bg-[#28722C] text-white rounded-lg text-sm font-bold cursor-default",
                "aria-current": "page",
                children: "۱",
              }),
            }),
        ],
      }),
    ],
  });
}
const ee = [
  { slug: "رینوپلاستی", label: "رینوپلاستی", count: 8 },
  { slug: "جراحی-بینی-ترمیمی", label: "جراحی بینی ترمیمی", count: 4 },
  { slug: "بینی-استخوانی", label: "بینی استخوانی", count: 3 },
  { slug: "سپتوپلاستی", label: "سپتوپلاستی", count: 2 },
  { slug: "مراقبت-بعد-از-عمل", label: "مراقبت بعد از عمل", count: 5 },
  { slug: "ریکاوری", label: "ریکاوری", count: 4 },
  { slug: "جراحی-بینی-مردانه", label: "جراحی بینی مردانه", count: 2 },
  { slug: "آندوسکوپی-سینوس", label: "آندوسکوپی سینوس", count: 2 },
  { slug: "قوز-بینی", label: "قوز بینی", count: 3 },
  { slug: "هزینه-جراحی-بینی", label: "هزینه جراحی بینی", count: 2 },
  { slug: "دکتر-باستانی‌نژاد", label: "دکتر باستانی‌نژاد", count: 11 },
];
function se() {
  const { slug: s = "" } = ae(),
    [r, l] = h.useState(s || ""),
    n = ee.find((i) => i.slug === r) || { label: r || "تگ" },
    t = r
      ? f.filter(
          (i) =>
            i.title.includes(n.label.split("-")[0]) || i.excerpt.includes(n.label.split("-")[0]),
        )
      : f;
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, {
        data: E([
          { label: "خانه", href: "/" },
          { label: "برچسب‌ها", href: "/tag" },
          ...(r ? [{ label: n.label }] : []),
        ]),
        id: "tag-schema",
      }),
      e.jsx(v, {
        items: [
          { label: "خانه", href: "/" },
          { label: "وبلاگ", href: "/blog" },
          { label: r ? `#${n.label}` : "همه برچسب‌ها" },
        ],
      }),
      e.jsx("header", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-14 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto",
          children: [
            e.jsxs("div", {
              className:
                "inline-flex items-center gap-1.5 text-xs font-bold text-[#28722C] bg-[#28722C]/20 px-3 py-1 rounded-full border border-[#28722C]/30 mb-4",
              children: [e.jsx(Ve, { size: 12 }), "برچسب‌ها"],
            }),
            e.jsx("h1", {
              className: "text-3xl sm:text-4xl font-bold mb-3",
              children: r ? `#${n.label}` : "مرکز برچسب‌ها",
            }),
            e.jsx("p", {
              className: "text-white/70 text-base max-w-xl",
              children: r
                ? `${t.length} مقاله با برچسب "${n.label}"`
                : "همه برچسب‌های سایت و مقالات مرتبط",
            }),
          ],
        }),
      }),
      e.jsxs("div", {
        className: "max-w-[1200px] mx-auto px-4 sm:px-6 py-10",
        children: [
          e.jsxs("section", {
            "aria-labelledby": "tagcloud-heading",
            className: "mb-10",
            children: [
              e.jsx("h2", {
                id: "tagcloud-heading",
                className: "text-lg font-bold text-[#25272C] mb-4",
                children: "همه برچسب‌ها",
              }),
              e.jsxs("div", {
                className: "archive__tag-cloud",
                children: [
                  e.jsx("button", {
                    onClick: () => l(""),
                    className: `archive__tag ${r ? "" : "active"}`,
                    children: "همه",
                  }),
                  ee.map((i) =>
                    e.jsxs(
                      "button",
                      {
                        onClick: () => l(i.slug),
                        className: `inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors cursor-pointer ${r === i.slug ? "bg-[#28722C] text-white border-[#28722C]" : "bg-[#E4F0E4] text-[#28722C] border-[#28722C]/20 hover:bg-[#28722C] hover:text-white"}`,
                        "aria-pressed": r === i.slug,
                        children: [
                          "#",
                          i.label,
                          e.jsxs("span", {
                            className: "text-[10px] opacity-60 mr-1",
                            children: ["(", i.count, ")"],
                          }),
                        ],
                      },
                      i.slug,
                    ),
                  ),
                ],
              }),
            ],
          }),
          e.jsxs("section", {
            "aria-labelledby": "tagposts-heading",
            children: [
              e.jsxs("h2", {
                id: "tagposts-heading",
                className: "text-lg font-bold text-[#25272C] mb-5",
                children: [
                  r ? `مقالات با برچسب #${n.label}` : "همه مقالات",
                  e.jsxs("span", {
                    className: "text-sm font-normal text-[#545B64] mr-2",
                    children: ["(", t.length, ")"],
                  }),
                ],
              }),
              t.length === 0
                ? e.jsx("p", {
                    className: "text-[#545B64] py-8 text-center",
                    children: "مقاله‌ای یافت نشد.",
                  })
                : e.jsx("div", {
                    className: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5",
                    children: t.map((i) =>
                      e.jsxs(
                        "article",
                        {
                          className:
                            "bg-white rounded-2xl border border-[#DDE2DD] overflow-hidden group hover:shadow-md transition-shadow",
                          children: [
                            e.jsx("div", {
                              className: "h-44 bg-[#DDE2DD] overflow-hidden",
                              children: e.jsx("img", {
                                src: i.img,
                                alt: i.title,
                                className:
                                  "w-full h-full object-cover group-hover:scale-105 transition-transform duration-500",
                                loading: "lazy",
                              }),
                            }),
                            e.jsxs("div", {
                              className: "p-4",
                              children: [
                                e.jsx("span", {
                                  className:
                                    "inline-block text-xs font-semibold text-[#28722C] bg-[#E4F0E4] px-2 py-0.5 rounded-full mb-2",
                                  children: i.cat,
                                }),
                                e.jsx("h3", {
                                  className:
                                    "font-bold text-[#25272C] text-sm leading-relaxed mb-2 group-hover:text-[#28722C] transition-colors",
                                  children: i.title,
                                }),
                                e.jsxs("div", {
                                  className:
                                    "flex items-center justify-between text-xs text-[#545B64]",
                                  children: [
                                    e.jsxs("span", {
                                      className: "flex items-center gap-1",
                                      children: [e.jsx(N, { size: 11 }), i.date],
                                    }),
                                    e.jsxs(m, {
                                      to: `/blog/${i.slug}`,
                                      className:
                                        "flex items-center gap-1 text-[#28722C] font-semibold hover:gap-2 transition-all",
                                      children: ["بیشتر ", e.jsx(j, { size: 12 })],
                                    }),
                                  ],
                                }),
                              ],
                            }),
                          ],
                        },
                        i.id,
                      ),
                    ),
                  }),
            ],
          }),
        ],
      }),
    ],
  });
}
const te = [
  {
    title: "صفحات اصلی",
    icon: e.jsx(be, { size: 16 }),
    color: "text-[#28722C]",
    items: [
      { label: "خانه", href: "/", desc: "صفحه اصلی کلینیک" },
      { label: "درباره دکتر", href: "/about", desc: "سوابق و اعتبارنامه‌های دکتر باستانی‌نژاد" },
      { label: "گالری", href: "/gallery", desc: "گالری قبل و بعد از جراحی" },
      { label: "سوالات متداول", href: "/faq", desc: "پرسش‌های رایج بیماران" },
      { label: "تماس با ما", href: "/contact", desc: "اطلاعات تماس و فرم درخواست مشاوره" },
      { label: "رزرو نوبت", href: "/booking", desc: "رزرو مشاوره آنلاین" },
      { label: "نقشه سایت", href: "/sitemap", desc: "این صفحه" },
    ],
  },
  {
    title: "خدمات زیبایی",
    icon: e.jsx(he, { size: 16 }),
    color: "text-[#B8860B]",
    items: [
      { label: "همه خدمات", href: "/services", desc: "فهرست کامل خدمات" },
      {
        label: "جراحی بینی — نمای کلی",
        href: "/services/rhinoplasty",
        desc: "معرفی انواع رینوپلاستی",
      },
      ...L.map((s) => ({ label: s.title, href: `/services/${s.slug}`, desc: s.subtitle })),
    ],
  },
  {
    title: "خدمات درمانی",
    icon: e.jsx(U, { size: 16 }),
    color: "text-[#1e6fce]",
    items: M.map((s) => ({ label: s.title, href: `/services/${s.slug}`, desc: s.subtitle })),
  },
  {
    title: "وبلاگ",
    icon: e.jsx(Ye, { size: 16 }),
    color: "text-[#9333ea]",
    items: [
      { label: "همه مقالات", href: "/blog", desc: "آرشیو کامل مقالات" },
      ...f.map((s) => ({ label: s.title, href: `/blog/${s.slug}`, desc: s.cat })),
    ],
  },
  {
    title: "صفحات قانونی",
    icon: e.jsx(Ke, { size: 16 }),
    color: "text-[#545B64]",
    items: [
      {
        label: "سیاست حریم خصوصی",
        href: "/legal/privacy",
        desc: "نحوه جمع‌آوری و استفاده از اطلاعات",
      },
      { label: "شرایط استفاده", href: "/legal/terms", desc: "قوانین و مقررات استفاده از سایت" },
      { label: "سیاست لغو", href: "/legal/cancellation", desc: "شرایط لغو و کنسلی وقت" },
    ],
  },
];
function pt() {
  const [s, r] = h.useState(""),
    l = te
      .map((t) => ({
        ...t,
        items: s
          ? t.items.filter((i) => i.label.includes(s) || (i.desc && i.desc.includes(s)))
          : t.items,
      }))
      .filter((t) => t.items.length > 0),
    n = te.reduce((t, i) => t + i.items.length, 0);
  return e.jsxs(e.Fragment, {
    children: [
      e.jsx(C, {
        data: E([{ label: "خانه", href: "/" }, { label: "نقشه سایت" }]),
        id: "sitemap-schema",
      }),
      e.jsx(v, { items: [{ label: "خانه", href: "/" }, { label: "نقشه سایت" }] }),
      e.jsx("header", {
        className: "bg-gradient-to-br from-[#25272C] to-[#1a2318] text-white py-14 px-4 sm:px-6",
        children: e.jsxs("div", {
          className: "max-w-[1200px] mx-auto",
          children: [
            e.jsx("h1", {
              className: "text-3xl sm:text-4xl font-bold mb-3",
              children: "نقشه سایت",
            }),
            e.jsxs("p", {
              className: "text-white/70 mb-6",
              children: [n, " صفحه در سایت کلینیک دکتر باستانی‌نژاد"],
            }),
            e.jsxs("div", {
              className: "relative max-w-sm",
              children: [
                e.jsx(pe, {
                  size: 15,
                  className: "absolute right-3 top-1/2 -translate-y-1/2 text-white/40",
                  "aria-hidden": !0,
                }),
                e.jsx("input", {
                  type: "search",
                  value: s,
                  onChange: (t) => r(t.target.value),
                  placeholder: "جستجو در نقشه سایت...",
                  className:
                    "w-full pr-9 pl-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder:text-white/40 text-sm focus:outline-none focus:border-[#28722C] transition-all",
                  "aria-label": "جستجو در نقشه سایت",
                }),
                s &&
                  e.jsx("button", {
                    onClick: () => r(""),
                    className:
                      "absolute left-3 top-1/2 -translate-y-1/2 text-white/40 hover:text-white",
                    "aria-label": "پاک کردن جستجو",
                    children: e.jsx(Xe, { size: 15 }),
                  }),
              ],
            }),
          ],
        }),
      }),
      e.jsxs("div", {
        className: "max-w-[1200px] mx-auto px-4 sm:px-6 py-10 space-y-6",
        children: [
          l.map((t) =>
            e.jsxs(
              "div",
              {
                className: "bg-white rounded-2xl border border-[#DDE2DD] p-5",
                children: [
                  e.jsxs("h2", {
                    className: `flex items-center gap-2 text-base font-bold mb-4 pb-3 border-b border-[#DDE2DD] ${t.color}`,
                    children: [
                      t.icon,
                      t.title,
                      e.jsxs("span", {
                        className: "text-xs font-normal text-[#545B64] mr-auto",
                        children: [t.items.length, " مورد"],
                      }),
                    ],
                  }),
                  e.jsx("div", {
                    className: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1",
                    children: t.items.map((i) =>
                      e.jsxs(
                        m,
                        {
                          to: i.href,
                          className:
                            "flex items-start gap-2 px-3 py-2 rounded-lg text-sm text-[#545B64] hover:text-[#28722C] hover:bg-[#E4F0E4] transition-colors",
                          children: [
                            e.jsx(Ze, {
                              size: 12,
                              "aria-hidden": !0,
                              className: "flex-shrink-0 mt-0.5",
                            }),
                            e.jsxs("span", {
                              children: [
                                e.jsx("span", {
                                  className:
                                    "block text-[#25272C] font-medium text-sm leading-tight",
                                  children: i.label,
                                }),
                                i.desc &&
                                  e.jsx("span", {
                                    className: "text-[11px] text-[#545B64]",
                                    children: i.desc,
                                  }),
                              ],
                            }),
                          ],
                        },
                        i.href,
                      ),
                    ),
                  }),
                ],
              },
              t.title,
            ),
          ),
          l.length === 0 &&
            e.jsx("div", {
              className: "text-center py-16",
              children: e.jsx("p", {
                className: "text-[#545B64]",
                children: "نتیجه‌ای یافت نشد. جستجوی دیگری امتحان کنید.",
              }),
            }),
        ],
      }),
    ],
  });
}
function bt() {
  return e.jsx(ye, {
    children: e.jsx(Ce, {
      children: e.jsxs(x, {
        element: e.jsx(As, {}),
        children: [
          e.jsx(x, { index: !0, element: e.jsx(Js, {}) }),
          e.jsx(x, { path: "about", element: e.jsx(tt, {}) }),
          e.jsx(x, { path: "gallery", element: e.jsx(lt, {}) }),
          e.jsx(x, { path: "faq", element: e.jsx(it, {}) }),
          e.jsx(x, { path: "contact", element: e.jsx(nt, {}) }),
          e.jsx(x, { path: "booking", element: e.jsx(dt, {}) }),
          e.jsx(x, { path: "sitemap", element: e.jsx(pt, {}) }),
          e.jsx(x, { path: "services", element: e.jsx(at, {}) }),
          e.jsx(x, { path: "services/rhinoplasty", element: e.jsx(ds, {}) }),
          e.jsx(x, { path: "services/rhinoplasty-primary", element: e.jsx(xs, {}) }),
          e.jsx(x, { path: "services/rhinoplasty-revision", element: e.jsx(ms, {}) }),
          e.jsx(x, { path: "services/rhinoplasty-bony", element: e.jsx(ps, {}) }),
          e.jsx(x, { path: "services/rhinoplasty-natural", element: e.jsx(bs, {}) }),
          e.jsx(x, { path: "services/hump-removal", element: e.jsx(us, {}) }),
          e.jsx(x, { path: "services/septoplasty", element: e.jsx(js, {}) }),
          e.jsx(x, { path: "services/turbinoplasty", element: e.jsx(fs, {}) }),
          e.jsx(x, { path: "services/sinus-endoscopy", element: e.jsx(Ns, {}) }),
          e.jsx(x, { path: "blog", element: e.jsx(rt, {}) }),
          e.jsx(x, { path: "blog/rhinoplasty", element: e.jsx(Je, {}) }),
          e.jsx(x, { path: "blog/rhinoplasty-revision", element: e.jsx(es, {}) }),
          e.jsx(x, { path: "blog/pre-op-steps", element: e.jsx(ss, {}) }),
          e.jsx(x, { path: "blog/post-op-care", element: e.jsx(ts, {}) }),
          e.jsx(x, { path: "blog/choose-surgeon", element: e.jsx(ls, {}) }),
          e.jsx(x, { path: "blog/revision-rhinoplasty", element: e.jsx(rs, {}) }),
          e.jsx(x, { path: "blog/rhinoplasty-complications", element: e.jsx(is, {}) }),
          e.jsx(x, { path: "blog/male-rhinoplasty", element: e.jsx(ns, {}) }),
          e.jsx(x, { path: "blog/nutrition-rhinoplasty", element: e.jsx(os, {}) }),
          e.jsx(x, { path: "blog/atl-removal", element: e.jsx(cs, {}) }),
          e.jsx(x, { path: "category/:slug", element: e.jsx(ht, {}) }),
          e.jsx(x, { path: "tag", element: e.jsx(se, {}) }),
          e.jsx(x, { path: "tag/:slug", element: e.jsx(se, {}) }),
          e.jsx(x, { path: "legal/privacy", element: e.jsx(Ae, {}) }),
          e.jsx(x, { path: "legal/terms", element: e.jsx(Re, {}) }),
          e.jsx(x, { path: "legal/cancellation", element: e.jsx(_e, {}) }),
          e.jsx(x, { path: "*", element: e.jsx(xt, {}) }),
        ],
      }),
    }),
  });
}
fe.createRoot(document.getElementById("root")).render(
  e.jsx(Ne.StrictMode, { children: e.jsx(bt, {}) }),
);
