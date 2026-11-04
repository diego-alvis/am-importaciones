<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class InventoryService
{
    /**
     * Obtiene los productos filtrados según el rol, sede y estado de stock.
     *
     * @return array<string, mixed>
     */
    public function getInventoryData(User $user, array $filters): array
    {
        $search = $filters['search'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $stockFilter = $filters['stock_status'] ?? null;
        $filterLocationId = $filters['location_id'] ?? null;

        $centralWarehouse = Location::where('type', LocationType::Almacen->value)->first();
        $scopedLocation = $this->determineScopedLocation($user, $filterLocationId, $centralWarehouse);
        $scopedLocationId = $scopedLocation?->id;

        $query = Product::with(['category', 'stocks.location'])
            ->orderBy('name', 'asc');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (! empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if ($stockFilter === 'bajo_stock') {
            $query->whereHas('stocks', function ($q) use ($scopedLocationId) {
                if ($scopedLocationId) {
                    $q->where('location_id', $scopedLocationId);
                }
                $q->whereColumn('quantity', '<=', 'min_stock');
            });
        } elseif ($stockFilter === 'sin_stock') {
            $query->whereHas('stocks', function ($q) use ($scopedLocationId) {
                if ($scopedLocationId) {
                    $q->where('location_id', $scopedLocationId);
                }
                $q->where('quantity', '<=', 0);
            });
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $locations = $this->getAvailableLocations($user);

        return compact(
            'products',
            'categories',
            'locations',
            'search',
            'categoryId',
            'stockFilter',
            'scopedLocation',
            'centralWarehouse',
            'filterLocationId'
        );
    }

    private function determineScopedLocation(User $user, ?string $filterLocationId, ?Location $centralWarehouse): ?Location
    {
        if ($user->isVendedor()) {
            $activeStand = $user->getActiveStand();
            $locId = $activeStand?->location_id ?? $user->location_id;

            return $locId ? Location::find($locId) : null;
        }

        if ($user->isEncargado()) {
            return $user->location_id ? Location::find($user->location_id) : null;
        }

        if ($user->isAlmacen()) {
            return $user->location ?? $centralWarehouse;
        }

        if ($user->isAdmin() && ! empty($filterLocationId)) {
            return Location::find($filterLocationId);
        }

        return null;
    }

    private function getAvailableLocations(User $user): Collection
    {
        if ($user->isEncargado()) {
            return Location::where('id', $user->location_id)->get();
        }

        if ($user->isAlmacen()) {
            return Location::where('type', LocationType::Almacen->value)->get();
        }

        return Location::orderBy('name')->get();
    }
}
