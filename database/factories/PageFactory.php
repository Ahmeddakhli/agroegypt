<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'template' => Page::TEMPLATE_DEFAULT,
            'status' => ContentStatus::Published,
            'show_hero' => true,
            'en' => ['title' => ucwords($title), 'slug' => str($title)->slug()->toString()],
        ];
    }

    public function template(string $template): static
    {
        return $this->state(['template' => $template]);
    }

    public function draft(): static
    {
        return $this->state(['status' => ContentStatus::Draft]);
    }
}
