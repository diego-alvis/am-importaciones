<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorController extends Controller
{
    public function index(): View
    {
        $baseQuery = CashSession::with(['user', 'approver', 'stand.location', 'sales.payments']);

        // Si es encargado, filtramos solo las cajas de su sede
        if (auth()->user()->role === 'encargado') {
            $baseQuery->whereHas('stand', function ($q) {
                $q->where('location_id', auth()->user()->location_id);
            });
        }

        $cajas = (clone $baseQuery)
            ->where('status', 'precierre')
            ->orderBy('closing_time', 'desc')
            ->get();

        $cajasAprobadas = (clone $baseQuery)
            ->where('status', 'aprobada')
            ->orderBy('closing_time', 'desc')
            ->take(30)
            ->get();

        return view('supervisor.cajas', compact('cajas', 'cajasAprobadas'));
    }

    public function approve(Request $request, int|string $id): RedirectResponse
    {
        $caja = CashSession::with('stand')->where('status', 'precierre')->findOrFail($id);

        // Validar seguridad extra: el encargado no puede aprobar cajas de otras sedes
        if (auth()->user()->role === 'encargado' && (int) $caja->stand?->location_id !== (int) auth()->user()->location_id) {
            abort(403, 'No puedes aprobar cajas de otra sede.');
        }

        $caja->update([
            'status' => 'aprobada',
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('supervisor.reporte', $caja->id)->with('success', 'Caja aprobada exitosamente. Puedes revisar el reporte oficial a continuación.');
    }

    public function report(int|string $id): View
    {
        $caja = CashSession::with([
            'user',
            'approver',
            'stand.location',
            'sales' => function ($q) {
                $q->where('status', 'completada')->with(['items.product.category', 'payments']);
            },
        ])->findOrFail($id);

        if (auth()->user()->role === 'encargado' && (int) $caja->stand?->location_id !== (int) auth()->user()->location_id) {
            abort(403, 'No tienes autorización para consultar el reporte de otra sede.');
        }

        $metrics = $caja->getMetrics();
        $summaryItems = $caja->getItemsSummary();

        return view('supervisor.reporte', compact('caja', 'metrics', 'summaryItems'));
    }
}
