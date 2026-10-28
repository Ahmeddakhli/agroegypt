<?php

namespace Database\Factories;

use App\Models\Gallery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'is_active' => true,
            'is_featured' => false,
            'sort' => 0,
            'en' => ['title' => ucwords($title), 'slug' => str($title)->slug()->toString()],
        ];
    }
}
