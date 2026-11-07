@extends('admin.layout')
@section('title', 'ইমেইল / Gmail')
@section('content')
    <div @class(['adm-alert', 'ok' => $ready, 'warn' => ! $ready])>
        <i class="fa-solid {{ $ready ? 'fa-circle-check' : 'fa-circle-info' }}"></i>
        <div>
            @if ($ready)
                <strong>ইমেইল চালু আছে</strong> —
                @if ($mailer === 'gmail_api') Gmail (Connect with Google): {{ $m['gmail_email'] ?? '' }}
                @elseif ($isGmailSmtp) Gmail (App Password): {{ $m['username'] ?? '' }}
                @else SMTP: {{ $m['host'] ?? '' }} @endif
                · নতুন বার্তা যাবে: <strong>{{ $notify }}</strong>
            @else
                <strong>ইমেইল এখন বন্ধ।</strong> Contact ফর্মের বার্তা Admin → Messages-এ জমা হচ্ছে, কিন্তু ইমেইল যাচ্ছে না। নিচের যেকোনো একটি পদ্ধতিতে চালু করুন।
            @endif
        </div>
    </div>
    @if ($ready)
        <div class="adm-actions" style="margin-bottom:18px">
            <form method="POST" action="{{ route('admin.mail.test') }}">@csrf<button class="adm-btn ghost"><i class="fa-solid fa-paper-plane"></i> টেস্ট ইমেইল পাঠান</button></form>
            <form method="POST" action="{{ route('admin.mail.off') }}">@csrf<button class="adm-btn ghost danger">ইমেইল বন্ধ করুন</button></form>
        </div>
    @endif

    <div class="ml-grid">
        <div class="adm-card ml-card">
            <span class="ml-badge">সবচেয়ে সহজ · ২ মিনিট</span>
            <h2 class="adm-h"><i class="fa-brands fa-google"></i> Gmail — App Password</h2>
            <p class="adm-muted">Server, port, encryption সব নিজে থেকে বসে যাবে (smtp.gmail.com · 465 · SSL)। শুধু Gmail ঠিকানা আর একটি App Password দিন। <strong>আপনার আসল Gmail পাসওয়ার্ড এখানে দেবেন না</strong> — কাজও করবে না।</p>
            <ol class="ml-steps">
                <li>Google অ্যাকাউন্টে <a href="https://myaccount.google.com/signinoptions/twosv" target="_blank" rel="noopener">2-Step Verification</a> চালু করুন (না থাকলে)।</li>
                <li><a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">App passwords</a> খুলুন → নাম দিন "Website" → Create।</li>
                <li>১৬ অক্ষরের কোডটা কপি করে নিচে বসান → সেভ। সাথে সাথে একটি টেস্ট ইমেইল যাবে।</li>
            </ol>
            <form method="POST" action="{{ route('admin.mail.app-password') }}" class="adm-fields">
                @csrf
                <div class="adm-field full"><label>Gmail ঠিকানা</label><input type="email" name="gmail" required value="{{ old('gmail', $isGmailSmtp ? ($m['username'] ?? '') : '') }}" placeholder="yourname@gmail.com"></div>
                <div class="adm-field full"><label>App Password (১৬ অক্ষর)</label><input type="password" name="app_password" autocomplete="new-password" placeholder="{{ $isGmailSmtp ? '•••• সেভ করা আছে — বদলাতে নতুনটা দিন' : 'abcd efgh ijkl mnop' }}"></div>
                <div class="adm-field"><label>পাঠানোর নাম</label><input name="from_name" value="{{ old('from_name', $m['from_name'] ?? setting('site.name')) }}"></div>
                <div class="adm-field"><label>নতুন বার্তা আসবে যে ঠিকানায়</label><input type="email" name="notify_email" value="{{ old('notify_email', $m['notify_email'] ?? '') }}" placeholder="খালি = এই Gmail"></div>
                <div class="adm-field full"><button class="adm-btn"><i class="fa-solid fa-plug"></i> সেভ করে টেস্ট করুন</button></div>
            </form>
        </div>

        <div class="adm-card ml-card">
            <span class="ml-badge blue">১ ক্লিকে সংযোগ</span>
            <h2 class="adm-h"><i class="fa-brands fa-google"></i> Connect with Google</h2>
            @if ($connected)
                <p><i class="fa-solid fa-circle-check ok"></i> যুক্ত আছে: <strong>{{ $m['gmail_email'] }}</strong>{{ $mailer === 'gmail_api' ? ' — এখন এটা দিয়েই ইমেইল যাচ্ছে।' : '' }}</p>
                <div class="adm-actions">
                    <a href="{{ route('admin.mail.google.connect') }}" class="adm-btn ghost"><i class="fa-solid fa-rotate"></i> আবার যুক্ত করুন</a>
                    <form method="POST" action="{{ route('admin.mail.google.disconnect') }}">@csrf<button class="adm-btn ghost danger">সংযোগ সরান</button></form>
                </div>
            @elseif ($hasClient)
                <p class="adm-muted">Google Client সেভ করা আছে। নিচের বাটনে চাপ দিয়ে আপনার Gmail-এ লগইন করে "Allow" দিন — ব্যস।</p>
                <a href="{{ route('admin.mail.google.connect') }}" class="adm-btn ml-google"><i class="fa-brands fa-google"></i> Connect with Google</a>
            @endif
            <p class="adm-muted">সাইট শুধু <strong>ইমেইল পাঠানোর</strong> অনুমতি চায় (gmail.send) — আপনার ইনবক্স পড়তে পারে না। Google-এর পাসওয়ার্ড সাইট কখনো দেখে না। Google প্রথমবার একটি ছোট সেটআপ চায় (একবারই, ~৫ মিনিট):</p>
            <details @if (! $hasClient) open @endif class="ml-setup">
                <summary>এককালীন Google সেটআপ</summary>
                <ol class="ml-steps">
                    <li><a href="https://console.cloud.google.com/projectcreate" target="_blank" rel="noopener">Google Cloud Console</a>-এ একটি Project বানান (যেমন "Website mail")।</li>
                    <li><a href="https://console.cloud.google.com/apis/library/gmail.googleapis.com" target="_blank" rel="noopener">Gmail API</a> → <em>Enable</em>।</li>
                    <li><a href="https://console.cloud.google.com/auth/branding" target="_blank" rel="noopener">OAuth consent screen</a> → External → App name, আপনার ইমেইল দিন → সেভ। তারপর <em>Audience</em> → <strong>Publish app</strong> (In production) করুন — নইলে ৭ দিন পর সংযোগ কেটে যায়।</li>
                    <li><a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener">Clients</a> → <em>Create client</em> → Web application → <em>Authorized redirect URI</em>-তে নিচের ঠিকানা দিন:<br><code class="ml-uri" data-copy>{{ $redirectUri }}</code> <button type="button" class="adm-btn ghost sm" data-copy-btn>কপি</button></li>
                    <li>তৈরি হওয়া Client ID আর Client secret নিচে বসিয়ে সেভ করুন, তারপর "Connect with Google" চাপুন।</li>
                    <li>লগইনের সময় "Google hasn't verified this app" দেখালে <em>Advanced → Go to … (unsafe)</em> চাপুন — এটা আপনার নিজের অ্যাপ, নিজের ব্যবহারের জন্য।</li>
                </ol>
                <p class="adm-muted"><strong>মূল ডোমেইনে সরালে</strong> Google Client-এ নতুন redirect URI (নতুন ডোমেইনের) যোগ করে আবার Connect করুন।</p>
                <form method="POST" action="{{ route('admin.mail.client') }}" class="adm-fields">
                    @csrf
                    <div class="adm-field full"><label>Client ID</label><input name="client_id" value="{{ old('client_id', $m['gmail_client_id'] ?? '') }}" placeholder="….apps.googleusercontent.com"></div>
                    <div class="adm-field full"><label>Client secret</label><input type="password" name="client_secret" autocomplete="new-password" placeholder="{{ $hasClient ? '•••• সেভ করা আছে' : 'GOCSPX-…' }}"></div>
                    <div class="adm-field full"><button class="adm-btn ghost"><i class="fa-solid fa-floppy-disk"></i> সেভ করুন</button></div>
                </form>
            </details>
        </div>
    </div>

    <div class="adm-card">
        <h3 class="adm-card-h">অন্য মেইল সার্ভার (cPanel মেইল ইত্যাদি)</h3>
        <p class="adm-muted">নিজের ডোমেইনের ইমেইল (যেমন hello@brownfoxit.com) দিয়ে পাঠাতে চাইলে <a href="{{ route('admin.settings.edit', 'mail') }}">SMTP সেটিংস</a> হাতে পূরণ করুন।</p>
        <p class="adm-muted">মনে রাখুন: ফ্রি Gmail দিনে প্রায় ৫০০টি ইমেইল পাঠাতে দেয়; ইমেইল যাবে আপনার Gmail ঠিকানা থেকে। দোকানে অনেক অর্ডার হলে পরে নিজের ডোমেইনের মেইল বা কোনো ইমেইল সার্ভিস ব্যবহার করা ভালো।</p>
    </div>
    <script>document.querySelector('[data-copy-btn]')?.addEventListener('click', e => { navigator.clipboard?.writeText(document.querySelector('[data-copy]').textContent.trim()); e.target.textContent = 'কপি হয়েছে'; });</script>
@endsection
