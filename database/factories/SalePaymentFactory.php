<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalePayment>
 */
class SalePaymentFactory extends Factory
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
            'payment_method' => PaymentMethod::Efectivo->value,
            'amount' => fake()->randomFloat(2, 10, 200),
            'reference_code' => null,
        ];
    }

    /**
     * Pago con un método digital o tarjeta, que lleva código de operación.
     */
    public function method(PaymentMethod $method): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => $method->value,
            'reference_code' => $method === PaymentMethod::Efectivo ? null : 'OP-'.fake()->numerify('######'),
        ]);
    }
}
