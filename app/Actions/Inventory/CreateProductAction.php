<?php

namespace App\Actions\Inventory;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    /**
     * Da de alta un nuevo producto en el catálogo e inicializa sus existencias multisede.
     */
    public function execute(array $productData, array $rawInputs = []): Product
    {
        return DB::transaction(function () use ($productData, $rawInputs) {
            $product = Product::create([
                'name' => $productData['name'],
                'sku' => strtoupper($productData['sku']),
                'category_id' => $productData['category_id'],
                'base_price' => $productData['base_price'],
            ]);

            $locations = Location::all();
            foreach ($locations as $loc) {
                $stockInitial = $rawInputs["stock_loc_{$loc->id}"] ?? 0;
                Stock::create([
                    'location_id' => $loc->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $stockInitial,
                    'min_stock' => 5,
                ]);
            }

            return $product;
        });
    }
}
