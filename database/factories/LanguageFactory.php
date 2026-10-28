<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??'),
            'name' => fake()->word(),
            'native_name' => fake()->word(),
            'direction' => 'ltr',
            'is_default' => false,
            'is_active' => true,
            'sort' => 0,
        ];
    }

    public function english(): static
    {
        return $this->state(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true, 'sort' => 1]);
    }

    public function arabic(): static
    {
        return $this->state(['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'fallback_code' => 'en', 'sort' => 2]);
    }

    public function french(): static
    {
        return $this->state(['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'fallback_code' => 'en', 'sort' => 3]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
