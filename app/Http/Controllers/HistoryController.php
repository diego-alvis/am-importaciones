<?php

namespace App\Http\Controllers;

use App\Actions\Sales\CancelSaleAction;
use App\Http\Requests\History\FilterHistoryRequest;
use App\Services\HistoryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(FilterHistoryRequest $request, HistoryService $historyService): View
    {
        $data = $historyService->getHistoryData(auth()->user(), $request->validated());

        return view('history.index', $data);
    }

    public function cancel(int|string $id, CancelSaleAction $cancelSaleAction): RedirectResponse
    {
        try {
            $sale = $cancelSaleAction->execute(auth()->user(), $id);

            return back()->with('success', "Venta #{$sale->id} anulada correctamente y stock devuelto a inventario.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al procesar la anulación: '.$e->getMessage());
        }
    }
}
