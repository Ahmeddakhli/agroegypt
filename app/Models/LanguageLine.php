<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Spatie\TranslationLoader\LanguageLine as BaseLanguageLine;

/**
 * Database-stored UI strings (buttons, labels, validation messages) that override lang/ files.
 */
class LanguageLine extends BaseLanguageLine
{
    use Auditable;

    /** Translation groups (lang/{locale}/{group}.php) managed from the admin. "*" = lang/{locale}.json */
    public const GROUPS = ['site', 'validation', 'auth', 'passwords', 'pagination'];

    /**
     * Flush every language's cached group, not only languages already present on this line, so lines
     * that fall back for a newly added language refresh too.
     */
    public function flushGroupCache(): void
    {
        foreach (Language::cached()->pluck('code')->push(config('app.locale'))->unique() as $locale) {
            Cache::forget(static::getCacheKey($this->group, $locale));
        }
    }

    public function missingIn(string $locale): bool
    {
        return blank($this->text[$locale] ?? null);
    }

    /**
     * Import keys from the lang/ files. Existing database values are never overwritten, so admin edits
     * survive; new keys and newly available languages are added. Returns the number of lines created.
     */
    public static function syncFromFiles(): int
    {
        $created = 0;
        $codes = Language::cached()->pluck('code')->all();

        foreach (self::GROUPS as $group) {
            $values = [];
            foreach ($codes as $code) {
                $path = lang_path("{$code}/{$group}.php");
                foreach (File::exists($path) ? Arr::dot(require $path) : [] as $key => $text) {
                    if (is_string($text)) {
                        $values[$key][$code] = $text;
                    }
                }
            }
            $created += self::merge($group, $values);
        }

        return $created;
    }

    /**
     * @param  array<string, array<string, string>>  $values  key => [locale => text]
     */
    public static function merge(string $group, array $values, bool $overwrite = false): int
    {
        $created = 0;
        $existing = static::where('group', $group)->get()->keyBy('key');

        foreach ($values as $key => $texts) {
            $texts = array_filter($texts, fn ($text) => is_string($text) && $text !== '');
            $line = $existing->get($key);

            if ($line === null) {
                static::create(['group' => $group, 'key' => $key, 'text' => $texts]);
                $created++;

                continue;
            }

            $merged = $overwrite ? [...$line->text, ...$texts] : [...$texts, ...$line->text];
            if ($merged !== $line->text) {
                $line->update(['text' => $merged]);
            }
        }

        return $created;
    }
}
