@props(['subtitle' => null])

<span {{ $attributes->merge(['class' => 'block leading-tight']) }}>
    <span class="block text-sm font-semibold uppercase tracking-wide text-brand">
        University of Baguio
    </span>

    @if ($subtitle)
        <span class="muted mt-0.5 block text-xs">{{ $subtitle }}</span>
    @endif
</span>
