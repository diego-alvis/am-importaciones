<?php

namespace App\Http\Requests\Transfer;

use App\Models\Transfer;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Transfer::class);
    }

    public function rules(): array
    {
        $isStoreUser = $this->user()->isVendedor() || $this->user()->isEncargado();

        return [
            'from_location_id' => $isStoreUser ? 'nullable|exists:locations,id' : 'nullable|exists:locations,id',
            'to_location_id' => $isStoreUser ? 'nullable|exists:locations,id' : 'required|exists:locations,id|different:from_location_id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
