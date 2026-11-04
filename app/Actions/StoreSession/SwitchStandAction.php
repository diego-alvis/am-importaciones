<?php

namespace App\Actions\StoreSession;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use App\Models\Stand;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class SwitchStandAction
{
    /**
     * Cambia la tienda activa de operación del usuario validando turnos y sedes.
     */
    public function execute(User $user, int|string $standId, ?string $operatorName = null): Stand
    {
        $openSession = CashSession::with('stand')
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Abierta)
            ->first();

        if ($openSession && (int) $openSession->stand_id !== (int) $standId) {
            throw new DomainException("Tienes un turno de caja abierto en '{$openSession->stand->name}'. Debes cerrar tu turno antes de cambiarte a otra tienda.");
        }

        $stand = Stand::with('location')->findOrFail($standId);

        if (! $user->isAdmin() && $user->location_id && (int) $stand->location_id !== (int) $user->location_id) {
            $locationName = $user->location->name ?? 'sede asignada';
            throw new DomainException("Solo puedes operar tiendas asignadas a tu sede ({$locationName}).");
        }

        DB::transaction(function () use ($user, $stand, $openSession, $operatorName) {
            session(['active_stand_id' => $stand->id]);

            if (! empty($operatorName)) {
                $cleanOperator = trim($operatorName);
                session(['operator_name' => $cleanOperator]);

                if ($openSession) {
                    $openSession->update(['operator_name' => $cleanOperator]);
                }
            }

            if ((int) $user->stand_id !== (int) $stand->id) {
                $user->update(['stand_id' => $stand->id]);
            }
        });

        return $stand;
    }
}
