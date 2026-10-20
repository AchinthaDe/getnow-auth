@props(['title' => 'Sign In', 'registration' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">
        <script>
            (() => {
                let theme;
                try { theme = localStorage.getItem('getnow-auth-theme'); } catch {}
                document.documentElement.dataset.authTheme = ['light', 'dark'].includes(theme)
                    ? theme : (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            })();
        </script>
        <title>{{ __($title) }} - GetNow</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/auth.js'])
        @if (! $registration && Route::has('passkey.login-options') && Route::has('passkey.login'))
            @vite('resources/js/login.js')
        @endif
    </head>
    <body class="getnow-login {{ $registration ? 'registration-page' : '' }}">
        <main class="login-shell">
            <section class="login-story" aria-labelledby="story-title">
                <svg class="login-sculpture" viewBox="0 0 724 1086" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="upper-fold" x1=".15" y1=".2" x2="1" y2=".8" gradientUnits="objectBoundingBox">
                            <stop stop-color="#ffffff"/><stop offset=".48" stop-color="#eeeeef"/><stop offset="1" stop-color="#bdbec0"/>
                        </linearGradient>
                        <linearGradient id="front-fold" x1=".2" y1=".12" x2=".9" y2="1" gradientUnits="objectBoundingBox">
                            <stop stop-color="#fff"/><stop offset=".38" stop-color="#f9f9f9"/><stop offset=".73" stop-color="#e2e2e3"/><stop offset="1" stop-color="#b6b7b9"/>
                        </linearGradient>
                        <linearGradient id="lower-fold" x1=".35" y1="0" x2=".75" y2="1" gradientUnits="objectBoundingBox">
                            <stop stop-color="#acadaf"/><stop offset=".46" stop-color="#d5d5d6"/><stop offset="1" stop-color="#f4f4f4"/>
                        </linearGradient>
                        <filter id="fold-shadow" x="-50%" y="-50%" width="200%" height="220%"><feGaussianBlur stdDeviation="23"/></filter>
                    </defs>
                    <ellipse cx="641" cy="992" rx="128" ry="28" fill="#000" opacity=".12" filter="url(#fold-shadow)"/>
                    <path d="M724 235C604 220 496 309 492 431C488 552 590 631 724 684Z" fill="url(#upper-fold)"/>
                    <path d="M724 651C653 737 463 893 472 958C478 1005 580 1022 645 990C700 962 736 892 745 813Z" fill="url(#lower-fold)"/>
                    <path d="M318 566C431 490 598 508 724 593L724 690C627 791 499 870 475 948C502 864 433 782 366 741C312 705 282 621 318 566Z" fill="url(#front-fold)"/>
                    <path d="M724 690C627 791 499 870 475 948" fill="none" stroke="white" stroke-opacity=".7"/>
                </svg>
                <a class="story-logo" href="{{ route('home') }}" aria-label="GetNow home"><img src="{{ asset('images/getnow-logo.png') }}" alt="GetNow" width="1100" height="252"></a>
                <div class="story-copy">
                    <p class="story-eyebrow">SIMPLER WAYS<br>TO GET THINGS DONE</p>
                    <h1 id="story-title">Move<br>faster<br>together.</h1>
                    <p class="story-description">GetNow helps teams turn ideas into<br class="desktop-break"> progress, with less friction and more focus.</p>
                </div>
                <div class="story-footer">
                    <div class="story-pagination" aria-hidden="true"><span></span><span></span><span></span></div>
                    <p>PLAN <span>/</span> COLLABORATE <span>/</span> GET RESULTS</p>
                </div>
            </section>
            <section class="login-panel" aria-labelledby="login-title">
                <div class="login-card">
                    <button class="auth-theme-toggle" type="button" data-theme-toggle aria-label="{{ __('Switch to dark theme') }}" aria-pressed="false" title="{{ __('Switch theme') }}">
                        <svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                        <svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/></svg>
                    </button>
                    {{ $slot }}
                </div>
            </section>
        </main>
        @fluxScripts
    </body>
</html>
