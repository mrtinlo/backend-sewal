<?php

namespace App\Http\Requests\Admin\CourtPartner;

use Illuminate\Foundation\Http\FormRequest;

class EditCourtPartnerRequest extends FormRequest
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
            'id' => 'required',
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'start_at' => ['required'],
            'end_at' => ['required'],
            'phone' => 'required|string',
            'partner_name' => 'required|string',
            'is_down_payment' => 'required',
            'is_monthly_membership' => 'required',
            'bank_account_name' => 'required',
            'bank_account_number' => 'required',
            'pin' => 'required',
            'down_payment_amount' => 'required',
            'city_id' => 'required',
            'image' => 'image'
        ];
    }
}
