<?php

namespace App\Http\Controllers\OsaStaff;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Services\OsaPdfReportService;
use App\Services\OsaReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

    /**
     * Export institutional PDF report using mPDF with rendered browser chart images.
     */
    public function exportPdf(
        Request $request,
        OsaReportService $reportService,
        OsaPdfReportService $pdfService
    ): Response {
        $report = $this->generatePdfReport($request, $reportService, $pdfService);
        $filename = 'OSA-Report-'.$report['filenameTimestamp'].'.pdf';

        return response($report['pdf'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Preview institutional PDF report inline for review before download.
     */
    public function previewPdf(
        Request $request,
        OsaReportService $reportService,
        OsaPdfReportService $pdfService
    ): Response {
        $report = $this->generatePdfReport($request, $reportService, $pdfService);

        return response($report['pdf'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="OSA-Report-'.$report['filenameTimestamp'].'.pdf"',
        ]);
    }

    /**
     * @return array{pdf: string, filenameTimestamp: string}
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
        ];
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
