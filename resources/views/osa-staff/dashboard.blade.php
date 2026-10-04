@php
    $badgeFor = [
        'pending'  => 'badge-warning',
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-title title="OSA dashboard"
                      description="Manage student drops and readmission requests." />
    </x-slot>

    <div class="page space-y-6">

        {{-- Key figures --}}
        <section class="card overflow-hidden" aria-label="Summary">
            <div class="stat-grid grid-cols-2 lg:grid-cols-4">

                <div>
                    <p class="muted text-sm">Pending readmissions</p>
                    <p @class(['mt-1 text-3xl font-semibold tabular-nums', 'text-warn' => $pendingReadmissions > 0])>
                        {{ $pendingReadmissions }}
                    </p>
                    <a href="{{ route('osa.readmissions') }}" class="link mt-3 inline-block text-sm">
                        Review requests
                    </a>
                </div>

                <div>
                    <p class="muted text-sm">Approved</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $approvedReadmissions }}</p>
                </div>

                <div>
                    <p class="muted text-sm">Rejected</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $rejectedReadmissions }}</p>
                </div>

                <div>
                    <p class="muted text-sm">Total drop records</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums">{{ $totalDrops }}</p>
                    <a href="{{ route('osa.archive.drops') }}" class="link mt-3 inline-block text-sm">
                        View drop records
                    </a>
                </div>

            </div>
        </section>

        {{-- Recent requests --}}
        <section class="card overflow-hidden">

            <div class="card-header flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Recent readmission requests</h2>
                    <p class="muted mt-0.5 text-sm">The latest requests submitted by students.</p>
                </div>

                <a href="{{ route('osa.readmissions') }}" class="link shrink-0 text-sm">View all</a>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Course</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="is-end"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentReadmissions as $request)
                            <tr>
                                <td>
                                    <div class="font-medium">{{ $request->first_name }} {{ $request->last_name }}</div>
                                    <div class="muted text-xs">{{ $request->student_number }}</div>
                                </td>

                                <td>
                                    <div class="font-medium">{{ $request->subject_code }}</div>
                                    <div class="muted text-xs">{{ $request->course_name }}</div>
                                </td>

                                <td>{{ ucfirst(str_replace('_', ' ', (string) $request->reason_code)) }}</td>

                                <td>
                                    <span class="badge {{ $badgeFor[$request->status] ?? 'badge-neutral' }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>

                                <td class="is-end">
                                    <a href="{{ route('osa.readmissions.show', $request->readmission_id) }}"
                                       @class(['btn btn-sm', 'btn-primary' => $request->status === 'pending', 'btn-secondary' => $request->status !== 'pending'])>
                                        {{ $request->status === 'pending' ? 'Review request' : 'View request' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted py-12 text-center">
                                    No readmission requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</x-app-layout>