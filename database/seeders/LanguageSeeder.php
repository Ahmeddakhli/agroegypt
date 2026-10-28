<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        Language::updateOrCreate(['code' => 'en'], ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_default' => true, 'is_active' => true, 'sort' => 1]);
        Language::updateOrCreate(['code' => 'ar'], ['name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'fallback_code' => 'en', 'is_active' => true, 'sort' => 2]);
    }
}
