<?php

namespace App\Support;

use App\Models\Language;
use App\Models\LanguageLine;
use Illuminate\Support\Facades\DB;

/**
 * Translation completeness per language, for interface strings and for content records.
 */
final class TranslationStats
{
    /** @var array<string, string> parent table => translation table foreign key */
    public const CONTENT_TABLES = [
        'pages' => 'page_id',
        'page_sections' => 'page_section_id',
        'section_items' => 'section_item_id',
        'categories' => 'category_id',
        'products' => 'product_id',
        'product_varieties' => 'product_variety_id',
        'product_packagings' => 'product_packaging_id',
        'packaging_specs' => 'packaging_spec_id',
        'product_seasons' => 'product_season_id',
        'galleries' => 'gallery_id',
        'menu_items' => 'menu_item_id',
        'slides' => 'slide_id',
        'documents' => 'document_id',
    ];

    /**
     * @return array<string, array{interface: int, content: int}> percentages per language code
     */
    public static function percentages(): array
    {
        $lines = LanguageLine::all(['text']);
        $stats = [];

        foreach (Language::cached() as $language) {
            $translated = $lines->filter(fn (LanguageLine $line) => filled($line->text[$language->code] ?? null))->count();

            $total = 0;
            $done = 0;
            foreach (self::CONTENT_TABLES as $table => $foreignKey) {
                $total += DB::table($table)->count();
                $done += DB::table(str($table)->singular()->append('_translations')->toString())->where('locale', $language->code)->distinct()->count($foreignKey);
            }

            $stats[$language->code] = [
                'interface' => $lines->isEmpty() ? 100 : (int) floor($translated / $lines->count() * 100),
                'content' => $total === 0 ? 100 : (int) floor($done / $total * 100),
            ];
        }

        return $stats;
    }
}
