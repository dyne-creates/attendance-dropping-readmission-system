<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $student = $request->user()->student;
        $guardians = $student?->guardians()->orderBy('guardian_id')->get() ?? collect();
        $guardian = $guardians->first(
            fn (Guardian $guardian): bool => filled($guardian->email)
        ) ?? $guardians->first();

        return view('profile.edit', [
            'user' => $request->user(),
            'guardian' => $guardian,
        ]);
    }
}
