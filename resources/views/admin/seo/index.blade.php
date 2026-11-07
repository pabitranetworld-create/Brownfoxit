@extends('admin.layout')
@section('title', 'SEO ড্যাশবোর্ড')
@section('content')
    @unless ($visible)
        <div class="adm-alert warn"><i class="fa-solid fa-eye-slash"></i>
            <div><strong>এই ডোমেইন এখন search engine থেকে লুকানো (noindex)।</strong> {{ $production ? 'আসল ডোমেইন '.$production.' — সেখানে সাইট চালু হলে স্বয়ংক্রিয়ভাবে দেখা যাবে।' : '' }} Draft ডোমেইনের জন্য এটাই ঠিক। <a href="{{ route('admin.settings.edit', 'seo') }}">SEO সেটিংস</a></div>
        </div>
    @endunless

    <div class="adm-card seo-run">
        <div>
            <h2 class="adm-h" style="margin:0">সাইট চেক (Audit)</h2>
            <p class="adm-muted">প্রতিটি পাবলিক পেজ Google যেভাবে দেখে সেভাবে খুলে টাইটেল, description, canonical, H1, ছবির alt, structured data আর ভাঙা লিংক পরীক্ষা করে। এগুলো সাইটের নিজের চেক — Google-এর র‍্যাংকিং বা Search Console-এর ডেটা নয়।
                @if ($lastAudit) শেষ চেক: <strong>{{ $lastAudit->diffForHumans() }}</strong>@else এখনো চেক হয়নি।@endif</p>
        </div>
        <div class="seo-run-btns">
            <button type="button" class="adm-btn" data-run="{{ route('admin.seo.audit') }}"><i class="fa-solid fa-magnifying-glass-chart"></i> সব পেজ চেক করুন</button>
            <button type="button" class="adm-btn ghost" data-run="{{ route('admin.seo.audit.links') }}" @disabled(! $counts['checked'])><i class="fa-solid fa-link-slash"></i> ভাঙা লিংক খুঁজুন</button>
        </div>
        <div class="seo-progress" hidden><span></span><em></em></div>
    </div>

    <div class="adm-stats seo-stats">
        <div class="adm-card"><small>পাবলিক URL</small><strong>{{ $counts['public'] }}</strong><span class="adm-muted">মোট {{ $counts['all'] }} (খসড়াসহ)</span></div>
        <div class="adm-card"><small>Google-এ দেখানোর যোগ্য</small><strong>{{ $counts['indexable'] }}</strong><span class="adm-muted">{{ $counts['noindex'] }}টি noindex/অন্য canonical</span></div>
        <div class="adm-card"><small>চেক করা পেজ</small><strong>{{ $counts['checked'] }}</strong></div>
        <div @class(['adm-card', 'bad' => $counts['errors']])><small>খোলে না (404/500)</small><strong>{{ $counts['errors'] }}</strong></div>
        <div @class(['adm-card', 'bad' => $counts['missing_desc']])><small>Description নেই</small><strong>{{ $counts['missing_desc'] }}</strong></div>
        <div @class(['adm-card', 'bad' => $counts['h1']])><small>H1 নেই / একাধিক</small><strong>{{ $counts['h1'] }}</strong></div>
        <div @class(['adm-card', 'bad' => $counts['img_alt']])><small>Alt ছাড়া ছবি</small><strong>{{ $counts['img_alt'] }}</strong></div>
        <div @class(['adm-card', 'bad' => $counts['canonical'] || $counts['missing_title'] || $counts['schema']])><small>Title/Canonical/Schema ত্রুটি</small><strong>{{ $counts['missing_title'] + $counts['canonical'] + $counts['schema'] }}</strong></div>
    </div>

    <div class="seo-cols">
        <div class="adm-card">
            <h3 class="adm-card-h">মনোযোগ দরকার</h3>
            @forelse ($attention as $a)
                @php($row = $rowsByKey[$a->subject_type.':'.$a->subject_key] ?? null)
                <div class="seo-att">
                    <a href="{{ route('admin.seo.edit', [$a->subject_type, $a->subject_key]) }}"><strong>{{ $row['label'] ?? $a->url }}</strong></a> <span class="adm-muted">{{ $a->url }}</span>
                    <ul>@foreach (collect($a->issues)->whereIn('level', ['error', 'warn'])->take(4) as $i)<li class="lv-{{ $i['level'] }}">{{ $i['text'] }}</li>@endforeach</ul>
                </div>
            @empty
                <p class="adm-muted">{{ $counts['checked'] ? 'বড় কোনো সমস্যা পাওয়া যায়নি। 👍' : 'আগে "সব পেজ চেক করুন" চাপুন।' }}</p>
            @endforelse
            <a class="adm-btn ghost sm" href="{{ route('admin.seo.pages', ['show' => 'problems']) }}">সব সমস্যা দেখুন</a>
        </div>

        <div>
            <div class="adm-card">
                <h3 class="adm-card-h">Sitemap</h3>
                @if ($visible)
                    <p><a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener">{{ url('/sitemap.xml') }}</a> · <a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener">robots.txt</a></p>
                @else
                    <p class="adm-muted">ডোমেইন লুকানো থাকায় sitemap বন্ধ। <a href="{{ url('/sitemap.xml?preview=1') }}" target="_blank" rel="noopener">Preview দেখুন</a> · <a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener">robots.txt</a></p>
                @endif
                <table class="adm-table compact">@foreach ($sitemap as $sec => $n)<tr><td>{{ $sec }}</td><td>{{ $n ?? '—' }} URL</td></tr>@endforeach</table>
                <p class="adm-muted">নতুন কিছু Publish করলে sitemap নিজে থেকেই আপডেট হয়।</p>
            </div>

            <div class="adm-card">
                <h3 class="adm-card-h">ডুপ্লিকেট টাইটেল / Description</h3>
                @forelse ($dupTitles as $t => $g)<p class="seo-dup"><strong>Title:</strong> {{ \Illuminate\Support\Str::limit($g->first()->title, 70) }} <span class="adm-muted">— {{ $g->pluck('url')->implode(', ') }}</span></p>@empty<p class="adm-muted">একই টাইটেল কোথাও নেই।</p>@endforelse
                @foreach ($dupDescs as $d => $g)<p class="seo-dup"><strong>Description:</strong> {{ \Illuminate\Support\Str::limit($g->first()->description, 70) }} <span class="adm-muted">— {{ $g->pluck('url')->implode(', ') }}</span></p>@endforeach
            </div>

            <div class="adm-card">
                <h3 class="adm-card-h">ভাঙা লিংক @if ($linksChecked)<small class="adm-muted">({{ $linksChecked }})</small>@endif</h3>
                @if (! $linksChecked)<p class="adm-muted">এখনো চেক হয়নি।</p>
                @elseif (! $broken)<p class="adm-muted">কোনো ভাঙা ভেতরের লিংক পাওয়া যায়নি।</p>
                @else
                    @foreach ($broken as $b)<p class="seo-dup"><span class="lv-error">{{ $b['status'] }}</span> <code>{{ $b['path'] }}</code> <span class="adm-muted">— আছে: {{ implode(', ', array_slice($b['on'], 0, 4)) }}</span> <a href="{{ route('admin.redirects.index', ['from' => $b['path']]) }}">redirect দিন</a></p>@endforeach
                @endif
            </div>

            <div class="adm-card">
                <h3 class="adm-card-h">Redirect সমস্যা</h3>
                @forelse ($redirectProblems as [$r, $why])<p class="seo-dup"><code>{{ $r->from_path }}</code> → {{ $r->to_url }} <span class="lv-warn">{{ $why }}</span></p>@empty<p class="adm-muted">কোনো loop বা chain নেই।</p>@endforelse
                <a class="adm-btn ghost sm" href="{{ route('admin.redirects.index') }}">Redirects</a>
            </div>

            <div class="adm-card">
                <h3 class="adm-card-h">সম্প্রতি বদলানো</h3>
                @foreach ($recent as $r)<p class="seo-dup"><a href="{{ route('admin.seo.edit', [$r['type'], $r['key']]) }}">{{ $r['label'] }}</a> <span class="adm-muted">{{ $r['modified']?->diffForHumans() }}</span></p>@endforeach
            </div>
        </div>
    </div>

    <details class="adm-card seo-help">
        <summary><strong>Google Search Console ও Bing — কীভাবে যুক্ত করবেন (আসল ডোমেইনে চালুর পর)</strong></summary>
        <ol>
            <li><a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a> খুলুন → <em>Add property</em> → <em>URL prefix</em> → https://{{ $production ?: 'yourdomain.com' }}/</li>
            <li>যাচাইয়ের পদ্ধতি <em>HTML tag</em> বেছে নিন; content="…" এর ভেতরের কোডটা <a href="{{ route('admin.settings.edit', 'seo_integrations') }}">Google ও Bing</a> পেজে বসিয়ে সেভ করুন, তারপর Search Console-এ <em>Verify</em> চাপুন। (DNS দিয়ে যাচাই করলে এটা লাগে না।)</li>
            <li>Search Console → <em>Sitemaps</em> → <code>sitemap.xml</code> লিখে Submit করুন।</li>
            <li>কোনো পেজ Google-এ আছে কিনা দেখতে উপরের সার্চ বক্সে (URL Inspection) পুরো লিংক দিন; নতুন পেজ হলে <em>Request indexing</em> চাপুন। এই সাইট নিজে থেকে Google-এ URL জমা দেয় না — সেটা Search Console থেকেই করতে হয়।</li>
            <li>নিয়মিত দেখুন: <em>Pages</em> (কোন পেজ index হয়নি ও কেন), <em>Core Web Vitals</em>, <em>Enhancements</em> (structured data)। ক্লিক/impression শুধু Search Console-এ পাওয়া যায় — এই ড্যাশবোর্ড তা দেখায় না।</li>
            <li><a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">Bing Webmaster Tools</a> → <em>Import from Google Search Console</em> — সবচেয়ে সহজ; নইলে Meta tag কোডটা একই পেজে দিন।</li>
            <li>গতি মাপতে <a href="https://pagespeed.web.dev/" target="_blank" rel="noopener">PageSpeed Insights</a> ব্যবহার করুন (আসল ডোমেইনে)।</li>
        </ol>
    </details>

    <details class="adm-card seo-help">
        <summary><strong>মূল ডোমেইনে চালুর চেকলিস্ট</strong></summary>
        <ol>
            <li>SEO সেটিংসে আসল ডোমেইন ঠিক আছে কিনা দেখুন (brownfoxit.com বা www.brownfoxit.com — যেটা চান)।</li>
            <li>পুরোনো WordPress সাইট বন্ধ করার <strong>আগে</strong> Redirects → "পুরোনো সাইটের ঠিকানা আনুন" চালিয়ে redirect তৈরি করুন।</li>
            <li>SSL (https) চালু আছে কিনা দেখুন; তারপর সাইট খুলে http:// আর www. দিয়ে খুললে ঠিক ঠিকানায় যাচ্ছে কিনা দেখুন।</li>
            <li>এই পেজে "সব পেজ চেক করুন" আর "ভাঙা লিংক খুঁজুন" চালান।</li>
            <li>Search Console-এ property যোগ করে sitemap জমা দিন; Bing-এ import করুন।</li>
            <li>আইনি পেজগুলো (Privacy, Terms, Refund) দেখে Publish করুন।</li>
            <li>প্রথম ২–৪ সপ্তাহ Search Console-এর Pages রিপোর্টে 404 বা "redirect error" আছে কিনা দেখুন।</li>
        </ol>
    </details>

    <script>
    (() => {
        const token = @json(csrf_token()), box = document.querySelector('.seo-progress'), bar = box.querySelector('span'), msg = box.querySelector('em');
        document.querySelectorAll('[data-run]').forEach(btn => btn.addEventListener('click', async () => {
            document.querySelectorAll('[data-run]').forEach(b => b.disabled = true);
            box.hidden = false; let offset = 0, total = 1;
            try {
                do {
                    const r = await fetch(btn.dataset.run, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token}, body: JSON.stringify({offset})});
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    const j = await r.json(); total = j.total || 1; offset = j.next;
                    bar.style.width = Math.round(offset / total * 100) + '%'; msg.textContent = offset + ' / ' + total;
                    if (j.finished) break;
                } while (offset < total);
                msg.textContent = 'শেষ — আপডেট হচ্ছে…'; location.reload();
            } catch (e) { msg.textContent = 'থেমে গেছে: ' + e.message; document.querySelectorAll('[data-run]').forEach(b => b.disabled = false); }
        }));
    })();
    </script>
@endsection
