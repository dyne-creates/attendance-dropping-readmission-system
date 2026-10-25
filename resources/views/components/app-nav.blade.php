@props(['home', 'subtitle' => null, 'links' => []])

@php
    $user = Auth::user();

    $roleLabels = [
        'student'   => 'Student',
        'faculty'   => 'Faculty',
        'osa_staff' => 'OSA Staff',
    ];
    $roleLabel = $roleLabels[$user->role] ?? ucfirst(str_replace('_', ' ', (string) $user->role));
@endphp

<div class="sticky top-0 z-30 flex h-14 items-center justify-between border-b bg-white px-4 dark:border-gray-700 dark:bg-gray-900 lg:hidden">
    <button type="button" class="btn btn-secondary btn-sm" @click="mobileSidebarOpen = true" aria-label="Open navigation menu">
        Menu
    </button>
    <a href="{{ $home }}" class="text-sm font-semibold">{{ $subtitle ?? 'Attendance System' }}</a>
    <x-theme-toggle />
</div>

<div x-show="mobileSidebarOpen" x-cloak class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="mobileSidebarOpen = false"></div>

<aside
    class="fixed inset-y-0 left-0 z-50 flex -translate-x-full flex-col border-r bg-white transition-all duration-200 dark:border-gray-700 dark:bg-gray-900 lg:translate-x-0"
    :class="{
        'translate-x-0': mobileSidebarOpen,
        'w-20': sidebarCollapsed,
        'w-64': !sidebarCollapsed
    }"
    aria-label="Main navigation"
>
    <div class="flex h-16 items-center border-b dark:border-gray-700"
         :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between gap-2 px-4'">
        <a href="{{ $home }}" x-show="!sidebarCollapsed || mobileSidebarOpen" class="flex min-w-0 items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-600 text-sm font-bold text-white" aria-hidden="true">UB</span>
            <span x-show="!sidebarCollapsed" class="min-w-0">
                <span class="block truncate text-sm font-semibold text-rose-600">University of Baguio</span>
                <span class="block truncate text-xs muted">{{ $subtitle ?? $roleLabel }}</span>
            </span>
        </a>
        <button type="button" class="btn btn-ghost btn-sm lg:hidden" @click="mobileSidebarOpen = false" aria-label="Close navigation menu">
            &times;
        </button>
        <button type="button" class="btn btn-ghost btn-sm hidden lg:inline-flex" @click="sidebarCollapsed = !sidebarCollapsed"
                :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <span x-text="sidebarCollapsed ? '›' : '‹'">‹</span>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}"
               @class([
                   'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                   'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' => request()->routeIs($link['active'] ?? $link['route']),
                   'muted hover:bg-gray-100 dark:hover:bg-gray-800' => ! request()->routeIs($link['active'] ?? $link['route']),
               ])
               :title="sidebarCollapsed ? @js($link['label']) : null">
                <span class="w-5 shrink-0 text-center" aria-hidden="true">{{ strtoupper(substr($link['label'], 0, 1)) }}</span>
                <span x-show="!sidebarCollapsed" class="truncate">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="space-y-3 border-t p-3 dark:border-gray-700">
        <div class="flex items-center gap-3 px-2">
            @if ($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">
            @else
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold dark:bg-gray-800">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
            @endif
            <span x-show="!sidebarCollapsed" class="min-w-0">
                <span class="block truncate text-sm font-semibold">{{ $user->name }}</span>
                <span class="block truncate text-xs muted">{{ $roleLabel }}</span>
            </span>
        </div>
        <div class="grid grid-cols-2 gap-1">
            <a href="{{ route('profile.edit') }}"
               @class([
                   'btn btn-secondary btn-sm w-full min-w-0',
                   'border-rose-500 text-rose-700 dark:text-rose-300' => request()->routeIs('profile.*'),
               ])
               :class="sidebarCollapsed ? 'px-1' : 'px-3'"
               :title="sidebarCollapsed ? 'Profile' : null"
               aria-label="Profile">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="" class="h-4 w-4 shrink-0 rounded-full object-cover">
                @else
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.5" />
                        <path stroke-linecap="round" d="M5 20a7 7 0 0 1 14 0" />
                    </svg>
                @endif
                <span x-show="!sidebarCollapsed">Profile</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm w-full min-w-0"
                        :class="sidebarCollapsed ? 'px-1' : 'px-3'"
                        :title="sidebarCollapsed ? 'Log out' : null" aria-label="Log out">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" />
                    </svg>
                    <span x-show="!sidebarCollapsed">Log out</span>
                </button>
            </form>
        </div>
        <div class="flex justify-end">
            <span x-show="!sidebarCollapsed"><x-theme-toggle /></span>
        </div>
    </div>
</aside>
