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

        {{-- Filter Panel --}}
        <section class="card report-filter-panel p-5 sm:p-6" aria-labelledby="filter-heading">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b pb-4 mb-5">
                <div>
                    <h2 id="filter-heading" class="text-base font-semibold">Report Filters</h2>
                    <p class="muted text-xs mt-0.5">Filter records across dropping, readmission, referral, and attendance datasets.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="previewReportPdf()" class="btn btn-primary btn-sm inline-flex items-center gap-1.5" title="Preview report before download">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Preview Report</span>
                    </button>
                    <a href="{{ route('osa.reports') }}" class="btn btn-secondary btn-sm" title="Clear all filters">
                        Reset Filters
                    </a>
                </div>
            </div>

            <form id="filterForm" method="GET" action="{{ route('osa.reports') }}" class="space-y-4">
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Date From --}}
                    <div>
                        <label for="date_from" class="field-label">Date From</label>
                        <input id="date_from" name="date_from" type="date"
                               value="{{ $filters['date_from'] ?? '' }}"
                               class="field mt-1" />
                        @error('date_from')
                            <p class="field-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Date To --}}
                    <div>
                        <label for="date_to" class="field-label">Date To</label>
                        <input id="date_to" name="date_to" type="date"
                               value="{{ $filters['date_to'] ?? '' }}"
                               class="field mt-1" />
                        @error('date_to')
                            <p class="field-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Predefined Year --}}
                    <div>
                        <label for="year" class="field-label">Specific Year</label>
                        <select id="year" name="year" class="field mt-1">
                            <option value="">All Years</option>
                            @foreach ($filterOptions['years'] as $yr)
                                <option value="{{ $yr }}" @selected(($filters['year'] ?? '') == $yr)>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Predefined Month / Quarter --}}
                    <div>
                        <label for="month" class="field-label">Specific Month</label>
                        <select id="month" name="month" class="field mt-1">
                            <option value="">All Months</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected(($filters['month'] ?? '') == $m)>
                                    {{ \Carbon\Carbon::create(2026, $m, 1)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Quarter --}}
                    <div>
                        <label for="quarter" class="field-label">Specific Quarter</label>
                        <select id="quarter" name="quarter" class="field mt-1">
                            <option value="">All Quarters</option>
                            <option value="1" @selected(($filters['quarter'] ?? '') == '1')>Q1 (Jan - Mar)</option>
                            <option value="2" @selected(($filters['quarter'] ?? '') == '2')>Q2 (Apr - Jun)</option>
                            <option value="3" @selected(($filters['quarter'] ?? '') == '3')>Q3 (Jul - Sep)</option>
                            <option value="4" @selected(($filters['quarter'] ?? '') == '4')>Q4 (Oct - Dec)</option>
                        </select>
                    </div>

                    {{-- School Year --}}
                    <div>
                        <label for="school_year_id" class="field-label">School Year & Semester</label>
                        <select id="school_year_id" name="school_year_id" class="field mt-1">
                            <option value="">All School Years</option>
                            @foreach ($filterOptions['school_years'] as $sy)
                                <option value="{{ $sy->school_year_id }}" @selected(($filters['school_year_id'] ?? '') == $sy->school_year_id)>
                                    {{ $sy->year_label }} ({{ $sy->semester }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Program / Course --}}
                    <div>
                        <label for="program" class="field-label">Course / Program</label>
                        <select id="program" name="program" class="field mt-1">
                            <option value="">All Programs</option>
                            @foreach ($filterOptions['programs'] as $prog)
                                <option value="{{ $prog }}" @selected(($filters['program'] ?? '') === $prog)>
                                    {{ $prog }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Year Level --}}
                    <div>
                        <label for="year_level" class="field-label">Year Level</label>
                        <select id="year_level" name="year_level" class="field mt-1">
                            <option value="">All Year Levels</option>
                            @foreach ($filterOptions['year_levels'] as $yl)
                                <option value="{{ $yl }}" @selected(($filters['year_level'] ?? '') == $yl)>
                                    Year {{ $yl }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Section --}}
                    <div>
                        <label for="section" class="field-label">Section</label>
                        <select id="section" name="section" class="field mt-1">
                            <option value="">All Sections</option>
                            @foreach ($filterOptions['sections'] as $sec)
                                <option value="{{ $sec }}" @selected(($filters['section'] ?? '') === $sec)>
                                    {{ $sec }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Faculty Member --}}
                    <div>
                        <label for="faculty_id" class="field-label">Faculty Member</label>
                        <select id="faculty_id" name="faculty_id" class="field mt-1">
                            <option value="">All Faculty</option>
                            @foreach ($filterOptions['faculty'] as $f)
                                <option value="{{ $f->faculty_id }}" @selected(($filters['faculty_id'] ?? '') == $f->faculty_id)>
                                    {{ $f->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Case / Request Status Checkboxes --}}
                <div class="pt-2 border-t mt-4">
                    <span class="field-label mb-2">Case & Request Statuses:</span>
                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        @foreach ($filterOptions['statuses'] as $statusCode => $statusLabel)
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="status[]" value="{{ $statusCode }}"
                                       @checked(in_array($statusCode, (array) ($filters['status'] ?? []), true))
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-800" />
                                <span>{{ $statusLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="btn btn-primary btn-sm px-6">
                        Apply Filters
                    </button>
                </div>
            </form>
        </section>

        {{-- Active Filter Notification / Scope Banner --}}
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs muted bg-surface px-4 py-3 rounded-lg border">
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Reporting Scope:</span>
                @if ($startDate && $endDate)
                    <span>{{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}</span>
                @else
                    <span>All-time records</span>
                @endif
                @if (!empty($filters['program']))
                    &bull; Program: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $filters['program'] }}</span>
                @endif
                @if (!empty($filters['section']))
                    &bull; Section: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $filters['section'] }}</span>
                @endif
            </div>
            <div>
                @php($reportGeneratedAt = now('Asia/Manila'))
                <span>Records Generated: <time id="report-live-clock" data-live-clock
                    data-timestamp="{{ $reportGeneratedAt->getTimestamp() * 1000 }}"
                    datetime="{{ $reportGeneratedAt->toIso8601String() }}">{{ $reportGeneratedAt->format('M d, Y - h:i A') }}</time></span>
            </div>
        </div>

        {{-- Summary Statistics Grid --}}
        <section aria-label="Summary Statistics" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
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
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
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
                    @if ($drops->hasPages())
                        <div class="p-4 border-t">
                            {{ $drops->appends(['tab' => 'drops'])->links() }}
                        </div>
                    @endif
                </div>

                <div id="report-panel-referrals" role="tabpanel" aria-labelledby="report-tab-referrals" tabindex="0"
                     data-report-panel="referrals" @class(['hidden' => $activeTab !== 'referrals'])>
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
                    @if ($referrals->hasPages())
                        <div class="p-4 border-t">
                            {{ $referrals->appends(['tab' => 'referrals'])->links() }}
                        </div>
                    @endif
                </div>

                <div id="report-panel-readmissions" role="tabpanel" aria-labelledby="report-tab-readmissions" tabindex="0"
                     data-report-panel="readmissions" @class(['hidden' => $activeTab !== 'readmissions'])>
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
                    @if ($readmissions->hasPages())
                        <div class="p-4 border-t">
                            {{ $readmissions->appends(['tab' => 'readmissions'])->links() }}
                        </div>
                    @endif
                </div>

                <div id="report-panel-absences" role="tabpanel" aria-labelledby="report-tab-absences" tabindex="0"
                     data-report-panel="absences" @class(['hidden' => $activeTab !== 'absences'])>
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
                    @if ($absences->hasPages())
                        <div class="p-4 border-t">
                            {{ $absences->appends(['tab' => 'absences'])->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Hidden Form for PDF Export --}}
        <form id="pdfExportForm" method="POST" action="{{ route('osa.reports.export-pdf') }}" class="hidden">
            @csrf
            <input type="hidden" name="chart_drops" id="input_chart_drops">
            <input type="hidden" name="chart_excused" id="input_chart_excused">
            <input type="hidden" name="chart_timeline" id="input_chart_timeline">
            <input type="hidden" name="chart_status" id="input_chart_status">
            <input type="hidden" name="chart_absence" id="input_chart_absence">
        </form>

        <div id="reportPdfPreview" x-data="{ open: false }" x-show="open"
             @report-preview-open.window="open = true" @keydown.escape.window="open = false"
             @click.self="open = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
             style="display: none;">
            <div class="flex h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-lg bg-white dark:bg-gray-800"
                 role="dialog" aria-modal="true" aria-labelledby="report-preview-title">
                <div class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 id="report-preview-title" class="text-lg font-semibold">
                            Student Attendance, Dropping &amp; Readmission Report
                        </h3>
                        <p class="muted text-sm">
                            @if ($startDate && $endDate)
                                {{ $startDate->format('M j, Y') }} to {{ $endDate->format('M j, Y') }}
                            @elseif ($startDate)
                                From {{ $startDate->format('M j, Y') }}
                            @elseif ($endDate)
                                Through {{ $endDate->format('M j, Y') }}
                            @else
                                All Recorded History
                            @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" onclick="exportReportPdf()" class="btn btn-primary btn-sm">
                            Download PDF
                        </button>
                        <button type="button" @click="open = false" class="btn btn-secondary btn-sm">
                            Close
                        </button>
                    </div>
                </div>
                <iframe name="pdfPreviewIframe" title="PDF report preview" class="min-h-0 w-full flex-1"
                        src="about:blank"></iframe>
            </div>
        </div>
    </div>

    <script>
        window.reportCharts = {};

        function setReportTab(tabName) {
            const selectedTab = document.querySelector(`[data-report-tab="${tabName}"]`);
            if (!selectedTab) {
                return;
            }

            document.querySelectorAll('[data-report-tab]').forEach((tab) => {
                const isSelected = tab === selectedTab;
                tab.setAttribute('aria-selected', String(isSelected));
                tab.tabIndex = isSelected ? 0 : -1;
                tab.classList.remove(
                    'border-red-600',
                    'text-red-600',
                    'dark:border-red-400',
                    'dark:text-red-400',
                    'border-transparent',
                    'text-gray-500',
                    'hover:border-gray-300',
                    'hover:text-gray-700',
                    'dark:text-gray-400'
                );
                tab.classList.add(...(isSelected
                    ? ['border-red-600', 'text-red-600', 'dark:border-red-400', 'dark:text-red-400']
                    : ['border-transparent', 'text-gray-500', 'hover:border-gray-300', 'hover:text-gray-700', 'dark:text-gray-400']));
            });

            document.querySelectorAll('[data-report-panel]').forEach((panel) => {
                const isSelected = panel.dataset.reportPanel === tabName;
                panel.hidden = !isSelected;
                panel.classList.toggle('hidden', !isSelected);
            });

            const filterTab = document.querySelector('#filterForm input[name="tab"]');
            if (filterTab) {
                filterTab.value = tabName;
            }

            const url = new URL(window.location.href);
            if (url.searchParams.get('tab') !== tabName) {
                url.searchParams.set('tab', tabName);
                window.history.replaceState({ reportTab: tabName }, '', url);
            }
        }

        document.querySelectorAll('[data-report-tab]').forEach((tab, index, tabs) => {
            tab.addEventListener('click', () => setReportTab(tab.dataset.reportTab));
            tab.addEventListener('keydown', (event) => {
                const nextIndex = event.key === 'ArrowRight' || event.key === 'ArrowDown'
                    ? (index + 1) % tabs.length
                    : event.key === 'ArrowLeft' || event.key === 'ArrowUp'
                        ? (index - 1 + tabs.length) % tabs.length
                        : event.key === 'Home'
                            ? 0
                            : event.key === 'End'
                                ? tabs.length - 1
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

        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');

        const reportClock = document.querySelector('[data-live-clock]');
        if (reportClock) {
            const serverTimestamp = Number(reportClock.dataset.timestamp);
            const pageLoadedAt = performance.now();
            const dateFormatter = new Intl.DateTimeFormat('en-US', {
                timeZone: 'Asia/Manila',
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
            });

            const updateReportClock = () => {
                const formattedParts = dateFormatter.formatToParts(
                    new Date(serverTimestamp + performance.now() - pageLoadedAt)
                );
                const getPart = (type) => formattedParts.find((part) => part.type === type)?.value;
                const date = `${getPart('month')} ${getPart('day')}, ${getPart('year')}`;
                const time = `${getPart('hour')}:${getPart('minute')} ${getPart('dayPeriod')}`;

                reportClock.textContent = `${date} - ${time}`;
            };

            updateReportClock();
            window.setInterval(updateReportClock, 1000);
        }

        function submitReportPdf(preview) {
            const filtersForm = document.getElementById('filterForm');
            const pdfForm = document.getElementById('pdfExportForm');

            dateTo.setCustomValidity(
                dateFrom.value && dateTo.value && dateTo.value < dateFrom.value
                    ? 'The end date must be on or after the start date.'
                    : ''
            );
            if (!filtersForm.reportValidity()) {
                return;
            }

            try {
                const chartFields = [
                    ['input_chart_drops', 'drops'],
                    ['input_chart_excused', 'excused'],
                    ['input_chart_timeline', 'timeline'],
                    ['input_chart_status', 'status'],
                    ['input_chart_absence', 'absence'],
                ];

                chartFields.forEach(([inputId, chartName]) => {
                    const input = document.getElementById(inputId);
                    const chart = window.reportCharts[chartName];
                    input.value = chart ? chart.toBase64Image('image/png', 1) : '';
                });

                pdfForm.querySelectorAll('[data-report-filter]').forEach((input) => input.remove());
                new FormData(filtersForm).forEach((value, name) => {
                    if (name === 'tab') {
                        return;
                    }

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    input.dataset.reportFilter = 'true';
                    pdfForm.appendChild(input);
                });
            } catch (error) {
                console.error('Unable to prepare the report PDF.', error);
                if (typeof window.showAppAlert === 'function') {
                    window.showAppAlert('The report could not be prepared. Please try again.', 'Report export failed');
                } else {
                    window.alert('The report could not be prepared. Please try again.');
                }

                return;
            }

            pdfForm.action = preview
                ? @json(route('osa.reports.preview-pdf'))
                : @json(route('osa.reports.export-pdf'));
            pdfForm.target = preview ? 'pdfPreviewIframe' : '_self';

            if (preview) {
                window.dispatchEvent(new CustomEvent('report-preview-open'));
            }

            pdfForm.submit();
        }

        window.previewReportPdf = () => submitReportPdf(true);
        window.exportReportPdf = () => submitReportPdf(false);

        [dateFrom, dateTo].forEach((input) => {
            input.addEventListener('input', () => dateTo.setCustomValidity(''));
        });
    </script>

    {{-- Chart.js Initialization & Export Scripts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js is not loaded.');
                return;
            }

            const chartData = @json($chartData);
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#d1d5db' : '#374151';
            const gridColor = isDark ? 'rgba(75, 85, 99, 0.25)' : 'rgba(229, 231, 235, 0.8)';

            // Common responsive config
            Chart.defaults.color = textColor;
            Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

            // 1. Chart Drops (Bar)
            const ctxDrops = document.getElementById('chartDrops')?.getContext('2d');
            let chartDrops = null;
            if (ctxDrops) {
                chartDrops = new Chart(ctxDrops, {
                    type: 'bar',
                    data: {
                        labels: chartData.drops.labels,
                        datasets: [{
                            label: 'Number of Students',
                            data: chartData.drops.data,
                            backgroundColor: ['#3b82f6', '#f59e0b', '#ef4444', '#dc2626', '#991b1b'],
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => ctx.raw + ' student(s)' } }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // 2. Chart Excused (Doughnut)
            const ctxExcused = document.getElementById('chartExcused')?.getContext('2d');
            let chartExcused = null;
            if (ctxExcused) {
                chartExcused = new Chart(ctxExcused, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.excused.labels,
                        datasets: [{
                            data: chartData.excused.data,
                            backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                            borderWidth: 2,
                            borderColor: isDark ? '#1f2937' : '#ffffff',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }

            // 3. Chart Timeline (Bar / Line)
            const ctxTimeline = document.getElementById('chartTimeline')?.getContext('2d');
            let chartTimeline = null;
            if (ctxTimeline) {
                chartTimeline = new Chart(ctxTimeline, {
                    type: 'bar',
                    data: {
                        labels: chartData.timeline.labels,
                        datasets: [
                            {
                                label: 'Instructor Referrals',
                                data: chartData.timeline.referrals,
                                backgroundColor: '#6366f1',
                                borderRadius: 4,
                            },
                            {
                                label: 'Readmission Requests',
                                data: chartData.timeline.readmissions,
                                backgroundColor: '#ec4899',
                                borderRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // 4. Chart Status (Bar)
            const ctxStatus = document.getElementById('chartStatus')?.getContext('2d');
            let chartStatus = null;
            if (ctxStatus) {
                chartStatus = new Chart(ctxStatus, {
                    type: 'bar',
                    data: {
                        labels: chartData.status.labels,
                        datasets: [{
                            label: 'Requests',
                            data: chartData.status.data,
                            backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#6b7280'],
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // 5. Chart Absence (Horizontal Bar)
            const ctxAbsence = document.getElementById('chartAbsence')?.getContext('2d');
            let chartAbsence = null;
            if (ctxAbsence) {
                chartAbsence = new Chart(ctxAbsence, {
                    type: 'bar',
                    data: {
                        labels: chartData.absence.labels.length > 0 ? chartData.absence.labels : ['No absence records'],
                        datasets: [{
                            label: 'Total Absence Hours',
                            data: chartData.absence.data.length > 0 ? chartData.absence.data : [0],
                            backgroundColor: '#b91c1c',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => ctx.raw + ' hrs' } }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            window.reportCharts = {
                drops: chartDrops,
                excused: chartExcused,
                timeline: chartTimeline,
                status: chartStatus,
                absence: chartAbsence,
            };
        });
    </script>
</x-app-layout>
