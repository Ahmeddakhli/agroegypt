<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Catalog URLs nest slugs (/our-products/citrus/orange/navel), so a slug only has to be unique among
 * the categories and products sharing the same parent category, per language.
 */
final class CatalogSlug
{
    public static function isTaken(string $locale, string $slug, ?int $parentCategoryId, ?Model $except = null): bool
    {
        $siblings = fn (Builder $query, string $column) => $parentCategoryId === null ? $query->whereNull($column) : $query->where($column, $parentCategoryId);
        $hasSlug = fn (Builder $query) => $query->where('locale', $locale)->where('slug', $slug);

        $category = $siblings(Category::query(), 'parent_id')->whereHas('translations', $hasSlug)
            ->when($except instanceof Category, fn (Builder $q) => $q->whereKeyNot($except->getKey()));
        $product = $siblings(Product::query(), 'category_id')->whereHas('translations', $hasSlug)
            ->when($except instanceof Product, fn (Builder $q) => $q->whereKeyNot($except->getKey()));

        return $category->exists() || $product->exists();
    }

    /**
     * URL slug from a name; keeps Arabic and other non-Latin letters instead of transliterating.
     */
    public static function from(string $name): string
    {
        return Str::slug($name, '-', preg_match('/^[\x00-\x7F]*$/', $name) ? 'en' : null);
    }
}
