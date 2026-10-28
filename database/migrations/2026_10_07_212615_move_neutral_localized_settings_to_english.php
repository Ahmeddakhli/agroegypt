<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The reference import stored its English texts for per-language settings as language-neutral values. Once
 * another language became the default, English pages fell back to that language instead. The texts become
 * English values; settings already filled in for English are kept.
 */
return new class extends Migration
{
    private const LOCALIZED = [
        'company' => ['name'],
        'contact' => ['address'],
        'seo' => ['title_format', 'description'],
        'cookies' => ['message'],
        'header' => ['button_label'],
        'footer' => ['tagline', 'links_title', 'contact_title', 'social_title', 'copyright', 'credit'],
    ];

    public function up(): void
    {
        if (! DB::table('languages')->where('code', 'en')->exists()) {
            return;
        }

        foreach (self::LOCALIZED as $group => $keys) {
            foreach ($keys as $key) {
                $hasEnglish = DB::table('settings')->where(['group' => $group, 'key' => $key, 'locale' => 'en'])->exists();
                if (! $hasEnglish) {
                    DB::table('settings')->where(['group' => $group, 'key' => $key, 'locale' => ''])->update(['locale' => 'en', 'updated_at' => now()]);
                }
            }
        }

        Cache::forget('settings.all');
    }
};
