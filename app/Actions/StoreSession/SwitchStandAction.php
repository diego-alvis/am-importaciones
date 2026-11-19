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
     * Cambia la tienda activa de operación (solo cuentas sin stand fijo) y/o actualiza el operador de turno.
     */
    public function execute(User $user, int|string|null $standId = null, ?string $operatorName = null): Stand
    {
        $openSession = CashSession::with('stand')
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Abierta)
            ->first();

        if ($user->hasFixedStand()) {
            if (! is_null($standId) && $standId !== '' && (int) $standId !== (int) $user->stand_id) {
                throw new DomainException('Tu cuenta pertenece a un stand fijo y no puede cambiarse a otra tienda.');
            }

            $stand = Stand::with('location')->findOrFail($user->stand_id);

            DB::transaction(function () use ($stand, $openSession, $operatorName): void {
                session(['active_stand_id' => $stand->id]);

                if (! empty($operatorName)) {
                    $cleanOperator = trim($operatorName);
                    session(['operator_name' => $cleanOperator]);

                    if ($openSession) {
                        $openSession->update(['operator_name' => $cleanOperator]);
                    }
                }
            });

            return $stand;
        }

        if (empty($standId)) {
            throw new DomainException('Por favor selecciona la tienda en la que operarás.');
        }

        if ($openSession && (int) $openSession->stand_id !== (int) $standId) {
            throw new DomainException("Tienes un turno de caja abierto en '{$openSession->stand->name}'. Debes cerrar tu turno antes de cambiarte a otra tienda.");
        }

        $stand = Stand::with('location')->findOrFail($standId);

        if (! $user->isAdmin() && $user->location_id && (int) $stand->location_id !== (int) $user->location_id) {
            $locationName = $user->location->name ?? 'sede asignada';
            throw new DomainException("Solo puedes operar tiendas asignadas a tu sede ({$locationName}).");
        }

        DB::transaction(function () use ($user, $stand, $openSession, $operatorName): void {
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
