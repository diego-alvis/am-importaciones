<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use App\Enums\LocationType;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'location_id',
        'stand_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function stand()
    {
        return $this->belongsTo(Stand::class);
    }

    public function cashSessions()
    {
        return $this->hasMany(CashSession::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function getActiveCashSession(): ?CashSession
    {
        return $this->cashSessions()
            ->with(['stand.location'])
            ->where('status', CashSessionStatus::Abierta)
            ->first();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin->value;
    }

    public function isEncargado(): bool
    {
        return $this->role === UserRole::Encargado->value;
    }

    public function isVendedor(): bool
    {
        return $this->role === UserRole::Vendedor->value;
    }

    public function isAlmacenero(): bool
    {
        return $this->role === UserRole::Almacenero->value;
    }

    public function isAlmacen(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->role === UserRole::Almacenero->value) {
            return true;
        }

        if (! $this->location_id) {
            return false;
        }

        return $this->location && $this->location->type === LocationType::Almacen->value;
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->isVendedor()) {
            $activeSession = $this->getActiveCashSession();
            if ($activeSession && ! empty($activeSession->operator_name)) {
                return $activeSession->operator_name;
            }

            return session('operator_name', $this->name);
        }

        return $this->name;
    }

    public function getActiveStand(): ?Stand
    {
        $activeStandId = session('active_stand_id', $this->stand_id);
        if ($activeStandId) {
            $stand = Stand::find($activeStandId);
            if ($stand) {
                return $stand;
            }
        }

        return $this->stand;
    }

    public function getAvailableStands(): Collection
    {
        if ($this->isAdmin()) {
            return Stand::with('location')->orderBy('location_id')->orderBy('name')->get();
        }
        if ($this->location_id) {
            return Stand::with('location')->where('location_id', $this->location_id)->orderBy('name')->get();
        }

        return new Collection;
    }
}
