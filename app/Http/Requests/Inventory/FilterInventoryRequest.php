<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class FilterInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'stock_status' => ['nullable', 'string', 'in:todos,ok,bajo_stock,sin_stock'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'sort' => ['nullable', 'string', 'in:name_asc,stock_asc,stock_desc,price_desc,price_asc'],
        ];
    }
}
