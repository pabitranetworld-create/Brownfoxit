{{-- Floating contact chip, back-to-top button and the video popup used by .play-btn links --}}
<a href="{{ setting('site.whatsapp_url', '#') }}" class="float-contact" target="_blank" rel="noopener">Contact us</a>

<button class="back-top" id="backTop" aria-label="Back to top">
    <svg viewBox="0 0 44 44"><circle cx="22" cy="22" r="20" class="track"/><circle cx="22" cy="22" r="20" class="prog" id="backTopProg"/></svg>
    <i class="fa-solid fa-arrow-up"></i>
</button>

<div class="video-modal" id="videoModal" aria-hidden="true">
    <div class="video-box">
        <button class="video-close" id="videoClose" aria-label="Close">&times;</button>
        <div class="video-frame" id="videoFrame"></div>
    </div>
</div>
