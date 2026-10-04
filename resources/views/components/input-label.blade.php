@props(['value'])

<label {{ $attributes->merge(['class' => 'field-label']) }}>
    {{ $value ?? $slot }}
</label>