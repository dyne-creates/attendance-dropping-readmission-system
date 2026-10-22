<x-app-layout>
    <x-slot name="header">
        <x-page-title
            title="Online readmission request"
            description="Review your automatically filled information, provide your reason, and submit the request to the Office of Student Affairs." />
    </x-slot>

    <div class="page space-y-6">
        @if ($drop->drop_type === \App\Models\DroppingTransaction::TYPE_PERMANENT)
            <div class="alert alert-danger" role="status">
                This is recorded as a permanent-drop case because absences reached 20% of the subject's semester hours.
                OSA may still review and approve your readmission request.
            </div>
        @elseif ($drop->drop_type === \App\Models\DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL)
            <div class="alert alert-warning" role="status">
                Your instructor referred you to OSA based on a circumstance. OSA will review your readmission request.
            </div>
        @else
            <div class="alert alert-warning" role="status">
                Your instructor referred this weekly-hours drop to OSA for readmission review.
            </div>
        @endif

        <section class="card p-5 sm:p-6" aria-labelledby="policy-heading">
            <h2 id="policy-heading" class="text-lg font-semibold">Section 1: Attendance monitoring and consent</h2>
            <div class="muted mt-3 space-y-3 text-sm leading-6">
                <p>
                    In line with the University's Vision and Mission of a relevant and responsive education, timely and
                    accurate monitoring of student attendance can help improve the University's academic process. The
                    Office of Student Affairs operates the Online Attendance Monitoring and Readmission System to
                    support a timely, proper, and efficient response to factors affecting student attendance and
                    performance.
                </p>
                <p>
                    The University may coordinate with parents or guardians by notifying them of student absences and
                    providing an opportunity to share any concerns. Attendance monitoring is intended to encourage
                    responsibility and help students get the full benefit of their education.
                </p>
                <p>
                    By submitting this readmission request, you acknowledge that the readmission officer may notify
                    your parents or guardians about your absences as part of the readmission process. Your personal
                    information will be handled in accordance with applicable data protection laws, securely stored,
                    and accessed only by authorized personnel involved in the process. It will not be disclosed to
                    third parties unless required by law or with your explicit consent. For privacy concerns, contact
                    the Office of Student Affairs before submitting.
                </p>
                <p>
                    Please ensure that your information is accurate and use your student email address.
                </p>
                <p>
                    I certify that the information provided is true and correct and understand that false statements
                    or concealment may lead to disciplinary sanctions. I consent to the collection, processing, and
                    storage of my data under RA 10173 (Data Privacy Act of 2012), and authorize the Office of Student
                    Affairs to use this information to notify my parents or guardians about my absences and attendance.
                </p>
            </div>
        </section>

        <section class="card p-5 sm:p-6" aria-labelledby="student-information-heading">
            <h2 id="student-information-heading" class="text-lg font-semibold">Section 2: Student information</h2>
            <p class="muted mt-1 text-sm">Automatically loaded from your student profile.</p>

            <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="field-label">ID number</dt>
                    <dd class="mt-1 font-medium">{{ $student->student_number }}</dd>
                </div>
                <div>
                    <dt class="field-label">Student email</dt>
                    <dd class="mt-1 font-medium">{{ $student->user->email }}</dd>
                </div>
                <div>
                    <dt class="field-label">Last name</dt>
                    <dd class="mt-1 font-medium">{{ $student->last_name }}</dd>
                </div>
                <div>
                    <dt class="field-label">First name</dt>
                    <dd class="mt-1 font-medium">{{ $student->first_name }}</dd>
                </div>
                <div>
                    <dt class="field-label">Honorifics</dt>
                    <dd class="mt-1 font-medium">{{ $student->honorifics ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt class="field-label">Year level</dt>
                    <dd class="mt-1 font-medium">{{ $student->year_level ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt class="field-label">School / college</dt>
                    <dd class="mt-1 font-medium">{{ $student->department ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt class="field-label">Program</dt>
                    <dd class="mt-1 font-medium">{{ $student->program ?? 'Not provided' }}</dd>
                </div>
            </dl>
        </section>

        <section class="card p-5 sm:p-6" aria-labelledby="class-information-heading">
            <h2 id="class-information-heading" class="text-lg font-semibold">Section 3: Class information</h2>
            <p class="muted mt-1 text-sm">Automatically loaded from the dropped class and its history.</p>

            <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="field-label">Course</dt>
                    <dd class="mt-1 font-medium">{{ $enrollment->course->course_name }}</dd>
                </div>
                <div>
                    <dt class="field-label">Subject code</dt>
                    <dd class="mt-1 font-medium">{{ $enrollment->course->subject_code }}</dd>
                </div>
                <div>
                    <dt class="field-label">Section</dt>
                    <dd class="mt-1 font-medium">{{ $enrollment->course->section }}</dd>
                </div>
                <div>
                    <dt class="field-label">Class schedule</dt>
                    <dd class="mt-1 font-medium">
                        @forelse ($enrollment->course->schedules as $schedule)
                            {{ $schedule->day_of_week }}
                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}@unless ($loop->last), @endunless
                        @empty
                            Not provided
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="field-label">Times dropped from this class</dt>
                    <dd class="mt-1 font-medium">{{ $dropCount }}</dd>
                </div>
                <div>
                    <dt class="field-label">Instructor</dt>
                    <dd class="mt-1 font-medium">{{ $enrollment->course->facultyMember->full_name }}</dd>
                </div>
            </dl>
        </section>

        @if ($previousAttempts->isNotEmpty())
            <section class="card p-5 sm:p-6" aria-labelledby="previous-attempts-heading">
                <h2 id="previous-attempts-heading" class="text-lg font-semibold">Previous readmission attempts</h2>
                <ol class="mt-4 space-y-3">
                    @foreach ($previousAttempts as $previousAttempt)
                        <li class="rounded-lg border p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">Attempt {{ $previousAttempt->attempt_number }}</span>
                                <span class="badge {{ $previousAttempt->status === 'rejected' ? 'badge-danger' : 'badge-neutral' }}">
                                    {{ ucfirst($previousAttempt->status) }}
                                </span>
                            </div>
                            @if ($previousAttempt->rejection_reason)
                                <p class="mt-2 whitespace-pre-line text-sm">
                                    <span class="font-medium">OSA feedback:</span> {{ $previousAttempt->rejection_reason }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <section class="card p-5 sm:p-6" aria-labelledby="absence-heading">
            <h2 id="absence-heading" class="text-lg font-semibold">Section 4: Absence and reason information</h2>
            <p class="muted mt-1 text-sm">
                Absence dates below are taken from recorded attendance and grouped by grading period.
            </p>

            <div class="mt-5 space-y-2">
                @foreach ($absenceDates as $period)
                    <details class="disclosure rounded-lg border">
                        <summary class="flex cursor-pointer items-center justify-between gap-3 p-4">
                            <span class="font-medium">{{ $period['label'] }}</span>
                            <span class="badge badge-neutral">{{ $period['dates']->count() }} absent</span>
                        </summary>
                        @if ($period['dates']->isNotEmpty())
                            <ul class="space-y-1 border-t px-4 py-3 text-sm">
                                @foreach ($period['dates'] as $absence)
                                    <li>{{ $absence->session_date->format('F j, Y') }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="muted border-t px-4 py-3 text-sm">No absences assigned to this period.</p>
                        @endif
                    </details>
                @endforeach
            </div>

            <p class="alert alert-warning mt-4" role="note">
                Only absences assigned to a grading period are listed. Ask your instructor to assign a grading period
                if an absence is missing. If a previous attempt was rejected, you may submit a revised request.
            </p>

            <form method="POST"
                  action="{{ route('student.readmissions.store', $enrollment) }}"
                  enctype="multipart/form-data"
                  class="mt-6 space-y-5"
                  x-data="{ imagePreview: null, selectedFileName: '', attachmentRequired: @js(in_array(old('reason_code'), ['dental_medical', 'personal_family'], true)) }">
                @csrf

                <p class="muted text-sm">Submitting attempt {{ $attemptNumber }}.</p>

                <div>
                    <label for="reason_code" class="field-label mb-1">Reason for absences <span aria-hidden="true">*</span></label>
                    <select id="reason_code" name="reason_code" class="field" required
                            @change="attachmentRequired = ['dental_medical', 'personal_family'].includes($event.target.value)">
                        <option value="">Select a reason</option>
                        @foreach ($reasons as $code => $label)
                            <option value="{{ $code }}" @selected(old('reason_code') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('reason_code')
                        <p class="field-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reason_details" class="field-label mb-1">Additional details <span class="muted">(required if you select Other)</span></label>
                    <textarea id="reason_details" name="reason_details" rows="4" maxlength="2000" class="field">{{ old('reason_details') }}</textarea>
                    @error('reason_details')
                        <p class="field-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="attachment" class="field-label mb-1">
                        Supporting document
                        <span class="muted">(required for dental/medical and personal/family emergencies; JPEG or PNG only)</span>
                    </label>
                    <input id="attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                           class="field" :required="attachmentRequired"
                           @change="
                               const file = $event.target.files[0];
                               selectedFileName = file ? file.name : '';
                               imagePreview = file ? URL.createObjectURL(file) : null;
                           ">
                    <p class="muted mt-1 text-sm" x-text="selectedFileName || 'No image selected.'"></p>
                    <img x-show="imagePreview" x-cloak :src="imagePreview" alt="Selected supporting document preview"
                         class="mt-3 max-h-72 rounded-lg border object-contain">
                    @error('attachment')
                        <p class="field-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <section class="rounded-lg border p-4" aria-labelledby="guardian-information-heading">
                    <h3 id="guardian-information-heading" class="font-semibold">Section 5: Parent / guardian information</h3>
                    <p class="muted mt-1 text-sm">Automatically loaded from your student profile.</p>

                    @if ($guardian)
                        <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="field-label">Name</dt>
                                <dd class="mt-1 font-medium">{{ $guardian->full_name }}</dd>
                            </div>
                            <div>
                                <dt class="field-label">Relationship</dt>
                                <dd class="mt-1 font-medium">{{ $guardian->relationship }}</dd>
                            </div>
                            <div>
                                <dt class="field-label">Email address</dt>
                                <dd class="mt-1 font-medium">{{ $guardian->email ?? 'Not provided' }}</dd>
                            </div>
                            <div>
                                <dt class="field-label">Contact number</dt>
                                <dd class="mt-1 font-medium">{{ $guardian->phone_number }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="alert alert-warning mt-4" role="status">
                            No parent or guardian information is on file. Please update your profile before submitting.
                        </p>
                    @endif
                </section>

                <div>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="consent" value="1" class="mt-1" @checked(old('consent')) required>
                        <span class="text-sm">
                            I certify that the information provided is true and correct, consent to the collection,
                            processing, and storage of my data under RA 10173, and authorize the Office of Student
                            Affairs to notify my parent or guardian about my absences and attendance.
                        </span>
                    </label>
                    @error('consent')
                        <p class="field-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('student.dashboard') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit readmission request</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
