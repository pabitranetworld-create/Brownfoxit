<nav class="st-acc-nav" aria-label="My account">
    <a href="{{ route('account.dashboard') }}" @class(['on' => request()->routeIs('account.dashboard')])><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="{{ route('account.orders') }}" @class(['on' => request()->routeIs('account.orders')])><i class="fa-solid fa-receipt"></i> Orders</a>
    <a href="{{ route('account.downloads') }}" @class(['on' => request()->routeIs('account.downloads')])><i class="fa-solid fa-download"></i> Downloads</a>
    <a href="{{ route('account.profile') }}" @class(['on' => request()->routeIs('account.profile')])><i class="fa-solid fa-user-pen"></i> Profile</a>
    <form method="post" action="{{ route('account.logout') }}">@csrf<button type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log out</button></form>
</nav>
