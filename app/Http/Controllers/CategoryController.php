<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ]);

        Category::create([
            'name' => trim($request->name),
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoría creada exitosamente.');
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
        ]);

        $category->update([
            'name' => trim($request->name),
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $category = Category::withCount('products')->findOrFail($id);

        if ($category->products_count > 0) {
            return redirect()->route('categories.index')->with('error', "No se puede eliminar la categoría '{$category->name}' porque contiene {$category->products_count} producto(s) asignado(s).");
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Categoría eliminada exitosamente.');
    }
}
