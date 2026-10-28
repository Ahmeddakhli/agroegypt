<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['folder_id', 'disk', 'path', 'name', 'mime_type', 'size', 'width', 'height', 'sha1', 'variants'])]
class Media extends Model implements TranslatableContract
{
    use Auditable, HasFactory, Translatable;

    /** @var list<string> */
    public array $translatedAttributes = ['alt', 'title'];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function url(?int $width = null): string
    {
        $path = $this->path;

        if ($width !== null && isset($this->variants[$width])) {
            $path = $this->variants[$width];
        }

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Responsive srcset built from the generated WebP variants plus the original.
     */
    public function srcset(): string
    {
        $sources = collect($this->variants ?? [])
            ->sortKeys()
            ->map(fn (string $path, int $width) => Storage::disk($this->disk)->url($path)." {$width}w");

        if ($this->width) {
            $sources->push($this->url()." {$this->width}w");
        }

        return $sources->implode(', ');
    }

    public function altText(): string
    {
        return (string) ($this->alt ?: $this->title ?: pathinfo($this->name, PATHINFO_FILENAME));
    }
}
