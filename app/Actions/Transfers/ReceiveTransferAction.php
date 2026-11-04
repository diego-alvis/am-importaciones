<?php

namespace App\Actions\Transfers;

use App\Enums\TransferStatus;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReceiveTransferAction
{
    /**
     * Confirma la recepción física de una transferencia o pedido e ingresa el stock en la sede de destino.
     */
    public function execute(User $user, Transfer $transfer): Transfer
    {
        if ($transfer->status === TransferStatus::Recibido) {
            throw new DomainException('Este traslado ya fue recibido y confirmado anteriormente.');
        }

        if ($transfer->status === TransferStatus::Cancelado) {
            throw new DomainException('No se puede recibir un traslado cancelado.');
        }

        return DB::transaction(function () use ($transfer) {
            $transfer->loadMissing(['items.product', 'fromLocation', 'toLocation']);

            // Si por alguna razón el traslado no se había descontado de origen (ej. solicitud directa), lo descuenta
            if ($transfer->status === TransferStatus::Solicitado) {
                foreach ($transfer->items as $item) {
                    Stock::where('location_id', $transfer->from_location_id)
                        ->where('product_id', $item->product_id)
                        ->decrement('quantity', (int) $item->quantity);
                }
            }

            // Ingresar stock en la sede de destino
            foreach ($transfer->items as $item) {
                $stockDestino = Stock::firstOrCreate(
                    ['location_id' => $transfer->to_location_id, 'product_id' => $item->product_id],
                    ['quantity' => 0, 'min_stock' => 5]
                );
                $stockDestino->increment('quantity', (int) $item->quantity);
            }

            $transfer->update(['status' => TransferStatus::Recibido]);

            return $transfer;
        });
    }
}
