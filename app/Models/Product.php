<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'base_price',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
        ];
    }

    protected $appends = [
        'requires_device_model',
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
        $prodName = strtolower($this->name ?? '');

        return str_contains($catName, 'carcasa')
            || str_contains($catName, 'case')
            || str_contains($catName, 'funda')
            || str_contains($catName, 'mica')
            || str_contains($catName, 'vidrio')
            || str_contains($catName, 'templado')
            || str_contains($prodName, 'carcasa')
            || str_contains($prodName, 'case')
            || str_contains($prodName, 'funda')
            || str_contains($prodName, 'mica')
            || str_contains($prodName, 'vidrio')
            || str_contains($prodName, 'templado');
    }

    public function getRequiresDeviceModelAttribute(): bool
    {
        return $this->requiresDeviceModel();
    }
}
