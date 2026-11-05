@extends('admin.layout')

@section('title', 'Media library')

@section('content')
    <div class="adm-card">
        <div class="adm-toolbar">
            <div class="adm-search"><i class="fa-solid fa-magnifying-glass"></i><input id="mlSearch" placeholder="নাম বা Alt লেখা দিয়ে খুঁজুন…"></div>
            <form method="POST" action="{{ route('admin.media.sync') }}">@csrf<button class="adm-btn ghost" title="public/uploads-এ আগে থেকে থাকা ছবিগুলো লাইব্রেরিতে যোগ করে"><i class="fa-solid fa-rotate"></i> পুরোনো ছবি যোগ করুন</button></form>
            <label class="adm-btn"><i class="fa-solid fa-upload"></i> ছবি আপলোড<input type="file" id="mlUpload" accept="image/*" multiple hidden></label>
        </div>
        <div class="ml-drop" id="mlDrop"><i class="fa-solid fa-cloud-arrow-up"></i> ছবি এখানে টেনে এনে ছেড়ে দিন (সর্বোচ্চ ৫ MB, JPG / PNG / WEBP / AVIF / GIF)</div>
        <div class="ml-grid" id="mlGrid"></div>
        <div class="adm-pages" id="mlPager"></div>
    </div>

    <div class="adm-modal" id="mlEdit" hidden>
        <div class="adm-modal-box wide">
            <div class="ml-edit">
                <div class="ml-edit-img"><img alt=""></div>
                <div>
                    <h3 data-name></h3>
                    <p class="adm-muted" data-meta></p>
                    <div class="adm-field"><label>Alt লেখা (ছবিটা কী দেখাচ্ছে, SEO ও Accessibility-র জন্য)</label><input data-alt></div>
                    <div class="adm-field"><label>ক্যাপশন</label><input data-caption></div>
                    <div class="adm-field"><label>লিংক</label><input data-url readonly onclick="this.select()"></div>
                    <div class="adm-actions">
                        <button type="button" class="adm-btn ghost danger" data-delete><i class="fa-solid fa-trash"></i> মুছুন</button>
                        <span class="spacer"></span>
                        <button type="button" class="adm-btn ghost" data-close>বন্ধ</button>
                        <button type="button" class="adm-btn" data-save><i class="fa-solid fa-floppy-disk"></i> সেভ</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>window.BFI_MEDIA_PAGE = { update: @json(url('admin/media')) };</script>
<script src="{{ asset('assets/admin/media-page.js') }}?v={{ filemtime(public_path('assets/admin/media-page.js')) }}"></script>
@endpush
