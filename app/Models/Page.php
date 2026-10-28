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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['template', 'status', 'published_at', 'hero_media_id', 'show_hero', 'sort'])]
class Page extends Model implements TranslatableContract
{
    use Auditable, HasFactory, SoftDeletes, Translatable;

    public const TEMPLATE_DEFAULT = 'default';

    public const TEMPLATE_HOME = 'home';

    public const TEMPLATE_PRODUCTS = 'products';

    public const TEMPLATE_GALLERY = 'gallery';

    /** @var list<string> */
    public array $translatedAttributes = ['title', 'slug', 'meta_title', 'meta_description', 'meta_keywords', 'og_media_id'];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'show_hero' => 'boolean',
        ];
    }

    /**
     * Published and not scheduled in the future.
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published && ($this->published_at === null || $this->published_at->isPast());
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort');
    }

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_media_id');
    }
}
