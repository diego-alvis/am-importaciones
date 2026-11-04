<?php

namespace App\Http\Requests\Inventory;

use App\Enums\StockAdjustmentType;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = Product::findOrFail($this->route('id'));

        return $this->user()->can('adjustStock', $product);
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'exists:locations,id'],
            'operation' => ['required', Rule::enum(StockAdjustmentType::class)],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
