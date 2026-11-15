<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo">
            <img src="{{ media(setting('site.logo')) }}" alt="{{ setting('site.name') }}">
        </a>

        <nav class="main-nav" id="mainNav">
            <ul>
                @foreach (array_filter(setting('menu', []), fn ($i) => link_visible($i['url'] ?? null)) as $item)
                    @php
                        $children = array_filter($item['children'] ?? [], fn ($c) => link_visible($c['url'] ?? null));
                        $isActive = ($item['url'] ?? '') === '/' ? request()->is('/') : (str_starts_with($item['url'] ?? '', '/') && strlen(trim($item['url'], '/')) && ! str_contains($item['url'], '#') && request()->is(trim($item['url'], '/').'*'));
                    @endphp
                    <li @class(['has-sub' => count($children), 'active' => $isActive])>
                        <a href="{{ link_to_url($item['url'] ?? '#') }}">
                            {{ $item['label'] }}
                            @if (count($children)) <i class="fa-solid fa-angle-down"></i> @endif
                        </a>
                        @if (count($children))
                            <ul class="sub-menu">
                                @foreach ($children as $child)
                                    <li><a href="{{ link_to_url($child['url'] ?? '#') }}">{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        @if (setting('header_button.enabled', true))
            @php($hbUrl = trim((string) setting('header_button.url', '')) ?: '/contact')
            <a href="{{ link_to_url($hbUrl) }}" class="btn btn-gradient btn-sm header-btn" @if (setting('header_button.new_tab') || preg_match('#^https?://#i', $hbUrl) && ! str_starts_with($hbUrl, url('/'))) target="_blank" rel="noopener" @endif>
                <i class="{{ setting('header_button.icon') ?: 'fa-regular fa-comments' }}"></i> {{ setting('header_button.text') ?: 'Contact Us' }}
            </a>
        @endif
        <a href="{{ auth()->check() && ! auth()->user()->isAdmin() ? route('account.dashboard') : route('account.login') }}" class="hdr-cart hdr-acc" aria-label="My Account" title="My Account"><i class="fa-solid fa-user"></i></a>
        @if (store('show_cart_icon', true))
            @php($cartCount = request()->cookie(\App\Store\CartService::COOKIE) || auth()->check() ? rescue(fn () => app(\App\Store\CartService::class)->count(), 0, false) : 0)
            <a href="{{ route('cart.show') }}" class="hdr-cart" aria-label="Cart ({{ $cartCount }})"><i class="fa-solid fa-bag-shopping"></i>@if ($cartCount)<b>{{ $cartCount }}</b>@endif</a>
        @endif
        <button class="menu-toggle" id="menuToggle" aria-label="Open menu"><span></span><span></span><span></span></button>
    </div>
</header>
