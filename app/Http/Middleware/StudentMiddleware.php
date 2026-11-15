<?php

namespace App\Http\Middleware;

use App\Models\Guardian;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'student') {
            return redirect()->route(match ($request->user()?->role) {
                'faculty' => 'faculty.dashboard',
                'osa_staff' => 'osa.dashboard',
                'admin' => 'admin.osa-accounts.index',
                default => 'profile.edit',
            });
        }

        $student = $request->user()->student;

        if (
            $student !== null
            && $request->user()->policy_accepted_at !== null
            && ! $request->routeIs('student.dashboard', 'student.guardian-email.store')
            && ! $student->guardians()
                ->get()
                ->contains(fn (Guardian $guardian): bool => filled($guardian->email))
        ) {
            return redirect()->route('student.dashboard')
                ->with('info', 'Add a parent or guardian email before continuing.');
        }

        return $next($request);
    }
}
