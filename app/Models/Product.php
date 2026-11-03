<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'base_price',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function requiresDeviceModel(): bool
    {
        $catName = strtolower($this->category?->name ?? '');
        return str_contains($catName, 'carcasa') 
            || str_contains($catName, 'case') 
            || str_contains($catName, 'funda')
            || str_contains($catName, 'mica') 
            || str_contains($catName, 'vidrio') 
            || str_contains($catName, 'templado');
    }
}