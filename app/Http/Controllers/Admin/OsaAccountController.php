<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OsaStaff;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OsaAccountController extends Controller
{
    public function index(): View
    {
        return view('admin.osa-accounts', [
            'accounts' => User::query()
                ->where('role', 'osa_staff')
                ->with('osaStaff')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $account = DB::transaction(function () use ($validated): User {
            $account = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'password' => $validated['password'],
                'role' => 'osa_staff',
                'email_verified_at' => now(),
            ]);

            OsaStaff::create(['user_id' => $account->user_id]);

            return $account;
        });
        $auditLogger->record('admin.osa_account.created', 'user', (int) $account->getKey());

        return redirect()
            ->route('admin.osa-accounts.index')
            ->with('success', 'OSA account created. The staff member can now sign in.');
    }

    public function update(Request $request, User $account, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($account->role === 'osa_staff', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($account->user_id, 'user_id'),
            ],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        $account->update([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            ...($validated['password'] ? ['password' => $validated['password']] : []),
        ]);
        $account->osaStaff()->firstOrCreate();
        $auditLogger->record('admin.osa_account.updated', 'user', (int) $account->getKey());

        return redirect()
            ->route('admin.osa-accounts.index')
            ->with('success', 'OSA account updated.');
    }
}
