# AgroEgypt

The AgroEgypt company website (English and Arabic) with a content management dashboard. It rebuilds agroegypt.com on Laravel: every page, product, gallery, menu, setting and interface string is edited from the dashboard, and languages can be added without code changes.

- **Site:** `/{locale}/…`, for example `/en/our-products/citrus/orange/navel` or `/ar/about-us`. `/` redirects to the visitor's language, or to the default language.
- **Dashboard:** `/admin`, in English or Arabic (switch from the user menu). How to use it: [docs/ADMIN-GUIDE.md](docs/ADMIN-GUIDE.md).
- **Background:** [docs/IMPLEMENTATION-PLAN.md](docs/IMPLEMENTATION-PLAN.md) (architecture and schema), [docs/PROGRESS.md](docs/PROGRESS.md) (status, decisions, the local server setup) and [docs/audit/](docs/audit/) (the audit of the original site).

## Stack

Laravel 13 on PHP 8.4, MariaDB 10.11, Filament 5 for the dashboard, Blade with Tailwind 4 and Alpine.js on the site. The packages and the reasons for each choice are listed in the implementation plan.

## Setting up a new environment

Requirements: PHP 8.4 with `gd`, `intl`, `pdo_mysql` and `fileinfo`; Composer 2; Node 22; MariaDB 10.11+ or MySQL 8.

```sh
composer install
npm ci && npm run build
cp .env.example .env && php artisan key:generate
# Set DB_* in .env (DB_CONNECTION=mariadb), then:
php artisan migrate --seed          # languages, roles, and the full reference content with its images
php artisan storage:link
php artisan app:create-admin --name="Your Name" --email=you@example.com
```

`migrate --seed` imports the original site's content: English, plus Arabic translations that still need a native speaker's review. The import skips itself when pages already exist, so running it again never overwrites edits.

The web server's document root is `public/`.

### Production settings

| Setting | Value |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | The public `https://` address (used in the sitemap, canonical URLs and emails) |
| `SESSION_SECURE_COOKIE` | `true` when the site is served over HTTPS |
| `MAIL_*` | The mail server for submission notifications. Recipients are set in the dashboard under Settings → Site settings → Email notifications. |

Cache, sessions and the queue use the database, so no Redis is needed. After each deployment run:

```sh
php artisan migrate --force
php artisan optimize
```

Email notifications are queued. Keep a worker running under a process manager such as systemd or Supervisor:

```sh
php artisan queue:work --tries=3
```

## Commands

| Command | Purpose |
|---|---|
| `php artisan app:create-admin` | Create an administrator, or promote an existing user. Options: `--role=super-admin\|admin\|editor\|translator\|inbox`. |
| `php artisan app:sync-translations` | Import new interface strings from `lang/` into the dashboard's translation editor. It never overwrites edits made in the dashboard. |

## Tests

```sh
php artisan test --compact           # feature tests on in-memory SQLite (about 130 tests)
vendor/bin/pint --dirty              # code style
```

### Browser tests

`tests/Browser/site.spec.cjs` drives the running local site (`http://agroegypt.local`, override with `BROWSER_BASE_URL`) in the installed Google Chrome, at desktop and mobile sizes. It covers all 11 page types in both languages and checks for:

- the correct `lang` and `dir`
- console errors, failed requests and broken images
- horizontal overflow
- full-page screenshots

It also tests the menus, the language switcher, the lightbox, the slider, back-to-top and the contact form's validation.

It uses the Playwright that ships with `@playwright/cli` (`npm i -g @playwright/cli`):

```sh
P=$(npm root -g)/@playwright/cli/node_modules
NODE_PATH=$P node $P/playwright/cli.js test --config tests/Browser/playwright.config.cjs --update-snapshots  # first run: record the baseline
NODE_PATH=$P node $P/playwright/cli.js test --config tests/Browser/playwright.config.cjs                     # later runs: compare
```

Screenshots depend on the machine's fonts and on the database content, so the baselines are not committed (`tests/Browser/__screenshots__` is gitignored). Record a baseline on a known-good state before making visual changes. After a change, check the diff images in `storage/framework/testing/playwright/`; if the change was intended, record the baseline again.
