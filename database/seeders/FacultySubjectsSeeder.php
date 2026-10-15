<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Enrollment;
use App\Models\FacultyMember;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FacultySubjectsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get or create active school year
        $schoolYear = SchoolYear::first() ?? SchoolYear::create([
            'year_label' => '2026-2027',
            'semester' => '1st Semester',
            'start_date' => '2026-08-10',
            'end_date' => '2026-12-18',
            'is_archived' => false,
        ]);

        // 2. Ensure Faculty Danny Storm exists
        $facultyUser = User::where('email', 'danny.storm@e.ubaguio.edu')->first();
        if (! $facultyUser) {
            $facultyUser = User::create([
                'name' => 'Danny Storm',
                'role' => 'faculty',
                'email' => 'danny.storm@e.ubaguio.edu',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        $faculty = FacultyMember::firstOrCreate(
            ['user_id' => $facultyUser->user_id],
            [
                'full_name' => 'Danny Storm',
                'department' => 'School of Information Technology',
                'suppress_schedule_warnings' => false,
            ]
        );

        // 3. Create or find the list of students
        $studentsList = [
            ['student_number' => '2023-00101', 'first_name' => 'John', 'last_name' => 'Depp', 'email' => 'john.depp@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 3],
            ['student_number' => '2024-00202', 'first_name' => 'Steven', 'last_name' => 'Strange', 'email' => 'steven.strange@s.ubaguio.edu', 'program' => 'BSCS', 'year' => 2],
            ['student_number' => '2024-00301', 'first_name' => 'Mina', 'last_name' => 'Akbar', 'email' => 'mina.akbar@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 2],
            ['student_number' => '2024-00302', 'first_name' => 'Oliver', 'last_name' => 'Albe', 'email' => 'oliver.albe@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 2],
            ['student_number' => '2024-00303', 'first_name' => 'Hunter', 'last_name' => 'Brown', 'email' => 'hunter.brown@s.ubaguio.edu', 'program' => 'BSCS', 'year' => 3],
            ['student_number' => '2024-00304', 'first_name' => 'Duncan', 'last_name' => 'Carroll', 'email' => 'duncan.carroll@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 2],
            ['student_number' => '2024-00305', 'first_name' => 'Chris', 'last_name' => 'Casper', 'email' => 'chris.casper@s.ubaguio.edu', 'program' => 'BSCS', 'year' => 3],
            ['student_number' => '2024-00306', 'first_name' => 'Emily', 'last_name' => 'Churchill', 'email' => 'emily.churchill@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 2],
            ['student_number' => '2023-1001', 'first_name' => 'Juan', 'last_name' => 'dela Cruz', 'email' => 'juan.delacruz@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 3],
            ['student_number' => '2023-1002', 'first_name' => 'Maria Clara', 'last_name' => 'Santos', 'email' => 'maria.santos@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 3],
            ['student_number' => '2023-1003', 'first_name' => 'Gabriel', 'last_name' => 'Reyes', 'email' => 'gabriel.reyes@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 3],
            ['student_number' => '2023-1004', 'first_name' => 'Beatrice', 'last_name' => 'Bautista', 'email' => 'beatrice.bautista@s.ubaguio.edu', 'program' => 'BSCS', 'year' => 3],
            ['student_number' => '2023-1005', 'first_name' => 'Christian', 'last_name' => 'Garcia', 'email' => 'christian.garcia@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 3],
            ['student_number' => '2023-1006', 'first_name' => 'Sophia', 'last_name' => 'Aquino', 'email' => 'sophia.aquino@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 3],
            ['student_number' => '2023-1007', 'first_name' => 'Angelo', 'last_name' => 'Mendoza', 'email' => 'angelo.mendoza@s.ubaguio.edu', 'program' => 'BSCS', 'year' => 3],
            ['student_number' => '2023-1008', 'first_name' => 'Camille', 'last_name' => 'Navarro', 'email' => 'camille.navarro@s.ubaguio.edu', 'program' => 'BSIT', 'year' => 3],
        ];

        $studentsByNumber = [];
        foreach ($studentsList as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => "{$data['first_name']} {$data['last_name']}",
                    'role' => 'student',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $student = Student::firstOrCreate(
                ['student_number' => $data['student_number']],
                [
                    'user_id' => $user->user_id,
                    'last_name' => $data['last_name'],
                    'first_name' => $data['first_name'],
                    'honorifics' => 'Mx.',
                    'department' => 'School of Information Technology',
                    'program' => $data['program'],
                    'year_level' => $data['year'],
                ]
            );

            $studentsByNumber[$data['student_number']] = $student;
        }

        // 4. Create Danny Storm's 4 distinct subjects
        $subjectsData = [
            [
                'subject_code' => 'AGLDEV',
                'course_name' => 'Agile Development',
                'section' => 'IDB1',
                'hours_per_week' => 5,
                'total_semester_hours' => 90.00,
                'schedules' => [
                    ['day_of_week' => 'Mon', 'start_time' => '14:00:00', 'end_time' => '17:00:00', 'room' => 'F-402'],
                    ['day_of_week' => 'Fri', 'start_time' => '14:00:00', 'end_time' => '16:00:00', 'room' => 'CL-3'],
                ],
                'students' => [
                    '2023-00101', // John Depp
                    '2024-00301', // Mina Akbar
                    '2024-00302', // Oliver Albe
                    '2024-00303', // Hunter Brown
                    '2024-00304', // Duncan Carroll
                    '2024-00305', // Chris Casper
                    '2024-00306', // Emily Churchill
                    '2023-1001',  // Juan dela Cruz
                ],
            ],
            [
                'subject_code' => 'WEBDEV',
                'course_name' => 'Web Systems and Technologies',
                'section' => 'IAA1',
                'hours_per_week' => 4,
                'total_semester_hours' => 72.00,
                'schedules' => [
                    ['day_of_week' => 'Tue', 'start_time' => '08:30:00', 'end_time' => '10:30:00', 'room' => 'F-301'],
                    ['day_of_week' => 'Thu', 'start_time' => '08:30:00', 'end_time' => '10:30:00', 'room' => 'CL-2'],
                ],
                'students' => [
                    '2024-00202', // Steven Strange
                    '2023-1002',  // Maria Clara Santos
                    '2023-1003',  // Gabriel Reyes
                    '2023-1004',  // Beatrice Bautista
                    '2023-1005',  // Christian Garcia
                    '2023-1006',  // Sophia Aquino
                    '2024-00301', // Mina Akbar
                    '2024-00302', // Oliver Albe
                ],
            ],
            [
                'subject_code' => 'CS211',
                'course_name' => 'Data Structures and Algorithms',
                'section' => 'ICA1',
                'hours_per_week' => 4,
                'total_semester_hours' => 72.00,
                'schedules' => [
                    ['day_of_week' => 'Wed', 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'room' => 'F-405'],
                    ['day_of_week' => 'Fri', 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'room' => 'F-405'],
                ],
                'students' => [
                    '2023-1007',  // Angelo Mendoza
                    '2023-1008',  // Camille Navarro
                    '2024-00303', // Hunter Brown
                    '2024-00304', // Duncan Carroll
                    '2023-00101', // John Depp
                    '2024-00202', // Steven Strange
                    '2023-1004',  // Beatrice Bautista
                ],
            ],
            [
                'subject_code' => 'IT212',
                'course_name' => 'Database Management Systems',
                'section' => 'IDB2',
                'hours_per_week' => 3,
                'total_semester_hours' => 54.00,
                'schedules' => [
                    ['day_of_week' => 'Mon', 'start_time' => '09:00:00', 'end_time' => '12:00:00', 'room' => 'CL-5'],
                ],
                'students' => [
                    '2024-00305', // Chris Casper
                    '2024-00306', // Emily Churchill
                    '2023-1001',  // Juan dela Cruz
                    '2023-1002',  // Maria Clara Santos
                    '2023-1005',  // Christian Garcia
                    '2023-1006',  // Sophia Aquino
                ],
            ],
            [
                'subject_code' => 'IT301',
                'course_name' => 'Operating Systems',
                'section' => 'ICA2',
                'hours_per_week' => 4,
                'total_semester_hours' => 72.00,
                'schedules' => [
                    // Two slots on the same day: Monday morning and Monday afternoon
                    ['day_of_week' => 'Mon', 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'room' => 'F-201'],
                    ['day_of_week' => 'Mon', 'start_time' => '14:00:00', 'end_time' => '17:00:00', 'room' => 'CL-4'],
                ],
                'students' => [
                    '2023-00101', // John Depp
                    '2024-00202', // Steven Strange
                    '2024-00301', // Mina Akbar
                    '2024-00302', // Oliver Albe
                    '2023-1005',  // Christian Garcia
                    '2023-1006',  // Sophia Aquino
                ],
            ],
        ];

        foreach ($subjectsData as $sData) {
            $course = Course::firstOrCreate(
                [
                    'faculty_id' => $faculty->faculty_id,
                    'subject_code' => $sData['subject_code'],
                    'section' => $sData['section'],
                ],
                [
                    'school_year_id' => $schoolYear->school_year_id,
                    'course_name' => $sData['course_name'],
                    'hours_per_week' => $sData['hours_per_week'],
                    'total_semester_hours' => $sData['total_semester_hours'],
                ]
            );

            // Create schedules if missing (keyed by day + start_time to allow multiple slots per day)
            foreach ($sData['schedules'] as $sch) {
                CourseSchedule::firstOrCreate(
                    [
                        'course_id' => $course->course_id,
                        'day_of_week' => $sch['day_of_week'],
                        'start_time' => $sch['start_time'],
                    ],
                    [
                        'end_time' => $sch['end_time'],
                        'room' => $sch['room'],
                    ]
                );
            }

            // Enroll the students in this course
            $enrollments = [];
            foreach ($sData['students'] as $sNum) {
                if (isset($studentsByNumber[$sNum])) {
                    $student = $studentsByNumber[$sNum];
                    $enr = Enrollment::firstOrCreate(
                        [
                            'student_id' => $student->student_id,
                            'course_id' => $course->course_id,
                        ],
                        [
                            'status' => 'enrolled',
                            'cycle_number' => 1,
                        ]
                    );
                    $enrollments[] = $enr;
                }
            }

            // Seed sample past dates matching the course's schedules
            $firstScheduleDay = $sData['schedules'][0]['day_of_week'];
            $targetDayIndex = match ($firstScheduleDay) {
                'Mon' => 1,
                'Tue' => 2,
                'Wed' => 3,
                'Thu' => 4,
                'Fri' => 5,
                default => 1,
            };

            $pastDates = [
                Carbon::now()->subWeeks(3)->startOfWeek()->addDays($targetDayIndex - 1)->toDateString(),
                Carbon::now()->subWeeks(2)->startOfWeek()->addDays($targetDayIndex - 1)->toDateString(),
                Carbon::now()->subWeeks(1)->startOfWeek()->addDays($targetDayIndex - 1)->toDateString(),
            ];

            foreach ($pastDates as $dateIdx => $pastDate) {
                foreach ($enrollments as $enrIdx => $enr) {
                    $status = 'present';
                    $hoursAbsent = null;
                    $remarks = null;

                    // A few realistic sample statuses
                    if ($enrIdx % 5 === 1 && $dateIdx === 1) {
                        $status = 'late';
                        $remarks = '15 minutes late';
                    } elseif ($enrIdx % 6 === 2 && $dateIdx === 0) {
                        $status = 'absent';
                        $hoursAbsent = 2.0;
                        $remarks = 'Personal emergency';
                    } elseif ($enrIdx % 7 === 3 && $dateIdx === 2) {
                        $status = 'excused';
                        $remarks = 'University student athlete event';
                    }

                    AttendanceRecord::firstOrCreate(
                        [
                            'enrollment_id' => $enr->enrollment_id,
                            'session_date' => $pastDate,
                        ],
                        [
                            'recorded_by' => $faculty->faculty_id,
                            'status' => $status,
                            'grading_period' => 'first_grading',
                            'hours_absent' => $hoursAbsent,
                            'remarks' => $remarks,
                            'is_out_of_schedule' => false,
                        ]
                    );
                }
            }
        }
    }
}
