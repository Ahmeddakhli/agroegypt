# AgroEgypt Reference Website Audit

- **Reference:** https://agroegypt.com/
- **Audited:** 2026-10-02
- **Method:** WordPress REST API, sitemap and raw HTML (`raw/`), plus headless Chrome renders through Playwright at 1440×900, 768×1024 and 390×844 (`screenshots/reference/`).

Companion files:

| File | Contents |
|---|---|
| [page-inventory.md](page-inventory.md) | Every page, its type, status and screenshots |
| [asset-inventory.md](asset-inventory.md) | Every downloaded original asset, its pixel size and where it's used |
| `content/<wp-id>.json` | Full extracted content per page, including widget text, HTML, images and links |
| `content/products.json` | Structured product tree with packaging, seasonality and counts |
| `raw/` | Original HTML, rendered DOM, CSS, the media library list (`media.json`) and the REST API dumps |

---

## 1. Platform findings

| Item | Finding |
|---|---|
| CMS | WordPress, Elementor and the GT3 "Agrosector" theme (`agrosector-child`) |
| Plugins seen | Revolution Slider 6.6.7, Contact Form 7 with reCAPTCHA v2, All in One SEO Pro 4.3.0, LiteSpeed Cache, Social Snap, MonsterInsights (GA), GT3 Photo Gallery, and leftover WPML styles |
| Languages | **English only.** `/ar/`, `/de/` and the other locale prefixes return 404. WPML CSS is present but there's no switcher and no `hreflang`. All Arabic content in the new build is new. |
| Content | 40 pages, 0 blog posts, 6 `gt3_gallery` items, 512 media items |

### Rendering defects on the live reference (important for the visual comparison)

1. **LiteSpeed delays all JS until the first `mouseover`, `wheel` or `touch` event**, then throws `jQuery is not defined`. As a result:
   - the **Revolution Slider renders at 0px height**, although its 4 slides are in the DOM
   - the progress bars stay empty
   - the animated headline doesn't initialise
2. When the scripts are forced to load in order (`fixrender.js` in the scratch tooling), the slider initialises but its wrapper stays at 0px, so it **sits on top of the next ~800px**: Our Products, the features and the spacer sections. That's a layout bug, not intended design.
3. The homepage **"CERTIFICATS" media carousel has no slides**. Its settings and slide list are empty.
4. The **mobile menu** opens as an inline wrap of the desktop links rather than the theme's vertical accordion.
5. The map iframe and gallery images are lazy-loaded through `data-litespeed-src`, so a plain fetch shows them blank.

So the visual comparison targets the **intended** design, using the `*-fixed.png` renders with each defect corrected:

- slider stacked above the content, 813px tall
- certificate logos taken from the media library (BRC, ISO 22000, GLOBALG.A.P, "Certificates")
- standard vertical mobile menu

Every such deviation is listed in §8.

### Inaccessible or unavailable

- **`/syc/`**: password-protected ("Protected: syc"). No content can be retrieved, so it isn't recreated. A protected-page feature in the CMS covers the use case if it's ever needed.
- **Wayback Machine**: unreachable from this environment (timeouts), so no historical renders.
- **Legacy `Heebo-*.svg` font files**: 404. Not needed.
- **ACERTA certificate logo**: mentioned in the About text, but there's no image in the media library.

---

## 2. Page inventory (summary)

The full table is in [page-inventory.md](page-inventory.md).

| Group | Pages |
|---|---|
| Home | `/` |
| Company | `/about-us/`, `/brands/` ("Our Brands"), `/markets/` ("Our Markets"; `/shipping-markets/` is an unlinked duplicate), `/careers/`, `/contact-us/` |
| Product hub | `/our-products/` |
| Product categories (listing) | `/our-products/citrus/`, `/our-products/citrus/orange/` (subcategory), `/our-products/grapes/`, `/our-products/onions/`, `/our-products/fresh-garlic/` |
| Product details (23) | Citrus: Baladi, Navels, Valencia (under Oranges), Lemons, Mandarins (`mandarine-2`), Grapefruit (`grapefruit-2`) · Grapes: Sugraone, Flame, Red Globe, Crimson, Black Grapes · Pomegranate · Mango · Onions: Yellow, Red, White · Garlic: Red, White |
| Gallery | `/gallery/` hub plus 7 albums: citrus, grapes, pomegranate, mango, garlic, onions, pack-house ("Packing Station") |
| Protected | `/syc/` (inaccessible) |

---

## 3. Global components

### Header (all pages)

- White background, **90px** tall, not sticky (no sticky header on scroll).
- **Layout:** container ~1170px. Logo on the left (`2022/02/logo-200px.png`, about 190×72 displayed). Menu on the right.
- **Menu items:** Heebo 18px/90px, colour `#182719`. The active item and hovered sub-items are `#223a22`, shown as the green active colour.
- **Main menu** (WP `menu-main-menu`). Desktop labels are shown; mobile uses "Products", "Brands" and "Markets".
  - Home
  - About us
  - Our Products ▾ (3 levels)
    - Citrus ›
      - Oranges ›
        - Baladi
        - Navels
        - Valencia
      - Lemons
      - Mandarins
      - Grapefruit
    - Grapes ›
      - Sugraone
      - Flame
      - Red Globe
      - Crimson
      - Black Grapes
    - Pomegranate
    - Mango
    - Onions ›
      - Yellow
      - Red Onion
      - White Onion
    - Garlic ›
      - Red Garlic
      - White Garlic
  - Our Brands
  - Our Markets
  - Gallery
  - Careers
  - Contact us
- **Dropdown:** white panel, **200px** wide, `box-shadow: 0 9px 68px rgba(38,43,43,.1)`, links 16px/25px `#121c10`, a "›" chevron on items with children, `transition: .2s`. Opens on hover; nested panels fly out to the side. Screenshot: `screenshots/reference/state-dropdown-desktop.png`.
- **Mobile (<1200px):** a hamburger toggle (`.mobile-navigation-toggle`) on the right and a smaller logo.

### Page title hero (every page except Home)

- A full-width **~345px** band with a `background-image` (cover) chosen per page or category, e.g. a grapes photo for grape products and orange trees for Careers.
- The H1 is **Heebo 95px/114px, weight 700, white, centred**.
- The breadcrumb sits below it, in white 18px, with **green dot separators** (`#789915`), for example Home • Our Products • Grapes • Sugraone.
- The Contact page shows a faint duplicate title line above the breadcrumb (theme quirk, a low-opacity subtitle).

### Footer (all pages)

- Background **`#182719`**. Top padding about 80px. 4 columns in a ~1170px container:
  1. **Logo column:** white logo (`2020/09/logo512White.png`, about 200px wide) and the text "We do care about our quality because We believe that you deserve the best" (white, 16px/28px).
  2. **Useful Links** (H3 white 24px bold): Home, About us, Our Products, Careers, Contact us (white, 18px, about 42px rhythm).
  3. **Contact Info**, with an orange line icon before each line (`2020/09/lemon-0{9..12}Oranged-300x300.png`):
     - CPC Industrial Park – Plot no (2-3) 6<sup>th</sup> October City, Egypt.
     - (+202) 38642048 (phone)
     - (+202) 38642049 (fax)
     - info@agroegypt.com
  4. **Social Media:** square brand-coloured icon buttons (44px) with a grey tail. Facebook `#3b5998` (facebook.com/egyptian.oranges), Instagram `#c13584` (instagram.com/agroegyptofficial), LinkedIn `#0077b5` (linkedin.com/company/argoegypt), YouTube `#cd201f` (youtube.com/@AgroEgypt/videos). On mobile they stretch to full width.
- **Bottom bar:** a 1px top border at `rgba(255,255,255,.1)`. "© 2020 AgroEgypt. All rights reserved." on the left; "Powered by Mount Wolf" on the right (the link is dark on dark, which reads as a contrast bug).
- **Floating elements:** a back-to-top button (`.gt3_back2top`) and a WhatsApp chat widget, the Social Snap "whatsapp" class.

---

## 4. Page-by-page structure

### Home `/`

1. **Hero slider** (Revolution Slider, full width).
   - **Height:** 813px on desktop, then 600, 500 and 450px at the 1240, 1024, 778 and 480px grid steps.
   - **Behaviour:** 5s autoplay. Transition: fade in from y −50% with scale 0.7 (`power4.inOut`). Mouse parallax. "Zeus" arrows, hidden below 600px.
   - **Slides:** one huge centred white word on each (Heebo ~150px, bold):

     | Slide | Word | Background |
     |---|---|---|
     | 1 | QUALITY | `2022/01/slider2.jpg` |
     | 2 | EFFECTIVENESS | `2022/01/s17ee.jpg` |
     | 3 | RELIABILTY (sic) | `2022/01/s18.jpg` |
     | 4 | SELECTION | `2020/09/3.jpg` |

2. **Our Products:**
   - Background `#182719`. Title "Our Products" (white, 48px, weight 400).
   - **6 icon tiles in a row:** orange line icons, about 60px (`2020/10/{Citrus,Pomegranate,Grapes,Mango,Onions,Fresh-Garlic}.png`), with an orange label (`#eaa84e`, 24px bold).
   - Each tile links to its category or product and has the **"hang" hover**.
3. *(Hidden SEO H1)*: "Agro Egypt Ghallab is one of the top leading companies…". It's rendered at 1px; it isn't kept as visible content.
4. **Features:**
   - Background `#2d3c2e`. A 2×2 grid of image boxes, each an orange icon on the left plus a title (white 24px bold) and text (white 16px).
   - Items:
     - **Quality:** "We assure you the highest quality of agricultural products in the market that complies with the food industry's international standards."
     - **Effectiveness:** "Our products are well packed and quality controlled on the sorting, sizing and packing to maximize shelf-life."
     - **Selection:** "Our local farms produce the finest fruits and vegetables."
     - **Reliability:** "We provide you with a quick and accurate worldwide shipping."
   - Each box has a **fadeIn** scroll entrance.
5. **CERTIFICATS** (sic) band: background `#858533` (olive), white 36px bold heading linking to About. The media carousel below it is empty on the live site (§1).
6. **Catalogue CTA:**
   - Full-width background `2020/10/home-page.jpg` (terraced fields) over `#182719`, about 590px tall.
   - Heading "Download our Catalogue" (white, 48px bold). Small green button "DOWNLOAD" linking to `2020/09/AGROEGYPT.pdf`.
   - A long hidden heading carries the "41000 tons p/s" copy (SEO only).
7. *(Hidden SEO headings)*: company description and "safe time with us…". Not visible.

### About us `/about-us/`

1. Hero: "About us", with an oranges background.
2. **Our History:**
   - Background `#182719`. Founder photo on the left, about 245×395 (`2021/02/WhatsApp-Image-2021-02-18-at-2.15.45-PM.jpeg`), captioned "Mr. Roshdy Ghallab".
   - On the right, H2 "Our History" (white, 75px bold) and a paragraph (white 18px/30px).
3. **Our Company:**
   - A factory photo (`2021/01/dasd.jpg`, 940×444) that **overlaps** the dark section above and the white section below.
   - Centred H2 "Our Company" (`#182719`, 75px). Two justified paragraphs (18px/30px).
4. **Our Packing Station:**
   - Dark background. Text on the left: H2 plus three justified paragraphs.
   - Machine-line photo on the right (`2021/01/sta.png`, about 365×585), which also overlaps upward.
5. **Our Mission:**
   - Background slideshow of images (Elementor `background_slideshow`) under a dark overlay.
   - Centred H2 with an **orange line + orange-icon divider**, and centred text.
6. **Our Vision:**
   - Image collage on the left (`2021/02/12121-copy.jpg`). H2 "Our Vision" on the right, the text, then **4 progress bars**: Product Purity 98%, Farmers Competence 89%, Equipment 90%, Satisfied Customers 99%.
   - Bar labels are 24px bold `#323b43`; the bars animate on view. The button "GET IN TOUCH" (green `#789915`, white 16px bold uppercase) links to Contact.

### Our Products `/our-products/` and the category listings

- Hero, then a centred grid of **image cards**: square photos (400×400 originals) with a **caption under each** (24px bold `#182719`) and the **hang hover**. Each card is an Elementor image widget with a figcaption.
- **`/our-products/`:** Citrus, Grapes and Pomegranate in a row of 3 (large); Mango, Onion and Garlic in a row of 3 (smaller, centred).
- **Citrus:** Oranges, Lemons, Grapefruit, Mandarins (4).
- **Oranges:** Navels, Valencia, Baladi (3).
- **Grapes:** 5 varieties.
- **Onions:** 3.
- **Garlic:** White and Red (2).

### Product detail (23 pages, one template)

- **Hero:** the product name and a 4–5 level breadcrumb; the background depends on the category.
- **Desktop layout:** two columns, about 8/4. Left column, in order:
  - **Subtitle** (H3 24px bold `#182719`) and **description** (18px/36px).
  - **Variety** (optional): an icon-list of named varieties. Mango has 6; Navels has 3.
  - **Packaging:** H3 24px weight 400. One block per packaging option:
    - a line-icon (open-top carton, telescopic carton, mesh bag, plastic box or basket) about 100px
    - a bold title, e.g. "Open Top Carton (Net Weight 4.5 Kgs)"
    - then 1–2 spec pairs: a light 14px grey label ("No. of Pallets/Container", "No. of Cartons/Pallet") with an icon (container or pallet) and a value at 22px
  - **Seasonality:** one row per variety. The variety name is in orange (`#eaa84e`, 24px bold). Each month in season is an uppercase 3-letter label with a category-specific season icon underneath (`2020/12/product2-Recovered-0N-1.png`).
  - **Counts** (citrus, pomegranate, mango, onion) or **Size** (grapes, garlic): a horizontal list of values with a small icon.
- **Right column:** the product image, a cut-out photo about 330px wide.
- **Mobile:** a single column in the order description, packaging, seasonality, image, counts.
- The data is fully extracted to `content/products.json`.

### Our Brands `/brands/`

- Hero, then an H2 "Brands" (40px bold) and an intro paragraph (18px bold).
- An orange line + icon divider, then a **3×3 grid of brand box images** with captions: Agro Egypt, Four Brothers, Princess, Okay, Sun Fruit, Lemons, Wonderful Lemons, Sun Citrus and Pomegranate. Then the divider again.

### Our Markets `/markets/` ("Shipping & Markets")

1. Hero: "Shipping & Markets".
2. **Shipping:** dark background, an H3 and text on the left (about half width). The Elementor *slides* widget is empty.
3. **Markets:** a dark leaf-texture background, an H2 with an orange divider on the left and text on the right.
4. A full-width **world map image** (`2024/11/markets-copy.png`, orange highlighted regions and pins) on `#182719`.
5. **Regions grid**, 5 columns × 2 rows. Each has an orange heading (16px bold `#eaa84e`) and countries with a pin icon (14px orange):
   - **Western Europe:** Spain, Italy, Netherlands, England, France, Germany, Slovenia
   - **Eastern Europe:** Russia, Ukraine, Belarus, Latvia, Lithuania
   - **East Asia:** China, Hong Kong, Japan, Singapore
   - **Southeast Asia:** Malaysia, Cambodia, Indonesia
   - **Gulf Region:** UAE, Jordan, Kuwait, Oman, Qatar, Bahrain, Saudi Arabia
   - **South Asia:** India, Bangladesh
   - **North America:** Canada
   - **South America:** Argentina, Brazil
   - **Central Asia:** Kazakhstan
   - **Oceania:** Australia

### Gallery `/gallery/` and its 7 albums

- **Hub:** 6 product cut-out images with captions (Citrus, Grapes, Pomegranates, Mango, Garlic, Onions) in a row of 6 with the hang hover. Below them, a rounded (12px) "Packing Station" image card.
- **Albums:** an image grid with a lightbox (GT3 Photo Gallery grid). Image counts: citrus 16, grapes 13, pomegranate 11, garlic 20, pack-house 9, onions 3, mango 2.

### Careers `/careers/`

- Hero, then a **card** (`#2d3c2e`, radius 6px, large soft shadow) containing H2 "Careers" (75px bold white) and an inner darker card (`#182719`) with the CF7 form.
- **Fields:**
  - Name * (text, max 400)
  - E-Mail Address * (email)
  - Phone Number * (tel)
  - Job Title * (text)
  - **CV file upload** * (accept `.odt,.doc,.docx,.txt,.pdf,.jpg,.jpeg,.png`)
  - Message (max 2,000)
  - reCAPTCHA
- **Submit:** "SEND MESSAGE" (green `#789915`, 16px bold uppercase, padding about 14px 32px).
- **Field style:** a transparent background, a single bottom border at `rgba(255,255,255,.1)`, a white placeholder and 16px text.

### Contact us `/contact-us/`

1. Hero: "Contact us" (orchard background).
2. **4 contact items in a row.** Each has a dark-green line icon about 40px (`2021/02/lemon-{10,11,12,09}…png`) and centred 16px text:
   - **Address:** CPC Industrial Park – Plot no (2-3), 6th October City, Egypt.
   - **Phone:** (+202) 38642048
   - **Fax:** (+202) 38642049
   - **Email:** info@agroegypt.com
3. **"Get in touch" card:**
   - About 960px wide, background `#2d3c2e`, radius 6px, large shadow.
   - H2 (75px bold white) and the intro text: "Want to know more information about our tasty fruits and vegetables? Leave your contact info and message below and one of our customer support team will contact you shortly."
   - **Form:** Name * and E-Mail * side by side, then Message, then "SEND MESSAGE". reCAPTCHA v2 is included.
4. **Google Map** (full width, 450px), an embed of "AGRO EGYPT" at **29.950353, 30.8781868**.

---

## 5. Design system (measured)

### Colours

| Token | Value | Use |
|---|---|---|
| `--c-dark` | `#182719` | Body text, header links, footer and dark section backgrounds |
| `--c-dark-2` | `#2d3c2e` | Feature section, form cards |
| `--c-deep` | `#121c10` | Dropdown links, darkest accents |
| `--c-active` | `#223a22` | Active or hovered menu item |
| `--c-primary` | `#789915` | Buttons, breadcrumb dots, links (theme colour) |
| `--c-orange` | `#eaa84e` | Product names, region headings, icons, dividers |
| `--c-olive` | `#858533` | Certificates band |
| `--c-heading-alt` | `#323b43` | Progress labels, some headings |
| `--c-muted` | `#858585` | Light labels (packaging spec labels) |
| `--c-white` | `#ffffff` | Header background, light sections |
| Social | `#3b5998` / `#c13584` / `#0077b5` / `#cd201f` | Footer social buttons |

### Typography

- **Family:** Heebo, weights 300, 400, 500, 700 and 800, self-hosted on the reference from `2022/02/heebo.zip`. Heebo has **no Arabic glyphs**, so the new build pairs it with **Cairo** for RTL locales.

| Style | Size / line height | Weight |
|---|---|---|
| Body | 18px / 30px | 400 |
| Small body (forms, contact) | 16px / 28–33px | 400 |
| Page title H1 (hero) | 95px / 114px | 700 |
| Section H2 (About, Contact, Careers) | 75px / 90px | 700 |
| Section H2 (Home: "Our Products", "Download our Catalogue") | 48px | 400/700 |
| Brands H2 | 40px | 700 |
| H3 (card titles, footer headings) | 24px / 34px | 700 (400 for "Packaging"/"Seasonality") |
| Menu | 18px / 90px | 400 |
| Dropdown | 16px / 25px | 400 |
| Button | 16px / 23px, uppercase | 700 |

- **Responsive type:** the hero H1 shrinks to about 52px on mobile, and section H2s to about 40px.

### Layout and spacing

- **Container:** 1170px content width (1140px plus gutters); Elementor "boxed".
- **Breakpoints:** Elementor's mobile ≤767px, tablet ≤1024px; the theme's mobile header ≤1200px; slider grid steps at 1240, 1024, 778 and 480px.
- **Section padding:** 60–100px vertical on desktop, 40–60px on mobile.
- **Radii:** cards 6px, gallery feature card 12px, buttons 0.
- **Shadows:** dropdown `0 9px 68px rgba(38,43,43,.1)`; form cards use a large soft shadow, about `0 0 60px rgba(0,0,0,.1)`.

### Buttons

- **Primary:** background `#789915`, white text, 16px bold uppercase, no radius, padding about 14px 28px. On hover it darkens. Used for "GET IN TOUCH" and "SEND MESSAGE".
- **Small inline:** the "DOWNLOAD" button on Home, about 13px with tight padding.

### Animations and interactions (complete list)

| Effect | Where | Spec |
|---|---|---|
| Slider transition | Home hero | 5s per slide, fade + scale 0.7→1 + y −50%→0, `power4.inOut` about 2000ms; text layers fade in; arrows; touch swipe |
| Mouse parallax | Home hero | Small layer offset |
| `fadeIn` entrance | Home feature boxes (4) | Elementor fadeIn on viewport entry, about 1.25s |
| "hang" hover | Product and category cards, Home icons, gallery tiles | `elementor-animation-hang`: translateY(−8px) then a looping ±4px bob |
| Progress bar fill | About vision bars | Width 0→N% on viewport entry |
| Background slideshow | About mission | Elementor background slideshow, fade |
| Dropdown | Desktop menu | Hover, `.2s` fade/slide |
| Lightbox | Gallery albums | Click an image to open the full-size viewer with next/prev |
| Footer `fadeOnLoad` | Footer | Fade on page load |
| Back-to-top | All pages | Appears after scrolling, smooth scroll to top |

---

## 6. Responsive behaviour

- **Tablet (768px):** the header switches to hamburger plus logo. Product and category grids drop to 2–3 per row. The footer goes to 2 columns. Product details become a single column.
- **Mobile (390px):**
  - Hero 250px tall, H1 about 52px. The breadcrumb wraps.
  - Homepage product icons go to 2 per row; the features stack to 1 column.
  - Footer stacks to 1 column and the social buttons go full width.
  - Contact items stack; the form fields stack.
  - The slider is 450px tall with no arrows.

---

## 7. Content facts used for seeding

- **Company:** Agro Egypt for Agricultural Products (Ghallab). Founded on the 1976 shipment by Mr. Roshdy Ghallab; his sons took over in 2003; the packing station opened in 2014.
- **Figures:**
  - 30,000 t/yr citrus capacity
  - 1,500 t cold store, planned to reach 5,000 t and 60,000 t/yr
  - over 15 countries
  - 41,000 t per season
- **Certificates mentioned:** BRC, ISO 22000:2005, ACERTA, GLOBALG.A.P.
- **Brands:** Agro Egypt, Okay, Princess & Lemons, Four Brothers, Sun Citrus, Sun Fruit, Wonderful.
- **SEO:** titles follow the pattern "<Page> - Agro Egypt for agricultural products (Ghallab) Citrus Export Company in Egypt". Meta descriptions are per page and stored in `content/*.json` under `head`.

---

## 8. Deliberate deviations (to be confirmed by the owner)

| # | Reference behaviour | New build | Reason |
|---|---|---|---|
| 1 | Slider overlaps content and renders at 0px height until a user interaction | Slider stacked above the content, rendered immediately | Fixes the reference's JS bug |
| 2 | Empty CERTIFICATS carousel | Carousel of the BRC, ISO 22000, GLOBALG.A.P and "Certificates" logos from the original media library | Restores the intended content; editable |
| 3 | Mobile menu renders broken | Vertical accordion menu | Fixes the reference bug |
| 4 | Hidden SEO-only headings (1px text) | Not reproduced as hidden text; the copy goes into meta descriptions | Hidden text is an SEO anti-pattern |
| 5 | reCAPTCHA v2 on forms | Honeypot plus rate limiting by default; reCAPTCHA optional in settings | No keys available; avoids a third-party dependency |
| 6 | `mandarine-2` and `grapefruit-2` slugs; URLs without a locale | Clean slugs and `/{locale}/…` URLs, with **301 redirects from every legacy URL** | SEO-friendly localised URLs without breaking old links |
| 7 | Typos in the source ("CERTIFICATS", "RELIABILTY", "Telescpic", "Pasket") | Kept verbatim in the seed and editable in the admin | Fidelity; the owner decides |
| 8 | Arabic content doesn't exist | Arabic translations of all seeded content, marked "machine-prepared – needs review" | The requirement says Arabic must be available |
