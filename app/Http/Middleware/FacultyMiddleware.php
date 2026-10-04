<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FacultyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'faculty') {
            return redirect()->route(match ($request->user()?->role) {
                'student' => 'student.dashboard',
                'osa_staff' => 'osa.dashboard',
                default => 'profile.edit',
            });
        }

        return $next($request);
    }
}
