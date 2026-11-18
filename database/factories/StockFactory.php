<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(10, 50),
            'min_stock' => 5,
        ];
    }

    /**
     * Stock por debajo del mínimo (dispara alertas de reposición).
     */
    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 2,
            'min_stock' => 5,
        ]);
    }

    /**
     * Sin unidades disponibles.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }
}
