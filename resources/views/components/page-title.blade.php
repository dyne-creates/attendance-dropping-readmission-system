@props(['title', 'description' => null])

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <h1 class="text-xl font-semibold leading-7">{{ $title }}</h1>

        @if ($description)
            <p class="muted mt-0.5 text-sm">{{ $description }}</p>
        @endif
    </div>

    @if (trim((string) $slot) !== '')
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>