const themeKey = 'getnow-auth-theme';
const systemTheme = matchMedia('(prefers-color-scheme: dark)');

function applyTheme(theme) {
    document.documentElement.dataset.authTheme = theme;
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(theme === 'dark'));
        button.setAttribute('aria-label', `Switch to ${theme === 'dark' ? 'light' : 'dark'} theme`);
    });
}

function resetSubmitButtons() {
    document.querySelectorAll('.login-form[aria-busy]').forEach((form) => {
        form.removeAttribute('aria-busy');
        form.querySelector('[type="submit"]').disabled = false;
    });
}

applyTheme(document.documentElement.dataset.authTheme);

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-theme-toggle]')) return;
    const theme = document.documentElement.dataset.authTheme === 'dark' ? 'light' : 'dark';
    applyTheme(theme);
    try { localStorage.setItem(themeKey, theme); } catch { /* Theme still works without storage. */ }
});

systemTheme.addEventListener('change', (event) => {
    try { if (localStorage.getItem(themeKey)) return; } catch {}
    applyTheme(event.matches ? 'dark' : 'light');
});

window.addEventListener('storage', (event) => {
    if (event.key === themeKey) {
        applyTheme(['light', 'dark'].includes(event.newValue) ? event.newValue : (systemTheme.matches ? 'dark' : 'light'));
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.matches('.login-form') || event.defaultPrevented) return;
    if (form.getAttribute('aria-busy') === 'true') {
        event.preventDefault();
        return;
    }
    form.setAttribute('aria-busy', 'true');
    form.querySelector('[type="submit"]').disabled = true;
});

window.addEventListener('pageshow', resetSubmitButtons);
document.addEventListener('livewire:navigated', () => {
    applyTheme(document.documentElement.dataset.authTheme);
    resetSubmitButtons();
});
