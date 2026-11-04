<?php

namespace App\Actions\Inventory;

use App\Models\Product;

class DeleteProductAction
{
    /**
     * Da de baja lógica (soft delete) un producto del catálogo.
     */
    public function execute(int|string $productId): Product
    {
        $product = Product::findOrFail($productId);
        $product->delete();

        return $product;
    }
}
