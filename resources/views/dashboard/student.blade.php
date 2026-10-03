@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">

    {{-- Navbar --}}
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14c4.418 0 8 1.79 8 4v1H4v-1c0-2.21 3.582-4 8-4zm0 0a4 4 0 100-8 4 4 0 000 8z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white text-sm leading-none">ADRS</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Student Portal</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600 dark:text-gray-300">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Welcome back, {{ auth()->user()->name }}! 👋</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Here's your student dashboard.</p>
        </div>

        <section>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Your subjects by semester</h2>

            @forelse ($enrollments as $enrollment)
                <details class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 mb-4">
                    <summary class="flex cursor-pointer list-none flex-col sm:flex-row sm:items-start sm:justify-between gap-4 p-6 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span class="flex flex-col items-start">
                            <span class="text-lg font-semibold text-gray-800 dark:text-white">{{ $enrollment->course->course_name }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $enrollment->course->subject_code }} · {{ $enrollment->course->section }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $enrollment->course->schoolYear->year_label }} · {{ $enrollment->course->schoolYear->semester }}
                            </span>
                            @if ($enrollment->status === 'dropped')
                                <span class="mt-2 inline-flex rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                    Dropped
                                </span>
                            @endif
                        </span>
                        <span class="flex flex-col items-start sm:items-end gap-3">
                                <span class="text-sm text-blue-600 dark:text-blue-400 group-open:hidden">View attendance</span>
                                <span class="text-sm text-blue-600 dark:text-blue-400 hidden group-open:inline">Hide attendance</span>
                                <span class="grid grid-cols-4 gap-4 text-center">
                                    <span>
                                        <span class="block text-xs text-gray-400 dark:text-gray-500">Total days</span>
                                        <span class="block font-semibold text-gray-800 dark:text-white">{{ $enrollment->attendance_records_count }}</span>
                                    </span>
                                    <span>
                                        <span class="block text-xs text-gray-400 dark:text-gray-500">Present</span>
                                        <span class="block font-semibold text-emerald-600 dark:text-emerald-400">{{ $enrollment->present_count }}</span>
                                    </span>
                                    <span>
                                        <span class="block text-xs text-gray-400 dark:text-gray-500">Absent</span>
                                        <span class="block font-semibold text-red-600 dark:text-red-400">{{ $enrollment->absent_count }}</span>
                                    </span>
                                    <span>
                                        <span class="block text-xs text-gray-400 dark:text-gray-500">Late</span>
                                        <span class="block font-semibold text-amber-600 dark:text-amber-400">{{ $enrollment->late_count }}</span>
                                    </span>
                                </span>
                        </span>
                    </summary>

                    <div class="px-6 pb-6">
                        <div class="flex flex-col gap-3">
                            @foreach ($enrollment->grading_periods as $gradingPeriod)
                                <details class="rounded-xl border border-gray-200 dark:border-gray-700">
                                    <summary class="cursor-pointer list-none p-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                        <span class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            <span class="font-medium text-gray-800 dark:text-white">{{ $gradingPeriod['label'] }}</span>
                                            <span class="grid grid-cols-4 gap-4 text-center">
                                                <span>
                                                    <span class="block text-xs text-gray-400 dark:text-gray-500">Days</span>
                                                    <span class="block font-semibold text-gray-800 dark:text-white">{{ $gradingPeriod['attendance_records']->count() }}</span>
                                                </span>
                                                <span>
                                                    <span class="block text-xs text-gray-400 dark:text-gray-500">Present</span>
                                                    <span class="block font-semibold text-emerald-600 dark:text-emerald-400">{{ $gradingPeriod['counts']['present'] }}</span>
                                                </span>
                                                <span>
                                                    <span class="block text-xs text-gray-400 dark:text-gray-500">Absent</span>
                                                    <span class="block font-semibold text-red-600 dark:text-red-400">{{ $gradingPeriod['counts']['absent'] }}</span>
                                                </span>
                                                <span>
                                                    <span class="block text-xs text-gray-400 dark:text-gray-500">Late</span>
                                                    <span class="block font-semibold text-amber-600 dark:text-amber-400">{{ $gradingPeriod['counts']['late'] }}</span>
                                                </span>
                                            </span>
                                        </span>
                                    </summary>

                                    <div class="overflow-x-auto px-4 pb-4">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                            <thead class="bg-gray-50 dark:bg-gray-900">
                                                <tr>
                                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Class day</th>
                                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @forelse ($gradingPeriod['attendance_records'] as $attendanceRecord)
                                                    <tr>
                                                        <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">
                                                            {{ $attendanceRecord->session_date->format('l, M j, Y') }}
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            @php
                                                                $statusClasses = match ($attendanceRecord->status) {
                                                                    'present' => 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300',
                                                                    'absent' => 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300',
                                                                    'late' => 'bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300',
                                                                };
                                                            @endphp
                                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                                                {{ ucfirst($attendanceRecord->status) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                            {{ $attendanceRecord->remarks ?? '—' }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                                            No class days have been recorded for {{ strtolower($gradingPeriod['label']) }}.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            @endforeach

                            @if ($enrollment->unassigned_attendance_records->isNotEmpty())
                                <details class="rounded-xl border border-amber-200 dark:border-amber-800">
                                    <summary class="cursor-pointer list-none p-4 text-sm font-medium text-amber-700 dark:text-amber-300">
                                        Attendance without a grading period ({{ $enrollment->unassigned_attendance_records->count() }} days)
                                    </summary>
                                    <div class="overflow-x-auto px-4 pb-4">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                            <thead class="bg-gray-50 dark:bg-gray-900">
                                                <tr>
                                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Class day</th>
                                                    <th scope="col" class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach ($enrollment->unassigned_attendance_records as $attendanceRecord)
                                                    <tr>
                                                        <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">
                                                            {{ $attendanceRecord->session_date->format('l, M j, Y') }}
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                            {{ ucfirst($attendanceRecord->status) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            @endif
                        </div>
                    </div>
                </details>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
                    <p class="font-medium">You don’t have any subjects yet.</p>
                    <p class="text-sm mt-1">Your subjects and attendance summaries will appear here when you’re enrolled.</p>
                </div>
            @endforelse
        </section>
    </main>
</div>
@endsection
