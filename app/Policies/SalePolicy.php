<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function create(User $user): bool
    {
        return ! $user->isAlmacen();
    }

    public function view(User $user, Sale $sale): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isEncargado()) {
            return (int) $sale->cashSession?->stand?->location_id === (int) $user->location_id;
        }

        if ($user->isVendedor()) {
            if ((int) $sale->user_id === (int) $user->id) {
                return true;
            }

            return $user->hasFixedStand() && (int) $sale->cashSession?->stand_id === (int) $user->stand_id;
        }

        return false;
    }

    public function cancel(User $user, Sale $sale): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isEncargado()) {
            return (int) $sale->cashSession?->stand?->location_id === (int) $user->location_id;
        }

        return false;
    }
}
