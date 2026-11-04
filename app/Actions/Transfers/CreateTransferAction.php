<?php

namespace App\Actions\Transfers;

use App\Enums\LocationType;
use App\Enums\TransferStatus;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateTransferAction
{
    /**
     * Registra una solicitud de pedido de tienda o un traslado directo de almacén.
     */
    public function execute(User $user, array $data): Transfer
    {
        $almacen = Location::where('type', LocationType::Almacen)->firstOrFail();

        $isStoreUser = $user->isVendedor() || $user->isEncargado();

        if ($isStoreUser) {
            if (! $user->location_id) {
                throw new DomainException('Tu usuario no tiene una sede asignada para realizar pedidos.');
            }

            $fromId = $almacen->id;
            $toId = (int) $user->location_id;
            $status = TransferStatus::Solicitado;
            $requestedBy = $user->id;
            $dispatchedBy = null;
        } else {
            $fromId = isset($data['from_location_id']) ? (int) $data['from_location_id'] : $almacen->id;
            $toId = (int) $data['to_location_id'];
            $status = TransferStatus::EnTransito;
            $requestedBy = null;
            $dispatchedBy = $user->id;
        }

        if ($fromId === $toId) {
            throw new DomainException('La sede de origen y destino no pueden ser la misma.');
        }

        return DB::transaction(function () use ($fromId, $toId, $status, $requestedBy, $dispatchedBy, $data, $isStoreUser) {
            // Si es un traslado directo (despacho inmediato por Almacén o Admin), validamos y descontamos stock de origen
            if (! $isStoreUser) {
                foreach ($data['items'] as $item) {
                    $stockOrigen = Stock::where('location_id', $fromId)
                        ->where('product_id', $item['product_id'])
                        ->lockForUpdate()
                        ->first();

                    $disponible = $stockOrigen ? $stockOrigen->quantity : 0;
                    if (! $stockOrigen || $disponible < (int) $item['quantity']) {
                        $prodName = Product::find($item['product_id'])->name ?? 'Producto';
                        throw new DomainException("Stock insuficiente en la sede de origen para '{$prodName}'. Disponible: {$disponible} und.");
                    }
                }
            }

            $transfer = Transfer::create([
                'from_location_id' => $fromId,
                'to_location_id' => $toId,
                'status' => $status,
                'requested_by' => $requestedBy,
                'dispatched_by' => $dispatchedBy,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                ]);

                // Si fue despacho directo, descontar stock de origen
                if (! $isStoreUser) {
                    Stock::where('location_id', $fromId)
                        ->where('product_id', $item['product_id'])
                        ->decrement('quantity', (int) $item['quantity']);
                }
            }

            return $transfer;
        });
    }
}
