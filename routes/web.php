<?php

use App\Http\Controllers\Faculty\FacultyController;
use App\Http\Controllers\OsaStaff\OsaStaffController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->middleware('prevent-back-history');

Route::middleware(['auth', 'prevent-back-history'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return match ($request->user()->role) {
            'student' => redirect()->route('student.dashboard'),
            'faculty' => redirect()->route('faculty.dashboard'),
            'osa_staff' => redirect()->route('osa.dashboard'),
            default => abort(403),
        };
    })->name('dashboard');

    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])
        ->middleware('studentMiddleware')
        ->name('student.dashboard');

    Route::get('/faculty/dashboard', [FacultyController::class, 'dashboard'])
        ->middleware('facultyMiddleware')
        ->name('faculty.dashboard');

    Route::post('/faculty/attendance', [FacultyController::class, 'storeAttendance'])
        ->middleware('facultyMiddleware')
        ->name('faculty.attendance.store');

    Route::post('/faculty/enrollments/{enrollment}/drop', [FacultyController::class, 'dropStudent'])
        ->middleware('facultyMiddleware')
        ->name('faculty.enrollments.drop');

    Route::post('/faculty/toggle-schedule-warning', [FacultyController::class, 'toggleScheduleWarning'])
        ->middleware('facultyMiddleware')
        ->name('faculty.toggle-schedule-warning');

    Route::get('/faculty/enrollments/{enrollment}/attendance-history', [FacultyController::class, 'studentAttendanceHistory'])
        ->middleware('facultyMiddleware')
        ->name('faculty.enrollments.history');

    Route::get('/osa-staff/dashboard', [OsaStaffController::class, 'dashboard'])
        ->middleware('staffMiddleware')
        ->name('osa.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
