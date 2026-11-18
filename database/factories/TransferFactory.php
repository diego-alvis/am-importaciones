<?php

namespace Database\Factories;

use App\Enums\TransferStatus;
use App\Models\Location;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_location_id' => Location::factory()->almacen(),
            'to_location_id' => Location::factory()->sede(),
            'requested_by' => User::factory()->vendedor(),
            'dispatched_by' => null,
            'notes' => null,
            'status' => TransferStatus::Solicitado->value,
        ];
    }

    /**
     * Pedido despachado por el almacén, aún no recibido.
     */
    public function enTransito(): static
    {
        return $this->state(fn (array $attributes) => [
            'dispatched_by' => User::factory()->almacenero(),
            'status' => TransferStatus::EnTransito->value,
        ]);
    }

    /**
     * Mercadería recibida en la sede de destino.
     */
    public function recibido(): static
    {
        return $this->state(fn (array $attributes) => [
            'dispatched_by' => User::factory()->almacenero(),
            'status' => TransferStatus::Recibido->value,
        ]);
    }

    /**
     * Pedido cancelado.
     */
    public function cancelado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransferStatus::Cancelado->value,
        ]);
    }
}
