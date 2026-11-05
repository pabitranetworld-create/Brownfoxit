/* =========================================================
   Brown Fox IT – Page Builder (admin)
   Data: window.BFI_BUILDER = { defs, layout, page, urls, csrf }
   The layout JSON is edited here, previewed through the real
   frontend renderer (iframe) and validated again on the server.
   ========================================================= */
(function () {
  "use strict";

  var B = window.BFI_BUILDER, defs = B.defs;
  var state = { layout: B.layout, page: B.page, sel: null, tab: "add", dirty: false, device: "desktop" };
  var history = [], future = [], lastSnap = JSON.stringify(state.layout);
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var panel = $("#bPanel"), frame = $("#bFrame");

  /* ---------------- helpers ---------------- */
  function uid() { return Math.random().toString(36).slice(2, 12).replace(/[^a-z0-9]/g, "a").padEnd(10, "x"); }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }
  function esc(s) { return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); }
  function h(html) { var t = document.createElement("template"); t.innerHTML = html.trim(); return t.content.firstChild; }
  function defaults(fields) {
    var o = {};
    fields.forEach(function (f) { if (f.type !== "note") o[f.key] = f.default !== undefined ? clone(f.default) : (f.type === "repeater" ? [] : ""); });
    return o;
  }
  function toast(msg, bad) {
    var t = $("#bToast"); t.textContent = msg; t.className = "bld-toast show" + (bad ? " bad" : "");
    clearTimeout(toast.t); toast.t = setTimeout(function () { t.className = "bld-toast"; }, 3200);
  }
  function report(what, status, detail) {
    try {
      var fd = new FormData();
      fd.append("what", what); fd.append("status", status || 0); fd.append("url", location.href); fd.append("detail", String(detail || "").slice(0, 1500));
      fetch(B.urls.clientLog, { method: "POST", body: fd, credentials: "same-origin", headers: { "Accept": "application/json" } });
    } catch (e) {}
  }
  window.addEventListener("error", function (e) { report("js error", 0, (e.message || "") + " @ " + (e.filename || "") + ":" + (e.lineno || "")); });

  function api(url, data) {
    var fd = new FormData();
    fd.append("_token", B.csrf);
    Object.keys(data || {}).forEach(function (k) {
      if (k === "page") Object.keys(data.page).forEach(function (pk) { var v = data.page[pk]; fd.append("page[" + pk + "]", typeof v === "boolean" ? (v ? "1" : "0") : (v == null ? "" : v)); });
      else fd.append(k, data[k]);
    });
    return fetch(url, { method: "POST", body: fd, credentials: "same-origin", headers: { "X-CSRF-TOKEN": B.csrf, "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" } })
      .then(function (r) {
        return r.text().then(function (t) {
          var j; try { j = JSON.parse(t); } catch (e) {
            report("bad response", r.status, t.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").slice(0, 600));
            throw new Error(r.status === 419 ? "সেশন শেষ হয়ে গেছে (419)। পেজ রিফ্রেশ করুন — আপনার কাজ এই ব্রাউজারে ব্যাকআপ রাখা আছে।"
              : r.status === 401 || r.status === 302 ? "লগইন শেষ হয়ে গেছে। নতুন ট্যাবে আবার লগইন করে Save চাপুন।"
              : "সার্ভার Error (HTTP " + r.status + "): " + t.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim().slice(0, 120));
          }
          if (!r.ok || j.ok === false) { if (r.status >= 500) report("server error", r.status, t); throw new Error(j.message || "Error " + r.status); }
          return j;
        });
      });
  }

  /* ---------------- find / walk ---------------- */
  function eachColumn(fn) {
    state.layout.sections.forEach(function (s) {
      s.columns.forEach(function (c) {
        fn(c, s, null);
        c.widgets.forEach(function (w) { if (w.type === "row") (w.columns || []).forEach(function (rc) { fn(rc, s, w); }); });
      });
    });
  }
  function find(id) {
    var found = null;
    state.layout.sections.forEach(function (s, si) {
      if (s.id === id) found = { kind: "section", node: s, list: state.layout.sections, index: si };
      s.columns.forEach(function (c, ci) {
        if (c.id === id) found = { kind: "column", node: c, list: s.columns, index: ci, parent: s };
        c.widgets.forEach(function (w, wi) {
          if (w.id === id) found = { kind: "widget", node: w, list: c.widgets, index: wi, column: c, section: s };
          if (w.type === "row") (w.columns || []).forEach(function (rc, rci) {
            if (rc.id === id) found = { kind: "column", node: rc, list: w.columns, index: rci, parent: w, nested: true };
            rc.widgets.forEach(function (rw, rwi) { if (rw.id === id) found = { kind: "widget", node: rw, list: rc.widgets, index: rwi, column: rc, section: s, nested: true }; });
          });
        });
      });
    });
    return found;
  }
  function label(f) {
    if (!f) return "";
    if (f.kind === "section") return "Section " + (f.index + 1);
    if (f.kind === "column") return "Column " + (f.index + 1) + " · " + Math.round(f.node.settings.w) + "%";
    var d = defs.widgets[f.node.type];
    return d ? d.label : f.node.type;
  }
  function summary(w) {
    var d = w.data || {};
    var t = d.title || d.text || d.eyebrow || d.q || (d.items && d.items[0] && (d.items[0].title || d.items[0].text || d.items[0].q)) || (d.nodes && d.nodes.map(function (n) { return n.label; }).join(" → ")) || "";
    if (!t && d.content) t = d.content.replace(/<[^>]+>/g, " ");
    return String(t).replace(/\s+/g, " ").trim().slice(0, 48);
  }

  /* ---------------- factories ---------------- */
  function newColumn(w) { var s = defaults(defs.column); s.w = w; s.w_tablet = w >= 50 ? 100 : 50; if (w === 100) s.w_tablet = 100; s.w_mobile = 100; s.anim = "none"; return { id: uid(), settings: s, widgets: [] }; }
  function newSection(widths) {
    var s = defaults(defs.section); s.anim = "none";
    return { id: uid(), settings: s, columns: widths.map(newColumn) };
  }
  function newWidget(type) {
    var w = { id: uid(), type: type, data: defaults(defs.widgets[type].fields), settings: defaults(defs.common) };
    if (type === "row") w.columns = [newColumn(50), newColumn(50)];
    return w;
  }

  /* ---------------- history ---------------- */
  var snapTimer;
  function changed(opts) {
    opts = opts || {};
    state.dirty = true;
    clearTimeout(snapTimer);
    snapTimer = setTimeout(snapshot, opts.instant ? 0 : 400);
    schedulePreview(opts.instant ? 0 : 450);
    if (opts.tree !== false && state.tab === "tree") renderPanel();
    updateButtons();
  }
  function snapshot() {
    var now = JSON.stringify(state.layout);
    if (now === lastSnap) return;
    history.push(lastSnap); if (history.length > 80) history.shift();
    future = []; lastSnap = now; updateButtons();
  }
  function undo() { snapshot(); if (!history.length) return; future.push(lastSnap); lastSnap = history.pop(); state.layout = JSON.parse(lastSnap); afterJump(); }
  function redo() { if (!future.length) return; history.push(lastSnap); lastSnap = future.pop(); state.layout = JSON.parse(lastSnap); afterJump(); }
  function afterJump() { if (state.sel && !find(state.sel)) state.sel = null; state.dirty = true; renderPanel(); schedulePreview(0); updateButtons(); }
  function updateButtons() {
    $("#bUndo").disabled = !history.length && JSON.stringify(state.layout) === lastSnap;
    $("#bRedo").disabled = !future.length;
    var st = $("#bStatus");
    if (state.page.is_template) { st.textContent = "Template"; st.className = "adm-badge"; }
    else if (state.dirty) { st.textContent = "সেভ হয়নি"; st.className = "adm-badge warn"; }
    else if (state.page.status === "published") { st.textContent = state.page.unpublished ? "Published · অপ্রকাশিত Draft আছে" : "Published"; st.className = "adm-badge " + (state.page.unpublished ? "warn" : "ok"); }
    else { st.textContent = "Draft"; st.className = "adm-badge"; }
    $("#bTitle").textContent = state.page.title;
  }

  /* ---------------- preview ---------------- */
  var previewTimer, previewSeq = 0, scrollY = 0;
  function schedulePreview(ms) { clearTimeout(previewTimer); previewTimer = setTimeout(refreshPreview, ms == null ? 450 : ms); }
  function refreshPreview() {
    var seq = ++previewSeq;
    $("#bLoading").classList.add("on");
    var fd = new FormData();
    fd.append("_token", B.csrf); fd.append("layout", JSON.stringify(state.layout)); fd.append("editor", "1");
    ["title", "show_page_header", "header_subtitle", "template", "custom_head", "custom_css", "custom_js"].forEach(function (k) { var v = state.page[k]; fd.append("page[" + k + "]", typeof v === "boolean" ? (v ? "1" : "0") : (v || "")); });
    fetch(B.urls.preview, { method: "POST", body: fd, credentials: "same-origin", headers: { "X-CSRF-TOKEN": B.csrf } })
      .then(function (r) { if (!r.ok) report("preview failed", r.status, ""); return r.text(); })
      .then(function (html) {
        if (seq !== previewSeq) return;
        var y = scrollY;
        frame.onload = function () {
          try { frame.contentWindow.scrollTo(0, y); } catch (e) {}
          $("#bLoading").classList.remove("on");
          highlight(false);
        };
        frame.srcdoc = html.replace("<head>", '<head><base href="' + B.urls.site + '/" target="_blank">');
      })
      .catch(function () { $("#bLoading").classList.remove("on"); toast("Preview লোড হয়নি", true); });
  }
  function highlight(scroll) {
    var f = state.sel && find(state.sel);
    try { frame.contentWindow.postMessage({ type: "bfi-highlight", id: f ? state.sel : null, label: label(f), scroll: scroll }, "*"); } catch (e) {}
  }
  window.addEventListener("message", function (e) {
    var m = e.data;
    if (!m || e.source !== frame.contentWindow) return;
    if (m.type === "bfi-scroll") scrollY = m.y;
    if (m.type === "bfi-select") { select(m.id, false); if (m.add) { state.tab = "add"; renderPanel(); } }
    if (m.type === "bfi-action") previewAction(m);
    if (m.type === "bfi-drop") previewDrop(m);
  });
  function findColumn(id) { var f = find(id); return f && f.kind === "column" ? f.node : null; }
  function previewAction(m) {
    if (m.action === "add-section-end") { state.sel = null; state.tab = "add"; renderPanel(); toast("নিচের \"নতুন Section\" থেকে একটা লেআউট বেছে নিন, অথবা Widget চাপুন"); return; }
    var f = find(m.id); if (!f) return;
    if (m.action === "edit") return select(m.id, false);
    if (m.action === "dup" || m.action === "del") return action(m.id, m.action);
    if (m.action === "add-above" && f.kind === "section") {
      var sec = newSection([100]); state.layout.sections.splice(f.index, 0, sec);
      changed({ instant: true }); state.sel = sec.columns[0].id; state.tab = "add"; renderPanel();
    }
  }
  function previewDrop(m) {
    if (m.kind === "newsection") {
      var widths = (m.widths || "100").split(",").map(Number);
      var sec = newSection(widths); state.layout.sections.splice(m.sectionIndex, 0, sec);
      changed({ instant: true }); select(sec.columns[0].id, false); state.tab = "add"; renderPanel(); return;
    }
    if (m.kind === "movesection") {
      var f = find(m.id); if (!f || f.kind !== "section") return;
      var to = m.sectionIndex; if (f.index < to) to--;
      state.layout.sections.splice(f.index, 1); state.layout.sections.splice(to, 0, f.node);
      changed({ instant: true }); return;
    }
    var col = m.col ? findColumn(m.col) : null;
    if (!col) { // dropped between sections: make a new one-column section there
      var ns = newSection([100]); state.layout.sections.splice(m.sectionIndex == null ? state.layout.sections.length : m.sectionIndex, 0, ns);
      col = ns.columns[0]; m.index = 0;
    }
    if (m.kind === "new") { if (defs.widgets[m.widget]) addWidget(m.widget, col, m.index); return; }
    if (m.kind === "move") {
      var mv = find(m.id); if (!mv || mv.kind !== "widget") return;
      var nested = false; eachColumn(function (c, s, row) { if (c === col && row) nested = true; });
      if (mv.node.type === "row" && (nested || (mv.node.columns || []).indexOf(col) > -1)) return toast("এখানে Nested কলাম রাখা যায় না", true);
      var idx = m.index; if (mv.list === col.widgets && mv.index < idx) idx--;
      mv.list.splice(mv.index, 1); col.widgets.splice(idx, 0, mv.node);
      state.sel = mv.node.id; changed({ instant: true });
    }
  }

  /* ---------------- selection + panel ---------------- */
  function select(id, scroll) {
    state.sel = id; state.tab = "edit";
    renderPanel(); highlight(scroll !== false);
  }
  document.querySelectorAll("#bTabs [data-tab]").forEach(function (b) {
    b.addEventListener("click", function () { state.tab = b.dataset.tab; if (state.tab !== "edit") { state.sel = state.sel; } renderPanel(); });
  });

  function renderPanel() {
    document.querySelectorAll("#bTabs [data-tab]").forEach(function (b) { b.classList.toggle("on", b.dataset.tab === state.tab); });
    panel.innerHTML = "";
    if (state.tab === "add") panel.appendChild(addPanel());
    else if (state.tab === "tree") panel.appendChild(treePanel());
    else if (state.tab === "page") panel.appendChild(pagePanel());
    else if (state.tab === "edit") { var f = state.sel && find(state.sel); if (f) panel.appendChild(editPanel(f)); else { state.tab = "tree"; renderPanel(); } }
  }

  /* ---- drag from the panel into the preview (pointer events, like Elementor) ---- */
  var dragJustEnded = false;
  function pointerDrag(el, kind, data, label) {
    el.addEventListener("pointerdown", function (e) {
      if (e.button !== 0) return;
      var sx = e.clientX, sy = e.clientY, ghost = null, active = false, overFrame = false;
      function bridge() { try { return frame.contentWindow.__bfiDrag; } catch (x) { return null; } }
      function move(ev) {
        if (!active && Math.abs(ev.clientX - sx) + Math.abs(ev.clientY - sy) < 6) return;
        if (!active) {
          active = true;
          ghost = h('<div class="bld-ghost">' + label + "</div>"); document.body.appendChild(ghost);
          document.body.classList.add("bld-dragging");
          try { el.setPointerCapture(ev.pointerId); } catch (x) {}
        }
        ghost.style.transform = "translate(" + (ev.clientX + 12) + "px," + (ev.clientY + 12) + "px)";
        var r = frame.getBoundingClientRect(), api = bridge();
        overFrame = ev.clientX >= r.left && ev.clientX <= r.right && ev.clientY >= r.top && ev.clientY <= r.bottom;
        if (!api) return;
        if (overFrame) { var fx = (ev.clientX - r.left), fy = (ev.clientY - r.top); api.scroll(fy); api.over(kind, fx, fy); ghost.classList.add("ok"); }
        else { api.leave(); ghost.classList.remove("ok"); }
      }
      function up() {
        document.removeEventListener("pointermove", move); document.removeEventListener("pointerup", up); document.removeEventListener("pointercancel", up);
        if (!active) return;
        ghost.remove(); document.body.classList.remove("bld-dragging");
        dragJustEnded = true; setTimeout(function () { dragJustEnded = false; }, 50);
        var api = bridge();
        if (api && overFrame) { var msg = api.drop(kind, data); if (msg) previewDrop(msg); }
        else if (api) api.leave();
      }
      document.addEventListener("pointermove", move); document.addEventListener("pointerup", up); document.addEventListener("pointercancel", up);
    });
  }

  /* ---- widget palette ---- */
  var PRESETS = [[100], [50, 50], [33.33, 33.33, 33.34], [25, 25, 25, 25], [60, 40], [40, 60], [66.67, 33.33], [33.33, 66.67]];
  function addPanel() {
    var box = h('<div class="bld-add"></div>');
    var tgt = targetColumn();
    box.appendChild(h('<p class="bld-hint"><i class="fa-solid fa-hand-pointer"></i> Widget <b>প্রিভিউতে টেনে এনে</b> যেখানে চান সেখানে ছাড়ুন, অথবা চাপুন' + (tgt ? ' (যোগ হবে: <b>' + esc(tgt.where) + '</b>)' : " (নতুন Section তৈরি হবে)") + "।</p>"));
    var cats = {};
    Object.keys(defs.widgets).forEach(function (k) { var w = defs.widgets[k]; (cats[w.category] = cats[w.category] || []).push(w); });
    Object.keys(defs.categories).forEach(function (c) {
      if (!cats[c]) return;
      box.appendChild(h('<h4 class="bld-cat">' + esc(defs.categories[c]) + "</h4>"));
      var grid = h('<div class="bld-palette"></div>');
      cats[c].forEach(function (w) {
        var b = h('<button type="button" class="bld-wbtn"><i class="' + esc(w.icon) + '"></i><span>' + esc(w.label) + "</span></button>");
        b.addEventListener("click", function () { if (!dragJustEnded) addWidget(w.type); });
        pointerDrag(b, "new", w.type, '<i class="' + esc(w.icon) + '"></i> ' + esc(w.label));
        grid.appendChild(b);
      });
      box.appendChild(grid);
    });
    box.appendChild(h('<h4 class="bld-cat">নতুন Section (কলাম লেআউট)</h4>'));
    var pre = h('<div class="bld-presets"></div>');
    PRESETS.forEach(function (p) {
      var b = h('<button type="button" class="bld-preset" title="' + p.map(Math.round).join(" / ") + ' — চাপুন অথবা প্রিভিউতে টেনে আনুন"></button>');
      p.forEach(function (w) { b.appendChild(h('<span style="flex:' + w + '"></span>')); });
      b.addEventListener("click", function () { if (!dragJustEnded) addSection(p); });
      pointerDrag(b, "newsection", p.join(","), '<i class="fa-solid fa-table-columns"></i> Section ' + p.map(Math.round).join("/"));
      pre.appendChild(b);
    });
    box.appendChild(pre);
    return box;
  }
  function targetColumn() {
    var f = state.sel && find(state.sel);
    if (f && f.kind === "column") return { col: f.node, index: f.node.widgets.length, where: label(f) };
    if (f && f.kind === "widget" && f.node.type !== "row") return { col: f.column, index: f.index + 1, where: "\"" + label(f) + "\"-এর পরে" };
    if (f && f.kind === "widget" && f.node.type === "row") return { col: f.node.columns[0], index: f.node.columns[0].widgets.length, where: "Nested কলাম ১" };
    if (f && f.kind === "section") return { col: f.node.columns[0], index: f.node.columns[0].widgets.length, where: label(f) + " → Column 1" };
    var last = state.layout.sections[state.layout.sections.length - 1];
    return last ? { col: last.columns[0], index: last.columns[0].widgets.length, where: "শেষ Section → Column 1" } : null;
  }
  function addWidget(type, col, index) {
    var t = col ? { col: col, index: index } : targetColumn();
    if (!t) { addSection([100], true); t = { col: state.layout.sections[state.layout.sections.length - 1].columns[0], index: 0 }; }
    var inNested = false;
    eachColumn(function (c, s, row) { if (c === t.col && row) inNested = true; });
    if (type === "row" && inNested) { toast("Nested কলামের ভেতরে আরেকটা Nested কলাম দেওয়া যায় না", true); return; }
    var w = newWidget(type);
    t.col.widgets.splice(t.index, 0, w);
    changed({ instant: true });
    select(w.id);
  }
  function addSection(widths, silent) {
    var f = state.sel && find(state.sel), at = state.layout.sections.length;
    if (f) { var s = f.kind === "section" ? f.node : (f.section || f.parent); var i = state.layout.sections.indexOf(s); if (i > -1) at = i + 1; }
    var sec = newSection(widths);
    state.layout.sections.splice(at, 0, sec);
    if (!silent) { changed({ instant: true }); select(sec.columns[0].id); }
  }

  /* ---- structure tree ---- */
  function treePanel() {
    var box = h('<div class="bld-tree"></div>');
    if (!state.layout.sections.length) {
      box.appendChild(h('<div class="bld-empty"><i class="fa-regular fa-square-plus"></i><p>পেজ এখনো খালি।</p></div>'));
      var b = h('<button type="button" class="adm-btn sm">Widget যোগ করুন</button>');
      b.addEventListener("click", function () { state.tab = "add"; renderPanel(); });
      box.appendChild(b);
      return box;
    }
    state.layout.sections.forEach(function (s, si) {
      var sec = node(s.id, "section", '<i class="fa-solid fa-square"></i> Section ' + (si + 1), s.settings.bg !== "none" ? s.settings.bg : "");
      var cols = h('<div class="bld-children"></div>');
      s.columns.forEach(function (c, ci) { cols.appendChild(columnNode(c, ci)); });
      sec.appendChild(cols);
      box.appendChild(sec);
    });
    var add = h('<button type="button" class="adm-btn ghost sm bld-addsec"><i class="fa-solid fa-plus"></i> নতুন Section</button>');
    add.addEventListener("click", function () { state.tab = "add"; renderPanel(); });
    box.appendChild(add);
    return box;
  }
  function columnNode(c, ci) {
    var col = node(c.id, "column", '<i class="fa-solid fa-table-columns"></i> Column ' + (ci + 1), Math.round(c.settings.w) + "%");
    var kids = h('<div class="bld-children"></div>');
    c.widgets.forEach(function (w) {
      var d = defs.widgets[w.type] || { icon: "fa-solid fa-cube", label: w.type };
      var wn = node(w.id, "widget", '<i class="' + esc(d.icon) + '"></i> ' + esc(d.label), esc(summary(w)));
      if (w.type === "row") {
        var rk = h('<div class="bld-children"></div>');
        (w.columns || []).forEach(function (rc, rci) { rk.appendChild(columnNode(rc, rci)); });
        wn.appendChild(rk);
      }
      kids.appendChild(wn);
    });
    if (!c.widgets.length) kids.appendChild(h('<div class="bld-drop-empty">Widget এখানে টেনে আনুন</div>'));
    col.appendChild(kids);
    // drop target: column
    col.addEventListener("dragover", function (e) { e.preventDefault(); e.stopPropagation(); col.classList.add("drop"); });
    col.addEventListener("dragleave", function () { col.classList.remove("drop"); });
    col.addEventListener("drop", function (e) {
      e.preventDefault(); e.stopPropagation(); col.classList.remove("drop");
      var target = e.target.closest(".bld-node[data-kind=widget]");
      var idx = c.widgets.length;
      if (target && target.parentNode === kids) idx = Array.prototype.indexOf.call(kids.children, target);
      dropInto(e, c, idx);
    });
    return col;
  }
  function node(id, kind, title, meta) {
    var n = h('<div class="bld-node" data-kind="' + kind + '" data-id="' + id + '"><div class="bld-row' + (state.sel === id ? " sel" : "") + '" draggable="' + (kind !== "column") + '">' +
      '<span class="bld-row-t">' + title + (meta ? '<small>' + meta + "</small>" : "") + "</span>" +
      '<span class="bld-row-a"><button type="button" data-a="up" title="ওপরে"><i class="fa-solid fa-arrow-up"></i></button><button type="button" data-a="down" title="নিচে"><i class="fa-solid fa-arrow-down"></i></button>' +
      '<button type="button" data-a="dup" title="কপি"><i class="fa-regular fa-copy"></i></button><button type="button" data-a="del" title="মুছুন" class="danger"><i class="fa-solid fa-trash"></i></button></span></div></div>');
    var row = n.firstChild;
    row.addEventListener("click", function (e) {
      var a = e.target.closest("[data-a]");
      if (a) { action(id, a.dataset.a); return; }
      select(id);
    });
    row.addEventListener("dragstart", function (e) { e.stopPropagation(); e.dataTransfer.setData("text/bfi-move", id); });
    if (kind === "section") {
      row.addEventListener("dragover", function (e) { if (e.dataTransfer.types.indexOf("text/bfi-move") > -1) { e.preventDefault(); row.classList.add("drop"); } });
      row.addEventListener("dragleave", function () { row.classList.remove("drop"); });
      row.addEventListener("drop", function (e) {
        e.preventDefault(); row.classList.remove("drop");
        var mv = find(e.dataTransfer.getData("text/bfi-move"));
        if (!mv || mv.kind !== "section") return;
        var to = find(id);
        state.layout.sections.splice(mv.index, 1);
        state.layout.sections.splice(state.layout.sections.indexOf(to.node), 0, mv.node);
        changed({ instant: true });
      });
    }
    return n;
  }
  function dropInto(e, col, idx) {
    var nt = e.dataTransfer.getData("text/bfi-new");
    if (nt) { addWidget(nt, col, idx); return; }
    var mv = find(e.dataTransfer.getData("text/bfi-move"));
    if (!mv || mv.kind !== "widget") return;
    if (mv.node.type === "row") { var nested = false; eachColumn(function (c, s, row) { if (c === col && row) nested = true; }); if (nested) return toast("Nested কলাম এখানে রাখা যায় না", true); if ((mv.node.columns || []).indexOf(col) > -1) return; }
    if (mv.list === col.widgets && mv.index < idx) idx--;
    mv.list.splice(mv.index, 1);
    col.widgets.splice(idx, 0, mv.node);
    changed({ instant: true });
  }
  function action(id, a) {
    var f = find(id); if (!f) return;
    if (a === "up" && f.index > 0) { f.list.splice(f.index, 1); f.list.splice(f.index - 1, 0, f.node); }
    else if (a === "down" && f.index < f.list.length - 1) { f.list.splice(f.index, 1); f.list.splice(f.index + 1, 0, f.node); }
    else if (a === "dup") {
      if (f.kind === "column" && f.list.length >= 6) return toast("একটা সারিতে সর্বোচ্চ ৬টা কলাম", true);
      var c = reid(clone(f.node)); f.list.splice(f.index + 1, 0, c); state.sel = c.id;
    } else if (a === "del") {
      if (f.kind === "column" && f.list.length <= 1) return toast("শেষ কলামটা মোছা যায় না, পুরো Section মুছুন", true);
      if (!confirm(label(f) + " মুছে ফেলবেন?")) return;
      f.list.splice(f.index, 1); if (state.sel === id) { state.sel = null; state.tab = "tree"; }
    } else return;
    changed({ instant: true }); renderPanel(); highlight(true);
  }
  function reid(n) {
    n.id = uid();
    (n.columns || []).forEach(reid);
    (n.widgets || []).forEach(reid);
    return n;
  }

  /* ---- page settings ---- */
  function pagePanel() {
    var box = h('<div class="bld-form"></div>');
    var p = state.page;
    var fields = [
      { key: "title", label: "পেজের নাম", type: "text" },
      { key: "slug", label: "URL (" + B.urls.site.replace(/^https?:\/\//, "") + "/…)", type: "text" },
      { key: "template", label: "Page Layout", type: "select", options: B.templates, help: "Canvas = শুধু আপনার কন্টেন্ট (Landing page / পুরো Custom HTML পেজের জন্য)। Full Width = Header-Footer থাকে, টাইটেল ব্যানার থাকে না।" },
      { key: "show_page_header", label: "ওপরে টাইটেল ব্যানার (Breadcrumb সহ) — শুধু Default লেআউটে", type: "checkbox" },
      { key: "header_subtitle", label: "ব্যানারের নিচের লেখা", type: "text" },
      { key: "seo_title", label: "SEO Title (খালি = পেজের নাম)", type: "text" },
      { key: "seo_description", label: "SEO Description", type: "textarea" },
      { key: "og_image", label: "Social share ছবি (Facebook/LinkedIn)", type: "image" },
      { key: "noindex", label: "Google-এ দেখাবে না (noindex)", type: "checkbox" },
      { key: "custom_css", label: "এই পেজের Custom CSS", type: "code", lang: "css" },
      { key: "custom_js", label: "এই পেজের Custom JavaScript (পেজের শেষে চলে)", type: "code", lang: "js" },
      { key: "custom_head", label: "<head>-এ বাড়তি কোড (Font, Meta, Tracking)", type: "code", lang: "html" }
    ];
    if (p.is_template) fields = fields.filter(function (f) { return ["title"].indexOf(f.key) > -1; });
    fields.forEach(function (f) {
      box.appendChild(field(f, p[f.key], function (v) {
        p[f.key] = v; state.dirty = true; updateButtons();
        if (["title", "show_page_header", "header_subtitle", "template", "custom_head", "custom_css", "custom_js"].indexOf(f.key) > -1) schedulePreview(f.type === "code" ? 900 : 300);
      }));
    });
    if (p.seo_description !== undefined) {
      box.appendChild(h('<p class="bld-hint">Description ১৫০-১৬০ অক্ষরের মধ্যে রাখলে Google-এ পুরোটা দেখায়।</p>'));
    }
    return box;
  }

  /* ---- element editor ---- */
  function editPanel(f) {
    var box = h('<div class="bld-edit"></div>');
    var head = h('<div class="bld-edit-h"><button type="button" class="bld-icon" title="Structure"><i class="fa-solid fa-arrow-left"></i></button><strong>' + esc(label(f)) + '</strong>' +
      '<span><button type="button" class="bld-icon" data-a="dup" title="কপি"><i class="fa-regular fa-copy"></i></button><button type="button" class="bld-icon danger" data-a="del" title="মুছুন"><i class="fa-solid fa-trash"></i></button></span></div>');
    head.firstChild.addEventListener("click", function () { state.tab = "tree"; renderPanel(); });
    head.querySelector("[data-a=dup]").addEventListener("click", function () { action(f.node.id, "dup"); });
    head.querySelector("[data-a=del]").addEventListener("click", function () { action(f.node.id, "del"); });
    box.appendChild(head);

    var groups = { content: [], style: [], motion: [], advanced: [] };
    var target, fieldsList;
    if (f.kind === "widget") {
      var wd = defs.widgets[f.node.type];
      wd.fields.forEach(function (x) { groups[x.group || "content"].push({ f: x, obj: f.node.data }); });
      defs.common.forEach(function (x) { groups[x.group || "advanced"].push({ f: x, obj: f.node.settings }); });
    } else {
      (f.kind === "section" ? defs.section : defs.column).forEach(function (x) { groups[x.group || "style"].push({ f: x, obj: f.node.settings }); });
    }

    var names = { content: "Content", style: "Style", motion: "Motion", advanced: "Advanced" };
    var tabs = h('<div class="bld-subtabs"></div>'), body = h('<div class="bld-form"></div>');
    var avail = Object.keys(groups).filter(function (g) { return groups[g].length || (g === "content" && f.kind !== "widget"); });
    var cur = editPanel.group && avail.indexOf(editPanel.group) > -1 ? editPanel.group : avail[0];
    avail.forEach(function (g) {
      var b = h('<button type="button"' + (g === cur ? ' class="on"' : "") + ">" + names[g] + "</button>");
      b.addEventListener("click", function () { editPanel.group = g; renderPanel(); });
      tabs.appendChild(b);
    });
    box.appendChild(tabs);

    if (cur === "content" && f.kind !== "widget") {
      body.appendChild(structureTools(f));
    }
    groups[cur].forEach(function (it) {
      body.appendChild(field(it.f, it.obj[it.f.key], function (v) { it.obj[it.f.key] = v; changed({ tree: false }); }));
    });
    if (cur === "motion") {
      var test = h('<button type="button" class="adm-btn ghost sm"><i class="fa-solid fa-play"></i> Animation আবার দেখুন</button>');
      test.addEventListener("click", function () { schedulePreview(0); });
      body.appendChild(test);
    }
    box.appendChild(body);
    return box;
  }
  function structureTools(f) {
    var wrap = h('<div class="bld-tools"></div>');
    if (f.kind === "section" || (f.kind === "widget" && f.node.type === "row")) {
      var cols = f.node.columns;
      wrap.appendChild(h('<p class="bld-hint">' + cols.length + 'টা কলাম। কলামে ক্লিক করে প্রস্থ বদলান।</p>'));
      var pre = h('<div class="bld-presets"></div>');
      PRESETS.forEach(function (p) {
        var b = h('<button type="button" class="bld-preset" title="কলাম লেআউট বদলান"></button>');
        p.forEach(function (w) { b.appendChild(h('<span style="flex:' + w + '"></span>')); });
        b.addEventListener("click", function () {
          var old = cols.slice();
          var next = p.map(function (w, i) { var c = old[i] || newColumn(w); c.settings.w = w; c.settings.w_tablet = p.length >= 3 ? (p.length === 4 ? 50 : 100) : 100; return c; });
          // widgets of removed columns move to the last column
          old.slice(p.length).forEach(function (c) { next[next.length - 1].widgets = next[next.length - 1].widgets.concat(c.widgets); });
          f.node.columns = next;
          changed({ instant: true }); renderPanel();
        });
        pre.appendChild(b);
      });
      wrap.appendChild(pre);
    }
    if (f.kind === "column") {
      var add = h('<button type="button" class="adm-btn ghost sm"><i class="fa-solid fa-plus"></i> এই কলামে Widget যোগ করুন</button>');
      add.addEventListener("click", function () { state.tab = "add"; renderPanel(); });
      wrap.appendChild(add);
    }
    return wrap;
  }

  /* ---------------- field controls ---------------- */
  function field(f, value, set) {
    var wrap = h('<div class="bld-field t-' + f.type + '"></div>');
    var id = "f" + uid();
    if (f.type !== "checkbox" && f.type !== "note" && f.label) wrap.appendChild(h('<label for="' + id + '">' + esc(f.label) + "</label>"));
    var ctl;
    switch (f.type) {
      case "textarea":
        ctl = h('<textarea id="' + id + '" rows="4"></textarea>');
        ctl.value = value || ""; ctl.addEventListener("input", function () { set(ctl.value); });
        break;
      case "code":
        ctl = codeField(id, f, value, set);
        break;
      case "number":
        ctl = h('<input id="' + id + '" type="number"' + (f.min != null ? ' min="' + f.min + '"' : "") + (f.max != null ? ' max="' + f.max + '"' : "") + ' step="' + (f.step || 1) + '">');
        ctl.value = value; ctl.addEventListener("input", function () { if (ctl.value !== "") set(+ctl.value); });
        break;
      case "select":
        ctl = h('<select id="' + id + '"></select>');
        Object.keys(f.options).forEach(function (k) { var o = document.createElement("option"); o.value = k; o.textContent = f.options[k]; if (String(value) === k) o.selected = true; ctl.appendChild(o); });
        ctl.addEventListener("change", function () { set(ctl.value); });
        break;
      case "checkbox":
        ctl = h('<label class="adm-switch"><input id="' + id + '" type="checkbox"' + (value ? " checked" : "") + '><span></span> ' + esc(f.label) + "</label>");
        ctl.querySelector("input").addEventListener("change", function (e) { set(e.target.checked); });
        break;
      case "color":
        ctl = h('<div class="bld-color"><input type="color"><input type="text" id="' + id + '"></div>');
        var c1 = ctl.children[0], c2 = ctl.children[1]; c1.value = /^#[0-9a-f]{6}$/i.test(value) ? value : "#1d1047"; c2.value = value || "";
        c1.addEventListener("input", function () { c2.value = c1.value; set(c1.value); });
        c2.addEventListener("input", function () { if (/^#[0-9a-f]{3,8}$/i.test(c2.value)) { set(c2.value); } });
        break;
      case "icon":
        ctl = h('<div class="bld-iconin"><span><i></i></span><input id="' + id + '" type="text" placeholder="fa-solid fa-star"></div>');
        ctl.querySelector("input").value = value || ""; ctl.querySelector("i").className = value || "";
        ctl.querySelector("input").addEventListener("input", function (e) { ctl.querySelector("i").className = e.target.value; set(e.target.value.trim()); });
        break;
      case "image":
        ctl = imageField(value, set);
        break;
      case "richtext":
        ctl = richField(value, set);
        break;
      case "repeater":
        ctl = repeaterField(f, value || [], set);
        break;
      case "note":
        ctl = h('<p class="bld-hint"><i class="fa-solid fa-circle-info"></i> ' + esc(f.text || "") + "</p>");
        break;
      default:
        ctl = h('<input id="' + id + '" type="text">'); ctl.value = value == null ? "" : value;
        if (f.type === "url") ctl.placeholder = "/contact অথবা https://…";
        ctl.addEventListener("input", function () { set(ctl.value); });
    }
    wrap.appendChild(ctl);
    if (f.help) wrap.appendChild(h('<small class="bld-help">' + esc(f.help) + "</small>"));
    return wrap;
  }

  function tabKeys(ta) {
    ta.addEventListener("keydown", function (e) {
      if (e.key !== "Tab") return;
      e.preventDefault();
      var s0 = ta.selectionStart, s1 = ta.selectionEnd;
      ta.value = ta.value.slice(0, s0) + "  " + ta.value.slice(s1);
      ta.selectionStart = ta.selectionEnd = s0 + 2;
      ta.dispatchEvent(new Event("input"));
    });
  }
  function codeField(id, f, value, set) {
    var box = h('<div class="bld-code"><textarea id="' + id + '" rows="9" class="mono" spellcheck="false" autocapitalize="off" autocomplete="off" wrap="off"></textarea>' +
      '<button type="button" class="adm-btn ghost sm"><i class="fa-solid fa-up-right-and-down-left-from-center"></i> বড় করে এডিট করুন</button></div>');
    var ta = box.querySelector("textarea");
    ta.value = value || ""; ta.placeholder = { html: "<div>…</div>", css: ".class { color: #fff; }", js: "document.querySelector(…)" }[f.lang] || "";
    tabKeys(ta);
    ta.addEventListener("input", function () { set(ta.value); });
    box.querySelector("button").addEventListener("click", function () {
      var m = h('<div class="bld-codemodal"><div class="bld-codemodal-h"><strong>' + esc(f.label) + '</strong><span class="bld-hint">Tab = ২ স্পেস · লেখার সাথে সাথে প্রিভিউ আপডেট হয়</span><button type="button" class="adm-btn sm"><i class="fa-solid fa-check"></i> শেষ</button></div><textarea class="mono" spellcheck="false" wrap="off"></textarea></div>');
      var big = m.querySelector("textarea"); big.value = ta.value; tabKeys(big);
      big.addEventListener("input", function () { ta.value = big.value; set(big.value); });
      m.querySelector("button").addEventListener("click", function () { m.remove(); });
      document.body.appendChild(m); big.focus();
    });
    return box;
  }

  function imageField(value, set) {
    var box = h('<div class="bld-image"><div class="bld-image-p"></div><div class="bld-image-b"><button type="button" class="adm-btn ghost sm" data-pick><i class="fa-solid fa-photo-film"></i> বেছে নিন</button><button type="button" class="adm-btn ghost sm danger" data-rm><i class="fa-solid fa-xmark"></i></button></div></div>');
    function show(v) {
      var p = box.querySelector(".bld-image-p");
      p.innerHTML = v ? '<img alt="">' : '<i class="fa-regular fa-image"></i>';
      if (v) p.firstChild.src = /^https?:/.test(v) ? v : B.urls.media + "/" + v;
      box.querySelector("[data-rm]").hidden = !v;
    }
    show(value);
    box.querySelector("[data-pick]").addEventListener("click", function () { window.BFIMedia.pick(function (it) { show(it.path); set(it.path); }); });
    box.querySelector(".bld-image-p").addEventListener("click", function () { window.BFIMedia.pick(function (it) { show(it.path); set(it.path); }); });
    box.querySelector("[data-rm]").addEventListener("click", function () { show(""); set(""); });
    return box;
  }

  function richField(value, set) {
    var box = h('<div class="bld-rich"><div class="bld-rich-bar">' +
      '<button type="button" data-c="bold" title="Bold"><i class="fa-solid fa-bold"></i></button><button type="button" data-c="italic" title="Italic"><i class="fa-solid fa-italic"></i></button>' +
      '<button type="button" data-c="underline" title="Underline"><i class="fa-solid fa-underline"></i></button><button type="button" data-b="h2" title="Heading 2">H2</button><button type="button" data-b="h3" title="Heading 3">H3</button><button type="button" data-b="p" title="Paragraph">P</button>' +
      '<button type="button" data-c="insertUnorderedList" title="List"><i class="fa-solid fa-list-ul"></i></button><button type="button" data-c="insertOrderedList" title="Numbered list"><i class="fa-solid fa-list-ol"></i></button>' +
      '<button type="button" data-b="blockquote" title="Quote"><i class="fa-solid fa-quote-left"></i></button><button type="button" data-link title="Link"><i class="fa-solid fa-link"></i></button>' +
      '<button type="button" data-c="unlink" title="Unlink"><i class="fa-solid fa-link-slash"></i></button><button type="button" data-c="removeFormat" title="Format মুছুন"><i class="fa-solid fa-text-slash"></i></button>' +
      '<button type="button" data-html title="HTML"><i class="fa-solid fa-code"></i></button></div>' +
      '<div class="bld-rich-ed" contenteditable="true"></div><textarea class="bld-rich-src mono" hidden spellcheck="false"></textarea></div>');
    var ed = box.querySelector(".bld-rich-ed"), src = box.querySelector(".bld-rich-src");
    ed.innerHTML = value || "";
    var push = function () { set(ed.innerHTML); };
    ed.addEventListener("input", push);
    src.addEventListener("input", function () { ed.innerHTML = src.value; set(src.value); });
    box.querySelector(".bld-rich-bar").addEventListener("mousedown", function (e) { if (e.target.closest("button")) e.preventDefault(); });
    box.querySelector(".bld-rich-bar").addEventListener("click", function (e) {
      var b = e.target.closest("button"); if (!b) return;
      if (b.hasAttribute("data-html")) { src.hidden = !src.hidden; ed.hidden = !src.hidden; src.value = ed.innerHTML; return; }
      ed.focus();
      if (b.dataset.c) document.execCommand(b.dataset.c, false, null);
      if (b.dataset.b) document.execCommand("formatBlock", false, b.dataset.b);
      if (b.hasAttribute("data-link")) { var u = prompt("লিংক (https://… অথবা /contact)", "https://"); if (u) document.execCommand("createLink", false, u); }
      push();
    });
    return box;
  }

  function repeaterField(f, rows, set) {
    var box = h('<div class="bld-rep"></div>'), list = h('<div class="bld-rep-list"></div>');
    var titleField = f.fields.filter(function (x) { return x.type === "text"; })[0] || f.fields[0];
    function title(r) { var k = titleField.key; return (r[k] !== undefined && String(r[k]).replace(/<[^>]+>/g, "").trim().slice(0, 40)) || "আইটেম"; }
    function draw() {
      list.innerHTML = "";
      rows.forEach(function (r, i) {
        var item = h('<div class="bld-rep-item' + (r.__open ? " open" : "") + '"><div class="bld-rep-h"><span>' + esc(title(r)) + '</span><span class="bld-row-a">' +
          '<button type="button" data-a="up"><i class="fa-solid fa-arrow-up"></i></button><button type="button" data-a="down"><i class="fa-solid fa-arrow-down"></i></button>' +
          '<button type="button" data-a="dup"><i class="fa-regular fa-copy"></i></button><button type="button" data-a="del" class="danger"><i class="fa-solid fa-trash"></i></button></span></div><div class="bld-rep-b"></div></div>');
        item.querySelector(".bld-rep-h").addEventListener("click", function (e) {
          var a = e.target.closest("[data-a]");
          if (!a) { r.__open = !r.__open; item.classList.toggle("open"); return; }
          if (a.dataset.a === "up" && i > 0) { rows.splice(i, 1); rows.splice(i - 1, 0, r); }
          if (a.dataset.a === "down" && i < rows.length - 1) { rows.splice(i, 1); rows.splice(i + 1, 0, r); }
          if (a.dataset.a === "dup") { if (f.max && rows.length >= f.max) return toast("সর্বোচ্চ " + f.max + "টা", true); var c = clone(r); rows.splice(i + 1, 0, c); }
          if (a.dataset.a === "del") rows.splice(i, 1);
          commit(); draw();
        });
        var body = item.querySelector(".bld-rep-b");
        f.fields.forEach(function (sf) {
          body.appendChild(field(sf, r[sf.key], function (v) { r[sf.key] = v; commit(); if (sf === titleField) item.querySelector(".bld-rep-h span").textContent = title(r); }));
        });
        list.appendChild(item);
      });
    }
    function commit() { set(rows.map(function (r) { var o = clone(r); delete o.__open; return o; })); }
    var add = h('<button type="button" class="adm-btn ghost sm"><i class="fa-solid fa-plus"></i> নতুন যোগ করুন</button>');
    add.addEventListener("click", function () {
      if (f.max && rows.length >= f.max) return toast("সর্বোচ্চ " + f.max + "টা", true);
      var r = defaults(f.fields); r.__open = true; rows.push(r); commit(); draw();
    });
    box.appendChild(list); box.appendChild(add);
    draw();
    return box;
  }

  /* ---------------- save / publish / revisions ---------------- */
  function payload() {
    var p = {};
    ["title", "slug", "seo_title", "seo_description", "og_image", "noindex", "show_page_header", "header_subtitle", "template", "custom_head", "custom_css", "custom_js"].forEach(function (k) { p[k] = state.page[k]; });
    return { layout: JSON.stringify(state.layout), page: p };
  }
  function busy(btn, on) { btn.disabled = on; btn.classList.toggle("busy", on); }
  function afterSave(res, published) {
    state.layout = res.layout; lastSnap = JSON.stringify(state.layout);
    state.page.status = res.status; state.page.slug = res.slug; state.page.unpublished = !published && res.status === "published";
    state.dirty = false; updateButtons(); toast(res.message);
    try { localStorage.removeItem(backupKey); } catch (e) {}
    $("#bLive").href = res.url;
    if (state.sel && !find(state.sel)) state.sel = null;
    renderPanel();
  }
  $("#bSave").addEventListener("click", function () {
    var b = this; busy(b, true);
    api(B.urls.save, payload()).then(function (r) { afterSave(r, false); }).catch(function (e) { toast(e.message, true); }).then(function () { busy(b, false); });
  });
  $("#bPublish").addEventListener("click", function () {
    var b = this; busy(b, true);
    api(B.urls.publish, payload()).then(function (r) { afterSave(r, true); }).catch(function (e) { toast(e.message, true); }).then(function () { busy(b, false); });
  });
  $("#bUnpublishForm").addEventListener("submit", function (e) { if (!confirm("পেজটা সাইট থেকে সরিয়ে Draft করবেন?")) e.preventDefault(); });

  $("#bRevisions").addEventListener("click", function () {
    fetch(B.urls.revisions, { credentials: "same-origin", headers: { "Accept": "application/json" } }).then(function (r) { return r.json(); }).then(function (list) {
      var m = h('<div class="adm-modal"><div class="adm-modal-box"><h3>আগের ভার্সন</h3><div class="bld-revs"></div><div class="adm-actions"><button type="button" class="adm-btn ghost" data-x>বন্ধ</button></div></div></div>');
      var box = m.querySelector(".bld-revs");
      if (!list.length) box.innerHTML = '<p class="adm-muted">এখনো কোনো সেভ করা ভার্সন নেই।</p>';
      var notes = { "draft saved": "Draft সেভ", "published": "Published", "restored": "ফিরিয়ে আনা" };
      list.forEach(function (rv) {
        var row = h('<div class="bld-rev"><div><strong>' + esc(notes[rv.note] || rv.note) + '</strong><small>' + esc(rv.date) + " · " + esc(rv.when) + (rv.user ? " · " + esc(rv.user) : "") + '</small></div><button type="button" class="adm-btn ghost sm">ফিরিয়ে আনুন</button></div>');
        row.querySelector("button").addEventListener("click", function () {
          if (state.dirty && !confirm("সেভ না করা পরিবর্তন হারিয়ে যাবে। চালিয়ে যাবেন?")) return;
          api(B.urls.restore + "/" + rv.id + "/restore", {}).then(function (res) {
            snapshot(); history.push(lastSnap); state.layout = res.layout; lastSnap = JSON.stringify(res.layout); state.dirty = false; state.page.unpublished = state.page.status === "published";
            m.remove(); renderPanel(); schedulePreview(0); updateButtons(); toast(res.message);
          }).catch(function (e) { toast(e.message, true); });
        });
        box.appendChild(row);
      });
      m.querySelector("[data-x]").addEventListener("click", function () { m.remove(); });
      m.addEventListener("click", function (e) { if (e.target === m) m.remove(); });
      document.body.appendChild(m);
    });
  });

  /* ---------------- devices, keys, leave warning ---------------- */
  document.querySelectorAll("[data-device]").forEach(function (b) {
    b.addEventListener("click", function () {
      document.querySelectorAll("[data-device]").forEach(function (x) { x.classList.toggle("on", x === b); });
      $("#bFrameWrap").className = "bld-frame-wrap dev-" + b.dataset.device;
    });
  });
  $("#bUndo").addEventListener("click", undo);
  $("#bRedo").addEventListener("click", redo);
  document.addEventListener("keydown", function (e) {
    var mod = e.ctrlKey || e.metaKey;
    if (mod && e.key.toLowerCase() === "s") { e.preventDefault(); $("#bSave").click(); }
    var typing = /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) || document.activeElement.isContentEditable;
    if (mod && e.key.toLowerCase() === "z" && !typing) { e.preventDefault(); if (e.shiftKey) redo(); else undo(); }
  });
  window.addEventListener("beforeunload", function (e) { if (state.dirty) { e.preventDefault(); e.returnValue = ""; } });
  document.querySelector(".bld-more > button").addEventListener("click", function (e) { e.stopPropagation(); this.parentNode.classList.toggle("open"); });
  document.addEventListener("click", function () { var m = document.querySelector(".bld-more.open"); if (m) m.classList.remove("open"); });

  /* ---------------- safety: local backup + autosave ---------------- */
  var backupKey = "bfi-page-backup:" + B.urls.save;
  function backup() { try { localStorage.setItem(backupKey, JSON.stringify({ t: Date.now(), layout: state.layout, page: state.page })); } catch (e) {} }
  setInterval(function () { if (state.dirty) backup(); }, 5000);
  try {
    var bk = JSON.parse(localStorage.getItem(backupKey) || "null");
    if (bk && JSON.stringify(bk.layout) !== JSON.stringify(state.layout) && confirm("এই পেজের সেভ না হওয়া কিছু কাজ এই ব্রাউজারে পাওয়া গেছে (" + new Date(bk.t).toLocaleString() + ")। সেটা ফিরিয়ে আনবেন?")) {
      state.layout = bk.layout; Object.keys(bk.page || {}).forEach(function (k) { if (k !== "status" && k !== "unpublished") state.page[k] = bk.page[k]; }); state.dirty = true;
    } else if (bk) { localStorage.removeItem(backupKey); }
  } catch (e) {}
  var saving = false;
  setInterval(function () {
    if (!state.dirty || saving || document.hidden) return;
    saving = true;
    api(B.urls.save, payload()).then(function (r) {
      if (JSON.stringify(state.layout) === JSON.stringify(r.layout)) { state.dirty = false; }
      state.page.status = r.status; state.page.unpublished = r.status === "published";
      updateButtons(); toast("স্বয়ংক্রিয়ভাবে Draft সেভ হয়েছে");
      try { localStorage.removeItem(backupKey); } catch (e) {}
    }).catch(function (e) { toast("স্বয়ংক্রিয় সেভ হয়নি: " + e.message, true); }).then(function () { saving = false; });
  }, 60000);

  renderPanel(); updateButtons(); refreshPreview();
  if (!state.layout.sections.length) toast("বাঁ পাশ থেকে Widget বা Section লেআউট বেছে নিয়ে শুরু করুন");
})();
