<?php

namespace App\Http\Requests\StoreSession;

use Illuminate\Foundation\Http\FormRequest;

class SetStandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stand_id' => 'required|exists:stands,id',
            'operator_name' => 'nullable|string|max:100',
        ];
    }
}
