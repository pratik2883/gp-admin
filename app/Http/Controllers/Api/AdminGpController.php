<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gp;
use Illuminate\Http\Request;

class AdminGpController extends Controller
{
    public function index(Request $request)
    {
        $query = Gp::with('user');
        if ($city = $request->query('city')) {
            $query->where('city', $city);
        }
        $items = $query->orderBy('id', 'desc')->paginate(20);

        return response()->json($items);
    }

    public function show($id)
    {
        $item = Gp::with('user')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = Gp::findOrFail($id);
        $data = $request->validate([
            'clinic_name' => 'nullable|string|max:190',
            'address_line' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'preferred_specialist_id' => 'nullable|exists:specialists,id',
            'preferred_location' => 'nullable|string|max:100',
        ]);
        $item->fill($data);
        $item->save();

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = Gp::findOrFail($id);
        $item->delete();

        return response()->noContent();
    }
}
