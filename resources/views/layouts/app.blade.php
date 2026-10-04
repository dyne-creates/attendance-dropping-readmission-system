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
    @include('layouts.partials.head', ['title' => $roleLabel.' | ADRS'])
</head>

<body class="min-h-screen antialiased">
    @if ($user->role === 'osa_staff')
        @include('layouts.osa-navigation')
    @elseif ($user->role === 'faculty')
        @include('layouts.faculty-navigation')
    @elseif (request()->routeIs('student.dashboard'))
        @include('layouts.student-topbar')
    @else
        @include('layouts.student-navigation')
    @endif

    {{-- Pages describe themselves here: <x-slot name="header"><x-page-title ... /></x-slot> --}}
    @isset($header)
        <header class="page-header">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    <main>
        {{ $slot }}
    </main>

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