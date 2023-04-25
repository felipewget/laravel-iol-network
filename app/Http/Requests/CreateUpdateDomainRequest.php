<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUpdateDomainRequest extends FormRequest
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
            'domain' => 'required|string',
            'extension' => 'required|string',
            'server_addresses' => 'required|array',
            'amount' => 'numeric|min:0',
            'tip' => 'numeric|min:0.0000000001',
            'observation' => 'string|max:300',
            'timestamp' => 'required',
            // 'signature' => 'required|string|min:5',
        ];
    }
}
