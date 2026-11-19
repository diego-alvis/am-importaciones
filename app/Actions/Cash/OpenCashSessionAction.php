<?php

namespace App\Actions\Cash;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use App\Models\User;
use DomainException;

class OpenCashSessionAction
{
    /**
     * Abre un turno de caja para el usuario en la tienda seleccionada.
     */
    public function execute(User $user, array $data): CashSession
    {
        $existing = CashSession::where('user_id', $user->id)
            ->where('status', CashSessionStatus::Abierta)
            ->first();

        if ($existing) {
            throw new DomainException('Ya tienes un turno de caja abierto actualmente.');
        }

        if ($user->hasFixedStand()) {
            if (! empty($data['stand_id']) && (int) $data['stand_id'] !== (int) $user->stand_id) {
                throw new DomainException('Solo puedes aperturar caja en el stand fijo asignado a tu cuenta.');
            }
            $standId = $user->stand_id;
        } else {
            $standId = $data['stand_id'] ?? ($user->getActiveStand()?->id ?: $user->stand_id);
        }

        if (! $standId) {
            throw new DomainException('Por favor selecciona la tienda en la que operarás para aperturar la caja.');
        }

        $operatorName = trim($data['operator_name']);
        session([
            'operator_name' => $operatorName,
            'active_stand_id' => $standId,
        ]);

        if ($user->stand_id !== (int) $standId) {
            $user->update(['stand_id' => $standId]);
        }

        return CashSession::create([
            'user_id' => $user->id,
            'stand_id' => $standId,
            'operator_name' => $operatorName,
            'expected_cash' => $data['opening_amount'],
            'opening_time' => now(),
            'status' => CashSessionStatus::Abierta,
        ]);
    }
}
