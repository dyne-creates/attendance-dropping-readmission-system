<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class StudentController extends Controller
{
    public function dashboard(): View
    {
        return view('dashboard.student');
    }
}
