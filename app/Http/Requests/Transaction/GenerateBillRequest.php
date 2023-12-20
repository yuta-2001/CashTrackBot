<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBillRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'opponent_id' => ['required', 'integer'],
            'opponent_name' => ['required', 'string'],
            'total_amount' => ['required', 'integer', 'min:0'],
            'borrow_amount' => ['required', 'integer'],
            'lend_amount' => ['required', 'integer'],
        ];
    }
}
