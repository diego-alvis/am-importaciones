<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\CashSession;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
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
            'user_id' => fn (array $attributes) => CashSession::find($attributes['cash_session_id'])->user_id,
            'operator_name' => fn (array $attributes) => CashSession::find($attributes['cash_session_id'])->operator_name,
            'customer_id' => null,
            'total_amount' => fake()->randomFloat(2, 10, 200),
            'status' => SaleStatus::Completada->value,
        ];
    }

    /**
     * Venta anulada.
     */
    public function anulada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SaleStatus::Anulada->value,
        ]);
    }
}
