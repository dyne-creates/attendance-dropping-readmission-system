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

<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">

    <div class="min-h-screen">

        <!-- Navigation -->
        <nav class="border-b border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">

                <div class="flex h-16 items-center justify-between">

                    <!-- Brand -->
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-600 text-sm font-bold text-white">
                            A
                        </div>

                        <div class="hidden sm:block">
                            <p class="text-sm font-bold text-gray-900">
                                Attendance, Dropping, and Readmission
                            </p>

                            <p class="text-xs text-gray-500">
                                University of Baguio
                            </p>
                        </div>

                    </a>

                    <!-- User -->
                    <div class="flex items-center gap-4">

                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-semibold text-gray-900">
                                {{ Auth::user()->name }}
                            </p>

                            <p class="text-xs capitalize text-gray-500">
                                {{ str_replace('_', ' ', Auth::user()->role) }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button
                                type="submit"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-red-600 hover:text-red-600"
                            >
                                Logout
                            </button>
                        </form>

                    </div>

                </div>

            </div>
        </nav>

        <!-- Page Content -->
        <main>
            {{ $slot ?? '' }}

            @yield('content')
        </main>

    </div>

</body>
</html>