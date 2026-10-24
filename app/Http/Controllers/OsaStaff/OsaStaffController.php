<?php

namespace App\Http\Controllers\OsaStaff;

use App\Http\Controllers\Controller;
use App\Mail\ReadmissionDecisionMail;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\ExcuseDocument;
use App\Models\ReadmissionRequest;
use App\Services\AttendanceService;
use App\Services\ReadmissionExportService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
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
            ->where(function ($query): void {
                $query->whereNull('outcome')
                    ->orWhere('outcome', ReadmissionRequest::OUTCOME_APPROVED);
            })
            ->count();

        $excusedReadmissions = DB::table('readmission_requests')
            ->where('outcome', ReadmissionRequest::OUTCOME_EXCUSED)
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
            'excusedReadmissions',
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
                'faculty_members.full_name as faculty_name',
                'dropping_transactions.drop_type',
                'dropping_transactions.absence_hours_at_drop',
                'dropping_transactions.instructor_reason',
            )
            ->orderByDesc('readmission_requests.readmission_id')
            ->get();

        return view('osa-staff.readmissions', compact('requests'));
    }

    public function exportReadmissions(ReadmissionExportService $exportService): BinaryFileResponse
    {
        $rows = $this->readmissionExportQuery()
            ->get()
            ->map(fn (object $record): array => $this->readmissionExportRow($record))
            ->prepend($this->readmissionExportHeaders())
            ->all();

        return $this->downloadReadmissionWorkbook(
            $exportService,
            $rows,
            'readmissions-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportReadmission(int $id, ReadmissionExportService $exportService): BinaryFileResponse
    {
        $record = $this->readmissionExportQuery()
            ->where('readmission_requests.readmission_id', $id)
            ->firstOrFail();

        $filename = 'readmission-'.$record->readmission_id.'.xlsx';

        return $this->downloadReadmissionWorkbook(
            $exportService,
            [$this->readmissionExportHeaders(), $this->readmissionExportRow($record)],
            $filename
        );
    }

    public function showReadmission(int $id)
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
                'dropping_transactions.drop_type',
                'dropping_transactions.instructor_reason',
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
            ->orderBy('session_date')
            ->get();

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

    public function approveReadmission(Request $request, int $id): RedirectResponse
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
                    'courses',
                    'enrollments.course_id',
                    '=',
                    'courses.course_id'
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
                ->where('readmission_requests.status', 'pending')
                ->lockForUpdate()
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
                    'courses.subject_code',
                    'courses.course_name',
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
                    'outcome' => ReadmissionRequest::OUTCOME_APPROVED,
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
                    ->send(new ReadmissionDecisionMail($readmission, 'approved', 'faculty'));
            } catch (\Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('osa.readmissions')
                    ->with('warning', 'The readmission was approved, but one or more email notifications could not be sent.');
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

    public function rejectReadmission(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        return $this->processNegativeDecision(
            $request,
            $id,
            ReadmissionRequest::OUTCOME_REJECTED,
            $validated['rejection_reason'],
        );
    }

    public function notExcusedReadmission(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        return $this->processNegativeDecision(
            $request,
            $id,
            ReadmissionRequest::OUTCOME_NOT_EXCUSED,
            $validated['rejection_reason'],
        );
    }

    public function excuseReadmission(Request $request, int $id, AttendanceService $attendanceService): RedirectResponse
    {
        $dateRangeRule = match ($request->input('duration')) {
            'single' => 'date_equals:excused_from',
            'multiple' => 'after:excused_from',
            default => 'after_or_equal:excused_from',
        };
        $validated = $request->validate([
            'duration' => ['nullable', 'in:single,multiple'],
            'excused_from' => ['required', 'date'],
            'excused_through' => ['required', 'date', $dateRangeRule],
        ]);

        try {
            $readmission = DB::transaction(function () use ($request, $id, $validated, $attendanceService): ?object {
                $osaStaff = $request->user()->osaStaff()->firstOrCreate();
                $readmission = $this->readmissionDecisionDetails($id, true);

                if ($readmission === null || $readmission->status !== 'pending') {
                    return null;
                }

                $drop = DroppingTransaction::query()
                    ->whereKey($readmission->drop_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $enrollment = Enrollment::query()
                    ->with('course.schedules')
                    ->whereKey($readmission->enrollment_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $absences = $enrollment->attendanceRecords()
                    ->where('status', 'absent')
                    ->whereBetween('session_date', [$validated['excused_from'], $validated['excused_through']])
                    ->lockForUpdate()
                    ->get();
                $deductedHours = $attendanceService->calculateAbsenceHours($enrollment->course, $absences);

                $enrollment->attendanceRecords()
                    ->where('status', 'absent')
                    ->whereBetween('session_date', [$validated['excused_from'], $validated['excused_through']])
                    ->update([
                        'status' => 'excused',
                        'hours_absent' => null,
                        'updated_at' => now(),
                    ]);

                $drop->update([
                    'absence_hours_at_drop' => max(0, (float) $drop->absence_hours_at_drop - $deductedHours),
                ]);
                $enrollment->update(['status' => 'readmitted']);

                DB::table('readmission_requests')
                    ->where('readmission_id', $id)
                    ->update([
                        'status' => 'approved',
                        'outcome' => ReadmissionRequest::OUTCOME_EXCUSED,
                        'excused_from' => $validated['excused_from'],
                        'excused_through' => $validated['excused_through'],
                        'excused_hours_deducted' => $deductedHours,
                        'rejection_reason' => null,
                        'processed_by' => $osaStaff->getKey(),
                        'processed_date' => now()->toDateString(),
                        'notice_sent_date' => now()->toDateString(),
                    ]);

                DB::table('notifications')->insert([
                    'readmission_id' => $id,
                    'osa_id' => $osaStaff->getKey(),
                    'student_id' => $readmission->student_id,
                    'guardian_id' => null,
                    'notification_type' => 'readmission_excused',
                    'channel' => 'email',
                    'message' => 'OSA readmitted you and excused absences from '.$validated['excused_from'].' through '.$validated['excused_through'].'. '.$deductedHours.' absence hours were deducted.',
                    'sent_at' => now(),
                ]);

                $readmission->outcome = ReadmissionRequest::OUTCOME_EXCUSED;
                $readmission->excused_from = $validated['excused_from'];
                $readmission->excused_through = $validated['excused_through'];
                $readmission->excused_hours_deducted = $deductedHours;

                return $readmission;
            });

            if ($readmission === null) {
                return redirect()
                    ->route('osa.readmissions')
                    ->with('error', 'The readmission request was not found or has already been processed.');
            }

            try {
                Mail::to($readmission->student_email)
                    ->send(new ReadmissionDecisionMail($readmission, ReadmissionRequest::OUTCOME_EXCUSED));
                Mail::to($readmission->faculty_email)
                    ->send(new ReadmissionDecisionMail($readmission, ReadmissionRequest::OUTCOME_EXCUSED, 'faculty'));
            } catch (\Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('osa.readmissions')
                    ->with('warning', 'The student was readmitted and absences were excused, but one or more email notifications could not be sent.');
            }

            return redirect()
                ->route('osa.readmissions')
                ->with('success', 'The student was readmitted, matching absences were excused, and both the student and faculty were notified.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->with('error', 'The readmission could not be excused. Please try again.');
        }
    }

    private function processNegativeDecision(Request $request, int $id, string $outcome, string $reason): RedirectResponse
    {
        try {
            $readmission = DB::transaction(function () use ($request, $id, $outcome, $reason): ?object {
                $osaStaff = $request->user()->osaStaff()->firstOrCreate();
                $readmission = $this->readmissionDecisionDetails($id, true);

                if ($readmission === null || $readmission->status !== 'pending') {
                    return null;
                }

                DB::table('readmission_requests')
                    ->where('readmission_id', $id)
                    ->update([
                        'status' => 'rejected',
                        'outcome' => $outcome,
                        'rejection_reason' => $reason,
                        'processed_by' => $osaStaff->getKey(),
                        'processed_date' => now()->toDateString(),
                        'notice_sent_date' => now()->toDateString(),
                    ]);

                DB::table('notifications')->insert([
                    'readmission_id' => $id,
                    'osa_id' => $osaStaff->getKey(),
                    'student_id' => $readmission->student_id,
                    'guardian_id' => null,
                    'notification_type' => $outcome === ReadmissionRequest::OUTCOME_NOT_EXCUSED
                        ? 'readmission_not_excused'
                        : 'readmission_rejected',
                    'channel' => 'email',
                    'message' => $outcome === ReadmissionRequest::OUTCOME_NOT_EXCUSED
                        ? 'Your readmission request was not excused. OSA feedback: '.$reason
                        : 'Your readmission request has been rejected. OSA feedback: '.$reason,
                    'sent_at' => now(),
                ]);

                $readmission->outcome = $outcome;
                $readmission->rejection_reason = $reason;

                return $readmission;
            });

            if ($readmission === null) {
                return redirect()
                    ->route('osa.readmissions')
                    ->with('error', 'The readmission request was not found or has already been processed.');
            }

            try {
                Mail::to($readmission->student_email)
                    ->send(new ReadmissionDecisionMail($readmission, $outcome));
                Mail::to($readmission->faculty_email)
                    ->send(new ReadmissionDecisionMail($readmission, $outcome, 'faculty'));
            } catch (\Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('osa.readmissions')
                    ->with('warning', 'The decision was saved, but one or more email notifications could not be sent.');
            }

            $message = $outcome === ReadmissionRequest::OUTCOME_NOT_EXCUSED
                ? 'The request was marked Not excused. The student may submit a corrected request; both parties were notified.'
                : 'The request was rejected for the term. The student remains inactive, and both parties were notified.';

            return redirect()->route('osa.readmissions')->with('success', $message);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->with('error', 'The readmission decision could not be saved. Please try again.');
        }
    }

    private function readmissionDecisionDetails(int $id, bool $lockForUpdate = false): ?object
    {
        $query = DB::table('readmission_requests')
            ->join('dropping_transactions', 'readmission_requests.drop_id', '=', 'dropping_transactions.drop_id')
            ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('users as student_users', 'students.user_id', '=', 'student_users.user_id')
            ->join('faculty_members', 'dropping_transactions.dropped_by', '=', 'faculty_members.faculty_id')
            ->join('users as faculty_users', 'faculty_members.user_id', '=', 'faculty_users.user_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('readmission_requests.readmission_id', $id)
            ->select(
                'readmission_requests.*',
                'dropping_transactions.drop_id',
                'dropping_transactions.absence_hours_at_drop',
                'enrollments.enrollment_id',
                'enrollments.student_id',
                'student_users.email as student_email',
                'faculty_users.email as faculty_email',
                'students.first_name',
                'students.last_name',
                'courses.subject_code',
                'courses.course_name',
                'faculty_members.full_name as faculty_name',
            );

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
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
                'courses.course_name',
                'dropping_transactions.drop_type',
                'dropping_transactions.absence_hours_at_drop',
                'dropping_transactions.instructor_reason',
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

    private function readmissionExportQuery(): Builder
    {
        return DB::table('readmission_requests')
            ->join('dropping_transactions', 'readmission_requests.drop_id', '=', 'dropping_transactions.drop_id')
            ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->select(
                'readmission_requests.*',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'courses.subject_code',
                'courses.course_name',
                'courses.section',
                'dropping_transactions.drop_type',
                'dropping_transactions.absence_hours_at_drop',
                'dropping_transactions.instructor_reason'
            )
            ->orderByDesc('readmission_requests.readmission_id');
    }

    /**
     * @return array<int, string>
     */
    private function readmissionExportHeaders(): array
    {
        return [
            'Readmission ID',
            'Student Number',
            'First Name',
            'Last Name',
            'Subject Code',
            'Course',
            'Section',
            'Status',
            'Outcome',
            'Attempt',
            'Reason',
            'Request Details',
            'Decision Details',
            'Channel',
            'Submitted',
            'Processed',
            'Excused From',
            'Excused Through',
            'Excused Hours Deducted',
            'Drop Type',
            'Absence Hours at Drop',
            'Instructor Reason',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function readmissionExportRow(object $record): array
    {
        return [
            (string) $record->readmission_id,
            (string) $record->student_number,
            (string) $record->first_name,
            (string) $record->last_name,
            (string) $record->subject_code,
            (string) $record->course_name,
            (string) $record->section,
            (string) $record->status,
            (string) ($record->outcome ?? ''),
            (string) $record->attempt_number,
            ReadmissionRequest::reasonLabel((string) $record->reason_code),
            (string) ($record->reason_details ?? ''),
            (string) ($record->rejection_reason ?? ''),
            strtoupper((string) $record->channel),
            $this->formatExportDate($record->created_at),
            $this->formatExportDate($record->processed_date),
            $this->formatExportDate($record->excused_from),
            $this->formatExportDate($record->excused_through),
            (string) ($record->excused_hours_deducted ?? ''),
            (string) ($record->drop_type ?? ''),
            (string) ($record->absence_hours_at_drop ?? ''),
            (string) ($record->instructor_reason ?? ''),
        ];
    }

    private function formatExportDate(?string $date): string
    {
        return $date === null ? '' : Carbon::parse($date)->format('Y-m-d');
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function downloadReadmissionWorkbook(
        ReadmissionExportService $exportService,
        array $rows,
        string $filename
    ): BinaryFileResponse {
        $path = $exportService->createWorkbook($rows);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
