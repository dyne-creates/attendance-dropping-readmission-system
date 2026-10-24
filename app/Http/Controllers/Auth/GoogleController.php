<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Support\UbAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;
use Throwable;

class GoogleController extends Controller
{
    private const INTENT_KEY = 'google_auth_intent';

    public function redirect(Request $request): RedirectResponse
    {
        $intent = $request->query('intent') === 'signup' ? 'signup' : 'login';
        $request->session()->put(self::INTENT_KEY, $intent);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $intent = $request->session()->pull(self::INTENT_KEY, 'login');
        $returnRoute = $intent === 'signup' ? 'register' : 'login';

        if ($request->filled('error')) {
            return redirect()->route($returnRoute)->withErrors([
                'google' => 'Google sign-in was canceled. You can try again.',
            ]);
        }

        try {
            $googleAccount = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route($returnRoute)->withErrors([
                'google' => 'Google sign-in could not be completed. Please try again.',
            ]);
        }

        $email = Str::lower(trim((string) $googleAccount->getEmail()));
        $googleId = trim((string) $googleAccount->getId());

        if ($email === '' || $googleId === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->rejectGoogleAccount($returnRoute, 'Google did not provide a usable account identity. Please try again.');
        }

        if (! filter_var($googleAccount->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return $this->rejectGoogleAccount($returnRoute, 'Your Google email must be verified before you can continue.');
        }

        $ubAccount = UbAccount::resolve($email);

        if ($ubAccount === null) {
            return $this->rejectGoogleAccount($returnRoute, $this->invalidEmailMessage($email));
        }

        if ($intent === 'signup') {
            return $this->signUp($request, $googleAccount, $email, $googleId, $ubAccount);
        }

        return $this->signIn($request, $googleAccount, $email, $googleId, $ubAccount);
    }

    /** @param array{role: string, student_number: ?string} $ubAccount */
    private function signUp(Request $request, object $googleAccount, string $email, string $googleId, array $ubAccount): RedirectResponse
    {
        $accountAlreadyExists = User::query()
            ->where('google_id', $googleId)
            ->orWhereRaw('LOWER(email) = ?', [$email])
            ->exists();

        if ($accountAlreadyExists) {
            return redirect()->route('login')->with(
                'status',
                'An account already exists for this University email. Please sign in with Google instead. Student accounts may already have been created from a class list.'
            );
        }

        [$name, $firstName, $lastName] = $this->accountNames($googleAccount, $email);
        $avatarUrl = $googleAccount->getAvatar();

        try {
            $user = DB::transaction(function () use ($email, $googleId, $name, $firstName, $lastName, $avatarUrl, $ubAccount): ?User {
                $student = null;

                if ($ubAccount['role'] === 'student') {
                    $student = Student::query()
                        ->where('student_number', $ubAccount['student_number'])
                        ->lockForUpdate()
                        ->first();

                    if ($student !== null && $student->user_id !== null) {
                        return null;
                    }
                }

                $user = User::query()->create([
                    'name' => $name,
                    'role' => $ubAccount['role'],
                    'email' => $email,
                    'google_id' => $googleId,
                    'avatar_url' => $avatarUrl,
                    'email_verified_at' => now(),
                    'password' => null,
                ]);

                if ($ubAccount['role'] === 'student') {
                    if ($student === null) {
                        $user->student()->create([
                            'student_number' => $ubAccount['student_number'],
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                        ]);
                    } else {
                        $student->forceFill([
                            'user_id' => $user->user_id,
                            'first_name' => trim((string) $student->first_name) === '' ? $firstName : $student->first_name,
                            'last_name' => trim((string) $student->last_name) === '' ? $lastName : $student->last_name,
                        ])->save();
                    }
                } elseif ($ubAccount['role'] === 'faculty') {
                    $user->facultyMember()->create(['full_name' => $name]);
                } else {
                    $user->osaStaff()->create();
                }

                return $user;
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('register')->withErrors([
                'google' => 'Your account could not be created right now. Please try again or contact support.',
            ]);
        }

        if ($user === null) {
            report(new RuntimeException('Google signup was rejected because the student record is already linked to another user.'));

            return redirect()->route('register')->withErrors([
                'google' => 'This student ID is already linked to another account. Please contact support.',
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route($this->dashboardRoute($user->role));
    }

    /** @param array{role: string, student_number: ?string} $ubAccount */
    private function signIn(Request $request, object $googleAccount, string $email, string $googleId, array $ubAccount): RedirectResponse
    {
        $accountByGoogleId = User::query()
            ->with(['student', 'facultyMember', 'osaStaff'])
            ->where('google_id', $googleId)
            ->first();
        $accountByEmail = User::query()
            ->with(['student', 'facultyMember', 'osaStaff'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (
            ($accountByGoogleId !== null && Str::lower($accountByGoogleId->email) !== $email)
            || ($accountByGoogleId !== null && $accountByEmail !== null && $accountByGoogleId->user_id !== $accountByEmail->user_id)
        ) {
            return $this->rejectGoogleAccount('login', 'This Google account is linked to a different University account.');
        }

        $user = $accountByGoogleId ?? $accountByEmail;

        if (! $user instanceof User) {
            return redirect()->route('login')->withErrors([
                'google' => "No account was found for {$email}. Please sign up first.",
            ]);
        }

        if ($user->role !== $ubAccount['role'] || ! $this->hasRoleProfile($user)) {
            return $this->rejectGoogleAccount('login', 'This University account is not configured for the account type assigned to its email. Please contact support.');
        }

        if ($user->google_id !== null && $user->google_id !== $googleId) {
            return $this->rejectGoogleAccount('login', 'This email is already linked to a different Google account.');
        }

        $avatarUrl = $googleAccount->getAvatar();
        $user->forceFill([
            'google_id' => $googleId,
            'avatar_url' => $avatarUrl ?: $user->avatar_url,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route($this->dashboardRoute($user->role), absolute: false));
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function accountNames(object $googleAccount, string $email): array
    {
        $raw = $googleAccount->getRaw();
        $fullName = trim((string) ($googleAccount->getName() ?? ''));

        if ($fullName === '') {
            $fullName = Str::before($email, '@');
        }

        $nameParts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lastNameFromFullName = count($nameParts) > 1 ? (string) array_pop($nameParts) : ($nameParts[0] ?? $fullName);
        $firstNameFromFullName = count($nameParts) > 0 ? implode(' ', $nameParts) : $lastNameFromFullName;
        $firstName = trim((string) ($raw['given_name'] ?? '')) ?: $firstNameFromFullName;
        $lastName = trim((string) ($raw['family_name'] ?? '')) ?: $lastNameFromFullName;
        $name = trim((string) ($googleAccount->getName() ?? '')) ?: trim($firstName.' '.$lastName);

        return [$name, $firstName, $lastName];
    }

    private function hasRoleProfile(User $user): bool
    {
        return match ($user->role) {
            'student' => $user->student !== null,
            'faculty' => $user->facultyMember !== null,
            'osa_staff' => $user->osaStaff !== null,
            default => false,
        };
    }

    private function dashboardRoute(string $role): string
    {
        return match ($role) {
            'student' => 'student.dashboard',
            'faculty' => 'faculty.dashboard',
            'osa_staff' => 'osa.dashboard',
        };
    }

    private function invalidEmailMessage(string $email): string
    {
        if (Str::endsWith($email, '@'.config('ub.student_domain'))) {
            return 'Student accounts must use their valid 8-digit student ID before @s.ubaguio.edu.';
        }

        return 'Only University of Baguio accounts are allowed.';
    }

    private function rejectGoogleAccount(string $route, string $message): RedirectResponse
    {
        return redirect()->route($route)->withErrors(['google' => $message]);
    }
}
