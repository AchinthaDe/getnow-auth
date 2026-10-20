import { expect, test } from '@playwright/test';

for (const path of ['/login', '/registration']) {
    test(`${path}: responsive controls, both themes, and password visibility`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        for (const viewport of [{ width: 1366, height: 768 }, { width: 390, height: 844 }, { width: 320, height: 568 }]) {
            await page.setViewportSize(viewport);
            await page.goto(path);
            await expect(page.locator('.login-card')).toHaveCSS('opacity', '1');
            for (const theme of ['light', 'dark']) {
                if (await page.locator('html').getAttribute('data-auth-theme') !== theme) {
                    await page.getByRole('button', { name: `Switch to ${theme} theme` }).click();
                }
                await expect(page.locator('html')).toHaveAttribute('data-auth-theme', theme);
                expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBe(viewport.width);
                const controlsFit = await page.locator('.login-input-wrap').evaluateAll((wrappers) => wrappers.every((wrapper) => {
                    const outer = wrapper.getBoundingClientRect();
                    return [...wrapper.children].every((child) => {
                        const rect = child.getBoundingClientRect();
                        return rect.left >= outer.left - 1 && rect.right <= outer.right + 1 && rect.bottom <= outer.bottom + 1;
                    });
                }));
                expect(controlsFit).toBe(true);
                await expect(page.locator('.card-logo img')).toBeVisible();
            }
        }
        await page.locator('#password').fill('VisiblePassword123!');
        await page.getByRole('button', { name: 'Show password', exact: true }).click();
        await expect(page.locator('#password')).toHaveAttribute('type', 'text');
        await page.getByRole('button', { name: 'Hide password', exact: true }).click();
        await expect(page.locator('#password')).toHaveAttribute('type', 'password');
        if (path === '/registration') {
            await page.getByRole('button', { name: 'Show confirm password', exact: true }).click();
            await expect(page.locator('#password_confirmation')).toHaveAttribute('type', 'text');
        }
        expect(errors).toEqual([]);
    });
}

test('theme persists through sign up, sign in, and reload', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('button', { name: 'Switch to dark theme' }).click();
    await page.getByRole('link', { name: 'Sign up', exact: true }).click();
    await expect(page).toHaveURL(/\/registration$/);
    await expect(page.locator('html')).toHaveAttribute('data-auth-theme', 'dark');
    await page.reload();
    await expect(page.getByRole('button', { name: 'Switch to light theme' })).toHaveAttribute('aria-pressed', 'true');
    await page.getByRole('link', { name: 'Sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/login$/);
    await page.getByRole('button', { name: 'Switch to light theme' }).click();
    await page.reload();
    await expect(page.locator('html')).toHaveAttribute('data-auth-theme', 'light');
});

test('system theme and reduced motion preferences are respected', async ({ page }) => {
    await page.emulateMedia({ colorScheme: 'dark', reducedMotion: 'reduce' });
    await page.goto('/registration');
    await expect(page.locator('html')).toHaveAttribute('data-auth-theme', 'dark');
    await expect(page.locator('.login-card')).toHaveCSS('animation-name', 'none');
    await expect(page.locator('.login-sculpture')).toHaveCSS('animation-name', 'none');
    await expect(page.locator('.login-card')).toHaveCSS('transition-duration', '0s');
});

test('entry and artwork animations run when motion is enabled', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    await page.goto('/login');
    await expect(page.locator('.login-card')).toHaveCSS('animation-name', 'auth-enter');
    await expect(page.locator('.login-sculpture')).toHaveCSS('animation-name', 'auth-float');
});

test('invalid registration round trip preserves theme and safe input with field errors', async ({ page }) => {
    await page.goto('/registration');
    await page.getByRole('button', { name: 'Switch to dark theme' }).click();
    await page.getByLabel('Full name', { exact: true }).fill('Validation Preview');
    await page.getByLabel('Username', { exact: true }).fill('preview_user');
    await page.getByLabel('Email address', { exact: true }).fill('invalid');
    await page.getByLabel('Mobile phone', { exact: true }).fill('not-a-phone');
    await page.getByLabel('Password', { exact: true }).fill('short');
    await page.getByLabel('Confirm password', { exact: true }).fill('different');
    await page.locator('.registration-form').evaluate((form) => { form.noValidate = true; });
    await page.getByRole('button', { name: 'Create account', exact: true }).click();
    for (const name of ['email', 'mobile_phone', 'address', 'password', 'password_confirmation']) {
        await expect(page.locator(`#${name}-error`)).toBeVisible();
        await expect(page.locator(`#${name}`)).toHaveAttribute('aria-invalid', 'true');
    }
    await expect(page.locator('#name')).toHaveValue('Validation Preview');
    await expect(page.locator('#password')).toHaveValue('');
    await expect(page.locator('#password_confirmation')).toHaveValue('');
    await expect(page.locator('html')).toHaveAttribute('data-auth-theme', 'dark');
    await expect(page.getByRole('button', { name: 'Create account', exact: true })).toBeEnabled();
});

test('native required validation does not leave the submit button disabled', async ({ page }) => {
    await page.goto('/registration');
    await page.getByRole('button', { name: 'Create account', exact: true }).click();
    await expect(page).toHaveURL(/\/registration$/);
    await expect(page.getByRole('button', { name: 'Create account', exact: true })).toBeEnabled();
    await expect(page.locator('.registration-form')).not.toHaveAttribute('aria-busy');
});

test('CSRF and guest access middleware protect the browser routes', async ({ request }) => {
    for (const path of ['/registration', '/login']) {
        const response = await request.post(path, { form: { email: 'preview@example.com' } });
        expect(response.status()).toBe(419);
    }
    const dashboard = await request.get('/dashboard', { maxRedirects: 0 });
    expect(dashboard.status()).toBe(302);
    expect(dashboard.headers().location).toMatch(/\/login$/);
});
