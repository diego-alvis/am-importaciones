<?php

namespace App\Http\Requests\Cash;

use App\Models\CashSession;
use Illuminate\Foundation\Http\FormRequest;

class OpenCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('open', CashSession::class);
    }

    public function rules(): array
    {
        return [
            'opening_amount' => 'required|numeric|min:0',
            'stand_id' => 'nullable|exists:stands,id',
            'operator_name' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'operator_name.required' => 'Por favor, ingresa tu nombre y apellido para el turno de caja.',
        ];
    }
}
