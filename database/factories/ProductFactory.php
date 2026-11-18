<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
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
            'category_id' => Category::factory(),
            'sku' => fake()->unique()->bothify('AM-???-####'),
            'name' => 'Cargador '.fake()->words(2, true),
            'base_price' => fake()->randomFloat(2, 5, 100),
        ];
    }

    /**
     * Producto que exige el modelo de celular al venderse (carcasas, micas).
     */
    public function requiresDeviceModel(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => Category::factory()->requiresDeviceModel(),
            'name' => 'Funda '.fake()->words(2, true),
        ]);
    }
}
