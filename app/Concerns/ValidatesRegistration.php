<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

trait ValidatesRegistration
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Shared by browser and API registration so validation stays consistent.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function validateRegistration(array $input): array
    {
        return Validator::make(
            $this->normalizeRegistration($input),
            $this->registrationRules(),
            $this->registrationMessages(),
        )->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function normalizeRegistration(array $input): array
    {
        foreach (['name', 'email', 'username', 'address'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim($input[$field]);

                if (in_array($field, ['email', 'username'], true)) {
                    $input[$field] = Str::lower($input[$field]);
                }
            }
        }

        if (isset($input['mobile_phone']) && is_string($input['mobile_phone'])) {
            $input['mobile_phone'] = preg_replace('/[\s().-]+/u', '', $input['mobile_phone']);
        }

        return $input;
    }

    /** @return array<string, array<int, Unique|Password|ValidationRule|array<mixed>|string>> */
    protected function registrationRules(): array
    {
        return [
            ...$this->profileRules(),
            'username' => [...$this->usernameRules(), 'min:3', 'max:30', 'regex:/\A[a-z0-9_]+\z/'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'mobile_phone' => ['required', 'string', 'regex:/\A\+?[0-9]{7,15}\z/'],
            'password' => $this->passwordRules(),
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }

    /** @return array<string, string> */
    protected function registrationMessages(): array
    {
        return [
            'username.regex' => __('Use only letters, numbers, and underscores for your username.'),
            'mobile_phone.regex' => __('Enter a valid phone number with 7–15 digits and an optional + country code.'),
        ];
    }
}
