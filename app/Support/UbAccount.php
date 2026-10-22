<?php

namespace App\Support;

use Illuminate\Support\Str;

final class UbAccount
{
    /** @return array{role: 'student', student_number: string}|array{role: 'faculty'|'osa_staff', student_number: null}|null */
    public static function resolve(string $email): ?array
    {
        $email = Str::lower(trim($email));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        [$localPart, $domain] = explode('@', $email, 2);

        if ($domain === config('ub.student_domain')) {
            if (preg_match((string) config('ub.student_number_pattern'), $localPart) !== 1) {
                return null;
            }

            return [
                'role' => 'student',
                'student_number' => $localPart,
            ];
        }

        if ($domain !== config('ub.employee_domain')) {
            return null;
        }

        $osaEmails = array_map(
            static fn (string $osaEmail): string => Str::lower(trim($osaEmail)),
            config('ub.osa_account_emails', [])
        );

        return [
            'role' => in_array($email, $osaEmails, true) ? 'osa_staff' : 'faculty',
            'student_number' => null,
        ];
    }
}
