<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\CreateProductAction;
use App\Actions\Inventory\DeleteProductAction;
use App\Actions\Inventory\UpdateProductAction;
use App\Http\Requests\Inventory\AdjustStockRequest;
use App\Http\Requests\Inventory\FilterInventoryRequest;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Models\Product;
use App\Services\InventoryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(FilterInventoryRequest $request, InventoryService $inventoryService): View
    {
        $data = $inventoryService->getInventoryData(auth()->user(), $request->validated());

        return view('inventory.index', $data);
    }

    public function store(StoreProductRequest $request, CreateProductAction $createProductAction): RedirectResponse
    {
        try {
            $createProductAction->execute($request->validated(), $request->all());

            return redirect()->route('inventory.index')->with('success', 'Producto registrado exitosamente.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error al crear producto: '.$e->getMessage());
        }
    }

    public function update(UpdateProductRequest $request, int|string $id, UpdateProductAction $updateProductAction): RedirectResponse
    {
        $updateProductAction->execute($id, $request->validated());

        return redirect()->route('inventory.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function adjustStock(AdjustStockRequest $request, int|string $id, AdjustStockAction $adjustStockAction): RedirectResponse
    {
        try {
            $adjustStockAction->execute(auth()->user(), $id, $request->validated());

            return redirect()->route('inventory.index')->with('success', 'Stock ajustado con éxito.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Error inesperado al ajustar stock: '.$e->getMessage());
        }
    }

    public function destroy(int|string $id, DeleteProductAction $deleteProductAction): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('delete', $product);

        $deleteProductAction->execute($id);

        return redirect()->route('inventory.index')->with('success', 'Producto archivado exitosamente del catálogo.');
    }
}
