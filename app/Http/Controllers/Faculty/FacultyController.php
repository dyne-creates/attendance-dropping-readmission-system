<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class FacultyController extends Controller
{
    public function dashboard(): View
    {
        return view('faculty.dashboard');
    }
}
