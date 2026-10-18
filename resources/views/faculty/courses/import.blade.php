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
                    Import Subject Class List
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="page">
        @if ($errors->any())
            <div class="alert alert-warning mb-4" role="alert">
                <p class="font-semibold">The class list could not be imported:</p>
                <ul class="mt-1 list-inside list-disc text-xs font-normal">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mx-auto max-w-3xl space-y-4">
            <section class="card p-5 sm:p-6 space-y-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Workbook requirements</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Upload the Blackboard subject class list in <span class="font-medium">.xlsx</span> format.
                    The first worksheet must contain the subject details and schedule in its heading, plus student
                    number, name, program, year level, and email in the roster.
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    The import creates the course, schedule, student profiles, login accounts for new students, and enrollments
                    together. Existing students are matched by student number.
                </p>
                <div class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
                    <span class="font-semibold">Initial student login:</span>
                    New student accounts will share the password <code class="font-mono">passwords</code>.
                    Change this before using the accounts with real student data.
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Schedule hours are used to calculate weekly hours; total semester hours use the app's 18-week convention.
                    A missing First Semester 2026–2027 term is created using August 10–December 18, 2026.
                </p>
            </section>

            <form method="POST" action="{{ route('faculty.courses.import.store') }}" enctype="multipart/form-data"
                  class="card p-5 sm:p-6 space-y-4">
                @csrf
                <div>
                    <label for="class-list-file" class="field-label">Blackboard class list (.xlsx)</label>
                    <input id="class-list-file" name="file" type="file" accept=".xlsx"
                           required class="field mt-1">
                    @error('file')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">Import class and students</button>
                    <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
