<?php

namespace App\Http\Requests\Cash;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()->isAlmacen();
    }

    public function rules(): array
    {
        return [
            'declared_cash' => 'required|numeric|min:0',
        ];
    }
}
