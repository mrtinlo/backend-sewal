<?php

namespace App\Http\Requests\Partner\CourtPrice;

use Illuminate\Foundation\Http\FormRequest;

class AddCourtPriceRequest extends FormRequest
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
            'court_price_id' => 'required|array',
            'price'=> 'required|numeric'
        ];
    }
}
