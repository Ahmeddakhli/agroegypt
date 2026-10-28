<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Astrotomic\Translatable\Locales;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Fillable(['code', 'name', 'native_name', 'direction', 'flag_path', 'fallback_code', 'is_default', 'is_active', 'sort'])]
class Language extends Model
{
    use Auditable, HasFactory;

    public const CACHE_KEY = 'languages.all';

    /** BCP 47 style: "en", "ar", "pt-BR", "zh-Hans" (region/script subtags must start uppercase). */
    public const CODE_PATTERN = '[a-z]{2,3}(?:-[A-Z][A-Za-z]{1,3})?';

    /** @var Collection<int, Language>|null per-request copy of the cached list */
    private static ?Collection $memo = null;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Language $language) {
            if ($language->is_default) {
                $language->is_active = true;
            }
        });

        static::saved(function (Language $language) {
            if ($language->is_default) {
                static::whereKeyNot($language->getKey())->where('is_default', true)->update(['is_default' => false]);
            }
            static::flush();
        });

        static::deleted(function (Language $language) {
            static::where('fallback_code', $language->code)->update(['fallback_code' => null]);
            LanguageLine::whereNotNull("text->{$language->code}")->get()->each(function (LanguageLine $line) use ($language) {
                $text = $line->text;
                unset($text[$language->code]);
                $line->update(['text' => $text]);
            });
            static::flush();
        });
    }

    /**
     * Drop the cached list and re-sync the translatable package so changes apply immediately.
     */
    public static function flush(): void
    {
        static::$memo = null;
        Cache::forget(self::CACHE_KEY);
        static::syncTranslatableConfig();
    }

    /**
     * Languages live in the database: feed them to the translatable package instead of hardcoding config.
     */
    public static function syncTranslatableConfig(): void
    {
        $codes = static::cached()->pluck('code')->all();
        $default = static::defaultCode();

        config([
            'translatable.locales' => $codes ?: [config('app.locale')],
            'translatable.fallback_locale' => $default,
            'app.fallback_locale' => $default,
        ]);

        app()->forgetInstance(Locales::class);
        app()->forgetInstance('translatable.locales');
    }

    /**
     * All languages ordered for display, cached until a language changes.
     * Returns an empty collection before the table exists (fresh installs).
     *
     * @return Collection<int, Language>
     */
    public static function cached(): Collection
    {
        try {
            // Cache raw attributes: the cache store refuses to unserialize arbitrary classes.
            return static::$memo ??= static::hydrate(Cache::rememberForever(
                self::CACHE_KEY,
                fn () => static::orderBy('sort')->orderBy('id')->get()->map->getAttributes()->all(),
            ));
        } catch (Throwable) {
            return new Collection;
        }
    }

    /**
     * @return Collection<int, Language>
     */
    public static function active(): Collection
    {
        return static::cached()->where('is_active', true)->values();
    }

    public static function findByCode(string $code): ?self
    {
        return static::cached()->firstWhere('code', $code);
    }

    public static function defaultCode(): string
    {
        return static::cached()->firstWhere('is_default', true)?->code ?? config('app.locale');
    }

    /**
     * Translation rows (across all translatable tables) written in this language.
     */
    public function contentCount(): int
    {
        $count = 0;
        foreach (Schema::getTableListing() as $table) {
            $name = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
            if (str_ends_with($name, '_translations')) {
                $count += DB::table($name)->where('locale', $this->code)->count();
            }
        }

        return $count + DB::table('settings')->where('locale', $this->code)->count();
    }

    /**
     * Whether anything is stored under this language's code (content, settings or interface strings);
     * the code is then locked, because renaming it would orphan that data and change every URL.
     */
    public function isInUse(): bool
    {
        return $this->contentCount() > 0 || LanguageLine::whereNotNull("text->{$this->code}")->exists();
    }

    public function canBeDeleted(): bool
    {
        return ! $this->is_default && $this->contentCount() === 0;
    }

    public function isRtl(): bool
    {
        return $this->direction === 'rtl';
    }

    /**
     * The fallback chain for this language: its configured fallback, then the default language.
     */
    public function fallbackCode(): string
    {
        return $this->fallback_code ?: static::defaultCode();
    }

    public function flagUrl(): ?string
    {
        return $this->flag_path ? Storage::disk('public')->url($this->flag_path) : null;
    }
}
