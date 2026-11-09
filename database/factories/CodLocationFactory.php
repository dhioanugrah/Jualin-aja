<?php

namespace Database\Factories;

use App\Enums\CodArea;
use App\Models\CodLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CodLocation>
 */
class CodLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Gedung '.fake()->randomLetter(),
            'area' => CodArea::Inside,
            'maps_url' => null,
            'is_active' => true,
        ];
    }

    public function around(): static
    {
        return $this->state(fn (array $attributes) => ['area' => CodArea::Around]);
    }
}
