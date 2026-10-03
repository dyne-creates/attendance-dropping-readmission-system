<?php

use App\Http\Middleware\StudentMiddleware;
use App\Http\Middleware\FacultyMiddleware;
use App\Http\Middleware\StaffMiddleware;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'prevent-back-history' => PreventBackHistory::class,
            'facultyMiddleware' => FacultyMiddleware::class,
            'staffMiddleware' => StaffMiddleware::class,
            'studentMiddleware' => StudentMiddleware::class,
        ]);

        $middleware->web(append: [
            PreventBackHistory::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request) {
            return match ($request->user()?->role) {
                'faculty' => route('faculty.dashboard'),
                'osa_staff' => route('osa.dashboard'),
                'student' => route('student.dashboard'),
                default => route('dashboard'),
            };
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );
    })->create();
