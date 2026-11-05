/* Brown Fox IT – Admin panel scripts (no libraries) */
(function () {
  "use strict";

  /* ---------- mobile sidebar ---------- */
  var side = document.getElementById("side"), overlay = document.getElementById("overlay"), burger = document.getElementById("burger");
  function toggleSide(open) { if (!side) return; side.classList.toggle("open", open); overlay.classList.toggle("show", open); }
  if (burger) burger.addEventListener("click", function () { toggleSide(!side.classList.contains("open")); });
  if (overlay) overlay.addEventListener("click", function () { toggleSide(false); });

  /* ---------- show / hide password ---------- */
  document.addEventListener("click", function (e) {
    var b = e.target.closest("[data-toggle-pass]");
    if (!b) return;
    var input = document.getElementById(b.getAttribute("data-toggle-pass"));
    input.type = input.type === "password" ? "text" : "password";
    b.innerHTML = input.type === "password" ? '<i class="fa-regular fa-eye"></i>' : '<i class="fa-regular fa-eye-slash"></i>';
  });

  /* ---------- simple modals: data-open="#id", data-close ---------- */
  document.addEventListener("click", function (e) {
    var o = e.target.closest("[data-open]");
    if (o) { var m = document.querySelector(o.getAttribute("data-open")); if (m) { m.hidden = false; var f = m.querySelector("input:not([type=hidden]),select"); if (f) f.focus(); } return; }
    var c = e.target.closest("[data-close]");
    if (c) { c.closest(".adm-modal").hidden = true; return; }
    if (e.target.classList && e.target.classList.contains("adm-modal") && e.target.id) e.target.hidden = true;
  });

  /* ---------- pick an image from the media library (admin forms) ---------- */
  document.addEventListener("click", function (e) {
    var b = e.target.closest("[data-image-library]");
    if (!b || !window.BFIMedia) return;
    var box = b.closest("[data-image]");
    window.BFIMedia.pick(function (it) {
      box.querySelector("[data-image-path]").value = it.path;
      box.querySelector("[data-image-file]").value = "";
      var img = box.querySelector("[data-image-img]"); img.src = it.url; img.hidden = false;
      box.querySelector("[data-image-empty]").hidden = true;
      box.querySelector("[data-image-clear]").hidden = false;
      box.querySelector("[data-image-name]").textContent = it.path.split("/").pop();
      box.closest("form").dispatchEvent(new Event("change"));
    });
  });

  /* ---------- confirm before delete ---------- */
  document.addEventListener("submit", function (e) {
    var msg = e.target.getAttribute("data-confirm");
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  /* ---------- warn about unsaved changes ---------- */
  document.querySelectorAll("form[data-unsaved]").forEach(function (form) {
    var dirty = false;
    form.addEventListener("input", function () { dirty = true; });
    form.addEventListener("change", function () { dirty = true; });
    form.addEventListener("submit", function () { dirty = false; });
    window.addEventListener("beforeunload", function (e) { if (dirty) { e.preventDefault(); e.returnValue = ""; } });
  });

  /* ---------- icon preview ---------- */
  document.addEventListener("input", function (e) {
    if (!e.target.matches("[data-icon-input]")) return;
    var prev = e.target.parentNode.querySelector("[data-icon-preview]");
    if (prev) prev.className = e.target.value;
  });

  /* ---------- single image ---------- */
  document.addEventListener("change", function (e) {
    var input = e.target;
    if (input.matches("[data-image-file]") && input.files && input.files[0]) {
      var box = input.closest("[data-image]");
      var img = box.querySelector("[data-image-img]");
      img.src = URL.createObjectURL(input.files[0]);
      img.hidden = false;
      box.querySelector("[data-image-empty]").hidden = true;
      box.querySelector("[data-image-clear]").hidden = false;
      box.querySelector("[data-image-name]").textContent = input.files[0].name + " (সেভ করলে আপলোড হবে)";
    }
    if (input.matches("[data-images-file]") && input.files) {
      var wrap = input.closest("[data-images]"), add = input.closest(".adm-images-add");
      wrap.querySelectorAll(".adm-images-item.new").forEach(function (n) { n.remove(); });
      Array.prototype.forEach.call(input.files, function (f) {
        var d = document.createElement("div");
        d.className = "adm-images-item new";
        d.innerHTML = '<img alt="">';
        d.querySelector("img").src = URL.createObjectURL(f);
        wrap.insertBefore(d, add);
      });
    }
  });

  document.addEventListener("click", function (e) {
    var clear = e.target.closest("[data-image-clear]");
    if (clear) {
      var box = clear.closest("[data-image]");
      box.querySelector("[data-image-path]").value = "";
      var file = box.querySelector("[data-image-file]");
      file.value = "";
      box.querySelector("[data-image-img]").hidden = true;
      box.querySelector("[data-image-empty]").hidden = false;
      box.querySelector("[data-image-name]").textContent = "কোনো ছবি নেই";
      clear.hidden = true;
      clear.closest("form").dispatchEvent(new Event("change"));
      return;
    }
    var rm = e.target.closest("[data-images-remove]");
    if (rm) { rm.closest(".adm-images-item").remove(); return; }

    /* ---------- repeater ---------- */
    var addBtn = e.target.closest("[data-row-add]");
    if (addBtn) {
      var rep = addBtn.closest("[data-repeater]");
      var tpl = rep.querySelector(":scope > template[data-row-template]");
      var key = "n" + Date.now().toString(36) + Math.floor(Math.random() * 1000);
      var html = tpl.innerHTML.split(rep.getAttribute("data-placeholder")).join(key);
      var holder = document.createElement("div");
      holder.innerHTML = html.trim();
      var row = holder.firstElementChild;
      rep.querySelector(":scope > [data-rows]").appendChild(row);
      var first = row.querySelector("input:not([type=hidden]), textarea, select");
      if (first) first.focus();
      return;
    }
    var row = e.target.closest("[data-row]");
    if (!row) return;
    if (e.target.closest("[data-row-remove]")) {
      if (window.confirm("এই আইটেমটা মুছে ফেলবেন?")) { var f = row.closest("form"); row.remove(); f.dispatchEvent(new Event("change")); }
    } else if (e.target.closest("[data-row-up]")) {
      if (row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
    } else if (e.target.closest("[data-row-down]")) {
      if (row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling, row);
    } else if (e.target.closest("[data-row-toggle]")) {
      row.classList.toggle("collapsed");
    }
  });

  // keep the row title in sync with its first field
  document.addEventListener("input", function (e) {
    var body = e.target.closest(".adm-row-body");
    if (!body) return;
    var first = body.querySelector(":scope > .adm-field input:not([type=hidden]), :scope > .adm-field textarea");
    if (first === e.target) {
      var title = body.parentNode.querySelector(":scope > .adm-row-bar [data-row-title]");
      if (title) title.textContent = e.target.value || "নতুন";
    }
  });

  // long lists start collapsed so the page stays short
  document.querySelectorAll("[data-rows]").forEach(function (rows) {
    if (rows.children.length > 3) Array.prototype.forEach.call(rows.children, function (r) { r.classList.add("collapsed"); });
  });
})();
