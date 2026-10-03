@php
    $heading    = $attributes->get('heading');
    $subheading = $attributes->get('subheading');
    $width      = $attributes->get('width', 'max-w-md');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">

    <title>{{ $heading ? $heading.' | ' : '' }}ADRS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">

    <div class="flex min-h-screen flex-col">

        <!-- Header -->
        <header class="border-t-4 border-red-700 border-b border-b-gray-200 bg-white dark:border-t-red-600 dark:border-b-gray-700 dark:bg-gray-800">
            <div class="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="block leading-tight">
                    <span class="block text-xs font-semibold uppercase tracking-wider text-red-700 dark:text-red-400">
                        University of Baguio
                    </span>
                    <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                        Attendance, Dropping, and Readmission System
                    </span>
                </a>
            </div>
        </header>

        <!-- Main -->
        <main class="flex flex-1 items-start justify-center px-4 py-10 sm:items-center sm:px-6 sm:py-16">
            <div class="w-full {{ $width }}">

                @if ($heading)
                    <div class="mb-6">
                        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-50">
                            {{ $heading }}
                        </h1>

                        @if ($subheading)
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $subheading }}
                            </p>
                        @endif
                    </div>
                @endif

                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-700 dark:bg-gray-800">
                    {{ $slot }}
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 py-4 text-center text-xs text-gray-500 dark:text-gray-400">
                Attendance, Dropping, and Readmission System &middot; {{ date('Y') }}
            </div>
        </footer>

    </div>

    <script>
        // If the browser restores this page from its back/forward cache, reload it so state is fresh.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>