<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    // ──────────────────────────────────────────
    //  Role-Selector Page
    // ──────────────────────────────────────────

    public function showSelectRole(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user()->role);
        }

        return view('auth.select-role');
    }

    // ──────────────────────────────────────────
    //  Student Login
    // ──────────────────────────────────────────

    public function showStudentLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user()->role);
        }

        return view('auth.login-student');
    }

    public function studentLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'regex:/@s\.ubaguio\.edu$/i'],
            'password' => ['required', 'string'],
        ], [
            'email.regex' => 'Student email must end with @s.ubaguio.edu.',
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password'], 'role' => 'student'], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard.student');
    }

    // ──────────────────────────────────────────
    //  Faculty Login
    // ──────────────────────────────────────────

    public function showFacultyLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user()->role);
        }

        return view('auth.login-faculty');
    }

    public function facultyLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'regex:/@e\.ubaguio\.edu$/i'],
            'password' => ['required', 'string'],
        ], [
            'email.regex' => 'Faculty email must end with @e.ubaguio.edu.',
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password'], 'role' => 'faculty'], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard.faculty');
    }

    // ──────────────────────────────────────────
    //  OSA Staff Login
    // ──────────────────────────────────────────

    public function showOsaLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user()->role);
        }

        return view('auth.login-osa');
    }

    public function osaLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password, 'role' => 'osa_staff'], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard.osa_staff');
    }

    // ──────────────────────────────────────────
    //  Logout (shared)
    // ──────────────────────────────────────────

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.select');
    }

    // ──────────────────────────────────────────
    //  Helper
    // ──────────────────────────────────────────

    private function redirectToDashboard(string $role): RedirectResponse
    {
        return match ($role) {
            'student' => redirect()->route('dashboard.student'),
            'faculty' => redirect()->route('dashboard.faculty'),
            'osa_staff' => redirect()->route('dashboard.osa_staff'),
            default => redirect()->route('login.select'),
        };
    }
}
