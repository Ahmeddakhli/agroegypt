<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\SectionType;
use App\Models\Category;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\Slider;
use App\Support\MediaLibrary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Imports the content of agroegypt.com captured during the audit (database/seeders/data/reference.json)
 * with the original images (resources/reference-assets). Arabic comes from data/ar.json, keyed by the
 * English source text; strings without an entry fall back to English and show as missing in the admin.
 */
class ReferenceContentSeeder extends Seeder
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, string> */
    private array $arabic;

    private ?Document $catalogue = null;

    /** @var array<int, Model> reference WordPress page id => created record */
    private array $records = [];

    /** Settings edited per language in the dashboard. */
    private const LOCALIZED_SETTINGS = [
        'company.name', 'contact.address', 'seo.title_format', 'seo.description',
        'footer.tagline', 'footer.links_title', 'footer.contact_title', 'footer.social_title', 'footer.copyright', 'footer.credit',
    ];

    public function run(): void
    {
        if (Page::exists()) {
            $this->command?->warn('Content already exists — skipping the reference import.');

            return;
        }

        $this->data = json_decode(file_get_contents(database_path('seeders/data/reference.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->arabic = json_decode(file_get_contents(database_path('seeders/data/ar.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () {
            $this->settings();
            $this->catalog();
            $this->galleries();
            $this->documents();
            $this->slider();
            $this->pages();
            $this->menus();
            $this->redirects();
        });
    }

    private function settings(): void
    {
        $values = [
            'company.name' => 'Agro Egypt for Agricultural Products (Ghallab)',
            'contact.address' => "CPC Industrial Park – Plot no (2-3)\n6th October City, Egypt.",
            'contact.phone' => '(+202) 38642048',
            'contact.fax' => '(+202) 38642049',
            'contact.email' => 'info@agroegypt.com',
            'social.facebook' => 'https://www.facebook.com/egyptian.oranges',
            'social.instagram' => 'https://www.instagram.com/agroegyptofficial',
            'social.linkedin' => 'https://www.linkedin.com/company/argoegypt',
            'social.youtube' => 'https://www.youtube.com/@AgroEgypt/videos',
            'footer.tagline' => 'We do care about our quality because We believe that you deserve the best',
            'footer.links_title' => 'Useful Links',
            'footer.contact_title' => 'Contact Info',
            'footer.social_title' => 'Social Media',
            'footer.copyright' => '© 2020 AgroEgypt. All rights reserved.',
            'footer.credit' => 'Powered by Mount Wolf',
            'seo.title_format' => '%s - Agro Egypt for agricultural products (Ghallab) Citrus Export Company in Egypt',
            'seo.description' => 'Agro Egypt is an Egyptian agricultural food exporting company providing high quality and hygienically safe fruits and vegetables — citrus, grapes, pomegranate, mango, onions and garlic — to over 15 countries.',
        ];

        // Reference texts are English: stored per language, so they never stand in for another language.
        foreach ($values as $key => $value) {
            Setting::put($key, $value, in_array($key, self::LOCALIZED_SETTINGS, true) ? 'en' : '');
            if (isset($this->arabic[$value])) {
                Setting::put($key, $this->arabic[$value], 'ar');
            }
        }

        foreach ([
            'theme.logo' => '2022/02/logo-200px.png',
            'theme.footer_logo' => '2020/09/logo512White.png',
            'theme.favicon' => '2020/08/cropped-logo512.png',
            'theme.default_hero' => '2021/02/contact1.png',
            'theme.divider_icon' => 'derived/divider-icon.svg',
            'seo.og_image' => '2021/02/logo-agro.png',
            'footer.icon_address' => '2020/09/lemon-10Oranged.png',
            'footer.icon_phone' => '2020/09/lemon-11Oranged.png',
            'footer.icon_fax' => '2020/09/lemon-12Oranged.png',
            'footer.icon_email' => '2020/09/lemon-09Oranged.png',
        ] as $key => $asset) {
            Setting::put($key, $this->media($asset)->path);
        }

        foreach ([
            'theme.animations' => '1', 'theme.hover_effects' => '1', 'header.show_language_switcher' => '1', 'header.sticky' => '0', 'seo.indexing' => '1',
            'mail.notifications_enabled' => '1', 'mail.notify_contact' => '', 'mail.notify_careers' => '', // recipients are set in the admin; never mail the real company from a copy
            'cookies.enabled' => '0', 'general.timezone' => 'Africa/Cairo',
        ] as $key => $value) {
            Setting::put($key, $value);
        }
    }

    private function catalog(): void
    {
        foreach ($this->data['catalog'] as $node) {
            $this->catalogNode($node, null);
        }
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function catalogNode(array $node, ?Category $parent): void
    {
        $shared = [
            'card_media_id' => $this->mediaId($node['card']),
            'hero_media_id' => $this->mediaId($node['hero']),
            'sort' => $node['sort'],
        ];
        $texts = ['name' => $node['name'], 'slug' => $node['slug'], 'meta_description' => $node['meta_description']];

        if ($node['kind'] === 'category') {
            $category = Category::create([...$shared, 'parent_id' => $parent?->id, 'is_active' => true, ...$this->localized($texts, ['slug'])]);
            $this->records[$node['wp_id']] = $category;
            foreach ($node['children'] as $child) {
                $this->catalogNode($child, $category);
            }

            return;
        }

        $product = Product::create([
            ...$shared,
            'category_id' => $parent?->id,
            'image_media_id' => $this->mediaId($node['image']),
            'season_icon_media_id' => $this->mediaId($node['season_icon']),
            'spec_values' => $node['spec_values'],
            'status' => ContentStatus::Published,
            ...$this->localized([...$texts, 'subtitle' => $node['subtitle'], 'description' => $node['description'], 'spec_label' => $node['spec_label']], ['slug']),
        ]);
        $this->records[$node['wp_id']] = $product;

        foreach ($node['varieties'] as $sort => $name) {
            $product->varieties()->create(['sort' => $sort, ...$this->localized(['name' => $name])]);
        }
        foreach ($node['packaging'] as $sort => $packaging) {
            $model = $product->packagings()->create(['sort' => $sort, 'icon_media_id' => $this->mediaId($packaging['icon']), ...$this->localized(['title' => $packaging['title']])]);
            foreach ($packaging['specs'] as $specSort => $spec) {
                $model->specs()->create(['sort' => $specSort, 'value' => $spec['value'], 'icon_media_id' => $this->mediaId($spec['icon']), ...$this->localized(['label' => $spec['label']])]);
            }
        }
        foreach ($node['seasons'] as $sort => $season) {
            $product->seasons()->create(['sort' => $sort, 'months' => $season['months'], ...$this->localized(['label' => $season['label']])]);
        }
    }

    private function galleries(): void
    {
        foreach ($this->data['galleries'] as $album) {
            $gallery = Gallery::create([
                'cover_media_id' => $this->mediaId($album['cover']),
                'is_featured' => $album['featured'],
                'is_active' => true,
                'sort' => $album['sort'],
                ...$this->localized(['title' => $album['title'], 'slug' => $album['slug'], 'meta_description' => $album['meta_description']], ['slug']),
            ]);
            $gallery->images()->sync(collect($album['images'])->values()->mapWithKeys(fn (string $image, int $sort) => [$this->mediaId($image) => ['sort' => $sort]])->all());
            $this->records[$album['wp_id']] = $gallery;
        }
    }

    private function documents(): void
    {
        $this->catalogue = Document::create(['type' => 'catalogue', 'file_media_id' => $this->mediaId($this->data['home']['catalogue_file']), 'sort' => 0, ...$this->localized(['title' => 'Download our Catalogue'])]);

        foreach ($this->data['certificates'] as $sort => $certificate) {
            Document::create(['type' => 'certificate', 'image_media_id' => $this->mediaId($certificate['image']), 'sort' => $sort, ...$this->localized(['title' => $certificate['title']])]);
        }
    }

    private function slider(): void
    {
        $slider = Slider::create(['name' => 'Home hero', 'key' => 'home', 'effect' => 'zoom', 'autoplay' => true, 'interval_ms' => 5000, 'transition_ms' => 2000, 'show_arrows' => true]);

        foreach ($this->data['home']['slides'] as $sort => $slide) {
            $slider->slides()->create(['media_id' => $this->mediaId($slide['image']), 'sort' => $sort, 'is_active' => true, ...$this->localized(['title' => $slide['title']])]);
        }
    }

    private function pages(): void
    {
        $home = $this->data['home'];
        $about = $this->data['about'];
        $markets = $this->data['markets'];
        $contact = $this->data['contact'];

        $this->page(1558, Page::TEMPLATE_HOME, 'Home', 'home', [
            [SectionType::HeroSlider, ['settings' => ['slider_key' => 'home']]],
            [SectionType::IconGrid, ['title' => $home['products_title']], array_map(fn ($i) => ['icon' => $i['icon'], 'title' => $i['title'], 'url' => $this->path($i['url'])], $home['icon_items'])],
            [SectionType::FeatureGrid, ['settings' => ['animation' => 'fade']], array_map(fn ($i) => ['icon' => $i['icon'], 'title' => $i['title'], 'body' => $i['body']], $home['features'])],
            [SectionType::BandHeading, ['title' => $home['certificates_title'], 'button_url' => '/about-us']],
            [SectionType::Certificates, []],
            [SectionType::CtaBackground, ['title' => $home['catalogue_title'], 'button_label' => $home['catalogue_button'], 'settings' => ['document_id' => $this->catalogue->id], 'media' => $home['catalogue_bg']]],
        ], metaTitle: 'Agro Egypt for agricultural products (Ghallab) - Citrus Export Company in Egypt');

        $this->page(322, Page::TEMPLATE_DEFAULT, 'About us', 'about-us', [
            [SectionType::ImageText, ['title' => $about['history']['title'], 'body' => $about['history']['body'], 'subtitle' => $about['history']['caption'], 'media' => $about['history']['image'], 'settings' => ['background' => 'dark']]],
            [SectionType::OverlapImage, ['title' => $about['company']['title'], 'body' => $about['company']['body'], 'media' => $about['company']['image']]],
            [SectionType::ImageText, ['title' => $about['packing']['title'], 'body' => $about['packing']['body'], 'media' => $about['packing']['image'], 'settings' => ['background' => 'dark', 'image_position' => 'end', 'image_offset' => 140]]],
            [SectionType::Mission, ['title' => $about['mission']['title'], 'body' => $about['mission']['body']], array_map(fn ($image) => ['media' => $image], $about['mission']['images'])],
            [SectionType::ProgressBars, ['title' => $about['vision']['title'], 'body' => $about['vision']['body'], 'media' => $about['vision']['image'], 'button_label' => $about['vision']['button'], 'button_url' => '/contact-us'], array_map(fn ($i) => ['title' => $i['title'], 'value' => $i['value']], $about['vision']['progress'])],
        ]);

        $this->page(1637, Page::TEMPLATE_PRODUCTS, 'Our Products', 'our-products', [[SectionType::CatalogGrid, []]]);

        $this->page(1751, Page::TEMPLATE_DEFAULT, 'Our Brands', 'brands', [
            [SectionType::RichText, ['title' => $this->data['brands']['title'], 'body' => $this->data['brands']['body'], 'settings' => ['divider' => true]]],
            [SectionType::ImageCardGrid, ['settings' => ['columns' => 3, 'caption' => 'normal', 'divider_after' => true]], array_map(fn ($i) => ['media' => $i['image'], 'title' => $i['title']], $this->data['brands']['items'])],
        ]);

        $this->page(2778, Page::TEMPLATE_DEFAULT, 'Shipping & Markets', 'markets', [
            [SectionType::ImageText, ['title' => $markets['shipping_title'], 'body' => $markets['shipping_body'], 'settings' => ['background' => 'dark']]],
            [SectionType::ImageText, ['title' => $markets['markets_title'], 'body' => $markets['markets_body'], 'media' => '2021/01/pixabay_photo_3808354.jpg', 'settings' => ['background' => 'deep', 'layout' => 'split_heading', 'media_as_background' => true]]],
            [SectionType::FullImage, ['media' => $markets['map']]],
            [SectionType::MarketRegions, [], $markets['regions']],
        ]);

        $this->page(3098, Page::TEMPLATE_GALLERY, 'Gallery', 'gallery', [[SectionType::GalleryGrid, []]]);

        $this->page(4460, Page::TEMPLATE_DEFAULT, 'Careers', 'careers', [[SectionType::CareersForm, ['title' => $this->data['careers']['title']]]]);

        $this->page(636, Page::TEMPLATE_DEFAULT, 'Contact us', 'contact-us', [
            [SectionType::ContactInfo, [], array_map(fn ($i) => ['icon' => $i['icon'], 'body' => $i['body'], 'url' => $i['url']], $contact['items'])],
            [SectionType::ContactForm, ['title' => $contact['title'], 'body' => $contact['body']]],
            [SectionType::Map, ['title' => 'AGRO EGYPT', 'button_url' => $contact['map']]],
        ]);
    }

    /**
     * @param  list<array{0: SectionType, 1: array<string, mixed>, 2?: list<array<string, mixed>>}>  $sections
     */
    private function page(int $wpId, string $template, string $title, string $slug, array $sections, ?string $metaTitle = null): void
    {
        $source = $this->data['pages'][(string) $wpId];
        $page = Page::create([
            'template' => $template,
            'status' => ContentStatus::Published,
            'hero_media_id' => $this->mediaId($source['hero']),
            'show_hero' => $template !== Page::TEMPLATE_HOME,
            'sort' => count($this->records),
            'en' => ['title' => $title, 'slug' => $slug, 'meta_title' => $metaTitle, 'meta_description' => $source['meta_description']],
            ...(isset($this->arabic[$title]) ? ['ar' => ['title' => $this->arabic[$title], 'slug' => $slug, 'meta_title' => $metaTitle ? ($this->arabic[$metaTitle] ?? null) : null]] : []),
        ]);
        $this->records[$wpId] = $page;

        foreach ($sections as $sort => [$type, $attributes]) {
            $items = $sections[$sort][2] ?? [];
            $texts = array_intersect_key($attributes, array_flip(['title', 'subtitle', 'body', 'button_label']));
            $section = PageSection::create([
                'page_id' => $page->id,
                'type' => $type,
                'sort' => $sort,
                'is_visible' => true,
                'media_id' => $this->mediaId($attributes['media'] ?? null),
                'settings' => $attributes['settings'] ?? null,
                ...$this->localized([...$texts, 'button_url' => $attributes['button_url'] ?? null], ['button_url'], requireAll: false),
            ]);

            foreach ($items as $itemSort => $item) {
                $section->items()->create([
                    'sort' => $itemSort,
                    'media_id' => $this->mediaId($item['media'] ?? null),
                    'icon_media_id' => $this->mediaId($item['icon'] ?? null),
                    'url' => $item['url'] ?? null,
                    'value' => $item['value'] ?? null,
                    ...$this->localized(array_intersect_key($item, array_flip(['title', 'body'])), requireAll: false),
                ]);
            }
        }
    }

    private function menus(): void
    {
        $header = Menu::create(['name' => 'Header', 'location' => 'header']);
        $labels = [1558 => 'Home', 322 => 'About us', 1637 => 'Our Products', 1751 => 'Our Brands', 2778 => 'Our Markets', 3098 => 'Gallery', 4460 => 'Careers', 636 => 'Contact us'];

        foreach (array_keys($labels) as $sort => $wpId) {
            $item = $this->menuItem($header, $this->records[$wpId], $labels[$wpId], $sort);
            if ($wpId === 1637) {
                $this->catalogMenu($header, $item, $this->data['catalog']);
            }
        }

        $footer = Menu::create(['name' => 'Footer', 'location' => 'footer']);
        foreach ([1558, 322, 1637, 4460, 636] as $sort => $wpId) {
            $this->menuItem($footer, $this->records[$wpId], $labels[$wpId], $sort);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function catalogMenu(Menu $menu, MenuItem $parent, array $nodes): void
    {
        foreach ($nodes as $node) {
            $item = $this->menuItem($menu, $this->records[$node['wp_id']], $node['name'], $node['sort'], $parent);
            $this->catalogMenu($menu, $item, $node['children'] ?? []);
        }
    }

    private function menuItem(Menu $menu, Model $linkable, string $label, int $sort, ?MenuItem $parent = null): MenuItem
    {
        return MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $parent?->id,
            'linkable_type' => $linkable->getMorphClass(),
            'linkable_id' => $linkable->getKey(),
            'sort' => $sort,
            'is_visible' => true,
            ...$this->localized(['label' => $label]),
        ]);
    }

    /**
     * 301s from every reference URL (no language prefix, trailing slash) to the new localized URL.
     */
    private function redirects(): void
    {
        $paths = [...$this->data['legacy_paths'], '/shipping-markets/' => 'wp:2778',
            '/gt3_gallery/citrus/' => 'wp:3110', '/gt3_gallery/grapes/' => 'wp:3146', '/gt3_gallery/garlic/' => 'wp:3180',
            '/gt3_gallery/mango/' => 'wp:3178', '/gt3_gallery/pomegranates/' => 'wp:3124', '/gt3_gallery/company/' => 'wp:3100'];

        foreach ($paths as $from => $token) {
            $target = $this->records[(int) substr($token, 3)] ?? null;
            if ($from === '/' || $target === null) {
                continue;
            }
            Redirect::updateOrCreate(['from_path' => Redirect::normalizePath($from)], ['to_path' => '/en'.$this->path($token), 'status_code' => 301, 'is_active' => true]);
        }
    }

    /**
     * Site path of a reference record ("wp:1647" → "/our-products/citrus"); the new URL structure keeps the old slugs.
     */
    private function path(string $token): string
    {
        $original = array_search($token, $this->data['legacy_paths'], true);

        return rtrim((string) $original, '/') ?: '/';
    }

    /**
     * Translation payload: English always; Arabic when the dictionary covers the text fields.
     *
     * @param  array<string, string|null>  $fields
     * @param  list<string>  $verbatim  fields copied to Arabic unchanged (slugs, URLs)
     * @return array<string, array<string, string|null>>
     */
    private function localized(array $fields, array $verbatim = [], bool $requireAll = true): array
    {
        $payload = ['en' => $fields];
        $arabic = [];
        foreach ($fields as $key => $value) {
            if (in_array($key, $verbatim, true) || $value === null || $value === '') {
                $arabic[$key] = $value;
            } elseif (isset($this->arabic[$value])) {
                $arabic[$key] = $this->arabic[$value];
            } elseif (! str_starts_with($key, 'meta_') && $requireAll) {
                return $payload;
            }
        }

        $hasText = collect($arabic)->except($verbatim)->filter()->isNotEmpty();

        return $hasText ? [...$payload, 'ar' => $arabic] : $payload;
    }

    private function media(string $relativePath): Media
    {
        return MediaLibrary::import(resource_path('reference-assets/'.$relativePath));
    }

    private function mediaId(?string $relativePath): ?int
    {
        return $relativePath ? $this->media($relativePath)->id : null;
    }
}
