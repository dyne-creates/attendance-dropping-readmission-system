{{--
    Shared course form partial.
    Required vars: $course (Model|null), $schoolYears (Collection), $formAction (string), $formMethod ('POST'|'PUT')
--}}
<div class="space-y-6" x-data="courseForm(@js($course ? $course->schedules->toArray() : []))">

    {{-- Subject Details --}}
    <section class="card p-5 sm:p-6 space-y-5" aria-labelledby="subject-details-heading">
        <h2 id="subject-details-heading" class="text-base font-semibold">Subject Details</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- Course Name --}}
            <div class="sm:col-span-2">
                <label for="course_name" class="field-label">Course Name</label>
                <input id="course_name" name="course_name" type="text"
                       value="{{ old('course_name', $course?->course_name) }}"
                       class="input mt-1 w-full" required maxlength="255"
                       placeholder="e.g. Web Systems and Technologies" />
                @error('course_name')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Subject Code --}}
            <div>
                <label for="subject_code" class="field-label">Subject Code</label>
                <input id="subject_code" name="subject_code" type="text"
                       value="{{ old('subject_code', $course?->subject_code) }}"
                       class="input mt-1 w-full" required maxlength="50"
                       placeholder="e.g. WEBDEV" />
                @error('subject_code')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Section --}}
            <div>
                <label for="section" class="field-label">Section</label>
                <input id="section" name="section" type="text"
                       value="{{ old('section', $course?->section) }}"
                       class="input mt-1 w-full" required maxlength="50"
                       placeholder="e.g. IAA1" />
                @error('section')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Hours per Week --}}
            <div>
                <label for="hours_per_week" class="field-label">Hours per Week</label>
                <input id="hours_per_week" name="hours_per_week" type="number" min="1" max="40"
                       value="{{ old('hours_per_week', $course?->hours_per_week ?? 3) }}"
                       class="input mt-1 w-full" required />
                @error('hours_per_week')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Total Semester Hours --}}
            <div>
                <label for="total_semester_hours" class="field-label">Total Semester Hours</label>
                <input id="total_semester_hours" name="total_semester_hours" type="number" min="1" max="999" step="0.5"
                       value="{{ old('total_semester_hours', $course ? (float) $course->total_semester_hours : 54) }}"
                       class="input mt-1 w-full" required />
                @error('total_semester_hours')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- School Year --}}
            <div class="sm:col-span-2">
                <label for="school_year_id" class="field-label">School Year / Semester</label>
                <select id="school_year_id" name="school_year_id" class="input mt-1 w-full" required>
                    @foreach ($schoolYears as $sy)
                        <option value="{{ $sy->school_year_id }}"
                            @selected(old('school_year_id', $course?->school_year_id) == $sy->school_year_id)>
                            {{ $sy->semester }} — {{ $sy->year_label }}
                        </option>
                    @endforeach
                </select>
                @error('school_year_id')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    {{-- Schedule Slots --}}
    <section class="card p-5 sm:p-6 space-y-4" aria-labelledby="schedule-heading">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 id="schedule-heading" class="text-base font-semibold">Schedule Slots</h2>
                <p class="muted text-xs mt-0.5">Add meeting days, times, and rooms for this subject.</p>
            </div>
            <button type="button" @click="addSlot()"
                    class="btn btn-secondary text-xs px-3 py-1.5 shrink-0">
                + Add Slot
            </button>
        </div>

        <div class="space-y-3">
            <template x-for="(slot, idx) in slots" :key="idx">
                <div class="grid grid-cols-12 gap-2 items-end p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                    {{-- Day --}}
                    <div class="col-span-12 sm:col-span-3">
                        <label class="field-label text-[11px]">Day</label>
                        <select :name="`schedules[${idx}][day_of_week]`" x-model="slot.day_of_week"
                                class="input mt-1 w-full text-sm">
                            <option value="Mon">Monday</option>
                            <option value="Tue">Tuesday</option>
                            <option value="Wed">Wednesday</option>
                            <option value="Thu">Thursday</option>
                            <option value="Fri">Friday</option>
                            <option value="Sat">Saturday</option>
                            <option value="Sun">Sunday</option>
                        </select>
                    </div>

                    {{-- Start Time --}}
                    <div class="col-span-5 sm:col-span-3">
                        <label class="field-label text-[11px]">Start Time</label>
                        <input type="time" :name="`schedules[${idx}][start_time]`" x-model="slot.start_time"
                               class="input mt-1 w-full text-sm" required />
                    </div>

                    {{-- End Time --}}
                    <div class="col-span-5 sm:col-span-3">
                        <label class="field-label text-[11px]">End Time</label>
                        <input type="time" :name="`schedules[${idx}][end_time]`" x-model="slot.end_time"
                               class="input mt-1 w-full text-sm" required />
                    </div>

                    {{-- Room --}}
                    <div class="col-span-10 sm:col-span-2">
                        <label class="field-label text-[11px]">Room</label>
                        <input type="text" :name="`schedules[${idx}][room]`" x-model="slot.room"
                               class="input mt-1 w-full text-sm" maxlength="100" placeholder="e.g. CS-201" />
                    </div>

                    {{-- Remove button --}}
                    <div class="col-span-2 sm:col-span-1 flex items-end pb-0.5">
                        <button type="button" @click="removeSlot(idx)"
                                class="w-full h-9 flex items-center justify-center rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 border border-transparent hover:border-red-200 dark:hover:border-red-800 transition-colors"
                                title="Remove slot">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>

            <p x-show="slots.length === 0" class="text-sm muted text-center py-4">
                No schedule slots added yet. Click "+ Add Slot" to add one.
            </p>
        </div>

        @error('schedules')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </section>

    {{-- Actions --}}
    <div class="flex items-center gap-3">
        <button type="submit" class="btn btn-primary">Save Subject</button>
        <a href="{{ route('faculty.dashboard', $course ? ['course_id' => $course->course_id] : []) }}"
           class="btn btn-secondary">Cancel</a>
    </div>
</div>

@push('scripts')
<script>
function courseForm(initialSlots) {
    return {
        slots: initialSlots.length > 0
            ? initialSlots.map(s => ({
                day_of_week: s.day_of_week ?? 'Monday',
                start_time: s.start_time ? s.start_time.substring(0, 5) : '08:00',
                end_time: s.end_time ? s.end_time.substring(0, 5) : '09:30',
                room: s.room ?? '',
              }))
            : [],

        addSlot() {
            this.slots.push({ day_of_week: 'Monday', start_time: '08:00', end_time: '09:30', room: '' });
        },

        removeSlot(idx) {
            this.slots.splice(idx, 1);
        },
    };
}
</script>
@endpush
