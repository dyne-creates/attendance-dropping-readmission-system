<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('faculty.dashboard') }}"
               class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Faculty Portal</p>
                <h1 class="font-serif text-3xl font-normal text-gray-900 dark:text-gray-100 tracking-tight mt-0.5">
                    Add New Subject
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="page">
        @if ($errors->any())
            <div class="alert alert-warning mb-4" role="alert">
                <p class="font-semibold">Please fix the following errors:</p>
                <ul class="mt-1 list-inside list-disc text-xs font-normal">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('faculty.courses.store') }}">
            @csrf
            @include('faculty.courses._form', [
                'course'      => null,
                'schoolYears' => $schoolYears,
            ])
        </form>
    </div>
</x-app-layout>
