<?php

namespace App\Http\Requests\Supervisor;

use App\Models\CashSession;
use Illuminate\Foundation\Http\FormRequest;

class ApproveCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = CashSession::with('stand')->findOrFail($this->route('id'));

        return $this->user()->can('approve', $session);
    }

    public function rules(): array
    {
        return [];
    }
}
