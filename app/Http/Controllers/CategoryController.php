<?php

namespace App\Http\Controllers;

use App\Actions\Category\DeleteCategoryAction;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('products')
            ->orderBy('name', 'asc')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create([
            'name' => trim($request->validated('name')),
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoría creada exitosamente.');
    }

    public function update(UpdateCategoryRequest $request, int|string $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        $category->update([
            'name' => trim($request->validated('name')),
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(int|string $id, DeleteCategoryAction $deleteCategoryAction): RedirectResponse
    {
        $category = Category::findOrFail($id);
        $this->authorize('delete', $category);

        try {
            $deleteCategoryAction->execute($id);

            return redirect()->route('categories.index')->with('success', 'Categoría eliminada exitosamente.');
        } catch (DomainException $e) {
            return redirect()->route('categories.index')->with('error', $e->getMessage());
        }
    }
}
