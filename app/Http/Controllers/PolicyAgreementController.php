<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PolicyAgreementController
{
    public function accept(Request $request): RedirectResponse
    {
        $request->validate([
            'accepted' => ['required', 'accepted'],
        ]);

        $request->user()->forceFill([
            'policy_accepted_at' => now(),
        ])->save();

        return redirect()->route('dashboard');
    }
}
