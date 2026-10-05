<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticCenter;
use App\Models\Hospital;
use App\Models\Specialist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $user = $request->user();
        $query = trim((string) $request->query('q', ''));
        $typeFilter = strtolower(trim((string) $request->query('type', 'all')));

        // Determine allowed entity types based on logged-in user role & subtype
        $allowedTypes = [];

        if ($user->role === 'gp') {
            $allowedTypes = ['specialist', 'hospital', 'diagnostic_center'];
        } elseif ($user->role === 'specialist') {
            if ($user->role_subtype === 'hospital') {
                $allowedTypes = ['specialist', 'diagnostic_center'];
            } elseif ($user->role_subtype === 'diagnostic_center') {
                $allowedTypes = ['specialist', 'hospital'];
            } else {
                $allowedTypes = ['hospital', 'diagnostic_center'];
            }
        } else {
            // Default fallback if role is admin or unknown
            $allowedTypes = ['specialist', 'hospital', 'diagnostic_center'];
        }

        $specialists = [];
        $hospitals = [];
        $diagnosticCenters = [];

        // If specific type filter requested, check if allowed
        $searchSpecialists = in_array('specialist', $allowedTypes, true) && ($typeFilter === 'all' || $typeFilter === 'specialist');
        $searchHospitals = in_array('hospital', $allowedTypes, true) && ($typeFilter === 'all' || $typeFilter === 'hospital');
        $searchDiagnostic = in_array('diagnostic_center', $allowedTypes, true) && ($typeFilter === 'all' || $typeFilter === 'diagnostic_center' || $typeFilter === 'diagnostic');

        if ($query !== '') {
            if ($searchSpecialists) {
                $spQuery = Specialist::query()
                    ->with(['user', 'location', 'specialty', 'hospitals'])
                    ->where('is_active', true)
                    ->where(function ($q) use ($query) {
                        $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$query.'%'))
                            ->orWhere('primary_specialization', 'like', '%'.$query.'%')
                            ->orWhere('clinic_name', 'like', '%'.$query.'%')
                            ->orWhere('hospital_name', 'like', '%'.$query.'%')
                            ->orWhere('clinic_address', 'like', '%'.$query.'%')
                            ->orWhereHas('specialty', fn ($s) => $s->where('name', 'like', '%'.$query.'%')->orWhere('plain_label', 'like', '%'.$query.'%'))
                            ->orWhereHas('location', fn ($l) => $l->where('name', 'like', '%'.$query.'%'));
                    });

                $specialists = $spQuery->limit(20)->get()->map(function (Specialist $sp) {
                    $primaryHospital = $sp->hospitals->first();
                    $specialtyLabel = $sp->specialty?->plain_label ?: ($sp->specialty?->name ?: $sp->primary_specialization);

                    return [
                        'id' => $sp->id,
                        'name' => $sp->user?->name ?? 'Specialist',
                        'speciality' => $specialtyLabel,
                        'area_name' => $sp->location?->name,
                        'hospital_name' => $sp->hospital_name ?: $primaryHospital?->name,
                        'clinic_address' => $sp->clinic_address,
                        'clinic_timings' => $sp->clinic_timings,
                        'hospital_visiting_hours' => $sp->hospital_visiting_hours,
                        'show_mobile_number' => (bool) ($sp->show_mobile_number ?? true),
                        'show_whatsapp_number' => (bool) ($sp->show_whatsapp_number ?? true),
                        'mobile' => ($sp->show_mobile_number ?? true) ? $sp->user?->mobile : null,
                        'whatsapp_number' => ($sp->show_whatsapp_number ?? true) ? $sp->whatsapp_number : null,
                        'years_of_experience' => $sp->years_of_experience,
                        'is_premium' => (bool) $sp->is_premium,
                        'photo_url' => $sp->profile_photo_path ? (is_file(public_path($sp->profile_photo_path)) ? url($sp->profile_photo_path) : Storage::disk('public')->url($sp->profile_photo_path)) : null,
                        'profile_photo' => $sp->profile_photo_path ? (is_file(public_path($sp->profile_photo_path)) ? url($sp->profile_photo_path) : Storage::disk('public')->url($sp->profile_photo_path)) : null,
                        'entity_type' => 'specialist',
                    ];
                })->values()->toArray();
            }

            if ($searchHospitals) {
                $hospQuery = Hospital::query()
                    ->with(['location'])
                    ->where('status', 'active')
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', '%'.$query.'%')
                            ->orWhere('hospital_type', 'like', '%'.$query.'%')
                            ->orWhere('address', 'like', '%'.$query.'%')
                            ->orWhere('micro_area', 'like', '%'.$query.'%')
                            ->orWhere('city', 'like', '%'.$query.'%')
                            ->orWhereHas('location', fn ($l) => $l->where('name', 'like', '%'.$query.'%'));
                    });

                $hospitals = $hospQuery->limit(20)->get()->map(function (Hospital $h) {
                    return [
                        'id' => $h->id,
                        'name' => $h->name,
                        'hospital_type' => $h->hospital_type,
                        'address' => $h->address ?: ($h->micro_area ? $h->micro_area.', '.$h->city : $h->city),
                        'area_name' => $h->location?->name ?? $h->city,
                        'contact_number' => $h->contact_number ?: $h->admin_mobile,
                        'entity_type' => 'hospital',
                    ];
                })->values()->toArray();
            }

            if ($searchDiagnostic) {
                $dxQuery = DiagnosticCenter::query()
                    ->with(['location', 'services'])
                    ->where('status', 'active')
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', '%'.$query.'%')
                            ->orWhere('center_type', 'like', '%'.$query.'%')
                            ->orWhere('address', 'like', '%'.$query.'%')
                            ->orWhere('micro_area', 'like', '%'.$query.'%')
                            ->orWhereHas('services', fn ($s) => $s->where('name', 'like', '%'.$query.'%'))
                            ->orWhereHas('location', fn ($l) => $l->where('name', 'like', '%'.$query.'%'));
                    });

                $diagnosticCenters = $dxQuery->limit(20)->get()->map(function (DiagnosticCenter $dc) {
                    return [
                        'id' => $dc->id,
                        'name' => $dc->name,
                        'center_type' => $dc->center_type,
                        'address' => $dc->address ?: $dc->micro_area,
                        'area_name' => $dc->location?->name,
                        'mobile_number' => $dc->mobile_number,
                        'services_count' => $dc->services->count(),
                        'service_names' => $dc->services->take(3)->pluck('name')->values()->toArray(),
                        'entity_type' => 'diagnostic_center',
                    ];
                })->values()->toArray();
            }
        }

        $totalCount = count($specialists) + count($hospitals) + count($diagnosticCenters);

        return response()->json([
            'query' => $query,
            'allowed_types' => $allowedTypes,
            'counts' => [
                'specialists' => count($specialists),
                'hospitals' => count($hospitals),
                'diagnostic_centers' => count($diagnosticCenters),
                'total' => $totalCount,
            ],
            'results' => [
                'specialists' => $specialists,
                'hospitals' => $hospitals,
                'diagnostic_centers' => $diagnosticCenters,
            ],
        ]);
    }
}
