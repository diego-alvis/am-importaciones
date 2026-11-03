<?php

namespace App\Models;

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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEncargado(): bool
    {
        return $this->role === 'encargado';
    }

    public function isVendedor(): bool
    {
        return $this->role === 'vendedor';
    }

    public function isAlmacenero(): bool
    {
        return $this->role === 'almacenero';
    }

    public function isAlmacen(): bool
    {
        return $this->role === 'almacenero' || ($this->location && $this->location->type === 'almacen');
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

    public function getAvailableStands(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->isAdmin()) {
            return Stand::with('location')->orderBy('location_id')->orderBy('name')->get();
        }
        if ($this->location_id) {
            return Stand::with('location')->where('location_id', $this->location_id)->orderBy('name')->get();
        }
        return Stand::none();
    }
}