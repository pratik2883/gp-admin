<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender?->name,
            'message' => $this->message,
            'attachments' => SupportTicketAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
        ];
    }
}
