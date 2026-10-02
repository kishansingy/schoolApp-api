<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already guarded by auth:sanctum
    }

    public function rules(): array
    {
        return [
            'amount'     => 'required|numeric|min:1',
            'student_id' => 'required',
            'fee_ids'    => 'required|array|min:1',
            'fee_ids.*'  => 'required',
            'notes'      => 'nullable|array',
        ];
    }
}
