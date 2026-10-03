<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->getKey()),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $domain = $this->user()->role === 'student' ? 's.ubaguio.edu' : 'e.ubaguio.edu';
                    $emailDomain = strtolower((string) substr(strrchr((string) $value, '@') ?: '', 1));

                    if ($emailDomain !== $domain) {
                        $fail('Use the University of Baguio email domain assigned to your account type.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
