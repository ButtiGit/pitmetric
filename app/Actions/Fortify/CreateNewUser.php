<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'newsletter_opt_in' => ['nullable', 'boolean'],
            'newsletter_locale' => ['nullable', 'in:en,it'],
        ])->validate();

        $subscribed = filter_var($input['newsletter_opt_in'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'newsletter_subscribed_at' => $subscribed ? now() : null,
            'newsletter_locale' => in_array($input['newsletter_locale'] ?? null, ['en', 'it'], true)
                ? $input['newsletter_locale']
                : 'en',
        ]);
    }
}
