<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', Rule::in(['student', 'faculty', 'osa_staff'])],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:'.User::class,
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $domain = $request->input('role') === 'student' ? 's.ubaguio.edu' : 'e.ubaguio.edu';
                    $emailDomain = strtolower((string) substr(strrchr((string) $value, '@') ?: '', 1));

                    if ($emailDomain !== $domain) {
                        $fail($request->input('role') === 'student'
                            ? 'Students must register with an @s.ubaguio.edu email address.'
                            : 'Faculty and OSA staff must register with an @e.ubaguio.edu email address.');
                    }
                },
            ],
            'student_number' => [
                'required_if:role,student',
                'nullable',
                'string',
                'max:50',
                Rule::unique(Student::class, 'student_number'),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'role' => $validated['role'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            if ($user->role === 'student') {
                $nameParts = preg_split('/\s+/', trim($user->name), 2) ?: [];

                $user->student()->create([
                    'student_number' => $validated['student_number'],
                    'first_name' => $nameParts[0] ?? $user->name,
                    'last_name' => $nameParts[1] ?? $nameParts[0] ?? $user->name,
                ]);
            } elseif ($user->role === 'faculty') {
                $user->facultyMember()->create([
                    'full_name' => $user->name,
                ]);
            } else {
                $user->osaStaff()->create();
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
