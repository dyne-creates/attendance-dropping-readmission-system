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
    'beta_password_login_enabled' => env('BETA_PASSWORD_LOGIN_ENABLED', false),
    'beta_password_accounts' => [
        [
            'username' => strtolower(trim((string) env('BETA_FACULTY_USERNAME', 'faculty-beta'))),
            'email' => strtolower(trim((string) env('BETA_FACULTY_EMAIL', 'faculty.beta@example.test'))),
            'name' => env('BETA_FACULTY_NAME', 'Beta Faculty'),
            'password' => (string) env('BETA_FACULTY_PASSWORD', ''),
            'role' => 'faculty',
        ],
        [
            'username' => strtolower(trim((string) env('BETA_OSA_USERNAME', 'osa-beta'))),
            'email' => strtolower(trim((string) env('BETA_OSA_EMAIL', 'osa.beta@example.test'))),
            'name' => env('BETA_OSA_NAME', 'Beta OSA Staff'),
            'password' => (string) env('BETA_OSA_PASSWORD', ''),
            'role' => 'osa_staff',
        ],
    ],
    'student_number_pattern' => env('UB_STUDENT_NUMBER_PATTERN', '/^\d{8}$/'),
];
