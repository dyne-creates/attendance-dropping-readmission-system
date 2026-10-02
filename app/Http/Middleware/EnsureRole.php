<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * Redirects unauthenticated users to the role selector,
     * and authenticated users whose role does not match to their own dashboard.
     *
     * @param  array<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login.select');
        }

        if (! in_array($request->user()->role, $roles, true)) {
            return redirect()->route('dashboard.'.$request->user()->role);
        }

        return $next($request);
    }
}
