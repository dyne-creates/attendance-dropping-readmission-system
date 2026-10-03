<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_database_seeder_does_not_create_demo_login_or_attendance_data(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('faculty_members', 0);
        $this->assertDatabaseCount('school_years', 0);
        $this->assertDatabaseCount('courses', 0);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('attendance_records', 0);
    }
}
