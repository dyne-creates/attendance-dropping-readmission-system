<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\ReadmissionRequest;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StudentReadmissionController extends Controller
{
    public function create(Request $request, Enrollment $enrollment): View|RedirectResponse
    {
        $student = $request->user()->student;

        abort_unless(
            $student !== null
                && $enrollment->student_id === $student->student_id
                && $enrollment->status === 'dropped',
            404,
        );

        $drop = $enrollment->droppingTransactions()
            ->with(['readmissionRequests' => function (HasMany $query): void {
                $query->orderBy('attempt_number');
            }])
            ->latest('drop_id')
            ->firstOrFail();
        $latestAttempt = $drop->readmissionRequests->last();

        if ($latestAttempt?->outcome === ReadmissionRequest::OUTCOME_REJECTED) {
            return redirect()
                ->route('student.dashboard')
                ->with('info', 'OSA rejected this readmission request for the term. Contact OSA for guidance.');
        }

        if ($latestAttempt !== null && $latestAttempt->status !== 'rejected') {
            return redirect()
                ->route('student.dashboard')
                ->with('info', 'A readmission request is already awaiting review or has been approved.');
        }

        $enrollment->load([
            'course.schoolYear',
            'course.facultyMember',
            'course.schedules',
            'student.guardians',
            'student.user',
            'attendanceRecords',
        ]);

        $absenceDates = $enrollment->attendanceRecords
            ->where('status', 'absent')
            ->sortBy('session_date')
            ->values();

        return view('student.readmission-form', [
            'student' => $student,
            'enrollment' => $enrollment,
            'drop' => $drop,
            'dropCount' => $enrollment->droppingTransactions()->count(),
            'attemptNumber' => ($latestAttempt?->attempt_number ?? 0) + 1,
            'previousAttempts' => $drop->readmissionRequests,
            'absenceDates' => $absenceDates,
            'reasons' => ReadmissionRequest::REASONS,
            'guardian' => $student->guardians->first(),
        ]);
    }

    public function store(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $student = $request->user()->student;

        abort_unless(
            $student !== null
                && $enrollment->student_id === $student->student_id
                && $enrollment->status === 'dropped',
            404,
        );

        $attachment = $request->file('attachment');

        if ($attachment instanceof UploadedFile && ! $attachment->isValid()) {
            $message = match ($attachment->getError()) {
                UPLOAD_ERR_INI_SIZE => 'The image exceeds the server upload limit. Choose a smaller image or increase upload_max_filesize and restart PHP.',
                UPLOAD_ERR_FORM_SIZE => 'The image exceeds the maximum size allowed by this form.',
                UPLOAD_ERR_PARTIAL => 'The image upload was interrupted. Please select it again and retry.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server is missing its temporary upload folder. Please contact the administrator.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded image. Please contact the administrator.',
                UPLOAD_ERR_EXTENSION => 'A server extension stopped the image upload. Please contact the administrator.',
                default => 'The image could not be uploaded. Please select it again and retry.',
            };

            return back()
                ->withErrors(['attachment' => $message])
                ->withInput($request->except('attachment'));
        }

        $validated = $request->validate([
            'reason_code' => ['required', Rule::in(array_keys(ReadmissionRequest::REASONS))],
            'reason_details' => ['nullable', 'string', 'max:2000', 'required_if:reason_code,other'],
            'attachment' => [
                Rule::requiredIf(in_array($request->input('reason_code'), ['dental_medical', 'personal_family'], true)),
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png',
                'extensions:jpg,jpeg,png',
                'max:5120',
            ],
            'consent' => ['accepted'],
        ]);

        $drop = $enrollment->droppingTransactions()
            ->latest('drop_id')
            ->firstOrFail();

        $latestAttempt = $drop->readmissionRequests()->latest('attempt_number')->first();

        if ($latestAttempt?->outcome === ReadmissionRequest::OUTCOME_REJECTED) {
            return redirect()
                ->route('student.dashboard')
                ->with('info', 'OSA rejected this readmission request for the term. Contact OSA for guidance.');
        }

        if ($latestAttempt !== null && $latestAttempt->status !== 'rejected') {
            return redirect()
                ->route('student.dashboard')
                ->with('info', 'A readmission request is already awaiting review or has been approved.');
        }

        $created = DB::transaction(function () use ($request, $validated, $student, $enrollment, $drop): bool {
            $lockedDrop = DroppingTransaction::query()
                ->whereKey($drop->drop_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedEnrollment = Enrollment::query()
                ->whereKey($enrollment->enrollment_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedEnrollment->student_id === $student->student_id
                    && $lockedEnrollment->status === 'dropped'
                    && $lockedDrop->enrollment_id === $lockedEnrollment->enrollment_id,
                404,
            );

            $latestRequest = ReadmissionRequest::query()
                ->where('drop_id', $lockedDrop->drop_id)
                ->lockForUpdate()
                ->orderByDesc('attempt_number')
                ->first();

            if ($latestRequest !== null && (
                $latestRequest->status !== 'rejected'
                || $latestRequest->outcome === ReadmissionRequest::OUTCOME_REJECTED
            )) {
                return false;
            }

            $readmission = ReadmissionRequest::create([
                'drop_id' => $lockedDrop->drop_id,
                'attempt_number' => ($latestRequest?->attempt_number ?? 0) + 1,
                'reason_code' => $validated['reason_code'],
                'reason_details' => $validated['reason_details'] ?? null,
                'policy_acknowledged_at' => now(),
                'channel' => 'online',
            ]);

            if ($request->hasFile('attachment')) {
                $filePath = $request->file('attachment')->store('readmission-documents', 'local');

                if ($filePath === false) {
                    throw new RuntimeException('The supporting document could not be stored.');
                }

                $readmission->excuseDocuments()->create([
                    'document_type' => 'supporting_document',
                    'file_path' => $filePath,
                ]);
            }

            return true;
        });

        if (! $created) {
            return redirect()
                ->route('student.dashboard')
                ->with('info', 'A readmission request has already been submitted for this drop.');
        }

        return redirect()
            ->route('student.dashboard')
            ->with('success', 'Your online readmission request has been submitted to the Office of Student Affairs.');
    }
}
