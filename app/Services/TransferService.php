<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Enums\TransferStatus;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Transfer;
use App\Models\User;

class TransferService
{
    /**
     * Obtiene las transferencias, el catálogo con disponibilidad en almacén/sede y las ubicaciones.
     *
     * @return array<string, mixed>
     */
    public function getTransfersIndexData(User $user): array
    {
        $query = Transfer::with(['fromLocation', 'toLocation', 'items.product.category', 'requestedBy', 'dispatchedBy'])
            ->latest();

        if ($user->isEncargado() || $user->isVendedor()) {
            $query->where(function ($q) use ($user): void {
                $q->where('from_location_id', $user->location_id)
                    ->orWhere('to_location_id', $user->location_id);
            });
        }

        $transfers = $query->paginate(15);
        $locations = Location::orderBy('name')->get();
        $almacen = Location::where('type', LocationType::Almacen)->first();
        $categories = Category::orderBy('name')->get();

        $rawProducts = Product::with(['stocks', 'category'])->orderBy('name')->get();

        $storeLocationId = $user->location_id;
        $almacenId = $almacen?->id;

        $catalogProducts = $rawProducts->map(function (Product $p) use ($almacenId, $storeLocationId): array {
            $almacenStock = $p->stocks->firstWhere('location_id', $almacenId)?->quantity ?? 0;
            $storeStockObj = $storeLocationId ? $p->stocks->firstWhere('location_id', $storeLocationId) : null;
            $storeStock = $storeStockObj?->quantity ?? 0;
            $minStock = $storeStockObj?->min_stock ?? 5;

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category_id' => $p->category_id,
                'category_name' => $p->category?->name ?? 'General',
                'almacen_stock' => $almacenStock,
                'store_stock' => $storeStock,
                'min_stock' => $minStock,
                'is_low_stock' => $storeStock <= $minStock,
            ];
        });

        $pendingCount = Transfer::where('status', TransferStatus::Solicitado)->count();

        return compact('transfers', 'locations', 'catalogProducts', 'categories', 'almacen', 'pendingCount');
    }
}
