<?php

namespace App\Http\Requests\Admin\CourtType;

use Illuminate\Foundation\Http\FormRequest;

class GetCourtTypeRequest extends FormRequest
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
            'page_size'=>'required|integer',
            'current_page'=>'required|integer',
            'search'=>'nullable',
        ];
    }
}
