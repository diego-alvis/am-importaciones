<?php

namespace App\Actions\Transfers;

use App\Enums\TransferStatus;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelTransferAction
{
    /**
     * Cancela una solicitud de pedido o un traslado en tránsito.
     */
    public function execute(User $user, Transfer $transfer): Transfer
    {
        if ($transfer->status === TransferStatus::Recibido) {
            throw new DomainException('No se puede cancelar una transferencia que ya fue recibida.');
        }

        if ($transfer->status === TransferStatus::Cancelado) {
            throw new DomainException('Esta transferencia ya se encuentra cancelada.');
        }

        return DB::transaction(function () use ($transfer) {
            // Si ya estaba en tránsito (el stock fue descontado del origen), lo devolvemos
            if ($transfer->status === TransferStatus::EnTransito) {
                foreach ($transfer->items as $item) {
                    Stock::where('location_id', $transfer->from_location_id)
                        ->where('product_id', $item->product_id)
                        ->increment('quantity', (int) $item->quantity);
                }
            }

            $transfer->update(['status' => TransferStatus::Cancelado]);

            return $transfer;
        });
    }
}

