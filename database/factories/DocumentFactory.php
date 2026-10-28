<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'certificate',
            'is_active' => true,
            'sort' => 0,
            'en' => ['title' => fake()->words(3, true)],
        ];
    }
}
