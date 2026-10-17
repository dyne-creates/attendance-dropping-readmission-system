<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('faculty.dashboard', ['course_id' => $course->course_id]) }}"
               class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                    {{ $course->subject_code }} &bull; Sec {{ $course->section }}
                </p>
                <h1 class="font-serif text-3xl font-normal text-gray-900 dark:text-gray-100 tracking-tight mt-0.5">
                    Add Schedule Slot
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="page space-y-6">
        @if ($errors->any())
            <div class="alert alert-warning" role="alert">
                <p class="font-semibold">Please fix the following errors:</p>
                <ul class="mt-1 list-inside list-disc text-xs font-normal">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="card p-5 sm:p-6 space-y-4" aria-labelledby="existing-schedule-heading">
            <div>
                <h2 id="existing-schedule-heading" class="text-base font-semibold">Current schedule</h2>
                <p class="muted mt-1 text-sm">{{ $course->course_name }}</p>
            </div>
            @forelse ($course->schedules as $schedule)
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ $schedule->day_of_week }} ·
                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }} –
                    {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                    @if ($schedule->room)
                        <span class="muted">({{ $schedule->room }})</span>
                    @endif
                </p>
            @empty
                <p class="muted text-sm">No schedule slots have been added yet.</p>
            @endforelse
        </section>

        <form method="POST" action="{{ route('faculty.courses.schedules.store', $course) }}" class="card p-5 sm:p-6 space-y-5">
            @csrf
            <div>
                <h2 class="text-base font-semibold">New meeting time</h2>
                <p class="muted mt-1 text-xs">Add one slot at a time. Existing slots will be preserved.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="day_of_week" class="field-label">Day</label>
                    <select id="day_of_week" name="day_of_week" class="input mt-1 w-full text-gray-900 dark:text-gray-900" required>
                        @foreach (['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'] as $day => $label)
                            <option value="{{ $day }}" @selected(old('day_of_week', 'Mon') === $day)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="start_time" class="field-label">Start Time</label>
                    <input id="start_time" name="start_time" type="time" value="{{ old('start_time', '08:00') }}"
                           class="input mt-1 w-full text-gray-900 dark:text-gray-900" required />
                </div>

                <div>
                    <label for="end_time" class="field-label">End Time</label>
                    <input id="end_time" name="end_time" type="time" value="{{ old('end_time', '09:30') }}"
                           class="input mt-1 w-full text-gray-900 dark:text-gray-900" required />
                </div>

                <div class="sm:col-span-2">
                    <label for="room" class="field-label">Room <span class="muted font-normal">(optional)</span></label>
                    <input id="room" name="room" type="text" value="{{ old('room') }}" maxlength="100"
                           class="input mt-1 w-full text-gray-900 dark:text-gray-900" placeholder="e.g. CS-201" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-primary">Add Schedule Slot</button>
                <a href="{{ route('faculty.dashboard', ['course_id' => $course->course_id]) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
