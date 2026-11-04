<?php

namespace App\Services;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SupervisorService
{
    /**
     * Obtiene el listado de cajas en precierre y el historial reciente de cajas aprobadas.
     *
     * @return array{cajas: Collection, cajasAprobadas: Collection}
     */
    public function getCajasIndexData(User $user): array
    {
        $baseQuery = CashSession::with(['user', 'approver', 'stand.location', 'sales.payments']);

        if ($user->isEncargado()) {
            $baseQuery->whereHas('stand', function ($q) use ($user) {
                $q->where('location_id', $user->location_id);
            });
        }

        $cajas = (clone $baseQuery)
            ->where('status', CashSessionStatus::Precierre)
            ->orderBy('closing_time', 'desc')
            ->get();

        $cajasAprobadas = (clone $baseQuery)
            ->where('status', CashSessionStatus::Aprobada)
            ->orderBy('closing_time', 'desc')
            ->take(30)
            ->get();

        return compact('cajas', 'cajasAprobadas');
    }
}
