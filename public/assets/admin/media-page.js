/* Brown Fox IT – Media library page */
(function () {
  "use strict";
  var api = window.BFIMedia, base = window.BFI_MEDIA_PAGE.update;
  var grid = document.getElementById("mlGrid"), pager = document.getElementById("mlPager");
  var modal = document.getElementById("mlEdit"), page = 1, q = "", current = null;

  function kb(n) { return n > 1048576 ? (n / 1048576).toFixed(1) + " MB" : Math.round(n / 1024) + " KB"; }

  function load() {
    api.request("GET", window.BFI_MEDIA.list + "?page=" + page + "&q=" + encodeURIComponent(q)).then(function (res) {
      grid.innerHTML = "";
      if (!res.total) grid.innerHTML = '<p class="adm-muted" style="grid-column:1/-1;text-align:center;padding:40px">কোনো ছবি নেই।</p>';
      res.data.forEach(function (it) {
        var b = document.createElement("button");
        b.type = "button"; b.className = "ml-item"; b.title = it.path;
        b.innerHTML = '<img loading="lazy" alt=""><span></span>' + (it.alt ? "" : '<em title="Alt লেখা নেই">ALT?</em>');
        b.querySelector("img").src = it.url; b.querySelector("span").textContent = it.alt || it.original_name || "";
        b.addEventListener("click", function () { open(it); });
        grid.appendChild(b);
      });
      pager.innerHTML = res.last_page > 1 ? '<button class="adm-icon-btn" data-p="-1"' + (res.current_page <= 1 ? " disabled" : "") + '><i class="fa-solid fa-chevron-left"></i></button><span class="info">পেজ ' + res.current_page + " / " + res.last_page + '</span><button class="adm-icon-btn" data-p="1"' + (res.current_page >= res.last_page ? " disabled" : "") + '><i class="fa-solid fa-chevron-right"></i></button>' : "";
    });
  }
  pager.addEventListener("click", function (e) { var b = e.target.closest("[data-p]"); if (b && !b.disabled) { page += +b.dataset.p; load(); } });

  function open(it) {
    current = it;
    modal.querySelector("img").src = it.url;
    modal.querySelector("[data-name]").textContent = it.original_name || it.path;
    modal.querySelector("[data-meta]").textContent = (it.width ? it.width + "×" + it.height + " px · " : "") + kb(it.size || 0) + " · " + it.path;
    modal.querySelector("[data-alt]").value = it.alt || "";
    modal.querySelector("[data-caption]").value = it.caption || "";
    modal.querySelector("[data-url]").value = it.url;
    modal.hidden = false;
  }
  modal.querySelector("[data-save]").addEventListener("click", function () {
    var fd = new FormData();
    fd.append("_method", "PUT"); fd.append("alt", modal.querySelector("[data-alt]").value); fd.append("caption", modal.querySelector("[data-caption]").value);
    api.request("POST", base + "/" + current.id, fd).then(function () { modal.hidden = true; load(); });
  });
  modal.querySelector("[data-delete]").addEventListener("click", function () {
    if (!confirm("ছবিটা মুছে ফেলবেন? যেসব পেজে এটা ব্যবহার হয়েছে সেখানে আর দেখাবে না।")) return;
    var fd = new FormData(); fd.append("_method", "DELETE");
    api.request("POST", base + "/" + current.id, fd).then(function () { modal.hidden = true; load(); });
  });

  function up(files) {
    var drop = document.getElementById("mlDrop");
    drop.textContent = "আপলোড হচ্ছে…";
    api.upload(files).then(function (res) {
      drop.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> ' + (res.errors.length ? res.errors.join(" · ") : res.items.length + "টা ছবি আপলোড হয়েছে। আরও টেনে আনুন।");
      page = 1; load();
    }).catch(function (e) { drop.textContent = e.message; });
  }
  document.getElementById("mlUpload").addEventListener("change", function (e) { up(e.target.files); e.target.value = ""; });
  var drop = document.getElementById("mlDrop");
  ["dragenter", "dragover"].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add("on"); }); });
  ["dragleave", "drop"].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.remove("on"); }); });
  drop.addEventListener("drop", function (e) { e.preventDefault(); up(e.dataTransfer.files); });
  var t; document.getElementById("mlSearch").addEventListener("input", function (e) { clearTimeout(t); t = setTimeout(function () { q = e.target.value; page = 1; load(); }, 300); });
  load();
})();
