<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        {{ config('app.name', 'Attendance, Dropping, and Readmission System') }}
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-gray-900 antialiased">

    <div class="min-h-screen flex flex-col">

        <!-- Header -->
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-8">

                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-600 text-lg font-bold text-white">
                        U
                    </div>

                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-red-600">
                            University of Baguio
                        </p>

                        <p class="text-sm font-medium text-gray-700">
                            Attendance, Dropping, and Readmission System
                        </p>
                    </div>
                </a>

                <div class="hidden items-center gap-3 sm:flex">
                    @if (request()->routeIs('login'))
                        <span class="text-sm text-gray-500">
                            Don't have an account?
                        </span>

                        <a
                            href="{{ route('register') }}"
                            class="rounded-lg border border-red-600 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                        >
                            Register
                        </a>
                    @elseif (request()->routeIs('register'))
                        <span class="text-sm text-gray-500">
                            Already have an account?
                        </span>

                        <a
                            href="{{ route('login') }}"
                            class="rounded-lg border border-red-600 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                        >
                            Login
                        </a>
                    @endif
                </div>

            </div>
        </header>

        <!-- Main -->
        <main class="flex flex-1 items-center justify-center bg-gray-50 px-4 py-10 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-6 py-5 text-center text-xs text-gray-500">
                Attendance, Dropping, and Readmission System
            </div>
        </footer>

    </div>

</body>
</html>