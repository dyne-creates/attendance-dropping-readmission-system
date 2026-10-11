<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\ReadmissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class StudentReadmissionController extends Controller
{
    private const GRADING_PERIODS = [
        'first_grading' => 'First Grading',
        'midterms' => 'Midterms',
        'finals' => 'Finals',
    ];

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
            ->with('readmissionRequest')
            ->latest('drop_id')
            ->firstOrFail();

        if ($drop->readmissionRequest !== null) {
            return redirect()
                ->route('student.dashboard')
                ->with('info', 'A readmission request has already been submitted for this drop.');
        }

        $enrollment->load([
            'course.schoolYear',
            'course.facultyMember',
            'course.schedules',
            'student.guardians',
            'student.user',
            'attendanceRecords',
        ]);

        $absenceDates = collect(self::GRADING_PERIODS)
            ->map(function (string $label, string $key) use ($enrollment): array {
                return [
                    'label' => $label,
                    'dates' => $enrollment->attendanceRecords
                        ->where('status', 'absent')
                        ->where('grading_period', $key)
                        ->sortBy('session_date')
                        ->values(),
                ];
            })
            ->values();

        return view('student.readmission-form', [
            'student' => $student,
            'enrollment' => $enrollment,
            'drop' => $drop,
            'dropCount' => $enrollment->droppingTransactions()->count(),
            'absenceDates' => $absenceDates,
            'reasons' => ReadmissionRequest::REASONS,
            'guardian' => $student->guardians->first(),
        ]);
    }

    public function store(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'reason_code' => ['required', Rule::in(array_keys(ReadmissionRequest::REASONS))],
            'reason_details' => ['nullable', 'string', 'max:2000', 'required_if:reason_code,other'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:5120'],
            'consent' => ['accepted'],
        ]);

        $student = $request->user()->student;

        abort_unless(
            $student !== null
                && $enrollment->student_id === $student->student_id
                && $enrollment->status === 'dropped',
            404,
        );

        $drop = $enrollment->droppingTransactions()
            ->latest('drop_id')
            ->firstOrFail();

        if ($drop->readmissionRequest()->exists()) {
            return redirect()
                ->route('student.dashboard')
                ->with('info', 'A readmission request has already been submitted for this drop.');
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

            if (ReadmissionRequest::query()
                ->where('drop_id', $lockedDrop->drop_id)
                ->lockForUpdate()
                ->exists()) {
                return false;
            }

            $readmission = ReadmissionRequest::create([
                'drop_id' => $lockedDrop->drop_id,
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
