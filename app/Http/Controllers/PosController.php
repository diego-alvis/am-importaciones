<?php

namespace App\Http\Controllers;

use App\Actions\Pos\ProcessSaleAction;
use App\Http\Requests\Pos\StoreSaleRequest;
use App\Models\Sale;
use App\Services\PosService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(PosService $posService): View|RedirectResponse
    {
        $user = auth()->user();

        // El almacén central no realiza ventas
        if ($user->isAlmacen()) {
            return redirect()->route('inventory.index')
                ->with('warning', 'El Almacén Central está reservado para inventario y transferencias de mercadería. No realiza ventas directas.');
        }

        $data = $posService->getPosData($user);

        return view('pos.index', $data);
    }

    public function store(StoreSaleRequest $request, ProcessSaleAction $processSaleAction): JsonResponse
    {
        try {
            $sale = $processSaleAction->execute(auth()->user(), $request->validated());

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'total' => (float) $sale->total_amount,
                'message' => 'Venta registrada con éxito.',
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar la venta: '.$e->getMessage(),
            ], 500);
        }
    }

    public function ticket(int|string $id): View
    {
        $sale = Sale::with(['items.product', 'payments', 'user', 'cashSession.stand.location'])->findOrFail($id);

        $this->authorize('view', $sale);

        return view('pos.ticket', compact('sale'));
    }
}
