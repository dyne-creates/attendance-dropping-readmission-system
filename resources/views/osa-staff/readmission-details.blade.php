@php
    $statusBadges = [
        'pending'  => ['badge-warning', 'Pending'],
        'approved' => ['badge-success', 'Approved'],
        'rejected' => ['badge-danger',  'Rejected'],
    ];
    [$statusClass, $statusLabel] = $statusBadges[$request->status] ?? ['badge-neutral', ucfirst((string) $request->status)];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Review"
            title="Review Readmission Request"
            description="Review the student's information before making a decision."
        >
            <x-slot name="actions">
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>

                <a href="{{ route('osa.readmissions') }}" class="btn btn-secondary btn-sm">
                    Back to requests
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <!-- Student -->
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Student information</h2>
                </div>

                <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Full name</dt>
                        <dd class="mt-1 text-sm font-semibold">{{ $request->first_name }} {{ $request->last_name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Student number</dt>
                        <dd class="mt-1 text-sm">{{ $request->student_number }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Year level</dt>
                        <dd class="mt-1 text-sm">{{ $request->year_level }}</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Program</dt>
                        <dd class="mt-1 text-sm">{{ $request->program }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Course -->
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Course information</h2>
                </div>

                <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium muted">Subject code</dt>
                        <dd class="mt-1 text-sm font-semibold">{{ $request->subject_code }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Section</dt>
                        <dd class="mt-1 text-sm">{{ $request->section }}</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Course</dt>
                        <dd class="mt-1 text-sm">{{ $request->course_name }}</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Faculty</dt>
                        <dd class="mt-1 text-sm">{{ $request->faculty_name }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Dropping -->
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Dropping information</h2>
                </div>

                <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium muted">Drop date</dt>
                        <dd class="mt-1 text-sm">{{ $request->drop_date ? Carbon\Carbon::parse($request->drop_date)->format('M d, Y') : '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Absence hours</dt>
                        <dd class="mt-1 text-sm">{{ $request->absence_hours_at_drop }} hrs</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Drop transaction</dt>
                        <dd class="mt-1 text-sm font-semibold">#{{ $request->drop_id }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Request -->
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">Readmission request</h2>
                </div>

                <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium muted">Reason</dt>
                        <dd class="mt-1 text-sm">{{ ucfirst(str_replace('_', ' ', (string) $request->reason_code)) }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium muted">Submission channel</dt>
                        <dd class="mt-1"><span class="badge badge-neutral">{{ strtoupper($request->channel) }}</span></dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium muted">Details</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm">{{ $request->reason_details ?? 'No additional details provided.' }}</dd>
                    </div>
                </dl>
            </section>

        </div>

        <!-- Supporting documents -->
        <section class="card overflow-hidden">
            <div class="card-header">
                <h2 class="text-base font-semibold">Supporting documents</h2>
            </div>

            <div class="card-body">
                @forelse ($documents as $document)
                    <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between {{ $loop->first ? 'pt-0' : 'border-t' }} {{ $loop->last ? 'pb-0' : '' }}">
                        <div>
                            <p class="text-sm font-medium">{{ ucfirst(str_replace('_', ' ', (string) $document->document_type)) }}</p>
                            <p class="mt-0.5 text-xs muted">
                                Uploaded {{ $document->created_at ? Carbon\Carbon::parse($document->created_at)->format('M d, Y') : '—' }}
                            </p>
                        </div>

                        <a href="{{ asset('storage/' . $document->file_path) }}"
                           target="_blank"
                           rel="noopener"
                           class="btn btn-secondary btn-sm">
                            View document
                        </a>
                    </div>
                @empty
                    <p class="text-sm muted">No supporting documents were uploaded.</p>
                @endforelse
            </div>
        </section>

        <!-- Decision -->
        @if ($request->status === 'pending')
            <section class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="text-base font-semibold">OSA decision</h2>
                    <p class="mt-0.5 text-sm muted">Review all information before processing this request.</p>
                </div>

                <div class="card-body flex flex-col gap-3 sm:flex-row">
                    <form method="POST" action="{{ route('osa.readmissions.approve', $request->readmission_id) }}">
                        @csrf

                        <button type="submit"
                                onclick="return confirm('Are you sure you want to approve this readmission request?')"
                                class="btn btn-success w-full sm:w-auto">
                            Approve readmission
                        </button>
                    </form>

                    <form method="POST" action="{{ route('osa.readmissions.reject', $request->readmission_id) }}">
                        @csrf

                        <button type="submit"
                                onclick="return confirm('Are you sure you want to reject this readmission request?')"
                                class="btn btn-danger w-full sm:w-auto">
                            Reject request
                        </button>
                    </form>
                </div>
            </section>
        @else
            <div class="alert alert-neutral" role="status">
                This request has already been processed.
            </div>
        @endif

    </div>
</x-app-layout>