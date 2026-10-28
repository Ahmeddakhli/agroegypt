<?php

namespace App\Support;

use App\Models\Media;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;

/**
 * SEO metadata for one rendered page.
 */
final readonly class Seo
{
    /**
     * @param  array<string, string>  $alternates  locale => URL
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonical,
        public array $alternates = [],
        public ?string $image = null,
        public ?string $keywords = null,
        public array $jsonLd = [],
        public string $type = 'website',
        public bool $noindex = false,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public static function for(Model $model, string $name, ?string $metaTitle, ?string $description, ?Media $image = null, array $jsonLd = [], ?string $keywords = null): self
    {
        $locale = app()->getLocale();
        $format = (string) Setting::get('seo.title_format', '%s', $locale, config('translatable.fallback_locale'));
        $title = filled($metaTitle) ? $metaTitle : str_replace('%s', $name, $format);
        $description = filled($description) ? $description : Setting::get('seo.description', null, $locale, config('translatable.fallback_locale'));

        return new self(
            title: $title,
            description: $description !== null ? str((string) $description)->stripTags()->squish()->limit(300)->toString() : null,
            canonical: SiteIndex::url($model) ?? url()->current(),
            alternates: SiteIndex::alternates($model),
            image: $image?->url() ?? (($default = Setting::get('seo.og_image')) ? asset('storage/'.$default) : null),
            keywords: $keywords,
            jsonLd: $jsonLd,
        );
    }
}
