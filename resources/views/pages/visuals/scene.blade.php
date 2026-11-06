{{-- Animated technology scenes for hero sections. Pure decoration (aria-hidden on the wrapper). --}}
<div class="tv tv-s-{{ $scene }}">
    <div class="tv-halo"></div>
    <div class="tv-rings"><i></i><i></i><i></i></div>
    @switch($scene)

    @case('neural')
        <canvas class="tv-canvas" data-neural></canvas>
        <div class="tv-core"><span>AI</span><i></i><i></i></div>
        <div class="tv-chip c1"><i class="fa-solid fa-brain"></i> model</div>
        <div class="tv-chip c2"><i class="fa-solid fa-bolt"></i> inference</div>
        <div class="tv-chip c3"><i class="fa-solid fa-database"></i> data</div>
    @break

    @case('workflow')
        <svg class="tv-svg" viewBox="0 0 520 440">
            <defs><linearGradient id="twf" x1="0" x2="1"><stop offset="0" stop-color="#22d3ee"/><stop offset=".5" stop-color="#a855f7"/><stop offset="1" stop-color="#ff6a3d"/></linearGradient></defs>
            @foreach (['M95 120 C 180 120, 180 220, 260 220', 'M95 320 C 180 320, 180 220, 260 220', 'M260 220 C 340 220, 340 110, 425 110', 'M260 220 C 340 220, 340 220, 425 220', 'M260 220 C 340 220, 340 330, 425 330'] as $i => $path)
                <path id="twp{{ $i }}" d="{{ $path }}" class="tv-wire"/>
                <path d="{{ $path }}" class="tv-flow" style="animation-delay: -{{ $i * .4 }}s"/>
                <circle r="5" class="tv-packet p{{ $i % 3 }}"><animateMotion dur="{{ 2.2 + $i * .3 }}s" repeatCount="indefinite" begin="-{{ $i * .5 }}s"><mpath href="#twp{{ $i }}"/></animateMotion></circle>
            @endforeach
        </svg>
        <div class="tv-node n1" style="left:18%;top:27%"><i class="fa-solid fa-bolt"></i><small>Trigger</small></div>
        <div class="tv-node n2" style="left:18%;top:73%"><i class="fa-solid fa-envelope"></i><small>Email</small></div>
        <div class="tv-node n3 big" style="left:50%;top:50%"><i class="fa-solid fa-robot"></i><small>AI step</small></div>
        <div class="tv-node n4" style="left:82%;top:25%"><i class="fa-solid fa-table"></i><small>Sheet</small></div>
        <div class="tv-node n5" style="left:82%;top:50%"><i class="fa-solid fa-users"></i><small>CRM</small></div>
        <div class="tv-node n6" style="left:82%;top:75%"><i class="fa-solid fa-comment-dots"></i><small>Notify</small></div>
    @break

    @case('code')
        <div class="tv-win tv-editor">
            <div class="tv-bar"><i></i><i></i><i></i><span>app.js</span></div>
            <div class="tv-code">
                @foreach ([['k', 'import'], ['f', 'const app = createApp();'], ['', ''], ['k', 'app.get("/api/orders",'], ['s', '  async (req, res) => {'], ['f', '    const data = await db.find();'], ['c', '    // fast, tested, secure'], ['s', '    res.json(data);'], ['k', '});']] as $i => $ln)
                    <div class="ln" style="--i:{{ $i }}"><em>{{ $i + 1 }}</em><span class="t-{{ $ln[0] }}">{{ $ln[1] }}</span></div>
                @endforeach
            </div>
        </div>
        <div class="tv-win tv-term">
            <div class="tv-bar"><i></i><i></i><i></i><span>terminal</span></div>
            <p><b>$</b> npm run build</p><p class="ok">✓ compiled in 1.2s</p><p class="ok">✓ 48 tests passed</p><p><b>$</b> deploy<span class="tv-caret"></span></p>
        </div>
        <span class="tv-float f1">&lt;/&gt;</span><span class="tv-float f2">{ }</span><span class="tv-float f3">=&gt;</span>
    @break

    @case('browser')
        <div class="tv-win tv-site">
            <div class="tv-bar"><i></i><i></i><i></i><span class="tv-url">https://yourbrand.com</span></div>
            <div class="tv-page">
                <div class="b nav"><i></i><i></i><i></i></div>
                <div class="b hero"><i></i><i></i><u></u></div>
                <div class="b cards"><i></i><i></i><i></i></div>
                <div class="b row"><i></i><i></i></div>
            </div>
            <svg class="tv-cursor" viewBox="0 0 24 24"><path d="M4 2l16 9-7 2-3 7z"/></svg>
        </div>
        <div class="tv-phone"><div class="b nav"></div><div class="b hero"></div><div class="b"></div><div class="b"></div></div>
        <span class="tv-float f1"><i class="fa-brands fa-wordpress"></i></span><span class="tv-float f2"><i class="fa-brands fa-html5"></i></span><span class="tv-float f3"><i class="fa-solid fa-gauge-high"></i></span>
    @break

    @case('shop')
        <div class="tv-phone big">
            <div class="tv-scroll">@for ($r = 0; $r < 2; $r++)@foreach (['#ff6a3d', '#a855f7', '#22d3ee', '#ec4899'] as $c)<div class="tv-prod"><i style="background: {{ $c }}"></i><b></b><em></em></div>@endforeach @endfor</div>
        </div>
        <div class="tv-cart"><i class="fa-solid fa-cart-shopping"></i><b>3</b></div>
        <div class="tv-toast"><i class="fa-solid fa-circle-check"></i> Payment received</div>
        <span class="tv-float f1"><i class="fa-solid fa-tag"></i></span><span class="tv-float f2"><i class="fa-solid fa-credit-card"></i></span><span class="tv-float f3"><i class="fa-solid fa-box"></i></span>
    @break

    @case('film')
        <div class="tv-win tv-monitor">
            <div class="tv-screen"><i class="s1"></i><i class="s2"></i><i class="s3"></i><span class="tv-play"><i class="fa-solid fa-play"></i></span><em class="tv-rec">● REC</em></div>
            <div class="tv-timeline">
                <div class="trk"><i style="left:2%;width:30%"></i><i style="left:36%;width:22%"></i><i style="left:62%;width:34%"></i></div>
                <div class="trk t2"><i style="left:10%;width:40%"></i><i style="left:55%;width:28%"></i></div>
                <div class="wave">@for ($i = 0; $i < 40; $i++)<i style="--d:{{ ($i * 37 % 10) / 10 }}s"></i>@endfor</div>
                <span class="tv-head"></span>
            </div>
        </div>
        <span class="tv-float f1"><i class="fa-solid fa-film"></i></span><span class="tv-float f2"><i class="fa-solid fa-wand-magic-sparkles"></i></span><span class="tv-float f3"><i class="fa-solid fa-music"></i></span>
    @break

    @case('design')
        <svg class="tv-svg" viewBox="0 0 520 440">
            <defs><linearGradient id="tds" x1="0" x2="1"><stop offset="0" stop-color="#ff6a3d"/><stop offset=".5" stop-color="#ec4899"/><stop offset="1" stop-color="#a855f7"/></linearGradient></defs>
            <rect x="70" y="60" width="380" height="320" rx="18" class="tv-board"/>
            <path id="tdp" d="M110 300 C 170 120, 260 120, 270 220 S 380 330, 420 140" class="tv-pen"/>
            <g class="tv-handles"><line x1="110" y1="300" x2="150" y2="180"/><line x1="270" y1="220" x2="300" y2="290"/><line x1="420" y1="140" x2="390" y2="100"/><rect x="104" y="294" width="12" height="12"/><rect x="264" y="214" width="12" height="12"/><rect x="414" y="134" width="12" height="12"/><circle cx="150" cy="180" r="5"/><circle cx="300" cy="290" r="5"/><circle cx="390" cy="100" r="5"/></g>
            <g class="tv-nib"><path d="M0 -14 L8 6 L0 14 L-8 6 Z"/><animateMotion dur="5s" repeatCount="indefinite" rotate="auto"><mpath href="#tdp"/></animateMotion></g>
        </svg>
        <div class="tv-swatches">@foreach (['#ff6a3d', '#ec4899', '#a855f7', '#3b82f6', '#22d3ee', '#a3e635'] as $i => $c)<i style="--c: {{ $c }}; --i: {{ $i }}"></i>@endforeach</div>
        <span class="tv-float f1"><i class="fa-solid fa-pen-nib"></i></span><span class="tv-float f2"><i class="fa-solid fa-palette"></i></span><span class="tv-float f3">Aa</span>
    @break

    @case('globe')
        <svg class="tv-svg tv-globe" viewBox="0 0 520 440">
            <defs><radialGradient id="tgg" cx=".4" cy=".35"><stop offset="0" stop-color="#3b82f6" stop-opacity=".45"/><stop offset="1" stop-color="#0b1030" stop-opacity=".2"/></radialGradient></defs>
            <circle cx="260" cy="220" r="150" fill="url(#tgg)" class="tv-sphere"/>
            @for ($i = 0; $i < 6; $i++)<ellipse cx="260" cy="220" rx="150" ry="150" class="tv-mer" style="animation-delay: -{{ $i * 1.33 }}s"/>@endfor
            @foreach ([-100, -50, 0, 50, 100] as $y)<ellipse cx="260" cy="{{ 220 + $y }}" rx="{{ round(sqrt(150 * 150 - $y * $y)) }}" ry="{{ round(sqrt(150 * 150 - $y * $y) * .18) }}" class="tv-lat"/>@endforeach
            <path d="M180 170 Q 260 60 340 190" class="tv-arc a1"/><path d="M200 280 Q 300 200 360 260" class="tv-arc a2"/><path d="M150 230 Q 220 320 300 300" class="tv-arc a3"/>
            @foreach ([[180, 170], [340, 190], [200, 280], [360, 260], [150, 230], [300, 300]] as $i => $p)<circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="5" class="tv-city" style="animation-delay: -{{ $i * .5 }}s"/>@endforeach
            <g class="tv-sat-orbit"><ellipse cx="260" cy="220" rx="215" ry="70" class="tv-orbit-line"/><circle r="7" class="tv-sat"><animateMotion dur="8s" repeatCount="indefinite" path="M45 220 a215 70 0 1 0 430 0 a215 70 0 1 0 -430 0"/></circle></g>
        </svg>
    @break

    @case('chip')
        <svg class="tv-svg" viewBox="0 0 520 440">
            @php($traces = ['M200 180 H120 V100 H60', 'M200 220 H40', 'M200 260 H130 V340 H70', 'M320 180 H400 V90 H470', 'M320 220 H480', 'M320 260 H390 V350 H460', 'M240 160 V60 H200', 'M280 160 V40', 'M240 280 V390 H190', 'M280 280 V410 H340'])
            @foreach ($traces as $i => $t)<path d="{{ $t }}" class="tv-trace"/><path d="{{ $t }}" class="tv-pulse q{{ $i % 4 }}" style="animation-delay: -{{ ($i * .37) }}s"/>@endforeach
            <rect x="200" y="160" width="120" height="120" rx="14" class="tv-cpu"/>
            <rect x="222" y="182" width="76" height="76" rx="8" class="tv-die"/>
            <text x="260" y="228" text-anchor="middle" class="tv-cpu-t">CPU</text>
        </svg>
        <span class="tv-float f1">01</span><span class="tv-float f2">10</span><span class="tv-float f3"><i class="fa-solid fa-microchip"></i></span>
    @break

    @case('chart')
        <div class="tv-win tv-dash">
            <div class="tv-bar"><i></i><i></i><i></i><span>live data</span><em class="tv-live">● LIVE</em></div>
            <div class="tv-dash-body">
                <div class="tv-bars">@foreach ([55, 80, 40, 95, 65, 75, 50, 88] as $i => $h)<i style="--h: {{ $h }}%; --i: {{ $i }}"></i>@endforeach</div>
                <svg class="tv-line" viewBox="0 0 300 120" preserveAspectRatio="none"><path d="M0 100 L40 80 L80 90 L120 50 L160 60 L200 30 L240 40 L300 10"/><path class="area" d="M0 100 L40 80 L80 90 L120 50 L160 60 L200 30 L240 40 L300 10 V120 H0 Z"/></svg>
                <div class="tv-donut"><svg viewBox="0 0 42 42"><circle cx="21" cy="21" r="15.9" class="bg"/><circle cx="21" cy="21" r="15.9" class="v1"/><circle cx="21" cy="21" r="15.9" class="v2"/></svg></div>
            </div>
        </div>
        <span class="tv-float f1"><i class="fa-solid fa-chart-line"></i></span><span class="tv-float f2"><i class="fa-solid fa-magnifying-glass-chart"></i></span><span class="tv-float f3"><i class="fa-solid fa-bullseye"></i></span>
    @break

    @case('shield')
        <div class="tv-hex">@for ($i = 0; $i < 24; $i++)<i style="--d: {{ ($i * 53 % 30) / 10 }}s"></i>@endfor</div>
        <svg class="tv-svg" viewBox="0 0 520 440">
            <defs><linearGradient id="tsh" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#22d3ee"/><stop offset="1" stop-color="#a855f7"/></linearGradient></defs>
            <path d="M260 70 L370 115 V215 C370 295 315 345 260 370 C205 345 150 295 150 215 V115 Z" class="tv-shield"/>
            <path d="M215 220 L248 252 L310 185" class="tv-check"/>
            <rect x="140" y="70" width="240" height="4" class="tv-scanline"/>
        </svg>
        <span class="tv-float f1"><i class="fa-solid fa-lock"></i></span><span class="tv-float f2"><i class="fa-solid fa-key"></i></span><span class="tv-float f3"><i class="fa-solid fa-fingerprint"></i></span>
    @break

    @case('rocket')
        <div class="tv-speed">@for ($i = 0; $i < 14; $i++)<i style="--x: {{ ($i * 41 + 9) % 100 }}%; --d: {{ ($i * .23) }}s"></i>@endfor</div>
        <svg class="tv-svg tv-rocket" viewBox="0 0 520 440">
            <defs><linearGradient id="trk" x1="0" x2="1"><stop offset="0" stop-color="#e2e8f0"/><stop offset="1" stop-color="#94a3b8"/></linearGradient></defs>
            <g class="tv-ship">
                <path d="M260 60 C300 100 310 170 300 250 H220 C210 170 220 100 260 60 Z" fill="url(#trk)"/>
                <circle cx="260" cy="150" r="20" fill="#0b1030" stroke="#22d3ee" stroke-width="5"/>
                <path d="M220 200 L180 270 L222 252 Z" fill="#ff6a3d"/><path d="M300 200 L340 270 L298 252 Z" fill="#ff6a3d"/>
                <path d="M235 250 H285 L275 275 H245 Z" fill="#a855f7"/>
                <path d="M245 275 Q260 360 275 275 Z" class="tv-flame"/><path d="M252 275 Q260 330 268 275 Z" class="tv-flame in"/>
            </g>
        </svg>
        <span class="tv-float f1"><i class="fa-solid fa-star"></i></span><span class="tv-float f2"><i class="fa-solid fa-satellite"></i></span><span class="tv-float f3"><i class="fa-solid fa-moon"></i></span>
    @break

    @case('chat')
        <div class="tv-win tv-chatwin">
            <div class="tv-bar"><span class="tv-bot"><i class="fa-solid fa-robot"></i></span><span>Assistant · online</span></div>
            <div class="tv-msgs">
                <p class="u" style="--i:0">Hi! Can you help me with an order?</p>
                <p class="b" style="--i:1">Of course — what's the order number?</p>
                <p class="u" style="--i:2">#10234</p>
                <p class="b" style="--i:3">Found it. It ships today ✓</p>
                <p class="b typing" style="--i:4"><i></i><i></i><i></i></p>
            </div>
        </div>
        <span class="tv-float f1"><i class="fa-solid fa-comment-dots"></i></span><span class="tv-float f2"><i class="fa-brands fa-whatsapp"></i></span><span class="tv-float f3"><i class="fa-solid fa-headset"></i></span>
    @break

    @endswitch
</div>
