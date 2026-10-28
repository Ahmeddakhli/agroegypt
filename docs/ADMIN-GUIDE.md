# Dashboard guide

This guide is for the people who keep agroegypt.com up to date. Everything on the website is edited from the dashboard at **/admin**. You don't need a developer, and changes appear on the site as soon as you save.

## Signing in and the dashboard language

Sign in at `/admin` with your email and password. If you forget your password, use **Forgot password?** on the sign-in page; an administrator can also send you a reset link.

The dashboard works in **English and Arabic**. To switch, open the user menu (your initials, top corner) and choose **العربية** or **English**. Arabic switches the whole dashboard to right-to-left. The choice lasts until you sign out. Before you choose, the dashboard follows your browser's language.

The home screen shows:

- counts of published pages, products, categories and languages
- how much of the content is translated
- new messages
- recent activity, and shortcuts for common tasks

## Who can do what

Each account has one or more roles:

| Role | Can |
|---|---|
| Super admin | Everything, including managing administrators and roles |
| Admin | Everything except administrators and roles |
| Editor | Pages, products, categories, galleries, media, menus, sliders and documents |
| Translator | Edit the wording of existing content and interface translations. Can't create or delete anything. |
| Inbox | Contact messages and job applications only |

Roles are managed under **Users & security → Roles & permissions**, where each area has view / create / update / delete permissions. The super-admin role can't be edited.

## Working in two languages

Every text field that differs per language sits in **language tabs** (English, العربية, and any language you add later). Some things to know:

- A tab marked **Missing** has no text for that language yet. The website then shows the fallback language instead (English for Arabic, unless configured otherwise).
- Leaving every field of a language empty removes that translation. It doesn't save blank text.
- Arabic tabs are typed right-to-left automatically.
- The Arabic texts imported from the old website were machine-assisted. Have a native speaker review them.

To see what still needs translating, open **Localization → Content translations**. It shows complete / partial / missing per language and per area, with a **Translate** link to each record.

## Pages

**Content → Pages** lists the website's pages. Each page has three tabs:

- **Content & SEO:** per language: title, URL slug, SEO title, keywords, description and the image shown when the page is shared on social media.
- **Sections:** the page is built from sections stacked top to bottom. **Add section**, pick a type, fill in its fields, and drag to reorder. Collapse sections to keep long pages manageable, and use **Visible** to hide one without deleting it.
- **Publishing:**
  - **Status:** Draft or Published.
  - **Publish at:** schedule a page to go live at a set time.
  - **Template:**
    - **Home page**, **Products hub** and **Gallery hub** can each be used by one page only.
    - The products hub's slug starts every product URL, and the gallery hub's slug starts every album URL.
  - **Page header:** whether to show the header with title and breadcrumb, and its image.

The section types reproduce the blocks of the original website:

| Group | Section types |
|---|---|
| Banners and headings | hero slider, band heading, full image, video, map |
| Grids | icon grid, feature grid, image card grid, catalog grid, gallery grid, certificates |
| Text and images | image + text, overlap image, mission, progress bars, rich text, call-to-action with background |
| Contact | market regions, contact info, contact form, careers form |
| Advanced | custom HTML |

Some section types share a field to choose the image side, background colour, entrance animation, number of columns, and so on.

**Custom HTML** is limited to people allowed to change site settings. Scripts, styles, iframes and event handlers are always removed from it.

Page actions:

- **Preview (saved version):** while editing a page, opens it as saved, even if it's a draft, in the chosen language. The preview link is private and expires after an hour.
- **Duplicate:** in the page list, makes a draft copy with all its sections, for building a similar page.
- **Delete:** moves the page to the trash. Filter the list by **Deleted records** to restore it.
- **Publish / Unpublish:** select several pages and change their status in one step.

## Products and categories

**Catalog → Categories** holds the product tree, for example Citrus → Oranges. Each category has:

- per-language name, slug, menu label, intro text and SEO fields
- its parent category and visibility
- card, icon and page-header images

A category can't be deleted while it still contains products or subcategories.

**Catalog → Products** has these tabs:

- **Content:** per-language name, slug, description and SEO fields.
- **Settings & images:**
  - category (or directly under Our Products)
  - status, order and *featured*
  - main image, card image, page-header image (empty = taken from the category), seasonality icon and line icon
  - an image gallery
- **Specifications:** the sizes / counts list (type a value and press Enter) and the varieties.
- **Packaging:** each packaging type with its icon, title and figures, for example "20 / No. of Pallets/Container".
- **Seasonality:** one row per variety, with the months it's available.

**Duplicate** creates a draft copy of a product with all its varieties, packaging and seasons.

**URLs update themselves.** When you rename a slug or move a product or category, the old address keeps working because the site creates a permanent redirect automatically.

## Galleries, sliders and documents

- **Content → Galleries:** photo albums. Add images one by one in the album, or use **Bulk upload** in the list to add up to 50 at once. Drag images to reorder them. **Show as large card** gives an album the wide tile on the gallery page.
- **Content → Sliders:** the home page slider. You can set:
  - the transition (zoom, fade or slide)
  - autoplay, time per slide and arrows
  - the height on desktop, tablet and mobile
  - slides, each with an image, an optional mobile image, per-language text and a button

  A page shows a slider through a **Hero slider** section that refers to the slider's key.
- **Content → Certificates & documents:** certificates (the logo band on the home page), the catalogue and brochures. When a page button links to a document instead of a file, replacing the document's file keeps the button working.

## Media library

**Content → Media library** holds every image and file used on the site.

- **Upload files** adds several files at once. Uploading the same file twice reuses the existing copy.
- Edit an item to rename it, put it in a folder, and write its **alternative text** per language. Alt text describes the image for screen readers and search engines.
- **Replace** uploads a new version of the file. Every page using it shows the new version, and its address doesn't change.
- An item that's still in use can't be deleted. The edit screen shows how many places use it.
- Images are resized into lighter versions automatically. Allowed types are JPEG, PNG, WebP, GIF, SVG (without scripts), PDF, Word, Excel and MP4, up to 20 MB each.

## Menus

**Content → Menus** edits the header and footer menus. Switch between them with the tabs at the top.

- **Add menu item:**
  1. Write the label in each language.
  2. Choose what it links to: a page, product category, product, gallery album, or a custom URL. Links to records follow them when their address changes.
  3. Choose a **Parent item** to place it in a dropdown.
- Use the up and down arrows to reorder items at the same level, and **Visible** to hide an item.
- Deleting an item also deletes its sub-items.
- The **Preview** at the bottom shows the saved menu in every language.

## Settings and appearance

**Settings → Site settings** has these tabs:

- **Company & contact:** company name and address per language; phone, fax and email.
- **Social media:** profile links, and the WhatsApp number for the chat button (empty hides it).
- **SEO:** page title format, default description, search-engine indexing, robots.txt paths and the default sharing image.
- **Email notifications:**
  - whether new contact messages and job applications are emailed
  - who receives them (comma-separated addresses)
  - Mail server credentials are set on the server, not here.
- **Analytics & cookies:** the Google Analytics 4 ID, and the cookie consent banner (analytics loads only after consent).
- **System:**
  - the dashboard's time zone
  - **Maintenance mode**: visitors see a maintenance page, while signed-in staff can still browse the site.

**Settings → Appearance, header & footer** has these tabs:

- **Colours:** dark green, secondary dark, primary, primary hover, accent, certificates band, text and page background.
- **Typography & layout:**
  - fonts for Latin and Arabic text
  - card corner radius, section spacing and card shadow
  - animations and hover effects
- **Logos & images:** header logo, footer logo, favicon, default page-header image and divider icon.
- **Header:** language switcher, sticky header, and an optional button (label per language).
- **Footer:** footer texts per language and the contact icons. Footer columns with nothing to show are hidden.

**Live preview** at the bottom shows the site with your colour, font and layout changes *before* you save, in any language. Logos and texts appear in the preview once saved.

**Settings → URL redirects** sends old addresses to new ones (301 permanent or 302 temporary) and counts how often each is used. Redirects for the old website's addresses are already in place.

## Languages and interface text

**Localization → Languages** manages the site's languages.

- **Add a language:**
  1. Set its code (for example `fr`, which becomes the URL prefix `/fr/…`), English and native names, text direction, fallback language and flag.
  2. Every form gains a tab for it immediately, and the site serves it as soon as it's **Enabled**.
- **Default language:** the one visitors get at `/` when their browser language isn't offered. Only one language can be the default, and it can't be disabled or deleted.
- **Code locked:** once content uses a language, its code can't be changed. To retire a language, disable it.

**Localization → Interface translations** holds the site's fixed wording: buttons, form labels, validation messages and the like, with one column per language. Missing texts can be filtered, edited in place, and imported or exported as CSV or JSON for a translator. **Scan language files** picks up new strings added by a developer without touching your edits.

## Inbox

**Inbox → Contact messages** and **Inbox → Job applications** list form submissions, newest first. Unread entries are bold, and the menu shows how many are new.

- Opening a submission marks it as read.
- **Status & notes** sets the status (New, In progress, Replied, Archived) and keeps internal notes.
- Job applications have **Download CV**. CVs are stored privately and are never publicly reachable.
- Filter by status, read state or date. Select entries to mark them read or unread, delete them, or **Export CSV**.

## Accounts and security

**Users & security → Administrators** manages accounts:

- Passwords need at least 12 characters with letters and numbers.
- **Account active** off blocks sign-in without deleting the account.
- **Send password reset** emails the person a reset link.
- You can't deactivate or delete your own account, and only super admins can edit other super admins.

**Activity log** records who created, changed or deleted what and when, including translation-only edits. **Sign-in history** lists every sign-in attempt with its IP address. Both are read-only.
