<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Gallery;
use App\Models\Language;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

/**
 * Two-way map between localized URL paths and content records, per active language.
 *
 * Paths: pages "{slug}", categories "{products-page}/{parent}/{child}", products
 * "{category path}/{slug}", galleries "{gallery-page}/{slug}". Records without a translation in a
 * language get the fallback language's slug, so every page is reachable under every language.
 */
final class SiteIndex
{
    /**
     * @return array{type: string, id: int}|null
     */
    public static function resolve(string $locale, string $path): ?array
    {
        return self::index($locale)['paths'][trim($path, '/')] ?? null;
    }

    /**
     * Path (without locale prefix) for a record, or null when it is not publicly routable.
     */
    public static function path(Model $model, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return self::index($locale)['urls'][self::key($model)]['path'] ?? null;
    }

    public static function url(Model $model, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $path = self::path($model, $locale);

        return $path === null ? null : self::localizedUrl($locale, $path);
    }

    /**
     * Resolve an admin-entered link: absolute, mailto:, tel: and #anchors are kept as-is;
     * relative paths ("/contact-us") are prefixed with the current language; "/storage/…" files are served as-is.
     */
    public static function link(?string $url, ?string $locale = null): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (preg_match('#^(https?://|mailto:|tel:|\#)#i', $url)) {
            return $url;
        }

        if (preg_match('#^/?storage/#', $url)) {
            return asset(ltrim($url, '/'));
        }

        return self::localizedUrl($locale ?? app()->getLocale(), ltrim($url, '/'));
    }

    public static function localizedUrl(string $locale, string $path = ''): string
    {
        return url(rtrim("/{$locale}/{$path}", '/'));
    }

    /**
     * URLs of this record in every active language that has its own translation (for hreflang).
     *
     * @return array<string, string>
     */
    public static function alternates(Model $model): array
    {
        $alternates = [];
        foreach (Language::active() as $language) {
            $entry = self::index($language->code)['urls'][self::key($model)] ?? null;
            if ($entry !== null && ($entry['translated'] || $language->is_default)) {
                $alternates[$language->code] = self::localizedUrl($language->code, $entry['path']);
            }
        }

        return $alternates;
    }

    /**
     * Every routable entry for a language (sitemap).
     *
     * @return array<string, array{path: string, translated: bool, updated_at: string|null}>
     */
    public static function entries(string $locale): array
    {
        return self::index($locale)['urls'];
    }

    public static function key(Model $model): string
    {
        return $model->getMorphClass().':'.$model->getKey();
    }

    /**
     * @return array{paths: array<string, array{type: string, id: int}>, urls: array<string, array{path: string, translated: bool, updated_at: string|null}>}
     */
    private static function index(string $locale): array
    {
        return ContentCache::remember("site-index.{$locale}", fn () => self::build($locale));
    }

    /**
     * @return array{paths: array<string, array{type: string, id: int}>, urls: array<string, array{path: string, translated: bool, updated_at: string|null}>}
     */
    private static function build(string $locale): array
    {
        $index = ['paths' => [], 'urls' => []];
        $add = function (Model $model, string $path) use (&$index, $locale): void {
            $path = trim($path, '/');
            if (isset($index['paths'][$path])) {
                return;
            }
            $index['paths'][$path] = ['type' => $model->getMorphClass(), 'id' => $model->getKey()];
            $index['urls'][self::key($model)] = [
                'path' => $path,
                'translated' => $model->hasTranslation($locale),
                'updated_at' => $model->updated_at?->toAtomString(),
            ];
        };
        $slug = fn (Model $model): ?string => $model->translate($locale, true)?->slug;

        $pages = Page::published()->with('translations')->orderBy('sort')->get();
        foreach ($pages as $page) {
            $add($page, $page->template === Page::TEMPLATE_HOME ? '' : (string) $slug($page));
        }

        $productsBase = ($page = $pages->firstWhere('template', Page::TEMPLATE_PRODUCTS)) ? $slug($page) : null;
        if ($productsBase !== null) {
            $categories = Category::active()->with('translations')->orderBy('sort')->get()->keyBy('id');
            $categoryPaths = [];
            $resolvePath = function (Category $category) use (&$resolvePath, &$categoryPaths, $categories, $productsBase, $slug): ?string {
                if (array_key_exists($category->id, $categoryPaths)) {
                    return $categoryPaths[$category->id];
                }
                $parentPath = $category->parent_id === null
                    ? $productsBase
                    : (($parent = $categories->get($category->parent_id)) ? $resolvePath($parent) : null);

                return $categoryPaths[$category->id] = $parentPath === null ? null : $parentPath.'/'.$slug($category);
            };
            foreach ($categories as $category) {
                if (($path = $resolvePath($category)) !== null) {
                    $add($category, $path);
                }
            }

            foreach (Product::published()->with('translations')->orderBy('sort')->get() as $product) {
                $base = $product->category_id === null
                    ? $productsBase
                    : (($category = $categories->get($product->category_id)) ? $resolvePath($category) : null);
                if ($base !== null) {
                    $add($product, $base.'/'.$slug($product));
                }
            }
        }

        $galleryBase = ($page = $pages->firstWhere('template', Page::TEMPLATE_GALLERY)) ? $slug($page) : null;
        if ($galleryBase !== null) {
            foreach (Gallery::active()->with('translations')->orderBy('sort')->get() as $gallery) {
                $add($gallery, $galleryBase.'/'.$slug($gallery));
            }
        }

        return $index;
    }
}
