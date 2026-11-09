/* Brown Fox IT – home page tech touches: pause off-screen animation, gentle depth on hero icons. */
(function () {
  "use strict";
  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { e.target.classList.toggle("vb-off", !e.isIntersecting); });
    }, { rootMargin: "120px" });
    document.querySelectorAll("body.page-home main > section, body.page-home section").forEach(function (s) { io.observe(s); });
  }
})();
