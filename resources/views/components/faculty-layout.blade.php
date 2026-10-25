@props([
    'title' => null,
    'subtitle' => 'Faculty Portal',
    'backUrl' => null,
])

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            @if (isset($heading))
                <div class="min-w-0 flex-1">
                    {{ $heading }}
                </div>
            @else
                <div class="flex items-center gap-3">
                    @if ($backUrl)
                        <a href="{{ $backUrl }}"
                           aria-label="Back to {{ $subtitle }}"
                           class="text-gray-400 transition-colors hover:text-gray-700 dark:hover:text-gray-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </a>
                    @endif

                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ $subtitle }}
                        </p>
                        <h1 class="mt-0.5 font-serif text-3xl font-normal tracking-tight text-gray-900 dark:text-gray-100">
                            {{ $title }}
                        </h1>
                    </div>
                </div>
            @endif

            @isset($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </x-slot>

    <div {{ $attributes->class(['page']) }}>
        {{ $slot }}
    </div>
</x-app-layout>
