<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('faculty.dashboard', ['course_id' => $course->course_id]) }}"
                   class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </a>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                        {{ $course->subject_code }} &bull; Sec {{ $course->section }}
                    </p>
                    <h1 class="font-serif text-3xl font-normal text-gray-900 dark:text-gray-100 tracking-tight mt-0.5">
                        Edit Subject
                    </h1>
                </div>
            </div>

            {{-- Danger zone: delete --}}
            <form method="POST" action="{{ route('faculty.courses.destroy', $course) }}"
                  onsubmit="return confirm('Delete this subject? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="btn text-xs px-3 py-1.5 bg-red-50 dark:bg-red-950/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors rounded">
                    Delete Subject
                </button>
            </form>
        </div>
    </x-slot>

    <div class="page">
        @if (session('success'))
            <div class="alert alert-success mb-4" role="status">{{ session('success') }}</div>
        @endif

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

        <form method="POST" action="{{ route('faculty.courses.update', $course) }}">
            @csrf
            @method('PUT')
            @include('faculty.courses._form', [
                'course'      => $course,
                'schoolYears' => $schoolYears,
            ])
        </form>
    </div>
</x-app-layout>
