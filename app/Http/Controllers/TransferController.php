<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function index(): View
    {
        $transfers = Transfer::with(['fromLocation', 'toLocation', 'items.product'])
            ->latest()
            ->paginate(15);

        $locations = Location::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('transfers.index', compact('transfers', 'locations', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Verificar stock en origen
            foreach ($request->items as $item) {
                $stockOrigen = Stock::where('location_id', $request->from_location_id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (!$stockOrigen || $stockOrigen->quantity < $item['quantity']) {
                    $prodName = Product::find($item['product_id'])->name ?? 'Producto';
                    throw new \Exception("Stock insuficiente en la sede de origen para '{$prodName}'.");
                }
            }

            $transfer = Transfer::create([
                'from_location_id' => $request->from_location_id,
                'to_location_id' => $request->to_location_id,
                'status' => 'recibido', // Traslado directo inmediato
            ]);

            foreach ($request->items as $item) {
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);

                // Descontar en origen
                Stock::where('location_id', $request->from_location_id)
                    ->where('product_id', $item['product_id'])
                    ->decrement('quantity', $item['quantity']);

                // Aumentar en destino
                $stockDestino = Stock::firstOrCreate(
                    ['location_id' => $request->to_location_id, 'product_id' => $item['product_id']],
                    ['quantity' => 0, 'min_stock' => 5]
                );
                $stockDestino->increment('quantity', $item['quantity']);
            }

            DB::commit();
            return redirect()->route('transfers.index')->with('success', 'Transferencia entre sedes efectuada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error en la transferencia: ' . $e->getMessage());
        }
    }
}
