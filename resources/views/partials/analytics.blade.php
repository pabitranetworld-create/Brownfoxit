{{-- Google Analytics 4 with Google Consent Mode v2. When consent is required (EU/UK visitors), nothing is
     stored until the visitor accepts; the choice is remembered in this browser only. Never on admin pages. --}}
@php($ga = trim((string) setting('seo_integrations.ga4_id')))
@if ($ga !== '' && preg_match('/^G-[A-Z0-9]{4,15}$/', $ga) && ! request()->is('admin', 'admin/*') && empty($editor))
    @php($needConsent = (bool) setting('seo_integrations.consent_required', true))
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      (function () {
        var saved = null; try { saved = localStorage.getItem('bfi_consent'); } catch (e) {}
        var granted = {{ $needConsent ? 'false' : 'true' }} || saved === 'granted';
        gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: granted ? 'granted' : 'denied', wait_for_update: 500 });
      })();
      gtag('js', new Date());
      gtag('config', @json($ga), { anonymize_ip: true });
    </script>
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
    @if ($needConsent)
        @push('scripts')
        <div class="bfi-consent" id="bfiConsent" role="dialog" aria-label="Cookie consent" hidden>
            <p>We use analytics cookies to understand how the site is used. You can accept or decline.@if ($pp = setting('seo_integrations.privacy_url')) <a href="{{ $pp }}">Privacy policy</a>@endif</p>
            <div><button type="button" data-consent="denied">Decline</button><button type="button" data-consent="granted" class="on">Accept</button></div>
        </div>
        <style>.bfi-consent{position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;max-width:560px;margin:0 auto;display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding:14px 18px;border-radius:14px;background:rgba(12,10,30,.95);color:#e6e3f2;font:14px/1.5 sans-serif;box-shadow:0 18px 50px rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.12)}.bfi-consent p{flex:1 1 260px;margin:0}.bfi-consent a{color:#ff9a3e}.bfi-consent button{padding:8px 16px;border-radius:30px;border:1px solid rgba(255,255,255,.3);background:transparent;color:#fff;cursor:pointer;margin-left:6px}.bfi-consent button.on{background:linear-gradient(90deg,#ff9a3e,#ff5266);border:0}</style>
        <script>(function(){var b=document.getElementById('bfiConsent'),s=null;try{s=localStorage.getItem('bfi_consent')}catch(e){}if(!s)b.hidden=false;b.addEventListener('click',function(e){var v=e.target.getAttribute('data-consent');if(!v)return;try{localStorage.setItem('bfi_consent',v)}catch(x){}gtag('consent','update',{analytics_storage:v});b.hidden=true;});})();</script>
        @endpush
    @endif
@endif
