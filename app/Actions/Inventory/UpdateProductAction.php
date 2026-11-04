<?php

namespace App\Actions\Inventory;

use App\Models\Product;

class UpdateProductAction
{
    /**
     * Actualiza la información técnica y de catálogo del producto.
     */
    public function execute(int|string $productId, array $data): Product
    {
        $product = Product::findOrFail($productId);

        $product->update([
            'name' => $data['name'],
            'sku' => strtoupper($data['sku']),
            'category_id' => $data['category_id'],
            'base_price' => $data['base_price'],
        ]);

        return $product;
    }
}
