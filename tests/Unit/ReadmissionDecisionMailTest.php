<?php

namespace Tests\Unit;

use App\Mail\ReadmissionDecisionMail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReadmissionDecisionMailTest extends TestCase
{
    public static function decisionConclusions(): array
    {
        return [
            'approved' => ['approved', 'The student has been readmitted to this course.'],
            'excused' => ['excused', '2.50 absence hours were deducted.'],
            'not excused' => ['not_excused', 'The request was not excused.'],
            'rejected' => ['rejected', 'The request was rejected.'],
        ];
    }

    #[DataProvider('decisionConclusions')]
    public function test_email_renders_the_selected_decision_conclusion(string $outcome, string $expectedText): void
    {
        $readmission = (object) [
            'first_name' => 'Alex',
            'last_name' => 'Student',
            'subject_code' => 'CS 101',
            'course_name' => 'Introduction to Computing',
            'faculty_name' => 'Professor Example',
            'excused_from' => '2026-10-05',
            'excused_through' => '2026-10-06',
            'excused_hours_deducted' => 2.5,
            'rejection_reason' => 'Please provide the requested information.',
        ];

        (new ReadmissionDecisionMail($readmission, $outcome))
            ->assertSeeInText($expectedText);
    }
}
