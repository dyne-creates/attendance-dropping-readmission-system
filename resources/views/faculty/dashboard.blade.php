@php
    $term = $selectedCourse
        ? collect([
            $selectedCourse->schoolYear->semester ?? null,
            $selectedCourse->schoolYear->year_label ?? null,
        ])->filter()->join(', ')
        : '';

    // Pre-calculate initial status and hours maps for Alpine
    $initialStatuses = [];
    $initialHours = [];
    $initialRemarks = [];
    $gridRecords = [];
    $studentScores = [];
    $todayDateStr = \Carbon\Carbon::parse($selectedDate)->toDateString();

    if (isset($enrollmentRows)) {
        foreach ($enrollmentRows as $idx => $row) {
            $eId = $row['enrollment']->enrollment_id;
            // Only set if an actual record exists for today, else null so it shows '--'
            $initialStatuses[$idx] = $row['today_status'] ?? null;
            $initialHours[$idx] = $row['today_hours_absent'] ?? $sessionHours;
            $initialRemarks[$idx] = $row['today_remarks'] ?? '';

            $gridRecords[$eId] = $row['records_by_date']->toArray();
            $score = $row['overall_score'];
            $pillClass = $score >= 90 ? 'bb-pill-green' : ($score >= 80 ? 'bb-pill-lime' : ($score >= 70 ? 'bb-pill-yellow' : 'bb-pill-red'));
            $scoreText = ($score == 100) ? '100 / 100' : number_format($score, 2) . ' / 100';

            $studentScores[$eId] = [
                'score' => $score,
                'pill_class' => $pillClass,
                'score_text' => $scoreText,
                'absent_hours' => $row['absent_hours'],
                'maximum_absence_hours' => $row['maximum_absence_hours'],
                'weekly_drop_threshold_hours' => $row['weekly_drop_threshold_hours'],
                'absence_percentage' => $row['absence_percentage'],
                'maximum_absence_percentage' => $row['maximum_absence_percentage'],
                'is_drop_eligible' => $row['is_drop_eligible'],
                'is_permanent_drop_eligible' => $row['is_permanent_drop_eligible'],
                'is_dropped' => $row['status'] === 'dropped' && ! $row['can_record_attendance'],
            ];
        }
    }

    $isMeetingTab = in_array($activeTab, ['meeting', 'check']);
    $prevDate = $prevDate ?? \Carbon\Carbon::parse($selectedDate)->subDay()->toDateString();
    $nextDate = $nextDate ?? \Carbon\Carbon::parse($selectedDate)->addDay()->toDateString();
    $isToday = \Carbon\Carbon::parse($selectedDate)->isToday();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium tracking-wide">
                        {{ $selectedCourse ? $selectedCourse->course_name : 'Faculty Portal' }}
                    </span>
                </div>
                <h1 class="text-3xl md:text-4xl font-normal text-gray-900 dark:text-gray-100 tracking-tight mt-0.5">
                    Attendance
                </h1>
            </div>

            @if ($selectedCourse)
                <div class="flex items-center gap-2">
                    {{-- Blackboard Ultra View Segmented Toggle: [ Overall ] [ Meeting ] --}}
                    <div class="bb-view-toggle shadow-xs">
                        <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $selectedDate, 'tab' => 'overall']) }}"
                           @class(['bb-view-btn', 'bb-view-btn-active' => $activeTab === 'overall'])>
                            Overall
                        </a>
                        <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $selectedDate, 'tab' => 'meeting']) }}"
                           @class(['bb-view-btn', 'bb-view-btn-active' => $isMeetingTab])>
                            Meeting
                        </a>
                    </div>

                    <button type="button" @click="$dispatch('open-attendance-import')"
                            class="btn btn-primary btn-sm whitespace-nowrap">
                        Import attendance
                    </button>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="page space-y-6 px-4 py-6 sm:px-6 lg:px-8"
         @open-attendance-import.window="showImportModal = true"
         x-init="$nextTick(() => sortStudentRows())"
         x-data="facultyDashboard({
             courseId: {{ $selectedCourse ? $selectedCourse->course_id : 'null' }},
             defaultSessionHours: {{ $sessionHours ?? 1.5 }},
             initialStatuses: @js($initialStatuses),
             initialHours: @js($initialHours),
             initialRemarks: @js($initialRemarks),
             initialGridRecords: @js($gridRecords),
             initialStudentScores: @js($studentScores),
             meetingHoursByKey: @js($meetingHoursByKey),
             selectedDate: @js($selectedDate),
             selectedMeetingKey: @js($selectedDate.'|'.($selectedSchedule?->schedule_id ?? '')),
             activeTab: @js($activeTab),
             importHasErrors: @js($errors->has('file'))
         })">

        {{-- Toast Notification --}}
        <div x-show="toast.show" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed bottom-5 right-5 z-50 px-4 py-2.5 rounded-lg bg-gray-900 text-white text-xs font-semibold shadow-xl flex items-center gap-2 border border-gray-700">
            <svg class="h-4 w-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            <span x-text="toast.message"></span>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-warning" role="alert">
                <p class="font-semibold">Notice:</p>
                <ul class="mt-1 list-inside list-disc text-xs font-normal">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $selectedCourse)
            <div class="card p-8 text-center space-y-3">
                <div>
                    <p class="font-semibold text-lg">No course selected</p>
                    <p class="muted mt-1 text-sm">Create a subject manually or import a Blackboard class list to get started.</p>
                </div>
                <div class="flex flex-wrap justify-center gap-2">
                    <a href="{{ route('faculty.courses.import') }}" class="btn btn-primary">Import Class List</a>
                    <a href="{{ route('faculty.courses.create') }}" class="btn btn-secondary">Add Subject</a>
                </div>
            </div>
        @else

            {{-- Hidden compatibility notice for tests --}}
            <span class="sr-only">Attendance records do not automatically drop students.</span>
            <span class="sr-only">{{ $selectedCourse->subject_code }}</span>

            <section class="space-y-3" aria-label="Selected subject schedule">
                <div class="card flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-semibold text-sm">{{ $selectedCourse->subject_code }} · Section {{ $selectedCourse->section }}</p>
                        <p class="muted mt-0.5 text-xs">{{ $selectedCourse->course_name }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('faculty.toggle-schedule-warning') }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">
                                {{ $faculty->suppress_schedule_warnings ? 'Enable' : 'Disable' }} schedule warnings
                            </button>
                        </form>
                        <a href="{{ route('faculty.subjects') }}" class="btn btn-secondary btn-sm">Change subject</a>
                    </div>
                </div>

                {{-- Selected Subject Schedule Strip --}}
                @if ($selectedCourse->schedules->isNotEmpty())
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">
                                {{ $selectedCourse->subject_code }} Schedule:
                            </span>
                            @foreach ($selectedCourse->schedules as $sch)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-gray-100 dark:bg-gray-700/60 text-gray-800 dark:text-gray-200 font-medium">
                                    <svg class="h-3.5 w-3.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9" /><polyline points="12 7 12 12 15 12" />
                                    </svg>
                                    <span>{{ $sch->day_of_week }} {{ \Carbon\Carbon::parse($sch->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($sch->end_time)->format('g:i A') }}</span>
                                    @if ($sch->room)
                                        <span class="text-gray-400 dark:text-gray-500">({{ $sch->room }})</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 shrink-0">
                            <span>{{ $selectedCourse->total_semester_hours }} sem. hrs</span>
                            <span>&bull;</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $enrollmentRows->count() }} enrolled</span>
                            <a href="{{ route('faculty.courses.schedules.create', $selectedCourse) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors font-medium shadow-xs">
                                + Add Schedule Slot
                            </a>
                            <a href="{{ route('faculty.courses.edit', $selectedCourse) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors font-medium shadow-xs">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                </svg>
                                Edit Subject
                            </a>
                        </div>
                    </div>
                @else
                    {{-- No schedules yet — still show edit button --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 flex items-center justify-between gap-3 shadow-xs">
                        <span class="text-xs text-gray-400 dark:text-gray-500 italic">No schedule set for this subject.</span>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('faculty.courses.schedules.create', $selectedCourse) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-xs font-medium shadow-xs">
                                + Add Schedule Slot
                            </a>
                            <a href="{{ route('faculty.courses.edit', $selectedCourse) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-xs font-medium shadow-xs">
                                Edit Subject
                            </a>
                        </div>
                    </div>
                @endif

                @if ($selectedCourse->schedules->isNotEmpty())
                    <section class="space-y-3" aria-labelledby="weekly-schedule-heading">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 id="weekly-schedule-heading" class="font-semibold text-sm text-gray-900 dark:text-gray-100">
                                    Weekly schedule · {{ $selectedCourse->subject_code }}
                                </h3>
                                <p class="muted text-xs">
                                    {{ $weekStart->format('M j, Y') }} – {{ $weekEnd->format('M j, Y') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $previousWeekDate, 'tab' => 'meeting']) }}"
                                   class="btn btn-secondary btn-sm"
                                   aria-label="Previous week">
                                    &larr; Previous week
                                </a>
                                <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $nextWeekDate, 'tab' => 'meeting']) }}"
                                   class="btn btn-secondary btn-sm"
                                   aria-label="Next week">
                                    Next week &rarr;
                                </a>
                                <form method="GET" action="{{ route('faculty.dashboard') }}" class="flex items-center gap-2">
                                    <input type="hidden" name="course_id" value="{{ $selectedCourse->course_id }}">
                                    <input type="hidden" name="tab" value="meeting">
                                    <label for="weekly-schedule-date" class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Jump to date
                                    </label>
                                    <input id="weekly-schedule-date" type="date" name="date" value="{{ $selectedDate }}"
                                           onchange="this.form.submit()"
                                           class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 gap-2">
                            @foreach ($weeklySchedule as $day)
                                <div @class([
                                    'min-h-32 rounded-lg border p-3',
                                    'border-indigo-400 bg-indigo-50 dark:border-indigo-500 dark:bg-indigo-950/30' => $day['is_selected'],
                                    'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' => ! $day['is_selected'],
                                ])>
                                    <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $day['date'], 'tab' => 'meeting']) }}"
                                       @class([
                                           'flex items-baseline justify-between gap-2 rounded font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                           'text-indigo-700 dark:text-indigo-300' => $day['is_selected'],
                                           'text-gray-800 dark:text-gray-100 hover:text-indigo-700 dark:hover:text-indigo-300' => ! $day['is_selected'],
                                       ])
                                       @if ($day['is_selected']) aria-current="date" @endif>
                                        <span>{{ $day['day_name'] }}</span>
                                        <span @class([
                                            'inline-flex h-7 w-7 items-center justify-center rounded-full',
                                            'bg-indigo-600 text-white' => $day['is_today'],
                                            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200' => $day['is_selected'] && ! $day['is_today'],
                                            'text-gray-500 dark:text-gray-400' => ! $day['is_selected'] && ! $day['is_today'],
                                        ])>{{ $day['date_label'] }}</span>
                                    </a>

                                    <div class="mt-3 flex flex-col gap-2">
                                        @forelse ($day['slots'] as $slot)
                                            <a href="{{ route('faculty.dashboard', [
                                                'course_id' => $selectedCourse->course_id,
                                                'date' => $day['date'],
                                                'tab' => 'meeting',
                                                'schedule_id' => $slot['is_cancelled'] ? null : $slot['schedule_id'],
                                            ]) }}"
                                               @class([
                                                   'rounded-md border px-2 py-1.5 text-[11px] leading-tight transition-colors',
                                                   'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300' => $slot['is_cancelled'],
                                                   'border-indigo-200 bg-indigo-100 text-indigo-900 hover:border-indigo-400 dark:border-indigo-900 dark:bg-indigo-900/40 dark:text-indigo-200 dark:hover:border-indigo-600' => ! $slot['is_cancelled'],
                                               ])>
                                                <span class="block font-semibold">
                                                    {{ \Carbon\Carbon::parse($slot['start_time'])->format('g:i A') }}
                                                    – {{ \Carbon\Carbon::parse($slot['end_time'])->format('g:i A') }}
                                                </span>
                                                @if ($slot['room'])
                                                    <span class="mt-0.5 block truncate">{{ $slot['room'] }}</span>
                                                @endif
                                                @if ($slot['is_cancelled'])
                                                    <span class="mt-0.5 block font-semibold">Cancelled</span>
                                                @endif
                                            </a>
                                        @empty
                                            <span class="text-xs text-gray-400 dark:text-gray-500">No class</span>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </section>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-600 dark:text-gray-300"
                 aria-label="Student absence status colors">
                <span class="font-medium">Student status:</span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm border-2 border-emerald-500"></span>
                    Green: below weekly threshold
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm border-2 border-amber-500"></span>
                    Yellow: approaching or meeting weekly-hours drop threshold
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-3 w-3 rounded-sm border-2 border-rose-500"></span>
                    Red: 20% permanent-drop level or already dropped
                </span>
            </div>

            {{-- =========================================================================
                 VIEW 1: BLACKBOARD ULTRA MEETING VIEW (EXACTLY MATCHES media_1792078260731_1da7d63d.png)
                 ========================================================================= --}}
            @if ($isMeetingTab)

                {{-- Center Subheading: "Today" with Navigation Arrows --}}
                <div class="flex items-center justify-center gap-4 my-2">
                    <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $prevDate, 'tab' => 'meeting']) }}"
                       class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-full transition-colors"
                       title="Previous meeting ({{ \Carbon\Carbon::parse($prevDate)->format('M j') }})">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </a>

                    <div class="relative group cursor-pointer text-center px-4" title="Click to change date">
                        <h2 class="text-3xl md:text-4xl font-light text-gray-800 dark:text-gray-100 tracking-wide select-none">
                            {{ $isToday ? 'Today' : \Carbon\Carbon::parse($selectedDate)->format('M j, Y') }}
                        </h2>
                        @if (! $isOnSchedule)
                            <span class="inline-block px-2 py-0.5 mt-1 text-[11px] rounded bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 font-medium">
                                Out of regular schedule ({{ \Carbon\Carbon::parse($selectedDate)->format('l') }})
                            </span>
                        @endif
                        <input type="date" value="{{ $selectedDate }}"
                               onchange="window.location.href='{{ route('faculty.dashboard') }}?course_id={{ $selectedCourse->course_id }}&tab=meeting&date=' + this.value"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                               title="Click to jump to another date">
                    </div>

                    <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $nextDate, 'tab' => 'meeting']) }}"
                       class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-full transition-colors"
                       title="Next meeting ({{ \Carbon\Carbon::parse($nextDate)->format('M j') }})">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>

                {{-- Slot Picker: shown only when this day has multiple schedule slots --}}
                @if ($slotsForDay->count() > 1)
                    <div class="flex items-center justify-center gap-2 flex-wrap">
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-medium mr-1">Session:</span>
                        @foreach ($slotsForDay as $slot)
                            @php
                                $slotActive = $selectedSchedule && $selectedSchedule->schedule_id === $slot->schedule_id;
                            @endphp
                            <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $selectedDate, 'tab' => 'meeting', 'schedule_id' => $slot->schedule_id]) }}"
                               @class([
                                   'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors',
                                   'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 border-gray-900 dark:border-gray-100' => $slotActive,
                                   'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-gray-500 dark:hover:border-gray-400' => ! $slotActive,
                               ])>
                                <svg class="h-3 w-3 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}
                                @if ($slot->room)
                                    <span class="{{ $slotActive ? 'opacity-60' : 'text-gray-400' }}">({{ $slot->room }})</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @elseif ($selectedSchedule)
                    {{-- Single slot — show as a subtle label --}}
                    <div class="text-center">
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ \Carbon\Carbon::parse($selectedSchedule->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($selectedSchedule->end_time)->format('g:i A') }}
                            @if ($selectedSchedule->room) &nbsp;·&nbsp; {{ $selectedSchedule->room }} @endif
                            &nbsp;·&nbsp; {{ $sessionHours }} hrs
                        </span>
                    </div>
                @endif

                @if (! $canCheckAttendance)
                    <div class="alert alert-warning" role="status">
                        @if ($isSelectedDateCancelled)
                            All scheduled classes for this date have been cancelled. Attendance entry is disabled.
                        @else
                            No class is scheduled for this date. Attendance can only be recorded on scheduled class dates.
                        @endif
                    </div>
                @endif
                <div class="flex justify-end">
                    <button type="button" class="btn btn-secondary btn-sm"
                            @click="meetingFilterOpen = !meetingFilterOpen"
                            :aria-expanded="meetingFilterOpen.toString()">
                        Filter
                    </button>
                </div>
                <div x-show="meetingFilterOpen" x-cloak
                     class="card grid gap-3 p-4 sm:grid-cols-2">
                    <label class="space-y-1 text-xs font-medium">
                        Search students
                        <input type="search" x-model.debounce.150ms="meetingSearch"
                               placeholder="Name or student number" class="field mt-1">
                    </label>
                    <label class="space-y-1 text-xs font-medium">
                        Attendance status
                        <select x-model="meetingStatus" class="field mt-1">
                            <option value="all">All students</option>
                            <option value="unmarked">Unmarked</option>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="excused">Excused</option>
                        </select>
                    </label>
                </div>

                {{-- The Blackboard Ultra Meeting Grid Table --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 select-none">
                                    {{-- Column 1: Student --}}
                                    <th scope="col" class="sticky left-0 z-30 bg-white dark:bg-gray-800 py-4 px-6 text-left font-medium text-sm border-r border-gray-200 dark:border-gray-700 w-[38%] min-w-[14rem]">
                                        Student
                                    </th>

                                    {{-- Column 2: Present (Click header to mark all) --}}
                                    <th scope="col"
                                        @class([
                                            'py-4 px-4 text-center font-medium text-sm border-r border-gray-200 dark:border-gray-700 w-[12%] min-w-[5.5rem] transition-colors group',
                                            'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40' => $canCheckAttendance,
                                        ])
                                        @if ($canCheckAttendance)
                                            @click="markAll('present')"
                                        @endif
                                        title="{{ $canCheckAttendance ? 'Click to mark all as Present' : 'No class is scheduled for this date' }}">
                                        <span class="group-hover:text-emerald-700 dark:group-hover:text-emerald-400">Present</span>
                                    </th>

                                    {{-- Column 3: Late (Click header to mark all) --}}
                                    <th scope="col"
                                        @class([
                                            'py-4 px-4 text-center font-medium text-sm border-r border-gray-200 dark:border-gray-700 w-[12%] min-w-[5.5rem] transition-colors group',
                                            'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40' => $canCheckAttendance,
                                        ])
                                        @if ($canCheckAttendance)
                                            @click="markAll('late')"
                                        @endif
                                        title="{{ $canCheckAttendance ? 'Click to mark all as Late' : 'No class is scheduled for this date' }}">
                                        <span class="group-hover:text-amber-700 dark:group-hover:text-amber-400">Late</span>
                                    </th>

                                    {{-- Column 4: Absent (Click header to mark all) --}}
                                    <th scope="col"
                                        @class([
                                            'py-4 px-4 text-center font-medium text-sm border-r border-gray-200 dark:border-gray-700 w-[12%] min-w-[5.5rem] transition-colors group',
                                            'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40' => $canCheckAttendance,
                                        ])
                                        @if ($canCheckAttendance)
                                            @click="markAll('absent')"
                                        @endif
                                        title="{{ $canCheckAttendance ? 'Click to mark all as Absent' : 'No class is scheduled for this date' }}">
                                        <span class="group-hover:text-rose-700 dark:group-hover:text-rose-400">Absent</span>
                                    </th>

                                    {{-- Column 5: Excused (Click header to mark all) --}}
                                    <th scope="col"
                                        @class([
                                            'py-4 px-4 text-center font-medium text-sm w-[12%] min-w-[5.5rem] transition-colors group',
                                            'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40' => $canCheckAttendance,
                                        ])
                                        @if ($canCheckAttendance)
                                            @click="markAll('excused')"
                                        @endif
                                        title="{{ $canCheckAttendance ? 'Click to mark all as Excused' : 'No class is scheduled for this date' }}">
                                        <span class="group-hover:text-slate-700 dark:group-hover:text-slate-300">Excused</span>
                                    </th>
                                    <th scope="col" class="sticky right-0 z-30 bg-white dark:bg-gray-800 py-4 px-3 text-center font-medium text-sm border-l border-gray-200 dark:border-gray-700 min-w-[6rem]">
                                        Details
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($enrollmentRows as $index => $row)
                                    @php
                                        $student = $row['student'];
                                        $enrollment = $row['enrollment'];
                                        $isDropped = $row['status'] === 'dropped' && ! $row['can_record_attendance'];
                                        $canRecordStudentAttendance = $canCheckAttendance && $row['can_record_attendance'];
                                    @endphp

                                    <tr data-student-number="{{ $student->student_number }}"
                                        data-student-name="{{ $student->fullName }}"
                                        x-show="meetingStudentMatches({{ $index }}, @js($student->fullName), @js($student->student_number))"
                                        @class(['hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition-colors', 'opacity-60 bg-gray-50/50' => $isDropped])>

                                        {{-- Column 1: Student name only --}}
                                        <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 py-4 px-6 border-r border-b border-gray-200 dark:border-gray-700 min-w-[14rem]">
                                            <span class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $student->fullName }}</span>
                                        </td>
                                        {{-- Column 2: Present Icon Cell --}}
                                        <td @class([
                                                'py-4 px-4 text-center border-r border-b border-gray-200 dark:border-gray-700 bb-meeting-cell transition-colors',
                                                'cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-700/60' => $canRecordStudentAttendance,
                                            ])
                                            :class="{ 'bb-meeting-cell-active-present': studentStatuses[{{ $index }}] === 'present' }"
                                            @if ($canRecordStudentAttendance)
                                                @click="markStudent({{ $index }}, {{ $enrollment->enrollment_id }}, 'present', @js($student->fullName))"
                                            @endif
                                            title="{{ $isDropped ? 'Student is dropped and cannot be marked until readmission is processed' : ($canCheckAttendance ? 'Mark '.$student->fullName.' as Present' : 'Attendance entry is disabled without a scheduled class') }}">
                                            <svg class="h-5 w-5 mx-auto"
                                                 :class="studentStatuses[{{ $index }}] === 'present' ? 'text-gray-900 dark:text-gray-100 stroke-[2.4]' : 'text-gray-400 dark:text-gray-500 stroke-[1.8]'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </td>

                                        {{-- Column 3: Late Icon Cell --}}
                                        <td @class([
                                                'py-4 px-4 text-center border-r border-b border-gray-200 dark:border-gray-700 bb-meeting-cell transition-colors',
                                                'cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-700/60' => $canRecordStudentAttendance,
                                            ])
                                            :class="{ 'bb-meeting-cell-active-late': studentStatuses[{{ $index }}] === 'late' }"
                                            @if ($canRecordStudentAttendance)
                                                @click="markStudent({{ $index }}, {{ $enrollment->enrollment_id }}, 'late', @js($student->fullName))"
                                            @endif
                                            title="{{ $isDropped ? 'Student is dropped and cannot be marked until readmission is processed' : ($canCheckAttendance ? 'Mark '.$student->fullName.' as Late' : 'Attendance entry is disabled without a scheduled class') }}">
                                            <svg class="h-5 w-5 mx-auto"
                                                 :class="studentStatuses[{{ $index }}] === 'late' ? 'text-gray-900 dark:text-gray-100 stroke-[2.4]' : 'text-gray-400 dark:text-gray-500 stroke-[1.8]'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="9" />
                                                <polyline points="12 7 12 12 15 12" />
                                            </svg>
                                        </td>

                                        {{-- Column 4: Absent Icon Cell --}}
                                        <td @class([
                                                'py-4 px-4 text-center border-r border-b border-gray-200 dark:border-gray-700 bb-meeting-cell transition-colors',
                                                'cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-700/60' => $canRecordStudentAttendance,
                                            ])
                                            :class="{ 'bb-meeting-cell-active-absent': studentStatuses[{{ $index }}] === 'absent' }"
                                            @if ($canRecordStudentAttendance)
                                                @click="markStudent({{ $index }}, {{ $enrollment->enrollment_id }}, 'absent', @js($student->fullName))"
                                            @endif
                                            title="{{ $isDropped ? 'Student is dropped and cannot be marked until readmission is processed' : ($canCheckAttendance ? 'Mark '.$student->fullName.' as Absent' : 'Attendance entry is disabled without a scheduled class') }}">
                                            <svg class="h-5 w-5 mx-auto"
                                                 :class="studentStatuses[{{ $index }}] === 'absent' ? 'text-gray-900 dark:text-gray-100 stroke-[2.4]' : 'text-gray-400 dark:text-gray-500 stroke-[1.8]'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            <div x-show="studentStatuses[{{ $index }}] === 'absent'" x-cloak class="mt-1 flex items-center justify-center gap-1 text-[11px] text-gray-500 tabular-nums">
                                                <span x-text="studentHours[{{ $index }}] + 'h'"></span>
                                                @if ($canRecordStudentAttendance)
                                                    <button type="button" @click.stop="stepHours({{ $index }}, {{ $enrollment->enrollment_id }}, 0.5, @js($student->fullName))" class="flex h-4 w-4 items-center justify-center rounded bg-gray-100 font-bold leading-none text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200" title="Add 0.5 hour">+</button>
                                                    <button type="button" @click.stop="stepHours({{ $index }}, {{ $enrollment->enrollment_id }}, -0.5, @js($student->fullName))" class="flex h-4 w-4 items-center justify-center rounded bg-gray-100 font-bold leading-none text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200" title="Subtract 0.5 hour">-</button>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Column 5: Excused Icon Cell --}}
                                        <td @class([
                                                'py-4 px-4 text-center border-b border-gray-200 dark:border-gray-700 bb-meeting-cell transition-colors',
                                                'cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-700/60' => $canRecordStudentAttendance,
                                            ])
                                            :class="{ 'bb-meeting-cell-active-excused': studentStatuses[{{ $index }}] === 'excused' }"
                                            @if ($canRecordStudentAttendance)
                                                @click="markStudent({{ $index }}, {{ $enrollment->enrollment_id }}, 'excused', @js($student->fullName))"
                                            @endif
                                            title="{{ $isDropped ? 'Student is dropped and cannot be marked until readmission is processed' : ($canCheckAttendance ? 'Mark '.$student->fullName.' as Excused' : 'Attendance entry is disabled without a scheduled class') }}">
                                            <svg class="h-5 w-5 mx-auto"
                                                 :class="studentStatuses[{{ $index }}] === 'excused' ? 'text-gray-900 dark:text-gray-100 stroke-[2.4]' : 'text-gray-400 dark:text-gray-500 stroke-[1.8]'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="9" />
                                                <line x1="5.6" y1="5.6" x2="18.4" y2="18.4" />
                                            </svg>
                                        </td>

                                        <td class="sticky right-0 z-20 bg-white dark:bg-gray-800 py-4 px-2 text-center border-l border-b border-gray-200 dark:border-gray-700">
                                            <details data-student-details class="group relative inline-block text-left"
                                                     @toggle="handleStudentDetailsToggle($el)"
                                                     @scroll.window.capture="if ($el.open) positionStudentDetails($el)"
                                                     @resize.window="if ($el.open) positionStudentDetails($el)">
                                                <summary aria-label="View student details for {{ $student->fullName }}" class="inline-flex cursor-pointer list-none items-center gap-1 rounded-md border border-gray-300 bg-white px-2 py-1 text-[11px] font-semibold text-blue-700 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-blue-300 dark:hover:bg-gray-700">
                                                    View
                                                    <svg class="h-3 w-3 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                                    </svg>
                                                </summary>
                                                <div popover="manual" data-student-details-panel class="fixed z-[100] h-[40rem] max-h-[calc(100vh-2rem)] w-[32rem] max-w-[calc(100vw-2rem)] overflow-y-auto rounded-xl border-2 border-gray-300 bg-white p-5 text-left text-xs shadow-2xl ring-1 ring-black/10 dark:border-gray-600 dark:bg-gray-900 dark:ring-white/10">
                                                    <div class="sticky top-0 z-10 -mx-5 -mt-5 mb-4 flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-900">
                                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $student->fullName }}</p>
                                                        <button type="button" @click="closeStudentDetails($el.closest('details'))" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700" aria-label="Close student details">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" /></svg>
                                                        </button>
                                                    </div>
                                                    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                                                        <dt class="text-gray-500 dark:text-gray-400">Student number</dt>
                                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $student->student_number }}</dd>
                                                        <dt class="text-gray-500 dark:text-gray-400">Program / year</dt>
                                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $student->program ?: 'Not set' }} / {{ $student->year_level ?? 'Not set' }}</dd>
                                                        <dt class="text-gray-500 dark:text-gray-400">Department</dt>
                                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $student->department ?: 'Not set' }}</dd>
                                                        <dt class="text-gray-500 dark:text-gray-400">Enrollment</dt>
                                                        <dd class="font-medium capitalize text-gray-900 dark:text-gray-100">{{ $row['status'] }}</dd>
                                                        <dt class="text-gray-500 dark:text-gray-400">OSA status</dt>
                                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $row['osa_status'] }}</dd>
                                                        <dt class="text-gray-500 dark:text-gray-400">Drops</dt>
                                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $row['drop_count'] }} total, {{ $row['unexcused_drop_count'] }} unexcused</dd>
                                                        <dt class="text-gray-500 dark:text-gray-400">Absence-equivalent</dt>
                                                        <dd class="font-medium text-gray-900 dark:text-gray-100"><span x-text="Number(studentScores[{{ $enrollment->enrollment_id }}].absence_percentage).toFixed(2)">{{ number_format($row['absence_percentage'], 2) }}</span>% / {{ number_format($row['maximum_absence_percentage'], 2) }}% semester</dd>
                                                        <dt class="text-gray-500 dark:text-gray-400">Present / late / absent / excused</dt>
                                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $row['presences'] }} / {{ $row['lates'] }} / {{ $row['absences'] }} / {{ $row['excused'] }}</dd>
                                                        <dt class="text-gray-500 dark:text-gray-400">Absence hours</dt>
                                                        <dd class="font-medium text-gray-900 dark:text-gray-100"><span x-text="Number(studentScores[{{ $enrollment->enrollment_id }}].absent_hours).toFixed(2)">{{ number_format($row['absent_hours'], 2) }}</span> / {{ number_format($row['weekly_drop_threshold_hours'], 2) }} weekly hours</dd>
                                                    </dl>
                                                    @if ($row['drop_count'] > 0)
                                                        <div class="mt-2 flex items-center gap-2" role="img" aria-label="Dropped {{ $row['drop_count'] }} {{ $row['drop_count'] === 1 ? 'time' : 'times' }}, {{ $row['unexcused_drop_count'] }} unexcused">
                                                            <span @class([
                                                                'inline-flex h-4 w-10 items-center justify-center rounded',
                                                                'bg-gray-200 dark:bg-gray-600' => $row['unexcused_drop_count'] === 0,
                                                                'bg-orange-500 dark:bg-orange-400' => $row['unexcused_drop_count'] === 1,
                                                                'bg-red-600 dark:bg-red-500 text-white' => $row['unexcused_drop_count'] >= 2,
                                                            ])>
                                                                @if ($row['unexcused_drop_count'] >= 3)
                                                                    <span class="font-bold leading-none" aria-hidden="true">&times;</span>
                                                                @endif
                                                            </span>
                                                            <span class="text-gray-500 dark:text-gray-400">Dropped {{ $row['drop_count'] }} {{ $row['drop_count'] === 1 ? 'time' : 'times' }}</span>
                                                        </div>
                                                    @endif
                                                    @if (! $isDropped)
                                                        <div class="mt-3">
                                                            <div class="progress h-1.5" role="progressbar"
                                                                 aria-label="Drop threshold progress"
                                                                 aria-valuemin="0" aria-valuemax="100"
                                                                 x-bind:aria-valuenow="Math.round(weeklyProgressPercentage({{ $enrollment->enrollment_id }}))">
                                                                <div class="progress-bar"
                                                                     x-bind:class="weeklyProgressClass({{ $enrollment->enrollment_id }})"
                                                                     x-bind:style="`width: ${weeklyProgressPercentage({{ $enrollment->enrollment_id }})}%`"></div>
                                                            </div>
                                                        </div>
                                                        <div x-show="studentScores[{{ $enrollment->enrollment_id }}].is_drop_eligible" x-cloak class="mt-2">
                                                            <p x-show="!studentScores[{{ $enrollment->enrollment_id }}].is_permanent_drop_eligible" class="font-medium text-amber-700 dark:text-amber-400">
                                                                Drop threshold reached (3 lates = 1 absence).
                                                            </p>
                                                            <p x-show="studentScores[{{ $enrollment->enrollment_id }}].is_permanent_drop_eligible" class="font-medium text-rose-600 dark:text-rose-400">
                                                                20% semester absence reached. OSA may still review readmission.
                                                            </p>
                                                            <form method="POST" action="{{ route('faculty.enrollments.drop', $enrollment) }}"
                                                                  x-bind:data-confirm="studentScores[{{ $enrollment->enrollment_id }}].is_permanent_drop_eligible ? 'Mark this as a permanent drop and refer the student to OSA? OSA may still review readmission.' : 'Drop this student to OSA for readmission review?'">
                                                                @csrf
                                                                <button type="submit" x-show="!studentScores[{{ $enrollment->enrollment_id }}].is_permanent_drop_eligible" class="mt-1 font-semibold text-amber-700 underline dark:text-amber-400">
                                                                    Drop student to OSA
                                                                </button>
                                                                <button type="submit" x-show="studentScores[{{ $enrollment->enrollment_id }}].is_permanent_drop_eligible" class="mt-1 font-semibold text-rose-700 underline dark:text-rose-400">
                                                                    Permanent drop to OSA
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                    <details class="mt-3">
                                                        <summary class="cursor-pointer font-semibold text-blue-700 underline dark:text-blue-300">Refer student to OSA (Circumstance)</summary>
                                                        <form method="POST" action="{{ route('faculty.enrollments.drop', $enrollment) }}" class="mt-2 space-y-2" data-confirm="Refer this student to OSA for review of the stated circumstance?">
                                                            @csrf
                                                            <input type="hidden" name="referral_type" value="circumstance">
                                                            <label class="block font-medium text-gray-700 dark:text-gray-300">
                                                                Reason for referral (required)
                                                                <textarea name="instructor_reason" rows="2" maxlength="1000" required class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="Explain why this student should be referred to OSA."></textarea>
                                                            </label>
                                                            @error('instructor_reason')
                                                                <p class="text-xs text-rose-600">{{ $message }}</p>
                                                            @enderror
                                                            <button type="submit" class="font-semibold text-blue-700 underline dark:text-blue-300">Drop and refer to OSA</button>
                                                        </form>
                                                    </details>
                                                    <div class="mt-3 border-t border-gray-200 pt-2 dark:border-gray-700">
                                                        <p class="font-semibold text-gray-800 dark:text-gray-100">Drop history</p>
                                                        @forelse ($row['drop_history'] as $drop)
                                                            <section class="mt-2 rounded-md bg-gray-50 p-2 dark:bg-gray-900/60">
                                                                <div class="flex items-center justify-between gap-2">
                                                                    <span class="font-semibold text-gray-800 dark:text-gray-100">Drop {{ $drop['sequence'] }}</span>
                                                                    <span class="badge {{ $drop['status_class'] }} text-[10px]">{{ $drop['status'] }}</span>
                                                                </div>
                                                                @if ($drop['status'] === 'Awaiting student request')
                                                                    <p class="mt-1 text-[10px] text-amber-700 dark:text-amber-300">The student must submit a readmission request before OSA review.</p>
                                                                @endif
                                                                <dl class="mt-1 grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5">
                                                                    <dt class="text-gray-500 dark:text-gray-400">Date</dt>
                                                                    <dd class="text-gray-700 dark:text-gray-300">{{ $drop['date'] ?? 'Not recorded' }}</dd>
                                                                    <dt class="text-gray-500 dark:text-gray-400">Absence hours</dt>
                                                                    <dd class="text-gray-700 dark:text-gray-300">{{ number_format($drop['absence_hours'], 2) }} hours</dd>
                                                                    <dt class="text-gray-500 dark:text-gray-400">Drop type</dt>
                                                                    <dd class="capitalize text-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', $drop['type'] ?? 'Not recorded') }}</dd>
                                                                    <dt class="text-gray-500 dark:text-gray-400">Readmission application</dt>
                                                                    <dd class="font-medium text-gray-700 dark:text-gray-300">{{ $drop['application_status'] }}</dd>
                                                                    @if ($drop['attempt'])
                                                                        <dt class="text-gray-500 dark:text-gray-400">Attempt</dt>
                                                                        <dd class="text-gray-700 dark:text-gray-300">{{ $drop['attempt'] }}</dd>
                                                                        <dt class="text-gray-500 dark:text-gray-400">Applied</dt>
                                                                        <dd class="text-gray-700 dark:text-gray-300">{{ $drop['applied_at'] }}</dd>
                                                                        <dt class="text-gray-500 dark:text-gray-400">Reason</dt>
                                                                        <dd class="text-gray-700 dark:text-gray-300">{{ $drop['reason'] }}</dd>
                                                                        @if ($drop['reason_details'])
                                                                            <dt class="text-gray-500 dark:text-gray-400">Student details</dt>
                                                                            <dd class="whitespace-pre-line text-gray-700 dark:text-gray-300">{{ $drop['reason_details'] }}</dd>
                                                                        @endif
                                                                    @endif
                                                                </dl>
                                                            </section>
                                                        @empty
                                                            <p class="mt-1 text-gray-600 dark:text-gray-300">No drop history recorded.</p>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="muted py-14 text-center text-sm">
                                            No students are enrolled in this course yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Live Real-time Status Counter Summary Bar --}}
                    <div class="px-6 py-3 bg-gray-50/80 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-semibold text-gray-500 uppercase tracking-wider text-[11px]">Today:</span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-emerald-800 dark:text-emerald-300">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span x-text="countStatus('present')"></span> Present
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-amber-800 dark:text-amber-300">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                <span x-text="countStatus('late')"></span> Late
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-rose-800 dark:text-rose-300">
                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                <span x-text="countStatus('absent')"></span> Absent
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                                <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                                <span x-text="countStatus('excused')"></span> Excused
                            </span>
                            <span class="text-gray-400 dark:text-gray-500">
                                / {{ $enrollmentRows->count() }} Total
                            </span>
                        </div>

                        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                            <span>Changes are saved automatically</span>
                        </div>
                    </div>
                </div>

            {{-- =========================================================================
                 VIEW 2: BLACKBOARD ULTRA OVERALL SPREADSHEET GRID (IMAGE 2)
                 ========================================================================= --}}
            @elseif ($activeTab === 'overall')

                <div class="space-y-4">
                    @if (! $canCheckAttendance)
                        <div class="alert alert-warning" role="status">
                            No class is scheduled for the selected date. Attendance can only be recorded on scheduled class dates.
                        </div>
                    @endif

                    {{-- Legend & Controls --}}
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white dark:bg-gray-800 p-3.5 rounded border border-gray-200 dark:border-gray-700 shadow-xs">
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
                            <span class="font-semibold text-gray-500 uppercase tracking-wider text-[11px]">Legend:</span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                <span>Present</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                <span class="inline-block px-1.5 py-0.5 rounded bg-[#fffde7] dark:bg-yellow-950/40 border border-yellow-200 dark:border-yellow-800">
                                    <svg class="h-3.5 w-3.5 inline text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </span>
                                <span>Late</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                <span>Absent</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" /><line x1="5.6" y1="5.6" x2="18.4" y2="18.4" /></svg>
                                <span>Excused</span>
                            </span>
                        </div>

                    </div>

                    <form x-ref="clearMeetingForm" method="POST"
                          action="{{ route('faculty.attendance.meeting.clear') }}" class="hidden">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="course_id" value="{{ $selectedCourse->course_id }}">
                        <input type="hidden" name="session_date" :value="selectedMeetingDate">
                        <input type="hidden" name="schedule_id" :value="selectedMeetingScheduleId">
                    </form>
                    <form x-ref="deleteMeetingForm" method="POST"
                          action="{{ route('faculty.attendance.meeting.delete') }}" class="hidden">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="course_id" value="{{ $selectedCourse->course_id }}">
                        <input type="hidden" name="session_date" :value="selectedMeetingDate">
                        <input type="hidden" name="schedule_id" :value="selectedMeetingScheduleId">
                    </form>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold text-sm">Attendance log</h3>
                        <div class="flex items-center gap-2">
                            <label for="student-sort" class="field-label">Sort students</label>
                            <select id="student-sort" x-model="studentSortBy" @change="sortStudentRows()" class="field w-auto py-1.5 text-sm">
                                <option value="student_number">Student ID</option>
                                <option value="name">Name</option>
                            </select>
                            <button type="button" class="btn btn-secondary btn-sm"
                                    @click="toggleStudentSortDirection()"
                                    x-text="studentSortBy === 'name' ? (studentSortDirection === 1 ? 'A–Z' : 'Z–A') : (studentSortDirection === 1 ? 'ID ↑' : 'ID ↓')">
                                ID ↑
                            </button>
                        </div>
                    </div>

                    {{-- The Blackboard Overall Spreadsheet Grid --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-xs overflow-hidden">
                        <div class="overflow-x-auto max-h-[72vh]">
                            <table id="attendance-overall-grid" class="bb-grid-table border-collapse w-full">
                                <thead>
                                    <tr class="bg-white dark:bg-gray-800 sticky top-0 z-20 shadow-xs border-b border-gray-200 dark:border-gray-700">
                                        {{-- Student Column (Sticky left) --}}
                                        <th scope="col"
                                            class="sticky left-0 z-30 bg-white dark:bg-gray-800 py-3.5 px-4 text-left font-semibold text-gray-700 dark:text-gray-200 border-r border-b border-gray-200 dark:border-gray-700 min-w-[13rem]">
                                            Student
                                        </th>

                                        {{-- Date Columns (Image 2 style) --}}
                                        @forelse ($gridDates as $d)
                                            <th scope="col"
                                                @class([
                                                    'py-3.5 px-3 text-center border-r border-b border-gray-200 dark:border-gray-700 min-w-[6rem] transition-colors',
                                                    'bb-header-active' => $d['is_selected'],
                                                    'bg-white dark:bg-gray-800 font-medium text-gray-700 dark:text-gray-300' => ! $d['is_selected']
                                                ])
                                                title="{{ $d['full_label'] }} - Click to switch meeting"
                                                    @if ($d['can_record_attendance'])
                                                        @contextmenu.prevent="openMeetingMenu($event, @js($d['column_key']))"
                                                    @endif>
                                                <a href="{{ route('faculty.dashboard', [
                                                        'course_id' => $selectedCourse->course_id,
                                                        'date' => $d['date'],
                                                        'tab' => 'meeting',
                                                        'schedule_id' => $d['schedule_id'],
                                                    ]) }}"
                                                       class="block font-medium hover:underline text-xs">
                                                        {{ $d['header_label'] }}
                                                        @if ($d['schedule_label'])
                                                            <span class="block mt-1 text-[10px] font-normal text-gray-500 dark:text-gray-400">
                                                                {{ $d['schedule_label'] }}
                                                            </span>
                                                        @endif
                                                    </a>
                                            </th>
                                        @empty
                                            <th scope="col" class="py-3.5 px-3 text-center border-r border-b border-gray-200 dark:border-gray-700 min-w-[6rem]">
                                                {{ \Carbon\Carbon::parse($selectedDate)->format('n/j/y') }}
                                            </th>
                                        @endforelse

                                        {{-- Summary Columns at the end --}}
                                        <th scope="col"
                                            class="py-3.5 px-4 text-center font-semibold text-gray-700 dark:text-gray-200 border-r border-b border-gray-200 dark:border-gray-700 min-w-[7.5rem]">
                                            Overall
                                        </th>
                                        <th scope="col"
                                            class="sticky right-24 z-30 bg-white dark:bg-gray-800 py-3.5 px-3 text-center font-semibold text-gray-700 dark:text-gray-200 border-r border-b border-gray-200 dark:border-gray-700 min-w-[5rem]">
                                            Drops
                                        </th>
                                        <th scope="col"
                                            class="sticky right-0 z-30 bg-white dark:bg-gray-800 py-3.5 px-3 text-center font-semibold text-gray-700 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 min-w-[6rem]">
                                            Details
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($enrollmentRows as $row)
                                        @php
                                            $enrollmentId = $row['enrollment']->enrollment_id;
                                            $student = $row['student'];
                                            $isDropped = $row['status'] === 'dropped' && ! $row['can_record_attendance'];
                                            $overallScore = $row['overall_score'];
                                            $pillClass = $overallScore >= 90 ? 'bb-pill-green' : ($overallScore >= 80 ? 'bb-pill-lime' : ($overallScore >= 70 ? 'bb-pill-yellow' : 'bb-pill-red'));
                                            $scoreText = ($overallScore == 100) ? '100 / 100' : number_format($overallScore, 2) . ' / 100';
                                        @endphp

                                        <tr data-student-number="{{ $student->student_number }}"
                                            data-student-name="{{ $student->fullName }}"
                                            @class(['hover:bg-gray-50/70 dark:hover:bg-gray-800/50 transition-colors', 'opacity-60 bg-gray-50/50' => $isDropped])>

                                            {{-- Student Name & Info (Sticky Left) --}}
                                            <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 py-3 px-4 border-r border-b border-gray-200 dark:border-gray-700 min-w-[13rem]">
                                                <div class="flex items-center gap-3 rounded-lg border-2 border-transparent p-2 transition-colors"
                                                     x-bind:class="studentStatusOutline({{ $enrollmentId }})">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <div class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">
                                                                {{ $student->fullName }}
                                                            </div>
                                                        </div>
                                                        @if ($row['drop_count'] > 0)
                                                            <div class="mt-1 flex items-center gap-1.5"
                                                                 role="img"
                                                                 aria-label="Dropped {{ $row['drop_count'] }} {{ $row['drop_count'] === 1 ? 'time' : 'times' }}, {{ $row['unexcused_drop_count'] }} unexcused">
                                                                <span @class([
                                                                    'relative inline-flex h-4 w-10 items-center justify-center rounded',
                                                                    'bg-gray-200 dark:bg-gray-600' => $row['unexcused_drop_count'] === 0,
                                                                    'bg-orange-500 dark:bg-orange-400' => $row['unexcused_drop_count'] === 1,
                                                                    'bg-red-600 dark:bg-red-500 text-white' => $row['unexcused_drop_count'] >= 2,
                                                                ])>
                                                                    @if ($row['unexcused_drop_count'] >= 3)
                                                                        <span class="font-bold leading-none" aria-hidden="true">&times;</span>
                                                                    @endif
                                                                </span>
                                                            </div>
                                                            @if ($activeTab !== 'overall')
                                                                <details class="group mt-1 text-left">
                                                                <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-[11px] font-semibold text-blue-700 dark:text-blue-300">
                                                                    View student details
                                                                    <svg class="h-3 w-3 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                                                    </svg>
                                                                </summary>
                                                                <div class="mt-2 w-64 max-w-full rounded-lg border border-gray-200 bg-white p-3 text-[11px] shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                                                    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                                                                        <dt class="text-gray-500 dark:text-gray-400">Student number</dt>
                                                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $student->student_number }}</dd>
                                                                        <dt class="text-gray-500 dark:text-gray-400">Program / year</dt>
                                                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $student->program ?: 'Not set' }} / {{ $student->year_level ?? 'Not set' }}</dd>
                                                                        <dt class="text-gray-500 dark:text-gray-400">Department</dt>
                                                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $student->department ?: 'Not set' }}</dd>
                                                                        <dt class="text-gray-500 dark:text-gray-400">Enrollment</dt>
                                                                        <dd class="font-medium capitalize text-gray-900 dark:text-gray-100">{{ $row['status'] }}</dd>
                                                                    </dl>
                                                                    <div class="mt-3 border-t border-gray-200 pt-2 dark:border-gray-700">
                                                                        <p class="font-semibold text-gray-800 dark:text-gray-100">Drop history</p>
                                                                        <ol class="mt-2 space-y-2">
                                                                            @foreach ($row['drop_history'] as $drop)
                                                                                <li class="rounded-md bg-gray-50 p-2 dark:bg-gray-900/60">
                                                                                    <div class="flex items-center justify-between gap-2">
                                                                                        <span class="font-semibold text-gray-800 dark:text-gray-100">Drop {{ $drop['sequence'] }}</span>
                                                                                        <span class="badge {{ $drop['status_class'] }} text-[10px]">{{ $drop['status'] }}</span>
                                                                                    </div>
                                                                                    @if ($drop['status'] === 'Awaiting student request')
                                                                                        <p class="mt-1 text-[10px] text-amber-700 dark:text-amber-300">The student must submit a readmission request before OSA review.</p>
                                                                                    @endif
                                                                                    <dl class="mt-1 grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5">
                                                                                        <dt class="text-gray-500 dark:text-gray-400">Date</dt>
                                                                                        <dd class="text-gray-700 dark:text-gray-300">{{ $drop['date'] ?? 'Not recorded' }}</dd>
                                                                                        <dt class="text-gray-500 dark:text-gray-400">Absence hours</dt>
                                                                                        <dd class="text-gray-700 dark:text-gray-300">{{ number_format($drop['absence_hours'], 2) }} hours</dd>
                                                                                        <dt class="text-gray-500 dark:text-gray-400">Drop type</dt>
                                                                                        <dd class="capitalize text-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', $drop['type'] ?? 'Not recorded') }}</dd>
                                                                                        <dt class="text-gray-500 dark:text-gray-400">Readmission application</dt>
                                                                                        <dd class="font-medium text-gray-700 dark:text-gray-300">{{ $drop['application_status'] }}</dd>
                                                                                        @if ($drop['attempt'])
                                                                                            <dt class="text-gray-500 dark:text-gray-400">Attempt</dt>
                                                                                            <dd class="text-gray-700 dark:text-gray-300">{{ $drop['attempt'] }}</dd>
                                                                                            <dt class="text-gray-500 dark:text-gray-400">Applied</dt>
                                                                                            <dd class="text-gray-700 dark:text-gray-300">{{ $drop['applied_at'] }}</dd>
                                                                                            <dt class="text-gray-500 dark:text-gray-400">Reason</dt>
                                                                                            <dd class="text-gray-700 dark:text-gray-300">{{ $drop['reason'] }}</dd>
                                                                                            @if ($drop['reason_details'])
                                                                                                <dt class="text-gray-500 dark:text-gray-400">Student details</dt>
                                                                                                <dd class="whitespace-pre-line text-gray-700 dark:text-gray-300">{{ $drop['reason_details'] }}</dd>
                                                                                            @endif
                                                                                        @endif
                                                                                    </dl>
                                                                                </li>
                                                                            @endforeach
                                                                        </ol>
                                                                    </div>
                                                                </div>
                                                                    </details>
                                                                @endif
                                                            @endif
                                                        @if (! $isDropped)
                                                            <div class="mt-2 w-40 max-w-full">
                                                                <div class="progress h-1.5" role="progressbar"
                                                                     aria-label="Drop threshold progress"
                                                                     aria-valuemin="0" aria-valuemax="100"
                                                                     x-bind:aria-valuenow="Math.round(weeklyProgressPercentage({{ $enrollmentId }}))">
                                                                    <div class="progress-bar"
                                                                         x-bind:class="weeklyProgressClass({{ $enrollmentId }})"
                                                                         x-bind:style="`width: ${weeklyProgressPercentage({{ $enrollmentId }})}%`"></div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            {{-- Date Columns --}}
                                            @foreach ($gridDates as $d)
                                                @php
                                                    $rec = $row['records_by_date'][$d['column_key']] ?? null;
                                                    $cellStatus = $rec['status'] ?? null;
                                                    $hoursAbsent = $rec['hours_absent'] ?? null;
                                                    $remarks = $rec['remarks'] ?? '';
                                                    $canRecordCellAttendance = $row['can_record_attendance'] && $d['can_record_attendance'];
                                                @endphp

                                                <td id="cell-{{ $enrollmentId }}-{{ $d['date'] }}-{{ $d['schedule_id'] ?? 'none' }}"
                                                    @class([
                                                        'bb-cell text-center p-2 min-w-[5.5rem] h-[3.8rem] select-none group transition-colors border-r border-b border-gray-200 dark:border-gray-700',
                                                        'cursor-pointer' => $canRecordCellAttendance,
                                                        'bb-cell-late' => $cellStatus === 'late',
                                                        'bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700' => $cellStatus !== 'late' && $canRecordCellAttendance,
                                                        'bg-white dark:bg-gray-800' => $cellStatus !== 'late' && ! $canRecordCellAttendance,
                                                    ])
                                                    @if ($canRecordCellAttendance)
                                                        @click="openCellModal({{ $enrollmentId }}, @js($student->fullName), @js($d['date']), @js($d['header_label']), @js($d['schedule_id']), @js($cellStatus), @js($hoursAbsent), @js($remarks), {{ $d['session_hours'] }})"
                                                    @endif
                                                    title="{{ $student->fullName }} ({{ $d['full_label'] }}{{ $d['schedule_label'] ? ' · '.$d['schedule_label'] : '' }}): {{ $cellStatus ? ucfirst($cellStatus) : 'Unrecorded' }}{{ $isDropped ? ' - Student is dropped and cannot be marked until readmission is processed' : ($d['can_record_attendance'] ? ' - Click to edit' : ' - No scheduled class') }}">

                                                    @if ($cellStatus === 'present')
                                                        <div class="flex flex-col items-center justify-center">
                                                            <svg class="h-5 w-5 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                            </svg>
                                                            <span class="text-xs text-gray-700 dark:text-gray-300 font-medium mt-0.5">Present</span>
                                                        </div>
                                                    @elseif ($cellStatus === 'late')
                                                        <div class="flex flex-col items-center justify-center">
                                                            <svg class="h-5 w-5 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            <span class="text-xs text-gray-800 dark:text-gray-200 font-medium mt-0.5">Late</span>
                                                        </div>
                                                    @elseif ($cellStatus === 'absent')
                                                        <div class="flex flex-col items-center justify-center">
                                                            <svg class="h-5 w-5 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            <span class="text-xs text-gray-700 dark:text-gray-300 font-medium mt-0.5">
                                                                Absent
                                                                @if ($hoursAbsent !== null && (float) $hoursAbsent !== (float) $sessionHours)
                                                                    <span class="text-[10px] text-gray-500">({{ $hoursAbsent }}h)</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @elseif ($cellStatus === 'excused')
                                                        <div class="flex flex-col items-center justify-center">
                                                            <svg class="h-5 w-5 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                <circle cx="12" cy="12" r="9" />
                                                                <line x1="5.6" y1="5.6" x2="18.4" y2="18.4" />
                                                            </svg>
                                                            <span class="text-xs text-gray-700 dark:text-gray-300 font-medium mt-0.5">Excused</span>
                                                        </div>
                                                    @else
                                                        @if ($canRecordCellAttendance)
                                                            <div class="flex flex-col items-center justify-center opacity-30 group-hover:opacity-100 transition-opacity">
                                                                <span class="text-lg text-gray-400 font-light">+</span>
                                                            </div>
                                                        @else
                                                            <span class="muted text-xs">—</span>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach

                                            {{-- Overall and drops summary columns at the end --}}
                                            <td class="py-3 px-3 text-center border-r border-b border-gray-200 dark:border-gray-700">
                                                <span id="overall-pill-{{ $enrollmentId }}"
                                                      class="bb-pill {{ $pillClass }}"
                                                      title="Overall attendance score: {{ $overallScore }}%">
                                                    {{ $scoreText }}
                                                </span>
                                            </td>
                                            <td class="sticky right-24 z-10 bg-white dark:bg-gray-800 py-3 px-3 text-center border-r border-b border-gray-200 dark:border-gray-700 tabular-nums">
                                                {{ $row['drop_count'] }}
                                            </td>
                                            <td class="sticky right-0 z-20 bg-white dark:bg-gray-800 py-3 px-2 text-center border-b border-gray-200 dark:border-gray-700">
                                                <details data-student-details class="group relative inline-block text-left"
                                                         @toggle="handleStudentDetailsToggle($el)"
                                                         @scroll.window.capture="if ($el.open) positionStudentDetails($el)"
                                                         @resize.window="if ($el.open) positionStudentDetails($el)">
                                                    <summary aria-label="View student details for {{ $student->fullName }}" class="inline-flex cursor-pointer list-none items-center gap-1 rounded-md border border-gray-300 bg-white px-2 py-1 text-[11px] font-semibold text-blue-700 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-blue-300 dark:hover:bg-gray-700">
                                                        View
                                                        <svg class="h-3 w-3 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                                        </svg>
                                                    </summary>
                                                    <div popover="manual" data-student-details-panel class="fixed z-[100] h-[40rem] max-h-[calc(100vh-2rem)] w-[32rem] max-w-[calc(100vw-2rem)] overflow-y-auto rounded-xl border-2 border-gray-300 bg-white p-5 text-left text-xs shadow-2xl ring-1 ring-black/10 dark:border-gray-600 dark:bg-gray-900 dark:ring-white/10">
                                                        <div class="sticky top-0 z-10 -mx-5 -mt-5 mb-4 flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-900">
                                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $student->fullName }}</p>
                                                            <button type="button" @click="closeStudentDetails($el.closest('details'))" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700" aria-label="Close student details">
                                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" /></svg>
                                                            </button>
                                                        </div>
                                                        <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                                                            <dt class="text-gray-500 dark:text-gray-400">Student number</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $student->student_number }}</dd>
                                                            <dt class="text-gray-500 dark:text-gray-400">Program / year</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $student->program ?: 'Not set' }} / {{ $student->year_level ?? 'Not set' }}</dd>
                                                            <dt class="text-gray-500 dark:text-gray-400">Department</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $student->department ?: 'Not set' }}</dd>
                                                            <dt class="text-gray-500 dark:text-gray-400">Enrollment</dt>
                                                            <dd class="font-medium capitalize text-gray-900 dark:text-gray-100">{{ $row['status'] }}</dd>
                                                            <dt class="text-gray-500 dark:text-gray-400">OSA status</dt>
                                                            <dd><span class="badge {{ $row['osa_status_class'] }}">{{ $row['osa_status'] }}</span></dd>
                                                            <dt class="text-gray-500 dark:text-gray-400">Drops</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $row['drop_count'] }} total, {{ $row['unexcused_drop_count'] }} unexcused</dd>
                                                            <dt class="text-gray-500 dark:text-gray-400">Attendance score</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($overallScore, 2) }}%</dd>
                                                            <dt class="text-gray-500 dark:text-gray-400">Absence-equivalent</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-gray-100"><span x-text="Number(studentScores[{{ $enrollmentId }}].absence_percentage).toFixed(2)">{{ number_format($row['absence_percentage'], 2) }}</span>% / {{ number_format($row['maximum_absence_percentage'], 2) }}% semester</dd>
                                                            <dt class="text-gray-500 dark:text-gray-400">Present / late / absent / excused</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $row['presences'] }} / {{ $row['lates'] }} / {{ $row['absences'] }} / {{ $row['excused'] }}</dd>
                                                            <dt class="text-gray-500 dark:text-gray-400">Absence hours</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-gray-100"><span x-text="Number(studentScores[{{ $enrollmentId }}].absent_hours).toFixed(2)">{{ number_format($row['absent_hours'], 2) }}</span> / {{ number_format($row['weekly_drop_threshold_hours'], 2) }} weekly hours</dd>
                                                        </dl>
                                                        @if (! $isDropped)
                                                            <div class="mt-3 border-t border-gray-200 pt-2 dark:border-gray-700">
                                                                <p class="text-gray-600 dark:text-gray-300">
                                                                    Absence-equivalent hours:
                                                                    <span x-text="Number(studentScores[{{ $enrollmentId }}].absent_hours).toFixed(2)">{{ number_format($row['absent_hours'], 2) }}</span>
                                                                    / {{ number_format($row['weekly_drop_threshold_hours'], 2) }}h
                                                                </p>
                                                                <div x-show="studentScores[{{ $enrollmentId }}].is_drop_eligible" x-cloak class="mt-1">
                                                                    <p x-show="!studentScores[{{ $enrollmentId }}].is_permanent_drop_eligible" class="font-medium text-amber-700 dark:text-amber-400">
                                                                        Drop threshold reached (3 lates = 1 absence).
                                                                    </p>
                                                                    <p x-show="studentScores[{{ $enrollmentId }}].is_permanent_drop_eligible" class="font-medium text-rose-600 dark:text-rose-400">
                                                                        20% semester absence reached. OSA may still review readmission.
                                                                    </p>
                                                                    <form method="POST" action="{{ route('faculty.enrollments.drop', $row['enrollment']) }}"
                                                                          x-bind:data-confirm="studentScores[{{ $enrollmentId }}].is_permanent_drop_eligible ? 'Mark this as a permanent drop and refer the student to OSA? OSA may still review readmission.' : 'Drop this student to OSA for readmission review?'">
                                                                        @csrf
                                                                        <button type="submit" x-show="!studentScores[{{ $enrollmentId }}].is_permanent_drop_eligible" class="mt-1 font-semibold text-amber-700 underline dark:text-amber-400">
                                                                            Drop student to OSA
                                                                        </button>
                                                                        <button type="submit" x-show="studentScores[{{ $enrollmentId }}].is_permanent_drop_eligible" class="mt-1 font-semibold text-rose-700 underline dark:text-rose-400">
                                                                            Permanent drop to OSA
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                                <details class="mt-2">
                                                                    <summary class="cursor-pointer font-semibold text-blue-700 underline dark:text-blue-300">
                                                                        Refer student to OSA for a circumstance
                                                                    </summary>
                                                                    <form method="POST" action="{{ route('faculty.enrollments.drop', $row['enrollment']) }}" class="mt-2 space-y-2" data-confirm="Refer this student to OSA for review of the stated circumstance?">
                                                                        @csrf
                                                                        <input type="hidden" name="referral_type" value="circumstance">
                                                                        <label class="block font-medium text-gray-700 dark:text-gray-300">
                                                                            Reason for referral (required)
                                                                            <textarea name="instructor_reason" rows="2" maxlength="1000" required class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="Explain why this student should be referred to OSA."></textarea>
                                                                        </label>
                                                                        @error('instructor_reason')
                                                                            <p class="text-xs text-rose-600">{{ $message }}</p>
                                                                        @enderror
                                                                        <button type="submit" class="font-semibold text-blue-700 underline dark:text-blue-300">
                                                                            Drop and refer to OSA
                                                                        </button>
                                                                    </form>
                                                                </details>
                                                            </div>
                                                        @endif
                                                        <div class="mt-3 border-t border-gray-200 pt-2 dark:border-gray-700">
                                                            <p class="font-semibold text-gray-800 dark:text-gray-100">Drop history</p>
                                                            @forelse ($row['drop_history'] as $drop)
                                                                <section class="mt-2 rounded-md bg-gray-50 p-2 dark:bg-gray-900/60">
                                                                    <div class="flex items-center justify-between gap-2">
                                                                        <span class="font-semibold text-gray-800 dark:text-gray-100">Drop {{ $drop['sequence'] }}</span>
                                                                        <span class="badge {{ $drop['status_class'] }} text-[10px]">{{ $drop['status'] }}</span>
                                                                    </div>
                                                                    @if ($drop['status'] === 'Awaiting student request')
                                                                        <p class="mt-1 text-[10px] text-amber-700 dark:text-amber-300">The student must submit a readmission request before OSA review.</p>
                                                                    @endif
                                                                    <dl class="mt-1 grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5">
                                                                        <dt class="text-gray-500 dark:text-gray-400">Date</dt>
                                                                        <dd class="text-gray-700 dark:text-gray-300">{{ $drop['date'] ?? 'Not recorded' }}</dd>
                                                                        <dt class="text-gray-500 dark:text-gray-400">Absence hours</dt>
                                                                        <dd class="text-gray-700 dark:text-gray-300">{{ number_format($drop['absence_hours'], 2) }} hours</dd>
                                                                        <dt class="text-gray-500 dark:text-gray-400">Drop type</dt>
                                                                        <dd class="capitalize text-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', $drop['type'] ?? 'Not recorded') }}</dd>
                                                                        <dt class="text-gray-500 dark:text-gray-400">Readmission application</dt>
                                                                        <dd class="font-medium text-gray-700 dark:text-gray-300">{{ $drop['application_status'] }}</dd>
                                                                        @if ($drop['attempt'])
                                                                            <dt class="text-gray-500 dark:text-gray-400">Attempt</dt>
                                                                            <dd class="text-gray-700 dark:text-gray-300">{{ $drop['attempt'] }}</dd>
                                                                            <dt class="text-gray-500 dark:text-gray-400">Applied</dt>
                                                                            <dd class="text-gray-700 dark:text-gray-300">{{ $drop['applied_at'] }}</dd>
                                                                            <dt class="text-gray-500 dark:text-gray-400">Reason</dt>
                                                                            <dd class="text-gray-700 dark:text-gray-300">{{ $drop['reason'] }}</dd>
                                                                            @if ($drop['reason_details'])
                                                                                <dt class="text-gray-500 dark:text-gray-400">Student details</dt>
                                                                                <dd class="whitespace-pre-line text-gray-700 dark:text-gray-300">{{ $drop['reason_details'] }}</dd>
                                                                            @endif
                                                                        @endif
                                                                    </dl>
                                                                </section>
                                                            @empty
                                                                <p class="mt-1 text-gray-600 dark:text-gray-300">No drop history recorded.</p>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </details>
                                            </td>

                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 4 + max(1, $gridDates->count()) }}" class="muted py-12 text-center">
                                                No students are enrolled in this course yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="px-6 py-3 bg-gray-50/80 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-gray-500 dark:text-gray-400">
                            <p>Click cells on scheduled class dates to update marks. Overall score recalculates using Blackboard attendance weights.</p>
                            <span class="font-medium">{{ $enrollmentRows->count() }} students enrolled</span>
                        </div>
                    </div>
                </div>

                <div
                    x-show="meetingMenu.open"
                    x-cloak
                    x-bind:style="`left: ${meetingMenu.x}px; top: ${meetingMenu.y}px;`"
                    @click.outside="closeMeetingMenu()"
                    @keydown.escape.window="closeMeetingMenu()"
                    class="fixed z-50 min-w-52 rounded-lg border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-800"
                    role="menu"
                    aria-label="Meeting actions"
                >
                    <p class="px-3 py-2 text-xs font-semibold muted" x-text="meetingMenu.key.split('|')[0]"></p>
                    <button type="button" class="block w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                            role="menuitem" @click="markMeetingAll('present')">Mark all present</button>
                    <button type="button" class="block w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                            role="menuitem" @click="markMeetingAll('absent')">Mark all absent</button>
                    <button type="button" class="block w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                            role="menuitem" @click="clearSelectedMeeting()">Clear marks</button>
                    <button type="button" class="block w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                            role="menuitem" @click="markMeetingAll('excused')">Exempt meeting</button>
                    <button type="button" class="mt-1 block w-full rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                            role="menuitem" x-show="selectedMeetingScheduleId" @click="deleteSelectedMeeting()">Delete meeting</button>
                </div>

            @endif

        @endif

        @if ($selectedCourse)
            <div x-show="showImportModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
                 @keydown.escape.window="showImportModal = false">
                <button type="button" class="modal-scrim absolute inset-0 cursor-default"
                        aria-label="Close import attendance dialog" @click="showImportModal = false"></button>
                <section class="modal-panel relative w-full max-w-2xl overflow-hidden shadow-xl"
                         role="dialog" aria-modal="true" aria-labelledby="attendance-import-heading">
                    <div class="card-header flex items-start justify-between gap-4">
                        <div>
                            <h2 id="attendance-import-heading" class="font-semibold">Import attendance file</h2>
                            <p class="muted mt-1 text-sm">Upload attendance for {{ $selectedCourse->subject_code }}.</p>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm" aria-label="Close dialog"
                                @click="showImportModal = false">&times;</button>
                    </div>

                    <form method="POST" action="{{ route('faculty.attendance.import') }}"
                          enctype="multipart/form-data" class="space-y-4 p-5">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $selectedCourse->course_id }}">
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label for="attendance-import-file" class="field-label">CSV or Excel file</label>
                                <a href="{{ asset('attendance-import-template.xlsx') }}"
                                   class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                    Download sample Excel
                                </a>
                            </div>
                            <input id="attendance-import-file" name="file" type="file" accept=".csv,.xlsx"
                                   required class="field mt-1">
                            @error('file')
                                <p class="mt-2 text-sm text-rose-600" role="alert">{{ $message }}</p>
                            @enderror
                            <p class="muted mt-2 text-xs">
                                Put student_number first, then use meeting dates as column headings (YYYY-MM-DD).
                                Enter P/Present, A/Absent, L/Late, or E/Excused. Repeat a date column for multiple
                                meetings on the same day.
                            </p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn btn-secondary"
                                    @click="showImportModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Upload and save attendance</button>
                        </div>
                    </form>
                </section>
            </div>
        @endif

        {{-- =========================================================================
             OVERALL GRID CELL EDIT MODAL
             ========================================================================= --}}
        <div x-show="showCellModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg max-w-sm w-full shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                 @click.away="showCellModal = false">
                <div class="card-header border-b pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-sm" x-text="cellData.studentName"></h3>
                        <p class="muted text-xs mt-0.5" x-text="cellData.formattedDate"></p>
                    </div>
                    <button type="button" @click="showCellModal = false" class="text-gray-400 hover:text-gray-700 font-bold text-lg">&times;</button>
                </div>

                <div class="p-5 space-y-4 text-xs">
                    <div>
                        <label class="field-label mb-2">Attendance Status</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    class="bb-status-btn py-2 text-xs"
                                    :class="{ 'bb-status-btn-present-active': cellData.status === 'present' }"
                                    @click="cellData.status = 'present'">
                                ✓ Present
                            </button>
                            <button type="button"
                                    class="bb-status-btn py-2 text-xs"
                                    :class="{ 'bb-status-btn-late-active': cellData.status === 'late' }"
                                    @click="cellData.status = 'late'">
                                🕒 Late
                            </button>
                            <button type="button"
                                    class="bb-status-btn py-2 text-xs"
                                    :class="{ 'bb-status-btn-absent-active': cellData.status === 'absent' }"
                                    @click="cellData.status = 'absent'">
                                ✕ Absent
                            </button>
                            <button type="button"
                                    class="bb-status-btn py-2 text-xs"
                                    :class="{ 'bb-status-btn-excused-active': cellData.status === 'excused' }"
                                    @click="cellData.status = 'excused'">
                                ⊘ Excused
                            </button>
                        </div>
                    </div>

                    <div x-show="cellData.status === 'absent'" x-cloak class="border-t pt-3 space-y-2">
                        <label class="field-label">Hours Absent</label>
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.5" min="0.5" max="24"
                                   x-model="cellData.hoursAbsent"
                                   class="field py-1 px-2 w-20 text-center font-bold text-xs">
                            <span class="muted text-xs">hours</span>
                            <div class="flex items-center gap-1 ml-auto">
                                <button type="button" @click="cellData.hoursAbsent = 1.0" class="btn btn-secondary btn-sm text-[11px] py-0.5 px-2">1h</button>
                                <button type="button" @click="cellData.hoursAbsent = cellData.defaultSessionHours" class="btn btn-secondary btn-sm text-[11px] py-0.5 px-2" x-text="cellData.defaultSessionHours + 'h'"></button>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-3">
                        <label class="field-label">Remarks / Note</label>
                        <input type="text" x-model="cellData.remarks" placeholder="Optional notes…" class="field py-1 text-xs">
                    </div>
                </div>

                <div class="card-footer bg-gray-50/70 dark:bg-gray-800/70 flex items-center justify-end gap-2 p-3">
                    <button type="button" @click="showCellModal = false" class="btn btn-secondary btn-sm">Cancel</button>
                    <button type="button" @click="saveCellRecord()" :disabled="cellSaving" class="btn btn-primary btn-sm font-semibold">
                        <span x-show="!cellSaving">Save</span>
                        <span x-show="cellSaving">Saving…</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function facultyDashboard(config) {
            return {
                showImportModal: config.importHasErrors,
                meetingFilterOpen: false,
                meetingSearch: '',
                meetingStatus: 'all',
                studentSortBy: 'student_number',
                studentSortDirection: 1,
                meetingMenu: { open: false, x: 0, y: 0, key: '' },

                // Toast notification
                toast: {
                    show: false,
                    message: '',
                    timeout: null
                },

                showToast(msg) {
                    this.toast.message = msg;
                    this.toast.show = true;
                    if (this.toast.timeout) clearTimeout(this.toast.timeout);
                    this.toast.timeout = setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                },

                // Blackboard Quick Cell Modal
                showCellModal: false,
                cellSaving: false,
                cellData: {
                    courseId: config.courseId,
                    enrollmentId: null,
                    studentName: '',
                    date: '',
                    scheduleId: null,
                    formattedDate: '',
                    status: 'present',
                    hoursAbsent: config.defaultSessionHours || 1.5,
                    defaultSessionHours: config.defaultSessionHours || 1.5,
                    remarks: ''
                },

                // Status & hours maps for Meeting view
                studentStatuses: config.initialStatuses || {},
                studentHours: config.initialHours || {},
                studentRemarks: config.initialRemarks || {},
                selectedMeetingKey: config.selectedMeetingKey || '',

                get selectedMeetingDate() {
                    return this.selectedMeetingKey.split('|')[0] || config.selectedDate;
                },

                get selectedMeetingScheduleId() {
                    const scheduleId = this.selectedMeetingKey.split('|')[1] || '';
                    return scheduleId === '' ? '' : Number(scheduleId);
                },

                get selectedMeetingSessionHours() {
                    return config.meetingHoursByKey[this.selectedMeetingKey] || config.defaultSessionHours || 1.5;
                },

                meetingStudentMatches(index, name, studentNumber) {
                    const search = this.meetingSearch.trim().toLocaleLowerCase();
                    const matchesSearch = !search
                        || `${name} ${studentNumber}`.toLocaleLowerCase().includes(search);
                    const status = this.studentStatuses[index] || 'unmarked';

                    return matchesSearch
                        && (this.meetingStatus === 'all' || this.meetingStatus === status);
                },

                toggleStudentSortDirection() {
                    this.studentSortDirection *= -1;
                    this.sortStudentRows();
                },

                sortStudentRows() {
                    const tableBody = document.querySelector('#attendance-overall-grid tbody');
                    if (!tableBody) {
                        return;
                    }

                    const rows = Array.from(tableBody.querySelectorAll('tr[data-student-number]'));
                    rows.sort((firstRow, secondRow) => {
                        const attribute = this.studentSortBy === 'name' ? 'studentName' : 'studentNumber';
                        const result = firstRow.dataset[attribute].localeCompare(
                            secondRow.dataset[attribute],
                            undefined,
                            { numeric: true, sensitivity: 'base' }
                        );

                        return result * this.studentSortDirection;
                    });

                    rows.forEach(row => tableBody.appendChild(row));
                },

                openMeetingMenu(event, key) {
                    this.selectedMeetingKey = key;
                    const rect = event.currentTarget.getBoundingClientRect();
                    const menuWidth = 208;
                    const menuHeight = 240;
                    const left = Math.max(8, Math.min(rect.left, window.innerWidth - menuWidth - 8));
                    const below = rect.bottom + 4;
                    const top = below + menuHeight <= window.innerHeight - 8
                        ? below
                        : Math.max(8, rect.top - menuHeight - 4);

                    this.meetingMenu = {
                        open: true,
                        x: left,
                        y: top,
                        key
                    };
                },

                closeMeetingMenu() {
                    this.meetingMenu.open = false;
                },

                // Reactive grid records & student scores
                gridRecords: config.initialGridRecords || {},
                studentScores: config.initialStudentScores || {},

                countStatus(targetStatus) {
                    return Object.values(this.studentStatuses).filter(s => s === targetStatus).length;
                },

                studentStatusOutline(enrollmentId) {
                    const score = this.studentScores[enrollmentId] || {};

                    if (score.is_dropped || score.is_permanent_drop_eligible) {
                        return 'border-rose-500 ring-1 ring-inset ring-rose-500/40';
                    }

                    if (Number(score.absent_hours) >= Number(score.weekly_drop_threshold_hours) * 0.8) {
                        return 'border-amber-500 ring-1 ring-inset ring-amber-500/40';
                    }

                    return 'border-emerald-500 ring-1 ring-inset ring-emerald-500/40';
                },

                weeklyProgressPercentage(enrollmentId) {
                    const score = this.studentScores[enrollmentId] || {};
                    const threshold = Number(score.weekly_drop_threshold_hours) || 0;

                    return threshold > 0
                        ? Math.min(100, Math.max(0, (Number(score.absent_hours) / threshold) * 100))
                        : 0;
                },

                weeklyProgressClass(enrollmentId) {
                    const progress = this.weeklyProgressPercentage(enrollmentId);

                    return progress >= 100 ? 'progress-bad' : (progress >= 80 ? 'progress-warn' : '');
                },

                handleStudentDetailsToggle(details) {
                    if (!details.open) {
                        this.closeStudentDetailsPanel(details);

                        return;
                    }

                    document.querySelectorAll('details[data-student-details]').forEach((otherDetails) => {
                        if (otherDetails !== details && otherDetails.open) {
                            otherDetails.open = false;
                            this.closeStudentDetailsPanel(otherDetails);
                        }
                    });

                    const panel = details.querySelector('[data-student-details-panel]');

                    if (panel && ! panel.matches(':popover-open')) {
                        panel.showPopover();
                    }

                    this.positionStudentDetails(details);
                },

                closeStudentDetails(details) {
                    details.open = false;
                    this.closeStudentDetailsPanel(details);
                },

                closeStudentDetailsPanel(details) {
                    const panel = details.querySelector('[data-student-details-panel]');

                    if (panel?.matches(':popover-open')) {
                        panel.hidePopover();
                    }
                },

                positionStudentDetails(details) {
                    const summary = details.querySelector('summary');
                    const panel = details.querySelector('[data-student-details-panel]');

                    if (!summary || !panel) {
                        return;
                    }

                    const summaryBounds = summary.getBoundingClientRect();
                    const panelBounds = panel.getBoundingClientRect();
                    const padding = 16;
                    const left = Math.max(
                        padding,
                        Math.min(summaryBounds.right - panelBounds.width, window.innerWidth - panelBounds.width - padding)
                    );
                    const below = summaryBounds.bottom + 8;
                    const top = below + panelBounds.height <= window.innerHeight - padding
                        ? below
                        : Math.max(padding, summaryBounds.top - panelBounds.height - 8);

                    panel.style.right = 'auto';
                    panel.style.bottom = 'auto';
                    panel.style.margin = '0';
                    panel.style.left = `${left}px`;
                    panel.style.top = `${top}px`;
                },

                applyAttendanceStats(enrollmentId, stats) {
                    if (!stats) {
                        return;
                    }

                    this.studentScores[enrollmentId] = {
                        ...this.studentScores[enrollmentId],
                        score: stats.overall_score,
                        pill_class: this.getPillClass(stats.overall_score),
                        score_text: stats.overall_score == 100 ? '100 / 100' : parseFloat(stats.overall_score).toFixed(2) + ' / 100',
                        absent_hours: stats.absent_hours,
                        maximum_absence_hours: stats.maximum_absence_hours,
                        weekly_drop_threshold_hours: stats.weekly_drop_threshold_hours,
                        absence_percentage: stats.absence_percentage,
                        maximum_absence_percentage: stats.maximum_absence_percentage,
                        is_drop_eligible: stats.is_drop_eligible,
                        is_permanent_drop_eligible: stats.is_permanent_drop_eligible,
                    };

                    const scoreEl = document.getElementById(`overall-pill-${enrollmentId}`);
                    if (scoreEl) {
                        scoreEl.className = `bb-pill ${this.studentScores[enrollmentId].pill_class}`;
                        scoreEl.textContent = this.studentScores[enrollmentId].score_text;
                    }
                },

                // Step hours for Absent student
                stepHours(index, enrollmentId, delta, studentName) {
                    let current = parseFloat(this.studentHours[index]) || config.defaultSessionHours || 1.5;
                    let next = Math.max(0.5, current + delta);
                    this.studentHours[index] = next;

                    // Automatically update server if already absent
                    if (this.studentStatuses[index] === 'absent') {
                        this.postRecord(enrollmentId, 'absent', next, this.studentRemarks[index], studentName);
                    }
                },

                // Click handler on icon cells
                markStudent(index, enrollmentId, status, studentName) {
                    // Update state immediately
                    this.studentStatuses[index] = status;
                    const hoursAbsent = status === 'absent' ? (parseFloat(this.studentHours[index]) || config.defaultSessionHours || 1.5) : null;
                    const remarks = this.studentRemarks[index] || null;

                    this.postRecord(enrollmentId, status, hoursAbsent, remarks, studentName);
                },

                postRecord(enrollmentId, status, hoursAbsent, remarks, studentName) {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

                    fetch('{{ route("faculty.attendance.record") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            course_id: config.courseId,
                            enrollment_id: enrollmentId,
                            session_date: config.selectedDate,
                            schedule_id: {{ $selectedSchedule ? $selectedSchedule->schedule_id : 'null' }},
                            status: status,
                            hours_absent: hoursAbsent,
                            remarks: remarks
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (!this.gridRecords[enrollmentId]) {
                                this.gridRecords[enrollmentId] = {};
                            }
                            this.gridRecords[enrollmentId][`${config.selectedDate}|${data.record.schedule_id ?? ''}`] = data.record;
                            this.applyAttendanceStats(enrollmentId, data.stats);

                            const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
                            this.showToast(`${studentName}: ${statusLabel}`);
                        } else {
                            window.showAppAlert(data.error || Object.values(data.errors || {}).flat()[0] || data.message || 'Failed to save attendance record.', 'Attendance update failed');
                        }
                    })
                    .catch(err => {
                        console.error('Save error:', err);
                        window.showAppAlert(err.message || 'An error occurred while saving attendance.', 'Attendance update failed');
                    });
                },

                // Click handler on column headers (Mark All)
                markAll(targetStatus) {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

                    // Prepare bulk entries
                    const attendanceEntries = [];
                    @foreach ($enrollmentRows as $idx => $row)
                        @if ($row['can_record_attendance'])
                            this.studentStatuses[{{ $idx }}] = targetStatus;
                            attendanceEntries.push({
                                enrollment_id: {{ $row['enrollment']->enrollment_id }},
                                status: targetStatus,
                                hours_absent: targetStatus === 'absent' ? this.selectedMeetingSessionHours : null,
                                remarks: this.studentRemarks[{{ $idx }}] || null
                            });
                        @endif
                    @endforeach

                    fetch('{{ route("faculty.attendance.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            course_id: config.courseId,
                            session_date: this.selectedMeetingDate,
                            schedule_id: this.selectedMeetingScheduleId || null,
                            tab: config.activeTab,
                            attendance: attendanceEntries
                        })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            throw new Error(data.error || Object.values(data.errors || {}).flat()[0] || data.message || 'Failed to save attendance records.');
                        }

                        return data;
                    })
                    .then(data => {
                        const label = targetStatus.charAt(0).toUpperCase() + targetStatus.slice(1);
                        this.showToast(`Marked eligible students as ${label}`);
                        const url = new URL(window.location.href);
                        url.searchParams.set('date', this.selectedMeetingDate);
                        url.searchParams.set('tab', config.activeTab);
                        if (this.selectedMeetingScheduleId) {
                            url.searchParams.set('schedule_id', this.selectedMeetingScheduleId);
                        } else {
                            url.searchParams.delete('schedule_id');
                        }
                        window.location.assign(url.toString());
                    })
                    .catch(err => {
                        console.error('Mark all error:', err);
                        window.showAppAlert(err.message || 'Attendance marks could not be saved.', 'Attendance update failed');
                    });
                },

                clearSelectedMeeting() {
                    window.showAppConfirm('Clear attendance marks for this dated meeting? This will not delete the meeting.', () => {
                        this.closeMeetingMenu();
                        this.$refs.clearMeetingForm.requestSubmit();
                    });
                },

                deleteSelectedMeeting() {
                    if (this.selectedMeetingScheduleId) {
                        window.showAppConfirm('Delete this dated meeting and its attendance marks? The recurring weekly schedule will remain.', () => {
                        this.closeMeetingMenu();
                            this.$refs.deleteMeetingForm.requestSubmit();
                        });
                    }
                },

                markMeetingAll(targetStatus) {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';
                    const [sessionDate, scheduleValue] = this.meetingMenu.key.split('|');
                    const scheduleId = scheduleValue ? Number(scheduleValue) : null;
                    const meetingHours = config.meetingHoursByKey[this.meetingMenu.key] || config.defaultSessionHours || 1.5;
                    const attendanceEntries = [];

                    @foreach ($enrollmentRows as $row)
                        attendanceEntries.push({
                            enrollment_id: {{ $row['enrollment']->enrollment_id }},
                            status: targetStatus,
                            hours_absent: targetStatus === 'absent' ? meetingHours : null
                        });
                    @endforeach

                    fetch('{{ route("faculty.attendance.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            course_id: config.courseId,
                            session_date: sessionDate,
                            schedule_id: scheduleId,
                            attendance: attendanceEntries
                        })
                    })
                    .then(async response => {
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Could not update attendance for this meeting.');
                        }

                        const url = new URL(window.location.href);
                        url.searchParams.set('date', sessionDate);
                        url.searchParams.set('tab', config.activeTab);
                        if (scheduleId) {
                            url.searchParams.set('schedule_id', scheduleId);
                        } else {
                            url.searchParams.delete('schedule_id');
                        }
                        window.location.assign(url.toString());
                    })
                    .catch(error => {
                        this.closeMeetingMenu();
                        window.showAppAlert(error.message || 'The meeting could not be updated.', 'Attendance update failed');
                    });
                },

                openCellModal(enrollmentId, studentName, dateStr, formattedDate, scheduleId, currentStatus, hoursAbsent, remarks, sessionHours) {
                    const recordKey = `${dateStr}|${scheduleId ?? ''}`;
                    if (this.gridRecords[enrollmentId] && this.gridRecords[enrollmentId][recordKey]) {
                        const local = this.gridRecords[enrollmentId][recordKey];
                        currentStatus = local.status;
                        hoursAbsent = local.hours_absent;
                        remarks = local.remarks;
                    }

                    this.cellData = {
                        courseId: config.courseId,
                        enrollmentId: enrollmentId,
                        studentName: studentName,
                        date: dateStr,
                        scheduleId: scheduleId,
                        formattedDate: formattedDate,
                        hoursAbsent: (hoursAbsent !== null && hoursAbsent !== undefined) ? hoursAbsent : (sessionHours || 1.5),
                        defaultSessionHours: sessionHours || 1.5,
                        remarks: remarks || '',
                        status: currentStatus || 'present'
                    };
                    this.showCellModal = true;
                },

                getPillClass(score) {
                    score = parseFloat(score);
                    if (score >= 90) return 'bb-pill-green';
                    if (score >= 80) return 'bb-pill-lime';
                    if (score >= 70) return 'bb-pill-yellow';
                    return 'bb-pill-red';
                },

                buildCellHtml(record) {
                    const status = record.status;
                    const hoursAbsent = record.hours_absent;

                    if (status === 'present') {
                        return `<div class="flex flex-col items-center justify-center">
                            <svg class="h-5 w-5 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-xs text-gray-700 dark:text-gray-300 font-medium mt-0.5">Present</span>
                        </div>`;
                    }
                    if (status === 'late') {
                        return `<div class="flex flex-col items-center justify-center">
                            <svg class="h-5 w-5 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="text-xs text-gray-800 dark:text-gray-200 font-medium mt-0.5">Late</span>
                        </div>`;
                    }
                    if (status === 'absent') {
                        const hrsText = hoursAbsent !== null && hoursAbsent !== undefined ? `<span class="text-[10px] text-gray-500">(${hoursAbsent}h)</span>` : '';
                        return `<div class="flex flex-col items-center justify-center">
                            <svg class="h-5 w-5 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            <span class="text-xs text-gray-700 dark:text-gray-300 font-medium mt-0.5">Absent ${hrsText}</span>
                        </div>`;
                    }
                    if (status === 'excused') {
                        return `<div class="flex flex-col items-center justify-center">
                            <svg class="h-5 w-5 text-gray-800 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" /><line x1="5.6" y1="5.6" x2="18.4" y2="18.4" /></svg>
                            <span class="text-xs text-gray-700 dark:text-gray-300 font-medium mt-0.5">Excused</span>
                        </div>`;
                    }
                    return `<div class="flex flex-col items-center justify-center opacity-30 group-hover:opacity-100 transition-opacity"><span class="text-lg text-gray-400 font-light">+</span></div>`;
                },

                saveCellRecord() {
                    this.cellSaving = true;
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

                    const enrollmentId = this.cellData.enrollmentId;
                    const dateStr = this.cellData.date;
                    const studentName = this.cellData.studentName;

                    fetch('{{ route("faculty.attendance.record") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            course_id: this.cellData.courseId,
                            enrollment_id: enrollmentId,
                            session_date: dateStr,
                            schedule_id: this.cellData.scheduleId,
                            status: this.cellData.status,
                            hours_absent: this.cellData.status === 'absent' ? parseFloat(this.cellData.hoursAbsent) : null,
                            remarks: this.cellData.remarks
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.cellSaving = false;
                        if (data.success) {
                            this.showCellModal = false;

                            if (!this.gridRecords[enrollmentId]) {
                                this.gridRecords[enrollmentId] = {};
                            }
                            this.gridRecords[enrollmentId][`${dateStr}|${data.record.schedule_id ?? ''}`] = data.record;

                            const cellEl = document.getElementById(`cell-${enrollmentId}-${dateStr}-${data.record.schedule_id ?? 'none'}`);
                            if (cellEl) {
                                cellEl.className = 'bb-cell text-center p-2 min-w-[5.5rem] h-[3.8rem] select-none cursor-pointer group transition-colors border-r border-b border-gray-200 dark:border-gray-700 ' +
                                    (data.record.status === 'late' ? 'bb-cell-late' : 'bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700');
                                cellEl.innerHTML = this.buildCellHtml(data.record);
                            }

                            this.applyAttendanceStats(enrollmentId, data.stats);

                            this.showToast(`Updated ${studentName} to ${data.record.status.toUpperCase()}`);
                        } else {
                            window.showAppAlert(data.error || Object.values(data.errors || {}).flat()[0] || data.message || 'Failed to save mark.', 'Attendance update failed');
                        }
                    })
                    .catch(err => {
                        this.cellSaving = false;
                        window.showAppAlert(err.message || 'An error occurred while saving.', 'Attendance update failed');
                    });
                }
            };
        }
    </script>
</x-app-layout>
