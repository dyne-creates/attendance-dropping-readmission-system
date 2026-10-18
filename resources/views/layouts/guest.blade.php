@php
    $heading    = $attributes->get('heading');
    $subheading = $attributes->get('subheading');
    $width      = $attributes->get('width', 'max-w-md');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['title' => ($heading ? $heading.' | ' : '').'ADRS'])
</head>

<body class="min-h-screen antialiased">

    <div class="flex min-h-screen flex-col">

        <!-- Header -->
        <header class="topbar">
            <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}">
                    <x-application-logo  />
                </a>

                <x-theme-toggle />
            </div>
        </header>

        <!-- Main -->
        <main class="flex flex-1 items-start justify-center px-4 py-10 sm:items-center sm:px-6 sm:py-14">
            <div class="w-full {{ $width }}">

                <h1 class="mb-6 text-center text-2xl font-bold tracking-tight">
                    Attendance, Dropping, and Readmission System
                </h1>

                <div class="card p-6 sm:p-8">

                    @if ($heading)
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold">{{ $heading }}</h2>

                            @if ($subheading)
                                <p class="mt-1 text-sm muted">{{ $subheading }}</p>
                            @endif
                        </div>
                    @endif

                    {{ $slot }}

                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t">
            <div class="mx-auto max-w-7xl px-4 py-4 text-center text-xs muted">
                University of Baguio &middot; {{ date('Y') }}
            </div>
        </footer>

    </div>

    <script>
        // Revalidate history-restored pages with the server so auth changes take effect.
        window.addEventListener('pageshow', function (event) {
            const navigation = performance.getEntriesByType('navigation')[0];

            if (event.persisted || navigation?.type === 'back_forward') {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
