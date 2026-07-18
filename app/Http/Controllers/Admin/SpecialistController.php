<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialist;

class SpecialistController extends Controller
{
    public function index()
    {
        $specialists = Specialist::with('user')->orderBy('id', 'desc')->paginate(20);

        return view('admin.specialists', compact('specialists'));
    }
}
