<?php

namespace Database\Factories;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->approved(),
            'buyer_id' => User::factory()->buyer(),
            'price' => fake()->numberBetween(5, 100) * 1000,
            'message' => null,
            'status' => OfferStatus::Pending,
            'expires_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatus::Accepted,
            'expires_at' => now()->addHours(24),
        ]);
    }
}
