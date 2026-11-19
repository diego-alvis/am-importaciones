<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cash_session_id',
        'user_id',
        'operator_name',
        'customer_id',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function getSellerNameAttribute(): string
    {
        return $this->operator_name ?: ($this->user?->name ?? 'Vendedor');
    }

    /**
     * Aplica el ámbito de visibilidad de ventas según el rol del usuario autenticado.
     */
    public function scopeForUserScope(
        Builder $query,
        User $user,
        ?string $standId = null,
        ?string $locationId = null,
        ?string $userId = null,
        ?string $scope = null
    ): Builder {
        if ($user->isVendedor()) {
            $activeStand = $user->getActiveStand();
            if ($activeStand) {
                return $query->whereHas('cashSession', fn ($q) => $q->where('stand_id', $activeStand->id));
            }

            return $query->where('user_id', $user->id);
        }

        if ($user->isEncargado()) {
            $query->whereHas('cashSession.stand', fn ($q) => $q->where('location_id', $user->location_id));

            if ($scope === 'personal') {
                $query->where('user_id', $user->id);
            } elseif (! empty($userId)) {
                $query->where('user_id', $userId);
            }

            if (! empty($standId)) {
                $query->whereHas('cashSession', fn ($q) => $q->where('stand_id', $standId));
            }

            return $query;
        }

        if (! empty($locationId)) {
            $query->whereHas('cashSession.stand', fn ($q) => $q->where('location_id', $locationId));
        }
        if (! empty($standId)) {
            $query->whereHas('cashSession', fn ($q) => $q->where('stand_id', $standId));
        }
        if (! empty($userId)) {
            $query->where('user_id', $userId);
        }

        return $query;
    }
}
