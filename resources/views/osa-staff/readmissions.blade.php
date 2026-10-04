<x-app-layout>

    <div class="px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8">

            <div class="flex items-center gap-2 mb-2">

                <span class="text-xs uppercase tracking-widest
                             text-red-500 font-semibold">

                    Review

                </span>

            </div>

            <h1 class="text-3xl font-bold text-white">

                Readmission Requests

            </h1>

            <p class="mt-2 text-gray-400">

                Review and process student readmission requests.

            </p>

        </div>

        @if(session('success'))

            <div class="mb-6 p-4 rounded-xl
                        bg-green-950/40
                        border border-green-800/50
                        text-green-400">

                {{ session('success') }}

            </div>

        @endif

        @if(session('error'))

            <div class="mb-6 p-4 rounded-xl
                        bg-red-950/40
                        border border-red-800/50
                        text-red-400">

                {{ session('error') }}

            </div>

        @endif

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

                    <h2 class="text-lg font-semibold text-white">

                        Pending Requests

                    </h2>

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

                                Reason

                            </th>

                            <th class="px-6 py-4 text-left text-xs
                                       font-semibold text-gray-500 uppercase">

                                Channel

                            </th>

                            <th class="px-6 py-4 text-right text-xs
                                       font-semibold text-gray-500 uppercase">

                                Action

                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-800">

                        @forelse($requests as $request)

                            <tr class="hover:bg-red-950/10 transition">

                                <td class="px-6 py-5">

                                    <div class="font-medium text-white">

                                        {{ $request->first_name }}
                                        {{ $request->last_name }}

                                    </div>

                                    <div class="text-sm text-gray-500">

                                        {{ $request->student_number }}

                                    </div>

                                </td>

                                <td class="px-6 py-5">

                                    <div class="font-medium text-gray-300">

                                        {{ $request->subject_code }}

                                    </div>

                                    <div class="text-sm text-gray-500">

                                        {{ $request->course_name }}

                                    </div>

                                    <div class="text-xs text-gray-600">

                                        Section {{ $request->section }}

                                    </div>

                                </td>

                                <td class="px-6 py-5 text-sm text-gray-400">

                                    {{ $request->faculty_name }}

                                </td>

                                <td class="px-6 py-5 text-sm text-gray-400">

                                    {{ $request->reason_code }}

                                </td>

                                <td class="px-6 py-5">

                                    <span class="inline-flex
                                                 px-3 py-1
                                                 rounded-full
                                                 text-xs
                                                 font-medium
                                                 bg-gray-800
                                                 text-gray-300">

                                        {{ strtoupper($request->channel) }}

                                    </span>

                                </td>

                                <td class="px-6 py-5 text-right">

                                    <a href="{{ route(
                                        'osa.readmissions.show',
                                        $request->readmission_id
                                    ) }}"
                                       class="inline-flex items-center
                                              px-4 py-2
                                              bg-red-600
                                              text-white
                                              rounded-lg
                                              text-sm font-medium
                                              hover:bg-red-500
                                              shadow-lg shadow-red-900/20
                                              transition">

                                        Review

                                        <span class="ml-2">→</span>

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6"
                                    class="px-6 py-14 text-center">

                                    <div class="text-gray-500">

                                        No pending readmission requests.

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-app-layout>
