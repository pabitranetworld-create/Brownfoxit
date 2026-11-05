/* Runs inside the page-builder preview iframe: click to select, hover outlines. */
(function () {
  "use strict";
  var css = document.createElement("style");
  css.textContent =
    "[data-bfi-id]{cursor:pointer}" +
    ".bfi-hover{outline:1px dashed rgba(255,154,62,.8)!important;outline-offset:-1px}" +
    ".bfi-sel{outline:2px solid #ff5266!important;outline-offset:-2px;position:relative}" +
    ".bfi-sel::after{content:attr(data-bfi-label);position:absolute;top:0;left:0;z-index:50;background:#ff5266;color:#fff;font:700 11px/1 sans-serif;padding:4px 7px;border-radius:0 0 6px 0;pointer-events:none}" +
    ".pb-section.bfi-sel::after{left:auto;right:0;border-radius:0 0 0 6px}";
  document.head.appendChild(css);

  var hovered = null;
  function target(e) { return e.target.closest("[data-bfi-id]"); }
  function deepest(el) { return el; }

  document.addEventListener("mouseover", function (e) {
    var t = target(e);
    if (hovered && hovered !== t) hovered.classList.remove("bfi-hover");
    if (t) { t.classList.add("bfi-hover"); hovered = t; }
  });
  document.addEventListener("click", function (e) {
    var t = target(e);
    if (e.target.closest("a, button, form")) e.preventDefault();
    if (!t) return;
    e.preventDefault(); e.stopPropagation();
    parent.postMessage({ type: "bfi-select", id: deepest(t).getAttribute("data-bfi-id") }, "*");
  }, true);
  document.addEventListener("submit", function (e) { e.preventDefault(); }, true);

  window.addEventListener("message", function (e) {
    if (!e.data || e.data.type !== "bfi-highlight") return;
    document.querySelectorAll(".bfi-sel").forEach(function (x) { x.classList.remove("bfi-sel"); });
    var el = e.data.id && document.querySelector('[data-bfi-id="' + e.data.id + '"]');
    if (!el) return;
    el.classList.add("bfi-sel");
    el.setAttribute("data-bfi-label", e.data.label || "");
    if (e.data.scroll) {
      var r = el.getBoundingClientRect();
      if (r.top < 60 || r.top > window.innerHeight - 80) window.scrollTo({ top: window.scrollY + r.top - 120, behavior: "smooth" });
    }
  });

  window.addEventListener("scroll", function () { parent.postMessage({ type: "bfi-scroll", y: window.scrollY }, "*"); }, { passive: true });
  parent.postMessage({ type: "bfi-ready" }, "*");
})();
