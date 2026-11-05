<form class="pb-search" method="get" action="{{ route('search') }}" role="search">
    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
    <input type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="{{ $d['placeholder'] }}" aria-label="{{ $d['placeholder'] }}" required minlength="2" maxlength="100">
    <button type="submit" class="pb-btn pb-btn-primary">{{ $d['button'] }}</button>
</form>
