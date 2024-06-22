<?php

namespace App\Http\Requests\Partner\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
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
            'cart' => 'required',
            'payment_type' => 'required',
            'name'=> 'required|max:255',
            'phone'=>'required|numeric|min:11',
            'discount'=>'nullable',
            'payment_method' => 'nullable',
            'down_payment_amount'=>'required',
            'total_price'=>'required',
        ];
    }
}
