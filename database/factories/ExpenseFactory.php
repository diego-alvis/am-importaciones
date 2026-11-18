<?php

namespace Database\Factories;

use App\Models\CashSession;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cash_session_id' => CashSession::factory(),
            'type' => 'extra',
            'amount' => fake()->randomFloat(2, 1, 50),
            'description' => fake()->sentence(3),
        ];
    }

    /**
     * Viático fijo del turno.
     */
    public function fijo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fijo',
            'amount' => 11.00,
            'description' => 'Viático diario',
        ]);
    }

    /**
     * Bono de comisión por meta alcanzada.
     */
    public function comision(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'comision',
            'amount' => 10.00,
            'description' => 'Bono por meta de ventas',
        ]);
    }
}
