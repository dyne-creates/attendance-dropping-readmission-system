@php
    $badgeFor = [
        'present' => 'badge-success',
        'late'    => 'badge-warning',
        'absent'  => 'badge-danger',
    ];

    $groups = $enrollments->groupBy(
        fn ($enrollment) => $enrollment->course->schoolYear->semester . ', ' . $enrollment->course->schoolYear->year_label
    );

    $activeCount = $enrollments->where('status', '!=', 'dropped')->count();
    $absenceTotal = (int) $enrollments->sum('absent_count');

    $summary = $enrollments->isEmpty()
        ? 'Subjects and attendance records appear here once you are enrolled.'
        : $activeCount . ' active ' . Str::plural('subject', $activeCount)
            . ', ' . $absenceTotal . ' ' . Str::plural('absence', $absenceTotal) . ' recorded';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-title title="My attendance" :description="$summary" />
    </x-slot>

    <div class="page">

        @forelse ($groups as $term => $items)
            <section @class(['mt-10' => ! $loop->first]) aria-labelledby="term-{{ $loop->index }}">

                <h2 id="term-{{ $loop->index }}" class="mb-3 text-base font-semibold">
                    {{ $term }}
                </h2>

                <div class="space-y-3">
                    @foreach ($items as $enrollment)
                        @php
                            $days = collect($enrollment->grading_periods)
                                ->flatMap(fn ($period) => $period['attendance_records'])
                                ->sortBy('session_date')
                                ->values();
                        @endphp

                        <details class="card disclosure overflow-hidden">

                            <summary class="p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-semibold">{{ $enrollment->course->course_name }}</h3>

                                            @if ($enrollment->status === 'dropped')
                                                <span class="badge badge-neutral">Dropped</span>
                                            @endif
                                        </div>

                                        <p class="muted mt-0.5 text-sm">
                                            {{ $enrollment->course->subject_code }}, section {{ $enrollment->course->section }}
                                        </p>
                                    </div>

                                    <svg class="chevron muted mt-1 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                         stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                                    </svg>
                                </div>

                                @if ($enrollment->absence_warning === 'approaching_limit')
                                    <p class="alert alert-warning mt-4" role="status">
                                        Absence warning: {{ number_format($enrollment->absence_hours, 2) }} of
                                        {{ number_format($enrollment->maximum_absence_hours, 2) }} allowable absence hours
                                        used in this subject (80% warning level).
                                    </p>
                                @elseif ($enrollment->absence_warning === 'limit_reached')
                                    <p class="alert alert-danger mt-4" role="alert">
                                        Maximum absence limit reached:
                                        {{ number_format($enrollment->absence_hours, 2) }} of
                                        {{ number_format($enrollment->maximum_absence_hours, 2) }} allowable hours used.
                                        Contact your instructor or the Office of Student Affairs.
                                    </p>
                                @endif

                                @if ($days->isNotEmpty())
                                    <div class="strip mt-4" role="img"
                                         aria-label="Attendance by class day: {{ $enrollment->present_count }} present, {{ $enrollment->late_count }} late, {{ $enrollment->absent_count }} absent">
                                        @foreach ($days as $day)
                                            <span class="tick tick-{{ $day->status }}" aria-hidden="true"
                                                  title="{{ $day->session_date->format('M j, Y') }}: {{ ucfirst($day->status) }}"></span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="muted mt-4 text-sm">No class days recorded yet.</p>
                                @endif

                                <x-attendance-counts
                                    class="mt-3"
                                    :total="$enrollment->attendance_records_count"
                                    :present="$enrollment->present_count"
                                    :late="$enrollment->late_count"
                                    :absent="$enrollment->absent_count" />
                            </summary>

                            <div class="border-t">
                                @foreach ($enrollment->grading_periods as $gradingPeriod)
                                    <details class="disclosure @unless ($loop->first) border-t @endunless">

                                        <summary class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                                            <span class="flex items-center gap-2 text-sm font-medium">
                                                <svg class="chevron muted h-4 w-4 shrink-0" fill="none" stroke="currentColor"
                                                     stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                                                </svg>
                                                {{ $gradingPeriod['label'] }}
                                            </span>

                                            <x-attendance-counts
                                                :total="$gradingPeriod['attendance_records']->count()"
                                                :present="$gradingPeriod['counts']['present']"
                                                :late="$gradingPeriod['counts']['late']"
                                                :absent="$gradingPeriod['counts']['absent']" />
                                        </summary>

                                        <div class="overflow-x-auto border-t">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Class day</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Remarks</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($gradingPeriod['attendance_records'] as $record)
                                                        <tr>
                                                            <td class="whitespace-nowrap">
                                                                {{ $record->session_date->format('l, M j, Y') }}
                                                            </td>
                                                            <td>
                                                                <span class="badge {{ $badgeFor[$record->status] ?? 'badge-neutral' }}">
                                                                    {{ ucfirst($record->status) }}
                                                                </span>
                                                            </td>
                                                            <td class="muted">{{ $record->remarks ?? '—' }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3" class="muted py-8 text-center">
                                                                No class days have been recorded for
                                                                {{ strtolower($gradingPeriod['label']) }}.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                @endforeach

                            </div>
                        </details>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="card px-6 py-12 text-center">
                <p class="font-medium">You don’t have any subjects yet.</p>
                <p class="muted mt-1 text-sm">
                    Your subjects and attendance summaries will appear here when you’re enrolled.
                </p>
            </div>
        @endforelse

    </div>
</x-app-layout>