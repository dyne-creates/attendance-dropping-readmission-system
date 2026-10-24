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

        @if (! $user->policy_accepted_at)
            @include('components.policy-agreement-modal')
        @endif

        <div x-data="appDialogs" @app-dialog.window="openAppDialog($event.detail)">
            <div x-show="dialogOpen" x-cloak
                 class="fixed inset-0 z-[70] flex items-center justify-center overflow-y-auto p-4"
                 @keydown.escape.window="closeAppDialog()">
                <button type="button" class="modal-scrim absolute inset-0 cursor-default"
                        aria-label="Close dialog" @click="closeAppDialog()"></button>
                <section class="modal-panel relative w-full max-w-md p-5 shadow-xl"
                         :role="dialogType === 'confirm' ? 'alertdialog' : 'dialog'" aria-modal="true"
                         :aria-labelledby="'app-dialog-title'"
                         :aria-describedby="'app-dialog-message'">
                    <h2 id="app-dialog-title" class="text-lg font-semibold" x-text="dialogTitle"></h2>
                    <p id="app-dialog-message" class="muted mt-2 whitespace-pre-line text-sm" x-text="dialogMessage"></p>
                    <div class="mt-5 flex justify-end gap-2">
                        <button x-show="dialogType === 'confirm'" type="button" class="btn btn-secondary"
                                @click="closeAppDialog()">
                            Cancel
                        </button>
                        <button x-show="dialogType === 'confirm'" type="button" class="btn btn-primary"
                                @click="confirmAppDialog()">
                            Continue
                        </button>
                        <button x-show="dialogType !== 'confirm'" type="button" class="btn btn-primary"
                                @click="closeAppDialog()">
                            OK
                        </button>
                    </div>
                </section>
            </div>
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
