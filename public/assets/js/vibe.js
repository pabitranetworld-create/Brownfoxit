/* Brown Fox IT – "landing page vibe": small interactive touches on top of motion.js.
   Pointer effects only run on devices with a real mouse; nothing runs for reduced-motion users. */
(function () {
  "use strict";
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  if (reduce) return;

  // 1. Cursor spotlight + gentle 3D tilt on cards
  if (finePointer) {
    var cards = document.querySelectorAll(".pb-section .pb-ibox, .sp-card, .vb-tilt, .pb-project, .vp-feature");
    cards.forEach(function (el) {
      var raf = 0;
      el.addEventListener("pointermove", function (e) {
        var r = el.getBoundingClientRect();
        var x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(function () {
          el.style.setProperty("--mx", (x * 100).toFixed(1) + "%");
          el.style.setProperty("--my", (y * 100).toFixed(1) + "%");
          if (!el.closest(".pb-grid.cols-1")) {
            el.style.transform = "perspective(900px) rotateX(" + ((0.5 - y) * 6).toFixed(2) + "deg) rotateY(" + ((x - 0.5) * 8).toFixed(2) + "deg) translateY(-6px)";
          }
        });
      });
      el.addEventListener("pointerleave", function () { cancelAnimationFrame(raf); el.style.transform = ""; });
    });

    // 2. Magnetic primary buttons
    document.querySelectorAll(".pb-btn-primary, .sp-btn").forEach(function (b) {
      b.addEventListener("pointermove", function (e) {
        var r = b.getBoundingClientRect();
        b.style.translate = ((e.clientX - r.left - r.width / 2) * 0.15).toFixed(1) + "px " + ((e.clientY - r.top - r.height / 2) * 0.25).toFixed(1) + "px";
      });
      b.addEventListener("pointerleave", function () { b.style.translate = ""; });
    });

    // 3. Floating shapes drift away from the mouse a little (depth effect)
    var layers = document.querySelectorAll(".vb-shapes, .tv-wrap .tv");
    if (layers.length) {
      var mx = 0, my = 0, ticking = false;
      window.addEventListener("pointermove", function (e) {
        mx = e.clientX / window.innerWidth - 0.5; my = e.clientY / window.innerHeight - 0.5;
        if (ticking) return; ticking = true;
        requestAnimationFrame(function () {
          layers.forEach(function (l, i) { l.style.translate = (mx * -26 * (1 + i % 2)).toFixed(1) + "px " + (my * -20 * (1 + i % 2)).toFixed(1) + "px"; });
          ticking = false;
        });
      }, { passive: true });
    }
  }

  // 4. Orbiting colour dots around centred heroes on white pages
  document.querySelectorAll(".bg-aurora .pb-hero.no-img").forEach(function (h) {
    var o = document.createElement("div");
    o.className = "vb-orbit"; o.setAttribute("aria-hidden", "true");
    var colors = ["#FF6A3D", "#8B3DFF", "#00C2FF", "#22C55E", "#FF2E93"];
    var r = Math.min(430, window.innerWidth * 0.46);
    for (var i = 0; i < 5; i++) {
      var dot = document.createElement("i");
      dot.style.background = colors[i];
      dot.style.setProperty("--or", (r - (i % 2) * 40) + "px");
      dot.style.setProperty("--t", (26 + i * 6) + "s");
      dot.style.setProperty("--d", (-i * 7) + "s");
      dot.style.boxShadow = "0 0 18px " + colors[i];
      if (i % 2) { dot.style.width = dot.style.height = "11px"; }
      o.appendChild(dot);
    }
    h.appendChild(o);
  });

  // 5. Pause the decorative animations of sections that are off screen (saves battery)
  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { e.target.classList.toggle("vb-off", !e.isIntersecting); });
    }, { rootMargin: "100px" });
    document.querySelectorAll(".pb-section, .vp-section").forEach(function (s) { io.observe(s); });
  }
})();

/* Sticky buy bar on product pages (runs even with reduced motion – it is navigation, not decoration). */
(function () {
  var bar = document.querySelector("[data-sticky-buy]"), box = document.querySelector(".vp-buybox");
  if (!bar || !box || !("IntersectionObserver" in window)) return;
  new IntersectionObserver(function (e) {
    var show = !e[0].isIntersecting && e[0].boundingClientRect.top < 0;
    bar.classList.toggle("show", show);
    bar.setAttribute("aria-hidden", show ? "false" : "true");
    bar.querySelectorAll("a").forEach(function (a) { a.tabIndex = show ? 0 : -1; });
  }).observe(box);
})();
