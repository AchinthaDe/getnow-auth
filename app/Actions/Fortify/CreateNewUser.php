<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Domain\DTO\CreateUserDTO;
use App\Domain\User\Services\UserService;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private UserService $users) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input  Unvalidated registration request data.
     */
    public function create(array $input): User
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

        $validated = Validator::make($input, [
            ...$this->profileRules(),
            'username' => [...$this->usernameRules(), 'min:3', 'max:30', 'regex:/\A[a-z0-9_]+\z/'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'mobile_phone' => ['required', 'string', 'regex:/\A\+?[0-9]{7,15}\z/'],
            'password' => $this->passwordRules(),
            'password_confirmation' => ['required', 'string', 'same:password'],
        ], [
            'username.regex' => __('Use only letters, numbers, and underscores for your username.'),
            'mobile_phone.regex' => __('Enter a valid phone number with 7–15 digits and an optional + country code.'),
        ])->validate();

        return $this->users->create(CreateUserDTO::fromArray($validated));
    }
}
