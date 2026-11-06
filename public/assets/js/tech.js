/* Brown Fox IT – technology animations: neural network + code rain canvases, typing / rotating /
   scrambling text. Nothing runs for visitors who ask for reduced motion; canvases sleep off screen. */
(function () {
  "use strict";
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
  var COLORS = ["#22d3ee", "#a855f7", "#ec4899", "#ff6a3d", "#a3e635", "#facc15"];

  function visible(el, cb) {
    if (!("IntersectionObserver" in window)) { cb(true); return; }
    new IntersectionObserver(function (e) { cb(e[0].isIntersecting); }, { rootMargin: "80px" }).observe(el);
  }
  function fit(c) {
    var r = c.getBoundingClientRect(), d = Math.min(window.devicePixelRatio || 1, 2);
    c.width = Math.max(1, r.width * d); c.height = Math.max(1, r.height * d);
    var x = c.getContext("2d"); x.setTransform(d, 0, 0, d, 0, 0);
    return { x: x, w: r.width, h: r.height };
  }

  /* ---- neural network: layered nodes, colourful signals travelling along the links ---- */
  document.querySelectorAll("[data-neural]").forEach(function (c) {
    var g, nodes = [], links = [], sig = [], on = false, raf = 0;
    function build() {
      g = fit(c); nodes = []; links = [];
      var layers = [4, 6, 6, 4], cx = g.w / 2, cy = g.h / 2;
      layers.forEach(function (n, li) {
        for (var i = 0; i < n; i++) {
          var x = g.w * (0.1 + li * 0.8 / (layers.length - 1)), y = g.h * (0.14 + (i + 0.5) * 0.72 / n);
          if (Math.abs(x - cx) < 70 && Math.abs(y - cy) < 70) y += (y < cy ? -60 : 60);
          nodes.push({ x: x, y: y, l: li, c: COLORS[(li * 2 + i) % COLORS.length], p: Math.random() * 6 });
        }
      });
      nodes.forEach(function (a) { nodes.forEach(function (b) { if (b.l === a.l + 1) links.push([a, b]); }); });
    }
    function frame(t) {
      if (!on) return;
      var x = g.x; x.clearRect(0, 0, g.w, g.h);
      x.lineWidth = 1;
      links.forEach(function (k) { x.strokeStyle = "rgba(140,160,255,.13)"; x.beginPath(); x.moveTo(k[0].x, k[0].y); x.lineTo(k[1].x, k[1].y); x.stroke(); });
      if (Math.random() < 0.35 && sig.length < 40) { var k = links[(Math.random() * links.length) | 0]; sig.push({ k: k, p: 0, s: 0.008 + Math.random() * 0.014, c: k[0].c }); }
      sig = sig.filter(function (s) {
        s.p += s.s; if (s.p >= 1) return false;
        var a = s.k[0], b = s.k[1], px = a.x + (b.x - a.x) * s.p, py = a.y + (b.y - a.y) * s.p;
        var tx = a.x + (b.x - a.x) * Math.max(0, s.p - 0.12), ty = a.y + (b.y - a.y) * Math.max(0, s.p - 0.12);
        var gr = x.createLinearGradient(tx, ty, px, py); gr.addColorStop(0, "rgba(0,0,0,0)"); gr.addColorStop(1, s.c);
        x.strokeStyle = gr; x.lineWidth = 2.2; x.beginPath(); x.moveTo(tx, ty); x.lineTo(px, py); x.stroke();
        x.fillStyle = s.c; x.shadowColor = s.c; x.shadowBlur = 12; x.beginPath(); x.arc(px, py, 2.6, 0, 7); x.fill(); x.shadowBlur = 0;
        return true;
      });
      nodes.forEach(function (n) {
        var r = 5 + Math.sin(t / 500 + n.p) * 1.5;
        x.fillStyle = "#0b0d1f"; x.strokeStyle = n.c; x.lineWidth = 2; x.shadowColor = n.c; x.shadowBlur = 14;
        x.beginPath(); x.arc(n.x, n.y, r, 0, 7); x.fill(); x.stroke(); x.shadowBlur = 0;
      });
      raf = requestAnimationFrame(frame);
    }
    build();
    window.addEventListener("resize", function () { build(); }, { passive: true });
    visible(c, function (v) { on = v; cancelAnimationFrame(raf); if (v) raf = requestAnimationFrame(frame); });
  });

  /* ---- code rain ---- */
  document.querySelectorAll("[data-matrix]").forEach(function (c) {
    var g, cols = [], on = false, last = 0, raf = 0, chars = "01<>{}[]/=+*#$&01ABCDEF";
    function build() { g = fit(c); cols = []; for (var i = 0; i < Math.ceil(g.w / 18); i++) cols.push({ y: Math.random() * -g.h, s: 1 + Math.random() * 2.5, c: COLORS[i % COLORS.length] }); }
    function frame(t) {
      if (!on) return;
      raf = requestAnimationFrame(frame);
      if (t - last < 50) return; last = t;
      var x = g.x; x.fillStyle = "rgba(5,6,15,.18)"; x.fillRect(0, 0, g.w, g.h);
      x.font = "14px ui-monospace, Menlo, monospace";
      cols.forEach(function (col, i) {
        x.fillStyle = col.c; x.fillText(chars[(Math.random() * chars.length) | 0], i * 18, col.y);
        col.y += col.s * 9; if (col.y > g.h + 40) col.y = Math.random() * -200;
      });
    }
    build(); window.addEventListener("resize", build, { passive: true });
    visible(c, function (v) { on = v; cancelAnimationFrame(raf); if (v) raf = requestAnimationFrame(frame); });
  });

  /* ---- typing / deleting a list of words, forever ---- */
  function typeLoop(el, words, opts) {
    var wi = 0, ci = words[0].length, del = true, wait = opts.hold || 1800;
    function tick() {
      var w = words[wi];
      if (del) { ci--; if (ci <= 0) { del = false; wi = (wi + 1) % words.length; } }
      else { ci++; if (ci >= words[wi].length) { del = true; el.textContent = words[wi]; setTimeout(tick, wait); return; } }
      el.textContent = words[wi].slice(0, Math.max(0, ci)) || "​";
      setTimeout(tick, del ? 45 : 85);
    }
    setTimeout(tick, wait);
  }
  document.querySelectorAll("[data-rotate]").forEach(function (el) {
    try { var words = JSON.parse(el.getAttribute("data-rotate")); if (words.length > 1) typeLoop(el, words, { hold: 2000 }); } catch (e) {}
  });
  /* eyebrow labels: re-type themselves every few seconds like a terminal */
  document.querySelectorAll(".theme-dark [data-type]").forEach(function (el) {
    var txt = el.getAttribute("data-type");
    if (txt && txt.length < 60) typeLoop(el, [txt, txt], { hold: 4200 });
  });

  /* ---- scramble: highlighted words decode from random characters, repeating ---- */
  var GLYPHS = "!<>-_\\/[]{}=+*^?#01";
  document.querySelectorAll(".theme-dark .tx-scramble").forEach(function (el) {
    var final = el.textContent, frame = 0, q = [];
    function run() {
      q = final.split("").map(function (ch) { var s = (Math.random() * 18) | 0; return { to: ch, start: s, end: s + 10 + ((Math.random() * 18) | 0) }; });
      frame = 0; step();
    }
    function step() {
      var out = "", done = 0;
      q.forEach(function (o) {
        if (frame >= o.end || o.to === " ") { done++; out += o.to; }
        else if (frame >= o.start) out += GLYPHS[(Math.random() * GLYPHS.length) | 0];
        else out += o.to;
      });
      el.textContent = out;
      if (done < q.length) { frame++; requestAnimationFrame(step); } else setTimeout(run, 4500);
    }
    setTimeout(run, 1600);
  });
})();
