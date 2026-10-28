<?php

namespace App\Support;

use App\Filament\Pages\ManageMenus;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Sliders\SliderResource;
use App\Models\Category;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Language;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\PackagingSpec;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductPackaging;
use App\Models\ProductSeason;
use App\Models\ProductVariety;
use App\Models\SectionItem;
use App\Models\Slide;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Which content is translated into a language: every translatable record that has text in the default
 * language is "complete", "partial" (fewer filled fields than the default) or "missing" in the target.
 */
final class TranslationCoverage
{
    public const STATUSES = ['complete', 'partial', 'missing'];

    /**
     * @return array<string, array{label: string, model: class-string<Model>, with: list<string>, edit: Closure(Model): ?string}>
     */
    public static function modules(): array
    {
        $pageEdit = fn (?int $pageId) => $pageId ? PageResource::getUrl('edit', ['record' => $pageId]) : null;
        $productEdit = fn (?int $productId) => $productId ? ProductResource::getUrl('edit', ['record' => $productId]) : null;

        return [
            'pages' => ['label' => 'Pages', 'model' => Page::class, 'with' => [], 'edit' => fn (Page $page) => $pageEdit($page->id)],
            'sections' => ['label' => 'Page sections', 'model' => PageSection::class, 'with' => [], 'edit' => fn (PageSection $section) => $pageEdit($section->page_id)],
            'section_items' => ['label' => 'Section items', 'model' => SectionItem::class, 'with' => ['section'], 'edit' => fn (SectionItem $item) => $pageEdit($item->section?->page_id)],
            'categories' => ['label' => 'Categories', 'model' => Category::class, 'with' => [], 'edit' => fn (Category $category) => CategoryResource::getUrl('edit', ['record' => $category])],
            'products' => ['label' => 'Products', 'model' => Product::class, 'with' => [], 'edit' => fn (Product $product) => $productEdit($product->id)],
            'varieties' => ['label' => 'Product varieties', 'model' => ProductVariety::class, 'with' => [], 'edit' => fn (ProductVariety $variety) => $productEdit($variety->product_id)],
            'packaging' => ['label' => 'Product packaging', 'model' => ProductPackaging::class, 'with' => [], 'edit' => fn (ProductPackaging $packaging) => $productEdit($packaging->product_id)],
            'packaging_specs' => ['label' => 'Packaging figures', 'model' => PackagingSpec::class, 'with' => ['packaging'], 'edit' => fn (PackagingSpec $spec) => $productEdit($spec->packaging?->product_id)],
            'seasons' => ['label' => 'Seasonality rows', 'model' => ProductSeason::class, 'with' => [], 'edit' => fn (ProductSeason $season) => $productEdit($season->product_id)],
            'galleries' => ['label' => 'Gallery albums', 'model' => Gallery::class, 'with' => [], 'edit' => fn (Gallery $gallery) => GalleryResource::getUrl('edit', ['record' => $gallery])],
            'menu_items' => ['label' => 'Menu items', 'model' => MenuItem::class, 'with' => ['menu'], 'edit' => fn (MenuItem $item) => ManageMenus::getUrl(['location' => $item->menu?->location])],
            'slides' => ['label' => 'Slides', 'model' => Slide::class, 'with' => [], 'edit' => fn (Slide $slide) => SliderResource::getUrl('edit', ['record' => $slide->slider_id])],
            'documents' => ['label' => 'Certificates & documents', 'model' => Document::class, 'with' => [], 'edit' => fn (Document $document) => DocumentResource::getUrl('edit', ['record' => $document])],
            'media' => ['label' => 'Image alt texts', 'model' => Media::class, 'with' => [], 'edit' => fn (Media $media) => MediaResource::getUrl('edit', ['record' => $media])],
        ];
    }

    /**
     * @return list<array{module: string, module_label: string, id: int, label: string, status: string, edit_url: ?string}>
     */
    public static function rows(string $locale, ?string $module = null): array
    {
        $default = Language::defaultCode();
        $rows = [];

        foreach (self::modules() as $key => $definition) {
            if ($module !== null && $module !== $key) {
                continue;
            }

            $records = $definition['model']::query()
                ->with([...$definition['with'], 'translations' => fn ($query) => $query->whereIn('locale', array_unique([$default, $locale]))])
                ->get();

            foreach ($records as $record) {
                $source = $record->translations->firstWhere('locale', $default);
                $sourceFilled = self::filled($source, $record->translatedAttributes);
                if ($sourceFilled === 0) {
                    continue; // nothing to translate
                }

                $targetFilled = self::filled($record->translations->firstWhere('locale', $locale), $record->translatedAttributes);

                $rows[] = [
                    'module' => $key,
                    'module_label' => $definition['label'],
                    'id' => $record->getKey(),
                    'label' => self::label($source, $record),
                    'status' => match (true) {
                        $targetFilled === 0 => 'missing',
                        $targetFilled < $sourceFilled => 'partial',
                        default => 'complete',
                    },
                    'edit_url' => $definition['edit']($record),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{module: string, module_label: string, status: string}>  $rows
     * @return array<string, array{label: string, complete: int, partial: int, missing: int, total: int, percent: int}>
     */
    public static function summary(array $rows): array
    {
        $summary = [];
        foreach ($rows as $row) {
            $summary[$row['module']] ??= ['label' => $row['module_label'], 'complete' => 0, 'partial' => 0, 'missing' => 0, 'total' => 0, 'percent' => 0];
            $summary[$row['module']][$row['status']]++;
            $summary[$row['module']]['total']++;
        }

        foreach ($summary as &$module) {
            $module['percent'] = (int) floor($module['complete'] / $module['total'] * 100);
        }

        return $summary;
    }

    /**
     * @param  list<string>  $attributes
     */
    private static function filled(?Model $translation, array $attributes): int
    {
        if ($translation === null) {
            return 0;
        }

        return collect($attributes)
            ->reject(fn (string $attribute) => in_array($attribute, ['slug', 'og_media_id'], true))
            ->filter(fn (string $attribute) => filled(trim(strip_tags((string) $translation->{$attribute}))))
            ->count();
    }

    private static function label(Model $source, Model $record): string
    {
        foreach (['title', 'name', 'label', 'alt', 'body', 'description'] as $attribute) {
            $text = trim(strip_tags((string) ($source->{$attribute} ?? '')));
            if ($text !== '') {
                return str($text)->squish()->limit(80)->toString();
            }
        }

        return class_basename($record).' #'.$record->getKey();
    }
}
