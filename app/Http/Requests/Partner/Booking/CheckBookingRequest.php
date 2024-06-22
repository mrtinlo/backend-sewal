<?php

namespace App\Http\Requests\Partner\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CheckBookingRequest extends FormRequest
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
            'cart' => 'required|array',
            'is_membership' => 'required',
            'date' => 'required',
            'payment_type' => 'required',
            'discount' => 'numeric'
        ];
    }
}
