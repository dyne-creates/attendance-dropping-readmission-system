@props(['active'])

@php
    $isActive = $active ?? false;
    $classes = $isActive ? 'nav-responsive nav-responsive-active' : 'nav-responsive';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($isActive) aria-current="page" @endif>
    {{ $slot }}
</a>