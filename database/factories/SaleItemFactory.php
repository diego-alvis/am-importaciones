<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'device_model' => null,
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => fake()->randomFloat(2, 5, 100),
        ];
    }

    /**
     * Ítem con el modelo de celular indicado (carcasas, micas).
     */
    public function withDeviceModel(string $deviceModel = 'iPhone 13'): static
    {
        return $this->state(fn (array $attributes) => [
            'device_model' => $deviceModel,
        ]);
    }
}
