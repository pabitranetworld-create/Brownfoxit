/* =========================================================
   Brown Fox IT – motion system for inner pages
   Works with the attributes written by App\PageBuilder\Attr:
     data-anim, data-anim-dur, data-anim-delay, data-anim-int,
     data-anim-mobile, data-anim-reduced, data-stagger, data-parallax,
     data-words, data-inview, data-reveal, data-tilt, .hv-tilt
   plus interactive widgets: FAQ, tabs, lightbox, video popup,
   project filter, testimonial slider, scroll progress.
   ========================================================= */
(function () {
  "use strict";
  window.__motionReady = true;

  var root = document.documentElement;
  var reduced = root.classList.contains("reduced");
  var mobile = window.matchMedia("(max-width: 767px)").matches;
  var editor = root.hasAttribute("data-editor");

  /* ---------- words: split headings into animated words ---------- */
  document.querySelectorAll("[data-words]").forEach(function (el) {
    if (el.dataset.split) return;
    el.dataset.split = "1";
    var i = 0;
    (function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (child) {
        if (child.nodeType === 3) {
          var frag = document.createDocumentFragment();
          child.textContent.split(/(\s+)/).forEach(function (part) {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(part)); return; }
            var s = document.createElement("span");
            s.className = "w"; s.style.setProperty("--wi", i++); s.textContent = part;
            frag.appendChild(s);
          });
          child.parentNode.replaceChild(frag, child);
        } else if (child.nodeType === 1) {
          walk(child);
        }
      });
    })(el);
  });

  /* ---------- prepare entrance animations ---------- */
  var anims = Array.prototype.slice.call(document.querySelectorAll("[data-anim]"));
  if (mobile) {
    anims.forEach(function (el) {
      var m = el.getAttribute("data-anim-mobile");
      if (m === "off") el.classList.add("in");
      if (m === "simple") el.setAttribute("data-anim", "fade");
    });
  }
  if (reduced) {
    // Only elements asked to "fade" animate; the rest are simply visible.
    anims.forEach(function (el) { if (el.getAttribute("data-anim-reduced") !== "fade") el.classList.add("in"); });
  }

  function reveal(el) {
    if (el.hasAttribute("data-anim") && !el.classList.contains("in")) {
      var dur = parseInt(el.getAttribute("data-anim-dur") || "800", 10);
      var delay = parseInt(el.getAttribute("data-anim-delay") || "0", 10);
      el.style.transitionDuration = (reduced ? 600 : dur) + "ms";
      el.style.transitionDelay = (reduced ? 0 : delay) + "ms";
      el.classList.add("in");
      // clean up so hover transforms are not slowed down later
      setTimeout(function () { el.style.transitionDuration = ""; el.style.transitionDelay = ""; }, dur + delay + 100);
    }
    if (el.hasAttribute("data-stagger")) {
      var step = reduced ? 0 : parseInt(el.getAttribute("data-stagger") || "90", 10);
      var base = parseInt(el.getAttribute("data-anim-delay") || "0", 10);
      el.querySelectorAll(".pb-item").forEach(function (item, i) {
        item.style.transitionDelay = (base + i * step) + "ms";
        item.classList.add("st-in");
        setTimeout(function () { item.style.transitionDelay = ""; }, base + i * step + 900);
      });
    }
    if (el.hasAttribute("data-words") || el.hasAttribute("data-inview") || el.hasAttribute("data-reveal")) el.classList.add("in");
    el.querySelectorAll("[data-words]:not(.in), [data-inview]:not(.in), [data-reveal]:not(.in)").forEach(function (c) {
      if (!c.closest("[data-anim]:not(.in)") || c.closest("[data-anim]") === el) c.classList.add("in");
    });
  }

  var watched = document.querySelectorAll("[data-anim], [data-stagger], [data-words], [data-inview], [data-reveal]");
  if ("IntersectionObserver" in window && !editor) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { reveal(e.target); io.unobserve(e.target); }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0.12 });
    watched.forEach(function (el) { io.observe(el); });
  } else {
    // No observer (or inside the admin preview): show everything right away.
    watched.forEach(reveal);
  }

  /* ---------- parallax ---------- */
  var par = Array.prototype.slice.call(document.querySelectorAll("[data-parallax]"));
  if (par.length && !reduced) {
    var factor = { subtle: 0.04, medium: 0.09, strong: 0.16 };
    var ticking = false;
    var update = function () {
      var vh = window.innerHeight;
      par.forEach(function (el) {
        var r = el.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) return;
        var f = factor[el.getAttribute("data-parallax")] || 0.05;
        if (mobile) f /= 2;
        var off = (r.top + r.height / 2 - vh / 2) * -f;
        el.style.translate = "0 " + off.toFixed(1) + "px";
      });
      ticking = false;
    };
    window.addEventListener("scroll", function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    update();
  }

  /* ---------- 3D tilt ---------- */
  if (!reduced && window.matchMedia("(hover: hover)").matches) {
    document.querySelectorAll("[data-tilt], .hv-tilt").forEach(function (el) {
      el.addEventListener("mousemove", function (e) {
        var r = el.getBoundingClientRect();
        var x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
        el.style.transform = "perspective(900px) rotateY(" + (x * 10).toFixed(2) + "deg) rotateX(" + (-y * 10).toFixed(2) + "deg)";
      });
      el.addEventListener("mouseleave", function () { el.style.transform = ""; });
    });
  }

  /* ---------- scroll progress bar ---------- */
  if (document.body.classList.contains("page-inner")) {
    var bar = document.createElement("div");
    bar.className = "pb-progress"; bar.setAttribute("aria-hidden", "true");
    document.body.appendChild(bar);
    var prog = function () {
      var max = document.documentElement.scrollHeight - window.innerHeight;
      bar.style.transform = "scaleX(" + (max > 0 ? Math.min(1, window.scrollY / max) : 0) + ")";
    };
    window.addEventListener("scroll", prog, { passive: true });
    prog();
  }

  /* ---------- FAQ accordion ---------- */
  document.querySelectorAll("[data-accordion]").forEach(function (acc) {
    acc.addEventListener("click", function (e) {
      var q = e.target.closest(".pb-faq-q");
      if (!q || !acc.contains(q)) return;
      var item = q.parentNode, open = !item.classList.contains("open");
      acc.querySelectorAll(".pb-faq-item.open").forEach(function (o) {
        if (o !== item) { o.classList.remove("open"); o.querySelector(".pb-faq-q").setAttribute("aria-expanded", "false"); o.querySelector(".pb-faq-a").hidden = true; }
      });
      item.classList.toggle("open", open);
      q.setAttribute("aria-expanded", open ? "true" : "false");
      item.querySelector(".pb-faq-a").hidden = !open;
    });
  });

  /* ---------- tabs ---------- */
  document.querySelectorAll("[data-tabs]").forEach(function (tabs) {
    var list = tabs.querySelector(".pb-tab-list"), ink = tabs.querySelector(".pb-tab-ink");
    var btns = Array.prototype.slice.call(tabs.querySelectorAll(".pb-tab"));
    var panels = Array.prototype.slice.call(tabs.querySelectorAll(".pb-tab-panel"));
    function moveInk(b) {
      if (!ink || !b) return;
      ink.style.left = b.offsetLeft + "px"; ink.style.width = b.offsetWidth + "px";
      ink.style.top = b.offsetTop + "px"; ink.style.height = b.offsetHeight + "px";
    }
    function select(i, focus) {
      btns.forEach(function (b, j) { b.classList.toggle("on", i === j); b.setAttribute("aria-selected", i === j ? "true" : "false"); b.tabIndex = i === j ? 0 : -1; });
      panels.forEach(function (p, j) { p.hidden = i !== j; });
      moveInk(btns[i]);
      if (focus) btns[i].focus();
    }
    btns.forEach(function (b, i) {
      b.addEventListener("click", function () { select(i); });
      b.addEventListener("keydown", function (e) {
        if (e.key === "ArrowRight" || e.key === "ArrowDown") { e.preventDefault(); select((i + 1) % btns.length, true); }
        if (e.key === "ArrowLeft" || e.key === "ArrowUp") { e.preventDefault(); select((i - 1 + btns.length) % btns.length, true); }
      });
    });
    select(0);
    window.addEventListener("resize", function () { moveInk(list.querySelector(".pb-tab.on")); });
  });

  /* ---------- modal helper ---------- */
  function modal(cls, html, onKey) {
    var m = document.createElement("div");
    m.className = cls; m.setAttribute("role", "dialog"); m.setAttribute("aria-modal", "true");
    m.innerHTML = html;
    document.body.appendChild(m);
    document.body.style.overflow = "hidden";
    requestAnimationFrame(function () { m.classList.add("open"); });
    function close() {
      m.classList.remove("open"); document.body.style.overflow = "";
      document.removeEventListener("keydown", key);
      setTimeout(function () { m.remove(); }, 300);
    }
    function key(e) { if (e.key === "Escape") close(); else if (onKey) onKey(e); }
    document.addEventListener("keydown", key);
    m.addEventListener("click", function (e) { if (e.target === m || e.target.closest(".close, .lb-close")) close(); });
    var c = m.querySelector(".close, .lb-close"); if (c) c.focus();
    return m;
  }

  /* ---------- gallery lightbox ---------- */
  document.querySelectorAll("[data-lightbox]").forEach(function (gal) {
    var links = Array.prototype.slice.call(gal.querySelectorAll("a[href]"));
    links.forEach(function (a, i) {
      a.addEventListener("click", function (e) {
        e.preventDefault();
        var cur = i, m;
        function show() {
          m.querySelector("img").src = links[cur].href;
          m.querySelector("img").alt = links[cur].getAttribute("data-caption") || "";
          m.querySelector("p").textContent = links[cur].getAttribute("data-caption") || "";
        }
        m = modal("pb-lightbox", '<img alt=""><p></p><button class="lb-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
          (links.length > 1 ? '<button class="lb-prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button><button class="lb-next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>' : ""),
          function (ev) { if (ev.key === "ArrowRight") { cur = (cur + 1) % links.length; show(); } if (ev.key === "ArrowLeft") { cur = (cur - 1 + links.length) % links.length; show(); } });
        m.addEventListener("click", function (ev) {
          if (ev.target.closest(".lb-next")) { cur = (cur + 1) % links.length; show(); }
          if (ev.target.closest(".lb-prev")) { cur = (cur - 1 + links.length) % links.length; show(); }
        });
        show();
      });
    });
  });

  /* ---------- video popup ---------- */
  document.addEventListener("click", function (e) {
    var b = e.target.closest("[data-video-popup]");
    if (!b) return;
    var src = b.getAttribute("data-video-popup");
    var inner = b.getAttribute("data-video-type") === "video"
      ? '<video src="' + src.replace(/"/g, "") + '" controls autoplay playsinline></video>'
      : '<iframe src="' + src.replace(/"/g, "") + '" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
    modal("pb-vmodal", '<button class="close" aria-label="Close">&times;</button><div class="box">' + inner + "</div>");
  });

  /* ---------- portfolio filter ---------- */
  document.querySelectorAll("[data-filter-group]").forEach(function (group) {
    var items = Array.prototype.slice.call(group.querySelectorAll(".pb-project"));
    group.querySelectorAll("[data-filter]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var f = btn.getAttribute("data-filter");
        group.querySelectorAll("[data-filter]").forEach(function (b) { b.classList.toggle("on", b === btn); });
        items.forEach(function (it) {
          var show = f === "*" || (" " + it.getAttribute("data-cats") + " ").indexOf(" " + f + " ") > -1;
          if (show) {
            it.classList.remove("gone");
            requestAnimationFrame(function () { requestAnimationFrame(function () { it.classList.remove("out"); }); });
          } else {
            it.classList.add("out");
            setTimeout(function () { if (it.classList.contains("out")) it.classList.add("gone"); }, reduced ? 0 : 450);
          }
        });
      });
    });
  });

  /* ---------- testimonial slider ---------- */
  if (window.Swiper) {
    document.querySelectorAll(".pb-swiper").forEach(function (el) {
      new window.Swiper(el, {
        slidesPerView: 1, spaceBetween: 24, speed: reduced ? 0 : 700,
        autoplay: reduced ? false : { delay: 5000, pauseOnMouseEnter: true },
        pagination: { el: el.querySelector(".swiper-pagination"), clickable: true },
        breakpoints: { 768: { slidesPerView: 2 }, 1100: { slidesPerView: 3 } }
      });
    });
  }
})();
