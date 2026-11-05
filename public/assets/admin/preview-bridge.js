/* =========================================================
   Runs inside the page-builder preview iframe (Elementor-style):
   - hover handles for sections, columns and widgets
   - click anything to edit it
   - drag widgets / section layouts from the panel INTO the page
   - drag widgets and sections around inside the page
   Every change is sent to the builder (parent window) with postMessage;
   the builder updates the layout and re-renders this preview.
   ========================================================= */
(function () {
  "use strict";
  var doc = document;
  // a Canvas page whose HTML widget is a whole document: every click edits that widget
  var docId = document.documentElement.getAttribute("data-bfi-doc");
  var send = function (msg) { parent.postMessage(msg, "*"); };

  /* ---------- styles ---------- */
  var css = doc.createElement("style");
  css.textContent = [
    "[data-bfi-id]{cursor:default}",
    ".pb-w[data-bfi-id]:hover{outline:1px dashed rgba(255,154,62,.9);outline-offset:-1px}",
    ".pb-col[data-bfi-id]:hover{outline:1px dashed rgba(120,160,255,.55);outline-offset:-1px}",
    ".bfi-sel{outline:2px solid #ff5266!important;outline-offset:-2px}",
    ".pb-section.bfi-sel{outline:2px solid #3b82f6!important;outline-offset:-2px}",
    ".pb-section[data-bfi-id]:hover{box-shadow:inset 0 0 0 1px rgba(59,130,246,.7)}",
    ".bfi-h{position:fixed;z-index:2147483000;display:none;gap:1px;font:600 12px/1 system-ui,sans-serif;box-shadow:0 4px 14px rgba(0,0,0,.35)}",
    ".bfi-h button{border:0;background:inherit;color:#fff;height:26px;min-width:26px;padding:0 7px;cursor:pointer;display:inline-flex;align-items:center;gap:5px}",
    ".bfi-h button:hover{background:rgba(255,255,255,.18)}",
    ".bfi-h .grip{cursor:grab;touch-action:none}",
    ".bfi-h-sec{background:#3b82f6;border-radius:0 0 8px 8px}",
    ".bfi-h-col{background:#64748b;border-radius:0 0 6px 0}",
    ".bfi-h-w{background:#ff5266;border-radius:0 0 0 6px}",
    ".bfi-drop{position:fixed;z-index:2147483001;height:4px;border-radius:4px;background:#ff9a3e;box-shadow:0 0 0 2px rgba(255,154,62,.35),0 0 18px #ff9a3e;pointer-events:none;display:none}",
    ".bfi-drop.sec{background:#3b82f6;box-shadow:0 0 0 2px rgba(59,130,246,.35),0 0 18px #3b82f6}",
    ".bfi-drop span{position:absolute;left:50%;top:-24px;transform:translateX(-50%);background:inherit;color:#fff;font:700 11px/1 system-ui,sans-serif;padding:5px 9px;border-radius:12px;white-space:nowrap}",
    ".pb-add-zone{margin:40px auto 80px;max-width:1170px;border:2px dashed rgba(255,255,255,.22);border-radius:18px;padding:34px 20px;text-align:center;color:#b9b4cf;font:600 15px/1.5 system-ui,sans-serif;cursor:pointer;transition:.2s}",
    ".pb-add-zone:hover,.pb-add-zone.over{border-color:#ff9a3e;color:#fff;background:rgba(255,154,62,.06)}",
    ".pb-add-zone b{display:inline-grid;place-items:center;width:44px;height:44px;border-radius:50%;background:linear-gradient(90deg,#ff9a3e,#ff5266);color:#fff;font-size:24px;margin-bottom:10px}",
    ".pb-empty-col{cursor:pointer}",
    "body.bfi-dragging *{cursor:grabbing!important}"
  ].join("");
  doc.head.appendChild(css);

  /* ---------- handles ---------- */
  function bar(cls, buttons) {
    var b = doc.createElement("div");
    b.className = "bfi-h " + cls;
    b.innerHTML = buttons;
    doc.body.appendChild(b);
    return b;
  }
  var hSec = bar("bfi-h-sec",
    '<button type="button" data-a="add-above" title="ওপরে নতুন Section">＋</button>' +
    '<button type="button" class="grip" data-drag="section" title="টেনে সরান">⠿ Section</button>' +
    '<button type="button" data-a="edit" title="Section এডিট">✎</button>' +
    '<button type="button" data-a="dup" title="কপি">⧉</button>' +
    '<button type="button" data-a="del" title="মুছুন">✕</button>');
  var hCol = bar("bfi-h-col", '<button type="button" data-a="edit" title="Column এডিট">▥ Column</button>');
  var hW = bar("bfi-h-w",
    '<button type="button" class="grip" data-drag="widget" title="টেনে সরান">⠿</button>' +
    '<button type="button" data-a="edit" title="এডিট">✎</button>' +
    '<button type="button" data-a="dup" title="কপি">⧉</button>' +
    '<button type="button" data-a="del" title="মুছুন">✕</button>');
  var drop = doc.createElement("div"); drop.className = "bfi-drop"; drop.innerHTML = "<span></span>"; doc.body.appendChild(drop);

  var cur = { sec: null, col: null, w: null };
  function place(h, el, where) {
    if (!el) { h.style.display = "none"; return; }
    var r = el.getBoundingClientRect();
    if (r.bottom < 0 || r.top > innerHeight) { h.style.display = "none"; return; }
    h.style.display = "flex";
    var top = Math.max(0, r.top);
    if (where === "center") { h.style.top = top + "px"; h.style.left = (r.left + r.width / 2 - h.offsetWidth / 2) + "px"; }
    if (where === "left") { h.style.top = top + "px"; h.style.left = Math.max(0, r.left) + "px"; }
    if (where === "right") { h.style.top = top + "px"; h.style.left = (Math.min(innerWidth, r.right) - h.offsetWidth) + "px"; }
  }
  function refresh() {
    place(hSec, cur.sec, "center");
    place(hCol, cur.col, "left");
    place(hW, cur.w, "right");
  }
  doc.addEventListener("mousemove", function (e) {
    if (e.target.closest && e.target.closest(".bfi-h")) return;
    var t = e.target.closest ? e.target : null;
    if (!t) return;
    cur.w = t.closest(".pb-w[data-bfi-id]");
    cur.col = t.closest(".pb-col[data-bfi-id]");
    cur.sec = t.closest(".pb-section[data-bfi-id]");
    // a column's own handle is noise when it is the only column
    if (cur.col && cur.col.parentNode.querySelectorAll(":scope > .pb-col").length < 2 && !cur.col.closest(".pb-w-row")) cur.col = null;
    refresh();
  });
  doc.addEventListener("mouseleave", function () { cur = { sec: null, col: null, w: null }; refresh(); });
  addEventListener("scroll", refresh, { passive: true });
  addEventListener("resize", refresh);

  function handleClick(h, getEl) {
    h.addEventListener("click", function (e) {
      var b = e.target.closest("button[data-a]"); var el = getEl();
      if (!b || !el) return;
      e.preventDefault(); e.stopPropagation();
      send({ type: "bfi-action", action: b.dataset.a, id: el.getAttribute("data-bfi-id") });
    });
  }
  handleClick(hSec, function () { return cur.sec; });
  handleClick(hCol, function () { return cur.col; });
  handleClick(hW, function () { return cur.w; });

  /* ---------- click to select ---------- */
  doc.addEventListener("click", function (e) {
    if (e.target.closest(".bfi-h")) return;
    var zone = e.target.closest(".pb-add-zone");
    e.preventDefault(); e.stopPropagation();
    if (zone) { send({ type: "bfi-action", action: "add-section-end" }); return; }
    var empty = e.target.closest(".pb-empty-col");
    var t = e.target.closest("[data-bfi-id]");
    if (!t && docId) { send({ type: "bfi-select", id: docId, add: false }); return; }
    if (!t) return;
    // clicks on a section's own padding/background select the section, not a column
    if (t.classList.contains("pb-col") && !e.target.closest(".pb-w") && !empty) {
      var sec = t.closest(".pb-section");
      var r = t.getBoundingClientRect();
      if (sec && (e.clientY < r.top + 6 || e.clientY > r.bottom - 6)) t = sec;
    }
    send({ type: "bfi-select", id: t.getAttribute("data-bfi-id"), add: !!empty });
  }, true);
  doc.addEventListener("submit", function (e) { e.preventDefault(); }, true);

  /* ---------- drag & drop ---------- */
  function sections() { return Array.prototype.slice.call(doc.querySelectorAll("main .pb-section[data-bfi-id]")); }
  function sectionSlot(y) {
    var list = sections(), i;
    for (i = 0; i < list.length; i++) { var r = list[i].getBoundingClientRect(); if (y < r.top + r.height / 2) return { index: i, y: r.top }; }
    var last = list[list.length - 1];
    return { index: list.length, y: last ? last.getBoundingClientRect().bottom : 80 };
  }
  function widgetSlot(x, y, target) {
    var col = target && target.closest(".pb-col[data-bfi-id]");
    if (!col) {
      // over section padding: use the nearest column of that section
      var sec = target && target.closest(".pb-section[data-bfi-id]");
      if (!sec) return null;
      var cols = Array.prototype.slice.call(sec.querySelectorAll(".pb-col[data-bfi-id]"));
      var best = null, bd = 1e9;
      cols.forEach(function (c) { var r = c.getBoundingClientRect(); var d = Math.abs(x - (r.left + r.width / 2)) + (y < r.top ? r.top - y : y > r.bottom ? y - r.bottom : 0); if (d < bd) { bd = d; best = c; } });
      col = best;
      if (!col) return null;
    }
    var ws = Array.prototype.slice.call(col.children).filter(function (n) { return n.matches && n.matches(".pb-w[data-bfi-id]"); });
    var cr = col.getBoundingClientRect();
    for (var i = 0; i < ws.length; i++) {
      var r = ws[i].getBoundingClientRect();
      if (y < r.top + r.height / 2) return { col: col.getAttribute("data-bfi-id"), index: i, y: r.top - 2, left: cr.left, width: cr.width };
    }
    var lr = ws.length ? ws[ws.length - 1].getBoundingClientRect() : null;
    return { col: col.getAttribute("data-bfi-id"), index: ws.length, y: lr ? lr.bottom + 2 : cr.top + 10, left: cr.left, width: cr.width };
  }
  function showDrop(left, y, width, text, sec) {
    drop.style.display = "block"; drop.className = "bfi-drop" + (sec ? " sec" : "");
    drop.style.left = left + "px"; drop.style.top = (y - 2) + "px"; drop.style.width = width + "px";
    drop.firstChild.textContent = text;
  }
  function hideDrop() { drop.style.display = "none"; var z = doc.querySelector(".pb-add-zone.over"); if (z) z.classList.remove("over"); }

  /* move widgets / sections inside the preview with the ⠿ grip (pointer based: mouse, pen and touch) */
  var mv = null;
  function moveSlot(x, y) {
    var target = doc.elementFromPoint(x, y);
    hideDrop();
    if (!target) return null;
    if (mv.kind === "movesection") {
      var s = target.closest(".pb-add-zone") ? { index: sections().length, y: target.closest(".pb-add-zone").getBoundingClientRect().top } : sectionSlot(y);
      showDrop(0, s.y, innerWidth, "Section এখানে সরবে", true);
      return { sectionIndex: s.index };
    }
    if (mv.el.contains(target)) return null; // can't drop a widget into itself
    var w = widgetSlot(x, y, target);
    if (!w) return null;
    showDrop(w.left, w.y, w.width, "এখানে সরবে");
    return { col: w.col, index: w.index };
  }
  doc.addEventListener("pointerdown", function (e) {
    var g = e.target.closest && e.target.closest("[data-drag]");
    if (!g || e.button !== 0) return;
    var el = g.dataset.drag === "section" ? cur.sec : cur.w;
    if (!el) return;
    e.preventDefault();
    mv = { kind: g.dataset.drag === "section" ? "movesection" : "move", id: el.getAttribute("data-bfi-id"), el: el, sx: e.clientX, sy: e.clientY, active: false, slot: null };
    try { g.setPointerCapture(e.pointerId); } catch (x) {}
  });
  doc.addEventListener("pointermove", function (e) {
    if (!mv) return;
    if (!mv.active) {
      if (Math.abs(e.clientX - mv.sx) + Math.abs(e.clientY - mv.sy) < 5) return;
      mv.active = true; doc.body.classList.add("bfi-dragging"); mv.el.style.opacity = ".45";
      hSec.style.visibility = hCol.style.visibility = hW.style.visibility = "hidden";
    }
    if (e.clientY < 50) scrollBy({ top: -22, behavior: "instant" }); else if (e.clientY > innerHeight - 50) scrollBy({ top: 22, behavior: "instant" });
    mv.slot = moveSlot(e.clientX, e.clientY);
  });
  function endMove(cancel) {
    if (!mv) return;
    var m = mv; mv = null;
    hideDrop(); doc.body.classList.remove("bfi-dragging"); m.el.style.opacity = "";
    hSec.style.visibility = hCol.style.visibility = hW.style.visibility = "";
    if (cancel || !m.active || !m.slot) return;
    send({ type: "bfi-drop", kind: m.kind, id: m.id, col: m.slot.col || null, index: m.slot.index, sectionIndex: m.slot.sectionIndex, newSection: false });
  }
  doc.addEventListener("pointerup", function () { endMove(false); });
  doc.addEventListener("pointercancel", function () { endMove(true); });
  doc.addEventListener("keydown", function (e) { if (e.key === "Escape") endMove(true); });

  /* ---------- drag API for the builder panel (pointer-based, works across the iframe) ---------- */
  function slotAt(k, x, y) {
    var target = doc.elementFromPoint(x, y);
    var zone = target && target.closest && target.closest(".pb-add-zone");
    hideDrop();
    if (zone || k === "newsection") {
      var s = zone ? { index: sections().length, y: zone.getBoundingClientRect().top } : sectionSlot(y);
      if (zone) zone.classList.add("over"); else showDrop(0, s.y, innerWidth, "নতুন Section এখানে", true);
      return { kind: k, sectionIndex: s.index };
    }
    var w = target ? widgetSlot(x, y, target) : null;
    if (!w) { var s2 = sectionSlot(y); showDrop(0, s2.y, innerWidth, "নতুন Section-এ যোগ হবে", true); return { kind: k, sectionIndex: s2.index, newSection: true }; }
    showDrop(w.left, w.y, w.width, "এখানে যোগ হবে");
    return { kind: k, col: w.col, index: w.index };
  }
  var apiSlot = null;
  window.__bfiDrag = {
    over: function (k, x, y) { apiSlot = slotAt(k, x, y); },
    leave: function () { apiSlot = null; hideDrop(); },
    drop: function (k, data) {
      hideDrop();
      if (!apiSlot) return null;
      var msg = { type: "bfi-drop", kind: k, col: apiSlot.col || null, index: apiSlot.index, sectionIndex: apiSlot.sectionIndex, newSection: !apiSlot.col };
      if (k === "new") msg.widget = data; else msg.widths = data;
      apiSlot = null;
      return msg;
    }
  };
  // auto-scroll the preview while dragging near its edges
  window.__bfiDrag.scroll = function (y) { if (y < 60) scrollBy({ top: -22, behavior: "instant" }); else if (y > innerHeight - 60) scrollBy({ top: 22, behavior: "instant" }); };

  /* ---------- messages from the builder ---------- */
  addEventListener("message", function (e) {
    if (!e.data || e.data.type !== "bfi-highlight") return;
    doc.querySelectorAll(".bfi-sel").forEach(function (x) { x.classList.remove("bfi-sel"); });
    var el = e.data.id && doc.querySelector('[data-bfi-id="' + e.data.id + '"]');
    if (!el) return;
    el.classList.add("bfi-sel");
    if (e.data.scroll) {
      var r = el.getBoundingClientRect();
      if (r.top < 60 || r.top > innerHeight - 80) scrollTo({ top: scrollY + r.top - 120, behavior: "smooth" });
    }
  });
  addEventListener("scroll", function () { send({ type: "bfi-scroll", y: scrollY }); }, { passive: true });

  // "add section" zone at the end of the page
  var main = docId ? null : doc.querySelector("main");
  if (main) {
    var z = doc.createElement("div");
    z.className = "pb-add-zone";
    z.innerHTML = "<b>+</b><br>নতুন Section যোগ করুন<br><small>অথবা বাঁ পাশ থেকে Widget / লেআউট এখানে টেনে আনুন</small>";
    main.appendChild(z);
  }
  send({ type: "bfi-ready" });
})();
