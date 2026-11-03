<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Stand;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        // El Almacén no realiza ventas ni maneja cajas registradoras
        if ($user->isAlmacen()) {
            return redirect()->route('transfers.index')->with('warning', 'El Almacén Central gestiona inventario y traslados; no realiza ventas ni apertura de caja.');
        }

        // Sesión de caja abierta actualmente
        $activeSession = CashSession::with(['sales' => function ($query) {
            $query->where('status', 'completada')->with(['items.product', 'payments']);
        }, 'stand.location'])
            ->where('user_id', $user->id)
            ->where('status', 'abierta')
            ->first();

        $resumen = $activeSession ? $activeSession->getMetrics() : null;

        // Última sesión en precierre enviada hoy (esperando aprobación del supervisor)
        $pendingSession = CashSession::with(['stand.location', 'sales.payments'])
            ->where('user_id', $user->id)
            ->where('status', 'precierre')
            ->whereDate('closing_time', Carbon::today())
            ->latest('closing_time')
            ->first();

        // Sesiones cerradas y aprobadas recientemente por este usuario
        $pastSessions = CashSession::with(['approver', 'stand.location'])
            ->where('user_id', $user->id)
            ->where('status', 'aprobada')
            ->latest('closing_time')
            ->take(5)
            ->get();

        $activeStand = $user->getActiveStand();
        $availableStands = $user->getAvailableStands();

        return view('cash.index', compact(
            'activeSession',
            'resumen',
            'pendingSession',
            'pastSessions',
            'activeStand',
            'availableStands'
        ));
    }

    public function open(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isAlmacen()) {
            return redirect()->route('transfers.index')->with('error', 'El Almacén Central no puede aperturar cajas.');
        }

        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'stand_id' => 'nullable|exists:stands,id',
            'operator_name' => 'nullable|string|max:100',
        ]);

        if ($request->filled('operator_name')) {
            $opName = trim($request->input('operator_name'));
            session(['operator_name' => $opName]);
            $user->update(['name' => $opName]);
        }

        // Evitar apertura simultánea para el mismo usuario
        $existing = CashSession::where('user_id', $user->id)
            ->where('status', 'abierta')
            ->first();

        if ($existing) {
            return redirect()->route('cash.index')->with('warning', 'Ya tienes un turno de caja abierto actualmente.');
        }

        $standId = $request->stand_id ?: ($user->getActiveStand()?->id ?: $user->stand_id);

        if (!$standId) {
            return redirect()->route('cash.index')->with('error', 'Por favor selecciona la tienda en la que operarás para aperturar la caja.');
        }

        // Actualizar stand de la sesión si fue seleccionado
        session(['active_stand_id' => $standId]);
        if ($user->stand_id !== (int) $standId) {
            $user->update(['stand_id' => $standId]);
        }

        CashSession::create([
            'user_id' => $user->id,
            'stand_id' => $standId,
            'expected_cash' => $request->opening_amount,
            'opening_time' => now(),
            'status' => 'abierta',
        ]);

        return redirect()->route('pos.index')->with('success', '¡Caja abierta exitosamente! Listo para vender.');
    }

    public function close(Request $request): RedirectResponse
    {
        $request->validate(['declared_cash' => 'required|numeric|min:0']);

        $session = CashSession::where('user_id', auth()->id())
            ->where('status', 'abierta')
            ->first();

        if (!$session) {
            return redirect()->route('cash.index')->with('error', 'No tienes un turno de caja abierto para realizar el arqueo.');
        }

        $session->update([
            'declared_cash' => $request->declared_cash,
            'closing_time' => now(),
            'status' => 'precierre',
        ]);

        return redirect()->route('cash.index')->with('success', 'Tu turno de caja fue cerrado correctamente y enviado a tu supervisor para aprobación del arqueo.');
    }
}
