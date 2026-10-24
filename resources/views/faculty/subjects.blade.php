<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Faculty"
            title="Subjects"
            description="Choose a subject to open its attendance list, or manage its schedule."
        >
            <x-slot name="actions">
                <button type="button" class="btn btn-secondary btn-sm"
                        @click="$dispatch('open-class-list-import')">
                    Import subject class list
                </button>
                <a href="{{ route('faculty.courses.create') }}" class="btn btn-primary btn-sm">Add subject</a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 py-8 sm:px-6 lg:px-8"
         x-data="{ showClassListImport: @js($errors->any()) }"
         @open-class-list-import.window="showClassListImport = true"
         @keydown.escape.window="showClassListImport = false">
        @if ($errors->any())
            <div class="alert alert-warning" role="alert">
                <p class="font-semibold">The class list could not be imported:</p>
                <ul class="mt-1 list-inside list-disc text-xs font-normal">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @forelse ($courses as $course)
            <article class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-semibold">{{ $course->subject_code }}</h2>
                        <span class="badge badge-neutral">Section {{ $course->section }}</span>
                    </div>
                    <p class="mt-1 text-sm muted">{{ $course->course_name }}</p>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs muted">
                        <span>{{ $course->enrollments_count }} {{ Str::plural('student', $course->enrollments_count) }}</span>
                        <span>{{ $course->hours_per_week }} hours per week</span>
                        <span>
                            @forelse ($course->schedules as $schedule)
                                {{ $schedule->day_of_week }}
                                {{ Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}–{{ Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}@if (! $loop->last), @endif
                            @empty
                                Schedule not set
                            @endforelse
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('faculty.dashboard', ['course_id' => $course->course_id]) }}"
                       class="btn btn-primary btn-sm">Open attendance</a>
                    <a href="{{ route('faculty.courses.edit', $course) }}"
                       class="btn btn-secondary btn-sm">Edit subject</a>
                </div>
            </article>
        @empty
            <section class="card space-y-3 p-8 text-center">
                <div>
                    <h2 class="font-semibold">No subjects assigned</h2>
                    <p class="mt-1 text-sm muted">Import a class list or add a subject to get started.</p>
                </div>
                <div class="flex justify-center gap-2">
                    <button type="button" class="btn btn-secondary"
                            @click="$dispatch('open-class-list-import')">
                        Import subject class list
                    </button>
                    <a href="{{ route('faculty.courses.create') }}" class="btn btn-primary">Add subject</a>
                </div>
            </section>
        @endforelse

        <div x-show="showClassListImport" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
             role="presentation">
            <button type="button" class="modal-scrim absolute inset-0 cursor-default"
                    aria-label="Close class list import dialog"
                    @click="showClassListImport = false"></button>
            <section class="modal-panel relative w-full max-w-2xl overflow-hidden shadow-xl"
                     role="dialog" aria-modal="true" aria-labelledby="class-list-import-heading">
                <div class="card-header flex items-start justify-between gap-4">
                    <div>
                        <h2 id="class-list-import-heading" class="font-semibold">Import subject class list</h2>
                        <p class="muted mt-1 text-sm">Upload a Blackboard class list workbook (.xlsx).</p>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" aria-label="Close dialog"
                            @click="showClassListImport = false">&times;</button>
                </div>

                <form method="POST" action="{{ route('faculty.courses.import.store') }}"
                      enctype="multipart/form-data" class="space-y-4 p-5">
                    @csrf
                    <div class="space-y-3 text-sm">
                        <p class="muted">
                            The first worksheet must contain subject and schedule details, plus student numbers,
                            names, programs, year levels, and email addresses. The import creates the course,
                            schedule, student profiles, new student login accounts, and enrollments together.
                            Existing students are matched by student number.
                        </p>
                        <p class="rounded-md border border-amber-300 bg-amber-50 p-3 text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
                            New student accounts use the initial password
                            <code class="font-mono">passwords</code>. Change this before using the accounts with real student data.
                        </p>
                        <p class="text-xs muted">
                            Schedule hours are used to calculate weekly hours; semester hours use the app's 18-week convention.
                        </p>
                    </div>

                    <div>
                        <label for="class-list-file" class="field-label">Blackboard class list (.xlsx)</label>
                        <input id="class-list-file" name="file" type="file" accept=".xlsx"
                               required class="field mt-1">
                        @error('file')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-secondary"
                                @click="showClassListImport = false">Cancel</button>
                        <button type="submit" class="btn btn-primary">Import class and students</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
