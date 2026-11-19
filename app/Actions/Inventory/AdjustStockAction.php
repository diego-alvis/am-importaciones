<?php

namespace App\Actions\Inventory;

use App\Enums\LocationType;
use App\Enums\StockAdjustmentType;
use App\Models\Location;
use App\Models\Stock;
use App\Models\User;
use DomainException;

class AdjustStockAction
{
    /**
     * Aplica un ajuste manual de existencias validando permisos por sede y rol.
     *
     * @param  array<string, mixed>  $data
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

        if ($user->isAlmacen()) {
            $allowedLocationId = (int) ($user->location_id ?: Location::where('type', LocationType::Almacen->value)->value('id'));

            if ($locationId !== $allowedLocationId) {
                throw new DomainException('Solo puedes ajustar el stock del Almacén Central.');
            }
        }

        $stock = Stock::firstOrCreate(
            ['product_id' => $productId, 'location_id' => $locationId],
            ['quantity' => 0, 'min_stock' => 5]
        );

        $qty = max(0, (int) $data['quantity']);
        $operation = $data['operation'] instanceof StockAdjustmentType
            ? $data['operation']
            : StockAdjustmentType::tryFrom((string) $data['operation']);

        if ($operation === StockAdjustmentType::Add) {
            $stock->quantity += $qty;
        } elseif ($operation === StockAdjustmentType::Subtract) {
            $stock->quantity = max(0, $stock->quantity - $qty);
        } else {
            $stock->quantity = $qty;
        }

        if (array_key_exists('min_stock', $data) && $data['min_stock'] !== null) {
            $stock->min_stock = max(0, (int) $data['min_stock']);
        }

        $stock->save();

        return $stock;
    }
}
