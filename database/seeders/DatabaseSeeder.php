<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\DroppingTransaction;
use App\Models\Enrollment;
use App\Models\ExcuseDocument;
use App\Models\FacultyMember;
use App\Models\Notification;
use App\Models\OsaStaff;
use App\Models\ReadmissionRequest;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Clear records of all courses and dependent records
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Notification::truncate();
            ExcuseDocument::truncate();
            ReadmissionRequest::truncate();
            DroppingTransaction::truncate();
            AttendanceRecord::truncate();
            Enrollment::truncate();
            CourseSchedule::truncate();
            Course::truncate();
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Notification::truncate();
            ExcuseDocument::truncate();
            ReadmissionRequest::truncate();
            DroppingTransaction::truncate();
            AttendanceRecord::truncate();
            Enrollment::truncate();
            CourseSchedule::truncate();
            Course::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // 2. School Year
        $schoolYear = SchoolYear::firstOrCreate(
            [
                'year_label' => '2025-2026',
                'semester' => '1st Semester',
            ],
            [
                'start_date' => '2025-08-11',
                'end_date' => '2025-12-18',
                'is_archived' => false,
            ]
        );

        // 3. Faculty Users & Profiles
        // Standard faculty account
        $facultyUser1 = User::firstOrCreate(
            ['email' => 'faculty@e.ubaguio.edu'],
            [
                'name' => 'Prof. Divine Santos',
                'role' => 'faculty',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $faculty1 = FacultyMember::firstOrCreate(
            ['user_id' => $facultyUser1->user_id],
            [
                'full_name' => 'Prof. Divine Santos',
                'department' => 'School of Information Technology',
                'suppress_schedule_warnings' => false,
            ]
        );

        // Also ensure any existing test faculty user is set up with a FacultyMember profile
        $facultyMembers = [$faculty1];
        $testUser = User::where('role', 'faculty')->where('email', 'test@e.ubaguio.edu')->first();
        if ($testUser) {
            $faculty2 = FacultyMember::firstOrCreate(
                ['user_id' => $testUser->user_id],
                [
                    'full_name' => $testUser->name,
                    'department' => 'School of Information Technology',
                    'suppress_schedule_warnings' => false,
                ]
            );
            $facultyMembers[] = $faculty2;
        }

        // 4. OSA Staff User & Profile
        $osaUser = User::firstOrCreate(
            ['email' => 'osa@e.ubaguio.edu'],
            [
                'name' => 'OSA Officer',
                'role' => 'osa_staff',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        OsaStaff::firstOrCreate(['user_id' => $osaUser->user_id]);

        // 5. Add Course: AGLDEV1 (Agile Development)
        // We create AGLDEV1 for all faculty members so whoever logs in sees this course
        $createdCourses = [];
        foreach ($facultyMembers as $idx => $fac) {
            $agileCourse = Course::create([
                'faculty_id' => $fac->faculty_id,
                'school_year_id' => $schoolYear->school_year_id,
                'subject_code' => 'AGLDEV1',
                'course_name' => 'Agile Development',
                'section' => 'IDB1-SD',
                'hours_per_week' => 5,
                'total_semester_hours' => 90.00,
            ]);

            CourseSchedule::create([
                'course_id' => $agileCourse->course_id,
                'day_of_week' => 'Mon',
                'start_time' => '14:00:00',
                'end_time' => '17:00:00',
                'room' => 'F-402',
            ]);

            CourseSchedule::create([
                'course_id' => $agileCourse->course_id,
                'day_of_week' => 'Fri',
                'start_time' => '14:00:00',
                'end_time' => '16:00:00',
                'room' => 'CL-3',
            ]);

            $createdCourses[] = $agileCourse;
        }

        // 6. 8 Test Students
        $studentData = [
            ['student_number' => '2023-1001', 'last_name' => 'dela Cruz', 'first_name' => 'Juan', 'honorifics' => 'Mr.', 'program' => 'BSIT', 'year_level' => 3],
            ['student_number' => '2023-1002', 'last_name' => 'Santos', 'first_name' => 'Maria Clara', 'honorifics' => 'Ms.', 'program' => 'BSIT', 'year_level' => 3],
            ['student_number' => '2023-1003', 'last_name' => 'Reyes', 'first_name' => 'Gabriel', 'honorifics' => 'Mr.', 'program' => 'BSIT', 'year_level' => 3],
            ['student_number' => '2023-1004', 'last_name' => 'Bautista', 'first_name' => 'Beatrice', 'honorifics' => 'Ms.', 'program' => 'BSCS', 'year_level' => 3],
            ['student_number' => '2023-1005', 'last_name' => 'Garcia', 'first_name' => 'Christian', 'honorifics' => 'Mr.', 'program' => 'BSIT', 'year_level' => 3],
            ['student_number' => '2023-1006', 'last_name' => 'Aquino', 'first_name' => 'Sophia', 'honorifics' => 'Ms.', 'program' => 'BSIT', 'year_level' => 3],
            ['student_number' => '2023-1007', 'last_name' => 'Mendoza', 'first_name' => 'Angelo', 'honorifics' => 'Mr.', 'program' => 'BSCS', 'year_level' => 3],
            ['student_number' => '2023-1008', 'last_name' => 'Navarro', 'first_name' => 'Camille', 'honorifics' => 'Ms.', 'program' => 'BSIT', 'year_level' => 3],
        ];

        $students = [];
        foreach ($studentData as $i => $data) {
            $email = 'student'.($i + 1).'@s.ubaguio.edu';
            $user = User::firstOrCreate(
                ['email' => $email],
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
                    'honorifics' => $data['honorifics'],
                    'department' => 'School of Information Technology',
                    'program' => $data['program'],
                    'year_level' => $data['year_level'],
                ]
            );

            $students[] = $student;
        }

        // Enroll the 8 students in all created AGLDEV1 courses
        foreach ($createdCourses as $course) {
            $enrollments = [];
            foreach ($students as $student) {
                $enrollments[] = Enrollment::create([
                    'student_id' => $student->student_id,
                    'course_id' => $course->course_id,
                    'status' => 'enrolled',
                    'cycle_number' => 1,
                ]);
            }

            // 7. Seed Past Attendance for AGLDEV1 matching Monday & Friday schedule
            $pastDates = [
                Carbon::now()->subWeeks(3)->startOfWeek()->toDateString(), // 3 weeks ago Mon (3 hrs)
                Carbon::now()->subWeeks(3)->startOfWeek()->addDays(4)->toDateString(), // 3 weeks ago Fri (2 hrs)
                Carbon::now()->subWeeks(2)->startOfWeek()->toDateString(), // 2 weeks ago Mon (3 hrs)
                Carbon::now()->subWeeks(2)->startOfWeek()->addDays(4)->toDateString(), // 2 weeks ago Fri (2 hrs)
                Carbon::now()->subWeeks(1)->startOfWeek()->toDateString(), // 1 week ago Mon (3 hrs)
                Carbon::now()->subWeeks(1)->startOfWeek()->addDays(4)->toDateString(), // 1 week ago Fri (2 hrs)
            ];

            foreach ($pastDates as $date) {
                foreach ($enrollments as $idx => $enr) {
                    $status = 'present';
                    $remarks = null;

                    // Christian Garcia (student 5) has 5 absences (9 hrs Mon + 4 hrs Fri = 13 hrs absent / 18 hrs limit => 72.2% At Risk)
                    if ($idx === 4 && in_array($date, [$pastDates[0], $pastDates[1], $pastDates[2], $pastDates[3], $pastDates[4]])) {
                        $status = 'absent';
                        $remarks = 'Unexcused absence';
                    } elseif ($idx === 2 && $date === $pastDates[1]) {
                        // Gabriel Reyes (student 3) has 1 late
                        $status = 'late';
                        $remarks = '15 minutes tardy';
                    } elseif ($idx === 6 && $date === $pastDates[2]) {
                        // Angelo Mendoza (student 7) has 1 excused absence
                        $status = 'absent';
                        $remarks = 'Sick leave - excused';
                    }

                    AttendanceRecord::create([
                        'enrollment_id' => $enr->enrollment_id,
                        'recorded_by' => $course->faculty_id,
                        'session_date' => $date,
                        'status' => $status,
                        'remarks' => $remarks,
                        'is_out_of_schedule' => false,
                    ]);
                }
            }
        }
    }
}
