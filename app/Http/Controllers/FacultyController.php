<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class FacultyController extends Controller
{
    public function dashboard(): View
    {
        return view('dashboard.faculty');
    }
}
