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
    <div
        x-data="{ sidebarCollapsed: false, mobileSidebarOpen: false }"
        x-init="try { sidebarCollapsed = localStorage.getItem('adrs-sidebar-collapsed') === 'true'; } catch (error) {}"
        x-effect="try { localStorage.setItem('adrs-sidebar-collapsed', sidebarCollapsed); } catch (error) {}"
    >
        @if ($user->role === 'osa_staff')
            @include('layouts.osa-navigation')
        @elseif ($user->role === 'faculty')
            @include('layouts.faculty-navigation')
        @elseif (request()->routeIs('student.dashboard'))
            @include('layouts.student-topbar')
        @else
            @include('layouts.student-navigation')
        @endif

        <div class="min-w-0 transition-[padding] duration-200" :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'">
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
        </div>

        <script>
            window.addEventListener('pageshow', function (event) {
                const navigation = performance.getEntriesByType('navigation')[0];

                if (event.persisted || navigation?.type === 'back_forward') {
                    window.location.reload();
                }
            });
        </script>
        @stack('scripts')
    </div>
</body>
</html>
