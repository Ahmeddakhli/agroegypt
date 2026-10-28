<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value site settings. Global values use locale '', localized values use the language code.
 */
#[Fillable(['group', 'key', 'locale', 'value'])]
class Setting extends Model
{
    use Auditable;

    public const CACHE_KEY = 'settings.all';

    /** @var array<string, array<string, string|null>>|null per-request copy of the cached settings */
    private static ?array $memo = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }

    public static function flush(): void
    {
        static::$memo = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array<string, string|null>> keyed by "group.key", then locale
     */
    public static function allCached(): array
    {
        return static::$memo ??= Cache::rememberForever(self::CACHE_KEY, function (): array {
            $settings = [];
            foreach (static::all(['group', 'key', 'locale', 'value']) as $setting) {
                $settings["{$setting->group}.{$setting->key}"][$setting->locale] = $setting->value;
            }

            return $settings;
        });
    }

    /**
     * Read a setting. Localized values fall back to the given fallback locale, then to the global value.
     */
    public static function get(string $key, mixed $default = null, ?string $locale = null, ?string $fallbackLocale = null): mixed
    {
        $values = static::allCached()[$key] ?? null;

        if ($values === null) {
            return $default;
        }

        foreach (array_filter([$locale, $fallbackLocale]) as $candidate) {
            if (filled($values[$candidate] ?? null)) {
                return $values[$candidate];
            }
        }

        return $values[''] ?? $default;
    }

    /**
     * Read a per-language setting in the current locale, with the current fallback language.
     */
    public static function localized(string $key, mixed $default = null): mixed
    {
        return static::get($key, $default, app()->getLocale(), config('translatable.fallback_locale'));
    }

    public static function put(string $key, mixed $value, string $locale = ''): void
    {
        [$group, $name] = explode('.', $key, 2);

        static::updateOrCreate(['group' => $group, 'key' => $name, 'locale' => $locale], ['value' => $value]);
    }
}
