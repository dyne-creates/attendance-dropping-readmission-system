<?php

namespace App\Http\Controllers\OsaStaff;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OsaStaffController extends Controller
{
    public function dashboard(): View
    {
        return view('osa-staff.dashboard');
    }
}
