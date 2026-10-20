<x-layouts::auth.getnow>
    <a class="card-logo" href="{{ route('home') }}" aria-label="GetNow home"><img src="{{ asset('images/getnow-logo.png') }}" alt="GetNow" width="1100" height="252"></a>

    <header class="login-heading">
        <h2 id="login-title">{{ __('Welcome back') }}</h2>
        <p>{{ __('Sign in to your account to continue.') }}</p>
    </header>

    @if (session('status'))
        <p class="login-status" role="status">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="login-form">
        @csrf
        <x-getnow-field name="email" :label="__('Email or Username')" autocomplete="username webauthn" placeholder="you@company.com"
            :data-passkey-options="Route::has('passkey.login-options') ? route('passkey.login-options') : null"
            :data-passkey-submit="Route::has('passkey.login') ? route('passkey.login') : null" />
        <x-getnow-field name="password" :label="__('Password')" type="password" autocomplete="current-password" placeholder="•••••••••" />

        <div class="login-options">
            <label class="remember-label"><input type="checkbox" name="remember" value="1" @checked(old('remember', true))><span>{{ __('Remember me') }}</span></label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
            @endif
        </div>

        <button class="login-submit" type="submit" data-test="login-button">
            <span>{{ __('Sign In') }}</span>
            <svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 14h21m-7-8 8 8-8 8"/></svg>
        </button>
    </form>

    @if (Route::has('register'))
        <div class="login-divider"><span>{{ __('OR') }}</span></div>
        <p class="login-register">{{ __('Don’t have an account?') }} <a href="{{ route('register') }}">{{ __('Sign up') }}</a></p>
    @endif
</x-layouts::auth.getnow>
