<?php

namespace App\Http\Requests\Inventory;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = Product::findOrFail($this->route('id'));

        return $this->user()->can('update', $product);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($this->route('id'))->whereNull('deleted_at'),
            ],
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
        ];
    }
}
