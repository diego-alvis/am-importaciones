<?php

namespace App\Actions\Inventory;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    /**
     * Da de alta un nuevo producto en el catálogo e inicializa sus existencias multisede.
     *
     * @param  array<string, mixed>  $productData
     * @param  array<string, mixed>  $rawInputs
     */
    public function execute(array $productData, array $rawInputs = [], ?User $actor = null): Product
    {
        return DB::transaction(function () use ($productData, $rawInputs, $actor): Product {
            $product = Product::create([
                'name' => $productData['name'],
                'sku' => strtoupper($productData['sku']),
                'category_id' => $productData['category_id'],
                'base_price' => $productData['base_price'],
            ]);

            $minStock = max(0, (int) ($productData['min_stock'] ?? 5));
            $canSetAnyLocation = $actor === null || $actor->isAdmin();
            $allowedLocationId = $canSetAnyLocation
                ? null
                : (int) ($actor->location_id ?: Location::where('type', LocationType::Almacen->value)->value('id'));

            $locations = Location::all();
            foreach ($locations as $loc) {
                $allowLoc = $canSetAnyLocation || (int) $loc->id === $allowedLocationId;
                $stockInitial = $allowLoc ? max(0, (int) ($rawInputs["stock_loc_{$loc->id}"] ?? 0)) : 0;

                Stock::create([
                    'location_id' => $loc->id,
                    'product_id' => $product->id,
                    'quantity' => $stockInitial,
                    'min_stock' => $minStock,
                ]);
            }

            return $product;
        });
    }
}
