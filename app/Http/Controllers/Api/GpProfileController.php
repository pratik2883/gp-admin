<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class GpProfileController extends Controller
{
    // GET /api/gp/profile
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $gp = Gp::firstOrCreate(
            ['user_id' => $user->id],
            [] // default empty profile
        );

        $gp->loadMissing(['defaultLocation']);

        $profile = [
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'registration_number' => $gp->registration_number,
            'registration_type' => $gp->registration_type,
            'registration_council' => $gp->registration_council,
            'registration_valid_until' => optional($gp->registration_valid_until)->toDateString(),
            'designation' => $gp->designation,
            'address' => $gp->address_line,
            'city' => $gp->city,
            'clinic_name' => $gp->clinic_name,
            'default_location_id' => $gp->default_location_id,
            'default_location_name' => $gp->defaultLocation?->name,
        ];

        return response()->json([
            'profile' => $profile,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'gp' => $gp,
        ]);
    }

    // POST/PUT /api/gp/profile
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'nullable|string|max:190',
            'mobile' => 'nullable|string|max:20',
            'clinic_name' => 'nullable|string|max:190',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'default_location_id' => 'nullable|exists:locations,id',
            'preferred_location' => 'nullable|string|max:100',
            'preferred_specialist_id' => 'nullable|exists:specialists,id',
        ]);

        $gp = Gp::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        if (array_key_exists('name', $data) && $data['name'] !== null) {
            $user->name = $data['name'];
        }
        if (array_key_exists('mobile', $data) && $data['mobile'] !== null) {
            $user->mobile = $data['mobile'];
        }
        $user->save();

        if (array_key_exists('address', $data)) {
            $data['address_line'] = $data['address'];
            unset($data['address']);
        }

        $gp->fill($data);
        $gp->save();

        $gp->loadMissing(['defaultLocation']);

        $profile = [
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'registration_number' => $gp->registration_number,
            'registration_type' => $gp->registration_type,
            'registration_council' => $gp->registration_council,
            'registration_valid_until' => optional($gp->registration_valid_until)->toDateString(),
            'designation' => $gp->designation,
            'address' => $gp->address_line,
            'city' => $gp->city,
            'clinic_name' => $gp->clinic_name,
            'default_location_id' => $gp->default_location_id,
            'default_location_name' => $gp->defaultLocation?->name,
        ];

        return response()->json([
            'message' => 'GP profile updated',
            'profile' => $profile,
            'gp' => $gp,
        ]);
    }

    // POST /api/gp/profile/change-password
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors' => [
                    'current_password' => ['The current password is incorrect.'],
                ],
            ], 422);
        }

        $user->password = $data['new_password'];
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
