<?php

namespace App\Actions\Category;

use App\Models\Category;
use DomainException;

class DeleteCategoryAction
{
    /**
     * Elimina una categoría verificando que no tenga productos asociados (incluyendo archivados).
     */
    public function execute(int|string $categoryId): Category
    {
        $category = Category::withCount(['products' => function ($q) {
            $q->withTrashed();
        }])->findOrFail($categoryId);

        if ($category->products_count > 0) {
            throw new DomainException("No se puede eliminar la categoría '{$category->name}' porque contiene {$category->products_count} producto(s) asociado(s) en el catálogo.");
        }

        $category->delete();

        return $category;
    }
}
