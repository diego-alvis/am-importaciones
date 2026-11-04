<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stand extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'location_id',
        'name',
        'base_cash',
    ];

    protected $casts = [
        'base_cash' => 'decimal:2',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function cashSessions()
    {
        return $this->hasMany(CashSession::class);
    }
}
