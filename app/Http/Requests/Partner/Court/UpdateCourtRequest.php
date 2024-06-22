<?php

namespace App\Http\Requests\Partner\Court;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourtRequest extends FormRequest
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
        $id = $this->request->get('id');
        return [
            'name' => 'required|string|unique:courts,name,'.$id.',id',
            'id' => 'required|numeric',
            'description' => 'nullable',
            'status' => 'required'
        ];
    }
}
