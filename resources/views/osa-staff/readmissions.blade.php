<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Review"
            title="Readmission Requests"
            description="Review and process student readmission requests."
        />
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        <section class="card overflow-hidden">
            <div class="card-header flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Pending requests</h2>
                    <span class="badge badge-neutral">{{ count($requests) }} pending</span>
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
                            <th scope="col">Faculty</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Drop level</th>
                            <th scope="col">Attempt</th>
                            <th scope="col">Channel</th>
                            <th scope="col" class="text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $request->first_name }} {{ $request->last_name }}</p>
                                    <p class="mt-0.5 text-xs muted">{{ $request->student_number }}</p>
                                </td>

                                <td>
                                    <p class="font-medium">{{ $request->subject_code }}</p>
                                    <p class="mt-0.5 text-xs muted">{{ $request->course_name }} &middot; Section {{ $request->section }}</p>
                                </td>

                                <td>{{ $request->faculty_name }}</td>

                                <td>{{ \App\Models\ReadmissionRequest::reasonLabel((string) $request->reason_code) }}</td>

                                <td>
                                    @if ($request->drop_type === \App\Models\DroppingTransaction::TYPE_PERMANENT)
                                        <span class="badge badge-danger">Permanent · {{ $request->absence_hours_at_drop }} hrs</span>
                                        <p class="mt-1 text-xs muted">OSA may still review readmission.</p>
                                    @elseif ($request->drop_type === \App\Models\DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL)
                                        <span class="badge badge-neutral">Instructor referral</span>
                                    @else
                                        <span class="badge badge-warning">Weekly hours · {{ $request->absence_hours_at_drop }} hrs</span>
                                    @endif
                                    @if ($request->instructor_reason)
                                        <p class="mt-1 text-xs muted">{{ $request->instructor_reason }}</p>
                                    @endif
                                </td>

                                <td>{{ $request->attempt_number }}</td>

                                <td><span class="badge badge-neutral">{{ strtoupper($request->channel) }}</span></td>

                                <td class="text-right">
                                    <a href="{{ route('osa.readmissions.show', $request->readmission_id) }}" class="btn btn-primary btn-sm">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center muted">
                                    No pending readmission requests.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</x-app-layout>