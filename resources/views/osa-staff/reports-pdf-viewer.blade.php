<x-app-layout>
    <x-slot name="header">
        <x-page-header
            eyebrow="Office of Student Affairs"
            title="Report Viewer"
            description="Review the generated report, then download it as a PDF."
        />
    </x-slot>

    <div class="mx-auto max-w-[1400px] space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-flash-messages />

        {{-- Toolbar --}}
        <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('osa.reports', $backFilters) }}"
                   class="inline-flex items-center gap-1 text-xs font-medium text-red-600 hover:underline dark:text-red-400">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Reports
                </a>
                <h2 class="mt-1 truncate text-base font-semibold">{{ $filename }}</h2>
                <p class="muted text-xs">
                    {{ $period }} &bull; Generated {{ $generatedAt }} &bull; {{ $sizeLabel }}
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('osa.reports.pdf.file', $token) }}" target="_blank" rel="noopener"
                   class="btn btn-secondary btn-sm">Open in new tab</a>
                <a href="{{ route('osa.reports.pdf.download', $token) }}" class="btn btn-primary btn-sm inline-flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4" />
                    </svg>
                    Download PDF
                </a>
            </div>
        </div>

        {{-- Viewer --}}
        <div class="card overflow-hidden p-0">
            <iframe src="{{ route('osa.reports.pdf.file', $token) }}#view=FitH"
                    title="PDF report viewer"
                    class="block w-full bg-gray-100 dark:bg-gray-900"
                    style="height: calc(100vh - 17rem); min-height: 560px;"></iframe>
        </div>

        <p class="muted text-xs">
            Nothing displaying (common on phones)? Use <strong>Open in new tab</strong> or <strong>Download PDF</strong>.
            This copy is kept for one hour; regenerate it from the Reports page afterward.
        </p>
    </div>
</x-app-layout>