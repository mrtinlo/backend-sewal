<?php

namespace App\Http\Requests\Partner\Pin;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPinRequest extends FormRequest
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
            'pin' => 'required|numeric|min_digits:4|max_digits:4'
        ];
    }
}
