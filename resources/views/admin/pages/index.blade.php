@extends('admin.layout')

@section('title', $show === 'templates' ? 'Page Templates' : 'Pages')

@section('content')
    <div class="adm-card">
        <div class="adm-toolbar">
            <div class="adm-seg">
                <a href="{{ route('admin.pages.index') }}" @class(['on' => $show === 'pages'])>Pages</a>
                <a href="{{ route('admin.pages.index', ['show' => 'templates']) }}" @class(['on' => $show === 'templates'])>Templates</a>
            </div>
            <form method="GET" class="adm-search">
                @if ($show === 'templates')<input type="hidden" name="show" value="templates">@endif
                <i class="fa-solid fa-magnifying-glass"></i>
                <input name="q" value="{{ $q }}" placeholder="খুঁজুন…">
            </form>
            <button type="button" class="adm-btn" data-open="#newPage"><i class="fa-solid fa-plus"></i> নতুন পেজ</button>
        </div>

        @if ($pages->isEmpty())
            <div class="adm-empty"><i class="fa-regular fa-file"></i><p>{{ $show === 'templates' ? 'কোনো Template নেই। যেকোনো পেজের "Template হিসেবে সেভ" দিয়ে বানান।' : 'এখনো কোনো পেজ নেই।' }}</p></div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>পেজ</th><th>URL</th><th>অবস্থা</th><th>শেষ পরিবর্তন</th><th class="r">কাজ</th></tr></thead>
                    <tbody>
                        @foreach ($pages as $p)
                            <tr>
                                <td data-label="পেজ"><a class="adm-strong" href="{{ route('admin.pages.builder', $p) }}">{{ $p->title }}</a></td>
                                <td data-label="URL">@if (! $p->is_template)<a href="{{ $p->url() }}" target="_blank" class="adm-muted">/{{ $p->slug }}</a>@else<span class="adm-muted">—</span>@endif</td>
                                <td data-label="অবস্থা">
                                    @if ($p->is_template)<span class="adm-badge">Template</span>
                                    @elseif ($p->isPublished())<span class="adm-badge ok">Published</span>@if ($p->hasUnpublishedChanges()) <span class="adm-badge warn">অপ্রকাশিত পরিবর্তন</span>@endif
                                    @else<span class="adm-badge">Draft</span>@endif
                                </td>
                                <td data-label="শেষ পরিবর্তন" class="adm-muted">{{ $p->updated_at->diffForHumans() }}</td>
                                <td class="r adm-row-actions">
                                    <a href="{{ route('admin.pages.builder', $p) }}" class="adm-icon-btn" title="Builder-এ এডিট"><i class="fa-solid fa-pen-ruler"></i></a>
                                    <a href="{{ route('admin.seo.edit', ['page', $p->id]) }}" class="adm-icon-btn" title="SEO"><i class="fa-solid fa-magnifying-glass-chart"></i></a>
                                    <form method="POST" action="{{ route('admin.pages.duplicate', $p) }}">@csrf<button class="adm-icon-btn" title="কপি বানান"><i class="fa-regular fa-copy"></i></button></form>
                                    @if ($p->isPublished() && ! $p->is_template)
                                        <form method="POST" action="{{ route('admin.pages.unpublish', $p) }}" data-confirm="পেজটা সাইট থেকে সরিয়ে Draft করবেন?">@csrf<button class="adm-icon-btn" title="Unpublish"><i class="fa-solid fa-eye-slash"></i></button></form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.pages.destroy', $p) }}" data-confirm="&quot;{{ $p->title }}&quot; পেজটা মুছে ফেলবেন? সব ভার্সনও মুছে যাবে।">@csrf @method('DELETE')<button class="adm-icon-btn danger" title="মুছুন"><i class="fa-solid fa-trash"></i></button></form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="adm-pager">{{ $pages->links('admin.pagination') }}</div>
        @endif
    </div>

    <div class="adm-modal" id="newPage" @if (! $errors->any()) hidden @endif>
        <form method="POST" action="{{ route('admin.pages.store') }}" class="adm-modal-box">
            @csrf
            <h3>নতুন পেজ</h3>
            <div class="adm-field"><label>পেজের নাম</label><input name="title" value="{{ old('title') }}" required placeholder="যেমন: About Us"></div>
            <div class="adm-field"><label>URL (খালি রাখলে নাম থেকে বানানো হবে)</label><input name="slug" value="{{ old('slug') }}" placeholder="about-us"></div>
            <div class="adm-field">
                <label>শুরু করুন</label>
                <select name="template_id">
                    <option value="">খালি পেজ (Builder দিয়ে সাজাবেন)</option>
                    <option value="html">Custom HTML পেজ (Canvas — নিজের HTML/CSS/JS দিয়ে পুরো পেজ)</option>
                    @foreach ($templates as $t)<option value="{{ $t->id }}">Template: {{ $t->title }}</option>@endforeach
                </select>
            </div>
            <div class="adm-actions">
                <button type="button" class="adm-btn ghost" data-close>বাতিল</button>
                <button class="adm-btn"><i class="fa-solid fa-arrow-right"></i> Builder খুলুন</button>
            </div>
        </form>
    </div>
@endsection
