<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;

class HospitalController extends Controller
{
    public function index()
    {
        $hospitals = Hospital::orderBy('id', 'desc')->paginate(20);

        return view('admin.hospitals', compact('hospitals'));
    }
}
