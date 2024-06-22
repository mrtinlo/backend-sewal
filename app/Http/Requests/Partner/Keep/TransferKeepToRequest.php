<?php

namespace App\Http\Requests\Partner\Keep;

use Illuminate\Foundation\Http\FormRequest;

class TransferKeepToRequest extends FormRequest
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
            'keep_id'=>'required',
            'name'=>'required|max:255',
            'phone'=>'required|numeric|min:11',
            'payment_type' => 'required',
            'discount' => 'nullable'
        ];
    }
}
