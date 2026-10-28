<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id', 'icon_media_id', 'card_media_id', 'image_media_id', 'hero_media_id', 'season_icon_media_id',
    'spec_values', 'status', 'is_featured', 'sort',
])]
class Product extends Model implements TranslatableContract
{
    use Auditable, HasFactory, SoftDeletes, Translatable;

    /** @var list<string> */
    public array $translatedAttributes = [
        'name', 'slug', 'menu_label', 'subtitle', 'short_description', 'description', 'spec_label', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'spec_values' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function imageItems(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'product_images')->withPivot('sort')->orderByPivot('sort');
    }

    public function varieties(): HasMany
    {
        return $this->hasMany(ProductVariety::class)->orderBy('sort');
    }

    public function packagings(): HasMany
    {
        return $this->hasMany(ProductPackaging::class)->orderBy('sort');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(ProductSeason::class)->orderBy('sort');
    }

    public function icon(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'icon_media_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'card_media_id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_media_id');
    }

    public function seasonIcon(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'season_icon_media_id');
    }
}
