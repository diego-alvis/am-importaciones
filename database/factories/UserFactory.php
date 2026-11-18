<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Stand;
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
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
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

    /**
     * Administrador general (acceso a todas las sedes).
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin->value,
        ]);
    }

    /**
     * Encargado o supervisor de una sede.
     */
    public function encargado(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Encargado->value,
        ]);
    }

    /**
     * Vendedor de stand.
     */
    public function vendedor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Vendedor->value,
        ]);
    }

    /**
     * Encargado del almacén central.
     */
    public function almacenero(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Almacenero->value,
        ]);
    }

    /**
     * Asigna el usuario a una sede.
     */
    public function atLocation(Location $location): static
    {
        return $this->state(fn (array $attributes) => [
            'location_id' => $location->id,
        ]);
    }

    /**
     * Asigna el usuario a un stand y a la sede a la que pertenece.
     */
    public function atStand(Stand $stand): static
    {
        return $this->state(fn (array $attributes) => [
            'location_id' => $stand->location_id,
            'stand_id' => $stand->id,
        ]);
    }
}
