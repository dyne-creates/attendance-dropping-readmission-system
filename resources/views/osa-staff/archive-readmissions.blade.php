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
            <div class="card-header">
                <h2 class="text-lg font-semibold">Processed requests</h2>
                <p class="mt-0.5 text-sm muted">Approved and rejected readmission requests.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Course</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Channel</th>
                            <th scope="col">Status</th>
                            <th scope="col">Processed</th>
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

                                <td>{{ ucfirst(str_replace('_', ' ', (string) $record->reason_code)) }}</td>

                                <td><span class="badge badge-neutral">{{ strtoupper($record->channel) }}</span></td>

                                <td>
                                    @if ($record->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif ($record->status === 'rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                    @else
                                        <span class="badge badge-neutral">{{ ucfirst((string) $record->status) }}</span>
                                    @endif
                                </td>

                                <td>{{ $record->processed_date ? Carbon\Carbon::parse($record->processed_date)->format('M d, Y') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center muted">
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