<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Student"
            title="Readmission mail"
            description="Status, decisions, and conclusions for your readmission requests."
        />
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
        @forelse ($requests as $request)
            @php
                $outcome = $request->outcome ?? $request->status;
                [$statusClass, $statusLabel] = match ($outcome) {
                    'pending' => ['badge-warning', 'Pending'],
                    'approved' => ['badge-success', 'Approved'],
                    'excused' => ['badge-success', 'Excused'],
                    'rejected' => ['badge-danger', 'Rejected'],
                    'not_excused' => ['badge-warning', 'Not excused'],
                    default => ['badge-neutral', ucfirst((string) $outcome)],
                };
            @endphp

            <article class="card space-y-4 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ $request->course_name }} ({{ $request->subject_code }})</h2>
                        <p class="mt-1 text-xs muted">
                            Section {{ $request->section }} · Attempt {{ $request->attempt_number }}
                            · Submitted {{ Carbon\Carbon::parse($request->created_at)->format('M d, Y') }}
                        </p>
                    </div>
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium muted">Request reason</dt>
                        <dd class="mt-1">{{ \App\Models\ReadmissionRequest::reasonLabel((string) $request->reason_code) }}</dd>
                    </div>
                    @if ($request->reason_details)
                        <div>
                            <dt class="text-xs font-medium muted">Details submitted</dt>
                            <dd class="mt-1 whitespace-pre-line">{{ $request->reason_details }}</dd>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium muted">Conclusion</dt>
                        <dd class="mt-1">
                            @if ($outcome === 'pending')
                                Your request is awaiting review.
                            @elseif ($outcome === 'approved')
                                Your readmission request was approved.
                            @elseif ($outcome === 'excused')
                                @if ($request->excused_from && $request->excused_through)
                                    Your absence was excused from {{ Carbon\Carbon::parse($request->excused_from)->format('M d, Y') }}
                                    through {{ Carbon\Carbon::parse($request->excused_through)->format('M d, Y') }}.
                                @else
                                    Your absence was excused.
                                @endif
                            @elseif ($outcome === 'not_excused')
                                Your absence was not excused.
                            @elseif ($outcome === 'rejected')
                                Your request was not approved.
                            @else
                                {{ ucfirst(str_replace('_', ' ', (string) $outcome)) }}
                            @endif
                        </dd>
                        @if ($request->rejection_reason)
                            <p class="mt-1 whitespace-pre-line text-sm muted">{{ $request->rejection_reason }}</p>
                        @endif
                    </div>
                </dl>
            </article>
        @empty
            <section class="card p-8 text-center">
                <h2 class="font-semibold">No readmission updates</h2>
                <p class="mt-1 text-sm muted">Your submitted readmission requests and their decisions will appear here.</p>
            </section>
        @endforelse
    </div>
</x-app-layout>
