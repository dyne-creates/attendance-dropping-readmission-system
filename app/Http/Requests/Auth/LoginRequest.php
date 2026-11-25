<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
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
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $username = $this->input('username', $this->input('email'));

        $this->merge(['username' => Str::lower(trim((string) $username))]);
    }

    /** @throws ValidationException */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $username = $this->string('username')->toString();
        $password = $this->string('password')->toString();

        if (Auth::attempt([
            'username' => $username,
            'password' => $password,
        ], $this->boolean('remember'))) {
            RateLimiter::clear($this->throttleKey());

            return;
        }

        if (app()->environment(['local', 'testing'])) {
            $email = $this->resolveLocalEmail($username);

            if ($email === null || ! Auth::attempt([
                'email' => $email,
                'password' => $password,
            ], $this->boolean('remember'))) {
                $this->rejectCredentials();
            }

            $user = Auth::user();
            $dummyAccounts = config('ub.dummy_password_accounts', []);

            if (
                ! $user instanceof User
                || ! in_array($user->role, ['faculty', 'osa_staff', 'admin'], true)
                || ($user->role === 'faculty' && ! in_array($email, $dummyAccounts, true))
            ) {
                Auth::guard('web')->logout();
                $this->rejectCredentials();
            }
        } else {
            $this->rejectCredentials();
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function resolveLocalEmail(string $username): ?string
    {
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return $username;
        }

        $matches = array_values(array_filter(
            config('ub.dummy_password_accounts', []),
            static fn (string $email): bool => Str::before($email, '@') === $username
        ));

        return count($matches) === 1 ? $matches[0] : null;
    }

    /** @throws ValidationException */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
                'minutes' => ceil(RateLimiter::availableIn($this->throttleKey()) / 60),
            ]),
        ]);
    }

    private function rejectCredentials(): never
    {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages(['username' => trans('auth.failed')]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')).'|'.$this->ip());
    }
}
