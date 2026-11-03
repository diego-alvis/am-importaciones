<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $stockFilter = $request->input('stock_status');

        $query = Product::with(['category', 'stocks.location'])
            ->orderBy('name', 'asc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if ($stockFilter === 'bajo_stock') {
            $query->whereHas('stocks', function ($q) {
                $q->whereColumn('quantity', '<=', 'min_stock');
            });
        } elseif ($stockFilter === 'sin_stock') {
            $query->whereHas('stocks', function ($q) {
                $q->where('quantity', '<=', 0);
            });
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('inventory.index', compact('products', 'categories', 'locations', 'search', 'categoryId', 'stockFilter'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create([
                'name' => $request->name,
                'sku' => strtoupper($request->sku),
                'category_id' => $request->category_id,
                'base_price' => $request->base_price,
            ]);

            // Inicializar stocks para cada ubicación
            $locations = Location::all();
            foreach ($locations as $loc) {
                $stockInitial = $request->input("stock_loc_{$loc->id}", 0);
                Stock::create([
                    'location_id' => $loc->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $stockInitial,
                    'min_stock' => 5,
                ]);
            }

            DB::commit();
            return redirect()->route('inventory.index')->with('success', 'Producto registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al crear producto: ' . $e->getMessage());
        }
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
        ]);

        $product->update([
            'name' => $request->name,
            'sku' => strtoupper($request->sku),
            'category_id' => $request->category_id,
            'base_price' => $request->base_price,
        ]);

        return redirect()->route('inventory.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function adjustStock(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'operation' => 'required|in:add,subtract,set',
            'quantity' => 'required|integer|min:0',
        ]);

        $stock = Stock::firstOrCreate(
            ['product_id' => $id, 'location_id' => $request->location_id],
            ['quantity' => 0, 'min_stock' => 5]
        );

        $qty = (int) $request->quantity;

        if ($request->operation === 'add') {
            $stock->increment('quantity', $qty);
        } elseif ($request->operation === 'subtract') {
            $stock->quantity = max(0, $stock->quantity - $qty);
            $stock->save();
        } else {
            $stock->quantity = $qty;
            $stock->save();
        }

        return redirect()->route('inventory.index')->with('success', 'Stock ajustado con éxito.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('inventory.index')->with('success', 'Producto eliminado exitosamente del catálogo.');
    }
}
