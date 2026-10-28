<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'sort' => 0,
            'is_active' => true,
            'en' => ['name' => ucfirst($name), 'slug' => str($name)->slug()->toString()],
        ];
    }
}
