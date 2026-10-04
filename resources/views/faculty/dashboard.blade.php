<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <span class="inline-block w-3 h-3 rounded-full bg-red-700"></span>
                    Faculty Attendance Dashboard
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                    {{ $faculty->full_name }} &bull; <span class="font-medium text-gray-800 dark:text-gray-200">{{ $faculty->department }}</span>
                </p>
            </div>

            @if ($selectedCourse)
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                        {{ $selectedCourse->subject_code }} - Sec {{ $selectedCourse->section }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $selectedCourse->schoolYear->year_label ?? 'Current S.Y.' }} ({{ $selectedCourse->schoolYear->semester ?? '1st Sem' }})
                    </span>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8" x-data="facultyDashboard()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Notifications -->
            @if (session('success'))
                <div class="rounded-lg bg-emerald-50 border border-emerald-300 p-4 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-200 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="font-semibold text-sm">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if (session('dropped_alert'))
                <div class="rounded-lg bg-red-50 border-2 border-red-500 p-4 text-red-900 dark:bg-red-950/60 dark:border-red-600 dark:text-red-100 flex items-start gap-3 shadow-md">
                    <svg class="w-6 h-6 text-red-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h4 class="font-bold text-sm uppercase tracking-wide">Automatic Drop Threshold Exceeded</h4>
                        <p class="text-sm mt-0.5">{{ session('dropped_alert') }}</p>
                        <p class="text-xs text-red-700 dark:text-red-300 mt-1">Per university policy, students with absences exceeding 20% of class hours are marked as Dropped and must complete re-admission before re-entering class.</p>
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-blue-800 dark:bg-blue-950/40 dark:border-blue-800 dark:text-blue-200 flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm">{{ session('info') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-amber-50 border border-amber-300 p-4 text-amber-900 dark:bg-amber-950/40 dark:border-amber-700 dark:text-amber-200">
                    <p class="font-semibold text-sm">Please correct the following errors:</p>
                    <ul class="list-disc list-inside text-xs mt-1 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Assigned Courses Selection Cards -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Assigned Courses & Sections</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Select a course to check student attendance or review academic records</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        {{ $courses->count() }} {{ Str::plural('Course', $courses->count()) }} Assigned
                    </span>
                </div>

                @if ($courses->isEmpty())
                    <div class="text-center py-10 bg-gray-50 dark:bg-gray-700/30 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">No courses assigned yet</h3>
                        <p class="mt-1 text-xs text-gray-500">Contact the department chair or run the database seeder to populate sample courses.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($courses as $c)
                            @php
                                $isSelected = $selectedCourse && $selectedCourse->course_id === $c->course_id;
                            @endphp
                            <a href="{{ route('faculty.dashboard', ['course_id' => $c->course_id, 'date' => $selectedDate, 'tab' => $activeTab]) }}"
                               class="group block p-4 rounded-xl border transition-all duration-150 {{ $isSelected ? 'border-red-600 bg-red-50/50 dark:bg-red-950/20 ring-2 ring-red-500/20' : 'border-gray-200 dark:border-gray-700 hover:border-red-300 dark:hover:border-red-800 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-base {{ $isSelected ? 'text-red-700 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                                                {{ $c->subject_code }}
                                            </span>
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200">
                                                Sec {{ $c->section }}
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-1 line-clamp-1">
                                            {{ $c->course_name }}
                                        </h3>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full font-semibold {{ $isSelected ? 'bg-red-700 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                        {{ $c->enrollments_count }} {{ Str::plural('Student', $c->enrollments_count) }}
                                    </span>
                                </div>

                                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="truncate">
                                            @if ($c->schedules->isNotEmpty())
                                                {{ $c->schedules->pluck('day_of_week')->join(', ') }} ({{ Carbon\Carbon::parse($c->schedules->first()->start_time)->format('h:i A') }})
                                            @else
                                                Schedule TBA
                                            @endif
                                        </span>
                                    </div>
                                    <span class="font-medium">{{ $c->hours_per_week }} hrs/wk</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($selectedCourse)
                <!-- Selected Course Detail & KPI Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-red-800 via-red-700 to-red-900 text-white p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold tracking-wider uppercase bg-white/20 px-2 py-0.5 rounded text-white">Active Course</span>
                                    <span class="text-xs text-red-200">Room {{ $selectedCourse->schedules->first()->room ?? 'TBA' }}</span>
                                </div>
                                <h2 class="text-2xl font-bold mt-1">
                                    {{ $selectedCourse->subject_code }}: {{ $selectedCourse->course_name }}
                                </h2>
                                <p class="text-xs text-red-100 mt-0.5 flex items-center gap-2">
                                    <span>Section <strong>{{ $selectedCourse->section }}</strong></span> &bull;
                                    <span>{{ $selectedCourse->total_semester_hours }} Total Semester Hours</span> &bull;
                                    <span class="bg-red-950/40 px-2 py-0.5 rounded text-amber-200 font-medium">
                                        Absence Limit: {{ $courseStats['threshold_hours'] }} hrs (20%)
                                    </span>
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-xs text-red-200">Weekly Schedule</p>
                                    <p class="text-sm font-semibold">
                                        @foreach ($selectedCourse->schedules as $sch)
                                            <span class="inline-block bg-white/10 px-2 py-0.5 rounded text-xs">
                                                {{ $sch->day_of_week }} {{ Carbon\Carbon::parse($sch->start_time)->format('h:i A') }} - {{ Carbon\Carbon::parse($sch->end_time)->format('h:i A') }}
                                            </span>
                                        @endforeach
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4 Metric KPI Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-gray-200 dark:divide-gray-700 bg-gray-50/50 dark:bg-gray-800/40">
                        <div class="p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Enrolled Students</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ $courseStats['total_students'] }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">Active Class Roster</p>
                        </div>
                        <div class="p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sessions Recorded</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ $courseStats['sessions_count'] }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">Dates with attendance</p>
                        </div>
                        <div class="p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Attendance Rate</p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-2xl font-bold {{ $courseStats['attendance_rate'] >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($courseStats['attendance_rate'] >= 75 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ $courseStats['attendance_rate'] }}%
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Cumulative presence rate</p>
                        </div>
                        <div class="p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">At-Risk / Dropped</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-sm font-bold {{ $courseStats['at_risk_count'] > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $courseStats['at_risk_count'] }} Warning
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-sm font-bold {{ $courseStats['dropped_count'] > 0 ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $courseStats['dropped_count'] }} Dropped
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Approaching / exceeded 20% limit</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700 flex space-x-2">
                    <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $selectedDate, 'tab' => 'check']) }}"
                       class="inline-flex items-center gap-2 py-3 px-4 text-sm font-semibold border-b-2 transition-colors {{ $activeTab === 'check' ? 'border-red-600 text-red-600 dark:text-red-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span>Check Attendance</span>
                        <span class="text-xs px-2 py-0.2 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            {{ Carbon\Carbon::parse($selectedDate)->format('M d') }}
                        </span>
                    </a>

                    <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $selectedDate, 'tab' => 'summary']) }}"
                       class="inline-flex items-center gap-2 py-3 px-4 text-sm font-semibold border-b-2 transition-colors {{ $activeTab === 'summary' ? 'border-red-600 text-red-600 dark:text-red-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Student Summary & Watchlist</span>
                        @if ($courseStats['at_risk_count'] > 0 || $courseStats['dropped_count'] > 0)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 font-bold">
                                {{ $courseStats['at_risk_count'] + $courseStats['dropped_count'] }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $selectedDate, 'tab' => 'history']) }}"
                       class="inline-flex items-center gap-2 py-3 px-4 text-sm font-semibold border-b-2 transition-colors {{ $activeTab === 'history' ? 'border-red-600 text-red-600 dark:text-red-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Session History</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            {{ $sessionHistory->count() }}
                        </span>
                    </a>
                </div>

                <!-- TAB 1: CHECK ATTENDANCE -->
                @if ($activeTab === 'check')
                    <div class="space-y-6">

                        <!-- Date Control & Schedule Status Card -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <form method="GET" action="{{ route('faculty.dashboard') }}" class="flex flex-wrap items-center gap-3">
                                    <input type="hidden" name="course_id" value="{{ $selectedCourse->course_id }}">
                                    <input type="hidden" name="tab" value="check">

                                    <label for="date-input" class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                                        Session Date:
                                    </label>

                                    <input type="date"
                                           id="date-input"
                                           name="date"
                                           value="{{ $selectedDate }}"
                                           onchange="this.form.submit()"
                                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-red-600 focus:ring-red-600">

                                    <button type="submit" class="px-3 py-2 text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition">
                                        Load Session
                                    </button>

                                    @if ($selectedDate !== now()->toDateString())
                                        <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => now()->toDateString(), 'tab' => 'check']) }}"
                                           class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline">
                                            Jump to Today
                                        </a>
                                    @endif
                                </form>

                                <!-- Schedule Status Badge -->
                                <div>
                                    @if ($isOnSchedule)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
                                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Regular Scheduled Meeting Day ({{ Carbon\Carbon::parse($selectedDate)->format('l') }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 dark:bg-amber-950/40 dark:border-amber-700 dark:text-amber-300">
                                            <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Out-of-Schedule Day ({{ Carbon\Carbon::parse($selectedDate)->format('l') }})
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Out-of-schedule warning alert if not suppressed -->
                            @if (! $isOnSchedule && ! $faculty->suppress_schedule_warnings)
                                <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg flex items-start justify-between gap-3 text-xs text-amber-900 dark:text-amber-200">
                                    <div class="flex items-start gap-2">
                                        <span class="text-base">ℹ️</span>
                                        <div>
                                            <span class="font-semibold">Notice:</span> Today is <strong>{{ Carbon\Carbon::parse($selectedDate)->format('l') }}</strong>. This class meets on <strong>{{ implode(', ', $scheduledDays) }}</strong>. Recording attendance for this date will be tagged as an out-of-schedule session (e.g. makeup or special class).
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('faculty.toggle-schedule-warning') }}">
                                        @csrf
                                        <button type="submit" class="text-amber-700 dark:text-amber-400 underline font-medium hover:text-amber-900 whitespace-nowrap">
                                            Suppress Warning
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        <!-- Main Attendance Sheet Form -->
                        <form method="POST" action="{{ route('faculty.attendance.store') }}" id="attendance-form">
                            @csrf
                            <input type="hidden" name="course_id" value="{{ $selectedCourse->course_id }}">
                            <input type="hidden" name="session_date" value="{{ $selectedDate }}">

                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <!-- Table Toolbar -->
                                <div class="p-4 bg-gray-50/70 dark:bg-gray-700/40 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">Quick Actions:</span>
                                        <button type="button"
                                                @click="markAll('present')"
                                                class="px-2.5 py-1 text-xs font-semibold rounded bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300 transition">
                                            Mark All Present
                                        </button>
                                        <button type="button"
                                                @click="markAll('late')"
                                                class="px-2.5 py-1 text-xs font-semibold rounded bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/50 dark:text-amber-300 transition">
                                            Mark All Late
                                        </button>
                                        <button type="button"
                                                @click="markAll('absent')"
                                                class="px-2.5 py-1 text-xs font-semibold rounded bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-300 transition">
                                            Mark All Absent
                                        </button>
                                    </div>

                                    <div class="w-full sm:w-64">
                                        <input type="text"
                                               x-model="searchQuery"
                                               placeholder="Filter student..."
                                               class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 py-1.5 focus:border-red-600 focus:ring-red-600">
                                    </div>
                                </div>

                                <!-- Attendance Sheet Table -->
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-sm">
                                        <thead class="bg-gray-50 dark:bg-gray-700/60 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            <tr>
                                                <th scope="col" class="py-3 px-4 w-12">#</th>
                                                <th scope="col" class="py-3 px-4">Student Details</th>
                                                <th scope="col" class="py-3 px-4">Absence Record</th>
                                                <th scope="col" class="py-3 px-4 text-center">Status</th>
                                                <th scope="col" class="py-3 px-4">Session Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                            @forelse ($enrollmentRows as $index => $row)
                                                @php
                                                    $isDropped = $row['status'] === 'dropped';
                                                    $student = $row['student'];
                                                    $currentStatus = $row['today_status'] ?? 'present';
                                                @endphp
                                                <tr x-show="matchesSearch('{{ strtolower($student->fullName ?? '') }} {{ strtolower($student->student_number ?? '') }}')"
                                                    class="{{ $isDropped ? 'bg-gray-100/60 dark:bg-gray-900/50 opacity-70' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30' }} transition">

                                                    <td class="py-3 px-4 text-xs font-semibold text-gray-400">
                                                        {{ $index + 1 }}
                                                    </td>

                                                    <td class="py-3 px-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 font-bold text-xs flex items-center justify-center shrink-0">
                                                                {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}{{ strtoupper(substr($student->last_name ?? 'T', 0, 1)) }}
                                                            </div>
                                                            <div>
                                                                <p class="font-semibold text-gray-900 dark:text-gray-100 leading-tight">
                                                                    {{ $student->fullName }}
                                                                </p>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                                    ID: <span class="font-mono">{{ $student->student_number }}</span> &bull; {{ $student->program }} {{ $student->year_level ? "({$student->year_level})" : '' }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="py-3 px-4">
                                                        <div class="text-xs">
                                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                                <span class="font-medium {{ $row['percent_of_threshold'] >= 100 ? 'text-red-600 font-bold' : ($row['percent_of_threshold'] >= 70 ? 'text-amber-600 font-bold' : 'text-gray-600 dark:text-gray-300') }}">
                                                                    {{ $row['absent_hours'] }} / {{ $courseStats['threshold_hours'] }} hrs
                                                                </span>
                                                                <span class="text-gray-400">
                                                                    {{ $row['absences'] }} abs &bull; {{ $row['lates'] }} late
                                                                </span>
                                                            </div>
                                                            <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                                                <div class="h-full rounded-full {{ $row['percent_of_threshold'] >= 100 ? 'bg-red-600' : ($row['percent_of_threshold'] >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                                                     style="width: {{ min(100, $row['percent_of_threshold']) }}%"></div>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="py-3 px-4">
                                                        @if ($isDropped)
                                                            <div class="text-center">
                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-200">
                                                                    DROPPED (Locked)
                                                                </span>
                                                                <p class="text-[10px] text-gray-500 mt-0.5">Readmission required</p>
                                                            </div>
                                                        @else
                                                            <input type="hidden" name="attendance[{{ $index }}][enrollment_id]" value="{{ $row['enrollment']->enrollment_id }}">
                                                            <div class="flex items-center justify-center gap-1.5">
                                                                <!-- Present -->
                                                                <label class="cursor-pointer">
                                                                    <input type="radio"
                                                                           name="attendance[{{ $index }}][status]"
                                                                           value="present"
                                                                           class="peer sr-only status-radio status-present"
                                                                           {{ $currentStatus === 'present' ? 'checked' : '' }}>
                                                                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold border transition peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-emerald-400">
                                                                        Present
                                                                    </span>
                                                                </label>

                                                                <!-- Late -->
                                                                <label class="cursor-pointer">
                                                                    <input type="radio"
                                                                           name="attendance[{{ $index }}][status]"
                                                                           value="late"
                                                                           class="peer sr-only status-radio status-late"
                                                                           {{ $currentStatus === 'late' ? 'checked' : '' }}>
                                                                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold border transition peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-amber-400">
                                                                        Late
                                                                    </span>
                                                                </label>

                                                                <!-- Absent -->
                                                                <label class="cursor-pointer">
                                                                    <input type="radio"
                                                                           name="attendance[{{ $index }}][status]"
                                                                           value="absent"
                                                                           class="peer sr-only status-radio status-absent"
                                                                           {{ $currentStatus === 'absent' ? 'checked' : '' }}>
                                                                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold border transition peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-red-400">
                                                                        Absent
                                                                    </span>
                                                                </label>
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td class="py-3 px-4">
                                                        @if ($isDropped)
                                                            <span class="text-xs text-gray-400 italic">No remarks allowed for dropped student</span>
                                                        @else
                                                            <input type="text"
                                                                   name="attendance[{{ $index }}][remarks]"
                                                                   value="{{ $row['today_remarks'] ?? '' }}"
                                                                   placeholder="Optional note / excuse..."
                                                                   class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 focus:border-red-600 focus:ring-red-600">
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-8 text-center text-sm text-gray-500">
                                                        No students are enrolled in this course yet.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Action Bottom Bar -->
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Saving attendance will automatically evaluate if any student exceeds the 20% absence threshold ({{ $courseStats['threshold_hours'] }} hrs).
                                    </div>

                                    <button type="submit"
                                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-red-700 hover:bg-red-800 text-white font-semibold text-sm rounded-lg shadow-sm transition focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Save Attendance for {{ Carbon\Carbon::parse($selectedDate)->format('M d, Y') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                <!-- TAB 2: STUDENT SUMMARY & WATCHLIST -->
                @elseif ($activeTab === 'summary')
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden space-y-4 p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Cumulative Attendance Summary & Dropping Watchlist</h3>
                                <p class="text-xs text-gray-500">Monitor absence accumulation against the 20% semester threshold ({{ $courseStats['threshold_hours'] }} hours max)</p>
                            </div>

                            <div class="flex items-center gap-3">
                                <select x-model="statusFilter" class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 focus:border-red-600 focus:ring-red-600">
                                    <option value="all">All Students</option>
                                    <option value="at_risk">At Risk (>= 70% threshold)</option>
                                    <option value="dropped">Dropped Only</option>
                                    <option value="active">Active Only</option>
                                </select>

                                <input type="text"
                                       x-model="searchQuery"
                                       placeholder="Search student..."
                                       class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 py-1.5 focus:border-red-600 focus:ring-red-600 w-48">
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/60 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <tr>
                                        <th scope="col" class="py-3 px-4">Student</th>
                                        <th scope="col" class="py-3 px-4">Program & Year</th>
                                        <th scope="col" class="py-3 px-4 text-center">Sessions</th>
                                        <th scope="col" class="py-3 px-4 text-center">P / L / A</th>
                                        <th scope="col" class="py-3 px-4">Absence Progress</th>
                                        <th scope="col" class="py-3 px-4 text-center">Status</th>
                                        <th scope="col" class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                    @forelse ($enrollmentRows as $row)
                                        @php
                                            $student = $row['student'];
                                            $isDropped = $row['status'] === 'dropped';
                                            $isAtRisk = $row['is_at_risk'];
                                            $canManuallyDrop = (! $isDropped) && ($row['absent_hours'] >= $courseStats['threshold_hours']);
                                        @endphp
                                        <tr x-show="matchesSummaryFilter('{{ $row['status'] }}', {{ $isAtRisk ? 'true' : 'false' }}, '{{ strtolower($student->fullName ?? '') }} {{ strtolower($student->student_number ?? '') }}')"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $student->fullName }}
                                                </div>
                                                <div class="text-xs text-gray-500 font-mono">
                                                    {{ $student->student_number }}
                                                </div>
                                            </td>

                                            <td class="py-3 px-4 text-xs text-gray-600 dark:text-gray-300">
                                                {{ $student->program }} {{ $student->year_level ? "• Yr {$student->year_level}" : '' }}
                                            </td>

                                            <td class="py-3 px-4 text-center text-xs font-medium">
                                                {{ $row['total_sessions'] }}
                                            </td>

                                            <td class="py-3 px-4 text-center text-xs">
                                                <span class="text-emerald-600 font-semibold">{{ $row['presences'] }}</span> /
                                                <span class="text-amber-600 font-semibold">{{ $row['lates'] }}</span> /
                                                <span class="text-red-600 font-semibold">{{ $row['absences'] }}</span>
                                            </td>

                                            <td class="py-3 px-4">
                                                <div class="text-xs">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="font-bold {{ $row['percent_of_threshold'] >= 100 ? 'text-red-600' : ($row['percent_of_threshold'] >= 70 ? 'text-amber-600' : 'text-gray-700 dark:text-gray-300') }}">
                                                            {{ $row['absent_hours'] }} / {{ $courseStats['threshold_hours'] }} hrs
                                                        </span>
                                                        <span class="text-gray-400 text-[11px]">
                                                            {{ $row['percent_of_threshold'] }}%
                                                        </span>
                                                    </div>
                                                    <div class="w-36 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full {{ $row['percent_of_threshold'] >= 100 ? 'bg-red-600' : ($row['percent_of_threshold'] >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                                             style="width: {{ min(100, $row['percent_of_threshold']) }}%"></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="py-3 px-4 text-center">
                                                @if ($isDropped)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-300">
                                                        DROPPED
                                                    </span>
                                                @elseif ($row['status'] === 'readmitted')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                                                        READMITTED
                                                    </span>
                                                @elseif ($isAtRisk)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                                                        AT RISK
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                        Active
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="py-3 px-4 text-right space-x-2">
                                                <!-- View History Button -->
                                                <button type="button"
                                                        @click="fetchStudentHistory({{ $row['enrollment']->enrollment_id }})"
                                                        class="px-2.5 py-1 text-xs font-semibold rounded bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 transition">
                                                    History
                                                </button>

                                                @if ($canManuallyDrop)
                                                    <form method="POST" action="{{ route('faculty.enrollments.drop', $row['enrollment']) }}" class="inline" onsubmit="return confirm('Confirm dropping student {{ $student->fullName }} for exceeding absence threshold?');">
                                                        @csrf
                                                        <button type="submit"
                                                                class="px-2.5 py-1 text-xs font-bold rounded bg-red-600 hover:bg-red-700 text-white transition">
                                                            Drop
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-6 text-center text-sm text-gray-500">
                                                No students enrolled in this course.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                <!-- TAB 3: SESSION HISTORY -->
                @elseif ($activeTab === 'history')
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Past Class Attendance Sessions</h3>
                                <p class="text-xs text-gray-500">Review and modify previously recorded attendance entries</p>
                            </div>
                            <span class="text-xs px-2.5 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold">
                                {{ $sessionHistory->count() }} Sessions Logged
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/60 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <tr>
                                        <th scope="col" class="py-3 px-4">Session Date</th>
                                        <th scope="col" class="py-3 px-4">Schedule Tag</th>
                                        <th scope="col" class="py-3 px-4 text-center">Present</th>
                                        <th scope="col" class="py-3 px-4 text-center">Late</th>
                                        <th scope="col" class="py-3 px-4 text-center">Absent</th>
                                        <th scope="col" class="py-3 px-4 text-center">Total Students</th>
                                        <th scope="col" class="py-3 px-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                    @forelse ($sessionHistory as $s)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ Carbon\Carbon::parse($s['session_date'])->format('F d, Y') }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ Carbon\Carbon::parse($s['session_date'])->format('l') }}
                                                </div>
                                            </td>

                                            <td class="py-3 px-4">
                                                @if ($s['is_out_of_schedule'])
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                                        Out-of-Schedule
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                        Regular
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="py-3 px-4 text-center font-semibold text-emerald-600">
                                                {{ $s['present_count'] }}
                                            </td>

                                            <td class="py-3 px-4 text-center font-semibold text-amber-600">
                                                {{ $s['late_count'] }}
                                            </td>

                                            <td class="py-3 px-4 text-center font-semibold text-red-600">
                                                {{ $s['absent_count'] }}
                                            </td>

                                            <td class="py-3 px-4 text-center text-xs font-medium text-gray-600 dark:text-gray-300">
                                                {{ $s['total'] }}
                                            </td>

                                            <td class="py-3 px-4 text-right">
                                                <a href="{{ route('faculty.dashboard', ['course_id' => $selectedCourse->course_id, 'date' => $s['session_date'], 'tab' => 'check']) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300 transition">
                                                    <span>Edit / View</span>
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-8 text-center text-sm text-gray-500">
                                                No attendance records have been saved for this course yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Modal for Student Attendance History -->
            <div x-show="showHistoryModal"
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showHistoryModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                         @click="showHistoryModal = false"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showHistoryModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-200 dark:border-gray-700">

                        <div class="bg-red-800 text-white p-4 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold" x-text="historyData.student_name || 'Student Attendance History'"></h3>
                                <p class="text-xs text-red-200" x-text="'ID: ' + (historyData.student_number || '') + ' • ' + (historyData.course || '')"></p>
                            </div>
                            <button @click="showHistoryModal = false" class="text-white hover:text-red-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 max-h-96 overflow-y-auto">
                            <template x-if="isLoadingHistory">
                                <div class="py-8 text-center text-sm text-gray-500">Loading attendance log...</div>
                            </template>

                            <template x-if="!isLoadingHistory && (!historyData.records || historyData.records.length === 0)">
                                <div class="py-8 text-center text-sm text-gray-500">No attendance records found for this student.</div>
                            </template>

                            <template x-if="!isLoadingHistory && historyData.records && historyData.records.length > 0">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50 uppercase text-gray-500 font-semibold">
                                        <tr>
                                            <th class="py-2 px-3 text-left">Date</th>
                                            <th class="py-2 px-3 text-center">Status</th>
                                            <th class="py-2 px-3 text-left">Remarks</th>
                                            <th class="py-2 px-3 text-center">Schedule</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                        <template x-for="rec in historyData.records" :key="rec.attendance_id">
                                            <tr>
                                                <td class="py-2.5 px-3 font-semibold text-gray-800 dark:text-gray-200" x-text="rec.session_date"></td>
                                                <td class="py-2.5 px-3 text-center">
                                                    <span :class="{
                                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': rec.status === 'present',
                                                        'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': rec.status === 'late',
                                                        'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300': rec.status === 'absent'
                                                    }" class="px-2 py-0.5 rounded font-bold uppercase text-[10px]" x-text="rec.status"></span>
                                                </td>
                                                <td class="py-2.5 px-3 text-gray-600 dark:text-gray-300 italic" x-text="rec.remarks || '—'"></td>
                                                <td class="py-2.5 px-3 text-center">
                                                    <span x-show="rec.is_out_of_schedule" class="text-[10px] text-amber-600 font-semibold">Out-of-Sched</span>
                                                    <span x-show="!rec.is_out_of_schedule" class="text-[10px] text-gray-400">Regular</span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </template>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/50 p-3 text-right">
                            <button @click="showHistoryModal = false" class="px-4 py-1.5 text-xs font-semibold rounded bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 hover:bg-gray-300">
                                Close
                            </button>
                        </div>
                    </div>
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
                historyData: {},

                matchesSearch(text) {
                    if (!this.searchQuery) return true;
                    return text.includes(this.searchQuery.toLowerCase().trim());
                },

                matchesSummaryFilter(status, isAtRisk, text) {
                    if (this.searchQuery && !text.includes(this.searchQuery.toLowerCase().trim())) {
                        return false;
                    }

                    if (this.statusFilter === 'all') return true;
                    if (this.statusFilter === 'dropped') return status === 'dropped';
                    if (this.statusFilter === 'at_risk') return isAtRisk;
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
                        this.isLoadingHistory = false;
                        alert('Unable to load attendance history.');
                    });
                }
            };
        }
    </script>
</x-app-layout>
