<x-app-layout>

    <div class="px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8">

            <div class="flex flex-col md:flex-row
                        md:items-center
                        md:justify-between
                        gap-4">

                <div>

                    <div class="flex items-center gap-2 mb-2">

                        <span class="text-xs uppercase tracking-widest
                                     text-red-500 font-semibold">

                            Office of Student Affairs

                        </span>

                    </div>

                    <h1 class="text-3xl font-bold text-white">

                        OSA Staff Dashboard

                    </h1>

                    <p class="mt-2 text-gray-400">

                        Manage student drops and readmission requests.

                    </p>

                </div>

                <div class="flex items-center gap-3">

                    <div class="px-4 py-2 rounded-lg
                                bg-gray-900/80
                                border border-gray-800">

                        <p class="text-xs text-gray-500">
                            Logged in as
                        </p>

                        <p class="text-sm font-medium text-white">
                            {{ Auth::user()->name }}
                        </p>

                    </div>

                </div>

            </div>

        </div>

        <div class="grid grid-cols-1 gap-5 mb-8">

            <div class="group relative
                        bg-gray-900/80
                        backdrop-blur
                        border border-gray-800
                        rounded-2xl
                        p-6
                        overflow-hidden
                        hover:border-red-800
                        transition">

                <div class="absolute -right-8 -top-8
                            w-24 h-24
                            bg-red-600/10
                            rounded-full
                            blur-2xl
                            group-hover:bg-red-600/20
                            transition">
                </div>

                <div class="relative">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                Pending Readmissions
                            </p>

                            <p class="mt-2 text-3xl font-bold text-white">

                                {{ $pendingReadmissions }}

                            </p>

                        </div>

                    </div>

                    <a href="{{ route('osa.readmissions') }}" class="inline-block mt-5 text-sm
                              font-medium text-red-500
                              hover:text-red-400">

                        Review requests →

                    </a>

                </div>

            </div>

            <div class="group relative
                        bg-gray-900/80
                        backdrop-blur
                        border border-gray-800
                        rounded-2xl
                        p-6
                        overflow-hidden
                        hover:border-green-900
                        transition">

                <div class="absolute -right-8 -top-8
                            w-24 h-24
                            bg-green-600/5
                            rounded-full
                            blur-2xl">
                </div>

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-gray-500">
                            Approved
                        </p>

                        <p class="mt-2 text-3xl font-bold text-white">

                            {{ $approvedReadmissions }}

                        </p>

                    </div>

                </div>

            </div>

            <div class="group relative
                        bg-gray-900/80
                        backdrop-blur
                        border border-gray-800
                        rounded-2xl
                        p-6
                        overflow-hidden
                        hover:border-red-900
                        transition">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-gray-500">
                            Rejected
                        </p>

                        <p class="mt-2 text-3xl font-bold text-white">

                            {{ $rejectedReadmissions }}

                        </p>

                    </div>

                </div>

            </div>

            <div class="group relative
                        bg-gray-900/80
                        backdrop-blur
                        border border-gray-800
                        rounded-2xl
                        p-6
                        overflow-hidden
                        hover:border-red-800
                        transition">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm text-gray-500">
                            Total Drop Records
                        </p>

                        <p class="mt-2 text-3xl font-bold text-white">

                            {{ $totalDrops }}

                        </p>

                    </div>

                </div>

                <a href="{{ route('osa.archive.drops') }}" class="inline-block mt-5 text-sm
                          font-medium text-red-500
                          hover:text-red-400">

                    View records →

                </a>

            </div>

        </div>

        <div class="bg-gray-900/80
                    backdrop-blur
                    border border-gray-800
                    rounded-2xl
                    overflow-hidden
                    shadow-2xl shadow-black/20">

            <div class="px-6 py-5
                        border-b border-gray-800
                        flex flex-col sm:flex-row
                        sm:items-center
                        sm:justify-between
                        gap-3">

                <div>

                    <div class="flex items-center gap-2">

                        <div class="w-1.5 h-6 rounded-full bg-red-600">
                        </div>

                        <h2 class="text-lg font-semibold text-white">

                            Recent Readmission Requests

                        </h2>

                    </div>

                    <p class="text-sm text-gray-500 mt-1">

                        Review recently submitted requests.

                    </p>

                </div>

                <a href="{{ route('osa.readmissions') }}" class="text-sm font-medium text-red-500
                          hover:text-red-400">

                    View all →

                </a>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-gray-950">

                        <tr>

                            <th class="px-6 py-4 text-left
                                       text-xs font-semibold
                                       text-gray-500 uppercase tracking-wider">

                                Student

                            </th>

                            <th class="px-6 py-4 text-left
                                       text-xs font-semibold
                                       text-gray-500 uppercase tracking-wider">

                                Course

                            </th>

                            <th class="px-6 py-4 text-left
                                       text-xs font-semibold
                                       text-gray-500 uppercase tracking-wider">

                                Reason

                            </th>

                            <th class="px-6 py-4 text-left
                                       text-xs font-semibold
                                       text-gray-500 uppercase tracking-wider">

                                Status

                            </th>

                            <th class="px-6 py-4 text-right
                                       text-xs font-semibold
                                       text-gray-500 uppercase tracking-wider">

                                Action

                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-800">

                        @forelse($recentReadmissions as $request)

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

                                                    </td>

                                                    <td class="px-6 py-5 text-sm text-gray-400">

                                                        {{ $request->reason_code }}

                                                    </td>

                                                    <td class="px-6 py-5">

                                                        @if($request->status === 'pending')

                                                            <span class="inline-flex items-center
                                                                                 px-3 py-1
                                                                                 rounded-full
                                                                                 text-xs font-medium
                                                                                 bg-yellow-900/30
                                                                                 text-yellow-400
                                                                                 border border-yellow-800/40">

                                                                Pending

                                                            </span>

                                                        @elseif($request->status === 'approved')

                                                            <span class="inline-flex items-center
                                                                                 px-3 py-1
                                                                                 rounded-full
                                                                                 text-xs font-medium
                                                                                 bg-green-900/30
                                                                                 text-green-400
                                                                                 border border-green-800/40">

                                                                Approved

                                                            </span>

                                                        @else

                                                            <span class="inline-flex items-center
                                                                                 px-3 py-1
                                                                                 rounded-full
                                                                                 text-xs font-medium
                                                                                 bg-red-900/30
                                                                                 text-red-400
                                                                                 border border-red-800/40">

                                                                Rejected

                                                            </span>

                                                        @endif

                                                    </td>

                                                    <td class="px-6 py-5 text-right">

                                                        <a href="{{ route(
                                'osa.readmissions.show',
                                $request->readmission_id
                            ) }}" class="inline-flex items-center
                                                                      px-4 py-2
                                                                      rounded-lg
                                                                      bg-red-600
                                                                      text-white
                                                                      text-sm
                                                                      font-medium
                                                                      hover:bg-red-500
                                                                      shadow-lg shadow-red-900/20
                                                                      transition">

                                                            Review

                                                        </a>

                                                    </td>

                                                </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="px-6 py-12 text-center">

                                    <p class="text-gray-500">

                                        No readmission requests found.

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