<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'role' => $this->role,
            'status' => $this->status,
            'gp_id' => $this->gp()->value('id'),
            'specialist_id' => $this->specialist()->value('id'),
            'created_at' => $this->created_at,
        ];
    }
}
