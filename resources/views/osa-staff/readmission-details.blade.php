@php
    $outcome = $request->outcome ?? $request->status;
    $statusBadges = [
        'pending'    => ['badge-warning', 'Pending'],
        'approved'   => ['badge-success', 'Approved'],
        'excused'    => ['badge-success', 'Excused'],
        'rejected'   => ['badge-danger', 'Rejected'],
        'not_excused' => ['badge-warning', 'Not excused'],
    ];
    [$statusClass, $statusLabel] = $statusBadges[$outcome] ?? ['badge-neutral', ucfirst((string) $outcome)];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Review"
            title="Review Readmission Request"
            description="Review the student's information before making a decision."
        >
            <x-slot name="actions">
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>

                <a href="{{ route('osa.readmissions') }}" class="btn btn-secondary btn-sm">
                    Back to requests
                </a>
                <a href="{{ route('osa.readmissions.export-one', $request->readmission_id) }}" class="btn btn-primary btn-sm">
                    Export this form to Excel
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-flash-messages />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <!-- Student -->
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Student information</h2>
                </div>

                <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Full name</dt>
                        <dd class="mt-1 text-sm font-semibold">{{ $request->first_name }} {{ $request->last_name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Student number</dt>
                        <dd class="mt-1 text-sm">{{ $request->student_number }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Year level</dt>
                        <dd class="mt-1 text-sm">{{ $request->year_level }}</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Program</dt>
                        <dd class="mt-1 text-sm">{{ $request->program }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Course -->
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Course information</h2>
                </div>

                <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium muted">Subject code</dt>
                        <dd class="mt-1 text-sm font-semibold">{{ $request->subject_code }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Section</dt>
                        <dd class="mt-1 text-sm">{{ $request->section }}</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Course</dt>
                        <dd class="mt-1 text-sm">{{ $request->course_name }}</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Faculty</dt>
                        <dd class="mt-1 text-sm">{{ $request->faculty_name }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Dropping -->
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Dropping information</h2>
                </div>

                <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium muted">Drop date</dt>
                        <dd class="mt-1 text-sm">{{ $request->drop_date ? Carbon\Carbon::parse($request->drop_date)->format('M d, Y') : '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Absence hours</dt>
                        <dd class="mt-1 text-sm">{{ $request->absence_hours_at_drop }} hrs</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Drop level</dt>
                        <dd class="mt-1 text-sm font-semibold">
                            @if ($request->drop_type === \App\Models\DroppingTransaction::TYPE_PERMANENT)
                                Permanent-drop level (20% of semester hours)
                                <span class="mt-1 block text-xs font-normal muted">OSA may still review and approve readmission.</span>
                            @elseif ($request->drop_type === \App\Models\DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL)
                                Instructor referral to OSA
                            @else
                                Accumulated absence-hours drop referred to OSA
                            @endif
                        </dd>
                    </div>
                    @if ($request->instructor_reason)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium muted">Instructor's reason for referral</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm">{{ $request->instructor_reason }}</dd>
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Drop transaction</dt>
                        <dd class="mt-1 text-sm font-semibold">#{{ $request->drop_id }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Request -->
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Readmission request</h2>
                </div>

                <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium muted">Attempt number</dt>
                        <dd class="mt-1 text-sm font-semibold">{{ $request->attempt_number }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Reason</dt>
                        <dd class="mt-1 text-sm">{{ \App\Models\ReadmissionRequest::reasonLabel((string) $request->reason_code) }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Submission channel</dt>
                        <dd class="mt-1"><span class="badge badge-neutral">{{ strtoupper($request->channel) }}</span></dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Details</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm">{{ $request->reason_details ?? 'No additional details provided.' }}</dd>
                    </div>

                    @if ($request->rejection_reason)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium muted">OSA decision note</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm">{{ $request->rejection_reason }}</dd>
                        </div>
                    @endif

                    @if ($request->outcome === \App\Models\ReadmissionRequest::OUTCOME_EXCUSED)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium muted">Selected excused dates</dt>
                            <dd class="mt-1 text-sm">
                                {{ collect(json_decode((string) ($request->excused_dates ?? '[]'), true) ?: [$request->excused_from, $request->excused_through])->filter()->map(fn (string $date): string => Carbon\Carbon::parse($date)->format('M d, Y'))->join(', ') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium muted">Absence hours deducted</dt>
                            <dd class="mt-1 text-sm">{{ number_format((float) $request->excused_hours_deducted, 2) }} hrs</dd>
                        </div>
                    @endif

                    @if ($request->correction_requested_at)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium muted">Correction requested</dt>
                            <dd class="mt-1 text-sm">Sent to the student on {{ Carbon\Carbon::parse($request->correction_requested_at)->format('M d, Y g:i A') }}.</dd>
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Policy consent</dt>
                        <dd class="mt-1 text-sm">
                            {{ $request->policy_acknowledged_at ? 'Acknowledged on '.Carbon\Carbon::parse($request->policy_acknowledged_at)->format('M d, Y g:i A') : 'Not recorded' }}
                        </dd>
                    </div>
                </dl>
            </section>

        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Parent / guardian information</h2>
                </div>
                <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    @if ($guardian)
                        <div>
                            <dt class="text-sm font-medium muted">Name</dt>
                            <dd class="mt-1 text-sm">{{ $guardian->full_name ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium muted">Relationship</dt>
                            <dd class="mt-1 text-sm">{{ $guardian->relationship ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium muted">Email</dt>
                            <dd class="mt-1 text-sm">{{ $guardian->email ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium muted">Contact number</dt>
                            <dd class="mt-1 text-sm">{{ $guardian->phone_number ?: '—' }}</dd>
                        </div>
                    @else
                        <div class="sm:col-span-2">
                            <dd class="text-sm muted">No parent or guardian is listed on the student's profile.</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Attendance status and weekly drill-down</h2>
                </div>
                <div class="card-body space-y-4">
                    @forelse ($attendanceRecords as $absence)
                        <p class="text-sm">
                            {{ Carbon\Carbon::parse($absence->session_date)->format('F j, Y') }}
                            <span class="badge {{ $absence->status === 'excused' ? 'badge-success' : ($absence->status === 'absent' ? 'badge-danger' : 'badge-neutral') }}">{{ ucfirst($absence->status) }}</span>
                            @if ($absence->hours_absent !== null)
                                <span class="muted">({{ $absence->hours_absent }} hours)</span>
                            @endif
                        </p>
                    @empty
                        <p class="text-sm muted">No attendance records.</p>
                    @endforelse
                    @if ($weeklyAbsenceHours->isNotEmpty())
                        <div class="border-t pt-3">
                            <p class="field-label mb-2">Weekly absence-hours drill-down</p>
                            @foreach ($weeklyAbsenceHours as $week => $hours)
                                <p class="text-sm">Week of {{ Carbon\Carbon::parse($week)->format('M d, Y') }} <span class="muted">· {{ number_format($hours, 2) }} hrs absent</span></p>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <section class="card overflow-hidden">
            <div class="card-header"><h2 class="text-base font-semibold">Student dropping and readmission history</h2></div>
            <div class="card-body space-y-4">
                <div class="stat-grid grid-cols-2 overflow-hidden rounded-lg sm:grid-cols-4">
                    @foreach (['drops' => 'Drops', 'requests' => 'Requests', 'excused' => 'Excused', 'not_excused' => 'Not excused'] as $key => $label)
                        <div>
                            <p class="muted text-sm">{{ $label }}</p>
                            <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $historyCounters[$key] }}</p>
                        </div>
                    @endforeach
                </div>
                @forelse ($studentHistory as $history)
                    <div class="surface-alt rounded-lg border p-3 text-sm">
                        <p class="font-medium">{{ $history->subject_code }} · {{ $history->course_name }}</p>
                        <p class="mt-1 muted">Dropped {{ Carbon\Carbon::parse($history->drop_date)->format('M d, Y') }} · {{ number_format((float) $history->absence_hours_at_drop, 2) }} hrs @if($history->attempt_number) · Request #{{ $history->attempt_number }}: {{ ucfirst($history->readmission_outcome ?? $history->readmission_status) }} @endif</p>
                    </div>
                @empty
                    <p class="text-sm muted">No prior dropping or readmission history.</p>
                @endforelse
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="card-header"><h2 class="text-base font-semibold">Dropping and correction notifications</h2></div>
            <div class="card-body space-y-3">
                @forelse ($notifications as $notification)
                    <div class="rounded-lg border p-3">
                        <p class="text-sm">{{ $notification->message }}</p>
                        <p class="mt-1 text-xs muted">{{ $notification->sent_at ? Carbon\Carbon::parse($notification->sent_at)->format('M d, Y g:i A') : 'Not sent' }}</p>
                    </div>
                @empty
                    <p class="text-sm muted">No dropping or correction notifications are recorded for this student.</p>
                @endforelse
            </div>
        </section>

        <!-- Supporting documents -->
        <section class="card overflow-hidden">
            <div class="card-header">
                <h2 class="text-base font-semibold">Supporting documents</h2>
            </div>

            <div class="card-body">
                @forelse ($documents as $document)
                    <div x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false" class="{{ $loop->first ? '' : 'border-t pt-5' }} {{ $loop->last ? '' : 'pb-5' }}">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium">{{ ucfirst(str_replace('_', ' ', (string) $document->document_type)) }}</p>
                                <p class="mt-0.5 text-xs muted">
                                    Uploaded {{ $document->created_at ? Carbon\Carbon::parse($document->created_at)->format('M d, Y') : '—' }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" @click="previewOpen = true">Preview</button>
                                <a href="{{ route('osa.readmissions.documents.download', $document->document_id) }}" class="btn btn-secondary btn-sm">Download</a>
                            </div>
                        </div>
                        <iframe src="{{ route('osa.readmissions.documents.preview', $document->document_id) }}#view=FitH" title="Supporting document preview" class="mt-4 w-full max-w-4xl rounded-lg border bg-white" style="height: min(72vh, 700px);"></iframe>

                        <div x-show="previewOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true" aria-label="Full-screen document preview">
                            <button type="button" class="absolute inset-0 cursor-default" aria-label="Close document preview" @click="previewOpen = false"></button>
                            <section class="relative flex h-full w-full max-w-7xl flex-col rounded-lg bg-white p-3 shadow-2xl">
                                <div class="mb-3 flex items-center justify-between gap-4">
                                    <p class="text-sm font-semibold text-gray-900">Document preview</p>
                                    <button type="button" class="btn btn-secondary btn-sm" @click="previewOpen = false">Close</button>
                                </div>
                                <iframe src="{{ route('osa.readmissions.documents.preview', $document->document_id) }}#view=FitH" title="Full-screen supporting document preview" class="min-h-0 flex-1 w-full rounded border bg-white"></iframe>
                            </section>
                        </div>
                    </div>
                @empty
                    <p class="text-sm muted">No supporting documents were uploaded.</p>
                @endforelse
            </div>
        </section>

        <!-- Decision -->
        @if ($request->status === 'pending')
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">OSA decision</h2>
                    <p class="mt-0.5 text-sm muted">Review all information before processing this request.</p>
                </div>

                <div class="card-body space-y-5">
                    <form method="POST" action="{{ route('osa.readmissions.approve', $request->readmission_id) }}"
                          data-confirm="Are you sure you want to approve this readmission request?">
                        @csrf

                        <button type="submit"
                                class="btn btn-success w-full sm:w-auto">
                            Approve and reactivate
                        </button>
                    </form>

                    <form method="POST" action="{{ route('osa.readmissions.excuse', $request->readmission_id) }}"
                          class="max-w-2xl space-y-3 rounded-lg border p-4"
                          data-confirm="Excuse the selected absence dates and reactivate the student?">
                        @csrf

                        <div>
                            <h3 class="font-semibold">Excused absence and readmission</h3>
                            <p class="muted mt-1 text-sm">Tick each recorded absence to excuse. Only selected dates are changed and deducted.</p>
                        </div>
                        <fieldset><legend class="field-label mb-2">Select absence dates</legend><div class="flex flex-wrap gap-2">
                            @forelse ($absenceDates as $absence)
                                @php($date = Carbon\Carbon::parse($absence->session_date)->toDateString())
                                <label class="cursor-pointer rounded-lg border px-3 py-2 text-sm has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 dark:has-[:checked]:bg-indigo-950/40">
                                    <input type="checkbox" name="excused_dates[]" value="{{ $date }}" @checked(in_array($date, old('excused_dates', []), true))>
                                    {{ Carbon\Carbon::parse($date)->format('M j') }} @if($absence->hours_absent !== null)<span class="muted">· {{ $absence->hours_absent }}h</span>@endif
                                </label>
                            @empty
                                <div class="w-full space-y-2">
                                    <p class="text-sm muted">There are no unexcused absences available. Choose one date to record this OSA decision in reports.</p>
                                    <label for="reporting_date" class="field-label">Excused date <span aria-hidden="true">*</span></label>
                                    <input id="reporting_date" name="reporting_date" type="date" value="{{ old('reporting_date') }}" class="field max-w-xs" required>
                                </div>
                            @endforelse
                        </div></fieldset>
                        @error('excused_dates')<p class="field-error">{{ $message }}</p>@enderror
                        @error('reporting_date')<p class="field-error">{{ $message }}</p>@enderror

                        <button type="submit" class="btn btn-primary">
                            Excuse dates and reactivate
                        </button>
                    </form>

                    <form method="POST" action="{{ route('osa.readmissions.reject', $request->readmission_id) }}"
                          class="max-w-2xl space-y-3">
                        @csrf
                        <div>
                            <label for="rejection_reason" class="field-label mb-1">OSA note for the student <span aria-hidden="true">*</span></label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="3" maxlength="2000"
                                      class="field" required>{{ old('rejection_reason') }}</textarea>
                            @error('rejection_reason')
                                <p class="field-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" formaction="{{ route('osa.readmissions.reject', $request->readmission_id) }}"
                                    class="btn btn-danger"
                                    data-confirm="Reject this request for the term? The student will remain inactive and cannot enroll in this course during the term.">
                                Reject for the term
                            </button>
                            <button type="submit" formaction="{{ route('osa.readmissions.not-excused', $request->readmission_id) }}"
                                    class="btn btn-secondary"
                                    data-confirm="Mark this request Not excused? The student will remain inactive but may submit a corrected request.">
                                Not excused — allow resubmission
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('osa.readmissions.correction-request', $request->readmission_id) }}" class="max-w-2xl space-y-3">
                        @csrf
                        <label for="correction_note" class="field-label mb-1">Correction instructions for the student <span aria-hidden="true">*</span></label>
                        <textarea id="correction_note" name="correction_note" rows="3" maxlength="2000" class="field" required>{{ old('correction_note') }}</textarea>
                        <button type="submit" class="btn btn-secondary" data-confirm="Send this request back to the student for correction?">Request correction</button>
                    </form>
                </div>
            </section>
        @else
            <div class="alert alert-neutral" role="status">
                This request has already been processed as <strong>{{ $statusLabel }}</strong>.
            </div>
        @endif

    </div>
</x-app-layout>
