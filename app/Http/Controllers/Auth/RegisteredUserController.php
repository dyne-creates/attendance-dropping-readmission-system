<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $request->validate([
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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'role' => $request->role,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
