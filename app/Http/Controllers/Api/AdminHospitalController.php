<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use Illuminate\Http\Request;

class AdminHospitalController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'hospital_type' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'micro_area' => 'nullable|string|max:150',
            'city' => 'required|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:190',
            'admin_name' => 'nullable|string|max:190',
            'admin_designation' => 'nullable|string|max:190',
            'admin_mobile' => 'nullable|string|max:20',
            'admin_email' => 'nullable|email|max:190',
            'status' => 'nullable|string|max:50',
        ]);

        $item = Hospital::create($data);

        return response()->json($item, 201);
    }

    public function index(Request $request)
    {
        $query = Hospital::query();
        if ($city = $request->query('city')) {
            $query->where('city', $city);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        $items = $query->orderBy('id', 'desc')->paginate(20);

        return response()->json($items);
    }

    public function show($id)
    {
        $item = Hospital::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = Hospital::findOrFail($id);
        $data = $request->validate([
            'name' => 'nullable|string|max:190',
            'hospital_type' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'micro_area' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:190',
            'admin_name' => 'nullable|string|max:190',
            'admin_designation' => 'nullable|string|max:190',
            'admin_mobile' => 'nullable|string|max:20',
            'admin_email' => 'nullable|email|max:190',
            'status' => 'nullable|string|max:50',
        ]);
        $item->fill($data);
        $item->save();

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = Hospital::findOrFail($id);
        $item->delete();

        return response()->noContent();
    }
}
