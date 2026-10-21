import { Passkeys } from '@laravel/passkeys';

// Keep saved passkeys available in the browser's native username picker.
async function enablePasskeyAutofill() {
    const input = document.querySelector('[data-passkey-options]');

    if (!input) return;

    try {
        const response = await Passkeys.autofill({
            routes: {
                options: input.dataset.passkeyOptions,
                submit: input.dataset.passkeySubmit,
            },
        });

        if (response?.redirect) window.location.assign(response.redirect);
    } catch {
        // Autofill is optional; the password form remains available.
    }
}

enablePasskeyAutofill();
document.addEventListener('livewire:navigated', enablePasskeyAutofill);
document.addEventListener('livewire:navigating', () => Passkeys.cancel());
