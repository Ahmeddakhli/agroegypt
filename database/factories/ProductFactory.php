<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'status' => ContentStatus::Published,
            'sort' => 0,
            'spec_values' => ['40', '48', '56'],
            'en' => [
                'name' => ucwords($name),
                'slug' => str($name)->slug()->toString(),
                'description' => fake()->sentence(),
                'spec_label' => 'Counts',
            ],
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ContentStatus::Draft]);
    }
}
