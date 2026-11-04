<?php

namespace App\Policies;

use App\Models\CashSession;
use App\Models\User;

class CashSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isAlmacen();
    }

    public function open(User $user): bool
    {
        return ! $user->isAlmacen();
    }

    public function close(User $user, CashSession $session): bool
    {
        return (int) $session->user_id === (int) $user->id;
    }

    public function approve(User $user, CashSession $session): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isEncargado()) {
            return (int) $session->stand?->location_id === (int) $user->location_id;
        }

        return false;
    }

    public function viewReport(User $user, CashSession $session): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isEncargado()) {
            return (int) $session->stand?->location_id === (int) $user->location_id;
        }

        if ($user->isVendedor()) {
            return (int) $session->user_id === (int) $user->id;
        }

        return false;
    }
}
