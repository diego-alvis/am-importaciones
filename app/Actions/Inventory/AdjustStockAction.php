<?php

namespace App\Actions\Inventory;

use App\Enums\StockAdjustmentType;
use App\Models\Stock;
use App\Models\User;
use DomainException;

class AdjustStockAction
{
    /**
     * Aplica un ajuste manual de existencias validando permisos por sede y rol.
     */
    public function execute(User $user, int|string $productId, array $data): Stock
    {
        if ($user->isVendedor()) {
            throw new DomainException('Los vendedores no tienen autorización para alterar las existencias de inventario.');
        }

        $locationId = (int) $data['location_id'];

        if ($user->isEncargado() && $locationId !== (int) $user->location_id) {
            throw new DomainException('Solo puedes ajustar el stock de tu propia sede.');
        }

        if ($user->isAlmacenero() && $locationId !== (int) $user->location_id) {
            throw new DomainException('Solo puedes ajustar el stock del Almacén Central.');
        }

        $stock = Stock::firstOrCreate(
            ['product_id' => $productId, 'location_id' => $locationId],
            ['quantity' => 0, 'min_stock' => 5]
        );

        $qty = (int) $data['quantity'];
        $operation = $data['operation'] instanceof StockAdjustmentType
            ? $data['operation']
            : StockAdjustmentType::tryFrom($data['operation']);

        if ($operation === StockAdjustmentType::Add) {
            $stock->increment('quantity', $qty);
        } elseif ($operation === StockAdjustmentType::Subtract) {
            $stock->quantity = max(0, $stock->quantity - $qty);
            $stock->save();
        } else {
            $stock->quantity = $qty;
            $stock->save();
        }

        return $stock;
    }
}
