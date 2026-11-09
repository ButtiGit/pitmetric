<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMobileActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'installation_id' => ['required', 'uuid'],
            'platform' => ['required', 'in:pwa,android,ios'],
        ];
    }
}
