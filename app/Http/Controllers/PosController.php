<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Stand;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        // El almacén central no realiza ventas
        if ($user->isAlmacen()) {
            return redirect()->route('inventory.index')
                ->with('warning', 'El Almacén Central está reservado para inventario y transferencias de mercadería. No realiza ventas directas.');
        }

        $activeStand = $user->getActiveStand();

        // Tiendas disponibles para el usuario
        $availableStandsQuery = Stand::query();
        if ($user->role !== 'admin' && $user->location_id) {
            $availableStandsQuery->where('location_id', $user->location_id);
        }
        $availableStands = $availableStandsQuery->orderBy('name')->get();

        $categories = Category::orderBy('name')->get();

        $locationId = $user->location_id ?? $activeStand?->location_id;

        $products = Product::with(['category', 'stocks' => function ($query) use ($locationId) {
            if ($locationId) {
                $query->where('location_id', $locationId);
            }
        }])
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                $product->requires_device_model = $product->requiresDeviceModel();
                return $product;
            });

        // Verificar si tiene caja abierta en esta tienda
        $activeSession = CashSession::where('user_id', $user->id)
            ->where('status', 'abierta')
            ->first();

        return view('pos.index', compact('products', 'categories', 'activeStand', 'availableStands', 'activeSession'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
        ]);

        $user = auth()->user();

        if ($user->isAlmacen()) {
            return response()->json([
                'success' => false,
                'error' => 'El Almacén Central no puede registrar ventas directas.',
            ], 403);
        }

        DB::beginTransaction();

        try {
            // 1. Validar que exista una caja 'abierta'
            $cashSession = CashSession::where('user_id', $user->id)
                ->where('status', 'abierta')
                ->first();

            if (!$cashSession) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes un turno de caja abierto. Por favor, abre tu caja antes de registrar ventas.',
                ], 422);
            }

            // 2. Crear Venta Maestra
            $sale = Sale::create([
                'cash_session_id' => $cashSession->id,
                'user_id' => $user->id,
                'customer_id' => null,
                'total_amount' => $request->total,
                'status' => 'completada',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3. Crear Detalles (Items) y descontar stock de la sede
            $locationId = $cashSession->stand?->location_id ?? $user->location_id;

            foreach ($request->cart as $item) {
                $product = Product::find($item['id']);
                $model = ($product && $product->requiresDeviceModel()) ? ($item['device_model'] ?? null) : null;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'device_model' => $model,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Descontar Stock
                if ($locationId) {
                    Stock::where('product_id', $item['id'])
                        ->where('location_id', $locationId)
                        ->decrement('quantity', $item['quantity']);
                }
            }

            // 4. Crear Pagos (SalePayment)
            if ($request->payment_method === 'Mixto') {
                if ($request->cash_tendered > 0) {
                    $efectivo = $request->cash_tendered - ($request->change > 0 ? $request->change : 0);
                    if ($efectivo > 0) {
                        SalePayment::create([
                            'sale_id' => $sale->id,
                            'payment_method' => 'efectivo',
                            'amount' => $efectivo,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                if ($request->transfer_tendered > 0) {
                    SalePayment::create([
                        'sale_id' => $sale->id,
                        'payment_method' => 'yape',
                        'amount' => $request->transfer_tendered,
                        'reference_code' => 'TX-' . rand(100000, 999999),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                $method = strtolower($request->payment_method);
                if ($method === 'yape/plin') {
                    $method = 'yape';
                }

                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => $method,
                    'amount' => $request->total,
                    'reference_code' => in_array($method, ['yape', 'plin', 'tarjeta'], true) ? 'TX-' . rand(100000, 999999) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'total' => (float) $sale->total_amount,
                'message' => 'Venta registrada con éxito.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function ticket(int|string $id): View
    {
        $sale = Sale::with(['items.product', 'payments', 'user', 'cashSession.stand.location'])->findOrFail($id);
        return view('pos.ticket', compact('sale'));
    }
}
