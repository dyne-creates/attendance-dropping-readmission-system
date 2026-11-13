<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Office of Student Affairs"
            title="Reports & Analytics"
            description="Institutional statistics, trends, and records for student drops, readmissions, and absences."
        />
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <x-flash-messages />

        @php
            $statusLabels = $filterOptions['statuses'] ?? [];
            $advancedCount = collect(['year_level', 'section', 'faculty_id'])->filter(fn ($k) => ! empty($filters[$k]))->count()
                + (empty($filters['status']) ? 0 : 1);

            $chips = [];
            if (! empty($filters['school_year_id'])) {
                $syOpt = collect($filterOptions['school_years'])->firstWhere('school_year_id', (int) $filters['school_year_id']);
                $chips['School Year'] = $syOpt ? $syOpt->year_label.' ('.$syOpt->semester.')' : '#'.$filters['school_year_id'];
            }
            if (! empty($filters['program'])) { $chips['Program'] = $filters['program']; }
            if (! empty($filters['year_level'])) { $chips['Year Level'] = 'Year '.$filters['year_level']; }
            if (! empty($filters['section'])) { $chips['Section'] = $filters['section']; }
            if (! empty($filters['faculty_id'])) {
                $facOpt = collect($filterOptions['faculty'])->firstWhere('faculty_id', (int) $filters['faculty_id']);
                $chips['Faculty'] = $facOpt->full_name ?? '#'.$filters['faculty_id'];
            }
            if (! empty($filters['status'])) {
                $chips['Status'] = collect($filters['status'])->map(fn ($s) => $statusLabels[$s] ?? $s)->implode(', ');
            }

            if ($startDate && $endDate) {
                $scopeLabel = $startDate->format('M d, Y').' to '.$endDate->format('M d, Y');
            } elseif ($startDate) {
                $scopeLabel = 'From '.$startDate->format('M d, Y');
            } elseif ($endDate) {
                $scopeLabel = 'Through '.$endDate->format('M d, Y');
            } else {
                $scopeLabel = 'All recorded history';
            }
            $reportGeneratedAt = now('Asia/Manila');
        @endphp

        {{-- Filter Panel --}}
        <section class="card report-filter-panel p-5 sm:p-6" aria-labelledby="filter-heading"
                 x-data="{ more: {{ $advancedCount > 0 ? 'true' : 'false' }} }">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 id="filter-heading" class="text-base font-semibold">Filters</h2>
                    <p class="muted mt-0.5 text-xs">Applies to the charts, tables, PDF report, and readmission extract.</p>
                </div>
            </div>

            <form id="filterForm" method="GET" action="{{ route('osa.reports') }}" class="space-y-4">
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                {{-- Always visible: the four filters OSA uses most --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="school_year_id" class="field-label">School Year & Semester</label>
                        <select id="school_year_id" name="school_year_id" class="field mt-1">
                            <option value="">All school years</option>
                            @foreach ($filterOptions['school_years'] as $sy)
                                <option value="{{ $sy->school_year_id }}" @selected(($filters['school_year_id'] ?? '') == $sy->school_year_id)>
                                    {{ $sy->year_label }} ({{ $sy->semester }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="program" class="field-label">Program</label>
                        <select id="program" name="program" class="field mt-1">
                            <option value="">All programs</option>
                            @foreach ($filterOptions['programs'] as $prog)
                                <option value="{{ $prog }}" @selected(($filters['program'] ?? '') === $prog)>{{ $prog }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date_from" class="field-label">Date From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="field mt-1" />
                        @error('date_from') <p class="field-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="date_to" class="field-label">Date To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="field mt-1" />
                        @error('date_to') <p class="field-error mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Quick ranges replace the old Year / Month / Quarter dropdowns --}}
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="muted">Quick range:</span>
                    <button type="button" class="btn btn-secondary btn-sm" data-range="month">This month</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-range="quarter">This quarter</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-range="year">This year</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-range="clear">Clear dates</button>
                </div>

                {{-- Advanced filters (collapsed unless one is active) --}}
                <div x-show="more" x-cloak x-transition class="space-y-4 border-t pt-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="year_level" class="field-label">Year Level</label>
                            <select id="year_level" name="year_level" class="field mt-1">
                                <option value="">All year levels</option>
                                @foreach ($filterOptions['year_levels'] as $yl)
                                    <option value="{{ $yl }}" @selected(($filters['year_level'] ?? '') == $yl)>Year {{ $yl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="section" class="field-label">Section</label>
                            <select id="section" name="section" class="field mt-1">
                                <option value="">All sections</option>
                                @foreach ($filterOptions['sections'] as $sec)
                                    <option value="{{ $sec }}" @selected(($filters['section'] ?? '') === $sec)>{{ $sec }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="faculty_id" class="field-label">Faculty Member</label>
                            <select id="faculty_id" name="faculty_id" class="field mt-1">
                                <option value="">All faculty</option>
                                @foreach ($filterOptions['faculty'] as $f)
                                    <option value="{{ $f->faculty_id }}" @selected(($filters['faculty_id'] ?? '') == $f->faculty_id)>{{ $f->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <fieldset>
                        <legend class="field-label mb-2">Case / request status</legend>
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                            @foreach ($statusLabels as $statusCode => $statusLabel)
                                <label class="inline-flex cursor-pointer items-center gap-1.5">
                                    <input type="checkbox" name="status[]" value="{{ $statusCode }}"
                                           @checked(in_array($statusCode, (array) ($filters['status'] ?? []), true))
                                           class="rounded border-gray-300 text-red-600 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-800" />
                                    <span>{{ $statusLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
                    <button type="button" @click="more = !more" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            :aria-expanded="more.toString()">
                        <svg class="h-4 w-4 transition-transform" :class="more && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span x-text="more ? 'Fewer filters' : 'More filters'"></span>
                        @if ($advancedCount > 0)
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">{{ $advancedCount }}</span>
                        @endif
                    </button>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('osa.reports') }}" class="btn btn-secondary btn-sm">Reset</a>
                        <button type="submit" class="btn btn-primary btn-sm px-6">Apply Filters</button>
                    </div>
                </div>
            </form>
        </section>

        {{-- Scope summary + export actions --}}
        <div class="flex flex-col gap-3 rounded-lg border bg-surface px-4 py-3 text-xs sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Showing:</span>
                <span class="rounded-full border px-2.5 py-0.5 font-medium">{{ $scopeLabel }}</span>
                @foreach ($chips as $chipLabel => $chipValue)
                    <span class="rounded-full border px-2.5 py-0.5"><span class="muted">{{ $chipLabel }}:</span> <span class="font-medium">{{ $chipValue }}</span></span>
                @endforeach
                <span class="muted">&bull; as of {{ $reportGeneratedAt->format('M d, Y h:i A') }}</span>
            </div>

            <button type="button" id="generatePdfBtn" onclick="generateReportPdf()" class="btn btn-primary btn-sm inline-flex shrink-0 items-center justify-center gap-1.5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span data-label>View PDF Report</span>
            </button>
        </div>

        {{-- Summary Statistics Grid --}}
        <section aria-label="Summary Statistics" class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-6">
            <div class="card report-kpi p-4">
                <p class="muted text-xs font-medium uppercase tracking-wider">Total Drops</p>
                <p class="mt-2 text-2xl font-bold tabular-nums">{{ $summary['total_drops'] }}</p>
                <p class="muted mt-1 text-xs">{{ $summary['total_students_dropped'] }} distinct students</p>
            </div>

            <div class="card report-kpi p-4">
                <p class="muted text-xs font-medium uppercase tracking-wider">Referrals</p>
                <p class="mt-2 text-2xl font-bold tabular-nums">{{ $summary['total_referrals'] }}</p>
                <p class="muted mt-1 text-xs">Instructor referrals</p>
            </div>

            <div class="card report-kpi p-4">
                <p class="muted text-xs font-medium uppercase tracking-wider">Readmission Requests</p>
                <p class="mt-2 text-2xl font-bold tabular-nums">{{ $summary['total_readmissions'] }}</p>
                <p class="muted mt-1 text-xs">{{ $summary['distinct_readmission_students'] }} students applied</p>
            </div>

            <div class="card report-kpi p-4">
                <p class="muted text-xs font-medium uppercase tracking-wider">Excused Cases</p>
                <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400 tabular-nums">{{ $summary['excused_cases'] }}</p>
                <p class="muted mt-1 text-xs">{{ $summary['not_excused_cases'] }} unexcused</p>
            </div>

            <div class="card report-kpi p-4">
                <p class="muted text-xs font-medium uppercase tracking-wider">Pending Cases</p>
                <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400 tabular-nums">{{ $summary['pending_cases'] }}</p>
                <p class="muted mt-1 text-xs">{{ $summary['approved_cases'] }} regular approved</p>
            </div>

            <div class="card report-kpi p-4">
                <p class="muted text-xs font-medium uppercase tracking-wider">Absence Hours</p>
                <p class="mt-2 text-2xl font-bold tabular-nums">{{ $summary['accumulated_absence_hours'] }}</p>
                <p class="muted mt-1 text-xs">{{ $summary['tardiness_count'] }} lates recorded</p>
            </div>
        </section>

        {{-- Graphs and Charts Section --}}
        <section aria-label="Visualizations" class="space-y-6">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                {{-- Chart 1: Students by Number of Drops --}}
                <div class="card report-chart-card p-5">
                    <div class="flex items-center justify-between pb-3 border-b mb-3">
                        <h3 class="text-sm font-semibold">1. Students by Number of Drops</h3>
                        <span class="muted text-xs">Drop distribution</span>
                    </div>
                    <div class="h-64 relative">
                        <canvas id="chartDrops"></canvas>
                    </div>
                </div>

                {{-- Chart 2: Excused vs Unexcused Cases --}}
                <div class="card report-chart-card p-5">
                    <div class="flex items-center justify-between pb-3 border-b mb-3">
                        <h3 class="text-sm font-semibold">2. Excused vs. Unexcused Cases</h3>
                        <span class="muted text-xs">Outcome ratio</span>
                    </div>
                    <div class="h-64 relative flex items-center justify-center">
                        <canvas id="chartExcused"></canvas>
                    </div>
                </div>

                {{-- Chart 3: Referral and Readmission Requests Over Time --}}
                <div class="card report-chart-card p-5">
                    <div class="flex items-center justify-between pb-3 border-b mb-3">
                        <h3 class="text-sm font-semibold">3. Referrals & Readmission Requests Over Time</h3>
                        <span class="muted text-xs">Monthly breakdown</span>
                    </div>
                    <div class="h-64 relative">
                        <canvas id="chartTimeline"></canvas>
                    </div>
                </div>

                {{-- Chart 4: Requests by Processing Status --}}
                <div class="card report-chart-card p-5">
                    <div class="flex items-center justify-between pb-3 border-b mb-3">
                        <h3 class="text-sm font-semibold">4. Requests by Processing Status</h3>
                        <span class="muted text-xs">Status categories</span>
                    </div>
                    <div class="h-64 relative">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>

                {{-- Chart 5: Accumulated Absence Hours by Subject --}}
                <div class="card report-chart-card p-5 lg:col-span-2">
                    <div class="flex items-center justify-between pb-3 border-b mb-3">
                        <h3 class="text-sm font-semibold">5. Accumulated Absence Hours by Subject Code</h3>
                        <span class="muted text-xs">Top subjects by total absences</span>
                    </div>
                    <div class="h-64 relative">
                        <canvas id="chartAbsence"></canvas>
                    </div>
                </div>
            </div>
        </section>

        {{-- Tabular Reports Section --}}
        <section id="report-tables" class="card overflow-hidden" aria-label="Detailed Tabular Reports">
            {{-- Tabs Header --}}
            <div class="border-b bg-surface px-4 pt-3 sm:px-6">
                <nav class="report-tab-nav -mb-px flex space-x-6 overflow-x-auto" role="tablist" aria-label="Report Tables">
                    <button type="button" id="report-tab-drops" role="tab" aria-controls="report-panel-drops"
                       aria-selected="{{ $activeTab === 'drops' ? 'true' : 'false' }}" tabindex="{{ $activeTab === 'drops' ? '0' : '-1' }}"
                       data-report-tab="drops"
                       @class([
                           'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors',
                           'border-red-600 text-red-600 dark:border-red-400 dark:text-red-400' => $activeTab === 'drops',
                           'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' => $activeTab !== 'drops',
                       ])>
                        Student Dropping Records
                        <span class="ml-2 rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                            {{ $drops->total() }}
                        </span>
                    </button>

                    <button type="button" id="report-tab-referrals" role="tab" aria-controls="report-panel-referrals"
                       aria-selected="{{ $activeTab === 'referrals' ? 'true' : 'false' }}" tabindex="{{ $activeTab === 'referrals' ? '0' : '-1' }}"
                       data-report-tab="referrals"
                       @class([
                           'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors',
                           'border-red-600 text-red-600 dark:border-red-400 dark:text-red-400' => $activeTab === 'referrals',
                           'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' => $activeTab !== 'referrals',
                       ])>
                        Faculty Referral Records
                        <span class="ml-2 rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                            {{ $referrals->total() }}
                        </span>
                    </button>

                    <button type="button" id="report-tab-readmissions" role="tab" aria-controls="report-panel-readmissions"
                       aria-selected="{{ $activeTab === 'readmissions' ? 'true' : 'false' }}" tabindex="{{ $activeTab === 'readmissions' ? '0' : '-1' }}"
                       data-report-tab="readmissions"
                       @class([
                           'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors',
                           'border-red-600 text-red-600 dark:border-red-400 dark:text-red-400' => $activeTab === 'readmissions',
                           'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' => $activeTab !== 'readmissions',
                       ])>
                        Readmission Requests & Outcomes
                        <span class="ml-2 rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                            {{ $readmissions->total() }}
                        </span>
                    </button>

                    <button type="button" id="report-tab-absences" role="tab" aria-controls="report-panel-absences"
                       aria-selected="{{ $activeTab === 'absences' ? 'true' : 'false' }}" tabindex="{{ $activeTab === 'absences' ? '0' : '-1' }}"
                       data-report-tab="absences"
                       @class([
                           'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors',
                           'border-red-600 text-red-600 dark:border-red-400 dark:text-red-400' => $activeTab === 'absences',
                           'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400' => $activeTab !== 'absences',
                       ])>
                        Absence & Attendance Records
                        <span class="ml-2 rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                            {{ $absences->total() }}
                        </span>
                    </button>
                </nav>
            </div>

            {{-- Table Content --}}
            <div class="report-table-shell p-0">
                <div id="report-panel-drops" role="tabpanel" aria-labelledby="report-tab-drops" tabindex="0"
                     data-report-panel="drops" @class(['hidden' => $activeTab !== 'drops'])>
                    <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Student ID</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">Program & Year</th>
                                <th scope="col">Course Offering</th>
                                <th scope="col">Faculty Dropper</th>
                                <th scope="col">Drop Number</th>
                                <th scope="col">Case Type</th>
                                <th scope="col">Absence Hours</th>
                                <th scope="col">Date Processed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($drops as $drop)
                                <tr>
                                    <td class="font-mono text-xs">{{ $drop->student_number }}</td>
                                    <td class="font-medium">{{ $drop->first_name }} {{ $drop->last_name }}</td>
                                    <td>{{ $drop->program ?? 'N/A' }} {{ $drop->year_level ? '- Yr '.$drop->year_level : '' }}</td>
                                    <td>
                                        <div class="font-medium">{{ $drop->subject_code }}</div>
                                        <div class="muted text-xs">{{ $drop->course_name }} ({{ $drop->section }})</div>
                                    </td>
                                    <td>{{ $drop->faculty_name }}</td>
                                    <td class="text-center font-semibold">{{ $drop->sequence_in_cycle }}</td>
                                    <td>
                                        @if ($drop->drop_type === \App\Models\DroppingTransaction::TYPE_PERMANENT)
                                            <span class="badge badge-danger">Permanent Drop</span>
                                        @elseif ($drop->drop_type === \App\Models\DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL)
                                            <span class="badge badge-neutral">Instructor Referral</span>
                                        @else
                                            <span class="badge badge-warning">Weekly Hours Threshold</span>
                                        @endif
                                    </td>
                                    <td class="tabular-nums">{{ $drop->absence_hours_at_drop }} hrs</td>
                                    <td>{{ \Carbon\Carbon::parse($drop->drop_date)->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-12 text-center muted">No student dropping records match the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    @if ($drops->hasPages())
                        <div class="p-4 border-t">
                            {{ $drops->withQueryString()->appends(['tab' => 'drops'])->fragment('report-tables')->links() }}
                        </div>
                    @endif
                </div>

                <div id="report-panel-referrals" role="tabpanel" aria-labelledby="report-tab-referrals" tabindex="0"
                     data-report-panel="referrals" @class(['hidden' => $activeTab !== 'referrals'])>
                    <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Student ID</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">Program</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Referring Faculty</th>
                                <th scope="col">Reason for Referral</th>
                                <th scope="col">Absence Hours</th>
                                <th scope="col">Referral Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($referrals as $ref)
                                <tr>
                                    <td class="font-mono text-xs">{{ $ref->student_number }}</td>
                                    <td class="font-medium">{{ $ref->first_name }} {{ $ref->last_name }}</td>
                                    <td>{{ $ref->program ?? 'N/A' }}</td>
                                    <td>
                                        <div class="font-medium">{{ $ref->subject_code }}</div>
                                        <div class="muted text-xs">{{ $ref->course_name }}</div>
                                    </td>
                                    <td>{{ $ref->faculty_name }}</td>
                                    <td class="max-w-xs truncate" title="{{ $ref->instructor_reason }}">
                                        {{ $ref->instructor_reason ?? 'Instructor initiated referral' }}
                                    </td>
                                    <td class="tabular-nums">{{ $ref->absence_hours_at_drop }} hrs</td>
                                    <td>{{ \Carbon\Carbon::parse($ref->drop_date)->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center muted">No instructor referral records match the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    @if ($referrals->hasPages())
                        <div class="p-4 border-t">
                            {{ $referrals->withQueryString()->appends(['tab' => 'referrals'])->fragment('report-tables')->links() }}
                        </div>
                    @endif
                </div>

                <div id="report-panel-readmissions" role="tabpanel" aria-labelledby="report-tab-readmissions" tabindex="0"
                     data-report-panel="readmissions" @class(['hidden' => $activeTab !== 'readmissions'])>
                    <div class="flex justify-end border-b p-4">
                        <a href="{{ route('osa.reports.readmissions.extract', request()->query()) }}" class="btn btn-secondary btn-sm">
                            Download readmission data
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Req #</th>
                                <th scope="col">Student ID</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">Course</th>
                                <th scope="col">Submission Date</th>
                                <th scope="col">Reason Code</th>
                                <th scope="col">Request Status</th>
                                <th scope="col">Processing Date</th>
                                <th scope="col">Processed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($readmissions as $req)
                                <tr>
                                    <td class="font-mono text-xs font-semibold">#{{ $req->readmission_id }} (Att. {{ $req->attempt_number }})</td>
                                    <td class="font-mono text-xs">{{ $req->student_number }}</td>
                                    <td class="font-medium">{{ $req->first_name }} {{ $req->last_name }}</td>
                                    <td>{{ $req->subject_code }}</td>
                                    <td>{{ \Carbon\Carbon::parse($req->submission_date)->format('M d, Y h:i A') }}</td>
                                    <td>{{ \App\Models\ReadmissionRequest::reasonLabel((string) $req->reason_code) }}</td>
                                    <td>
                                        @if ($req->outcome === 'excused')
                                            <span class="badge badge-success">Excused</span>
                                        @elseif ($req->outcome === 'not_excused')
                                            <span class="badge badge-danger">Not Excused</span>
                                        @elseif ($req->status === 'approved')
                                            <span class="badge badge-success">Approved</span>
                                        @elseif ($req->status === 'rejected')
                                            <span class="badge badge-danger">Rejected</span>
                                        @else
                                            <span class="badge badge-warning">Pending Review</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $req->processed_date ? \Carbon\Carbon::parse($req->processed_date)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="muted text-xs">{{ $req->processed_by_name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-12 text-center muted">No readmission requests match the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    @if ($readmissions->hasPages())
                        <div class="p-4 border-t">
                            {{ $readmissions->withQueryString()->appends(['tab' => 'readmissions'])->fragment('report-tables')->links() }}
                        </div>
                    @endif
                </div>

                <div id="report-panel-absences" role="tabpanel" aria-labelledby="report-tab-absences" tabindex="0"
                     data-report-panel="absences" @class(['hidden' => $activeTab !== 'absences'])>
                    <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Student ID</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">Program</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Section</th>
                                <th scope="col">Session Date</th>
                                <th scope="col">Attendance Mark</th>
                                <th scope="col">Hours Absent</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($absences as $rec)
                                <tr>
                                    <td class="font-mono text-xs">{{ $rec->student_number }}</td>
                                    <td class="font-medium">{{ $rec->first_name }} {{ $rec->last_name }}</td>
                                    <td>{{ $rec->program ?? 'N/A' }}</td>
                                    <td>
                                        <div class="font-medium">{{ $rec->subject_code }}</div>
                                        <div class="muted text-xs">{{ $rec->course_name }}</div>
                                    </td>
                                    <td>{{ $rec->section }}</td>
                                    <td>{{ \Carbon\Carbon::parse($rec->session_date)->format('M d, Y') }}</td>
                                    <td>
                                        @if ($rec->attendance_status === 'absent')
                                            <span class="badge badge-danger">Absent</span>
                                        @elseif ($rec->attendance_status === 'late')
                                            <span class="badge badge-warning">Late</span>
                                        @elseif ($rec->attendance_status === 'excused')
                                            <span class="badge badge-success">Excused</span>
                                        @else
                                            <span class="badge badge-neutral">{{ ucfirst($rec->attendance_status) }}</span>
                                        @endif
                                    </td>
                                    <td class="tabular-nums font-semibold">{{ $rec->hours_absent ?? '0.00' }} hrs</td>
                                    <td class="muted text-xs max-w-xs truncate">{{ $rec->remarks ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-12 text-center muted">No absence records match the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    @if ($absences->hasPages())
                        <div class="p-4 border-t">
                            {{ $absences->withQueryString()->appends(['tab' => 'absences'])->fragment('report-tables')->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Hidden form: charts + current filters are POSTed, server redirects to the viewer page --}}
        <form id="pdfExportForm" method="POST" action="{{ route('osa.reports.pdf.generate') }}" class="hidden">
            @csrf
            <input type="hidden" name="chart_drops" id="input_chart_drops">
            <input type="hidden" name="chart_excused" id="input_chart_excused">
            <input type="hidden" name="chart_timeline" id="input_chart_timeline">
            <input type="hidden" name="chart_status" id="input_chart_status">
            <input type="hidden" name="chart_absence" id="input_chart_absence">
        </form>
    </div>

    <script>
        window.reportCharts = {};

        /* ---------- Tabs ---------- */
        function setReportTab(tabName) {
            const selectedTab = document.querySelector(`[data-report-tab="${tabName}"]`);
            if (!selectedTab) return;

            const activeCls = ['border-red-600', 'text-red-600', 'dark:border-red-400', 'dark:text-red-400'];
            const idleCls = ['border-transparent', 'text-gray-500', 'hover:border-gray-300', 'hover:text-gray-700', 'dark:text-gray-400'];

            document.querySelectorAll('[data-report-tab]').forEach((tab) => {
                const isSelected = tab === selectedTab;
                tab.setAttribute('aria-selected', String(isSelected));
                tab.tabIndex = isSelected ? 0 : -1;
                tab.classList.remove(...activeCls, ...idleCls);
                tab.classList.add(...(isSelected ? activeCls : idleCls));
            });

            document.querySelectorAll('[data-report-panel]').forEach((panel) => {
                const isSelected = panel.dataset.reportPanel === tabName;
                panel.hidden = !isSelected;
                panel.classList.toggle('hidden', !isSelected);
            });

            const filterTab = document.querySelector('#filterForm input[name="tab"]');
            if (filterTab) filterTab.value = tabName;

            const url = new URL(window.location.href);
            if (url.searchParams.get('tab') !== tabName) {
                url.searchParams.set('tab', tabName);
                window.history.replaceState({ reportTab: tabName }, '', url);
            }
        }

        document.querySelectorAll('[data-report-tab]').forEach((tab, index, tabs) => {
            tab.addEventListener('click', () => setReportTab(tab.dataset.reportTab));
            tab.addEventListener('keydown', (event) => {
                const nextIndex = ['ArrowRight', 'ArrowDown'].includes(event.key) ? (index + 1) % tabs.length
                    : ['ArrowLeft', 'ArrowUp'].includes(event.key) ? (index - 1 + tabs.length) % tabs.length
                    : event.key === 'Home' ? 0
                    : event.key === 'End' ? tabs.length - 1
                    : null;
                if (nextIndex !== null) {
                    event.preventDefault();
                    tabs[nextIndex].focus();
                    setReportTab(tabs[nextIndex].dataset.reportTab);
                }
            });
        });

        window.addEventListener('popstate', () => {
            setReportTab(new URLSearchParams(window.location.search).get('tab') || 'drops');
        });

        /* ---------- Date inputs + quick ranges ---------- */
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');
        const pad = (n) => String(n).padStart(2, '0');
        const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

        function syncDateBounds() {
            dateTo.min = dateFrom.value || '';
            dateFrom.max = dateTo.value || '';
        }
        [dateFrom, dateTo].forEach((el) => el.addEventListener('change', syncDateBounds));
        syncDateBounds();

        document.querySelectorAll('[data-range]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const now = new Date();
                const y = now.getFullYear();
                const m = now.getMonth();
                let from = '', to = '';

                if (btn.dataset.range === 'month') {
                    from = iso(new Date(y, m, 1)); to = iso(new Date(y, m + 1, 0));
                } else if (btn.dataset.range === 'quarter') {
                    const q = Math.floor(m / 3) * 3;
                    from = iso(new Date(y, q, 1)); to = iso(new Date(y, q + 3, 0));
                } else if (btn.dataset.range === 'year') {
                    from = iso(new Date(y, 0, 1)); to = iso(new Date(y, 11, 31));
                }
                dateFrom.value = from;
                dateTo.value = to;
                syncDateBounds();
            });
        });

        /* ---------- Chart definitions (shared by screen + PDF) ---------- */
        function chartConfigs(theme, forPdf = false) {
            const chartData = @json($chartData);

            const base = forPdf
                ? { responsive: false, animation: false, devicePixelRatio: 2 }
                : { responsive: true, maintainAspectRatio: false };
            const opts = (extra = {}) => ({ ...base, color: theme.text, ...extra });
            const yAxis = (extra = {}) => ({ beginAtZero: true, grid: { color: theme.grid }, ticks: { precision: 0, color: theme.text }, ...extra });
            const xAxis = (extra = {}) => ({ grid: { display: false }, ticks: { color: theme.text }, ...extra });
            const hasData = (arr) => Array.isArray(arr) && arr.some((v) => Number(v) > 0);

            const excusedHasData = hasData(chartData.excused.data);

            return {
                drops: {
                    type: 'bar',
                    data: { labels: chartData.drops.labels, datasets: [{ label: 'Number of Students', data: chartData.drops.data,
                        backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444', '#dc2626', '#991b1b'], borderRadius: 4 }] },
                    options: opts({ plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.raw + ' student(s)' } } },
                        scales: { y: yAxis(), x: xAxis() } }),
                },
                excused: {
                    type: 'doughnut',
                    data: { labels: excusedHasData ? chartData.excused.labels : ['No data'],
                        datasets: [{ data: excusedHasData ? chartData.excused.data : [1],
                            backgroundColor: excusedHasData ? ['#10b981', '#ef4444', '#f59e0b'] : ['#d1d5db'],
                            borderWidth: 2, borderColor: theme.surface }] },
                    options: opts({ plugins: { legend: { position: 'bottom', labels: { color: theme.text } },
                        tooltip: { enabled: excusedHasData } } }),
                },
                timeline: {
                    type: 'bar',
                    data: { labels: chartData.timeline.labels, datasets: [
                        { label: 'Instructor Referrals', data: chartData.timeline.referrals, backgroundColor: '#6366f1', borderRadius: 4 },
                        { label: 'Readmission Requests', data: chartData.timeline.readmissions, backgroundColor: '#ec4899', borderRadius: 4 },
                    ] },
                    options: opts({ plugins: { legend: { position: 'bottom', labels: { color: theme.text } } },
                        scales: { y: yAxis(), x: xAxis() } }),
                },
                status: {
                    type: 'bar',
                    data: { labels: chartData.status.labels, datasets: [{ label: 'Requests', data: chartData.status.data,
                        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#6b7280'], borderRadius: 4 }] },
                    options: opts({ plugins: { legend: { display: false } }, scales: { y: yAxis(), x: xAxis() } }),
                },
                absence: {
                    type: 'bar',
                    data: { labels: chartData.absence.labels.length ? chartData.absence.labels : ['No absence records'],
                        datasets: [{ label: 'Total Absence Hours', data: chartData.absence.data.length ? chartData.absence.data : [0],
                            backgroundColor: '#b91c1c', borderRadius: 4 }] },
                    options: opts({ indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.raw + ' hrs' } } },
                        scales: { x: yAxis({ ticks: { color: theme.text } }), y: xAxis() } }),
                },
            };
        }

        const whiteBackground = {
            id: 'whiteBackground',
            beforeDraw(chart) {
                const { ctx, width, height } = chart;
                ctx.save();
                ctx.globalCompositeOperation = 'destination-over';
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, width, height);
                ctx.restore();
            },
        };

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js is not loaded.');
                return;
            }
            const isDark = document.documentElement.classList.contains('dark');
            Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

            const theme = isDark
                ? { text: '#d1d5db', grid: 'rgba(75, 85, 99, 0.25)', surface: '#1f2937' }
                : { text: '#374151', grid: 'rgba(229, 231, 235, 0.8)', surface: '#ffffff' };

            const ids = { drops: 'chartDrops', excused: 'chartExcused', timeline: 'chartTimeline', status: 'chartStatus', absence: 'chartAbsence' };
            const configs = chartConfigs(theme);
            Object.entries(ids).forEach(([name, id]) => {
                const canvas = document.getElementById(id);
                if (canvas) window.reportCharts[name] = new Chart(canvas.getContext('2d'), configs[name]);
            });
        });

        /* ---------- PDF: always render print-ready (light) chart images ---------- */
        function renderChartImagesForPdf() {
            const lightTheme = { text: '#374151', grid: 'rgba(209, 213, 219, 0.8)', surface: '#ffffff' };
            const configs = chartConfigs(lightTheme, true);
            const sizes = { absence: [1000, 340] };
            const images = {};

            Object.entries(configs).forEach(([name, config]) => {
                const [w, h] = sizes[name] || [560, 300];
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const chart = new Chart(canvas, { ...config, plugins: [whiteBackground] });
                images[name] = chart.toBase64Image('image/png', 1);
                chart.destroy();
            });

            return images;
        }

        function setPdfButtonBusy(busy) {
            const btn = document.getElementById('generatePdfBtn');
            if (!btn) return;
            btn.disabled = busy;
            btn.querySelector('[data-label]').textContent = busy ? 'Preparing report…' : 'View PDF Report';
        }
        // Restore the button when coming back via the browser's Back button (bfcache).
        window.addEventListener('pageshow', () => setPdfButtonBusy(false));

        function generateReportPdf() {
            const filtersForm = document.getElementById('filterForm');
            const pdfForm = document.getElementById('pdfExportForm');

            dateTo.setCustomValidity(
                dateFrom.value && dateTo.value && dateTo.value < dateFrom.value
                    ? 'The end date must be on or after the start date.' : ''
            );
            if (!filtersForm.reportValidity()) return;

            setPdfButtonBusy(true);

            try {
                const images = typeof Chart === 'undefined' ? {} : renderChartImagesForPdf();
                ['drops', 'excused', 'timeline', 'status', 'absence'].forEach((name) => {
                    document.getElementById(`input_chart_${name}`).value = images[name] || '';
                });

                pdfForm.querySelectorAll('[data-report-filter]').forEach((input) => input.remove());
                new FormData(filtersForm).forEach((value, name) => {
                    if (name === 'tab' || value === '') return;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    input.dataset.reportFilter = 'true';
                    pdfForm.appendChild(input);
                });
            } catch (error) {
                console.error('Unable to prepare the report PDF.', error);
                setPdfButtonBusy(false);
                const message = 'The report could not be prepared. Please try again.';
                typeof window.showAppAlert === 'function' ? window.showAppAlert(message, 'Report export failed') : window.alert(message);
                return;
            }

            pdfForm.submit(); // same tab: server builds the PDF, then redirects to the viewer page
        }
        window.generateReportPdf = generateReportPdf;

        [dateFrom, dateTo].forEach((input) => input.addEventListener('input', () => dateTo.setCustomValidity('')));
    </script>
</x-app-layout>