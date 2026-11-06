/* Brown Fox IT – home page tech touches: pause off-screen animation, gentle depth on hero icons. */
(function () {
  "use strict";
  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { e.target.classList.toggle("vb-off", !e.isIntersecting); });
    }, { rootMargin: "120px" });
    document.querySelectorAll("body.page-home main > section, body.page-home section").forEach(function (s) { io.observe(s); });
  }
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches || !window.matchMedia("(hover: hover) and (pointer: fine)").matches) return;
  var hero = document.querySelector(".hero"), chips = document.querySelectorAll(".hh-chip, .hh-ring");
  if (!hero || !chips.length) return;
  var raf = 0;
  hero.addEventListener("pointermove", function (e) {
    var r = hero.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
    cancelAnimationFrame(raf);
    raf = requestAnimationFrame(function () {
      chips.forEach(function (c, i) { var k = c.classList.contains("hh-ring") ? 10 : 22 + (i % 3) * 8; c.style.translate = (x * -k).toFixed(1) + "px " + (y * -k).toFixed(1) + "px"; });
    });
  });
  hero.addEventListener("pointerleave", function () { chips.forEach(function (c) { c.style.translate = ""; }); });
})();
