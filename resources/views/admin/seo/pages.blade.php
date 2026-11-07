@extends('admin.layout')
@section('title', 'সব পেজের SEO')
@section('content')
    <div class="adm-card">
        <div class="adm-toolbar">
            <form method="GET" class="seo-filter">
                <select name="type" onchange="this.form.submit()"><option value="">সব ধরন</option>@foreach (\App\Seo\Subjects::TYPES as $k => [$l])<option value="{{ $k }}" @selected($type === $k)>{{ $l }}</option>@endforeach</select>
                <select name="show" onchange="this.form.submit()"><option value="">সব</option><option value="problems" @selected($show === 'problems')>সমস্যা আছে</option><option value="noindex" @selected($show === 'noindex')>noindex / অন্য canonical</option><option value="drafts" @selected($show === 'drafts')>খসড়া / লুকানো</option></select>
            </form>
            <a class="adm-btn ghost sm" href="{{ route('admin.seo.index') }}"><i class="fa-solid fa-chart-line"></i> ড্যাশবোর্ড</a>
        </div>
        <div class="adm-table-wrap"><table class="adm-table">
            <thead><tr><th>পেজ</th><th>ধরন</th><th>Google</th><th>Title / Desc</th><th>Keyword</th><th>চেক</th><th></th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                @php($a = $audits[$r['id']] ?? null)
                @php($err = $a ? collect($a->issues)->where('level', 'error')->count() : 0)
                @php($warn = $a ? collect($a->issues)->where('level', 'warn')->count() : 0)
                <tr>
                    <td><strong>{{ $r['label'] }}</strong><br><a class="adm-muted" href="{{ url($r['path']) }}" target="_blank" rel="noopener">{{ $r['path'] }}</a></td>
                    <td>{{ \App\Seo\Subjects::TYPES[$r['type']][0] }}</td>
                    <td>@if (! $r['public'])<span class="seo-pill gray">খসড়া</span>@elseif ($r['reason'])<span class="seo-pill amber">{{ $r['reason'] }}</span>@else<span class="seo-pill green">index</span>@endif</td>
                    <td>{!! $r['has_title'] ? '<i class="fa-solid fa-check ok"></i>' : '<span class="adm-muted" title="Generated automatically">auto</span>' !!} / {!! $r['has_desc'] ? '<i class="fa-solid fa-check ok"></i>' : '<span class="adm-muted" title="Generated automatically">auto</span>' !!}</td>
                    <td>{{ $r['keyword'] ?: '—' }}</td>
                    <td>@if (! $a)<span class="adm-muted">—</span>@elseif ($err)<span class="seo-pill red">{{ $err }} ত্রুটি</span>@elseif ($warn)<span class="seo-pill amber">{{ $warn }} সতর্কতা</span>@else<span class="seo-pill green">ঠিক আছে</span>@endif</td>
                    <td><a class="adm-btn ghost sm" href="{{ route('admin.seo.edit', [$r['type'], $r['key']]) }}"><i class="fa-solid fa-pen"></i> SEO</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="adm-muted">কিছু পাওয়া যায়নি।</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
@endsection
