@php
    $term = $selectedCourse
        ? collect([
            $selectedCourse->schoolYear->semester ?? null,
            $selectedCourse->schoolYear->year_label ?? null,
        ])->filter()->join(', ')
        : '';

    $tabs = $selectedCourse ? [
        'check'   => 'Take attendance',
        'summary' => 'Students',
        'history' => 'Session history',
    ] : [];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-title title="Attendance"
                      :description="$faculty->full_name . ($faculty->department ? ', '.$faculty->department : '')">
            @if ($selectedCourse)
                <span class="badge badge-neutral">
                    {{ $selectedCourse->subject_code }}, section {{ $selectedCourse->section }}
                </span>
                @if ($term !== '')
                    <span class="muted text-sm">{{ $term }}</span>
                @endif
            @endif
        </x-page-title>
    </x-slot>

    <div class="page space-y-6" x-data="facultyDashboard()">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if (session('info'))
            <div class="alert alert-neutral" role="status">{{ session('info') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-warning" role="alert">
                <p class="font-semibold">Please correct the following:</p>
                <ul class="mt-1 list-inside list-disc text-xs font-normal">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Course picker --}}
        <section class="card">
            <div class="card-header flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Assigned courses</h2>
                    <p class="muted mt-0.5 text-sm">Choose a course to take attendance or review records.</p>
                </div>
                <span class="badge badge-neutral shrink-0">
                    {{ $courses->count() }} {{ Str::plural('course', $courses->count()) }}
                </span>
            </div>

            <div class="card-body">
                @if ($courses->isEmpty())
                    <div class="py-8 text-center">
                        <p class="font-medium">No courses assigned yet</p>
                        <p class="muted mt-1 text-sm">Contact your department chair to have courses assigned to you.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($courses as $c)
                            @php
                                $isSelected = $selectedCourse && $selectedCourse->course_id === $c->course_id;
                            @endphp

                            <a href="{{ route('faculty.dashboard', ['course_id' => $c->course_id, 'date' => $selectedDate, 'tab' => $activeTab]) }}"
                               @class(['option-card', 'option-card-active' => $isSelected])
                               @if ($isSelected) aria-current="true" @endif>

                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold">
                                            {{ $c->subject_code }}
                                            <span class="muted font-normal">section {{ $c->section }}</span>
                                        </p>
                                        <p class="muted mt-0.5 truncate text-sm">{{ $c->course_name }}</p>
                                    </div>
                                    <span class="badge badge-neutral shrink-0">
                                        {{ $c->enrollments_count }} {{ Str::plural('student', $c->enrollments_count) }}
                                    </span>
                                </div>

                                <div class="muted mt-3 flex items-center justify-between gap-3 border-t pt-3 text-xs">
                                    <span class="truncate">
                                        @if ($c->schedules->isNotEmpty())
                                            {{ $c->schedules->pluck('day_of_week')->join(', ') }}
                                            at {{ \Carbon\Carbon::parse($c->schedules->first()->start_time)->format('g:i A') }}
                                        @else
                                            Schedule to be announced
                                        @endif
                                    </span>
                                    <span class="shrink-0 font-medium">{{ $c->hours_per_week }} hrs/wk</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        @if ($selectedCourse)

            {{-- Selected course and key figures --}}
            <section class="card overflow-hidden">
                <div class="card-header">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold">
                                {{ $selectedCourse->subject_code }}: {{ $selectedCourse->course_name }}
                            </h2>
                            <p class="muted mt-0.5 text-sm">
                                Section {{ $selectedCourse->section }},
                                {{ $selectedCourse->total_semester_hours }} semester hours,
                                room {{ $selectedCourse->schedules->first()->room ?? 'to be announced' }}
                            </p>
                        </div>

                        <ul class="flex flex-wrap gap-2" aria-label="Weekly schedule">
                            @foreach ($selectedCourse->schedules as $sch)
                                <li class="badge badge-neutral">
                                    {{ $sch->day_of_week }}
                                    {{ \Carbon\Carbon::parse($sch->start_time)->format('g:i A') }} to
                                    {{ \Carbon\Carbon::parse($sch->end_time)->format('g:i A') }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="stat-grid grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="muted text-sm">Enrolled students</p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $courseStats['total_students'] }}</p>
                        <p class="muted mt-1 text-xs">Active class roster</p>
                    </div>

                    <div>
                        <p class="muted text-sm">Sessions recorded</p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $courseStats['sessions_count'] }}</p>
                        <p class="muted mt-1 text-xs">Dates with attendance</p>
                    </div>

                    <div>
                        <p class="muted text-sm">Attendance rate</p>
                        <p @class([
                            'mt-1 text-2xl font-semibold tabular-nums',
                            'text-ok'   => $courseStats['attendance_rate'] >= 85,
                            'text-warn' => $courseStats['attendance_rate'] >= 75 && $courseStats['attendance_rate'] < 85,
                            'text-bad'  => $courseStats['attendance_rate'] < 75,
                        ])>
                            {{ $courseStats['attendance_rate'] }}%
                        </p>
                        <p class="muted mt-1 text-xs">Cumulative presence rate</p>
                    </div>

                    <div>
                        <p class="muted text-sm">Enrollment status</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span @class(['badge', 'badge-danger' => $courseStats['dropped_count'] > 0, 'badge-neutral' => $courseStats['dropped_count'] === 0])>
                                {{ $courseStats['dropped_count'] }} dropped
                            </span>
                        </div>
                        <p class="muted mt-2 text-xs">Drop decisions are made by faculty.</p>
                    </div>
                </div>
            </section>

            {{-- Tabs --}}
            <nav class="flex flex-wrap gap-x-6 border-b" aria-label="Course views">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $selectedDate, 'tab' => $key]) }}"
                       @class(['nav-tab whitespace-nowrap py-3', 'nav-tab-active' => $activeTab === $key])
                       @if ($activeTab === $key) aria-current="page" @endif>
                        {{ $label }}

                        @if ($key === 'check')
                            <span class="badge badge-neutral ml-2">
                                {{ \Carbon\Carbon::parse($selectedDate)->format('M j') }}
                            </span>
                        @elseif ($key === 'summary' && $courseStats['dropped_count'] > 0)
                            <span class="badge badge-warning ml-2">
                                {{ $courseStats['dropped_count'] }}
                            </span>
                        @elseif ($key === 'history')
                            <span class="badge badge-neutral ml-2">{{ $sessionHistory->count() }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            {{-- TAB: take attendance --}}
            @if ($activeTab === 'check')
                <div class="space-y-6">

                    <section class="card card-body">
                        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                            <form method="GET" action="{{ route('faculty.dashboard') }}"
                                  class="flex flex-wrap items-end gap-3">
                                <input type="hidden" name="course_id" value="{{ $selectedCourse->course_id }}">
                                <input type="hidden" name="tab" value="check">

                                <div>
                                    <label for="date-input" class="field-label mb-1">Session date</label>
                                    <input type="date" id="date-input" name="date"
                                           value="{{ $selectedDate }}"
                                           onchange="this.form.submit()"
                                           class="field w-auto">
                                </div>

                                <button type="submit" class="btn btn-secondary">Load session</button>

                                @if ($selectedDate !== now()->toDateString())
                                    <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => now()->toDateString(), 'tab' => 'check']) }}"
                                       class="link self-center text-sm">
                                        Jump to today
                                    </a>
                                @endif
                            </form>

                            <div>
                                @if ($isOnSchedule)
                                    <span class="badge badge-success">
                                        Regular meeting day ({{ \Carbon\Carbon::parse($selectedDate)->format('l') }})
                                    </span>
                                @else
                                    <span class="badge badge-warning">
                                        Out-of-schedule day ({{ \Carbon\Carbon::parse($selectedDate)->format('l') }})
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if (! $isOnSchedule && ! $faculty->suppress_schedule_warnings)
                            <div class="alert alert-warning mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <p class="font-normal">
                                    <span class="font-semibold">Notice:</span>
                                    {{ \Carbon\Carbon::parse($selectedDate)->format('l') }} is not a regular meeting day.
                                    This class meets on <strong>{{ implode(', ', $scheduledDays) }}</strong>.
                                    Attendance saved for this date is tagged as an out-of-schedule session,
                                    such as a makeup or special class.
                                </p>

                                <form method="POST" action="{{ route('faculty.toggle-schedule-warning') }}" class="shrink-0">
                                    @csrf
                                    <button type="submit" class="link whitespace-nowrap underline">
                                        Suppress this warning
                                    </button>
                                </form>
                            </div>
                        @endif
                    </section>

                    <form method="POST" action="{{ route('faculty.attendance.store') }}" id="attendance-form">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $selectedCourse->course_id }}">
                        <input type="hidden" name="session_date" value="{{ $selectedDate }}">

                        <div class="card overflow-hidden">

                            <div class="card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="w-full sm:w-64">
                                    <label for="grading-period" class="field-label">Grading period</label>
                                    <select id="grading-period" name="grading_period" class="field" required>
                                        <option value="">Select a grading period</option>
                                        <option value="first_grading">First Grading</option>
                                        <option value="midterms">Midterms</option>
                                        <option value="finals">Finals</option>
                                    </select>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="muted text-sm">Mark everyone</span>
                                    <button type="button" class="btn btn-secondary btn-sm" @click="markAll('present')">Present</button>
                                    <button type="button" class="btn btn-secondary btn-sm" @click="markAll('late')">Late</button>
                                    <button type="button" class="btn btn-secondary btn-sm" @click="markAll('absent')">Absent</button>
                                </div>

                                <div class="w-full sm:w-64">
                                    <label for="student-filter" class="sr-only">Filter students</label>
                                    <input type="text" id="student-filter" x-model="searchQuery"
                                           placeholder="Filter students"
                                           class="field">
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="w-12">No.</th>
                                            <th scope="col">Student</th>
                                            <th scope="col">Absence hours</th>
                                            <th scope="col" class="is-center">Status</th>
                                            <th scope="col">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($enrollmentRows as $index => $row)
                                            @php
                                                $isDropped = $row['status'] === 'dropped';
                                                $student = $row['student'];
                                                $currentStatus = $row['today_status'] ?? 'present';
                                                $searchText = strtolower(($student->fullName ?? '') . ' ' . ($student->student_number ?? ''));
                                            @endphp

                                            <tr x-show="matchesSearch(@js($searchText))"
                                                @class(['opacity-60' => $isDropped])>

                                                <td class="muted tabular-nums">{{ $index + 1 }}</td>

                                                <td>
                                                    <div class="font-medium">{{ $student->fullName }}</div>
                                                    <div class="muted text-xs">
                                                        {{ $student->student_number }},
                                                        {{ $student->program }}{{ $student->year_level ? ', year ' . $student->year_level : '' }}
                                                    </div>
                                                </td>

                                                <td class="min-w-[11rem]">
                                                    <div class="text-xs tabular-nums">
                                                        <span>{{ $row['absent_hours'] }} absent hrs</span>
                                                        <span class="muted">· {{ $row['absences'] }} absent, {{ $row['lates'] }} late</span>
                                                    </div>
                                                </td>

                                                <td class="is-center">
                                                    @if ($isDropped)
                                                        <span class="badge badge-danger">Dropped, locked</span>
                                                        <p class="muted mt-1 text-xs">Readmission required</p>
                                                    @else
                                                        <input type="hidden"
                                                               name="attendance[{{ $index }}][enrollment_id]"
                                                               value="{{ $row['enrollment']->enrollment_id }}">

                                                        <div class="seg" role="radiogroup"
                                                             aria-label="Status for {{ $student->fullName }}">
                                                            <label class="seg-item">
                                                                <input type="radio" value="present"
                                                                       name="attendance[{{ $index }}][status]"
                                                                       class="seg-input status-radio status-present"
                                                                       @checked($currentStatus === 'present')>
                                                                <span class="seg-label seg-present">Present</span>
                                                            </label>
                                                            <label class="seg-item">
                                                                <input type="radio" value="late"
                                                                       name="attendance[{{ $index }}][status]"
                                                                       class="seg-input status-radio status-late"
                                                                       @checked($currentStatus === 'late')>
                                                                <span class="seg-label seg-late">Late</span>
                                                            </label>
                                                            <label class="seg-item">
                                                                <input type="radio" value="absent"
                                                                       name="attendance[{{ $index }}][status]"
                                                                       class="seg-input status-radio status-absent"
                                                                       @checked($currentStatus === 'absent')>
                                                                <span class="seg-label seg-absent">Absent</span>
                                                            </label>
                                                        </div>
                                                    @endif
                                                </td>

                                                <td>
                                                    @if ($isDropped)
                                                        <span class="muted text-xs">Remarks are locked for dropped students.</span>
                                                    @else
                                                        <label class="sr-only" for="remarks-{{ $index }}">
                                                            Remarks for {{ $student->fullName }}
                                                        </label>
                                                        <input type="text" id="remarks-{{ $index }}"
                                                               name="attendance[{{ $index }}][remarks]"
                                                               value="{{ $row['today_remarks'] ?? '' }}"
                                                               placeholder="Optional note or excuse"
                                                               class="field">
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="muted py-10 text-center">
                                                    No students are enrolled in this course yet.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="card-footer flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="muted text-sm">Attendance records do not automatically drop students.</p>

                                <button type="submit" class="btn btn-primary">
                                    Save attendance for {{ \Carbon\Carbon::parse($selectedDate)->format('M j, Y') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            {{-- TAB: students and watchlist --}}
            @elseif ($activeTab === 'summary')
                <section class="card overflow-hidden">

                    <div class="card-header flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="font-semibold">Student attendance summary</h2>
                            <p class="muted mt-0.5 text-sm">
                                Review each student’s attendance. Faculty members decide when a student should be dropped.
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div>
                                <label for="status-filter" class="sr-only">Filter by status</label>
                                <select id="status-filter" x-model="statusFilter" class="field w-full sm:w-auto">
                                    <option value="all">All students</option>
                                    <option value="dropped">Dropped only</option>
                                    <option value="active">Active only</option>
                                </select>
                            </div>

                            <div>
                                <label for="summary-search" class="sr-only">Search students</label>
                                <input type="text" id="summary-search" x-model="searchQuery"
                                       placeholder="Search students"
                                       class="field w-full sm:w-48">
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">Student</th>
                                    <th scope="col">Program and year</th>
                                    <th scope="col" class="is-center">Sessions</th>
                                    <th scope="col" class="is-center">Present / late / absent</th>
                                    <th scope="col">Absence hours</th>
                                    <th scope="col" class="is-center">Status</th>
                                    <th scope="col" class="is-end"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($enrollmentRows as $row)
                                    @php
                                        $student = $row['student'];
                                        $isDropped = $row['status'] === 'dropped';
                                        $canManuallyDrop = ! $isDropped;
                                        $searchText = strtolower(($student->fullName ?? '') . ' ' . ($student->student_number ?? ''));
                                    @endphp

                                    <tr x-show="matchesSummaryFilter(@js($row['status']), @js($searchText))">

                                        <td>
                                            <div class="font-medium">{{ $student->fullName }}</div>
                                            <div class="muted text-xs">{{ $student->student_number }}</div>
                                        </td>

                                        <td>
                                            {{ $student->program }}{{ $student->year_level ? ', year ' . $student->year_level : '' }}
                                        </td>

                                        <td class="is-center tabular-nums">{{ $row['total_sessions'] }}</td>

                                        <td class="is-center tabular-nums">
                                            <span class="text-ok font-semibold">{{ $row['presences'] }}</span> /
                                            <span class="text-warn font-semibold">{{ $row['lates'] }}</span> /
                                            <span class="text-bad font-semibold">{{ $row['absences'] }}</span>
                                        </td>

                                        <td class="tabular-nums">{{ $row['absent_hours'] }} hrs</td>

                                        <td class="is-center">
                                            @if ($isDropped)
                                                <span class="badge badge-danger">Dropped</span>
                                            @elseif ($row['status'] === 'readmitted')
                                                <span class="badge badge-neutral">Readmitted</span>
                                            @else
                                                <span class="badge badge-success">Active</span>
                                            @endif
                                        </td>

                                        <td class="is-end">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="btn btn-secondary btn-sm"
                                                        @click="fetchStudentHistory({{ $row['enrollment']->enrollment_id }})">
                                                    History
                                                </button>

                                                @if ($canManuallyDrop)
                                                    <form method="POST"
                                                          action="{{ route('faculty.enrollments.drop', $row['enrollment']) }}"
                                                          onsubmit="return confirm(@js('Manually drop ' . $student->fullName . ' from this course?'))">
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger btn-sm">Drop</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="muted py-10 text-center">
                                            No students enrolled in this course.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

            {{-- TAB: session history --}}
            @elseif ($activeTab === 'history')
                <section class="card overflow-hidden">

                    <div class="card-header flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">Past attendance sessions</h2>
                            <p class="muted mt-0.5 text-sm">Review or change previously recorded attendance.</p>
                        </div>
                        <span class="badge badge-neutral shrink-0">{{ $sessionHistory->count() }} logged</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">Session date</th>
                                    <th scope="col">Schedule</th>
                                    <th scope="col" class="is-center">Present</th>
                                    <th scope="col" class="is-center">Late</th>
                                    <th scope="col" class="is-center">Absent</th>
                                    <th scope="col" class="is-center">Students</th>
                                    <th scope="col" class="is-end"><span class="sr-only">Action</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sessionHistory as $s)
                                    <tr>
                                        <td>
                                            <div class="font-medium">
                                                {{ \Carbon\Carbon::parse($s['session_date'])->format('F j, Y') }}
                                            </div>
                                            <div class="muted text-xs">
                                                {{ \Carbon\Carbon::parse($s['session_date'])->format('l') }}
                                            </div>
                                        </td>

                                        <td>
                                            @if ($s['is_out_of_schedule'])
                                                <span class="badge badge-warning">Out of schedule</span>
                                            @else
                                                <span class="badge badge-neutral">Regular</span>
                                            @endif
                                        </td>

                                        <td class="is-center tabular-nums text-ok font-semibold">{{ $s['present_count'] }}</td>
                                        <td class="is-center tabular-nums text-warn font-semibold">{{ $s['late_count'] }}</td>
                                        <td class="is-center tabular-nums text-bad font-semibold">{{ $s['absent_count'] }}</td>
                                        <td class="is-center tabular-nums">{{ $s['total'] }}</td>

                                        <td class="is-end">
                                            <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $s['session_date'], 'tab' => 'check']) }}"
                                               class="btn btn-secondary btn-sm">
                                                Edit or view
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="muted py-10 text-center">
                                            No attendance has been saved for this course yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        @endif

        {{-- Student attendance history dialog --}}
        <div x-show="showHistoryModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="history-title"
             @keydown.escape.window="showHistoryModal = false">

            <div class="modal-scrim absolute inset-0" @click="showHistoryModal = false"></div>

            <div class="modal-panel relative w-full max-w-2xl">

                <div class="card-header flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h3 id="history-title" class="font-semibold"
                            x-text="historyData.student_name || 'Student attendance history'"></h3>
                        <p class="muted mt-0.5 text-sm"
                           x-text="[historyData.student_number ? 'ID ' + historyData.student_number : '', historyData.course].filter(Boolean).join(', ')"></p>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm shrink-0" @click="showHistoryModal = false">Close</button>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    <template x-if="isLoadingHistory">
                        <p class="muted px-5 py-10 text-center text-sm">Loading attendance log…</p>
                    </template>

                    <template x-if="!isLoadingHistory && loadError">
                        <p class="text-bad px-5 py-10 text-center text-sm">
                            Unable to load attendance history. Close this window and try again.
                        </p>
                    </template>

                    <template x-if="!isLoadingHistory && !loadError && (!historyData.records || historyData.records.length === 0)">
                        <p class="muted px-5 py-10 text-center text-sm">No attendance records found for this student.</p>
                    </template>

                    <template x-if="!isLoadingHistory && !loadError && historyData.records && historyData.records.length > 0">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col" class="is-center">Status</th>
                                    <th scope="col">Remarks</th>
                                    <th scope="col" class="is-center">Schedule</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="rec in historyData.records" :key="rec.attendance_id">
                                    <tr>
                                        <td class="whitespace-nowrap font-medium" x-text="rec.session_date"></td>
                                        <td class="is-center">
                                            <span class="badge capitalize"
                                                  :class="{
                                                      'badge-success': rec.status === 'present',
                                                      'badge-warning': rec.status === 'late',
                                                      'badge-danger': rec.status === 'absent'
                                                  }"
                                                  x-text="rec.status"></span>
                                        </td>
                                        <td class="muted" x-text="rec.remarks || '—'"></td>
                                        <td class="is-center">
                                            <span class="badge"
                                                  :class="rec.is_out_of_schedule ? 'badge-warning' : 'badge-neutral'"
                                                  x-text="rec.is_out_of_schedule ? 'Out of schedule' : 'Regular'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                </div>
            </div>
        </div>

    </div>

    <script>
        function facultyDashboard() {
            return {
                searchQuery: '',
                statusFilter: 'all',
                showHistoryModal: false,
                isLoadingHistory: false,
                loadError: false,
                historyData: {},

                matchesSearch(text) {
                    if (!this.searchQuery) return true;
                    return text.includes(this.searchQuery.toLowerCase().trim());
                },

                matchesSummaryFilter(status, text) {
                    if (this.searchQuery && !text.includes(this.searchQuery.toLowerCase().trim())) {
                        return false;
                    }

                    if (this.statusFilter === 'all') return true;
                    if (this.statusFilter === 'dropped') return status === 'dropped';
                    if (this.statusFilter === 'active') return status !== 'dropped';

                    return true;
                },

                markAll(targetStatus) {
                    const radios = document.querySelectorAll(`.status-radio.status-${targetStatus}`);
                    radios.forEach(radio => {
                        radio.checked = true;
                    });
                },

                fetchStudentHistory(enrollmentId) {
                    this.showHistoryModal = true;
                    this.isLoadingHistory = true;
                    this.loadError = false;
                    this.historyData = {};

                    fetch(`/faculty/enrollments/${enrollmentId}/attendance-history`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Network error');
                        return res.json();
                    })
                    .then(data => {
                        this.historyData = data;
                        this.isLoadingHistory = false;
                    })
                    .catch(err => {
                        console.error(err);
                        this.loadError = true;
                        this.isLoadingHistory = false;
                    });
                }
            };
        }
    </script>
</x-app-layout>
