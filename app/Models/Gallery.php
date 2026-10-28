<?php

namespace App\Models;

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

#[Fillable(['cover_media_id', 'hero_media_id', 'is_featured', 'is_active', 'sort'])]
class Gallery extends Model implements TranslatableContract
{
    use Auditable, HasFactory, Translatable;

    /** @var list<string> */
    public array $translatedAttributes = ['title', 'slug', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function imageItems(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort');
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'gallery_images')->withPivot('sort')->orderByPivot('sort');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_media_id');
    }
}
