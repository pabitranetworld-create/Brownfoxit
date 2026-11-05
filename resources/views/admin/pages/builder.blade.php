<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $page->title }} · Page Builder</title>
    <link rel="stylesheet" href="{{ asset('vendor/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/admin.css') }}?v={{ filemtime(public_path('assets/admin/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/builder.css') }}?v={{ filemtime(public_path('assets/admin/builder.css')) }}">
</head>
<body class="bld-body">
<div class="bld">
    <header class="bld-top">
        <a href="{{ route('admin.pages.index', $page->is_template ? ['show' => 'templates'] : []) }}" class="bld-icon" title="Pages-এ ফিরে যান"><i class="fa-solid fa-arrow-left"></i></a>
        <div class="bld-title">
            <strong id="bTitle">{{ $page->title }}</strong>
            <span id="bStatus" class="adm-badge"></span>
        </div>
        <div class="bld-devices" role="group" aria-label="ডিভাইস">
            <button type="button" class="on" data-device="desktop" title="Desktop"><i class="fa-solid fa-desktop"></i></button>
            <button type="button" data-device="tablet" title="Tablet"><i class="fa-solid fa-tablet-screen-button"></i></button>
            <button type="button" data-device="mobile" title="Mobile"><i class="fa-solid fa-mobile-screen-button"></i></button>
        </div>
        <div class="bld-actions">
            <button type="button" class="bld-icon" id="bUndo" title="Undo (Ctrl+Z)" disabled><i class="fa-solid fa-rotate-left"></i></button>
            <button type="button" class="bld-icon" id="bRedo" title="Redo (Ctrl+Shift+Z)" disabled><i class="fa-solid fa-rotate-right"></i></button>
            <button type="button" class="bld-icon" id="bRevisions" title="আগের ভার্সন"><i class="fa-solid fa-clock-rotate-left"></i></button>
            <a class="bld-icon" id="bPreviewTab" href="{{ route('admin.pages.preview', $page) }}" target="_blank" title="নতুন ট্যাবে Preview (সেভ করা Draft)"><i class="fa-solid fa-eye"></i></a>
            <div class="bld-more">
                <button type="button" class="bld-icon" title="আরও"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                <div class="bld-menu">
                    @unless ($page->is_template)
                        <form method="POST" action="{{ route('admin.pages.duplicate', $page) }}">@csrf<input type="hidden" name="as_template" value="1"><button><i class="fa-solid fa-layer-group"></i> Template হিসেবে সেভ</button></form>
                    @endunless
                    <form method="POST" action="{{ route('admin.pages.duplicate', $page) }}">@csrf<button><i class="fa-regular fa-copy"></i> পেজের কপি বানান</button></form>
                    <form method="POST" action="{{ route('admin.pages.unpublish', $page) }}" id="bUnpublishForm">@csrf<button><i class="fa-solid fa-eye-slash"></i> Unpublish (Draft করুন)</button></form>
                    <a href="{{ $page->url() }}" target="_blank" id="bLive"><i class="fa-solid fa-arrow-up-right-from-square"></i> লাইভ পেজ দেখুন</a>
                </div>
            </div>
            <button type="button" class="adm-btn ghost sm" id="bSave"><i class="fa-solid fa-floppy-disk"></i> <span>Draft সেভ</span></button>
            <button type="button" class="adm-btn sm" id="bPublish"><i class="fa-solid fa-rocket"></i> <span>{{ $page->is_template ? 'সেভ' : 'Publish' }}</span></button>
        </div>
    </header>

    <aside class="bld-side">
        <nav class="bld-tabs" id="bTabs">
            <button type="button" data-tab="add" class="on"><i class="fa-solid fa-plus"></i> Widget</button>
            <button type="button" data-tab="tree"><i class="fa-solid fa-layer-group"></i> Structure</button>
            <button type="button" data-tab="page"><i class="fa-solid fa-gear"></i> পেজ</button>
        </nav>
        <div class="bld-panel" id="bPanel"></div>
    </aside>

    <main class="bld-stage">
        <div class="bld-frame-wrap" id="bFrameWrap">
            <iframe id="bFrame" title="Page preview"></iframe>
            <div class="bld-loading" id="bLoading"><i class="fa-solid fa-spinner fa-spin"></i></div>
        </div>
    </main>
</div>

<div class="bld-toast" id="bToast" role="status" aria-live="polite"></div>

<script>
window.BFI_MEDIA = { list: @json(route('admin.media.list')), upload: @json(route('admin.media.store')), base: @json(url('/')), csrf: @json(csrf_token()) };
window.BFI_BUILDER = {
    defs: @json($defs),
    layout: @json($layout),
    page: @json($pageData),
    urls: {
        save: @json(route('admin.pages.save', $page)),
        publish: @json(route('admin.pages.publish', $page)),
        preview: @json(route('admin.pages.preview', $page)),
        revisions: @json(route('admin.pages.revisions', $page)),
        restore: @json(url('admin/pages/'.$page->id.'/revisions')),
        site: @json(url('/')),
        media: @json(url('/')),
    },
    csrf: @json(csrf_token())
};
</script>
<script src="{{ asset('assets/admin/media-picker.js') }}?v={{ filemtime(public_path('assets/admin/media-picker.js')) }}"></script>
<script src="{{ asset('assets/admin/builder.js') }}?v={{ filemtime(public_path('assets/admin/builder.js')) }}"></script>
</body>
</html>
