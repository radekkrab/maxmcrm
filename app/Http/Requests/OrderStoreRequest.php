<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer' => ['required', 'string', 'max:255'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'status' => ['required', 'in:active,completed,canceled'],
            'completed_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.count' => ['required', 'integer', 'min:1']
        ];
    }
}
