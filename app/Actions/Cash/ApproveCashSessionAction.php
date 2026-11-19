<?php

namespace App\Actions\Cash;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ApproveCashSessionAction
{
    /**
     * Aprueba la sesión de caja en precierre y registra la auditoría.
     */
    public function execute(User $approver, int|string $sessionId): CashSession
    {
        $caja = CashSession::with('stand')->findOrFail($sessionId);

        if ($caja->status !== CashSessionStatus::Precierre) {
            throw new DomainException('Solo se pueden aprobar sesiones que estén en estado de precierre.');
        }

        return DB::transaction(function () use ($caja, $approver) {
            $caja->update([
                'status' => CashSessionStatus::Aprobada,
                'approved_by' => $approver->id,
            ]);

            return $caja;
        });
    }
}
