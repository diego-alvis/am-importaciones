<?php

namespace App\Actions\Cash;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use App\Models\User;
use DomainException;

class CloseCashSessionAction
{
    /**
     * Cierra el turno de caja del usuario y pasa la sesión a precierre para auditoría.
     */
    public function execute(User $user, float $declaredCash): CashSession
    {
        $session = CashSession::where('user_id', $user->id)
            ->where('status', CashSessionStatus::Abierta)
            ->first();

        if (! $session) {
            throw new DomainException('No tienes un turno de caja abierto para realizar el arqueo.');
        }

        $session->update([
            'declared_cash' => $declaredCash,
            'closing_time' => now(),
            'status' => CashSessionStatus::Precierre,
        ]);

        session()->forget('operator_name');

        return $session;
    }
}
