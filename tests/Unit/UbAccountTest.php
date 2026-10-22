<?php

namespace Tests\Unit;

use App\Support\UbAccount;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UbAccountTest extends TestCase
{
    public static function accountEmails(): array
    {
        return [
            'student account' => [' 20254611@S.UBAGUIO.EDU ', ['role' => 'student', 'student_number' => '20254611']],
            'faculty account' => ['Faculty.Name@E.UBAGUIO.EDU', ['role' => 'faculty', 'student_number' => null]],
            'OSA account' => ['OSA@E.UBAGUIO.EDU', ['role' => 'osa_staff', 'student_number' => null]],
            'lookalike domain' => ['20254611@s.ubaguio.edu.evil.com', null],
            'non-student ID in student domain' => ['osa@s.ubaguio.edu', null],
            'seven digit student ID' => ['1234567@s.ubaguio.edu', null],
            'nine digit student ID' => ['123456789@s.ubaguio.edu', null],
            'other domain' => ['person@gmail.com', null],
            'empty email' => ['', null],
        ];
    }

    #[DataProvider('accountEmails')]
    public function test_resolves_only_configured_university_account_patterns(string $email, ?array $expected): void
    {
        $this->assertSame($expected, UbAccount::resolve($email));
    }
}
