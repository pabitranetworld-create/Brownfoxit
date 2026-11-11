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
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0 }); // threshold 0: very tall blocks (custom HTML) must still appear
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

  /* ---------- forms (contact / quote): send without reloading, show errors and success ---------- */
  document.querySelectorAll("form[data-pb-form]").forEach(function (form) {
    if (editor) return;
    var wrap = form.closest(".pb-form-wrap"), btn = form.querySelector(".pb-form-btn"), status = form.querySelector(".pb-form-status");
    function setErr(name, msg) {
      var el = form.elements[name]; if (!el) return;
      var field = (el.length && !el.tagName ? el[0] : el).closest(".pb-field"); if (!field) return;
      field.classList.toggle("has-err", !!msg);
      var s = field.querySelector(".pb-err"); if (s) s.textContent = msg || "";
    }
    function clientCheck() {
      var ok = true;
      ["name", "email", "message", "service", "consent"].forEach(function (n) {
        var el = form.elements[n]; if (!el) return;
        var bad = el.type === "checkbox" ? !el.checked : !el.value.trim();
        if (!bad && n === "email") bad = !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim());
        if (!bad && n === "message") bad = el.value.trim().length < 10;
        setErr(n, bad ? ({ name: "Please enter your name.", email: "Please enter a valid email address.", message: "Please write at least a sentence (10 characters or more).", service: "Please choose a service.", consent: "Please confirm that we may store your details to answer you." })[n] : "");
        if (bad) ok = false;
      });
      return ok;
    }
    form.addEventListener("input", function (e) { if (e.target.name) setErr(e.target.name, ""); });
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      status.textContent = ""; status.className = "pb-form-status";
      if (!clientCheck()) { var f = form.querySelector(".has-err input, .has-err select, .has-err textarea"); if (f) f.focus(); form.classList.remove("shake"); void form.offsetWidth; form.classList.add("shake"); return; }
      btn.disabled = true; form.classList.add("is-busy");
      fetch(form.action, { method: "POST", body: new FormData(form), headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" }, credentials: "same-origin" })
        .then(function (r) { return r.json().catch(function () { return { ok: false, message: r.status === 419 ? "The page was open for a long time. Please reload it and send again." : "Something went wrong. Please try again." }; }); })
        .then(function (res) {
          if (res.ok) {
            wrap.classList.add("is-sent");
            var done = wrap.querySelector(".pb-form-done"); done.hidden = false;
            form.reset();
            done.scrollIntoView({ behavior: reduced ? "auto" : "smooth", block: "center" });
            return;
          }
          if (res.errors) Object.keys(res.errors).forEach(function (k) { setErr(k, res.errors[k][0]); });
          status.textContent = res.message || "Please check the form."; status.className = "pb-form-status err";
          form.classList.remove("shake"); void form.offsetWidth; form.classList.add("shake");
        })
        .catch(function () { status.textContent = "No connection. Please check your internet and try again."; status.className = "pb-form-status err"; })
        .then(function () { btn.disabled = false; form.classList.remove("is-busy"); });
    });
  });

  /* ---------- store: add to cart, quantities, variants, gallery, checkout ---------- */
  function toast(html) {
    var t = document.querySelector(".st-toast") || document.body.appendChild(Object.assign(document.createElement("div"), { className: "st-toast", role: "status" }));
    t.innerHTML = html; requestAnimationFrame(function () { t.classList.add("show"); });
    clearTimeout(t._h); t._h = setTimeout(function () { t.classList.remove("show"); }, 4200);
  }
  function cartCount(n) {
    var c = document.querySelector(".hdr-cart"); if (!c) return;
    var b = c.querySelector("b"); if (!b) { b = document.createElement("b"); c.appendChild(b); }
    b.textContent = n; c.classList.remove("bump"); void c.offsetWidth; c.classList.add("bump");
  }
  document.querySelectorAll("form[data-add-cart]").forEach(function (f) {
    f.addEventListener("submit", function (e) {
      var sub = e.submitter;
      if (sub && sub.name === "buy_now") return; // normal submit → checkout
      e.preventDefault();
      var btn = sub || f.querySelector("button[type=submit]:not([name=buy_now])") || f.querySelector("button[type=submit]"), msg = f.querySelector(".st-buy-msg");
      btn.disabled = true;
      fetch(f.action, { method: "POST", body: new FormData(f), headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" }, credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.ok) {
            cartCount(res.count);
            btn.classList.add("added"); var span = btn.querySelector("span"), old = span && span.textContent; if (span) span.textContent = "Added";
            setTimeout(function () { btn.classList.remove("added"); if (span) span.textContent = old; }, 1800);
            var html = '<i class="fa-solid fa-circle-check"></i> ' + res.message.replace(/</g, "&lt;") + ' <a href="' + res.cart_url + '">View cart</a>';
            if (msg) { msg.className = "st-buy-msg ok"; msg.innerHTML = html; } else toast(html);
          } else {
            if (msg) { msg.className = "st-buy-msg err"; msg.textContent = res.message; } else toast(res.message);
          }
        })
        .catch(function () { f.submit(); })
        .then(function () { btn.disabled = false; });
    });
  });
  document.addEventListener("click", function (e) {
    var b = e.target.closest("[data-qty]"); if (!b) return;
    var input = b.parentNode.querySelector("input"); var v = (parseInt(input.value, 10) || 1) + parseInt(b.getAttribute("data-qty"), 10);
    input.value = Math.max(parseInt(input.min || "1", 10), Math.min(99, v));
  });
  document.querySelectorAll("input[name=variant]").forEach(function (r) {
    r.addEventListener("change", function () { if (!r.dataset.priceHtml) return; document.querySelectorAll("[data-price] .st-price").forEach(function (p) { p.innerHTML = r.dataset.priceHtml; p.animate && p.animate([{ opacity: 0, transform: "translateY(6px)" }, { opacity: 1, transform: "none" }], { duration: 350 }); }); });
  });
  document.querySelectorAll("[data-gallery]").forEach(function (g) {
    var main = g.querySelector("[data-main]");
    g.querySelectorAll(".st-thumbs button").forEach(function (b) {
      b.addEventListener("click", function () {
        g.querySelectorAll(".st-thumbs button").forEach(function (x) { x.classList.toggle("on", x === b); });
        main.classList.add("swap"); setTimeout(function () { main.src = b.dataset.src; main.classList.remove("swap"); }, 200);
      });
    });
  });
  /* Checkout coupon: applied / removed in place, the totals and the "Place order" amount update without a reload */
  var cp = document.querySelector("[data-coupon]");
  if (cp) {
    var coForm = cp.closest("form"), token = coForm && coForm.querySelector("input[name=_token]");
    var esc = function (t) { return String(t).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); };
    var say = function (text, err) { var m = cp.querySelector(".st-coupon-msg"); if (!m) return; m.textContent = text || ""; if (err) m.setAttribute("data-err", ""); else m.removeAttribute("data-err"); };
    var send = function (btn, body) {
      btn.disabled = true;
      body.append("_token", token ? token.value : "");
      return fetch(btn.getAttribute("formaction"), { method: "POST", body: body, headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" }, credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          var tot = document.querySelector("[data-totals]"); if (tot && res.totals_html) tot.innerHTML = res.totals_html;
          var place = document.querySelector(".st-place .t"); if (place && res.total) place.textContent = "Place order — " + res.total;
          return res;
        })
        .finally(function () { btn.disabled = false; });
    };
    var url = (cp.querySelector("[formaction]") || {}).getAttribute ? cp.querySelector("[formaction]").getAttribute("formaction") : "";
    var showApplied = function (code) {
      cp.innerHTML = '<p class="st-coupon-on"><i class="fa-solid fa-ticket"></i> Coupon <strong>' + esc(code) + '</strong> applied <button type="submit" class="st-link-btn" formaction="' + esc(url) + '" formnovalidate name="remove" value="1" data-coupon-remove>Remove</button></p><p class="st-coupon-msg" role="status"></p>';
    };
    var showToggle = function () {
      cp.innerHTML = '<div class="pb-check st-coupon-toggle"><label><input type="checkbox" data-coupon-open> <span>Do you have a coupon code?</span></label></div>' +
        '<div class="st-coupon-box" hidden><input name="code" placeholder="Enter coupon code" aria-label="Coupon code" maxlength="50" autocomplete="off"><button type="submit" class="st-btn ghost sm" formaction="' + esc(url) + '" formnovalidate data-coupon-apply>Apply</button></div>' +
        '<p class="st-coupon-msg" role="status"></p>';
    };
    var apply = function (btn) {
      var input = cp.querySelector("input[name=code]"), code = input ? input.value.trim() : "";
      if (!code) { say("Please enter a coupon code.", true); if (input) input.focus(); return; }
      var fd = new FormData(); fd.append("code", code);
      send(btn, fd).then(function (res) {
        if (res.ok) { showApplied(res.coupon || code); say(res.message); } else say(res.message, true);
      }).catch(function () { say("Could not check the coupon. Please try again.", true); });
    };
    cp.addEventListener("click", function (e) {
      var a = e.target.closest("[data-coupon-apply]"), r = e.target.closest("[data-coupon-remove]");
      if (a) { e.preventDefault(); apply(a); }
      if (r) {
        e.preventDefault();
        var fd = new FormData(); fd.append("remove", "1");
        send(r, fd).then(function (res) { showToggle(); say(res.message); }).catch(function () { say("Could not remove the coupon. Please try again.", true); });
      }
    });
    cp.addEventListener("change", function (e) {
      if (!e.target.matches("[data-toggle], [data-coupon-open]")) return;
      var box = cp.querySelector(".st-coupon-box"); if (!box) return;
      box.hidden = !e.target.checked; if (e.target.checked) { var i = box.querySelector("input"); if (i) i.focus(); }
    });
    /* Enter inside the coupon box applies the coupon instead of placing the order */
    cp.addEventListener("keydown", function (e) {
      if (e.key === "Enter" && e.target.name === "code") { e.preventDefault(); var b = cp.querySelector("[data-coupon-apply]"); if (b) apply(b); }
    });
  }
  document.querySelectorAll("[data-toggle]").forEach(function (c) {
    c.addEventListener("change", function () { var t = document.querySelector(c.getAttribute("data-toggle")); if (t) t.hidden = !c.checked; });
  });
  document.querySelectorAll("form[data-checkout], .st-gateways").forEach(function (f) {
    var form = f.tagName === "FORM" ? f : f.closest("form"); if (!form || form._st) return; form._st = true;
    form.addEventListener("submit", function () { form.classList.add("is-busy"); });
    window.addEventListener("pageshow", function () { form.classList.remove("is-busy"); });
    // only the reference box of the chosen manual method is sent
    function refs() { form.querySelectorAll("[data-ref]").forEach(function (i) { var on = i.closest(".st-gateway").querySelector("input[type=radio]").checked; i.disabled = !on; }); }
    form.addEventListener("change", refs); refs();
  });
})();
