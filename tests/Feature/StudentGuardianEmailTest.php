<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentGuardianEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_a_guardian_email_after_policy_acceptance(): void
    {
        [$user] = $this->createStudent();

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertSee('Attendance and readmission policy')
            ->assertDontSee('Add a parent or guardian email');

        $user->forceFill(['policy_accepted_at' => now()])->save();

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertSee('Add a parent or guardian email')
            ->assertSee(route('student.guardian-email.store'), false)
            ->assertSee('confirm your relationship.');
    }

    public function test_student_can_save_guardian_email_without_inventing_other_details(): void
    {
        [$user, $student] = $this->createStudent();

        $this->actingAs($user)
            ->post(route('student.guardian-email.store'), ['email' => 'guardian@example.test'])
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('guardians', [
            'student_id' => $student->student_id,
            'full_name' => null,
            'relationship' => null,
            'email' => 'guardian@example.test',
            'phone_number' => null,
            'is_verified' => false,
            'verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee('guardian@example.test');
    }

    public function test_student_can_add_email_to_an_existing_guardian_without_overwriting_other_details(): void
    {
        [$user, $student] = $this->createStudent();
        Guardian::query()->create([
            'student_id' => $student->student_id,
            'full_name' => 'Parent Example',
            'relationship' => 'Parent',
            'email' => null,
            'phone_number' => '09171234567',
        ]);

        $this->actingAs($user)
            ->post(route('student.guardian-email.store'), ['email' => 'guardian@example.test'])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('guardians', [
            'student_id' => $student->student_id,
            'full_name' => 'Parent Example',
            'relationship' => 'Parent',
            'email' => 'guardian@example.test',
            'phone_number' => '09171234567',
            'is_verified' => false,
        ]);
        $this->assertDatabaseCount('guardians', 1);
    }

    public function test_invalid_guardian_email_is_rejected_without_creating_a_guardian_record(): void
    {
        [$user] = $this->createStudent();

        $this->actingAs($user)
            ->from(route('student.dashboard'))
            ->post(route('student.guardian-email.store'), ['email' => 'not-an-email'])
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('guardians', 0);
    }

    public function test_student_routes_redirect_until_a_guardian_email_is_on_file(): void
    {
        [$user] = $this->createStudent();
        $user->forceFill(['policy_accepted_at' => now()])->save();

        $this->actingAs($user)
            ->get(route('student.mail'))
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('info');
    }

    public function test_non_student_cannot_save_guardian_email(): void
    {
        $user = User::factory()->faculty()->create();

        $this->actingAs($user)
            ->post(route('student.guardian-email.store'), ['email' => 'guardian@example.test'])
            ->assertRedirect(route('faculty.dashboard'));

        $this->assertDatabaseCount('guardians', 0);
    }

    public function test_existing_guardian_email_cannot_be_replaced_by_onboarding_request(): void
    {
        [$user, $student] = $this->createStudent();
        Guardian::query()->create([
            'student_id' => $student->student_id,
            'full_name' => 'Parent Example',
            'relationship' => 'Parent',
            'email' => 'original@example.test',
            'phone_number' => '09171234567',
            'is_verified' => true,
            'verification_method' => 'school',
            'verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('student.guardian-email.store'), ['email' => 'replacement@example.test'])
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('info');

        $this->assertDatabaseHas('guardians', [
            'student_id' => $student->student_id,
            'email' => 'original@example.test',
            'is_verified' => true,
            'verification_method' => 'school',
        ]);
        $this->assertDatabaseMissing('guardians', ['email' => 'replacement@example.test']);
    }

    /** @return array{User, Student} */
    private function createStudent(): array
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email' => fake()->unique()->numerify('2026####').'@s.ubaguio.edu',
        ]);
        $student = Student::query()->create([
            'user_id' => $user->user_id,
            'student_number' => fake()->unique()->numerify('2026####'),
            'first_name' => 'Student',
            'last_name' => 'Example',
        ]);

        return [$user, $student];
    }
}
