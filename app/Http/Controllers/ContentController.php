<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Gallery;
use App\Models\Media;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Setting;
use App\Support\SectionData;
use App\Support\Seo;
use App\Support\SiteIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __invoke(Request $request, string $path = ''): View|RedirectResponse
    {
        $entry = SiteIndex::resolve(app()->getLocale(), $path);

        if ($entry === null) {
            return Redirect::respondTo($request->path()) ?? abort(404);
        }

        return match ($entry['type']) {
            (new Page)->getMorphClass() => $this->page(Page::findOrFail($entry['id'])),
            (new Category)->getMorphClass() => $this->category(Category::findOrFail($entry['id'])),
            (new Product)->getMorphClass() => $this->product(Product::findOrFail($entry['id'])),
            (new Gallery)->getMorphClass() => $this->gallery(Gallery::findOrFail($entry['id'])),
        };
    }

    /**
     * Signed admin preview: renders a page even when it is a draft or scheduled.
     */
    public function preview(Page $page): View
    {
        return $this->page($page, preview: true);
    }

    private function page(Page $page, bool $preview = false): View
    {
        $page->load([
            'translations', 'hero.translations',
            'sections' => fn ($query) => $query->where('is_visible', true),
            'sections.translations', 'sections.media.translations',
            'sections.items.translations', 'sections.items.media.translations', 'sections.items.icon.translations',
        ]);

        $sharingImage = ($id = $page->og_media_id) ? Media::find($id) : null;
        $seo = Seo::for($page, $page->title, $this->own($page, 'meta_title'), $this->description($page, ['meta_description']), $sharingImage, $this->organizationJsonLd($page), $page->meta_keywords);

        return view('pages.show', [
            'page' => $page,
            'sectionData' => SectionData::load($page->sections),
            'seo' => $preview ? new Seo($seo->title, $seo->description, $seo->canonical, noindex: true) : $seo,
            'hero' => $page->hero,
            'breadcrumbs' => ($page->template === Page::TEMPLATE_HOME || ! $page->show_hero) ? [] : [...$this->home(), [$page->title, null]],
            'switcherModel' => $page,
        ]);
    }

    private function category(Category $category): View
    {
        $category->load([
            'translations', 'hero', 'card',
            'children' => fn ($query) => $query->active(),
            'children.translations', 'children.card.translations',
            'products' => fn ($query) => $query->published(),
            'products.translations', 'products.card.translations',
        ]);
        $ancestors = $this->ancestors($category);
        $cards = $category->children->toBase()->merge($category->products)->sortBy('sort')->values();

        return view('catalog.category', [
            'category' => $category,
            'cards' => $cards,
            'seo' => Seo::for($category, $category->name, $this->own($category, 'meta_title'), $this->description($category, ['meta_description', 'description']), $category->card),
            'hero' => $category->hero ?? $ancestors->pluck('hero')->filter()->last(),
            'breadcrumbs' => [...$this->catalogTrail($ancestors), [$category->name, null]],
            'switcherModel' => $category,
        ]);
    }

    private function product(Product $product): View
    {
        $product->load([
            'translations', 'hero', 'image.translations', 'seasonIcon', 'category',
            'varieties.translations', 'packagings.translations', 'packagings.icon',
            'packagings.specs.translations', 'packagings.specs.icon', 'seasons.translations', 'images.translations',
        ]);
        $ancestors = $product->category ? $this->ancestors($product->category)->push($product->category) : collect();

        return view('catalog.product', [
            'product' => $product,
            'seo' => Seo::for($product, $product->name, $this->own($product, 'meta_title'), $this->description($product, ['meta_description', 'short_description', 'description']), $product->image, [[
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'description' => str((string) $product->description)->stripTags()->squish()->toString(),
                'image' => $product->image?->url(),
                'brand' => ['@type' => 'Brand', 'name' => Setting::localized('company.name', config('app.name'))],
            ]]),
            'hero' => $product->hero ?? $ancestors->pluck('hero')->filter()->last(),
            'breadcrumbs' => [...$this->catalogTrail($ancestors), [$product->name, null]],
            'switcherModel' => $product,
        ]);
    }

    private function gallery(Gallery $gallery): View
    {
        $gallery->load(['translations', 'hero', 'images.translations']);
        $hub = Page::published()->where('template', Page::TEMPLATE_GALLERY)->with(['translations', 'hero'])->first();

        return view('gallery.show', [
            'gallery' => $gallery,
            'seo' => Seo::for($gallery, $gallery->title, $this->own($gallery, 'meta_title'), $this->description($gallery, ['meta_description']), $gallery->cover),
            'hero' => $gallery->hero ?? $hub?->hero,
            'breadcrumbs' => [...$this->home(), ...($hub ? [[$hub->title, SiteIndex::url($hub)]] : []), [$gallery->title, null]],
            'switcherModel' => $gallery,
        ]);
    }

    /**
     * A translated attribute in the current language only (no fallback).
     */
    private function own(Model $model, string $attribute): ?string
    {
        return $model->getTranslation(app()->getLocale(), false)?->{$attribute} ?: null;
    }

    /**
     * First filled candidate in the current language, then the same candidates in the fallback language.
     *
     * @param  list<string>  $attributes
     */
    private function description(Model $model, array $attributes): ?string
    {
        foreach ([false, true] as $withFallback) {
            $translation = $model->getTranslation(app()->getLocale(), $withFallback);
            foreach ($attributes as $attribute) {
                if (filled($translation?->{$attribute})) {
                    return $translation->{$attribute};
                }
            }
        }

        return null;
    }

    /**
     * Ancestor categories from the root down, with their heroes and translations loaded.
     *
     * @return Collection<int, Category>
     */
    private function ancestors(Category $category): Collection
    {
        $ancestors = collect();
        $parentId = $category->parent_id;
        while ($parentId !== null && ($parent = Category::with(['translations', 'hero'])->find($parentId))) {
            $ancestors->prepend($parent);
            $parentId = $parent->parent_id;
        }

        return $ancestors;
    }

    /**
     * @param  Collection<int, Category>  $ancestors
     * @return list<array{0: string, 1: string|null}>
     */
    private function catalogTrail(Collection $ancestors): array
    {
        $productsPage = Page::published()->where('template', Page::TEMPLATE_PRODUCTS)->with('translations')->first();

        return [
            ...$this->home(),
            ...($productsPage ? [[$productsPage->title, SiteIndex::url($productsPage)]] : []),
            ...$ancestors->map(fn (Category $ancestor) => [$ancestor->name, SiteIndex::url($ancestor)])->all(),
        ];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function home(): array
    {
        return [[__('site.home'), SiteIndex::localizedUrl(app()->getLocale())]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function organizationJsonLd(Page $page): array
    {
        if ($page->template !== Page::TEMPLATE_HOME) {
            return [];
        }

        return [[
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Setting::localized('company.name', config('app.name')),
            'url' => SiteIndex::localizedUrl(app()->getLocale()),
            'logo' => ($logo = Setting::get('theme.logo')) ? asset('storage/'.$logo) : null,
            'email' => Setting::get('contact.email'),
            'telephone' => Setting::get('contact.phone'),
            'sameAs' => array_values(array_filter([
                Setting::get('social.facebook'), Setting::get('social.instagram'), Setting::get('social.linkedin'), Setting::get('social.youtube'),
            ])),
        ]];
    }
}
