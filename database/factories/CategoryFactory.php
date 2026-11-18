<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Accesorios '.fake()->unique()->word(),
        ];
    }

    /**
     * Categoría cuyos productos exigen el modelo de celular al venderse.
     */
    public function requiresDeviceModel(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Carcasas '.fake()->unique()->word(),
        ]);
    }
}
