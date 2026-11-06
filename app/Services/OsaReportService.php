<?php

namespace App\Services;

use App\Models\Course;
use App\Models\DroppingTransaction;
use App\Models\FacultyMember;
use App\Models\ReadmissionRequest;
use App\Models\SchoolYear;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OsaReportService
{
    /**
     * Resolve effective date range based on filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function resolveDateRange(array $filters): array
    {
        $startDate = null;
        $endDate = null;

        $year = ! empty($filters['year']) ? (int) $filters['year'] : null;
        $month = ! empty($filters['month']) ? (int) $filters['month'] : null;
        $quarter = ! empty($filters['quarter']) ? (int) $filters['quarter'] : null;

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $startDate = Carbon::parse($filters['date_from'])->startOfDay();
            $endDate = Carbon::parse($filters['date_to'])->endOfDay();

            if ($startDate->greaterThan($endDate)) {
                throw ValidationException::withMessages([
                    'date_from' => 'The Start Date cannot be after the End Date.',
                ]);
            }

            return [$startDate, $endDate];
        }

        if (! empty($filters['date_from'])) {
            $startDate = Carbon::parse($filters['date_from'])->startOfDay();
        }

        if (! empty($filters['date_to'])) {
            $endDate = Carbon::parse($filters['date_to'])->endOfDay();
        }

        if ($startDate && $endDate) {
            return [$startDate, $endDate];
        }

        if ($quarter && $year) {
            $quarterMonths = [
                1 => [1, 3],
                2 => [4, 6],
                3 => [7, 9],
                4 => [10, 12],
            ];

            if (isset($quarterMonths[$quarter])) {
                [$startM, $endM] = $quarterMonths[$quarter];
                $startDate = Carbon::create($year, $startM, 1)->startOfMonth();
                $endDate = Carbon::create($year, $endM, 1)->endOfMonth();
            }
        } elseif ($month && $year) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        } elseif ($year) {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate = Carbon::create($year, 12, 31)->endOfYear();
        }

        return [$startDate, $endDate];
    }

    /**
     * Get options for filter dropdowns.
     *
     * @return array<string, mixed>
     */
    public function getFilterOptions(): array
    {
        return [
            'school_years' => SchoolYear::query()
                ->orderByDesc('year_label')
                ->orderBy('semester')
                ->get(),
            'programs' => Student::query()
                ->whereNotNull('program')
                ->where('program', '!=', '')
                ->distinct()
                ->orderBy('program')
                ->pluck('program'),
            'year_levels' => Student::query()
                ->whereNotNull('year_level')
                ->distinct()
                ->orderBy('year_level')
                ->pluck('year_level'),
            'sections' => Course::query()
                ->whereNotNull('section')
                ->where('section', '!=', '')
                ->distinct()
                ->orderBy('section')
                ->pluck('section'),
            'faculty' => FacultyMember::query()
                ->orderBy('full_name')
                ->get(['faculty_id', 'full_name']),
            'statuses' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'excused' => 'Excused',
                'not_excused' => 'Not Excused',
                'rejected' => 'Rejected',
            ],
            'years' => $this->getAvailableYears(),
        ];
    }

    /**
     * Get distinct years available in records.
     *
     * @return array<int, int>
     */
    private function getAvailableYears(): array
    {
        $years = collect();
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        $dropYearExpr = $isSqlite ? "strftime('%Y', drop_date) as yr" : 'YEAR(drop_date) as yr';
        $readmissionYearExpr = $isSqlite ? "strftime('%Y', created_at) as yr" : 'YEAR(created_at) as yr';
        $attendanceYearExpr = $isSqlite ? "strftime('%Y', session_date) as yr" : 'YEAR(session_date) as yr';

        $dropYears = DB::table('dropping_transactions')
            ->selectRaw($dropYearExpr)
            ->distinct()
            ->pluck('yr');
        $years = $years->merge($dropYears);

        $readmissionYears = DB::table('readmission_requests')
            ->selectRaw($readmissionYearExpr)
            ->distinct()
            ->pluck('yr');
        $years = $years->merge($readmissionYears);

        $attendanceYears = DB::table('attendance_records')
            ->selectRaw($attendanceYearExpr)
            ->distinct()
            ->pluck('yr');
        $years = $years->merge($attendanceYears);

        $currentYear = (int) date('Y');
        $years->push($currentYear);

        return $years->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn ($y) => (int) $y)
            ->all();
    }

    /**
     * Compute summary statistics based on current filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSummaryStatistics(array $filters): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        // 1. Dropping statistics
        $dropQuery = $this->applyCommonFilters(
            DB::table('dropping_transactions')
                ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('courses', 'enrollments.course_id', '=', 'courses.course_id'),
            $filters,
            'dropping_transactions.drop_date',
            $startDate,
            $endDate
        );

        $totalDrops = (clone $dropQuery)->count();
        $totalStudentsDropped = (clone $dropQuery)->distinct('enrollments.student_id')->count('enrollments.student_id');

        // 2. Referrals
        $referralQuery = (clone $dropQuery)
            ->where('dropping_transactions.drop_type', DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL);
        $totalReferrals = $referralQuery->count();

        // 3. Readmissions
        $readmissionQuery = $this->applyCommonFilters(
            DB::table('readmission_requests')
                ->join('dropping_transactions', 'readmission_requests.drop_id', '=', 'dropping_transactions.drop_id')
                ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('courses', 'enrollments.course_id', '=', 'courses.course_id'),
            $filters,
            'readmission_requests.created_at',
            $startDate,
            $endDate
        );

        $totalReadmissionRequests = (clone $readmissionQuery)->count();
        $distinctReadmissionStudents = (clone $readmissionQuery)->distinct('enrollments.student_id')->count('enrollments.student_id');

        $pendingCases = (clone $readmissionQuery)
            ->where('readmission_requests.status', 'pending')
            ->count();

        $excusedCases = (clone $readmissionQuery)
            ->where('readmission_requests.outcome', ReadmissionRequest::OUTCOME_EXCUSED)
            ->count();

        $notExcusedCases = (clone $readmissionQuery)
            ->where('readmission_requests.outcome', ReadmissionRequest::OUTCOME_NOT_EXCUSED)
            ->count();

        $approvedCases = (clone $readmissionQuery)
            ->where('readmission_requests.status', 'approved')
            ->where(function (Builder $query): void {
                $query->whereNull('readmission_requests.outcome')
                    ->orWhere('readmission_requests.outcome', ReadmissionRequest::OUTCOME_APPROVED);
            })
            ->count();

        $rejectedCases = (clone $readmissionQuery)
            ->where('readmission_requests.status', 'rejected')
            ->where(function (Builder $query): void {
                $query->whereNull('readmission_requests.outcome')
                    ->orWhere('readmission_requests.outcome', ReadmissionRequest::OUTCOME_REJECTED);
            })
            ->count();

        // Processed requests in period
        $processedInPeriodQuery = $this->applyCommonFilters(
            DB::table('readmission_requests')
                ->join('dropping_transactions', 'readmission_requests.drop_id', '=', 'dropping_transactions.drop_id')
                ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('courses', 'enrollments.course_id', '=', 'courses.course_id'),
            $filters,
            'readmission_requests.processed_date',
            $startDate,
            $endDate
        );
        $totalProcessedInPeriod = $processedInPeriodQuery
            ->whereNotNull('readmission_requests.processed_date')
            ->count();

        // 4. Attendance / Absence statistics
        $attendanceQuery = $this->applyCommonFilters(
            DB::table('attendance_records')
                ->join('enrollments', 'attendance_records.enrollment_id', '=', 'enrollments.enrollment_id')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('courses', 'enrollments.course_id', '=', 'courses.course_id'),
            $filters,
            'attendance_records.session_date',
            $startDate,
            $endDate
        );

        $accumulatedAbsenceHours = (clone $attendanceQuery)
            ->whereIn('attendance_records.status', ['absent', 'late'])
            ->sum('attendance_records.hours_absent') ?? 0;

        $tardinessCount = (clone $attendanceQuery)
            ->where('attendance_records.status', 'late')
            ->count();

        return [
            'total_drops' => $totalDrops,
            'total_students_dropped' => $totalStudentsDropped,
            'total_referrals' => $totalReferrals,
            'total_readmissions' => $totalReadmissionRequests,
            'distinct_readmission_students' => $distinctReadmissionStudents,
            'pending_cases' => $pendingCases,
            'excused_cases' => $excusedCases,
            'not_excused_cases' => $notExcusedCases,
            'approved_cases' => $approvedCases,
            'rejected_cases' => $rejectedCases,
            'processed_in_period' => $totalProcessedInPeriod,
            'accumulated_absence_hours' => round((float) $accumulatedAbsenceHours, 2),
            'tardiness_count' => $tardinessCount,
        ];
    }

    /**
     * Compute data for all 5 Chart.js visualizations.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getChartData(array $filters): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        // 1. Bar Chart: Students by Number of Drops (0, 1, 2, 3+)
        $dropsPerStudent = DB::table('students')
            ->leftJoin('enrollments', 'students.student_id', '=', 'enrollments.student_id')
            ->leftJoin('dropping_transactions', function ($join) use ($startDate, $endDate): void {
                $join->on('enrollments.enrollment_id', '=', 'dropping_transactions.enrollment_id');
                if ($startDate && $endDate) {
                    $join->whereBetween('dropping_transactions.drop_date', [
                        $startDate->toDateString(),
                        $endDate->toDateString(),
                    ]);
                }
            });

        if (! empty($filters['program'])) {
            $dropsPerStudent->where('students.program', $filters['program']);
        }
        if (! empty($filters['year_level'])) {
            $dropsPerStudent->where('students.year_level', (int) $filters['year_level']);
        }
        if (! empty($filters['school_year_id'])) {
            $dropsPerStudent->where(function ($q) use ($filters): void {
                $q->whereNull('enrollments.course_id')
                    ->orWhereExists(function ($sub) use ($filters): void {
                        $sub->select(DB::raw(1))
                            ->from('courses')
                            ->whereColumn('courses.course_id', 'enrollments.course_id')
                            ->where('courses.school_year_id', $filters['school_year_id']);
                    });
            });
        }

        $dropCounts = $dropsPerStudent
            ->groupBy('students.student_id')
            ->selectRaw('students.student_id, COUNT(dropping_transactions.drop_id) as drop_count')
            ->get();

        $dropBuckets = [
            '0 drops' => 0,
            '1 drop' => 0,
            '2 drops' => 0,
            '3 drops' => 0,
            '4+ drops' => 0,
        ];

        foreach ($dropCounts as $row) {
            $c = (int) $row->drop_count;
            if ($c === 0) {
                $dropBuckets['0 drops']++;
            } elseif ($c === 1) {
                $dropBuckets['1 drop']++;
            } elseif ($c === 2) {
                $dropBuckets['2 drops']++;
            } elseif ($c === 3) {
                $dropBuckets['3 drops']++;
            } else {
                $dropBuckets['4+ drops']++;
            }
        }

        $chartDrops = [
            'labels' => ['0 Drops', '1 Drop', '2 Drops', '3 Drops', '4+ Drops'],
            'data' => array_values($dropBuckets),
        ];

        // 2. Doughnut / Pie Chart: Excused vs. Unexcused Cases (Excused, Unexcused, Pending)
        $readmissionBase = $this->applyCommonFilters(
            DB::table('readmission_requests')
                ->join('dropping_transactions', 'readmission_requests.drop_id', '=', 'dropping_transactions.drop_id')
                ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('courses', 'enrollments.course_id', '=', 'courses.course_id'),
            $filters,
            'readmission_requests.created_at',
            $startDate,
            $endDate
        );

        $excused = (clone $readmissionBase)->where('readmission_requests.outcome', ReadmissionRequest::OUTCOME_EXCUSED)->count();
        $unexcused = (clone $readmissionBase)->where('readmission_requests.outcome', ReadmissionRequest::OUTCOME_NOT_EXCUSED)->count();
        $pending = (clone $readmissionBase)->where('readmission_requests.status', 'pending')->count();

        $chartExcused = [
            'labels' => ['Excused', 'Unexcused', 'Pending'],
            'data' => [$excused, $unexcused, $pending],
        ];

        // 3. Bar or Line Chart: Referral and Readmission Requests Over Time
        $timelineData = $this->getMonthlyTimeline($filters, $startDate, $endDate);

        // 4. Bar Chart: Requests by Processing Status (Pending, Approved, Excused, Not Excused, Rejected)
        $approvedOther = (clone $readmissionBase)
            ->where('readmission_requests.status', 'approved')
            ->where(function (Builder $query): void {
                $query->whereNull('readmission_requests.outcome')
                    ->orWhere('readmission_requests.outcome', ReadmissionRequest::OUTCOME_APPROVED);
            })
            ->count();

        $rejectedOther = (clone $readmissionBase)
            ->where('readmission_requests.status', 'rejected')
            ->where(function (Builder $query): void {
                $query->whereNull('readmission_requests.outcome')
                    ->orWhere('readmission_requests.outcome', ReadmissionRequest::OUTCOME_REJECTED);
            })
            ->count();

        $chartStatus = [
            'labels' => ['Pending', 'Approved', 'Excused', 'Not Excused', 'Rejected'],
            'data' => [$pending, $approvedOther, $excused, $unexcused, $rejectedOther],
        ];

        // 5. Bar Chart: Accumulated Absence Hours by Subject Code
        $absenceBase = $this->applyCommonFilters(
            DB::table('attendance_records')
                ->join('enrollments', 'attendance_records.enrollment_id', '=', 'enrollments.enrollment_id')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('courses', 'enrollments.course_id', '=', 'courses.course_id'),
            $filters,
            'attendance_records.session_date',
            $startDate,
            $endDate
        );

        $subjectAbsences = (clone $absenceBase)
            ->whereIn('attendance_records.status', ['absent', 'late'])
            ->select('courses.subject_code', DB::raw('SUM(attendance_records.hours_absent) as total_hours'))
            ->groupBy('courses.subject_code')
            ->orderByDesc('total_hours')
            ->limit(10)
            ->get();

        $chartAbsence = [
            'labels' => $subjectAbsences->pluck('subject_code')->all(),
            'data' => $subjectAbsences->map(fn ($r) => round((float) $r->total_hours, 1))->all(),
        ];

        return [
            'drops' => $chartDrops,
            'excused' => $chartExcused,
            'timeline' => $timelineData,
            'status' => $chartStatus,
            'absence' => $chartAbsence,
        ];
    }

    /**
     * Build monthly grouped timeline for referrals and readmissions.
     *
     * @param  array<string, mixed>  $filters
     * @return array{labels: array<int, string>, referrals: array<int, int>, readmissions: array<int, int>}
     */
    private function getMonthlyTimeline(array $filters, ?Carbon $startDate, ?Carbon $endDate): array
    {
        // Default to past 6 months if no dates given
        $start = $startDate ? $startDate->copy()->startOfMonth() : now()->subMonths(5)->startOfMonth();
        $end = $endDate ? $endDate->copy()->endOfMonth() : now()->endOfMonth();

        $periodMonths = [];
        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end) && count($periodMonths) < 24) {
            $periodMonths[$cursor->format('Y-m')] = $cursor->format('M Y');
            $cursor->addMonth();
        }

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $dropFormatExpr = $isSqlite ? "strftime('%Y-%m', dropping_transactions.drop_date) as ym, COUNT(*) as cnt" : "DATE_FORMAT(dropping_transactions.drop_date, '%Y-%m') as ym, COUNT(*) as cnt";
        $readmissionFormatExpr = $isSqlite ? "strftime('%Y-%m', readmission_requests.created_at) as ym, COUNT(*) as cnt" : "DATE_FORMAT(readmission_requests.created_at, '%Y-%m') as ym, COUNT(*) as cnt";

        // Query referrals grouped by YYYY-MM
        $referralQuery = $this->applyCommonFilters(
            DB::table('dropping_transactions')
                ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
                ->where('dropping_transactions.drop_type', DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL),
            $filters,
            'dropping_transactions.drop_date',
            $start,
            $end
        );

        $referralCounts = $referralQuery
            ->selectRaw($dropFormatExpr)
            ->groupBy('ym')
            ->pluck('cnt', 'ym');

        // Query readmissions grouped by YYYY-MM
        $readmissionQuery = $this->applyCommonFilters(
            DB::table('readmission_requests')
                ->join('dropping_transactions', 'readmission_requests.drop_id', '=', 'dropping_transactions.drop_id')
                ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('courses', 'enrollments.course_id', '=', 'courses.course_id'),
            $filters,
            'readmission_requests.created_at',
            $start,
            $end
        );

        $readmissionCounts = $readmissionQuery
            ->selectRaw($readmissionFormatExpr)
            ->groupBy('ym')
            ->pluck('cnt', 'ym');

        $labels = [];
        $referralData = [];
        $readmissionData = [];

        foreach ($periodMonths as $key => $label) {
            $labels[] = $label;
            $referralData[] = (int) ($referralCounts[$key] ?? 0);
            $readmissionData[] = (int) ($readmissionCounts[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'referrals' => $referralData,
            'readmissions' => $readmissionData,
        ];
    }

    /**
     * Get paginated Student Dropping Records.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getDroppingRecords(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $query = DB::table('dropping_transactions')
            ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->join('faculty_members', 'dropping_transactions.dropped_by', '=', 'faculty_members.faculty_id')
            ->select(
                'dropping_transactions.drop_id',
                'dropping_transactions.drop_date',
                'dropping_transactions.absence_hours_at_drop',
                'dropping_transactions.sequence_in_cycle',
                'dropping_transactions.drop_type',
                'dropping_transactions.instructor_reason',
                'students.student_id',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.program',
                'students.year_level',
                'courses.subject_code',
                'courses.course_name',
                'courses.section',
                'faculty_members.full_name as faculty_name'
            )
            ->orderByDesc('dropping_transactions.drop_date')
            ->orderByDesc('dropping_transactions.drop_id');

        $this->applyCommonFilters($query, $filters, 'dropping_transactions.drop_date', $startDate, $endDate);

        return $query->paginate($perPage, ['*'], 'drops_page')->withQueryString();
    }

    /**
     * Get paginated Referral Records.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getReferralRecords(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $query = DB::table('dropping_transactions')
            ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->join('faculty_members', 'dropping_transactions.dropped_by', '=', 'faculty_members.faculty_id')
            ->where('dropping_transactions.drop_type', DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL)
            ->select(
                'dropping_transactions.drop_id',
                'dropping_transactions.drop_date',
                'dropping_transactions.absence_hours_at_drop',
                'dropping_transactions.instructor_reason',
                'dropping_transactions.sequence_in_cycle',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.program',
                'courses.subject_code',
                'courses.course_name',
                'faculty_members.full_name as faculty_name'
            )
            ->orderByDesc('dropping_transactions.drop_date')
            ->orderByDesc('dropping_transactions.drop_id');

        $this->applyCommonFilters($query, $filters, 'dropping_transactions.drop_date', $startDate, $endDate);

        return $query->paginate($perPage, ['*'], 'referrals_page')->withQueryString();
    }

    /**
     * Get paginated Readmission Requests.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getReadmissionRecords(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $query = DB::table('readmission_requests')
            ->join('dropping_transactions', 'readmission_requests.drop_id', '=', 'dropping_transactions.drop_id')
            ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->leftJoin('osa_staff', 'readmission_requests.processed_by', '=', 'osa_staff.osa_id')
            ->leftJoin('users as osa_users', 'osa_staff.user_id', '=', 'osa_users.user_id')
            ->select(
                'readmission_requests.readmission_id',
                'readmission_requests.attempt_number',
                'readmission_requests.status',
                'readmission_requests.outcome',
                'readmission_requests.reason_code',
                'readmission_requests.channel',
                'readmission_requests.created_at as submission_date',
                'readmission_requests.processed_date',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.program',
                'courses.subject_code',
                'courses.course_name',
                'osa_users.name as processed_by_name'
            )
            ->orderByDesc('readmission_requests.created_at')
            ->orderByDesc('readmission_requests.readmission_id');

        $this->applyCommonFilters($query, $filters, 'readmission_requests.created_at', $startDate, $endDate);

        return $query->paginate($perPage, ['*'], 'readmissions_page')->withQueryString();
    }

    /**
     * Get paginated Absence Records.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAbsenceRecords(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $query = DB::table('attendance_records')
            ->join('enrollments', 'attendance_records.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->select(
                'attendance_records.attendance_id',
                'attendance_records.session_date',
                'attendance_records.status as attendance_status',
                'attendance_records.hours_absent',
                'attendance_records.remarks',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.program',
                'courses.subject_code',
                'courses.course_name',
                'courses.section'
            )
            ->whereIn('attendance_records.status', ['absent', 'late', 'excused'])
            ->orderByDesc('attendance_records.session_date')
            ->orderByDesc('attendance_records.attendance_id');

        $this->applyCommonFilters($query, $filters, 'attendance_records.session_date', $startDate, $endDate);

        return $query->paginate($perPage, ['*'], 'absences_page')->withQueryString();
    }

    /**
     * Get all tabular records for PDF generation (capped to prevent timeouts).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, Collection<int, object>>
     */
    public function getAllTabularRecordsForPdf(array $filters, int $limit = 100): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        // Drops
        $dropsQuery = DB::table('dropping_transactions')
            ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->join('faculty_members', 'dropping_transactions.dropped_by', '=', 'faculty_members.faculty_id')
            ->select(
                'dropping_transactions.drop_id',
                'dropping_transactions.drop_date',
                'dropping_transactions.absence_hours_at_drop',
                'dropping_transactions.sequence_in_cycle',
                'dropping_transactions.drop_type',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.program',
                'courses.subject_code',
                'faculty_members.full_name as faculty_name'
            )
            ->orderByDesc('dropping_transactions.drop_date')
            ->limit($limit);
        $this->applyCommonFilters($dropsQuery, $filters, 'dropping_transactions.drop_date', $startDate, $endDate);
        $drops = $dropsQuery->get();

        // Referrals
        $referralsQuery = DB::table('dropping_transactions')
            ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->join('faculty_members', 'dropping_transactions.dropped_by', '=', 'faculty_members.faculty_id')
            ->where('dropping_transactions.drop_type', DroppingTransaction::TYPE_INSTRUCTOR_REFERRAL)
            ->select(
                'dropping_transactions.drop_id',
                'dropping_transactions.drop_date',
                'dropping_transactions.instructor_reason',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'courses.subject_code',
                'faculty_members.full_name as faculty_name'
            )
            ->orderByDesc('dropping_transactions.drop_date')
            ->limit($limit);
        $this->applyCommonFilters($referralsQuery, $filters, 'dropping_transactions.drop_date', $startDate, $endDate);
        $referrals = $referralsQuery->get();

        // Readmissions
        $readmissionsQuery = DB::table('readmission_requests')
            ->join('dropping_transactions', 'readmission_requests.drop_id', '=', 'dropping_transactions.drop_id')
            ->join('enrollments', 'dropping_transactions.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->leftJoin('osa_staff', 'readmission_requests.processed_by', '=', 'osa_staff.osa_id')
            ->leftJoin('users as osa_users', 'osa_staff.user_id', '=', 'osa_users.user_id')
            ->select(
                'readmission_requests.readmission_id',
                'readmission_requests.status',
                'readmission_requests.outcome',
                'readmission_requests.created_at as submission_date',
                'readmission_requests.processed_date',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'courses.subject_code',
                'osa_users.name as processed_by_name'
            )
            ->orderByDesc('readmission_requests.created_at')
            ->limit($limit);
        $this->applyCommonFilters($readmissionsQuery, $filters, 'readmission_requests.created_at', $startDate, $endDate);
        $readmissions = $readmissionsQuery->get();

        // Absences
        $absencesQuery = DB::table('attendance_records')
            ->join('enrollments', 'attendance_records.enrollment_id', '=', 'enrollments.enrollment_id')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->select(
                'attendance_records.attendance_id',
                'attendance_records.session_date',
                'attendance_records.status as attendance_status',
                'attendance_records.hours_absent',
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'courses.subject_code'
            )
            ->whereIn('attendance_records.status', ['absent', 'late', 'excused'])
            ->orderByDesc('attendance_records.session_date')
            ->limit($limit);
        $this->applyCommonFilters($absencesQuery, $filters, 'attendance_records.session_date', $startDate, $endDate);
        $absences = $absencesQuery->get();

        return [
            'drops' => $drops,
            'referrals' => $referrals,
            'readmissions' => $readmissions,
            'absences' => $absences,
        ];
    }

    /**
     * Apply common filters across records.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyCommonFilters(
        Builder $query,
        array $filters,
        ?string $dateColumn = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Builder {
        if ($dateColumn && $startDate && $endDate) {
            $query->whereBetween($dateColumn, [
                $startDate->startOfDay()->toDateTimeString(),
                $endDate->endOfDay()->toDateTimeString(),
            ]);
        } elseif ($dateColumn && $startDate) {
            $query->where($dateColumn, '>=', $startDate->startOfDay()->toDateTimeString());
        } elseif ($dateColumn && $endDate) {
            $query->where($dateColumn, '<=', $endDate->endOfDay()->toDateTimeString());
        }

        if (! empty($filters['school_year_id'])) {
            $query->where('courses.school_year_id', (int) $filters['school_year_id']);
        }

        if (! empty($filters['program'])) {
            $query->where('students.program', $filters['program']);
        }

        if (! empty($filters['year_level'])) {
            $query->where('students.year_level', (int) $filters['year_level']);
        }

        if (! empty($filters['section'])) {
            $query->where('courses.section', $filters['section']);
        }

        if (! empty($filters['faculty_id'])) {
            $query->where('courses.faculty_id', (int) $filters['faculty_id']);
        }

        if (! empty($filters['status']) && is_array($filters['status'])) {
            $statuses = array_filter($filters['status']);
            if (! empty($statuses)) {
                $query->where(function (Builder $sub) use ($statuses): void {
                    foreach ($statuses as $st) {
                        if ($st === 'excused') {
                            $sub->orWhere('readmission_requests.outcome', ReadmissionRequest::OUTCOME_EXCUSED);
                        } elseif ($st === 'not_excused') {
                            $sub->orWhere('readmission_requests.outcome', ReadmissionRequest::OUTCOME_NOT_EXCUSED);
                        } else {
                            $sub->orWhere('readmission_requests.status', $st);
                        }
                    }
                });
            }
        }

        return $query;
    }
}
