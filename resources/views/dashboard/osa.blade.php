@extends('layouts.app')

@section('title', 'OSA Staff Dashboard')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">

    {{-- Navbar --}}
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white text-sm leading-none">ADRS</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">OSA Staff Portal</p>
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
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Welcome, {{ auth()->user()->name }}! 🛡️</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Here's your OSA Staff dashboard.</p>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <p class="text-sm text-gray-400 dark:text-gray-500">Email</p>
                <p class="font-semibold text-gray-800 dark:text-white mt-1 break-all">{{ auth()->user()->email }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <p class="text-sm text-gray-400 dark:text-gray-500">Role</p>
                <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-semibold bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300">OSA Staff</span>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <p class="text-sm text-gray-400 dark:text-gray-500">Status</p>
                <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">Active</span>
            </div>
        </div>

        {{-- Placeholder sections --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-400 dark:text-gray-500">
            <svg class="mx-auto w-12 h-12 mb-4 text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="font-medium">Readmission requests and student management tools will appear here.</p>
        </div>

    </main>
</div>
@endsection
