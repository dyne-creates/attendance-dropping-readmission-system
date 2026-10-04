<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Records"
            title="Drop Records"
            description="Historical record of students dropped from their courses."
        />
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <section class="card overflow-hidden">
            <div class="card-header">
                <h2 class="text-lg font-semibold">Dropping history</h2>
                <p class="mt-0.5 text-sm muted">All recorded student dropping transactions.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Course</th>
                            <th scope="col">Faculty</th>
                            <th scope="col">Absence hours</th>
                            <th scope="col">Drop no.</th>
                            <th scope="col">Drop date</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($drops as $drop)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $drop->first_name }} {{ $drop->last_name }}</p>
                                    <p class="mt-0.5 text-xs muted">{{ $drop->student_number }}</p>
                                </td>

                                <td>
                                    <p class="font-medium">{{ $drop->subject_code }}</p>
                                    <p class="mt-0.5 text-xs muted">{{ $drop->course_name }}</p>
                                </td>

                                <td>{{ $drop->faculty_name }}</td>

                                <td>{{ $drop->absence_hours_at_drop }} hrs</td>

                                <td><span class="badge badge-neutral">#{{ $drop->sequence_in_cycle }}</span></td>

                                <td>{{ $drop->drop_date ? Carbon\Carbon::parse($drop->drop_date)->format('M d, Y') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center muted">
                                    No drop records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</x-app-layout>