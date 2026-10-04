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

{{-- Shared top navigation for every role: same markup, only the links differ. --}}
<nav x-data="{ open: false }" class="topbar sticky top-0 z-40">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-stretch justify-between gap-4">

            <!-- Brand + links -->
            <div class="flex items-stretch gap-8">
                <a href="{{ $home }}" class="flex items-center">
                    <x-application-logo :subtitle="$subtitle" />
                </a>

                <div class="hidden items-stretch gap-6 sm:flex">
                    @foreach ($links as $link)
                        <x-nav-link :href="route($link['route'])" :active="request()->routeIs($link['active'] ?? $link['route'])">
                            {{ $link['label'] }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <!-- Theme + user menu (desktop) -->
            <div class="hidden items-center gap-3 sm:flex">
                <x-theme-toggle />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="btn btn-ghost" aria-haspopup="true">
                            <span class="block text-left leading-tight">
                                <span class="block max-w-[12rem] truncate text-sm font-semibold">{{ $user->name }}</span>
                                <span class="block text-xs font-medium muted">{{ $roleLabel }}</span>
                            </span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                            >
                                Log out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Menu button (mobile) -->
            <div class="flex items-center sm:hidden">
                <button type="button" @click="open = ! open" class="btn btn-secondary btn-sm">
                    <span x-text="open ? 'Close' : 'Menu'">Menu</span>
                </button>
            </div>

        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="open" style="display: none;" class="border-t sm:hidden">
        <div class="space-y-1 py-2">
            @foreach ($links as $link)
                <x-responsive-nav-link :href="route($link['route'])" :active="request()->routeIs($link['active'] ?? $link['route'])">
                    {{ $link['label'] }}
                </x-responsive-nav-link>
            @endforeach
        </div>

        <div class="border-t py-4">
            <div class="px-4">
                <p class="text-sm font-semibold">{{ $user->name }}</p>
                <p class="text-xs font-medium muted">{{ $roleLabel }} &middot; {{ $user->email }}</p>
            </div>

            <div class="mt-3 space-y-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link
                        :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                    >
                        Log out
                    </x-responsive-nav-link>
                </form>
            </div>

            <div class="mt-3 px-4">
                <x-theme-toggle />
            </div>
        </div>
    </div>
</nav>
