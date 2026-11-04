<?php

namespace App\Services;

use App\Enums\CashSessionStatus;
use App\Enums\SaleStatus;
use App\Models\CashSession;
use App\Models\User;
use Carbon\Carbon;

class CashSessionService
{
    /**
     * Obtiene los datos consolidados para la pantalla de control de caja.
     *
     * @return array<string, mixed>
     */
    public function getCashIndexData(User $user): array
    {
        $activeSession = CashSession::with(['sales' => function ($query) {
            $query->where('status', SaleStatus::Completada)->with(['items.product', 'payments']);
        }, 'stand.location'])
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Abierta)
            ->first();

        $resumen = $activeSession ? $activeSession->getMetrics() : null;

        $pendingSession = CashSession::with(['stand.location', 'sales.payments'])
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Precierre)
            ->whereDate('closing_time', Carbon::today())
            ->latest('closing_time')
            ->first();

        $pastSessions = CashSession::with(['approver', 'stand.location'])
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Aprobada)
            ->latest('closing_time')
            ->take(5)
            ->get();

        $activeStand = $user->getActiveStand();
        $availableStands = $user->getAvailableStands();

        return compact(
            'activeSession',
            'resumen',
            'pendingSession',
            'pastSessions',
            'activeStand',
            'availableStands'
        );
    }
}
