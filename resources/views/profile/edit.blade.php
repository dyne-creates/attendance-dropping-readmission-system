<x-app-layout>
    <x-slot name="header">
        <x-page-title title="Your profile" description="Your University account details." />
    </x-slot>

    @php
        $roleLabels = [
            'student' => 'Student',
            'faculty' => 'Faculty',
            'osa_staff' => 'OSA Staff',
        ];
    @endphp

    <div class="page">
        <section class="card mx-auto max-w-2xl p-6 sm:p-8">
            <div class="flex items-center gap-4">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="" class="h-16 w-16 rounded-full object-cover">
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-red-100 font-semibold text-red-700" aria-hidden="true">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                @endif

                <div>
                    <h2 class="text-lg font-semibold">{{ $user->name }}</h2>
                    <p class="muted">{{ $roleLabels[$user->role] ?? 'University account' }}</p>
                </div>
            </div>

            <dl class="mt-8 divide-y">
                <div class="grid gap-1 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium muted">Name</dt>
                    <dd class="sm:col-span-2">{{ $user->name }}</dd>
                </div>
                <div class="grid gap-1 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium muted">University email</dt>
                    <dd class="sm:col-span-2">{{ $user->email }}</dd>
                </div>
                <div class="grid gap-1 py-4 sm:grid-cols-3">
                    <dt class="text-sm font-medium muted">Account type</dt>
                    <dd class="sm:col-span-2">{{ $roleLabels[$user->role] ?? 'University account' }}</dd>
                </div>
                @if ($user->role === 'student')
                    <div class="grid gap-1 py-4 sm:grid-cols-3">
                        <dt class="text-sm font-medium muted">Parent / guardian email</dt>
                        <dd class="sm:col-span-2">{{ $guardian?->email ?: 'Not provided' }}</dd>
                    </div>
                @endif
            </dl>
        </section>
    </div>
</x-app-layout>
