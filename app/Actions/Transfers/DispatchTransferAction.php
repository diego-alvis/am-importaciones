<?php

namespace App\Actions\Transfers;

use App\Enums\TransferStatus;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class DispatchTransferAction
{
    /**
     * Alista y despacha una solicitud de pedido desde el Almacén Central hacia la sede de destino.
     */
    public function execute(User $user, Transfer $transfer): Transfer
    {
        if ($transfer->status !== TransferStatus::Solicitado) {
            throw new DomainException("Este pedido no puede ser despachado porque su estado actual es '{$transfer->status->label()}'.");
        }

        return DB::transaction(function () use ($user, $transfer) {
            $transfer->loadMissing(['items.product', 'fromLocation', 'toLocation']);

            // 1. Validar que Almacén Central tenga stock suficiente de cada artículo
            foreach ($transfer->items as $item) {
                $stockOrigen = Stock::where('location_id', $transfer->from_location_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $disponible = $stockOrigen ? $stockOrigen->quantity : 0;
                if (! $stockOrigen || $disponible < (int) $item->quantity) {
                    $prodName = $item->product->name ?? 'Producto';
                    throw new DomainException("Stock insuficiente en {$transfer->fromLocation->name} para despachar '{$prodName}'. Disponible: {$disponible} und, Solicitado: {$item->quantity} und.");
                }
            }

            // 2. Descontar stock del origen (mercadería alistada y en tránsito)
            foreach ($transfer->items as $item) {
                Stock::where('location_id', $transfer->from_location_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('quantity', (int) $item->quantity);
            }

            // 3. Actualizar estado a En Tránsito
            $transfer->update([
                'status' => TransferStatus::EnTransito,
                'dispatched_by' => $user->id,
            ]);

            return $transfer;
        });
    }
}

