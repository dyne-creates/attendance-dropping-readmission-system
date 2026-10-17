<?php

namespace App\Http\Controllers\OsaStaff;

use App\Http\Controllers\Controller;
use App\Mail\ReadmissionDecisionMail;
use App\Models\ExcuseDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OsaStaffController extends Controller
{
    public function dashboard()
    {
        $pendingReadmissions = DB::table('readmission_requests')
            ->where('status', 'pending')
            ->count();

        $approvedReadmissions = DB::table('readmission_requests')
            ->where('status', 'approved')
            ->count();

        $rejectedReadmissions = DB::table('readmission_requests')
            ->where('status', 'rejected')
            ->count();

        $totalDrops = DB::table('dropping_transactions')
            ->count();

        $recentReadmissions = DB::table('readmission_requests')
            ->join(
                'dropping_transactions',
                'readmission_requests.drop_id',
                '=',
                'dropping_transactions.drop_id'
            )
            ->join(
                'enrollments',
                'dropping_transactions.enrollment_id',
                '=',
                'enrollments.enrollment_id'
            )
            ->join(
                'students',
                'enrollments.student_id',
                '=',
                'students.student_id'
            )
            ->join(
                'courses',
                'enrollments.course_id',
                '=',
                'courses.course_id'
            )
            ->select(
                'readmission_requests.*',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'courses.subject_code',
                'courses.course_name'
            )
            ->latest('readmission_requests.readmission_id')
            ->limit(10)
            ->get();

        return view('osa-staff.dashboard', compact(
            'pendingReadmissions',
            'approvedReadmissions',
            'rejectedReadmissions',
            'totalDrops',
            'recentReadmissions'
        ));
    }

    public function readmissions()
    {
        $requests = DB::table('readmission_requests')
            ->join(
                'dropping_transactions',
                'readmission_requests.drop_id',
                '=',
                'dropping_transactions.drop_id'
            )
            ->join(
                'enrollments',
                'dropping_transactions.enrollment_id',
                '=',
                'enrollments.enrollment_id'
            )
            ->join(
                'students',
                'enrollments.student_id',
                '=',
                'students.student_id'
            )
            ->join(
                'courses',
                'enrollments.course_id',
                '=',
                'courses.course_id'
            )
            ->join(
                'faculty_members',
                'dropping_transactions.dropped_by',
                '=',
                'faculty_members.faculty_id'
            )
            ->where('readmission_requests.status', 'pending')
            ->select(
                'readmission_requests.*',
                'students.student_id',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'courses.course_id',
                'courses.subject_code',
                'courses.course_name',
                'courses.section',
                'faculty_members.faculty_id',
                'faculty_members.full_name as faculty_name'
            )
            ->orderByDesc('readmission_requests.readmission_id')
            ->get();

        return view('osa-staff.readmissions', compact('requests'));
    }

    public function showReadmission($id)
    {
        $request = DB::table('readmission_requests')
            ->join(
                'dropping_transactions',
                'readmission_requests.drop_id',
                '=',
                'dropping_transactions.drop_id'
            )
            ->join(
                'enrollments',
                'dropping_transactions.enrollment_id',
                '=',
                'enrollments.enrollment_id'
            )
            ->join(
                'students',
                'enrollments.student_id',
                '=',
                'students.student_id'
            )
            ->join(
                'courses',
                'enrollments.course_id',
                '=',
                'courses.course_id'
            )
            ->join(
                'faculty_members',
                'dropping_transactions.dropped_by',
                '=',
                'faculty_members.faculty_id'
            )
            ->where(
                'readmission_requests.readmission_id',
                $id
            )
            ->select(
                'readmission_requests.*',
                'dropping_transactions.drop_id',
                'dropping_transactions.drop_date',
                'dropping_transactions.absence_hours_at_drop',
                'enrollments.enrollment_id',
                'students.student_id',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.program',
                'students.year_level',
                'courses.subject_code',
                'courses.course_name',
                'courses.section',
                'faculty_members.faculty_id',
                'faculty_members.full_name as faculty_name'
            )
            ->first();

        if (! $request) {
            abort(404);
        }

        $documents = DB::table('excuse_documents')
            ->where('readmission_id', $id)
            ->get();

        $guardian = DB::table('guardians')
            ->where('student_id', $request->student_id)
            ->orderBy('guardian_id')
            ->first();

        $absenceDates = DB::table('attendance_records')
            ->where('enrollment_id', $request->enrollment_id)
            ->where('status', 'absent')
            ->whereNotNull('grading_period')
            ->orderBy('session_date')
            ->get()
            ->groupBy('grading_period');

        return view(
            'osa-staff.readmission-details',
            compact('request', 'documents', 'guardian', 'absenceDates')
        );
    }

    public function downloadExcuseDocument(ExcuseDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path);
    }

    public function previewExcuseDocument(ExcuseDocument $document): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return response()->file(Storage::disk('local')->path($document->file_path), [
            'Content-Type' => Storage::disk('local')->mimeType($document->file_path) ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approveReadmission(Request $request, int $id)
    {
        DB::beginTransaction();

        try {
            $osaStaff = $request->user()->osaStaff()->firstOrCreate();

            $readmission = DB::table('readmission_requests')
                ->join(
                    'dropping_transactions',
                    'readmission_requests.drop_id',
                    '=',
                    'dropping_transactions.drop_id'
                )
                ->join(
                    'enrollments',
                    'dropping_transactions.enrollment_id',
                    '=',
                    'enrollments.enrollment_id'
                )
                ->join(
                    'students',
                    'enrollments.student_id',
                    '=',
                    'students.student_id'
                )
                ->join(
                    'users as student_users',
                    'students.user_id',
                    '=',
                    'student_users.user_id'
                )
                ->join(
                    'faculty_members',
                    'dropping_transactions.dropped_by',
                    '=',
                    'faculty_members.faculty_id'
                )
                ->join(
                    'users as faculty_users',
                    'faculty_members.user_id',
                    '=',
                    'faculty_users.user_id'
                )
                ->where(
                    'readmission_requests.readmission_id',
                    $id
                )
                ->select(
                    'readmission_requests.*',
                    'dropping_transactions.drop_id',
                    'dropping_transactions.dropped_by',
                    'enrollments.enrollment_id',
                    'enrollments.student_id',
                    'student_users.email as student_email',
                    'faculty_users.email as faculty_email',
                    'students.first_name',
                    'students.last_name',
                    'faculty_members.full_name as faculty_name'
                )
                ->first();

            if (! $readmission) {
                DB::rollBack();

                return redirect()
                    ->route('osa.readmissions')
                    ->with('error', 'Readmission request not found.');
            }

            DB::table('readmission_requests')
                ->where('readmission_id', $id)
                ->update([
                    'status' => 'approved',
                    'processed_by' => $osaStaff->getKey(),
                    'processed_date' => now()->toDateString(),
                    'notice_sent_date' => now()->toDateString(),
                ]);

            DB::table('enrollments')
                ->where(
                    'enrollment_id',
                    $readmission->enrollment_id
                )
                ->update([
                    'status' => 'readmitted',
                ]);

            DB::table('notifications')->insert([
                'readmission_id' => $id,
                'osa_id' => $osaStaff->getKey(),
                'student_id' => $readmission->student_id,
                'guardian_id' => null,
                'notification_type' => 'readmission_approved',
                'channel' => 'email',
                'message' => 'Your readmission request has been approved by the OSA.',
                'sent_at' => now(),
            ]);

            DB::table('notifications')->insert([
                'readmission_id' => $id,
                'osa_id' => $osaStaff->getKey(),
                'student_id' => null,
                'guardian_id' => null,
                'notification_type' => 'student_readmitted',
                'channel' => 'email',
                'message' => 'The readmission request of '.
                    $readmission->first_name.
                    ' '.
                    $readmission->last_name.
                    ' has been approved.',
                'sent_at' => now(),
            ]);

            DB::commit();

            try {
                Mail::to($readmission->student_email)
                    ->send(new ReadmissionDecisionMail($readmission, 'approved'));

                Mail::to($readmission->faculty_email)
                    ->send(new ReadmissionDecisionMail($readmission, 'approved_faculty'));
            } catch (\Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('osa.readmissions')
                    ->with('warning', 'The readmission was approved, but an email notification could not be sent.');
            }

            return redirect()
                ->route('osa.readmissions')
                ->with(
                    'success',
                    'Readmission approved. Notifications were sent to the student and faculty.'
                );

        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            report($exception);

            return redirect()
                ->back()
                ->with('error', 'The readmission could not be approved. Please try again.');
        }
    }

    public function rejectReadmission(
        Request $request,
        int $id
    ): RedirectResponse {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::beginTransaction();

        try {
            $osaStaff = $request->user()->osaStaff()->firstOrCreate();

            $readmission = DB::table('readmission_requests')
                ->join(
                    'dropping_transactions',
                    'readmission_requests.drop_id',
                    '=',
                    'dropping_transactions.drop_id'
                )
                ->join(
                    'enrollments',
                    'dropping_transactions.enrollment_id',
                    '=',
                    'enrollments.enrollment_id'
                )
                ->join(
                    'students',
                    'enrollments.student_id',
                    '=',
                    'students.student_id'
                )
                ->join(
                    'users as student_users',
                    'students.user_id',
                    '=',
                    'student_users.user_id'
                )
                ->join(
                    'faculty_members',
                    'dropping_transactions.dropped_by',
                    '=',
                    'faculty_members.faculty_id'
                )
                ->join(
                    'users as faculty_users',
                    'faculty_members.user_id',
                    '=',
                    'faculty_users.user_id'
                )
                ->where(
                    'readmission_requests.readmission_id',
                    $id
                )
                ->select(
                    'readmission_requests.*',
                    'enrollments.enrollment_id',
                    'enrollments.student_id',
                    'student_users.email as student_email',
                    'faculty_users.email as faculty_email',
                    'students.first_name',
                    'students.last_name'
                )
                ->first();

            if (! $readmission) {
                DB::rollBack();

                return redirect()
                    ->route('osa.readmissions')
                    ->with('error', 'Request not found.');
            }

            DB::table('readmission_requests')
                ->where('readmission_id', $id)
                ->update([
                    'status' => 'rejected',
                    'rejection_reason' => $validated['rejection_reason'],
                    'processed_by' => $osaStaff->getKey(),
                    'processed_date' => now()->toDateString(),
                    'notice_sent_date' => now()->toDateString(),
                ]);

            DB::table('notifications')->insert([
                'readmission_id' => $id,
                'osa_id' => $osaStaff->getKey(),
                'student_id' => $readmission->student_id,
                'guardian_id' => null,
                'notification_type' => 'readmission_rejected',
                'channel' => 'email',
                'message' => 'Your readmission request has been rejected. OSA feedback: '.$validated['rejection_reason'],
                'sent_at' => now(),
            ]);

            DB::commit();

            try {
                $readmission->rejection_reason = $validated['rejection_reason'];
                Mail::to($readmission->student_email)
                    ->send(new ReadmissionDecisionMail($readmission, 'rejected'));
            } catch (\Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('osa.readmissions')
                    ->with('warning', 'The readmission was rejected, but the notification email could not be sent.');
            }

            return redirect()
                ->route('osa.readmissions')
                ->with(
                    'success',
                    'Readmission request rejected and student notified.'
                );

        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            report($exception);

            return redirect()
                ->back()
                ->with('error', 'The readmission could not be rejected. Please try again.');
        }
    }

    public function dropArchive()
    {
        $drops = DB::table('dropping_transactions')
            ->join(
                'enrollments',
                'dropping_transactions.enrollment_id',
                '=',
                'enrollments.enrollment_id'
            )
            ->join(
                'students',
                'enrollments.student_id',
                '=',
                'students.student_id'
            )
            ->join(
                'courses',
                'enrollments.course_id',
                '=',
                'courses.course_id'
            )
            ->join(
                'faculty_members',
                'dropping_transactions.dropped_by',
                '=',
                'faculty_members.faculty_id'
            )
            ->select(
                'dropping_transactions.*',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'courses.subject_code',
                'courses.course_name',
                'faculty_members.full_name as faculty_name'
            )
            ->orderByDesc('dropping_transactions.drop_id')
            ->get();

        return view('osa-staff.archive-drops', compact('drops'));
    }

    public function readmissionArchive()
    {
        $records = DB::table('readmission_requests')
            ->join(
                'dropping_transactions',
                'readmission_requests.drop_id',
                '=',
                'dropping_transactions.drop_id'
            )
            ->join(
                'enrollments',
                'dropping_transactions.enrollment_id',
                '=',
                'enrollments.enrollment_id'
            )
            ->join(
                'students',
                'enrollments.student_id',
                '=',
                'students.student_id'
            )
            ->join(
                'courses',
                'enrollments.course_id',
                '=',
                'courses.course_id'
            )
            ->select(
                'readmission_requests.*',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'courses.subject_code',
                'courses.course_name'
            )
            ->whereIn(
                'readmission_requests.status',
                ['approved', 'rejected']
            )
            ->orderByDesc(
                'readmission_requests.readmission_id'
            )
            ->get();

        return view(
            'osa-staff.archive-readmissions',
            compact('records')
        );
    }
}
