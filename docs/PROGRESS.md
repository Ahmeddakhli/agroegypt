# Progress Log

## Local environment (configured 2026-10-02)

| Component | State |
|---|---|
| PHP | 8.4.25. The CLI runs it, and the web uses a **dedicated FPM pool** `/etc/php/8.4/fpm/pool.d/agroegypt.conf` that runs as `dakhli`, with the socket at `/run/php/php8.4-fpm-agroegypt.sock` |
| Laravel | 13.34.0 |
| Composer | 2.10.3 at `/usr/local/bin/composer`. The Debian 2.5.5 package is left in place at `/usr/bin/composer`. |
| Node / npm | 22.23.2 / 10.9.8 |
| Database | Existing **MariaDB 10.11.18** (systemd `mariadb`, enabled at boot). Database `agroegypt` (utf8mb4_unicode_ci), user `agroegypt@localhost` with `ALL` on `agroegypt.*` only. The password is in `.env` only, which has mode 600 and is gitignored. |
| Web server | Existing **Apache 2.4**. vhost `/etc/apache2/sites-available/agroegypt.local.conf` with docroot `public/`, `Require local`, `AllowOverride All`, and PHP through the FPM pool. Modules `proxy_fcgi`, `rewrite` and `setenvif` enabled. `/etc/hosts` has `127.0.0.1 agroegypt.local`. |
| phpMyAdmin | Existing Debian package at http://localhost/phpmyadmin (cookie auth). It's now restricted to local requests through `/etc/apache2/conf-available/phpmyadmin-local-only.conf`. |
| Permissions | ACL `u:www-data:x` on `/home/dakhli` (traverse only, so Apache can serve `public/`) |
| Config backups | `/root/config-backups-agroegypt-20261002214833/` holds the original `/etc/apache2`, `/etc/hosts`, the FPM pool.d and `/etc/phpmyadmin` |
| Untouched | Other MariaDB databases (`buss`, `kayan`, `farog`, …), the default vhost, PHP 8.2 mod_php, and the Docker CRM stack (MySQL 8.3 :33060, Redis :6379, phpMyAdmin :8812) |

**To roll back:**

```sh
sudo a2dissite agroegypt.local
sudo a2disconf phpmyadmin-local-only
sudo rm /etc/php/8.4/fpm/pool.d/agroegypt.conf
sudo systemctl restart php8.4-fpm
sudo systemctl reload apache2
```

Then remove the hosts line.

## Phases

| # | Phase | Status | Notes |
|---|---|---|---|
| 1 | Environment and repository audit | ✅ Done | See above |
| 2 | Reference audit, inventories and assets | ✅ Done | `docs/audit/*`. 40 pages, 120 + 120 screenshots, 199 assets (32 MB) in `resources/reference-assets/` |
| 3 | Packages, design tokens and layout | ✅ Done | Filament 5.9, spatie permission 8.3 and translation-loader 2.8, astrotomic translatable 11, Intervention Image 4, Symfony HtmlSanitizer, laravel-lang (dev), Alpine and self-hosted Heebo/Cairo. Laravel Boost guidelines installed. Bundle: CSS 17 KB gz, JS 20 KB gz. |
| 4 | Schema, models, factories | ✅ Done | 17 migrations, about 40 tables (see IMPLEMENTATION-PLAN §2). Translatable locales come from the `languages` table at boot. |
| 5 | Auth, roles, permissions, audit and login logs | ✅ Done (backend) | Roles super-admin/admin/editor/translator/inbox, `{action} {module}` permissions, ModulePolicy, `app:create-admin`, audit trail, login history. Tests in AuthorizationTest. |
| 6 | Multilingual foundation | ✅ Done (backend and public) | `/{locale}/…` routing, DB-driven languages, fallback chain, RTL, hreflang, canonical, sitemap and robots. The translation editor UI is part of phase 7. |
| 13 | Public frontend, all page types | ✅ Done, first visual pass | Home, About, Products hub, category, product detail, Brands, Markets, Gallery hub and album, Careers, Contact, 404. Side-by-side reviewed against the `*-fixed.png` references at desktop, mobile and RTL. |
| 14 | Forms, notifications, SEO | ✅ Done | Contact and careers forms (honeypot, 5/min rate limit, private CV storage, queued mail), legacy 301s (44 seeded), JSON-LD |
| — | Reference content import | ✅ Done | `ReferenceContentSeeder`: 8 pages, 23 sections, 5 categories, 18 products, 7 galleries (74 images), 172 media files with WebP variants, menus, settings and full Arabic translations (`database/seeders/data/ar.json`, marked for review) |
| 7–12 | Admin dashboard (Filament) | ✅ Done and verified (2026-10-03) | 27 modules audited; each has Livewire/feature tests (125 tests, 867 assertions in total). See "Admin verification run" below. |
| — | Bilingual dashboard | ✅ Done (2026-10-07) | English/Arabic switch in the user menu (session, browser language by default), RTL in Arabic. Admin code keeps English keys translated through `lang/ar.json`. |
| 15 | Performance and security review | ✅ Done (2026-10-08) | See "Final verification" below |
| 16 | Browser and visual tests | ✅ Done (2026-10-08) | Playwright suite in `tests/Browser`, 56 checks; see README |
| 17 | Final verification and docs | ✅ Done (2026-10-08) | README, [ADMIN-GUIDE.md](ADMIN-GUIDE.md) |

### Known deviations found during the build
- Laravel 13's cache refuses to unserialize objects (`serializable_classes = false`), so all caches hold plain arrays.
- The reference certificate logos are white artwork and `Certificates.png` is fully transparent, so the carousel uses the olive band background and the transparent file is dropped.
- Slugs keep the reference values (`mandarine-2`, `grapefruit-2`) so old and new URLs map one-to-one under `/en`. Admins can rename them, and the redirects table covers the old paths.

## Admin verification run (2026-10-03)

Every admin module was checked with executable tests covering CRUD, validation, authorization, persistence and the effect on the public site. The tests are in `tests/Feature/*AdminTest.php`, `UrlRedirectsTest.php` and `AdminDeleteRestoreTest.php`.

**Bugs found by the tests and fixed:**
- **Page builder fields hidden:** every translated section and item field (title, text, button, map/video URL) was invisible. The fields resolved the section type relative to the language group; they now walk up to find it.
- **Section body lost on save:** the rich text and Custom HTML editors shared the `body` state path, so section bodies were never saved. Custom HTML now has its own field (`body_html`), mapped back to `body`.
- **Cleared translations kept:** clearing a language in a repeater item didn't remove that item's translation row.
- **Menu item creation crashed** when not every language had a label.
- **Delete crashed** on every translatable resource (record title attribute missing).
- **Settings form empty:** seeded language-neutral settings loaded empty, which blocked saving the company name. The phone and fax validation also rejected the company's own format `(+202) …`.
- **Toggles turned off silently:** untouched toggles saved as "off" because defaults weren't applied before stored values.
- **Wrong root redirect:** `/` ignored the default language when the browser's language wasn't offered.
- **Fragile catalogue link:** the homepage button stored a literal file path, so replacing the PDF broke it. The button now links to the Catalogue document.

**Added:**
- **Activity log:** translation and nested-content changes are recorded, logged against the parent record with their language.
- **Language codes:** a code is locked once content, settings or interface strings use it. Deleting a language removes its interface strings and fallback references.
- **Sharing image:** pages have a per-language social sharing image; categories use their card image.
- **Header and theme options:**
  - optional header button (label per language)
  - sticky header
  - card shadow presets
  - page background colour
  - separate hover-effects switch
  - footer columns hidden when empty
- **Animations:** the feature grid follows its section's animation setting.
- **Content translations page** (Localization): coverage per language and module (complete, partial or missing) with edit links.
- **Live preview:** Appearance changes preview before saving, through a signed URL; values are validated and the parameters can't be tampered with.
- **Automatic 301 redirects** when a slug changes or a category or product moves (including descendants), without chains or loops.
- **Validation:** slugs must be lowercase; section, item, slide and header links get the same safe-link validation as menus.
- **Translator role:** can view and update existing content (pages, catalog, galleries, menus, sliders, documents, media) but can't create or delete it.
- **Seeded notifications:** recipients are now empty instead of the real company inbox.

**Local-only check:** all 76 public URLs (38 per language) return 200, and all 235 internal links resolve locally. The only external references are the footer social profiles, the Google Maps embed and `mailto:info@agroegypt.com` (the company's public email). In the codebase, `agroegypt.com` appears only in audit documentation, a CSS comment, the seeder docblock and seed data (the company email address).

## Final verification (2026-10-08)

**Performance:**
- With a warm cache, every public URL (76) runs 7–24 queries, and the count doesn't grow with content: menus, site index and settings are cached and flushed on any content change.
- The full suite and a crawl of every page and dashboard screen were run with lazy-loading violations captured. The only N+1 in app code was the menu editor's link column, which now eager-loads `linkable.translations`. The remaining violations come from Filament's repeater internals during saves and from test assertions.
- Every foreign key and lookup column used for filtering or sorting is already indexed.

**Security:**
- `composer audit`: clean.
- `npm audit`: 2 critical advisories in `shell-quote` through `concurrently`, a dev-only tool used by `composer run dev` that isn't shipped. Upgrading it changes dependencies, so it's left for approval.
- **Added:** `SecurityHeaders` middleware on the site and the dashboard:
  - `nosniff`
  - `X-Frame-Options: SAMEORIGIN` (same origin, because the dashboard previews the site in an iframe)
  - `Referrer-Policy` and `Permissions-Policy`
  - HSTS on HTTPS
- **Fixed:** logo and favicon uploads in Appearance now reject SVGs containing scripts, the same as the media library already did.
- **Reviewed:** every unescaped Blade output either goes through the HTML sanitizer, is escaped first (`nl2br(e())`), or is JSON-LD encoded with `JSON_HEX_TAG`. All models use explicit `#[Fillable]` lists.

**Browser tests (`tests/Browser`):**
- All 11 page types are checked in English and Arabic, at desktop and mobile sizes: language and direction, console errors, failed requests, broken images, horizontal overflow and a full-page screenshot.
- Separate checks cover the menus, the language switcher, the lightbox, the slider, back-to-top and the localized form validation.
- 56 checks; 5 consecutive runs were identical.

**Bugs found and fixed:**
- **English pages showed the Arabic footer and company texts after Arabic was made the default language** (done in the dashboard on 2026-10-03). The reference import had stored the English texts as language-neutral values, so English fell back to the new default language first.
  - The import now stores them as English.
  - Migration `2026_10_07_212615_move_neutral_localized_settings_to_english` converts existing databases. It never overwrites English values that already exist.
  - Places that read the company name without a language now use `Setting::localized()`.
  - Regression test in `TranslationFallbackTest`.
- **Product "Counts" row** wrapped onto two lines. It now spans the full width below the image as on the reference, and the seasonality months use the reference spacing.
- **Form fields** were marked `aria-invalid` for errors from the other form on the page.

**Reference comparison:** the current desktop and mobile renders were checked side by side against the `*-fixed.png` references for home, about, brands, markets, products, category, product, gallery, careers and contact. The layouts match apart from one difference on the home page. The reference capture doesn't show the product icons or the four feature boxes, although both are in the captured content (`reference.json`, `home.features`), so they're rendered here.

**Current state of the local database:** Arabic is the default language (changed by user 1 in the dashboard on 2026-10-03), so `/` sends visitors whose browser language isn't offered to `/ar`.
