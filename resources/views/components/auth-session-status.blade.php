@props(['status'])

@if ($status)
    <div role="status" {{ $attributes->merge(['class' => 'alert alert-success']) }}>
        {{ $status }}
    </div>
@endif