<x-app-layout>

    <div class="px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8">

            <div class="flex flex-col sm:flex-row
                        sm:items-center
                        sm:justify-between
                        gap-4">

                <div>

                    <div class="flex items-center gap-2 mb-2">

                        <span class="text-xs uppercase tracking-widest
                                     text-red-500 font-semibold">

                            Review

                        </span>

                    </div>

                    <h1 class="text-3xl font-bold text-white">

                        Review Readmission Request

                    </h1>

                    <p class="mt-2 text-gray-400">

                        Review the student's information before making a decision.

                    </p>

                </div>

                <a href="{{ route('osa.readmissions') }}"
                   class="inline-flex items-center
                          px-4 py-2
                          rounded-lg
                          border border-gray-700
                          bg-gray-900
                          text-gray-300
                          text-sm
                          hover:bg-gray-800
                          hover:text-white
                          transition">

                    ← Back to Requests

                </a>

            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-gray-900/80
                        border border-gray-800
                        rounded-2xl
                        overflow-hidden">

                <div class="px-6 py-4
                            border-b border-gray-800
                            bg-gray-950/50">

                    <h2 class="font-semibold text-white">

                        Student Information

                    </h2>

                </div>

                <div class="p-6 space-y-5">

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Full Name
                        </p>

                        <p class="mt-1 font-medium text-white">

                            {{ $request->first_name }}
                            {{ $request->last_name }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Student Number
                        </p>

                        <p class="mt-1 text-gray-300">

                            {{ $request->student_number }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Program
                        </p>

                        <p class="mt-1 text-gray-300">

                            {{ $request->program }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Year Level
                        </p>

                        <p class="mt-1 text-gray-300">

                            {{ $request->year_level }}

                        </p>

                    </div>

                </div>

            </div>

            <div class="bg-gray-900/80
                        border border-gray-800
                        rounded-2xl
                        overflow-hidden">

                <div class="px-6 py-4
                            border-b border-gray-800
                            bg-gray-950/50">

                    <h2 class="font-semibold text-white">

                        Course Information

                    </h2>

                </div>

                <div class="p-6 space-y-5">

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Subject Code
                        </p>

                        <p class="mt-1 font-medium text-white">

                            {{ $request->subject_code }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Course
                        </p>

                        <p class="mt-1 text-gray-300">

                            {{ $request->course_name }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Section
                        </p>

                        <p class="mt-1 text-gray-300">

                            {{ $request->section }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Faculty
                        </p>

                        <p class="mt-1 text-gray-300">

                            {{ $request->faculty_name }}

                        </p>

                    </div>

                </div>

            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-gray-900/80
                        border border-gray-800
                        rounded-2xl
                        overflow-hidden">

                <div class="px-6 py-4
                            border-b border-gray-800">

                    <h2 class="font-semibold text-white">

                        Dropping Information

                    </h2>

                </div>

                <div class="p-6 space-y-5">

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Drop Date
                        </p>

                        <p class="mt-1 text-gray-300">

                            {{ $request->drop_date }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Absence Hours
                        </p>

                        <p class="mt-1 text-gray-300">

                            {{ $request->absence_hours_at_drop }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Drop Transaction
                        </p>

                        <p class="mt-1 text-red-400 font-medium">

                            #{{ $request->drop_id }}

                        </p>

                    </div>

                </div>

            </div>

            <div class="bg-gray-900/80
                        border border-gray-800
                        rounded-2xl
                        overflow-hidden">

                <div class="px-6 py-4
                            border-b border-gray-800">

                    <h2 class="font-semibold text-white">

                        Readmission Request

                    </h2>

                </div>

                <div class="p-6 space-y-5">

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Reason
                        </p>

                        <p class="mt-1 text-gray-300">

                            {{ $request->reason_code }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Details
                        </p>

                        <p class="mt-1 text-gray-400 whitespace-pre-line">

                            {{ $request->reason_details
                                ?? 'No additional details provided.' }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs uppercase tracking-wider text-gray-500">
                            Submission Channel
                        </p>

                        <span class="inline-flex mt-2
                                     px-3 py-1
                                     rounded-full
                                     text-xs font-medium
                                     bg-red-900/30
                                     text-red-400
                                     border border-red-800/40">

                            {{ strtoupper($request->channel) }}

                        </span>

                    </div>

                </div>

            </div>

        </div>

        <div class="bg-gray-900/80
                    border border-gray-800
                    rounded-2xl
                    overflow-hidden
                    mb-6">

            <div class="px-6 py-4
                        border-b border-gray-800">

                <h2 class="font-semibold text-white">

                    Supporting Documents

                </h2>

            </div>

            <div class="p-6">

                @forelse($documents as $document)

                    <div class="flex flex-col sm:flex-row
                                sm:items-center
                                sm:justify-between
                                gap-4
                                p-4 mb-3
                                rounded-xl
                                bg-gray-950
                                border border-gray-800
                                hover:border-red-900
                                transition">

                        <div class="flex items-center">

                            <div class="w-10 h-10 rounded-lg
                                        bg-red-900/30
                                        flex items-center justify-center
                                        mr-3">

                                <svg class="w-5 h-5 text-red-500"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>

                                </svg>

                            </div>

                            <div>

                                <p class="font-medium text-white">

                                    {{ $document->document_type }}

                                </p>

                                <p class="text-xs text-gray-500">

                                    Uploaded {{ $document->uploaded_at }}

                                </p>

                            </div>

                        </div>

                        <a href="{{ asset('storage/' . $document->file_path) }}"
                           target="_blank"
                           class="px-4 py-2
                                  text-sm font-medium
                                  text-red-500
                                  border border-red-900/50
                                  rounded-lg
                                  hover:bg-red-950/30
                                  transition">

                            View Document

                        </a>

                    </div>

                @empty

                    <p class="text-sm text-gray-500">

                        No supporting documents were uploaded.

                    </p>

                @endforelse

            </div>

        </div>

        @if($request->status === 'pending')

            <div class="bg-gray-900/80
                        border border-red-900/40
                        rounded-2xl
                        overflow-hidden
                        shadow-xl shadow-red-950/10">

                <div class="px-6 py-4
                            border-b border-gray-800">

                    <h2 class="font-semibold text-white">

                        OSA Decision

                    </h2>

                </div>

                <div class="p-6">

                    <p class="text-sm text-gray-400 mb-6">

                        Review all information before processing this
                        readmission request.

                    </p>

                    <div class="flex flex-col sm:flex-row gap-3">

                        <form method="POST"
                              action="{{ route(
                                  'osa.readmissions.approve',
                                  $request->readmission_id
                              ) }}">

                            @csrf

                            <button
                                type="submit"
                                onclick="return confirm(
                                    'Are you sure you want to approve this readmission request?'
                                )"
                                class="w-full sm:w-auto
                                       inline-flex items-center
                                       justify-center
                                       px-6 py-3
                                       rounded-lg
                                       bg-green-600
                                       text-white
                                       font-medium
                                       text-sm
                                       hover:bg-green-500
                                       transition">

                                ✓

                                <span class="ml-2">
                                    Approve Readmission
                                </span>

                            </button>

                        </form>

                        <form method="POST"
                              action="{{ route(
                                  'osa.readmissions.reject',
                                  $request->readmission_id
                              ) }}">

                            @csrf

                            <button
                                type="submit"
                                onclick="return confirm(
                                    'Are you sure you want to reject this readmission request?'
                                )"
                                class="w-full sm:w-auto
                                       inline-flex items-center
                                       justify-center
                                       px-6 py-3
                                       rounded-lg
                                       bg-red-600
                                       text-white
                                       font-medium
                                       text-sm
                                       hover:bg-red-500
                                       transition">

                                ✕

                                <span class="ml-2">
                                    Reject Request
                                </span>

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        @else

            <div class="bg-gray-900/80
                        border border-gray-800
                        rounded-2xl
                        p-6">

                <p class="text-sm text-gray-400">

                    This request has already been processed.

                </p>

            </div>

        @endif

    </div>

</x-app-layout>
