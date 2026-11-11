<div class="st-auth-tabs" role="tablist">
    <a href="{{ route('account.login') }}" role="tab" @class(['on' => $tab === 'login']) aria-selected="{{ $tab === 'login' ? 'true' : 'false' }}"><i class="fa-solid fa-right-to-bracket"></i> Log in</a>
    @if (store('registration', true))<a href="{{ route('account.register') }}" role="tab" @class(['on' => $tab === 'register']) aria-selected="{{ $tab === 'register' ? 'true' : 'false' }}"><i class="fa-solid fa-user-plus"></i> Sign up</a>@endif
</div>
