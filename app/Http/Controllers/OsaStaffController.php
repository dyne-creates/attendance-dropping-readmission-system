<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OsaStaffController extends Controller
{
    public function dashboard(): View
    {
        return view('dashboard.osa');
    }
}
