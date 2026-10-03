@php
    $user = Auth::user();

    $roleLabels = [
        'student'   => 'Student',
        'faculty'   => 'Faculty',
        'osa_staff' => 'OSA Staff',
    ];
    $roleLabel = $roleLabels[$user->role] ?? ucfirst(str_replace('_', ' ', (string) $user->role));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">

    <title>Attendance, Dropping, and Readmission System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">

    <div class="min-h-screen">

        <!-- Navigation -->
        <nav class="border-t-4 border-red-700 border-b border-b-gray-200 bg-white dark:border-t-red-600 dark:border-b-gray-700 dark:bg-gray-800">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

                <!-- Brand -->
                <a href="{{ route('dashboard') }}" class="block min-w-0 leading-tight">
                    <span class="block truncate text-xs font-semibold uppercase tracking-wider text-red-700 dark:text-red-400">
                        University of Baguio
                    </span>
                    <span class="hidden truncate text-sm font-medium text-gray-800 sm:block dark:text-gray-200">
                        Attendance, Dropping, and Readmission System
                    </span>
                </a>

                <!-- User -->
                <div class="flex shrink-0 items-center gap-3 sm:gap-5">

                    <div class="hidden max-w-[14rem] text-right leading-tight sm:block">
                        <span class="block truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $user->name }}
                        </span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            {{ $roleLabel }}
                        </span>
                    </div>

                    @if (Route::has('profile.edit'))
                        <a
                            href="{{ route('profile.edit') }}"
                            class="text-sm font-medium text-gray-700 hover:text-red-700 dark:text-gray-300 dark:hover:text-red-400"
                        >
                            Profile
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="rounded-md border border-gray-300 px-3.5 py-1.5 text-sm font-semibold text-gray-700 transition-colors hover:border-red-700 hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-700 focus-visible:ring-offset-2 dark:border-gray-600 dark:text-gray-200 dark:hover:border-red-500 dark:hover:text-red-400 dark:focus-visible:ring-red-400 dark:focus-visible:ring-offset-gray-800"
                        >
                            Log out
                        </button>
                    </form>

                </div>
            </div>
        </nav>

        <!-- Optional page heading (<x-slot name="header"> in a view) -->
        @isset($header)
            <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot ?? '' }}

            @yield('content')
        </main>

    </div>

    <script>
        // If the browser restores this page from its back/forward cache (e.g. after logout), reload it.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>