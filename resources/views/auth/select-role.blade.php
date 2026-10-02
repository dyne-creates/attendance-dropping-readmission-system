@extends('layouts.app')

@section('title', 'Login — Select Role')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 px-4">
    <div class="w-full max-w-md">

        {{-- Header --}}
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">ADRS</h1>
            <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm">Attendance, Dropping & Readmission System</p>
            <p class="mt-4 text-gray-600 dark:text-gray-300 font-medium">Select your role to continue</p>
        </div>

        {{-- Role Cards --}}
        <div class="flex flex-col gap-4">

            {{-- Student --}}
            <a href="{{ route('login.student') }}"
               class="group flex items-center gap-5 bg-white dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-xl border border-transparent hover:border-blue-400 dark:hover:border-blue-500 px-6 py-5 transition-all duration-200">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900 shrink-0 group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-colors">
                    <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14c4.418 0 8 1.79 8 4v1H4v-1c0-2.21 3.582-4 8-4zm0 0a4 4 0 100-8 4 4 0 000 8z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800 dark:text-white text-lg">Student</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">@s.ubaguio.edu</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            {{-- Faculty --}}
            <a href="{{ route('login.faculty') }}"
               class="group flex items-center gap-5 bg-white dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-xl border border-transparent hover:border-emerald-400 dark:hover:border-emerald-500 px-6 py-5 transition-all duration-200">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-900 shrink-0 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-800 transition-colors">
                    <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800 dark:text-white text-lg">Faculty</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">@e.ubaguio.edu</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            {{-- OSA Staff --}}
            <a href="{{ route('login.osa') }}"
               class="group flex items-center gap-5 bg-white dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-xl border border-transparent hover:border-violet-400 dark:hover:border-violet-500 px-6 py-5 transition-all duration-200">
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-violet-100 dark:bg-violet-900 shrink-0 group-hover:bg-violet-200 dark:group-hover:bg-violet-800 transition-colors">
                    <svg class="w-7 h-7 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800 dark:text-white text-lg">OSA Staff</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Office of Student Affairs</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-violet-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

        </div>

        <p class="text-center text-xs text-gray-400 dark:text-gray-600 mt-8">University of Baguio &mdash; ADRS &copy; {{ date('Y') }}</p>
    </div>
</div>
@endsection
