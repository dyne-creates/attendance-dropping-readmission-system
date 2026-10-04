<x-app-layout>

    <div class="px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8">

            <div class="flex items-center gap-2 mb-2">

                <span class="text-xs uppercase tracking-widest
                             text-red-500 font-semibold">

                    Records

                </span>

            </div>

            <h1 class="text-3xl font-bold text-white">

                Drop Records

            </h1>

            <p class="mt-2 text-gray-400">

                View historical records of students dropped by faculty.

            </p>

        </div>

        <div class="bg-gray-900/80
                    backdrop-blur
                    border border-gray-800
                    rounded-2xl
                    overflow-hidden">

            <div class="px-6 py-5
                        border-b border-gray-800">

                <div class="flex items-center gap-2">

                    <div class="w-1.5 h-6 rounded-full bg-red-600">
                    </div>

                    <div>

                        <h2 class="text-lg font-semibold text-white">

                            Dropping History

                        </h2>

                        <p class="text-sm text-gray-500">

                            All recorded student dropping transactions.

                        </p>

                    </div>

                </div>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-gray-950">

                        <tr>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Student

                            </th>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Course

                            </th>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Faculty

                            </th>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Absence Hours

                            </th>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Sequence

                            </th>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Drop Date

                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-800">

                        @forelse($drops as $drop)

                            <tr class="hover:bg-red-950/10 transition">

                                <td class="px-6 py-5">

                                    <div class="font-medium text-white">

                                        {{ $drop->first_name }}
                                        {{ $drop->last_name }}

                                    </div>

                                    <div class="text-sm text-gray-500">

                                        {{ $drop->student_number }}

                                    </div>

                                </td>

                                <td class="px-6 py-5">

                                    <div class="font-medium text-gray-300">

                                        {{ $drop->subject_code }}

                                    </div>

                                    <div class="text-sm text-gray-500">

                                        {{ $drop->course_name }}

                                    </div>

                                </td>

                                <td class="px-6 py-5 text-sm text-gray-400">

                                    {{ $drop->faculty_name }}

                                </td>

                                <td class="px-6 py-5 text-sm text-gray-400">

                                    {{ $drop->absence_hours_at_drop }}

                                </td>

                                <td class="px-6 py-5">

                                    <span class="inline-flex
                                                     px-3 py-1
                                                     rounded-full
                                                     text-xs
                                                     font-medium
                                                     bg-gray-800
                                                     text-gray-300">

                                        #{{ $drop->sequence_in_cycle }}

                                    </span>

                                </td>

                                <td class="px-6 py-5 text-sm text-gray-400">

                                    {{ $drop->drop_date }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="px-6 py-14 text-center">

                                    <p class="text-gray-500">

                                        No drop records found.

                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-app-layout>
