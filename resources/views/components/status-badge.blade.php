@props(['status'])
@php
    // Define badge styles for each status ensuring sufficient contrast (WCAG AA)
    $styles = [
        'Present' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
        'Late' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
        'Absent' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
        'Excused' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
        'Approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100',
        'Pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
        'Rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-800 dark:text-rose-100',
        'Permanent Drop' => 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100',
    ];
    $class = $styles[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $class"]) }}>
    {{ $status }}
</span>
