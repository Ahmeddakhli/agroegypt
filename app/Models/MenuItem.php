<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A menu entry linking to a page, category, product, gallery (via `linkable`) or a custom URL.
 */
#[Touches(['menu'])]
#[Fillable(['menu_id', 'parent_id', 'linkable_type', 'linkable_id', 'url', 'target', 'sort', 'is_visible'])]
class MenuItem extends Model implements TranslatableContract
{
    use Auditable, Translatable;

    /** @var list<string> */
    public array $translatedAttributes = ['label'];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
