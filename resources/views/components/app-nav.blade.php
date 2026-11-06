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
            <svg class="h-9 w-9 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2L2 7l10 5 10-5-10-5zm0 13l-8-4v5l8 4 8-4v-5l-8 4z" /></svg>
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
                <span class="flex h-5 w-5 shrink-0 items-center justify-center text-current" aria-hidden="true">
                    @php $lbl = strtolower($link['label']); @endphp
                    @if (Str::contains($lbl, ['dashboard', 'attendance']))
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                    @elseif (Str::contains($lbl, ['readmission', 'request']))
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    @elseif (Str::contains($lbl, ['drop']))
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.25a7.5 7.5 0 0113.842-3.87" /></svg>
                    @elseif (Str::contains($lbl, ['archive']))
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                    @elseif (Str::contains($lbl, ['report']))
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    @elseif (Str::contains($lbl, ['subject']))
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                    @elseif (Str::contains($lbl, ['mail', 'email']))
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                    @else
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    @endif
                </span>
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
