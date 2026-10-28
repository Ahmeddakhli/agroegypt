<?php

namespace App\Filament\Support;

use App\Models\Language;
use App\Support\CatalogSlug;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-language form tabs for astrotomic-translatable models. Fields live under the language code
 * ("en.name", "ar.name"), which is exactly the shape Model::fill() expects for translations.
 * Languages come from the database, so a newly added language gets its tab automatically.
 */
final class Translations
{
    /**
     * @param  Closure(Language $language, bool $isDefault): array<int, mixed>  $fields
     */
    public static function tabs(Closure $fields, string $label = 'Content'): Tabs
    {
        $default = Language::defaultCode();

        return Tabs::make($label)
            ->tabs(Language::cached()->map(fn (Language $language) => Tab::make($language->native_name)
                ->key('lang-'.$language->code)
                ->icon($language->is_active ? null : 'heroicon-o-eye-slash')
                ->badge(fn (?Model $record) => $record?->exists && method_exists($record, 'hasTranslation') && ! $record->hasTranslation($language->code) ? __('admin.missing') : null)
                ->badgeColor('warning')
                ->schema([
                    Group::make($fields($language, $language->code === $default))
                        ->statePath($language->code)
                        ->extraAttributes(['dir' => $language->direction, 'lang' => $language->code]),
                ]))
                ->all())
            ->persistTabInQueryString()
            ->columnSpanFull();
    }

    /**
     * Form state for a record: its own translations per language, without fallback values.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fill(Model $record): array
    {
        $state = [];

        foreach (Language::cached() as $language) {
            $translation = $record->getTranslation($language->code, false);
            $state[$language->code] = $translation ? $translation->only($record->translatedAttributes) : [];
        }

        return $state;
    }

    /**
     * Drop language blocks the admin left completely empty (an empty rich editor counts as empty), so no blank translation rows are created
     * and the frontend falls back instead. Returns the codes that were emptied.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public static function strip(array &$data): array
    {
        $emptied = [];

        foreach (Language::cached() as $language) {
            $block = $data[$language->code] ?? null;
            if (is_array($block) && collect($block)->filter(fn ($value) => is_string($value) ? filled(trim(strip_tags($value))) : filled($value))->isEmpty()) {
                unset($data[$language->code]);
                $emptied[] = $language->code;
            }
        }

        return $emptied;
    }

    /**
     * Delete translation rows for languages whose fields were all cleared.
     *
     * @param  list<string>  $codes
     */
    public static function forget(Model $record, array $codes): void
    {
        if ($codes !== []) {
            $record->deleteTranslations(array_values(array_diff($codes, [Language::defaultCode()])));
        }
    }

    /**
     * Make a relationship repeater read and write per-language item fields; a language whose fields are
     * all cleared loses its translation row, so the item falls back like the parent record does.
     *
     * @param  (Closure(array<string, mixed>): array<string, mixed>)|null  $afterFill  adjusts an item's form state after loading
     * @param  (Closure(array<string, mixed>): array<string, mixed>)|null  $beforeSave  adjusts an item's data before it is stored
     */
    public static function repeater(Repeater $repeater, ?Closure $afterFill = null, ?Closure $beforeSave = null): Repeater
    {
        $beforeSave ??= fn (array $data): array => $data;
        $afterFill ??= fn (array $data): array => $data;

        return $repeater
            ->mutateRelationshipDataBeforeFillUsing(function (array $data, Repeater $component) use ($afterFill): array {
                $record = $component->getCachedExistingRecords()->first(fn (Model $item) => $item->getKey() === ($data['id'] ?? null));

                return $afterFill($record ? [...$data, ...self::fill($record)] : $data);
            })
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data) use ($beforeSave): array {
                $data = $beforeSave($data);
                self::strip($data);

                return $data;
            })
            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, Model $record) use ($beforeSave): array {
                $data = $beforeSave($data);
                self::forget($record, self::strip($data));

                return $data;
            });
    }

    /**
     * Allowed link targets for admin-entered URLs: site paths, web addresses, mail, phone and anchors.
     */
    public const LINK_PATTERN = '#^(/|https?://|mailto:|tel:|\#)\S*$#';

    /**
     * Name input that fills the slug while the slug is still empty.
     */
    public static function nameField(string $name, bool $required, string $label = 'Name'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->required($required)
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                if (blank($get('slug')) && filled($state)) {
                    $set('slug', CatalogSlug::from($state));
                }
            });
    }

    /**
     * Slug input. $taken receives (locale, slug, form Get, edited record) and returns true when the slug is already used.
     *
     * @param  Closure(string, string, Get, ?Model): bool  $taken
     */
    public static function slugField(Language $language, bool $required, Closure $taken): TextInput
    {
        return TextInput::make('slug')
            ->label('URL slug')
            ->required($required)
            ->maxLength(190)
            ->regex('/^(?!.*\p{Lu})[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*$/u')
            ->validationMessages(['regex' => __('Use lowercase letters, numbers and hyphens only.')])
            ->rule(fn (Get $get, ?Model $record) => function (string $attribute, mixed $value, Closure $fail) use ($language, $taken, $get, $record) {
                if (filled($value) && $taken($language->code, (string) $value, $get, $record)) {
                    $fail(__('This slug is already used here in :language.', ['language' => $language->name]));
                }
            });
    }
}
