<x-layouts::auth.getnow title="Create account" :registration="true">
    <a class="card-logo" href="{{ route('home') }}" aria-label="GetNow home"><img src="{{ asset('images/getnow-logo.png') }}" alt="GetNow" width="1100" height="252"></a>

    <header class="login-heading">
        <h2 id="login-title">{{ __('Create account') }}</h2>
        <p>{{ __('A simpler way to get started. Join GetNow.') }}</p>
    </header>

    @if (session('status')) 
        <p class="login-status" role="status">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('register.store') }}" class="login-form registration-form">
        @csrf
        @if ($errors->any())
            <p class="registration-summary" role="alert">{{ __('Please check the highlighted fields below.') }}</p>
        @endif

        <div class="registration-fields">
            <x-getnow-field name="name" :label="__('Full name')" autocomplete="name" :placeholder="__('Your full name')" maxlength="255" />
            <x-getnow-field name="username" :label="__('Username')" autocomplete="username" placeholder="johndoe" minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" autocapitalize="none" spellcheck="false" described-by="registration-username-hint" />
            <div class="registration-wide registration-username-hint">
                <p class="registration-hint" id="registration-username-hint">{{ __('Username: 3–30 letters, numbers, or underscores.') }}</p>
            </div>
            <x-getnow-field name="email" :label="__('Email address')" type="email" autocomplete="email" placeholder="you@company.com" maxlength="255" autocapitalize="none" spellcheck="false" />
            <x-getnow-field name="mobile_phone" :label="__('Mobile phone')" type="tel" autocomplete="tel" inputmode="tel" placeholder="+94 77 123 4567" maxlength="40" />
            <div class="registration-wide">
                <x-getnow-field name="address" :label="__('Address')" type="textarea" autocomplete="street-address" :placeholder="__('Street address, city, and postal code')" minlength="5" maxlength="500" />
            </div>
            <x-getnow-field name="password" :label="__('Password')" type="password" autocomplete="new-password" :placeholder="__('Create password')" :minlength="app()->isProduction() ? 12 : 8" described-by="registration-password-hint" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" />
            <x-getnow-field name="password_confirmation" :label="__('Confirm password')" type="password" autocomplete="new-password" :placeholder="__('Repeat password')" :minlength="app()->isProduction() ? 12 : 8" />
            <p id="registration-password-hint" class="registration-hint registration-wide">{{ app()->isProduction() ? __('Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol.') : __('At least 8 characters; passwords must match.') }}</p>
        </div>

        <button class="login-submit" type="submit" data-test="register-user-button">
            <span>{{ __('Create account') }}</span>
            <svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 14h21m-7-8 8 8-8 8"/></svg>
        </button>
    </form>

    <div class="login-divider"><span>{{ __('OR') }}</span></div>
    <p class="login-register">{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
</x-layouts::auth.getnow>
