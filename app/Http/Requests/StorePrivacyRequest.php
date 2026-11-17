<?php

namespace App\Http\Requests;

use App\Models\PrivacyRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(PrivacyRequest::TYPES))],
            'details' => ['required', 'string', 'min:10', 'max:4000'],
        ];
    }
}
