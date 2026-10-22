<?php

return [
    'student_domain' => env('UB_STUDENT_DOMAIN', 's.ubaguio.edu'),
    'employee_domain' => env('UB_EMPLOYEE_DOMAIN', 'e.ubaguio.edu'),
    'osa_account_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('OSA_ACCOUNT_EMAILS', 'osa@e.ubaguio.edu'))
    ))),
    'dummy_password_accounts' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('UB_DUMMY_PASSWORD_EMAILS', 'faculty@e.ubaguio.edu,osa@e.ubaguio.edu'))
    ))),
    'student_number_pattern' => env('UB_STUDENT_NUMBER_PATTERN', '/^\d{8}$/'),
];
