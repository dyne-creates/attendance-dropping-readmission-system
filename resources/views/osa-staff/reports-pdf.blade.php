<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>OSA Institutional Report</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #1f2937;
            line-height: 1.4;
        }

        /* Institutional Header */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #b91c1c;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-logo {
            width: 70px;
            height: auto;
        }
        .header-institution {
            font-size: 14pt;
            font-weight: bold;
            color: #b91c1c;
            letter-spacing: 0.5px;
            margin: 0;
            padding: 0;
        }
        .header-subtext {
            font-size: 8pt;
            color: #4b5563;
            margin: 2px 0;
        }
        .header-dept {
            font-size: 11pt;
            font-weight: bold;
            color: #111827;
            margin-top: 3px;
        }

        /* Title & Meta */
        .report-title-block {
            text-align: center;
            margin-bottom: 14px;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            margin: 0;
        }
        .report-subtitle {
            font-size: 8.5pt;
            color: #6b7280;
            margin-top: 3px;
        }

        /* Meta Box */
        .meta-table {
            width: 100%;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            font-size: 8pt;
            margin-bottom: 15px;
        }
        .meta-table td {
            padding: 5px 8px;
        }
        .meta-label {
            font-weight: bold;
            color: #374151;
            width: 18%;
        }
        .meta-val {
            color: #111827;
        }

        /* Section Headings */
        .section-heading {
            font-size: 10pt;
            font-weight: bold;
            color: #b91c1c;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        /* Summary Stats Cards / Table */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .stats-table td {
            width: 16.66%;
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: center;
            background-color: #ffffff;
        }
        .stat-num {
            font-size: 14pt;
            font-weight: bold;
            color: #111827;
            margin-top: 2px;
        }
        .stat-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: bold;
        }
        .stat-sub {
            font-size: 7pt;
            color: #9ca3af;
            margin-top: 2px;
        }

        /* Charts 2-Column Table */
        .charts-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .chart-cell {
            width: 50%;
            padding: 6px;
            vertical-align: top;
        }
        .chart-box {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 6px;
            text-align: center;
        }
        .chart-title {
            font-size: 8pt;
            font-weight: bold;
            color: #374151;
            margin-bottom: 4px;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 2px;
        }
        .chart-img {
            max-width: 100%;
            height: auto;
            max-height: 180px;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 18px;
        }
        .data-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            text-align: left;
            padding: 5px 6px;
            border: 1px solid #d1d5db;
        }
        .data-table td {
            padding: 4px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 1px 4px;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 2px;
        }
        .badge-success { background-color: #dcfce7; color: #166534; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-neutral { background-color: #f3f4f6; color: #374151; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    {{-- Institutional Header --}}
    <table class="header-table">
        <tr>
            <td width="75" valign="middle">
                @if (file_exists($logo_path))
                    <img src="{{ $logo_path }}" class="header-logo" alt="University of Baguio" />
                @endif
            </td>
            <td valign="middle" style="padding-left: 10px;">
                <div class="header-institution">UNIVERSITY OF BAGUIO</div>
                <div class="header-subtext">General Luna Road, Baguio City, Philippines 2600</div>
                <div class="header-dept">OFFICE OF STUDENT AFFAIRS</div>
            </td>
            <td width="150" align="right" valign="bottom">
                <span style="font-size: 7.5pt; color: #6b7280;">Official Transcript Report</span>
            </td>
        </tr>
    </table>

    {{-- Title Block --}}
    <div class="report-title-block">
        <h1 class="report-title">Student Attendance, Dropping & Readmission Report</h1>
        <div class="report-subtitle">Official statistical summary and case processing records</div>
    </div>

    {{-- Metadata Table --}}
    <table class="meta-table">
        <tr>
            <td class="meta-label">Reporting Scope:</td>
            <td class="meta-val">{{ $periodLabel }}</td>
            <td class="meta-label">School Year / Term:</td>
            <td class="meta-val">{{ $schoolYearLabel }}</td>
        </tr>
        <tr>
            <td class="meta-label">Date Generated:</td>
            <td class="meta-val">{{ $generatedAt }}</td>
            <td class="meta-label">Generated By:</td>
            <td class="meta-val">{{ $generatedBy }}</td>
        </tr>
        @if (!empty($filters['program']) || !empty($filters['section']))
            <tr>
                <td class="meta-label">Program Filter:</td>
                <td class="meta-val">{{ $filters['program'] ?? 'All' }}</td>
                <td class="meta-label">Section Filter:</td>
                <td class="meta-val">{{ $filters['section'] ?? 'All' }}</td>
            </tr>
        @endif
    </table>

    {{-- Section: Summary Statistics --}}
    <div class="section-heading">I. Executive Summary Statistics</div>
    <table class="stats-table">
        <tr>
            <td>
                <div class="stat-label">Total Drops</div>
                <div class="stat-num">{{ $summary['total_drops'] }}</div>
                <div class="stat-sub">{{ $summary['total_students_dropped'] }} students</div>
            </td>
            <td>
                <div class="stat-label">Referrals</div>
                <div class="stat-num">{{ $summary['total_referrals'] }}</div>
                <div class="stat-sub">Instructor initiated</div>
            </td>
            <td>
                <div class="stat-label">Readmission Reqs</div>
                <div class="stat-num">{{ $summary['total_readmissions'] }}</div>
                <div class="stat-sub">{{ $summary['distinct_readmission_students'] }} students</div>
            </td>
            <td>
                <div class="stat-label">Excused Cases</div>
                <div class="stat-num" style="color: #16a34a;">{{ $summary['excused_cases'] }}</div>
                <div class="stat-sub">{{ $summary['not_excused_cases'] }} unexcused</div>
            </td>
            <td>
                <div class="stat-label">Pending Cases</div>
                <div class="stat-num" style="color: #d97706;">{{ $summary['pending_cases'] }}</div>
                <div class="stat-sub">{{ $summary['approved_cases'] }} regular approved</div>
            </td>
            <td>
                <div class="stat-label">Absence Hours</div>
                <div class="stat-num">{{ $summary['accumulated_absence_hours'] }}</div>
                <div class="stat-sub">{{ $summary['tardiness_count'] }} lates</div>
            </td>
        </tr>
    </table>

    {{-- Section: Charts & Visualizations --}}
    @if (!empty($chart_paths))
        <div class="section-heading">II. Graphical Visualizations & Trends</div>
        <table class="charts-table">
            <tr>
                <td class="chart-cell">
                    @if (!empty($chart_paths['chart_drops']))
                        <div class="chart-box">
                            <div class="chart-title">1. Students by Number of Drops</div>
                            <img src="{{ $chart_paths['chart_drops'] }}" class="chart-img" alt="Students by Drops" />
                        </div>
                    @endif
                </td>
                <td class="chart-cell">
                    @if (!empty($chart_paths['chart_excused']))
                        <div class="chart-box">
                            <div class="chart-title">2. Excused vs. Unexcused Cases</div>
                            <img src="{{ $chart_paths['chart_excused'] }}" class="chart-img" alt="Excused vs Unexcused" />
                        </div>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="chart-cell">
                    @if (!empty($chart_paths['chart_timeline']))
                        <div class="chart-box">
                            <div class="chart-title">3. Referrals & Readmissions Over Time</div>
                            <img src="{{ $chart_paths['chart_timeline'] }}" class="chart-img" alt="Monthly Timeline" />
                        </div>
                    @endif
                </td>
                <td class="chart-cell">
                    @if (!empty($chart_paths['chart_status']))
                        <div class="chart-box">
                            <div class="chart-title">4. Requests by Processing Status</div>
                            <img src="{{ $chart_paths['chart_status'] }}" class="chart-img" alt="Status Breakdown" />
                        </div>
                    @endif
                </td>
            </tr>
            @if (!empty($chart_paths['chart_absence']))
                <tr>
                    <td colspan="2" class="chart-cell">
                        <div class="chart-box">
                            <div class="chart-title">5. Accumulated Absence Hours by Subject</div>
                            <img src="{{ $chart_paths['chart_absence'] }}" class="chart-img" style="max-height: 200px;" alt="Absences by Subject" />
                        </div>
                    </td>
                </tr>
            @endif
        </table>
    @endif

    <div class="page-break"></div>

    {{-- Section III: Detailed Dropping Records --}}
    <div class="section-heading">III. Student Dropping Records ({{ count($drops) }} records)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="12%">Student ID</th>
                <th width="20%">Student Name</th>
                <th width="12%">Program</th>
                <th width="14%">Course</th>
                <th width="18%">Faculty Member</th>
                <th width="6%" class="text-center">Drop #</th>
                <th width="10%">Hours</th>
                <th width="8%">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($drops as $drop)
                <tr>
                    <td class="font-mono">{{ $drop->student_number }}</td>
                    <td>{{ $drop->first_name }} {{ $drop->last_name }}</td>
                    <td>{{ $drop->program ?? 'N/A' }}</td>
                    <td>{{ $drop->subject_code }}</td>
                    <td>{{ $drop->faculty_name }}</td>
                    <td class="text-center font-mono">{{ $drop->sequence_in_cycle }}</td>
                    <td>{{ $drop->absence_hours_at_drop }} hrs</td>
                    <td>{{ \Carbon\Carbon::parse($drop->drop_date)->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 12px; color: #6b7280;">No student dropping records found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Section IV: Faculty Referral Records --}}
    <div class="section-heading">IV. Faculty Referral Records ({{ count($referrals) }} records)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="14%">Student ID</th>
                <th width="22%">Student Name</th>
                <th width="14%">Subject</th>
                <th width="22%">Referring Faculty</th>
                <th width="18%">Reason</th>
                <th width="10%">Referral Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($referrals as $ref)
                <tr>
                    <td class="font-mono">{{ $ref->student_number }}</td>
                    <td>{{ $ref->first_name }} {{ $ref->last_name }}</td>
                    <td>{{ $ref->subject_code }}</td>
                    <td>{{ $ref->faculty_name }}</td>
                    <td>{{ $ref->instructor_reason ?? 'Instructor initiated referral' }}</td>
                    <td>{{ \Carbon\Carbon::parse($ref->drop_date)->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 12px; color: #6b7280;">No faculty referrals recorded for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Section V: Readmission Requests & Processing Records --}}
    <div class="section-heading">V. Readmission Requests & OSA Processing ({{ count($readmissions) }} records)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="10%">Req #</th>
                <th width="12%">Student ID</th>
                <th width="20%">Student Name</th>
                <th width="12%">Subject</th>
                <th width="14%">Submitted</th>
                <th width="12%">Status</th>
                <th width="10%">Processed</th>
                <th width="10%">Processor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($readmissions as $req)
                <tr>
                    <td class="font-mono">#{{ $req->readmission_id }}</td>
                    <td class="font-mono">{{ $req->student_number }}</td>
                    <td>{{ $req->first_name }} {{ $req->last_name }}</td>
                    <td>{{ $req->subject_code }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->submission_date)->format('M d, Y') }}</td>
                    <td>
                        @if ($req->outcome === 'excused')
                            <span class="badge badge-success">Excused</span>
                        @elseif ($req->outcome === 'not_excused')
                            <span class="badge badge-danger">Not Excused</span>
                        @elseif ($req->status === 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif ($req->status === 'rejected')
                            <span class="badge badge-danger">Rejected</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </td>
                    <td>{{ $req->processed_date ? \Carbon\Carbon::parse($req->processed_date)->format('M d, Y') : '—' }}</td>
                    <td>{{ $req->processed_by_name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 12px; color: #6b7280;">No readmission records found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Section VI: Student Absence & Attendance Records --}}
    <div class="section-heading">VI. Attendance & Absence Log Summary ({{ count($absences) }} records)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="14%">Student ID</th>
                <th width="24%">Student Name</th>
                <th width="14%">Subject</th>
                <th width="14%">Session Date</th>
                <th width="14%">Status</th>
                <th width="20%">Absence Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($absences as $abs)
                <tr>
                    <td class="font-mono">{{ $abs->student_number }}</td>
                    <td>{{ $abs->first_name }} {{ $abs->last_name }}</td>
                    <td>{{ $abs->subject_code }}</td>
                    <td>{{ \Carbon\Carbon::parse($abs->session_date)->format('M d, Y') }}</td>
                    <td>
                        @if ($abs->attendance_status === 'absent')
                            <span class="badge badge-danger">Absent</span>
                        @elseif ($abs->attendance_status === 'late')
                            <span class="badge badge-warning">Late</span>
                        @elseif ($abs->attendance_status === 'excused')
                            <span class="badge badge-success">Excused</span>
                        @else
                            <span class="badge badge-neutral">{{ ucfirst($abs->attendance_status) }}</span>
                        @endif
                    </td>
                    <td>{{ $abs->hours_absent ?? '0.00' }} hrs</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 12px; color: #6b7280;">No absence records found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
