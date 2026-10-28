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

/**
 * A seasonality row: a label (usually a variety name) and the months (1-12) it is available.
 */
#[Touches(['product'])]
#[Fillable(['product_id', 'months', 'sort'])]
#[WithoutTimestamps]
class ProductSeason extends Model implements TranslatableContract
{
    use Auditable, Translatable;

    /** @var list<string> */
    public array $translatedAttributes = ['label'];

    protected function casts(): array
    {
        return [
            'months' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
