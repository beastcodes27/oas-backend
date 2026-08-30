<?php

namespace Database\Factories;

use App\Models\HomeFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeFeature>
 */
class HomeFeatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'text' => fake()->sentence(12),
            'image' => 'https://loremflickr.com/640/300/application',
            'sort_order' => 0,
        ];
    }
}
