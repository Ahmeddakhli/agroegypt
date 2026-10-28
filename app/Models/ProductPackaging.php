<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Touches(['product'])]
#[Fillable(['product_id', 'icon_media_id', 'sort'])]
#[WithoutTimestamps]
class ProductPackaging extends Model implements TranslatableContract
{
    use Auditable, Translatable;

    /** @var list<string> */
    public array $translatedAttributes = ['title'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function icon(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'icon_media_id');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(PackagingSpec::class)->orderBy('sort');
    }
}
