<?php

namespace Database\Factories;

use App\Enums\UserMode;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nim = (string) fake()->unique()->numberBetween(10_000_000, 19_999_999);

        return [
            'name' => fake()->name(),
            'nim' => $nim,
            'email' => $nim.'@'.config('jualin.email_domain'),
            'whatsapp' => '628'.fake()->numerify('##########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'nim' => null,
            'role' => UserRole::Admin,
        ]);
    }

    public function seller(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_mode' => UserMode::Seller,
        ]);
    }

    public function buyer(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_mode' => UserMode::Buyer,
        ]);
    }

    public function withoutWhatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'whatsapp' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
