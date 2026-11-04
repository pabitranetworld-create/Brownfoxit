<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo">
            <img src="{{ media(setting('site.logo')) }}" alt="{{ setting('site.name') }}">
        </a>

        <nav class="main-nav" id="mainNav">
            <ul>
                @foreach (setting('menu', []) as $item)
                    @php
                        $children = $item['children'] ?? [];
                        $isActive = ($item['url'] ?? '') === '/' ? request()->is('/') : false;
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
