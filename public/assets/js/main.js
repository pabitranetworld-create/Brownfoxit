/* =========================================================
   Brown Fox IT – Home page scripts
   - Particles (floating coloured dots, like the theme's "Row Particles")
   - Scroll reveal, text fill headings, counters, progress bars
   - Sliders (Swiper), sticky header, mobile menu, video popup
   ========================================================= */
(function () {
  "use strict";

  /* ---------- 1. Particles ---------- */
  function Particles(section) {
    var canvas = section.querySelector("canvas.particles");
    if (!canvas) return;
    var ctx = canvas.getContext("2d");
    var colors = JSON.parse(section.dataset.colors || '["#ffffff"]');
    var base = parseInt(section.dataset.number || "5", 10);
    var size = parseFloat(section.dataset.size || "4");
    var randomSize = section.dataset.random === "true";
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var dots = [], w = 0, h = 0, running = false;

    function resize() {
      w = section.offsetWidth; h = section.offsetHeight;
      canvas.width = w * dpr; canvas.height = h * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      // particles.js counts density per 800×800 area; keep a similar feel
      var count = Math.max(base, Math.round(base * (w * h) / (800 * 800)));
      dots = [];
      for (var i = 0; i < count; i++) dots.push(make(true));
    }
    function make(anywhere) {
      var r = randomSize ? Math.random() * size + 1.5 : size;
      return {
        x: anywhere ? Math.random() * w : w + 20 * Math.random(),
        y: anywhere ? Math.random() * h : -20 * Math.random(),
        r: r,
        c: colors[Math.floor(Math.random() * colors.length)],
        // direction: bottom-left
        vx: -(0.25 + Math.random() * 0.6),
        vy: 0.25 + Math.random() * 0.6,
        a: 0.6 + Math.random() * 0.4,
        t: Math.random() * Math.PI * 2
      };
    }
    function draw() {
      if (!running) return;
      ctx.clearRect(0, 0, w, h);
      for (var i = 0; i < dots.length; i++) {
        var d = dots[i];
        d.t += 0.02;
        d.x += d.vx + Math.sin(d.t) * 0.15;
        d.y += d.vy;
        if (d.x < -20 || d.y > h + 20) {
          var n = make(false);
          // re-enter from the top or the right edge
          if (Math.random() > 0.5) { n.x = Math.random() * w; n.y = -10; } else { n.x = w + 10; n.y = Math.random() * h; }
          dots[i] = d = n;
        }
        ctx.beginPath();
        ctx.globalAlpha = d.a;
        ctx.fillStyle = d.c;
        ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.globalAlpha = 1;
      requestAnimationFrame(draw);
    }
    resize();
    window.addEventListener("resize", resize);
    // only animate while the section is on screen
    new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting && !running) { running = true; draw(); }
        else if (!e.isIntersecting) running = false;
      });
    }).observe(section);
  }
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (!reduce) document.querySelectorAll(".has-particles").forEach(Particles);

  /* ---------- 3. Sliders ---------- */
  if (window.Swiper) {
    new Swiper(".sbox-swiper", {
      rewind: true, speed: 700, slidesPerView: 1, autoplay: { delay: 4000 },
      navigation: { prevEl: ".sbox-prev", nextEl: ".sbox-next" },
      breakpoints: { 768: { slidesPerView: 2 }, 992: { slidesPerView: 3 } }
    });
    new Swiper(".client-swiper", {
      loop: true, speed: 4000, slidesPerView: 2, spaceBetween: 30, allowTouchMove: true,
      autoplay: { delay: 0, disableOnInteraction: false },
      navigation: { prevEl: ".client-prev", nextEl: ".client-next" },
      breakpoints: { 576: { slidesPerView: 3 }, 992: { slidesPerView: 4 } }
    });
    var cur = document.getElementById("projCurrent");
    new Swiper(".proj-swiper", {
      loop: true, speed: 800, slidesPerView: "auto", spaceBetween: 24, autoplay: { delay: 3500 },
      navigation: { prevEl: ".proj-prev", nextEl: ".proj-next" },
      on: { slideChange: function (s) { if (cur) cur.textContent = String(s.realIndex + 1).padStart(2, "0"); } }
    });
    new Swiper(".testi-swiper", {
      loop: true, speed: 700, effect: "fade", fadeEffect: { crossFade: true }, autoplay: { delay: 5000 },
      navigation: { prevEl: ".testi-prev", nextEl: ".testi-next" }
    });
    new Swiper(".blog-swiper", {
      loop: true, speed: 700, slidesPerView: 1, spaceBetween: 24, autoplay: { delay: 4500 },
      navigation: { prevEl: ".blog-prev", nextEl: ".blog-next" },
      breakpoints: { 640: { slidesPerView: 2 }, 992: { slidesPerView: 3 } }
    });
  }

  /* ---------- 4. Scroll reveal / counters / progress ---------- */
  function countUp(el) {
    var to = parseFloat(el.dataset.to), start = null, dur = 1800;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var el = e.target;
      if (el.classList.contains("reveal")) {
        setTimeout(function () { el.classList.add("in"); }, parseInt(el.dataset.delay || 0, 10));
      }
      if (el.classList.contains("count")) countUp(el);
      if (el.classList.contains("progress-item")) {
        var v = el.dataset.value;
        el.querySelector(".bar").style.width = v + "%";
        var pv = el.querySelector(".pval"), n = 0;
        var t = setInterval(function () { n++; pv.textContent = n + "%"; if (n >= v) clearInterval(t); }, 1800 / v);
      }
      io.unobserve(el);
    });
  }, { threshold: 0.15 });
  document.querySelectorAll(".reveal, .count, .progress-item").forEach(function (el) { io.observe(el); });

  /* ---------- 5. Text fill headings + header + back to top ---------- */
  var fills = document.querySelectorAll(".text-fill");
  var header = document.getElementById("siteHeader");
  var backTop = document.getElementById("backTop");
  var prog = document.getElementById("backTopProg");
  function onScroll() {
    var vh = window.innerHeight, y = window.scrollY;
    fills.forEach(function (el) {
      var r = el.getBoundingClientRect();
      var p = (vh * 0.9 - r.top) / (vh * 0.5);
      el.style.setProperty("--fill", Math.max(0, Math.min(1, p)) * 100 + "%");
    });
    if (header) header.classList.toggle("sticky", y > 300);
    if (backTop) backTop.classList.toggle("show", y > 400);
    var max = document.documentElement.scrollHeight - vh;
    if (prog) prog.style.strokeDashoffset = 125.6 - (125.6 * y) / (max || 1);
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();
  if (backTop) backTop.addEventListener("click", function () { window.scrollTo({ top: 0, behavior: "smooth" }); });

  /* ---------- 6. Mobile menu ---------- */
  var toggle = document.getElementById("menuToggle"), nav = document.getElementById("mainNav");
  if (toggle && nav) toggle.addEventListener("click", function () { toggle.classList.toggle("open"); nav.classList.toggle("open"); });
  if (nav) nav.querySelectorAll("a").forEach(function (a) {
    a.addEventListener("click", function (ev) {
      var li = a.parentElement;
      if (li.classList.contains("has-sub") && window.innerWidth < 992) { ev.preventDefault(); li.classList.toggle("open"); return; }
      toggle.classList.remove("open"); nav.classList.remove("open");
    });
  });

  /* ---------- 7. Video popup ---------- */
  var modal = document.getElementById("videoModal"), frame = document.getElementById("videoFrame");
  document.querySelectorAll("[data-video]").forEach(function (b) {
    b.addEventListener("click", function (ev) {
      ev.preventDefault();
      if (!modal) return;
      frame.innerHTML = '<iframe src="https://www.youtube.com/embed/' + b.dataset.video + '?autoplay=1" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
      modal.classList.add("open");
    });
  });
  function closeVideo() { if (!modal) return; modal.classList.remove("open"); frame.innerHTML = ""; }
  if (modal) {
    document.getElementById("videoClose").addEventListener("click", closeVideo);
    modal.addEventListener("click", function (e) { if (e.target === modal) closeVideo(); });
  }
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") closeVideo(); });

})();
