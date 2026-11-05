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

        <a href="{{ link_to_url('/#contact') }}" class="btn btn-gradient btn-sm header-btn">
            <i class="fa-regular fa-comments"></i> Contact Us
        </a>
        <button class="menu-toggle" id="menuToggle" aria-label="Open menu"><span></span><span></span><span></span></button>
    </div>
</header>
