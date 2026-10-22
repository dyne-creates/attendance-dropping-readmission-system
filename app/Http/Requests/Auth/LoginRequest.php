<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\UbAccount;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
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
        $ubAccount = UbAccount::resolve($email);
        $dummyAccounts = config('ub.dummy_password_accounts', []);

        if (
            ! app()->environment(['local', 'testing'])
            || ! in_array($email, $dummyAccounts, true)
            || ! in_array($ubAccount['role'] ?? null, ['faculty', 'osa_staff'], true)
            || ! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))
        ) {
            $this->rejectCredentials();
        }

        $user = Auth::user();

        if (! $user instanceof User || $user->role !== $ubAccount['role']) {
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
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages(['email' => trans('auth.failed')]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
