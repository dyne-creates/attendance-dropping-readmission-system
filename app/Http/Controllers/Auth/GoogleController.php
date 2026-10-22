<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;
use Throwable;

class GoogleController extends Controller
{
    private const INVALID_ACCOUNT_MESSAGE = 'This Google account is not eligible to sign in. Use an existing UB student or employee account.';

    /** Redirect the guest to Google for authentication. */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /** Authenticate an existing application account through its verified Google email. */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google sign-in was canceled. You can try again.',
            ]);
        }

        try {
            $googleAccount = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'google' => 'Google sign-in could not be completed. Please try again.',
            ]);
        }

        $email = Str::lower(trim((string) $googleAccount->getEmail()));
        $googleEmailIsVerified = filter_var(
            $googleAccount->getRaw()['email_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (
            ! $googleAccount->getId()
            || $email === ''
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! $googleEmailIsVerified
        ) {
            return $this->redirectForIneligibleAccount();
        }

        $user = User::query()
            ->with(['student', 'facultyMember', 'osaStaff'])
            ->where('email', $email)
            ->first();

        if (! $user instanceof User) {
            return $this->redirectForIneligibleAccount();
        }

        $dashboardRoute = match ($user->role) {
            'student' => Str::endsWith($email, '@s.ubaguio.edu') && $user->student !== null
                ? 'student.dashboard'
                : null,
            'faculty' => Str::endsWith($email, '@e.ubaguio.edu') && $user->facultyMember !== null
                ? 'faculty.dashboard'
                : null,
            'osa_staff' => Str::endsWith($email, '@e.ubaguio.edu') && $user->osaStaff !== null
                ? 'osa.dashboard'
                : null,
            default => null,
        };

        if ($dashboardRoute === null) {
            return $this->redirectForIneligibleAccount();
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route($dashboardRoute);
    }

    private function redirectForIneligibleAccount(): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'google' => self::INVALID_ACCOUNT_MESSAGE,
        ]);
    }
}
