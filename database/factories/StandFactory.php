<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Stand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stand>
 */
class StandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory()->sede(),
            'name' => 'Tienda '.fake()->unique()->bothify('?-##'),
            'base_cash' => 100.00,
        ];
    }
}
