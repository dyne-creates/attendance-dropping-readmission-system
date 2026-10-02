<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\OsaStaffController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────
//  Landing — redirect to role selector
// ──────────────────────────────────────────

Route::get('/', fn () => redirect()->route('login.select'));

// ──────────────────────────────────────────
//  Role Selection & Login Pages
// ──────────────────────────────────────────

Route::get('/login', [LoginController::class, 'showSelectRole'])->name('login.select');

// Student
Route::get('/login/student', [LoginController::class, 'showStudentLogin'])->name('login.student');
Route::post('/login/student', [LoginController::class, 'studentLogin'])->name('login.student.submit');

// Faculty
Route::get('/login/faculty', [LoginController::class, 'showFacultyLogin'])->name('login.faculty');
Route::post('/login/faculty', [LoginController::class, 'facultyLogin'])->name('login.faculty.submit');

// OSA Staff
Route::get('/login/osa', [LoginController::class, 'showOsaLogin'])->name('login.osa');
Route::post('/login/osa', [LoginController::class, 'osaLogin'])->name('login.osa.submit');

// Logout (shared)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ──────────────────────────────────────────
//  Protected Dashboards (auth + role check)
// ──────────────────────────────────────────

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/dashboard/student', [StudentController::class, 'dashboard'])->name('dashboard.student');
});

Route::middleware(['auth', 'role:faculty'])->group(function () {
    Route::get('/dashboard/faculty', [FacultyController::class, 'dashboard'])->name('dashboard.faculty');
});

Route::middleware(['auth', 'role:osa_staff'])->group(function () {
    Route::get('/dashboard/osa', [OsaStaffController::class, 'dashboard'])->name('dashboard.osa_staff');
});
