<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GpStoreRequest;
use App\Http\Requests\Admin\GpUpdateRequest;
use App\Http\Requests\Admin\ToggleStatusRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GpController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $items = User::query()
            ->where('role', 'gp')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return UserResource::collection($items);
    }

    public function store(GpStoreRequest $request)
    {
        $data = $request->validated();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'],
            'role' => 'gp',
            'status' => $data['status'] ?? 'active',
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'message' => 'Created',
            'user' => new UserResource($user),
        ], 201);
    }

    public function show(int $id)
    {
        $user = User::where('role', 'gp')->findOrFail($id);

        return new UserResource($user);
    }

    public function update(GpUpdateRequest $request, int $id)
    {
        $user = User::where('role', 'gp')->findOrFail($id);
        $data = $request->validated();
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }
        if (isset($data['mobile'])) {
            $user->mobile = $data['mobile'];
        }
        if (isset($data['status'])) {
            $user->status = $data['status'];
        }
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return response()->json([
            'message' => 'Updated',
            'user' => new UserResource($user),
        ]);
    }

    public function status(ToggleStatusRequest $request, int $id)
    {
        $status = $request->validated()['status'];
        $user = User::where('role', 'gp')->findOrFail($id);
        $user->status = $status;
        $user->save();

        if ($user->relationLoaded('gp') || $user->gp) {
            $user->gp->status = $status === 'active' ? 'approved' : $status;
            $user->gp->save();
        }

        return response()->json([
            'message' => 'Status updated',
            'user' => new UserResource($user),
        ]);
    }
}
