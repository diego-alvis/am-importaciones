<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Sede '.fake()->unique()->city(),
            'type' => LocationType::Sede->value,
        ];
    }

    /**
     * Sede de venta al público.
     */
    public function sede(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LocationType::Sede->value,
        ]);
    }

    /**
     * Almacén central (no realiza ventas).
     */
    public function almacen(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Almacén '.fake()->unique()->city(),
            'type' => LocationType::Almacen->value,
        ]);
    }
}
