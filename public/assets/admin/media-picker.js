/* Brown Fox IT – Media library picker (used by the page builder and admin forms)
   BFIMedia.pick(function (item) { item.path, item.url, item.alt }) */
(function () {
  "use strict";
  var cfg = window.BFI_MEDIA || {};

  function el(html) { var d = document.createElement("div"); d.innerHTML = html.trim(); return d.firstChild; }

  function request(method, url, body) {
    return fetch(url, {
      method: method, body: body, credentials: "same-origin",
      headers: { "X-CSRF-TOKEN": cfg.csrf, "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" }
    }).then(function (r) { return r.json().then(function (j) { if (!r.ok && !j.items) throw new Error(j.message || (j.errors || []).join("\n") || "Error " + r.status); return j; }); });
  }

  function upload(files) {
    var fd = new FormData();
    Array.prototype.forEach.call(files, function (f) { fd.append("files[]", f); });
    return request("POST", cfg.upload, fd);
  }

  function pick(onSelect) {
    var page = 1, q = "", selected = null;
    var m = el('<div class="adm-modal mp"><div class="adm-modal-box xwide">' +
      '<div class="mp-head"><h3>Media library</h3><div class="adm-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="খুঁজুন…" class="mp-q"></div>' +
      '<label class="adm-btn sm"><i class="fa-solid fa-upload"></i> আপলোড<input type="file" accept="image/*" multiple hidden class="mp-up"></label>' +
      '<button type="button" class="adm-icon-btn mp-x" aria-label="বন্ধ"><i class="fa-solid fa-xmark"></i></button></div>' +
      '<div class="mp-msg"></div><div class="ml-grid mp-grid"></div>' +
      '<div class="mp-foot"><button type="button" class="adm-btn ghost sm mp-more">আরও দেখুন</button><span class="spacer"></span>' +
      '<button type="button" class="adm-btn mp-ok" disabled><i class="fa-solid fa-check"></i> এই ছবিটা নিন</button></div></div></div>');
    document.body.appendChild(m);
    var grid = m.querySelector(".mp-grid"), ok = m.querySelector(".mp-ok"), more = m.querySelector(".mp-more"), msg = m.querySelector(".mp-msg");

    function card(it) {
      var c = el('<button type="button" class="ml-item" title=""><img loading="lazy" alt=""><span></span></button>');
      c.querySelector("img").src = it.url; c.querySelector("span").textContent = it.alt || it.original_name || "";
      c.title = it.path;
      c.addEventListener("click", function () {
        grid.querySelectorAll(".ml-item.sel").forEach(function (x) { x.classList.remove("sel"); });
        c.classList.add("sel"); selected = it; ok.disabled = false;
      });
      c.addEventListener("dblclick", function () { selected = it; done(); });
      return c;
    }
    function load(reset) {
      if (reset) { page = 1; grid.innerHTML = ""; }
      request("GET", cfg.list + "?page=" + page + "&q=" + encodeURIComponent(q)).then(function (res) {
        res.data.forEach(function (it) { grid.appendChild(card(it)); });
        more.hidden = res.current_page >= res.last_page;
        if (!res.total) grid.innerHTML = '<p class="adm-muted" style="grid-column:1/-1;text-align:center;padding:30px">কোনো ছবি নেই। ওপরের "আপলোড" দিয়ে যোগ করুন।</p>';
      });
    }
    function close() { m.remove(); document.removeEventListener("keydown", key); }
    function done() { if (selected) { onSelect(selected); close(); } }
    function key(e) { if (e.key === "Escape") close(); }

    m.querySelector(".mp-x").addEventListener("click", close);
    m.addEventListener("click", function (e) { if (e.target === m) close(); });
    document.addEventListener("keydown", key);
    ok.addEventListener("click", done);
    more.addEventListener("click", function () { page++; load(false); });
    var t; m.querySelector(".mp-q").addEventListener("input", function (e) { clearTimeout(t); t = setTimeout(function () { q = e.target.value; load(true); }, 300); });
    m.querySelector(".mp-up").addEventListener("change", function (e) {
      msg.textContent = "আপলোড হচ্ছে…"; msg.className = "mp-msg";
      upload(e.target.files).then(function (res) {
        msg.textContent = res.errors.length ? res.errors.join(" · ") : res.items.length + "টা ছবি আপলোড হয়েছে";
        msg.className = "mp-msg " + (res.errors.length ? "err" : "ok");
        res.items.slice().reverse().forEach(function (it) { grid.insertBefore(card(it), grid.firstChild); });
        if (res.items[0]) grid.firstChild.click();
      }).catch(function (err) { msg.textContent = err.message; msg.className = "mp-msg err"; });
      e.target.value = "";
    });
    load(true);
  }

  window.BFIMedia = { pick: pick, upload: upload, request: request };
})();
