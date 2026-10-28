<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'key', 'effect', 'autoplay', 'interval_ms', 'transition_ms', 'show_arrows', 'height_desktop', 'height_tablet', 'height_mobile'])]
class Slider extends Model
{
    use Auditable, HasFactory;

    /** @var list<string> */
    public const EFFECTS = ['fade', 'slide', 'zoom'];

    protected function casts(): array
    {
        return [
            'autoplay' => 'boolean',
            'show_arrows' => 'boolean',
        ];
    }

    public function slides(): HasMany
    {
        return $this->hasMany(Slide::class)->orderBy('sort');
    }

    public function activeSlides(): HasMany
    {
        return $this->slides()->where('is_active', true);
    }
}
