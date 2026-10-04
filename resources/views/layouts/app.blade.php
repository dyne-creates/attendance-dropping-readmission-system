@php
    $user = Auth::user();

    $roleLabels = [
        'student'   => 'Student',
        'faculty'   => 'Faculty',
        'osa_staff' => 'OSA Staff',
    ];
    $roleLabel = $roleLabels[$user->role] ?? ucfirst(str_replace('_', ' ', (string) $user->role));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        {{ config('app.name', 'OSA Management System') }}
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="font-sans antialiased bg-slate-950 text-gray-100">

    <div class="min-h-screen bg-slate-950">

        {{-- Navigation --}}
        @include('layouts.navigation')


        {{-- Main Background --}}
        <div class="relative overflow-hidden min-h-[calc(100vh-64px)]">


            {{-- Page Content --}}
            <main class="relative z-10">

                {{ $slot }}

            </main>

        </div>

    </div>

</body>

</html>