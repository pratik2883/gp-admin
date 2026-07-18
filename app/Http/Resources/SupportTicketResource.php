<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ticket_no' => $this->ticket_no,
            'role_type' => $this->role_type,
            'subject' => $this->subject,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'last_reply_at' => $this->last_reply_at,
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'can_reply' => $this->status !== 'closed',
            'assigned_admin' => $this->whenLoaded('assignedAdmin', fn () => [
                'id' => $this->assignedAdmin?->id,
                'name' => $this->assignedAdmin?->name,
                'email' => $this->assignedAdmin?->email,
            ]),
            'messages' => SupportTicketMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
