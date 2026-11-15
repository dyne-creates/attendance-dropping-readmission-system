<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): never
    {
        app(AuditLogger::class)->record('auth.password_login.invalid_input', result: 'failure');

        parent::failedValidation($validator);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    /** @throws ValidationException */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = $this->string('email')->toString();
        $dummyAccounts = config('ub.dummy_password_accounts', []);

        if (! app()->environment(['local', 'testing']) || ! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->rejectCredentials();
        }

        $user = Auth::user();

        if (
            ! $user instanceof User
            || ! in_array($user->role, ['faculty', 'osa_staff', 'admin'], true)
            || ($user->role === 'faculty' && ! in_array($email, $dummyAccounts, true))
        ) {
            Auth::guard('web')->logout();
            $this->rejectCredentials();
        }

        RateLimiter::clear($this->throttleKey());
    }

    /** @throws ValidationException */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        app(AuditLogger::class)->record('auth.password_login.locked_out', result: 'failure');
        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
                'minutes' => ceil(RateLimiter::availableIn($this->throttleKey()) / 60),
            ]),
        ]);
    }

    private function rejectCredentials(): never
    {
        app(AuditLogger::class)->record('auth.password_login.failed', result: 'failure');
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages(['email' => trans('auth.failed')]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
