<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

#[Fillable(['from_path', 'to_path', 'status_code', 'is_active'])]
class Redirect extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status_code' => 'integer',
        ];
    }

    /**
     * Normalize a request path to the stored form: leading slash, no trailing slash, lowercase.
     */
    public static function normalizePath(string $path): string
    {
        return '/'.trim(mb_strtolower(rawurldecode(parse_url($path, PHP_URL_PATH) ?? '')), '/');
    }

    /**
     * Redirect response for a legacy path, or null when no active redirect matches.
     */
    public static function respondTo(string $path): ?RedirectResponse
    {
        $redirect = static::where('is_active', true)->where('from_path', static::normalizePath($path))->first();

        if ($redirect === null) {
            return null;
        }

        $redirect->increment('hits');

        $target = str_starts_with($redirect->to_path, 'http') ? $redirect->to_path : url($redirect->to_path);

        return redirect()->away($target, $redirect->status_code);
    }

    protected function fromPath(): Attribute
    {
        return Attribute::set(fn (string $value) => static::normalizePath($value));
    }
}
