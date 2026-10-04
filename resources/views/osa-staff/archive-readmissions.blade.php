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

                Readmission Archive

            </h1>

            <p class="mt-2 text-gray-400">

                View completed readmission requests and their decisions.

            </p>

        </div>

        <div class="bg-gray-900/80
                    backdrop-blur
                    border border-gray-800
                    rounded-2xl
                    overflow-hidden">

            <div class="px-6 py-5
                        border-b border-gray-800">

                <div class="flex flex-col sm:flex-row
                            sm:items-center
                            sm:justify-between
                            gap-4">

                    <div>

                        <div class="flex items-center gap-2">

                            <div class="w-1.5 h-6 rounded-full bg-red-600">
                            </div>

                            <h2 class="text-lg font-semibold text-white">

                                Processed Requests

                            </h2>

                        </div>

                        <p class="mt-1 text-sm text-gray-500">

                            Approved and rejected readmission requests.

                        </p>

                    </div>

                    <div class="flex gap-2">

                        <span class="inline-flex items-center
                                     px-3 py-1
                                     rounded-full
                                     text-xs font-medium
                                     bg-green-900/30
                                     text-green-400
                                     border border-green-800/40">

                            Approved

                        </span>

                        <span class="inline-flex items-center
                                     px-3 py-1
                                     rounded-full
                                     text-xs font-medium
                                     bg-red-900/30
                                     text-red-400
                                     border border-red-800/40">

                            Rejected

                        </span>

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

                                Reason

                            </th>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Channel

                            </th>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Status

                            </th>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Processed

                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-800">

                        @forelse($records as $record)

                            <tr class="hover:bg-red-950/10 transition">

                                <td class="px-6 py-5">

                                    <div class="font-medium text-white">

                                        {{ $record->first_name }}
                                        {{ $record->last_name }}

                                    </div>

                                    <div class="text-sm text-gray-500">

                                        {{ $record->student_number }}

                                    </div>

                                </td>

                                <td class="px-6 py-5">

                                    <div class="font-medium text-gray-300">

                                        {{ $record->subject_code }}

                                    </div>

                                    <div class="text-sm text-gray-500">

                                        {{ $record->course_name }}

                                    </div>

                                </td>

                                <td class="px-6 py-5 text-sm text-gray-400">

                                    {{ $record->reason_code }}

                                </td>

                                <td class="px-6 py-5">

                                    <span class="inline-flex
                                                 px-3 py-1
                                                 rounded-full
                                                 text-xs
                                                 font-medium
                                                 bg-gray-800
                                                 text-gray-300">

                                        {{ strtoupper($record->channel) }}

                                    </span>

                                </td>

                                <td class="px-6 py-5">

                                    @if($record->status === 'approved')

                                        <span class="inline-flex items-center
                                                     px-3 py-1
                                                     rounded-full
                                                     text-xs
                                                     font-medium
                                                     bg-green-900/30
                                                     text-green-400
                                                     border border-green-800/40">

                                            <span class="w-1.5 h-1.5
                                                         mr-2
                                                         rounded-full
                                                         bg-green-500">
                                            </span>

                                            Approved

                                        </span>

                                    @elseif($record->status === 'rejected')

                                        <span class="inline-flex items-center
                                                     px-3 py-1
                                                     rounded-full
                                                     text-xs
                                                     font-medium
                                                     bg-red-900/30
                                                     text-red-400
                                                     border border-red-800/40">

                                            <span class="w-1.5 h-1.5
                                                         mr-2
                                                         rounded-full
                                                         bg-red-500">
                                            </span>

                                            Rejected

                                        </span>

                                    @endif

                                </td>

                                <td class="px-6 py-5 text-sm text-gray-400">

                                    {{ $record->processed_date }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6"
                                    class="px-6 py-14 text-center">

                                    <p class="text-gray-500">

                                        No processed readmission requests found.

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
