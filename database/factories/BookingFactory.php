<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\CodArea;
use App\Models\Booking;
use App\Models\CodLocation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->approved(),
            'seller_id' => fn (array $attributes) => Product::query()->find($attributes['product_id'])->user_id,
            'buyer_id' => User::factory()->buyer(),
            'agreed_price' => fn (array $attributes) => Product::query()->find($attributes['product_id'])->price,
            'cod_at' => now()->addDay()->setTime(10, 0),
            'cod_location_id' => CodLocation::factory(),
            'cod_area' => CodArea::Inside,
            'status' => BookingStatus::Pending,
            'respond_by' => now()->addHours(12),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Accepted,
        ])->afterCreating(fn (Booking $booking) => $booking->forceFill(['accepted_at' => now()])->save());
    }

    /**
     * Booking Diterima yang jadwal COD-nya sekian menit lagi.
     */
    public function scheduledIn(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'cod_at' => now()->addMinutes($minutes),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Completed,
        ])->afterCreating(fn (Booking $booking) => $booking->forceFill(['accepted_at' => now(), 'completed_at' => now()])->save());
    }
}
