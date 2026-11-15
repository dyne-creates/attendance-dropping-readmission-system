<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /** Allow password login only for local demo accounts. */
    public function store(LoginRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $auditLogger->record(
            'auth.password_login.succeeded',
            'user',
            (int) $request->user()->getAuthIdentifier(),
        );

        $dashboardRoute = match ($request->user()->role) {
            'admin' => 'admin.osa-accounts.index',
            'student' => 'student.dashboard',
            'faculty' => 'faculty.dashboard',
            'osa_staff' => 'osa.dashboard',
        };

        return redirect()->intended(route($dashboardRoute, absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
        $auditLogger->record('auth.logout.succeeded', 'user', $userId, actorId: $userId);

        return redirect()->route('login');
    }
}
