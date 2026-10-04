@props(['eyebrow' => null, 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    @if ($eyebrow)
        <p class="text-xs font-semibold uppercase tracking-wide text-brand">
            {{ $eyebrow }}
        </p>
    @endif

    <x-page-title :title="$title" :description="$description">
        @isset($actions)
            {{ $actions }}
        @endisset
    </x-page-title>
</div>
