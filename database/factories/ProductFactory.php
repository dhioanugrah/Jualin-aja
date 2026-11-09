<?php

namespace Database\Factories;

use App\Enums\CodArea;
use App\Enums\HandoverMethod;
use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->seller(),
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'condition' => fake()->randomElement(ProductCondition::cases()),
            'price' => fake()->numberBetween(5, 500) * 1000,
            'is_donation' => false,
            'handover_method' => HandoverMethod::Cod,
            'cod_areas' => [CodArea::Inside],
            'location' => 'Asrama ITK',
            'status' => ProductStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ProductStatus::Approved]);
    }

    public function needsRevision(string $note = 'Foto kurang jelas.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::NeedsRevision,
            'moderation_note' => $note,
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Sold,
            'sold_at' => now(),
        ]);
    }
}
