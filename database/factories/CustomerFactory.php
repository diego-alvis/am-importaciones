<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => 'DNI',
            'document_number' => fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('9########'),
        ];
    }

    /**
     * Cliente empresa identificado con RUC.
     */
    public function ruc(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'RUC',
            'document_number' => fake()->unique()->numerify('20#########'),
            'name' => fake()->company(),
        ]);
    }
}
