<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = Category::findOrFail($this->route('id'));

        return $this->user()->can('update', $category);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:categories,name,'.$this->route('id'),
        ];
    }
}
