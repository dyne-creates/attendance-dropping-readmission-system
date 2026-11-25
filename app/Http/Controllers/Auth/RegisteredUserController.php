<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\Student;
use App\Models\User;
use App\Support\UbAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $account = UbAccount::resolve($validated['email']);

        if ($account === null) {
            throw ValidationException::withMessages([
                'email' => 'Use a valid University of Baguio student or employee email address.',
            ]);
        }

        [$firstName, $lastName] = $this->splitName($validated['name']);

        if (Str::length($firstName) > 100 || Str::length($lastName) > 100) {
            throw ValidationException::withMessages([
                'name' => 'First and last names must each be 100 characters or fewer.',
            ]);
        }

        $user = DB::transaction(function () use ($validated, $account, $firstName, $lastName): User {
            $student = null;

            if ($account['role'] === 'student') {
                $student = Student::query()
                    ->where('student_number', $account['student_number'])
                    ->lockForUpdate()
                    ->first();

                if ($student !== null && $student->user_id !== null) {
                    throw ValidationException::withMessages([
                        'email' => 'This student ID is already linked to an account. Please sign in or contact support.',
                    ]);
                }
            }

            $user = User::query()->create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'role' => $account['role'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            if ($account['role'] === 'student') {
                if ($student === null) {
                    $user->student()->create([
                        'student_number' => $account['student_number'],
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
            } elseif ($account['role'] === 'faculty') {
                $user->facultyMember()->create(['full_name' => $validated['name']]);
            } else {
                $user->osaStaff()->create();
            }

            return $user;
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice')
            ->with('status', 'A verification link has been sent to your email address.');
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $name): array
    {
        $nameParts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lastName = (string) array_pop($nameParts);
        $firstName = $nameParts === [] ? $lastName : implode(' ', $nameParts);

        return [$firstName, $lastName];
    }
}
