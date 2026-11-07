@extends('admin.layout')
@section('title', 'SEO: '.$label)
@section('content')
    @php($old = fn ($k) => old($k, $v[$k] ?? null))
    <div class="seo-head adm-card">
        <div>
            <small class="adm-muted">{{ $typeLabel }}</small>
            <h2 class="adm-h" style="margin:2px 0 6px">{{ $label }}</h2>
            <a href="{{ url($path) }}" target="_blank" rel="noopener">{{ $base }}{{ $path === '/' ? '/' : $path }} <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            @if (! $public)<span class="seo-pill gray">খসড়া — পাবলিক নয়</span>@elseif ($reason)<span class="seo-pill amber">{{ $reason }}</span>@else<span class="seo-pill green">Google-এ দেখানোর যোগ্য</span>@endif
        </div>
        <div class="seo-head-btns">
            @if ($editUrl)<a class="adm-btn ghost sm" href="{{ $editUrl }}"><i class="fa-solid fa-pen-to-square"></i> কনটেন্ট এডিট</a>@endif
            <a class="adm-btn ghost sm" href="{{ route('admin.seo.pages') }}"><i class="fa-solid fa-list"></i> সব পেজ</a>
        </div>
    </div>

    <div class="seo-edit">
        <form method="POST" action="{{ route('admin.seo.update', [$type, $key]) }}" enctype="multipart/form-data" class="adm-card" data-unsaved>
            @csrf @method('PUT')
            <h3 class="adm-card-h">সার্চ রেজাল্টে যেভাবে দেখাবে</h3>
            <div class="seo-serp" aria-live="polite">
                <div class="u">{{ preg_replace('#^https?://#', '', $base) }} › {{ trim(str_replace('/', ' › ', $path), ' ›') }}</div>
                <div class="t" data-serp-title>{{ $resolved['title'] }}</div>
                <div class="d" data-serp-desc>{{ $resolved['description'] }}</div>
            </div>
            <p class="adm-muted">খালি রাখা ঘরগুলো পেজের লেখা থেকে নিজে থেকে তৈরি হয় (উপরের preview-তে যা দেখছেন)। শুধু বদলাতে চাইলে লিখুন।</p>

            <div class="adm-fields">
                <div class="adm-field full">
                    <label for="s_title">SEO title <small class="seo-count" data-count="s_title" data-min="30" data-max="60"></small></label>
                    <input id="s_title" name="title" maxlength="255" value="{{ $old('title') }}" placeholder="{{ $resolved['title'] }}" data-serp="title">
                    <small class="adm-help">পুরো টাইটেল যেভাবে Google-এ দেখাবে (ব্র্যান্ডের নাম নিজে যোগ করুন)। ~৬০ অক্ষরের বেশি হলে কেটে যেতে পারে।</small>
                </div>
                <div class="adm-field full">
                    <label for="s_desc">Meta description <small class="seo-count" data-count="s_desc" data-min="120" data-max="160"></small></label>
                    <textarea id="s_desc" name="description" rows="3" maxlength="500" placeholder="{{ $resolved['description'] }}" data-serp="desc">{{ $old('description') }}</textarea>
                    <small class="adm-help">পেজে কী পাওয়া যাবে তার সৎ, আকর্ষণীয় সারাংশ। Google মাঝে মাঝে নিজের মতো লেখা দেখায় — এটা স্বাভাবিক।</small>
                </div>
                @if ($canSlug)
                    <div class="adm-field full">
                        <label for="s_slug">URL (address)</label>
                        <div class="seo-slug"><span>{{ $base }}{{ \Illuminate\Support\Str::beforeLast($path, '/') }}/</span><input id="s_slug" name="slug" value="{{ $type === 'page' ? '' : $old('slug') }}" placeholder="{{ $type === 'page' ? $v['slug'] : '' }}"></div>
                        <small class="adm-help">{{ $type === 'page' ? 'পুরো ঠিকানা লিখুন (যেমন services/web-design)। ' : '' }}বদলালে পুরোনো ঠিকানা থেকে নিজে থেকেই 301 redirect তৈরি হবে। দরকার না হলে বদলাবেন না।</small>
                    </div>
                @endif
                <div class="adm-field">
                    <label for="s_kw">মূল keyword (পরিকল্পনার জন্য)</label>
                    <input id="s_kw" name="focus_keyword" maxlength="120" value="{{ $old('focus_keyword') }}" placeholder="যেমন n8n automation agency">
                </div>
                <div class="adm-field">
                    <label for="s_kw2">সম্পর্কিত keyword (কমা দিয়ে)</label>
                    <input id="s_kw2" name="secondary_keywords" maxlength="500" value="{{ $old('secondary_keywords') }}">
                </div>
            </div>

            <h3 class="adm-card-h">Google-এ দেখানো</h3>
            <div class="adm-fields">
                <div class="adm-field"><input type="hidden" name="noindex" value="0"><label class="adm-switch"><input type="checkbox" name="noindex" value="1" @checked($old('noindex'))><span></span> এই পেজ Google-এ দেখাবে না (noindex)</label></div>
                <div class="adm-field"><input type="hidden" name="nofollow" value="0"><label class="adm-switch"><input type="checkbox" name="nofollow" value="1" @checked($old('nofollow'))><span></span> এই পেজের লিংক অনুসরণ করবে না (nofollow)</label></div>
                <div class="adm-field full">
                    <label for="s_can">Canonical URL</label>
                    <input id="s_can" name="canonical" maxlength="500" value="{{ $old('canonical') }}" placeholder="{{ $resolved['canonical'] }}">
                    <small class="adm-help">সাধারণত খালি রাখুন (পেজ নিজেকেই নির্দেশ করে)। শুধু একই লেখা অন্য ঠিকানায় থাকলে মূল ঠিকানাটা দিন। এখানে অন্য পেজ দিলে এই পেজ sitemap থেকে বাদ যাবে।</small>
                </div>
                <div class="adm-field"><label for="s_rx">Robots (উন্নত)</label><input id="s_rx" name="robots_extra" value="{{ $old('robots_extra') }}" placeholder="max-image-preview:large, max-snippet:-1"></div>
                <div class="adm-field"><label for="s_bc">Breadcrumb-এ নাম</label><input id="s_bc" name="breadcrumb_label" value="{{ $old('breadcrumb_label') }}" placeholder="{{ $label }}"></div>
                <div class="adm-field"><input type="hidden" name="hide_breadcrumbs" value="0"><label class="adm-switch"><input type="checkbox" name="hide_breadcrumbs" value="1" @checked($old('hide_breadcrumbs'))><span></span> Breadcrumb structured data দেখাবে না</label></div>
            </div>

            <h3 class="adm-card-h">সোশ্যাল শেয়ার (Facebook, LinkedIn, X)</h3>
            <div class="adm-fields">
                <div class="adm-field"><label for="s_ogt">শেয়ার টাইটেল</label><input id="s_ogt" name="og_title" value="{{ $old('og_title') }}" placeholder="{{ $resolved['og_title'] }}"></div>
                <div class="adm-field"><label for="s_tc">X card</label><select id="s_tc" name="twitter_card"><option value="">অটো</option><option value="summary_large_image" @selected($old('twitter_card') === 'summary_large_image')>বড় ছবি</option><option value="summary" @selected($old('twitter_card') === 'summary')>ছোট</option></select></div>
                <div class="adm-field full"><label for="s_ogd">শেয়ার বিবরণ</label><textarea id="s_ogd" name="og_description" rows="2" placeholder="{{ $resolved['og_description'] }}">{{ $old('og_description') }}</textarea></div>
                @include('admin.fields.field', ['field' => ['key' => 'og_image', 'label' => 'শেয়ার ছবি (১২০০×৬৩০ ভালো)', 'type' => 'image'], 'name' => 'og_image', 'file' => 'og_image_file', 'value' => $old('og_image'), 'depth' => 0])
                <div class="adm-field"><label for="s_oga">ছবির বিবরণ (alt)</label><input id="s_oga" name="og_image_alt" value="{{ $old('og_image_alt') }}" placeholder="ছবিতে কী আছে"></div>
            </div>
            @if ($resolved['og_image'])<p class="adm-muted">এখন যে ছবি যাবে: <a href="{{ $resolved['og_image'] }}" target="_blank" rel="noopener">{{ basename($resolved['og_image']) }}</a></p>@endif

            <details class="seo-adv" @if ($errors->has('schema_override') || $old('schema_override')) open @endif>
                <summary>Structured data (JSON-LD) ও নোট</summary>
                <p class="adm-muted">সাইট নিজেই সঠিক structured data তৈরি করে (Organization, WebPage, Breadcrumb{{ $type === 'post' ? ', BlogPosting' : '' }}{{ $type === 'product' ? ', Product' : '' }})। এখানে শুধু অতিরিক্ত, পেজে সত্যিই দেখানো তথ্য যোগ করুন। Rating/review যোগ করা যাবে না।</p>
                <textarea name="schema_override" rows="7" class="mono" placeholder='{"@type": "HowTo", "name": "…"}' data-schema>{{ $old('schema_override') }}</textarea>
                <button type="button" class="adm-btn ghost sm" data-validate="{{ route('admin.seo.validate-schema') }}">যাচাই করুন</button> <span data-schema-msg class="adm-muted"></span>
                <label for="s_notes" style="display:block;margin-top:14px">SEO নোট (শুধু admin দেখবে)</label>
                <textarea id="s_notes" name="notes" rows="3">{{ $old('notes') }}</textarea>
            </details>

            <div class="adm-actions sticky">
                <a href="{{ url($path) }}" target="_blank" class="adm-btn ghost"><i class="fa-solid fa-eye"></i> পেজ দেখুন</a>
                <button class="adm-btn"><i class="fa-solid fa-floppy-disk"></i> সেভ করুন</button>
            </div>
        </form>

        <aside class="seo-side">
            <div class="adm-card">
                <h3 class="adm-card-h">চেকলিস্ট <small class="adm-muted">(সাইটের নিজের চেক)</small></h3>
                @if (! $analysis)
                    <p class="adm-muted">{{ $public ? 'পেজটা খোলা যায়নি।' : 'খসড়া পেজ — Publish করলে চেক হবে।' }}</p>
                @else
                    <ul class="seo-check">
                        <li class="{{ $analysis['status_code'] === 200 ? 'ok' : 'bad' }}">পেজ খোলে (HTTP {{ $analysis['status_code'] }})</li>
                        <li class="{{ $analysis['h1_count'] === 1 ? 'ok' : 'warn' }}">মূল শিরোনাম (H1): {{ $analysis['h1_count'] }}টি</li>
                        <li class="{{ $analysis['images_without_alt'] ? 'warn' : 'ok' }}">Alt ছাড়া ছবি: {{ $analysis['images_without_alt'] }}</li>
                        <li class="{{ $analysis['schema_ok'] ? 'ok' : 'bad' }}">Structured data {{ $analysis['schema_ok'] ? 'বৈধ' : 'ত্রুটিপূর্ণ' }}</li>
                        <li class="ok">দৃশ্যমান লেখা: প্রায় {{ $analysis['words'] }} শব্দ</li>
                        <li class="ok">ভেতরের লিংক: {{ count($analysis['links']) }}টি</li>
                        @foreach ($analysis['issues'] as $i)<li class="{{ $i['level'] === 'error' ? 'bad' : ($i['level'] === 'warn' ? 'warn' : 'info') }}">{{ $i['text'] }}</li>@endforeach
                    </ul>
                @endif
            </div>

            @if ($keywordChecks)
                <div class="adm-card">
                    <h3 class="adm-card-h">“{{ $v['focus_keyword'] }}” কোথায় আছে</h3>
                    <ul class="seo-check">@foreach ($keywordChecks as [$what, $ok])<li class="{{ $ok === true ? 'ok' : ($ok === false ? 'warn' : 'info') }}">{{ $what }}{{ is_string($ok) ? ': '.$ok : '' }}</li>@endforeach</ul>
                    <p class="adm-muted">শুধু লেখার সহায়িকা — কতবার লিখলে র‍্যাংক হবে এমন কোনো নিয়ম নেই। স্বাভাবিক, কাজের লেখাই সবচেয়ে ভালো।</p>
                </div>
            @endif

            @if ($suggestions)
                <div class="adm-card">
                    <h3 class="adm-card-h">সম্পর্কিত শব্দের আইডিয়া</h3>
                    <p class="adm-muted">এই পেজের লেখায় বারবার আসা শব্দগুচ্ছ — search volume-এর ডেটা নয়।</p>
                    <div class="seo-chips">@foreach ($suggestions as $s)<button type="button" data-add-kw="{{ $s }}">{{ $s }}</button>@endforeach</div>
                </div>
            @endif

            <div class="adm-card">
                <h3 class="adm-card-h">ভেতরের লিংকের আইডিয়া</h3>
                @forelse ($linkIdeas as $l)<p class="seo-dup"><a href="{{ url($l['path']) }}" target="_blank" rel="noopener">{{ $l['label'] }}</a> <code>{{ $l['path'] }}</code></p>@empty<p class="adm-muted">{{ $v['focus_keyword'] ?? null ? 'মিল আছে এমন আর কোনো পেজ নেই, অথবা সবগুলোতেই লিংক আছে।' : 'মূল keyword দিলে মিল আছে এমন পেজ দেখাবে।' }}</p>@endforelse
                <p class="adm-muted">সম্পর্কিত পেজে বর্ণনামূলক লেখা দিয়ে লিংক দিলে ভিজিটর আর Google দুজনেরই সুবিধা হয়।</p>
            </div>
        </aside>
    </div>

    <script>
    (() => {
        const $ = s => document.querySelector(s);
        const upd = () => {
            document.querySelectorAll('[data-count]').forEach(c => {
                const el = document.getElementById(c.dataset.count), n = (el.value || el.placeholder || '').length, min = +c.dataset.min, max = +c.dataset.max;
                c.textContent = n + ' অক্ষর' + (el.value ? '' : ' (অটো)'); c.className = 'seo-count ' + (n === 0 ? 'bad' : n < min ? 'warn' : n > max ? 'warn' : 'ok');
            });
            $('[data-serp-title]').textContent = $('#s_title').value || $('#s_title').placeholder;
            $('[data-serp-desc]').textContent = ($('#s_desc').value || $('#s_desc').placeholder).slice(0, 165) + (($('#s_desc').value || $('#s_desc').placeholder).length > 165 ? '…' : '');
        };
        ['#s_title', '#s_desc'].forEach(s => $(s).addEventListener('input', upd)); upd();
        document.querySelectorAll('[data-add-kw]').forEach(b => b.addEventListener('click', () => { const i = $('#s_kw2'); i.value = (i.value ? i.value + ', ' : '') + b.dataset.addKw; b.disabled = true; }));
        const vb = document.querySelector('[data-validate]');
        vb?.addEventListener('click', async () => {
            const r = await fetch(vb.dataset.validate, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify({json: $('[data-schema]').value})});
            const j = await r.json(); const m = $('[data-schema-msg]'); m.textContent = j.message; m.style.color = j.ok ? '#4ade80' : '#ff8a98';
        });
    })();
    </script>
@endsection
