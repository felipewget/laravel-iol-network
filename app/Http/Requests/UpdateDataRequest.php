<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDataRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'sender' => 'required|string',
            'data_id' => 'required|string',
            'data' => 'required|string',
            'tip' => 'numeric|min:0.0000000001',
            'observation' => 'string|max:300',
            'timestamp' => 'required',
            'signature' => 'required|string|min:5',
        ];
    }
}
