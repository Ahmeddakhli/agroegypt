<?php

namespace App\Support;

use App\Enums\SectionType;
use App\Models\Category;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\Slider;
use Illuminate\Support\Collection;

/**
 * Prefetches the records some section types display (sliders, certificates, catalog cards, galleries,
 * the document behind a download button)
 * so section views never query the database.
 */
final class SectionData
{
    /**
     * @param  Collection<int, PageSection>  $sections
     * @return array<int, mixed> extra data keyed by section id
     */
    public static function load(Collection $sections): array
    {
        $data = [];

        foreach ($sections as $section) {
            $data[$section->id] = match ($section->type) {
                SectionType::HeroSlider => Slider::where('key', $section->setting('slider_key', 'home'))
                    ->with(['activeSlides.translations', 'activeSlides.media', 'activeSlides.mobileMedia'])
                    ->first(),
                SectionType::Certificates => Document::active()->where('type', 'certificate')
                    ->with(['translations', 'image.translations', 'file'])->orderBy('sort')->get(),
                SectionType::CatalogGrid => self::catalogCards(),
                SectionType::CtaBackground => $section->setting('document_id')
                    ? Document::active()->with(['translations', 'file'])->find($section->setting('document_id'))
                    : null,
                SectionType::GalleryGrid => Gallery::active()->with(['translations', 'cover.translations'])->orderBy('sort')->get(),
                default => null,
            };
        }

        return $data;
    }

    /**
     * Top-level categories and uncategorized products, merged in their configured order.
     *
     * @return Collection<int, Category|Product>
     */
    public static function catalogCards(): Collection
    {
        $categories = Category::active()->whereNull('parent_id')->with(['translations', 'card.translations'])->get();
        $products = Product::published()->whereNull('category_id')->with(['translations', 'card.translations'])->get();

        return $categories->toBase()->merge($products)->sortBy('sort')->values();
    }
}
