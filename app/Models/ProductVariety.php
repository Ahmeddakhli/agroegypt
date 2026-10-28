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

#[Touches(['product'])]
#[Fillable(['product_id', 'sort'])]
#[WithoutTimestamps]
class ProductVariety extends Model implements TranslatableContract
{
    use Auditable, Translatable;

    /** @var list<string> */
    public array $translatedAttributes = ['name'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
