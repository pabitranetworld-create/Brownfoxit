@if (filled($d['css']))<style>{!! $d['css'] !!}</style>@endif
<div class="pb-html wrap-{{ $d['wrap'] }}">{!! \App\PageBuilder\Theme::closeOpenTags((string) $d['code']) !!}</div>
@if (filled($d['js']))<script>{!! $d['js'] !!}</script>@endif
@if (! empty($editor) && blank($d['code']) && blank($d['js']))
    <div class="pb-placeholder"><i class="fa-solid fa-code"></i> HTML কোড লিখুন</div>
@endif
