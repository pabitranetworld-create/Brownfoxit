@extends('admin.layout')
@section('title', 'Redirects')
@section('content')
    <div class="adm-card">
        <h2 class="adm-h">নতুন redirect</h2>
        <p class="adm-muted">পুরোনো ঠিকানা কেউ খুললে নতুন ঠিকানায় পাঠানো হবে। স্থায়ী পরিবর্তনে <strong>301</strong> ব্যবহার করুন। কোনো লেখা বা পেজের URL বদলালে redirect নিজে থেকেই তৈরি হয়।</p>
        <form method="POST" action="{{ route('admin.redirects.store') }}" class="rd-form">
            @csrf
            <label>পুরোনো ঠিকানা<input name="from_path" required placeholder="/old-page" value="{{ old('from_path', request('from')) }}"></label>
            <label>নতুন ঠিকানা<input name="to_url" required placeholder="/new-page বা https://…" value="{{ old('to_url') }}"></label>
            <label>ধরন<select name="status_code"><option value="301">301 স্থায়ী</option><option value="302">302 অস্থায়ী</option><option value="307">307 অস্থায়ী</option><option value="308">308 স্থায়ী</option></select></label>
            <label class="wide">নোট<input name="notes" maxlength="255" value="{{ old('notes') }}"></label>
            <button class="adm-btn"><i class="fa-solid fa-plus"></i> যোগ করুন</button>
        </form>
        <div class="rd-test">
            <label>যেকোনো ঠিকানা পরীক্ষা করুন <input id="rdTest" placeholder="/some-old-url"></label>
            <button type="button" class="adm-btn ghost sm" data-test="{{ route('admin.redirects.test') }}">পরীক্ষা</button>
            <span id="rdTestOut" class="adm-muted"></span>
        </div>
    </div>

    <div class="adm-card">
        <h2 class="adm-h">পুরোনো WordPress সাইটের ঠিকানা</h2>
        <p class="adm-muted">পুরোনো সাইট (যেমন brownfoxit.com) <strong>বন্ধ করার আগে</strong> এটা চালান: সাইট তার পেজ, পোস্ট, ক্যাটাগরি, ট্যাগ, প্রোডাক্ট আর পোর্টফোলিওর ঠিকানা পড়ে নতুন সাইটের সবচেয়ে কাছের পেজ প্রস্তাব করবে। আপনি দেখে নিশ্চিত করলে তবেই redirect তৈরি হবে। মিল না পেলে home page-এ পাঠানো হয় না — আপনাকে বেছে দিতে হবে।</p>
        <form method="POST" action="{{ route('admin.redirects.suggest') }}" class="rd-form">
            @csrf
            <label class="wide">পুরোনো সাইট<input name="site" value="{{ old('site', $oldSite) }}" required></label>
            <button class="adm-btn ghost"><i class="fa-brands fa-wordpress"></i> পুরোনো সাইটের ঠিকানা আনুন</button>
        </form>
        @if ($suggestions)
            <form method="POST" action="{{ route('admin.redirects.apply') }}">
                @csrf
                <div class="adm-table-wrap"><table class="adm-table compact">
                    <thead><tr><th><input type="checkbox" data-all checked></th><th>পুরোনো ঠিকানা</th><th>কী</th><th>নতুন ঠিকানা</th><th>মিল</th></tr></thead>
                    <tbody>@foreach ($suggestions as $s)
                        <tr @class(['rd-done' => $s['exists']])>
                            <td>@if ($s['exists'])<i class="fa-solid fa-check ok" title="Already exists"></i>@else<input type="checkbox" name="pick[{{ $s['from'] }}]" value="1" @checked($s['to'] && $s['confidence'] !== 'none')>@endif</td>
                            <td><code>{{ $s['from'] }}</code><br><small class="adm-muted">{{ \Illuminate\Support\Str::limit($s['label'], 60) }}</small></td>
                            <td>{{ $s['kind'] }}</td>
                            <td><input name="to[{{ md5($s['from']) }}]" value="{{ $s['to'] }}" placeholder="বেছে দিন, যেমন /services" @disabled($s['exists'])>@if ($s['note'])<br><small class="adm-muted">{{ $s['note'] }}</small>@endif</td>
                            <td><span class="seo-pill {{ ['high' => 'green', 'medium' => 'amber', 'low' => 'amber', 'none' => 'red'][$s['confidence']] }}">{{ $s['exists'] ? 'আছে' : $s['confidence'] }}</span></td>
                        </tr>
                    @endforeach</tbody>
                </table></div>
                <div class="adm-actions"><button class="adm-btn"><i class="fa-solid fa-check"></i> বেছে নেওয়াগুলোর redirect তৈরি করুন</button></div>
            </form>
            <form method="POST" action="{{ route('admin.redirects.clear') }}" style="margin-top:8px">@csrf<button class="adm-btn ghost sm">তালিকা মুছুন</button></form>
        @endif
    </div>

    <div class="adm-card">
        <div class="adm-toolbar">
            <h2 class="adm-h" style="margin:0">সব redirect</h2>
            <form method="GET" class="adm-search"><input name="q" value="{{ $q }}" placeholder="খুঁজুন…"></form>
        </div>
        @if ($redirects->isEmpty())
            <div class="adm-empty"><i class="fa-solid fa-diamond-turn-right"></i><p>এখনো কোনো redirect নেই।</p></div>
        @else
            <div class="adm-table-wrap"><table class="adm-table">
                <thead><tr><th>পুরোনো</th><th>নতুন</th><th>ধরন</th><th>উৎস</th><th>ব্যবহার</th><th></th></tr></thead>
                <tbody>@foreach ($redirects as $r)
                    <tr @class(['rd-off' => ! $r->is_active])>
                        <td><code>{{ $r->from_path }}</code>@if ($r->notes)<br><small class="adm-muted">{{ $r->notes }}</small>@endif</td>
                        <td><form method="POST" action="{{ route('admin.redirects.update', $r) }}" class="rd-inline">@csrf @method('PUT')<input type="hidden" name="from_path" value="{{ $r->from_path }}"><input type="hidden" name="notes" value="{{ $r->notes }}"><input type="hidden" name="is_active" value="{{ $r->is_active ? 1 : 0 }}"><input name="to_url" value="{{ $r->to_url }}"><select name="status_code">@foreach ([301, 302, 307, 308] as $c)<option @selected($r->status_code === $c)>{{ $c }}</option>@endforeach</select><button class="adm-btn ghost sm" title="Save"><i class="fa-solid fa-floppy-disk"></i></button></form>
                            @if (isset($problems[$r->id]))<small class="lv-warn">{{ $problems[$r->id] }}</small>@endif</td>
                        <td>{{ $r->status_code }}</td>
                        <td>{{ ['manual' => 'হাতে', 'slug-change' => 'URL বদল', 'wordpress' => 'WordPress'][$r->source] ?? $r->source }}</td>
                        <td>{{ $r->hits }}@if ($r->last_hit_at)<br><small class="adm-muted">{{ $r->last_hit_at->diffForHumans() }}</small>@endif</td>
                        <td class="rd-actions">
                            <form method="POST" action="{{ route('admin.redirects.toggle', $r) }}">@csrf<button class="adm-btn ghost sm">{{ $r->is_active ? 'বন্ধ' : 'চালু' }}</button></form>
                            <form method="POST" action="{{ route('admin.redirects.destroy', $r) }}" onsubmit="return confirm('মুছে ফেলবেন?')">@csrf @method('DELETE')<button class="adm-btn ghost sm danger"><i class="fa-solid fa-trash"></i></button></form>
                        </td>
                    </tr>
                @endforeach</tbody>
            </table></div>
            {{ $redirects->links('admin.pagination') }}
        @endif
    </div>
    <script>
    (() => {
        document.querySelector('[data-all]')?.addEventListener('change', e => document.querySelectorAll('input[name^="pick["]').forEach(c => c.checked = e.target.checked));
        const b = document.querySelector('[data-test]');
        b.addEventListener('click', async () => {
            const out = document.getElementById('rdTestOut'); out.textContent = '…';
            const r = await fetch(b.dataset.test, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify({path: document.getElementById('rdTest').value})});
            const j = await r.json(); out.textContent = j.hops.map(h => h.path + ' → ' + h.status).join('  ⟶  ');
        });
    })();
    </script>
@endsection
