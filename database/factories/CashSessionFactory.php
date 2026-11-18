<?php

namespace Database\Factories;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashSession>
 */
class CashSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->vendedor(),
            'stand_id' => Stand::factory(),
            'operator_name' => fake()->name(),
            'opening_time' => now(),
            'closing_time' => null,
            'expected_cash' => 100.00,
            'declared_cash' => null,
            'approved_by' => null,
            'status' => CashSessionStatus::Abierta->value,
        ];
    }

    /**
     * Turno abierto, listo para registrar ventas.
     */
    public function abierta(): static
    {
        return $this->state(fn (array $attributes) => [
            'closing_time' => null,
            'declared_cash' => null,
            'approved_by' => null,
            'status' => CashSessionStatus::Abierta->value,
        ]);
    }

    /**
     * Turno cerrado por el cajero, pendiente de aprobación.
     */
    public function precierre(): static
    {
        return $this->state(fn (array $attributes) => [
            'closing_time' => now(),
            'declared_cash' => 100.00,
            'approved_by' => null,
            'status' => CashSessionStatus::Precierre->value,
        ]);
    }

    /**
     * Turno arqueado y aprobado por un supervisor.
     */
    public function aprobada(): static
    {
        return $this->state(fn (array $attributes) => [
            'closing_time' => now(),
            'declared_cash' => 100.00,
            'approved_by' => User::factory()->encargado(),
            'status' => CashSessionStatus::Aprobada->value,
        ]);
    }
}
