<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gp;

class GpController extends Controller
{
    public function index()
    {
        $gps = Gp::with('user')->orderBy('id', 'desc')->paginate(20);

        return view('admin.gps', compact('gps'));
    }
}
