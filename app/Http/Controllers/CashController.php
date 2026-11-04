<?php

namespace App\Http\Controllers;

use App\Actions\Cash\CloseCashSessionAction;
use App\Actions\Cash\OpenCashSessionAction;
use App\Http\Requests\Cash\CloseCashSessionRequest;
use App\Http\Requests\Cash\OpenCashSessionRequest;
use App\Services\CashSessionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CashController extends Controller
{
    public function index(CashSessionService $cashSessionService): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isAlmacen()) {
            return redirect()->route('transfers.index')->with('warning', 'El Almacén Central gestiona inventario y traslados; no realiza ventas ni apertura de caja.');
        }

        $data = $cashSessionService->getCashIndexData($user);

        return view('cash.index', $data);
    }

    public function open(OpenCashSessionRequest $request, OpenCashSessionAction $openCashSessionAction): RedirectResponse
    {
        try {
            $openCashSessionAction->execute(auth()->user(), $request->validated());

            return redirect()->route('pos.index')->with('success', '¡Caja abierta exitosamente! Listo para vender.');
        } catch (DomainException $e) {
            return redirect()->route('cash.index')->with('warning', $e->getMessage());
        }
    }

    public function close(CloseCashSessionRequest $request, CloseCashSessionAction $closeCashSessionAction): RedirectResponse
    {
        try {
            $closeCashSessionAction->execute(auth()->user(), (float) $request->validated('declared_cash'));

            return redirect()->route('cash.index')->with('success', 'Tu turno de caja fue cerrado correctamente y enviado a tu supervisor para aprobación del arqueo.');
        } catch (DomainException $e) {
            return redirect()->route('cash.index')->with('error', $e->getMessage());
        }
    }
}
