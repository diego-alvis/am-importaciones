<?php

namespace App\Http\Controllers;

use App\Actions\Cash\ApproveCashSessionAction;
use App\Enums\SaleStatus;
use App\Http\Requests\Supervisor\ApproveCashSessionRequest;
use App\Models\CashSession;
use App\Services\SupervisorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupervisorController extends Controller
{
    public function index(SupervisorService $supervisorService): View
    {
        $data = $supervisorService->getCajasIndexData(auth()->user());

        return view('supervisor.cajas', $data);
    }

    public function approve(ApproveCashSessionRequest $request, int|string $id, ApproveCashSessionAction $approveCashSessionAction): RedirectResponse
    {
        try {
            $caja = $approveCashSessionAction->execute(auth()->user(), $id);

            return redirect()->route('supervisor.reporte', $caja->id)->with('success', 'Caja aprobada exitosamente. Puedes revisar el reporte oficial a continuación.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al aprobar la caja: '.$e->getMessage());
        }
    }

    public function report(int|string $id): View
    {
        $caja = CashSession::with([
            'user',
            'approver',
            'stand.location',
            'sales' => function ($q) {
                $q->where('status', SaleStatus::Completada)->with(['items.product.category', 'payments']);
            },
        ])->findOrFail($id);

        $this->authorize('viewReport', $caja);

        $metrics = $caja->getMetrics();
        $summaryItems = $caja->getItemsSummary();

        return view('supervisor.reporte', compact('caja', 'metrics', 'summaryItems'));
    }
}
