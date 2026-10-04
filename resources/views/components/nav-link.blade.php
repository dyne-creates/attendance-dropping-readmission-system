@props(['active'])

@php
    $isActive = $active ?? false;
    $classes = $isActive ? 'nav-tab nav-tab-active' : 'nav-tab';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($isActive) aria-current="page" @endif>
    {{ $slot }}
</a>