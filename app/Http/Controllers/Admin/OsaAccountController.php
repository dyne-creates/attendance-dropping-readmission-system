<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OsaStaff;
use App\Models\User;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        DB::transaction(function () use ($validated): void {
            $account = User::create([
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'password' => $validated['password'],
                'role' => 'osa_staff',
                'email_verified_at' => now(),
            ]);

            OsaStaff::create(['user_id' => $account->user_id]);
        });

        return redirect()
            ->route('admin.osa-accounts.index')
            ->with('success', 'OSA account created. The staff member can now sign in.');
    }

    public function update(Request $request, User $account): RedirectResponse
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
            ...(isset($validated['password']) ? ['password' => $validated['password']] : []),
        ]);
        $account->osaStaff()->firstOrCreate();

        return redirect()
            ->route('admin.osa-accounts.index')
            ->with('success', 'OSA account updated.');
    }
}
