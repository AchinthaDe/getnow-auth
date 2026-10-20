<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Concerns\ValidatesRegistration;
use App\Domain\DTO\CreateUserDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

final class RegisterRequest extends FormRequest
{
    use ValidatesRegistration;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace($this->normalizeRegistration($this->all()));
    }

    /** @return array<string, array<int, Unique|Password|ValidationRule|array<mixed>|string>> */
    public function rules(): array
    {
        return $this->registrationRules();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->registrationMessages();
    }

    public function toDTO(): CreateUserDTO
    {
        return CreateUserDTO::fromArray($this->validated());
    }
}
