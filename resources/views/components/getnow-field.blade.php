@props(['name', 'label', 'type' => 'text', 'hint' => null, 'describedBy' => null])

<div class="login-field" @if ($type === 'password') x-data="{ visible: false }" @endif>
    <label for="{{ $name }}">{{ $label }}</label>
    <div class="login-input-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            @if ($type === 'password')
                <rect x="5" y="10" width="14" height="11" rx="1.5"/><path d="M8 10V6a4 4 0 0 1 8 0v4m-4 5v2"/>
            @elseif ($type === 'email' || $name === 'email')
                <rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 6 9 7 9-7"/>
            @elseif ($type === 'tel')
                <rect x="6" y="2" width="12" height="20" rx="2"/><path d="M10 18h4"/>
            @elseif ($name === 'address')
                <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>
            @elseif ($name === 'username')
                <circle cx="12" cy="12" r="4"/><path d="M16 8v6a2 2 0 0 0 4 0v-2a8 8 0 1 0-3 6.2"/>
            @else
                <circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
            @endif
        </svg>
        @if ($type === 'textarea')
            <textarea id="{{ $name }}" name="{{ $name }}" rows="2" required
                @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
                {{ $attributes }}>{{ old($name) }}</textarea>
        @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" required
            @if ($type === 'password')
                x-bind:type="visible ? 'text' : 'password'"
            @else
                value="{{ old($name) }}"
            @endif
            @if ($hint || $describedBy || $errors->has($name))
                aria-describedby="{{ $describedBy }} {{ $hint ? $name.'-hint' : '' }} {{ $errors->has($name) ? $name.'-error' : '' }}"
            @endif
            @if ($errors->has($name)) aria-invalid="true" @endif
            {{ $attributes }}>
        @endif
        @if ($type === 'password')
            <button class="password-toggle" type="button" x-on:click="visible = !visible"
                x-bind:aria-pressed="visible" x-bind:aria-label="visible ? '{{ __('Hide :field', ['field' => strtolower($label)]) }}' : '{{ __('Show :field', ['field' => strtolower($label)]) }}'"
                aria-label="{{ __('Show :field', ['field' => strtolower($label)]) }}" aria-controls="{{ $name }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path x-show="visible" x-cloak d="m3 3 18 18"/></svg>
            </button>
        @endif
    </div>
    @if ($hint)<p id="{{ $name }}-hint" class="registration-hint">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $name }}-error" class="login-error" role="alert">{{ $message }}</p>@enderror
</div>
