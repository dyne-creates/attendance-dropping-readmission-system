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
                                Weekly-hours drop referred to OSA
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
                        <div>
                            <dt class="text-sm font-medium muted">Excused dates</dt>
                            <dd class="mt-1 text-sm">
                                {{ Carbon\Carbon::parse($request->excused_from)->format('M d, Y') }}
                                – {{ Carbon\Carbon::parse($request->excused_through)->format('M d, Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium muted">Absence hours deducted</dt>
                            <dd class="mt-1 text-sm">{{ number_format((float) $request->excused_hours_deducted, 2) }} hrs</dd>
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
                    <h2 class="text-base font-semibold">Recorded absence dates</h2>
                </div>
                <div class="card-body space-y-4">
                    @forelse ($absenceDates as $absence)
                        <p class="text-sm">
                            {{ Carbon\Carbon::parse($absence->session_date)->format('F j, Y') }}
                            @if ($absence->hours_absent !== null)
                                <span class="muted">({{ $absence->hours_absent }} hours)</span>
                            @endif
                        </p>
                    @empty
                        <p class="text-sm muted">No recorded absences.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <!-- Supporting documents -->
        <section class="card overflow-hidden">
            <div class="card-header">
                <h2 class="text-base font-semibold">Supporting documents</h2>
            </div>

            <div class="card-body">
                @forelse ($documents as $document)
                    <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between {{ $loop->first ? 'pt-0' : 'border-t' }} {{ $loop->last ? 'pb-0' : '' }}">
                        <div>
                            <p class="text-sm font-medium">{{ ucfirst(str_replace('_', ' ', (string) $document->document_type)) }}</p>
                            <p class="mt-0.5 text-xs muted">
                                Uploaded {{ $document->created_at ? Carbon\Carbon::parse($document->created_at)->format('M d, Y') : '—' }}
                            </p>
                        </div>

                        <a href="{{ route('osa.readmissions.documents.download', $document->document_id) }}"
                           class="btn btn-secondary btn-sm">
                            Download document
                        </a>
                    </div>
                    <img src="{{ route('osa.readmissions.documents.preview', $document->document_id) }}"
                         alt="Supporting document for readmission attempt {{ $request->attempt_number }}"
                         class="max-h-96 max-w-full rounded-lg border object-contain">
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
                          data-confirm="Excuse absences in this range and reactivate the student?"
                          x-data="{
                              duration: @js(old('excused_from') && old('excused_through') && old('excused_from') !== old('excused_through') ? 'multiple' : 'single'),
                              excusedFrom: @js(old('excused_from', '')),
                              excusedThrough: @js(old('excused_through', ''))
                          }"
                          x-effect="if (duration === 'single') excusedThrough = excusedFrom">
                        @csrf

                        <div>
                            <h3 class="font-semibold">Excused absence and readmission</h3>
                            <p class="muted mt-1 text-sm">Choose whether the excuse covers one day or multiple days. Recorded absence hours for those dates will be deducted before readmission.</p>
                        </div>

                        <fieldset>
                            <legend class="field-label mb-2">Excused absence duration</legend>
                            <div class="flex flex-wrap gap-2">
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                                       :class="duration === 'single' ? 'border-indigo-500 bg-indigo-50 text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200' : 'border-gray-300 dark:border-gray-600'">
                                    <input type="radio" name="duration" value="single" x-model="duration">
                                    1 day only
                                </label>
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                                       :class="duration === 'multiple' ? 'border-indigo-500 bg-indigo-50 text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200' : 'border-gray-300 dark:border-gray-600'">
                                    <input type="radio" name="duration" value="multiple" x-model="duration">
                                    2 days or more
                                </label>
                            </div>
                        </fieldset>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="excused_from" class="field-label mb-1">Start date <span aria-hidden="true">*</span></label>
                                <input id="excused_from" name="excused_from" type="date" x-model="excusedFrom" class="field" required>
                                @error('excused_from')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="excused_through" class="field-label mb-1">End date <span aria-hidden="true">*</span></label>
                                <input id="excused_through" name="excused_through" type="date" x-model="excusedThrough"
                                       :min="excusedFrom" :readonly="duration === 'single'" class="field" required>
                                @error('excused_through')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

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
                </div>
            </section>
        @else
            <div class="alert alert-neutral" role="status">
                This request has already been processed as <strong>{{ $statusLabel }}</strong>.
            </div>
        @endif

    </div>
</x-app-layout>