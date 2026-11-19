<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Obtiene los productos filtrados según el rol, sede y estado de stock.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getInventoryData(User $user, array $filters): array
    {
        $search = $filters['search'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $stockFilter = $filters['stock_status'] ?? null;
        $filterLocationId = $filters['location_id'] ?? null;
        $sort = $filters['sort'] ?? 'name_asc';

        $centralWarehouse = Location::where('type', LocationType::Almacen->value)->first();
        $scopedLocation = $this->determineScopedLocation($user, $filterLocationId, $centralWarehouse);
        $scopedLocationId = $scopedLocation?->id;

        $query = Product::with(['category', 'stocks.location'])
            ->withSum([
                'stocks as scoped_stock_qty' => function ($q) use ($scopedLocationId): void {
                    if ($scopedLocationId) {
                        $q->where('location_id', $scopedLocationId);
                    }
                },
            ], 'quantity');

        if (! empty($search)) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (! empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if ($stockFilter === 'bajo_stock') {
            $query->whereHas('stocks', function ($q) use ($scopedLocationId): void {
                if ($scopedLocationId) {
                    $q->where('location_id', $scopedLocationId);
                }
                $q->whereColumn('quantity', '<=', 'min_stock');
            });
        } elseif ($stockFilter === 'sin_stock') {
            $query->whereHas('stocks', function ($q) use ($scopedLocationId): void {
                if ($scopedLocationId) {
                    $q->where('location_id', $scopedLocationId);
                }
                $q->where('quantity', '<=', 0);
            });
        } elseif ($stockFilter === 'ok') {
            $query->whereHas('stocks', function ($q) use ($scopedLocationId): void {
                if ($scopedLocationId) {
                    $q->where('location_id', $scopedLocationId);
                }
                $q->whereColumn('quantity', '>', 'min_stock');
            });
        }

        match ($sort) {
            'stock_asc' => $query->orderByRaw('COALESCE(scoped_stock_qty, 0) ASC')->orderBy('name', 'asc'),
            'stock_desc' => $query->orderByRaw('COALESCE(scoped_stock_qty, 0) DESC')->orderBy('name', 'asc'),
            'price_desc' => $query->orderBy('base_price', 'desc')->orderBy('name', 'asc'),
            'price_asc' => $query->orderBy('base_price', 'asc')->orderBy('name', 'asc'),
            default => $query->orderBy('name', 'asc'),
        };

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $locations = $this->getAvailableLocations($user);
        $summary = $this->buildInventorySummary($scopedLocationId);

        return compact(
            'products',
            'categories',
            'locations',
            'search',
            'categoryId',
            'stockFilter',
            'scopedLocation',
            'centralWarehouse',
            'filterLocationId',
            'sort',
            'summary'
        );
    }

    /**
     * Calcula los indicadores de inventario para el alcance visible actual.
     *
     * @return array{total_products: int, total_units: int, out_of_stock: int, low_stock: int, inventory_value: float}
     */
    private function buildInventorySummary(?int $scopedLocationId): array
    {
        $stockBaseQuery = Stock::query()
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at');

        if ($scopedLocationId) {
            $stockBaseQuery->where('stocks.location_id', $scopedLocationId);
        }

        return [
            'total_products' => Product::count(),
            'total_units' => (int) (clone $stockBaseQuery)->sum('stocks.quantity'),
            'out_of_stock' => (clone $stockBaseQuery)->where('stocks.quantity', '<=', 0)->distinct('stocks.product_id')->count('stocks.product_id'),
            'low_stock' => (clone $stockBaseQuery)->where('stocks.quantity', '>', 0)->whereColumn('stocks.quantity', '<=', 'stocks.min_stock')->distinct('stocks.product_id')->count('stocks.product_id'),
            'inventory_value' => (float) (clone $stockBaseQuery)->sum(DB::raw('stocks.quantity * products.base_price')),
        ];
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
