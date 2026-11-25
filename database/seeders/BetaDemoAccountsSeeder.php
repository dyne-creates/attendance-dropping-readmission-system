<?php

namespace Database\Seeders;

use App\Models\FacultyMember;
use App\Models\OsaStaff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class BetaDemoAccountsSeeder extends Seeder
{
    /**
     * Create the explicitly configured beta staff accounts without touching application data.
     */
    public function run(): void
    {
        if (! config('ub.beta_password_login_enabled')) {
            return;
        }

        $accounts = config('ub.beta_password_accounts', []);
        $this->validateAccounts($accounts);

        foreach ($accounts as $account) {
            $existingUser = User::query()->where('email', $account['email'])->first();

            if ($existingUser !== null && ($existingUser->role !== $account['role'] || $existingUser->google_id !== null)) {
                throw new RuntimeException('A configured beta account email is already assigned to a non-demo account.');
            }

            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'username' => $account['username'],
                    'role' => $account['role'],
                    'password' => Hash::make($account['password']),
                ]
            );

            if ($account['role'] === 'faculty') {
                FacultyMember::updateOrCreate(
                    ['user_id' => $user->user_id],
                    [
                        'full_name' => $account['name'],
                        'department' => 'Beta Testing',
                    ]
                );
            } else {
                OsaStaff::firstOrCreate(['user_id' => $user->user_id]);
            }
        }
    }

    /**
     * @param  array<int, array{username: string, email: string, name: string, password: string, role: string}>  $accounts
     */
    private function validateAccounts(array $accounts): void
    {
        $usernames = [];
        $emails = [];

        foreach ($accounts as $account) {
            if (
                ! preg_match('/\A[a-z0-9][a-z0-9._-]{2,63}\z/', $account['username'])
                || ! filter_var($account['email'], FILTER_VALIDATE_EMAIL)
                || strlen($account['password']) < 12
                || ! in_array($account['role'], ['faculty', 'osa_staff'], true)
                || trim($account['name']) === ''
            ) {
                throw new RuntimeException('Beta login is enabled, but its account configuration is invalid. Check usernames, emails, names, roles, and passwords of at least 12 characters.');
            }

            if (in_array($account['username'], $usernames, true) || in_array($account['email'], $emails, true)) {
                throw new RuntimeException('Beta account usernames and emails must be unique.');
            }

            $usernames[] = $account['username'];
            $emails[] = $account['email'];
        }
    }
}
