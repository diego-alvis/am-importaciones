<?php

namespace App\Http\Requests\Pos;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Sale::class);
    }

    public function rules(): array
    {
        return [
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.price' => 'nullable|numeric|min:0',
            'cart.*.unit_discount' => 'nullable|numeric|min:0',
            'cart.*.device_model' => 'nullable|string|max:100',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'cash_tendered' => 'nullable|numeric|min:0',
            'transfer_tendered' => 'nullable|numeric|min:0',
            'change' => 'nullable|numeric|min:0',
            'reference_code' => 'nullable|string|max:100',
        ];
    }
}
