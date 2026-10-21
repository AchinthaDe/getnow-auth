# GetNow authentication pages

`/login` and `/registration` share the GetNow layout, input component, theme toggle, and animations. `/register` redirects to `/registration`.

The toggle stores `getnow-auth-theme` locally. Without a saved choice, the pages follow the system color scheme. Animations turn off when the browser requests reduced motion. The seven-field registration form can scroll on short screens so fields and errors stay readable.

Registration requires full name, username, email, address, mobile phone, password, and password confirmation. Usernames accept 3–30 letters, numbers, or underscores and are stored lowercase. Addresses accept 5–500 characters. Mobile numbers accept 7–15 digits with an optional leading `+`; spaces, brackets, dots, and dashes are removed before validation and storage. The existing password policy applies (8 characters in development; the stronger configured policy in production).

The users table stores `address` and `mobile_phone` as nullable columns for compatibility with existing accounts. New registrations require both. Contact details are excluded from public model serialization. Fortify handles hashed passwords, session login, verification emails, guest redirects, and CSRF protection; registration and login submissions are rate limited.

Apply the migration and build assets:

```sh
php artisan migrate
npm run build
```

Run the backend and browser checks:

```sh
php artisan test --compact
vendor/bin/pint --dirty --test
vendor/bin/phpstan analyse --no-progress --memory-limit=512M
npx playwright install chromium
npm run test:browser
```

The browser suite starts Laravel on port 8020. It checks responsive fields in both themes, theme persistence, password toggles, motion preferences, invalid registration, and middleware behavior. It does not create user accounts. To use an installed Chromium browser, set `PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH` to its executable path before running the suite. Backend tests use an isolated SQLite database in memory, forced by `phpunit.xml` so development database settings cannot override it. They cover successful registration, login, logout, email verification, two-factor authentication, validation, and throttling.
