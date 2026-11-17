<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class RespondToPrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-updates');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['processing', 'completed', 'rejected'])],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ];
    }
}
