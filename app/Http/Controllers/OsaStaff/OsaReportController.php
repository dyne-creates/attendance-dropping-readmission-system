<?php

namespace App\Http\Controllers\OsaStaff;

use App\Http\Controllers\Controller;
use App\Models\ReadmissionRequest;
use App\Models\SchoolYear;
use App\Services\OsaPdfReportService;
use App\Services\OsaReportService;
use App\Services\ReadmissionExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OsaReportController extends Controller
{
    /**
     * Show OSA reports dashboard with filters, metrics, charts, and data tables.
     */
    public function index(Request $request, OsaReportService $reportService): View
    {
        $validated = $this->validateReportFilters($request);
        $filterOptions = $reportService->getFilterOptions();

        [$startDate, $endDate] = $reportService->resolveDateRange($validated);
        $summary = $reportService->getSummaryStatistics($validated);
        $chartData = $reportService->getChartData($validated);

        $activeTab = $request->query('tab', 'drops');
        if (! in_array($activeTab, ['drops', 'referrals', 'readmissions', 'absences'], true)) {
            $activeTab = 'drops';
        }

        $drops = $reportService->getDroppingRecords($validated, 10);
        $referrals = $reportService->getReferralRecords($validated, 10);
        $readmissions = $reportService->getReadmissionRecords($validated, 10);
        $absences = $reportService->getAbsenceRecords($validated, 10);

        return view('osa-staff.reports', [
            'filters' => $validated,
            'filterOptions' => $filterOptions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $summary,
            'chartData' => $chartData,
            'activeTab' => $activeTab,
            'drops' => $drops,
            'referrals' => $referrals,
            'readmissions' => $readmissions,
            'absences' => $absences,
        ]);
    }

    private const PDF_TTL_MINUTES = 60;

    /**
     * Build the PDF, store it temporarily, then redirect to the full-page viewer.
     * POST -> redirect -> GET means refresh/back never re-submits chart images.
     */
    public function generatePdf(
        Request $request,
        OsaReportService $reportService,
        OsaPdfReportService $pdfService
    ): RedirectResponse {
        $chartRules = ['nullable', 'string', 'max:3000000', 'starts_with:data:image/png;base64,'];
        $request->validate([
            'chart_drops' => $chartRules,
            'chart_excused' => $chartRules,
            'chart_timeline' => $chartRules,
            'chart_status' => $chartRules,
            'chart_absence' => $chartRules,
        ]);

        $report = $this->generatePdfReport($request, $reportService, $pdfService);

        // Guard: a "PDF" that does not start with %PDF is HTML/stray output (PHP notices, error page, etc.).
        if (! str_starts_with($report['pdf'], '%PDF-')) {
            Log::error('OSA report: generator did not return a valid PDF.', [
                'starts_with' => substr($report['pdf'], 0, 300),
            ]);

            return back()->withInput()->with('error', 'The PDF report could not be generated. Please try again or contact support.');
        }

        $this->pruneExpiredPdfs();

        $token = (string) Str::uuid();
        $path = $this->pdfDirectory().'/'.$token.'.pdf';
        if (! Storage::disk($this->privateDisk())->put($path, $report['pdf'])) {
            throw new RuntimeException('The generated report could not be stored.');
        }

        Cache::put($this->pdfCacheKey($token), [
            'user_id' => Auth::id(),
            'path' => $path,
            'filename' => 'OSA-Report-'.$report['filenameTimestamp'].'.pdf',
            'generated_at' => $report['generatedAtLabel'],
            'period' => $report['periodLabel'],
            'filters' => collect($this->validateReportFilters($request))->filter(fn ($v) => $v !== null && $v !== '')->all(),
            'size' => strlen($report['pdf']),
        ], now()->addMinutes(self::PDF_TTL_MINUTES));

        return redirect()->route('osa.reports.pdf.view', ['token' => $token]);
    }

    /**
     * Full-page PDF viewer (like opening a PDF in Google Drive) with a Download button.
     */
    public function showPdf(string $token): View
    {
        $meta = $this->resolveStoredPdf($token);

        return view('osa-staff.reports-pdf-viewer', [
            'token' => $token,
            'filename' => $meta['filename'],
            'generatedAt' => $meta['generated_at'],
            'period' => $meta['period'],
            'sizeLabel' => number_format($meta['size'] / 1024, 0).' KB',
            'backFilters' => $meta['filters'],
        ]);
    }

    /**
     * Inline PDF bytes for the viewer iframe.
     */
    public function streamPdf(string $token): StreamedResponse
    {
        $meta = $this->resolveStoredPdf($token);

        return Storage::disk($this->privateDisk())->response($meta['path'], $meta['filename'], [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ], 'inline');
    }

    /**
     * Forced download with a guaranteed application/pdf content type.
     */
    public function downloadPdf(string $token): StreamedResponse
    {
        $meta = $this->resolveStoredPdf($token);

        return Storage::disk($this->privateDisk())->download($meta['path'], $meta['filename'], [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Download the filter-matched readmission dataset for institutional reporting.
     */
    public function extractReadmissions(
        Request $request,
        OsaReportService $reportService,
        ReadmissionExportService $exportService
    ): BinaryFileResponse {
        $filters = $this->validateReportFilters($request);
        $records = $reportService->getReadmissionExtractionRecords($filters);
        $headers = [
            'Readmission ID', 'Attempt', 'Student Number', 'Student Name', 'Program', 'Year Level',
            'Subject', 'Course', 'Section', 'Faculty', 'Drop Date', 'Drop Type', 'Absence Hours at Drop',
            'Faculty Referral Note', 'Request Status', 'Outcome', 'Reason', 'Request Details', 'OSA Note',
            'Channel', 'Submitted', 'Processed', 'Processed By', 'Correction Requested', 'Excused Dates',
            'Excused Hours Deducted',
        ];
        $rows = $records->map(function (object $record): array {
            $excusedDates = json_decode((string) ($record->excused_dates ?? '[]'), true);

            return [
                (string) $record->readmission_id,
                (string) $record->attempt_number,
                (string) $record->student_number,
                trim($record->first_name.' '.$record->last_name),
                (string) ($record->program ?? ''),
                (string) ($record->year_level ?? ''),
                (string) $record->subject_code,
                (string) $record->course_name,
                (string) ($record->section ?? ''),
                (string) $record->faculty_name,
                (string) ($record->drop_date ?? ''),
                (string) ($record->drop_type ?? ''),
                (string) ($record->absence_hours_at_drop ?? ''),
                (string) ($record->instructor_reason ?? ''),
                (string) $record->status,
                (string) ($record->outcome ?? ''),
                ReadmissionRequest::reasonLabel((string) $record->reason_code),
                (string) ($record->reason_details ?? ''),
                (string) ($record->rejection_reason ?? ''),
                strtoupper((string) $record->channel),
                (string) $record->submission_date,
                (string) ($record->processed_date ?? ''),
                (string) ($record->processed_by_name ?? ''),
                (string) ($record->correction_requested_at ?? ''),
                implode(', ', is_array($excusedDates) ? $excusedDates : []),
                (string) ($record->excused_hours_deducted ?? ''),
            ];
        })->prepend($headers)->all();
        $path = $exportService->createWorkbook($rows);

        return response()->download(
            $path,
            'readmission-data-extract-'.now('Asia/Manila')->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    /**
     * @return array{pdf: string, filenameTimestamp: string, generatedAtLabel: string, periodLabel: string}
     */
    private function generatePdfReport(
        Request $request,
        OsaReportService $reportService,
        OsaPdfReportService $pdfService
    ): array {
        $validated = $this->validateReportFilters($request);
        [$startDate, $endDate] = $reportService->resolveDateRange($validated);
        $summary = $reportService->getSummaryStatistics($validated);
        $tables = $reportService->getAllTabularRecordsForPdf($validated);

        $chartInputs = [
            'chart_drops' => $request->input('chart_drops'),
            'chart_excused' => $request->input('chart_excused'),
            'chart_timeline' => $request->input('chart_timeline'),
            'chart_status' => $request->input('chart_status'),
            'chart_absence' => $request->input('chart_absence'),
        ];

        $schoolYearLabel = 'All School Years';
        if (! empty($validated['school_year_id'])) {
            $sy = SchoolYear::find($validated['school_year_id']);
            if ($sy) {
                $schoolYearLabel = "{$sy->year_label} - {$sy->semester}";
            }
        }

        $periodLabel = 'All Recorded History';
        if ($startDate && $endDate) {
            $periodLabel = $startDate->format('M j, Y').' to '.$endDate->format('M j, Y');
        } elseif ($startDate) {
            $periodLabel = 'From '.$startDate->format('M j, Y');
        } elseif ($endDate) {
            $periodLabel = 'Through '.$endDate->format('M j, Y');
        }

        $generatedAt = now('Asia/Manila');
        $pdfBinary = $pdfService->generate([
            'filters' => $validated,
            'filterSummary' => $this->buildFilterSummary($validated, $reportService),
            'periodLabel' => $periodLabel,
            'schoolYearLabel' => $schoolYearLabel,
            'generatedAt' => $generatedAt->format('F j, Y, g:i A'),
            'generatedBy' => Auth::user()?->name ?? 'OSA Staff',
            'summary' => $summary,
            'charts' => $chartInputs,
            'drops' => $tables['drops'],
            'referrals' => $tables['referrals'],
            'readmissions' => $tables['readmissions'],
            'absences' => $tables['absences'],
        ]);

        return [
            'pdf' => $pdfBinary,
            'filenameTimestamp' => $generatedAt->format('Ymd-His'),
            'generatedAtLabel' => $generatedAt->format('F j, Y, g:i A'),
            'periodLabel' => $periodLabel,
        ];
    }

    /**
     * Human-readable list of every active filter, printed in the PDF header.
     *
     * @param  array<string, mixed>  $v
     * @return array<string, string>
     */
    private function buildFilterSummary(array $v, OsaReportService $reportService): array
    {
        $options = $reportService->getFilterOptions();
        $summary = [];

        if (! empty($v['program'])) {
            $summary['Program'] = $v['program'];
        }
        if (! empty($v['year_level'])) {
            $summary['Year Level'] = 'Year '.$v['year_level'];
        }
        if (! empty($v['section'])) {
            $summary['Section'] = $v['section'];
        }
        if (! empty($v['faculty_id'])) {
            $faculty = collect($options['faculty'] ?? [])->firstWhere('faculty_id', (int) $v['faculty_id']);
            $summary['Faculty'] = $faculty->full_name ?? ('#'.$v['faculty_id']);
        }
        if (! empty($v['status'])) {
            $labels = $options['statuses'] ?? [];
            $summary['Statuses'] = collect($v['status'])->map(fn ($s) => $labels[$s] ?? $s)->implode(', ');
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveStoredPdf(string $token): array
    {
        abort_unless(Str::isUuid($token), 404);

        $meta = Cache::get($this->pdfCacheKey($token));

        abort_unless(
            is_array($meta)
                && (int) $meta['user_id'] === (int) Auth::id()
                && Storage::disk($this->privateDisk())->exists($meta['path']),
            404,
            'This report has expired. Please generate it again from the Reports page.'
        );

        return $meta;
    }

    private function pdfCacheKey(string $token): string
    {
        return 'osa-report-pdf:'.$token;
    }

    private function pdfDirectory(): string
    {
        return 'osa-reports/'.Auth::id();
    }

    private function privateDisk(): string
    {
        return (string) config('filesystems.private_disk', 'local');
    }

    private function pruneExpiredPdfs(): void
    {
        $disk = Storage::disk($this->privateDisk());
        $cutoff = now()->subMinutes(self::PDF_TTL_MINUTES)->getTimestamp();

        foreach ($disk->files($this->pdfDirectory()) as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateReportFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,school_year_id'],
            'program' => ['nullable', 'string', 'max:50'],
            'year_level' => ['nullable', 'integer', 'between:1,5'],
            'section' => ['nullable', 'string', 'max:50'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculty_members,faculty_id'],
            'status' => ['nullable', 'array'],
            'status.*' => ['string', 'in:pending,approved,excused,not_excused,rejected'],
        ]);
    }
}
