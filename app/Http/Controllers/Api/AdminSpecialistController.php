<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use Illuminate\Http\Request;

class AdminSpecialistController extends Controller
{
    public function index(Request $request)
    {
        $query = Specialist::with('user');
        if ($city = $request->query('city')) {
            $query->where('clinic_city', $city);
        }
        if ($spec = $request->query('specialization')) {
            $query->where('primary_specialization', $spec);
        }
        $items = $query->orderBy('id', 'desc')->paginate(20);

        return response()->json($items);
    }

    public function show($id)
    {
        $item = Specialist::with('user')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = Specialist::findOrFail($id);
        $data = $request->validate([
            'whatsapp_number' => 'nullable|string|max:20',
            'primary_specialization' => 'nullable|string|max:100',
            'medical_council_registration_no' => 'nullable|string|max:100',
            'medical_council_name' => 'nullable|string|max:150',
            'clinic_name' => 'nullable|string|max:190',
            'clinic_address' => 'nullable|string|max:255',
            'clinic_city' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'education_primary' => 'nullable|array',
            'education_postgrad' => 'nullable|array',
            'additional_qualifications' => 'nullable|array',
        ]);
        $item->fill($data);
        $item->save();

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = Specialist::findOrFail($id);
        $item->delete();

        return response()->noContent();
    }
}
