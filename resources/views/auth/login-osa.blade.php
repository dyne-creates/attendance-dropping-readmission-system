@extends('layouts.app')

@section('title', 'OSA Staff Login')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-violet-50 to-purple-100 dark:from-gray-900 dark:to-gray-800 px-4">
    <div class="w-full max-w-md">

        {{-- Back --}}
        <a href="{{ route('login.select') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to role selection
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-100 dark:bg-violet-900 mb-4">
                    <svg class="w-8 h-8 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">OSA Staff Login</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Office of Student Affairs — Staff Portal</p>
            </div>

            {{-- Errors --}}
            @if ($errors->any())
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg px-4 py-3 text-sm mb-6">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.osa.submit') }}" class="flex flex-col gap-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Email Address
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        value="{{ old('email') }}"
                        placeholder="staff@gmail.com"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-violet-400 dark:focus:ring-violet-500 focus:border-transparent transition"
                    >
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        placeholder="••••••••"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-violet-400 dark:focus:ring-violet-500 focus:border-transparent transition"
                    >
                </div>

                {{-- Remember me --}}
                <div class="flex items-center gap-2">
                    <input id="remember" name="remember" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-violet-600">
                    <label for="remember" class="text-sm text-gray-600 dark:text-gray-400">Remember me</label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full py-3 px-4 bg-violet-600 hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-600 text-white font-semibold rounded-xl transition-colors duration-150 shadow-sm">
                    Sign In as OSA Staff
                </button>
            </form>

        </div>

        <p class="text-center text-xs text-gray-400 dark:text-gray-600 mt-6">University of Baguio &mdash; ADRS &copy; {{ date('Y') }}</p>
    </div>
</div>
@endsection
