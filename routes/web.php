<?php

use App\Http\Controllers\Faculty\FacultyController;
use App\Http\Controllers\Faculty\FacultyCourseController;
use App\Http\Controllers\OsaStaff\OsaStaffController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\StudentReadmissionController;
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
            default => redirect()->route('profile.edit'),
        };
    })->name('dashboard');

    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])
        ->middleware('studentMiddleware')
        ->name('student.dashboard');

    Route::get('/student/enrollments/{enrollment}/readmission-request', [StudentReadmissionController::class, 'create'])
        ->middleware('studentMiddleware')
        ->name('student.readmissions.create');

    Route::post('/student/enrollments/{enrollment}/readmission-request', [StudentReadmissionController::class, 'store'])
        ->middleware('studentMiddleware')
        ->name('student.readmissions.store');

    Route::get('/faculty/dashboard', [FacultyController::class, 'dashboard'])
        ->middleware('facultyMiddleware')
        ->name('faculty.dashboard');

    Route::post('/faculty/attendance/import', [FacultyController::class, 'importAttendance'])
        ->middleware('facultyMiddleware')
        ->name('faculty.attendance.import');

    Route::post('/faculty/attendance', [FacultyController::class, 'storeAttendance'])
        ->middleware('facultyMiddleware')
        ->name('faculty.attendance.store');

    Route::delete('/faculty/attendance/meeting', [FacultyController::class, 'clearMeeting'])
        ->middleware('facultyMiddleware')
        ->name('faculty.attendance.meeting.clear');

    Route::delete('/faculty/attendance/meeting/delete', [FacultyController::class, 'deleteMeeting'])
        ->middleware('facultyMiddleware')
        ->name('faculty.attendance.meeting.delete');

    Route::post('/faculty/attendance/record', [FacultyController::class, 'recordSingleAttendance'])
        ->middleware('facultyMiddleware')
        ->name('faculty.attendance.record');

    Route::post('/faculty/enrollments/{enrollment}/drop', [FacultyController::class, 'dropStudent'])
        ->middleware('facultyMiddleware')
        ->name('faculty.enrollments.drop');

    Route::post('/faculty/toggle-schedule-warning', [FacultyController::class, 'toggleScheduleWarning'])
        ->middleware('facultyMiddleware')
        ->name('faculty.toggle-schedule-warning');

    Route::get('/faculty/enrollments/{enrollment}/attendance-history', [FacultyController::class, 'studentAttendanceHistory'])
        ->middleware('facultyMiddleware')
        ->name('faculty.enrollments.history');

    // Faculty Course CRUD
    Route::middleware('facultyMiddleware')->prefix('faculty/courses')->name('faculty.courses.')->group(function () {
        Route::get('/create', [FacultyCourseController::class, 'create'])->name('create');
        Route::post('/', [FacultyCourseController::class, 'store'])->name('store');
        Route::get('/import', [FacultyCourseController::class, 'importForm'])->name('import');
        Route::post('/import', [FacultyCourseController::class, 'importClassList'])->name('import.store');
        Route::get('/{course}/schedules/create', [FacultyCourseController::class, 'createSchedule'])->name('schedules.create');
        Route::post('/{course}/schedules', [FacultyCourseController::class, 'storeSchedule'])->name('schedules.store');
        Route::get('/{course}/edit', [FacultyCourseController::class, 'edit'])->name('edit');
        Route::put('/{course}', [FacultyCourseController::class, 'update'])->name('update');
        Route::delete('/{course}', [FacultyCourseController::class, 'destroy'])->name('destroy');
    });

    Route::get('/osa-staff/dashboard', [OsaStaffController::class, 'dashboard'])
        ->middleware('staffMiddleware')
        ->name('osa-staff.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

Route::middleware(['auth', 'staffMiddleware'])->prefix('osa')->name('osa.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [
        OsaStaffController::class,
        'dashboard',
    ])->name('dashboard');

    // Readmission requests
    Route::get('/readmissions', [
        OsaStaffController::class,
        'readmissions',
    ])->name('readmissions');

    Route::get('/readmissions/{id}', [
        OsaStaffController::class,
        'showReadmission',
    ])->name('readmissions.show');

    Route::get('/readmissions/documents/{document}', [
        OsaStaffController::class,
        'downloadExcuseDocument',
    ])->name('readmissions.documents.download');

    Route::get('/readmissions/documents/{document}/preview', [
        OsaStaffController::class,
        'previewExcuseDocument',
    ])->name('readmissions.documents.preview');

    // Approve / reject
    Route::post('/readmissions/{id}/approve', [
        OsaStaffController::class,
        'approveReadmission',
    ])->name('readmissions.approve');

    Route::post('/readmissions/{id}/reject', [
        OsaStaffController::class,
        'rejectReadmission',
    ])->name('readmissions.reject');

    // Archives
    Route::get('/archive/drops', [
        OsaStaffController::class,
        'dropArchive',
    ])->name('archive.drops');

    Route::get('/archive/readmissions', [
        OsaStaffController::class,
        'readmissionArchive',
    ])->name('archive.readmissions');
});

require __DIR__.'/auth.php';
