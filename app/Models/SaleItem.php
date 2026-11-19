<?php

namespace App\Models;

use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'device_model',
        'quantity',
        'original_price',
        'unit_discount',
        'discount_amount',
        'unit_price',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'unit_discount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
