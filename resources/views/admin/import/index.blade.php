@extends('admin.layout')
@section('title', 'WordPress থেকে আনুন')
@section('content')
    <div class="adm-card">
        <h2 class="adm-h">পুরোনো সাইট থেকে ব্লগ, পোর্টফোলিও ও প্রোডাক্ট আনুন</h2>
        <p class="adm-muted">পুরোনো WordPress সাইটের পাবলিক REST API থেকে কনটেন্ট কপি হবে। ছবি এই সাইটে ডাউনলোড হবে। যা আগে থেকে আছে (একই URL) তা বদলানো হবে না — তাই বারবার চালালেও সমস্যা নেই।
            <strong>প্রোডাক্ট সবসময় Draft হিসেবে আসবে</strong> — দাম, বিবরণ ও ডাউনলোড ফাইল দেখে নিজে Publish করবেন।</p>
        <div class="imp-form">
            <label>সাইটের ঠিকানা
                <input id="imp-site" list="imp-sites" value="{{ $sites[0] ?? '' }}" placeholder="https://example.com">
                <datalist id="imp-sites">@foreach ($sites as $s)<option value="{{ $s }}">@endforeach</datalist>
            </label>
            <button type="button" class="adm-btn ghost" id="imp-discover"><i class="fa-solid fa-magnifying-glass"></i> কী কী আছে দেখুন</button>
        </div>
        <div id="imp-found" hidden>
            <div class="imp-kinds">
                <label><input type="checkbox" value="posts" checked> ব্লগ পোস্ট <span data-count="posts"></span></label>
                <span id="imp-portfolio"></span>
                <label><input type="checkbox" value="products"> প্রোডাক্ট (Draft) <span data-count="products"></span></label>
            </div>
            <div id="imp-errors" class="adm-muted"></div>
            <button type="button" class="adm-btn" id="imp-run"><i class="fa-solid fa-file-import"></i> ইমপোর্ট শুরু করুন</button>
        </div>
        <pre id="imp-progress" class="imp-progress" hidden></pre>
    </div>

    <div class="adm-card">
        <div class="adm-toolbar">
            <h2 class="adm-h" style="margin:0">ইনভেন্টরি / রিপোর্ট</h2>
            <form method="GET" class="imp-filter">
                <select name="kind" onchange="this.form.submit()"><option value="">সব ধরন</option>@foreach (['posts' => 'ব্লগ', 'portfolio' => 'পোর্টফোলিও', 'products' => 'প্রোডাক্ট'] as $k => $l)<option value="{{ $k }}" @selected(request('kind') === $k)>{{ $l }}</option>@endforeach</select>
                <select name="status" onchange="this.form.submit()"><option value="">সব অবস্থা</option>@foreach (['imported', 'missing_images', 'exists', 'failed'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select>
            </form>
        </div>
        @if ($stats->isNotEmpty())
            <div class="imp-stats">@foreach ($stats as $kind => $row)<div><strong>{{ $kind }}</strong>@foreach ($row as $st => $n)<span class="imp-st imp-st-{{ $st }}">{{ $st }}: {{ $n }}</span>@endforeach</div>@endforeach</div>
        @endif
        @if ($logs->isEmpty())
            <div class="adm-empty"><i class="fa-solid fa-file-import"></i><p>এখনো কিছু ইমপোর্ট হয়নি।</p></div>
        @else
            <div class="adm-table-wrap"><table class="adm-table">
                <thead><tr><th>শিরোনাম</th><th>ধরন</th><th>অবস্থা</th><th>নোট</th><th></th></tr></thead>
                <tbody>@foreach ($logs as $l)
                    @php($res = ['App\Models\Post' => 'posts', 'App\Models\Project' => 'projects', 'App\Models\Product' => 'products'][$l->target_type] ?? null)
                    <tr>
                        <td><strong>{{ $l->title }}</strong>@if ($l->source_url)<br><a class="adm-muted" href="{{ $l->source_url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($l->source_url, 60) }}</a>@endif</td>
                        <td>{{ $l->kind }}</td>
                        <td><span class="imp-st imp-st-{{ $l->status }}">{{ $l->status }}</span></td>
                        <td class="adm-muted" style="max-width:340px">{{ $l->notes }}</td>
                        <td>@if ($res && $l->target_id)<a class="adm-btn ghost sm" href="{{ route('admin.resource.edit', [$res, $l->target_id]) }}">এডিট</a>@endif</td>
                    </tr>
                @endforeach</tbody>
            </table></div>
            {{ $logs->links('admin.pagination') }}
        @endif
    </div>

    <script>
    (() => {
        const $ = (s) => document.querySelector(s), token = @json(csrf_token());
        const post = async (url, data) => {
            const r = await fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token}, body: JSON.stringify(data)});
            const j = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(j.message || ('HTTP ' + r.status));
            return j;
        };
        const log = (t) => { const p = $('#imp-progress'); p.hidden = false; p.textContent += t + '\n'; p.scrollTop = p.scrollHeight; };
        $('#imp-discover').addEventListener('click', async (e) => {
            e.target.disabled = true; $('#imp-progress').textContent = '';
            try {
                const d = await post(@json(route('admin.import.discover')), {site: $('#imp-site').value});
                $('[data-count=posts]').textContent = '(' + d.posts + ')';
                $('[data-count=products]').textContent = '(' + d.products + ')';
                $('#imp-portfolio').innerHTML = (d.portfolio || []).map(p => `<label><input type="checkbox" value="portfolio" data-path="${p.path}" checked> পোর্টফোলিও: ${p.name} (${p.count})</label>`).join('') || '<span class="adm-muted">পোর্টফোলিও টাইপ পাওয়া যায়নি</span>';
                $('#imp-errors').textContent = (d.errors || []).join(' · ');
                $('#imp-found').hidden = false;
            } catch (err) { log('ত্রুটি: ' + err.message); }
            e.target.disabled = false;
        });
        $('#imp-run').addEventListener('click', async (e) => {
            e.target.disabled = true;
            const site = $('#imp-site').value;
            for (const box of document.querySelectorAll('#imp-found input[type=checkbox]:checked')) {
                const kind = box.value, path = box.dataset.path || null;
                let page = 1, pages = 1;
                do {
                    try {
                        const r = await post(@json(route('admin.import.run')), {site, kind, page, path});
                        pages = r.pages || 0;
                        log(`${kind} পেজ ${page}/${pages || 1}: নতুন ${r.imported}, বাদ ${r.skipped}, ব্যর্থ ${r.failed} ${(r.messages || []).join(' ')}`);
                    } catch (err) { log(`${kind} পেজ ${page}: ত্রুটি ${err.message}`); break; }
                    page++;
                } while (page <= pages);
            }
            log('শেষ। নিচের রিপোর্ট দেখতে পেজ রিফ্রেশ করুন।');
            e.target.disabled = false;
        });
    })();
    </script>
@endsection
