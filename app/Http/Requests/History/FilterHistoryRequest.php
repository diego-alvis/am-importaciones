<?php

namespace App\Http\Requests\History;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'date_range' => ['nullable', 'string', 'in:hoy,ayer,semana,mes,personalizado'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'stand_id' => ['nullable', 'integer', 'exists:stands,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'scope' => ['nullable', 'string', 'in:personal,sede,todos'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
            'status' => ['nullable', 'string', Rule::in(['todos', SaleStatus::Completada->value, SaleStatus::Anulada->value])],
        ];
    }
}
