<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\DTO\LoginDTO;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $identifier = $this->input('email');

        if (is_string($identifier)) {
            $this->merge(['email' => mb_strtolower(trim($identifier))]);
        }
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function toDTO(): LoginDTO
    {
        return new LoginDTO($this->string('email')->toString(), $this->string('password')->toString());
    }
}
