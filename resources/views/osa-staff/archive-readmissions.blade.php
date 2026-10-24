<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Records"
            title="Readmission Archive"
            description="Completed readmission requests and their decisions."
        />
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <section class="card overflow-hidden">
            <div class="card-header flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Processed requests</h2>
                    <p class="mt-0.5 text-sm muted">Approved, excused, and rejected readmission requests.</p>
                </div>
                <a href="{{ route('osa.readmissions.export') }}" class="btn btn-secondary btn-sm">
                    Export all to Excel
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Course</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Drop level</th>
                            <th scope="col">Channel</th>
                            <th scope="col">Status</th>
                            <th scope="col">Processed</th>
                            <th scope="col">Form</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $record->first_name }} {{ $record->last_name }}</p>
                                    <p class="mt-0.5 text-xs muted">{{ $record->student_number }}</p>
                                </td>

                                <td>
                                    <p class="font-medium">{{ $record->subject_code }}</p>
                                    <p class="mt-0.5 text-xs muted">{{ $record->course_name }}</p>
                                </td>

                                <td>{{ \App\Models\ReadmissionRequest::reasonLabel((string) $record->reason_code) }}</td>

                                <td>
                                    @if ($record->drop_type === \App\Models\DroppingTransaction::TYPE_PERMANENT)
                                        <span class="badge badge-danger">Permanent · {{ $record->absence_hours_at_drop }} hrs</span>
                                    @elseif ($record->drop_type === \App\Models\DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL)
                                        <span class="badge badge-neutral">Instructor referral</span>
                                    @else
                                        <span class="badge badge-warning">Weekly hours · {{ $record->absence_hours_at_drop }} hrs</span>
                                    @endif
                                    @if ($record->instructor_reason)
                                        <p class="mt-1 text-xs muted">{{ $record->instructor_reason }}</p>
                                    @endif
                                </td>

                                <td><span class="badge badge-neutral">{{ strtoupper($record->channel) }}</span></td>

                                <td>
                                    @if (($record->outcome ?? $record->status) === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif (($record->outcome ?? $record->status) === 'excused')
                                        <span class="badge badge-success">Excused</span>
                                        <p class="mt-1 text-xs muted">
                                            {{ Carbon\Carbon::parse($record->excused_from)->format('M d, Y') }}
                                            – {{ Carbon\Carbon::parse($record->excused_through)->format('M d, Y') }};
                                            {{ number_format((float) $record->excused_hours_deducted, 2) }} hrs deducted
                                        </p>
                                    @elseif (($record->outcome ?? $record->status) === 'not_excused')
                                        <span class="badge badge-warning">Not excused</span>
                                    @elseif (($record->outcome ?? $record->status) === 'rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                    @else
                                        <span class="badge badge-neutral">{{ ucfirst((string) $record->status) }}</span>
                                    @endif
                                </td>

                                <td>{{ $record->processed_date ? Carbon\Carbon::parse($record->processed_date)->format('M d, Y') : '—' }}</td>
                                <td>
                                    <a href="{{ route('osa.readmissions.show', $record->readmission_id) }}"
                                       class="btn btn-secondary btn-sm">
                                        View attempt {{ $record->attempt_number }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center muted">
                                    No processed readmission requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</x-app-layout>