import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    workers: 1,
    use: {
        baseURL: 'http://127.0.0.1:8020',
        colorScheme: 'light',
        launchOptions: {
            ...(process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH
                ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH }
                : {}),
        },
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8020',
        url: 'http://127.0.0.1:8020/login',
        reuseExistingServer: !process.env.CI,
    },
});
