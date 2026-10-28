<?php

namespace App\Support;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\GalleryTranslation;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps old URLs working: before a change that can move URLs (slug edits, moving a category or product,
 * changing which page is the products/gallery hub) the current URL map is captured; after the change is
 * saved, every record whose path changed gets a 301 from its old path to the new one.
 */
final class UrlRedirects
{
    /** @var array<string, array<string, string>>|null locale => [record key => path] */
    private static ?array $snapshot = null;

    public static function affectsUrls(Model $model): bool
    {
        return match (true) {
            $model instanceof PageTranslation,
            $model instanceof CategoryTranslation,
            $model instanceof ProductTranslation,
            $model instanceof GalleryTranslation => $model->isDirty('slug'),
            $model instanceof Page => $model->isDirty('template'),
            $model instanceof Category => $model->isDirty('parent_id'),
            $model instanceof Product => $model->isDirty('category_id'),
            default => false,
        };
    }

    /**
     * Remember the URL map as it is before the pending change (first capture wins until committed).
     */
    public static function capture(): void
    {
        if (self::$snapshot !== null) {
            return;
        }

        self::$snapshot = [];
        foreach (Language::cached() as $language) {
            self::$snapshot[$language->code] = array_map(fn (array $entry) => $entry['path'], SiteIndex::entries($language->code));
        }
    }

    /**
     * Compare with the current URL map (call after the content cache was flushed) and add redirects.
     */
    public static function commit(): void
    {
        if (self::$snapshot === null) {
            return;
        }

        $before = self::$snapshot;
        self::$snapshot = null; // saving redirects fires model events again

        foreach ($before as $locale => $paths) {
            $current = SiteIndex::entries($locale);

            foreach ($paths as $key => $oldPath) {
                $newPath = $current[$key]['path'] ?? null;
                if ($newPath === null || $newPath === $oldPath) {
                    continue; // unpublished/removed, or unchanged
                }

                $from = Redirect::normalizePath("/{$locale}/{$oldPath}");
                $to = rtrim("/{$locale}/{$newPath}", '/');

                Redirect::where('from_path', Redirect::normalizePath($to))->delete(); // real content lives there now
                Redirect::where('to_path', $from)->update(['to_path' => $to]);         // no redirect chains
                Redirect::updateOrCreate(['from_path' => $from], ['to_path' => $to, 'status_code' => 301, 'is_active' => true]);
            }
        }
    }

    /**
     * Drop an uncommitted snapshot (used by tests).
     */
    public static function reset(): void
    {
        self::$snapshot = null;
    }
}
