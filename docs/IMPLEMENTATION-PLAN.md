# AgroEgypt — Implementation Plan

This plan is based on the audit in [audit/AUDIT.md](audit/AUDIT.md). Progress is tracked in [PROGRESS.md](PROGRESS.md).

## 1. Stack (verified to resolve together on this machine)

| Concern | Choice | Why |
|---|---|---|
| Framework | Laravel 13.34, PHP 8.4 | Already installed |
| Database | MariaDB 10.11 through Laravel's native `mariadb` driver | The existing server is reused. MySQL 8 would conflict with MariaDB's Debian packages and other apps' databases (see PROGRESS env notes). |
| Admin | **Filament 5.9** (Livewire 4) at `/admin` | Gives tables, filters, bulk actions, drag-reorder, uploads and forms across about 20 modules without hand-writing CRUD |
| Roles | spatie/laravel-permission 8 | Granular `{action} {module}` permissions |
| Content translations | astrotomic/laravel-translatable 11 | Per-model `*_translations` tables, which the brief asks for. Locales are loaded **from the `languages` table at boot**, never from config. |
| UI string translations | spatie/laravel-translation-loader 2.8 | Stores DB `language_lines` that override `lang/*.php`. Admins edit buttons, labels and validation messages without deploys. |
| Images | intervention/image 4 (GD) | Responsive WebP variants created on upload |
| Rich text safety | symfony/html-sanitizer 8 | Allow-list sanitising for rich text and the "custom HTML" section |
| Frontend | Blade, Tailwind 4 (already in the skeleton), Alpine.js, Vite 8 | Small bundle. The slider, carousel, lightbox and menu are small Alpine components, so no Swiper or jQuery. |
| Fonts | `@fontsource/heebo`, `@fontsource/cairo` (woff2, self-hosted) | Heebo matches the reference; Cairo covers Arabic, which Heebo lacks |
| Cache and queue | `database` cache and queue drivers | Redis on :6379 belongs to another project's Docker stack, so it isn't shared |
| Tests | PHPUnit 12 (in the skeleton) on SQLite in memory; Playwright for browser and visual regression | |

## 2. Database schema

`*_translations` tables all have `(parent_id, locale)` unique with an FK cascade, and `locale` references `languages.code`.

| Area | Tables |
|---|---|
| Auth | `users` (adds is_active, last_login_at), `password_reset_tokens`, spatie `roles`/`permissions`/pivots, `login_logs`, `audit_logs` |
| i18n | `languages` (code, name, native_name, direction ltr/rtl, flag_media_id, fallback_code, is_default, is_active, sort), `language_lines` (spatie: group, key, text json) |
| Media | `media_folders`; `media` (folder_id, disk, path, original_name, mime, size, width, height, sha1 **unique** for dedup, variants json); `media_translations` (alt, title) |
| CMS | `pages` (template, parent_id, status, published_at, sort, hero_media_id, soft deletes); `page_translations` (title, slug, meta_title, meta_description, og_media_id); `page_sections` (page_id, type, sort, is_visible, settings json for design only: bg colour/image, spacing, animation); `page_section_translations` (title, subtitle, body, button_label, button_url, data json for type-specific localised fields); `section_items` (section_id, sort, media_id, icon_media_id, url, value); `section_item_translations` (title, body, label) |
| Catalogue | `categories` (parent_id, icon_media_id, card_media_id, hero_media_id, sort, is_active, show_in_home, soft deletes); `category_translations` (name, slug, description, meta_*); `products` (category_id nullable for root products like Mango, card/image/hero/season_icon media, spec_values json, status, is_featured, sort, soft deletes); `product_translations` (name, slug, subtitle, short_description, description, spec_label, meta_*); `product_images` (gallery, sort); `product_varieties` + translations; `product_packagings` (icon) + translations (title); `packaging_specs` (packaging_id, icon_media_id, value) + translations (label); `product_seasons` (product_id, months json of ints, sort) + translations (label) |
| Galleries | `galleries` (cover media, sort, is_active) + translations (title, slug); `gallery_images` (gallery_id, media_id, sort) |
| Navigation | `menus` (location: header, footer, mobile); `menu_items` (menu_id, parent_id, type: page/category/product/gallery/url, linkable morph, url, target, sort, is_visible) + translations (label) |
| Slider | `sliders` (key, autoplay, interval_ms, transition_ms, effect fade/slide/zoom, arrows, heights json); `slides` (slider_id, media_id, mobile_media_id, url, sort, is_active) + translations (title, subtitle, button_label) |
| Documents | `documents` (type: certificate/catalogue/brochure/other, file_media_id, image_media_id, sort, is_active) + translations (title, description) |
| Forms | `contact_messages` (name, email, phone, subject, message, locale, status, read_at, notes, ip, user_agent, soft deletes); `job_applications` (+ job_title, cv_path on the **private** disk) |
| Settings and SEO | `settings` (group, key, locale nullable, value), unique on (group, key, locale), covering company, contact, social, theme, mail, analytics, cookie, robots and maintenance; `redirects` (from_path unique, to_path, code 301/302, hits) |

Multi-record writes, such as a product with its packagings, seasons and translations, or page duplication, run inside `DB::transaction`.

## 3. Routing and URLs

- `GET /` redirects to `/{default-locale}`, or to the browser's preferred locale when it's active.
- **Locale prefix:** `/{locale}/…` with `SetLocale` middleware. The locale must be an **active row in `languages`**, so it's never hardcoded. The middleware sets `app()->setLocale()`, Carbon's locale, `dir` and the astrotomic fallback (the language's `fallback_code`, then the default language).
- **Content routes:** `/{locale}/{path}` goes to one resolver:
  - The first segment is matched against `page_translations.slug` for that locale.
  - When the page's template is `products`, the remaining segments walk `category_translations.slug` and end at a category or a product, giving `/en/our-products/citrus/orange/navel`.
  - When the template is `gallery`, the next segment is an album.
- **Legacy URLs:** any non-matching path goes through the `redirects` table. It's seeded with all 40 reference URLs, so `/our-products/citrus/mandarine-2/` gets a 301 to `/en/our-products/citrus/mandarins`.
- **SEO output:**
  - Each page emits a `canonical` URL and an `hreflang` link for every active locale that has a translation, plus `x-default`.
  - `/sitemap.xml` is generated from the database and cached, with entries for every locale.
  - `/robots.txt` is built from settings.
  - Organization JSON-LD on Home and Product JSON-LD on product pages.
- **Forms:** `POST /{locale}/contact` and `/{locale}/careers` are rate-limited to 5 per minute per IP and include a honeypot. Validation messages come from translatable `language_lines`.

## 4. Frontend architecture

- **Layout:** `layouts/site.blade.php` sets `<html lang dir>` and emits a `:root{--c-primary…}` block built from the theme settings. The values are validated as hex, px or numbers, so no raw CSS gets through.
- **Theme components:** header (desktop dropdown up to 3 levels plus mobile accordion), page-title hero, footer, the language switcher (active languages and their flags), back-to-top and the WhatsApp link.
- **Page sections:** one Blade partial per section type: `hero_slider`, `icon_grid`, `feature_grid`, `band_heading`, `certificates_carousel`, `cta_background`, `image_text`, `rich_text`, `overlap_image`, `progress_bars`, `image_card_grid`, `market_regions`, `full_image`, `contact_info`, `contact_form`, `careers_form`, `map`, `gallery_grid`, `video`, `custom_html` (sanitised, permission-gated). Each is shaped to reproduce the original layout rather than forced into a generic template.
- **Catalogue templates:** category listing (card grid with the hang hover) and product detail (the reference template: description, varieties, packaging, seasonality, counts and image).
- **RTL:** CSS uses logical properties throughout (`ms-*`, `me-*`, `ps-*`, `start-*`, `text-start`). Chevrons and arrows flip with `rtl:` variants, slider direction follows `dir`, and the hang and fade animations are direction-neutral. The Arabic font is Cairo.
- **Accessibility and performance:**
  - `prefers-reduced-motion` turns off autoplay and animations.
  - Images use `srcset` with WebP variants and `loading="lazy"` below the fold. The hero image gets `fetchpriority="high"`.

## 5. Admin (Filament) modules

| Area | Modules |
|---|---|
| Overview | Dashboard with real counts (published pages, products, categories, languages, translation completion %, new messages), recent audit log and quick actions |
| Content | Pages with section builder (repeater with a type picker, drag reorder, per-locale tabs, preview link to the signed `?preview=` URL, duplicate, schedule), Galleries, Documents and Certificates, Sliders |
| Catalogue | Categories (tree), Products (tabs for basics, translations, varieties, packaging, seasonality, gallery and SEO; bulk publish/unpublish/delete; filters) |
| Appearance | Menus (nested drag-sort, translated labels, live preview), Header and Footer, Theme (colours, fonts, radius, spacing, animations, with a preview pane) |
| Localisation | Languages (add, set default, toggle, direction, fallback, flag; deletion guarded), Translation editor (search, filter by locale/group/missing, completion %, side-by-side, CSV/JSON import and export) |
| Media | Library (folders, search, preview, rename, replace keeping the URL, alt text per locale, dimensions and size, delete only when unused) |
| Inbox | Contact messages and job applications (search, date and status filters, read/unread, notes, export CSV, signed CV download), with email notification settings |
| SEO | Redirects, global SEO, robots, sitemap ping |
| System | Users (activate/deactivate, roles), Roles and Permissions, Login history, Audit log, Settings (company, contact, social, mail, timezone, maintenance, analytics, cookie consent) |

**Roles seeded:**

| Role | Access |
|---|---|
| `super-admin` | Everything |
| `admin` | Everything except users and roles |
| `editor` | Content, catalogue, media and menus |
| `translator` | Translations only |
| `inbox` | Messages only |

## 6. Phase sequence (each ends with tests passing and a PROGRESS entry)

1. ✅ Environment and repository audit
2. ✅ Reference audit, inventories and asset collection
3. Install packages, design tokens, fonts and the base layout
4. Schema: migrations, models, factories
5. Auth, roles, permissions, audit and login logs
6. Languages, locale middleware, translation loader and the translation editor
7. Filament panel shell and dashboard
8. Media library and settings
9. Pages and section builder, plus preview
10. Categories and products
11. Menus, header and footer
12. Sliders, galleries and documents
13. Public templates, page by page (Home, About, Products, Category, Product, Brands, Markets, Gallery, Album, Careers, Contact)
14. Forms, notifications, SEO (meta, hreflang, sitemap, robots, JSON-LD, redirects)
15. Performance (eager loading, caching with observers that invalidate, indexes, query counts measured in tests) and a security review
16. Feature and multilingual tests, Playwright browser tests, visual regression against the `*-fixed.png` references
17. Final verification, README, admin guide, final report

The seeder imports the reference content from `docs/audit/content/*.json` and the assets from `resources/reference-assets/`. The import is idempotent and produces the full English site, Arabic translations flagged for review, and the legacy redirects.
