<?php

namespace App\Policies;

use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function dispatch(User $user, Transfer $transfer): bool
    {
        if ($user->isAdmin() || $user->isAlmacenero()) {
            return true;
        }

        return (int) $transfer->from_location_id === (int) $user->location_id;
    }

    public function receive(User $user, Transfer $transfer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $transfer->to_location_id === (int) $user->location_id;
    }

    public function cancel(User $user, Transfer $transfer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($transfer->status === TransferStatus::Recibido) {
            return false;
        }

        return (int) $transfer->from_location_id === (int) $user->location_id
            || (int) $transfer->to_location_id === (int) $user->location_id
            || (int) $transfer->requested_by === (int) $user->id;
    }
}
