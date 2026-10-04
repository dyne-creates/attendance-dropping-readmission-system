<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'osa_staff') {
            return redirect()->route(match ($request->user()?->role) {
                'student' => 'student.dashboard',
                'faculty' => 'faculty.dashboard',
                default => 'profile.edit',
            });
        }

        return $next($request);
    }
}
