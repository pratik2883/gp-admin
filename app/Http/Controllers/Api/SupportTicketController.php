<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->resolveSupportedUser($request->user());
        $perPage = max(1, min(50, (int) $request->query('per_page', 20)));

        $tickets = SupportTicket::query()
            ->where('user_id', $user->id)
            ->with(['assignedAdmin:id,name,email'])
            ->orderByRaw('COALESCE(last_reply_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate($perPage);

        return SupportTicketResource::collection($tickets);
    }

    public function store(Request $request)
    {
        $user = $this->resolveSupportedUser($request->user());
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'category' => ['nullable', 'string', Rule::in($this->categories())],
            'priority' => ['required', 'string', Rule::in($this->priorities())],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt'],
        ]);

        $ticket = DB::transaction(function () use ($user, $request, $data) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'role_type' => $this->resolveRoleType($user),
                'subject' => trim((string) $data['subject']),
                'category' => $data['category'] ?? 'general',
                'priority' => $data['priority'],
                'status' => 'open',
                'last_reply_at' => now(),
            ]);

            $message = $ticket->messages()->create([
                'sender_type' => 'user',
                'sender_id' => $user->id,
                'message' => trim((string) $data['message']),
            ]);

            $this->storeAttachments(
                $ticket,
                $message,
                $request->file('attachments', [])
            );

            return $ticket;
        });

        $ticket->load($this->detailRelations());

        return (new SupportTicketResource($ticket))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $ticketId)
    {
        $user = $this->resolveSupportedUser($request->user());
        $ticket = $this->findOwnedTicket($user, $ticketId);

        return new SupportTicketResource($ticket);
    }

    public function reply(Request $request, int $ticketId)
    {
        $user = $this->resolveSupportedUser($request->user());
        $ticket = $this->findOwnedTicket($user, $ticketId);

        if ($ticket->isClosed()) {
            return response()->json([
                'message' => 'Closed tickets cannot receive new replies.',
            ], 422);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt'],
        ]);

        DB::transaction(function () use ($ticket, $user, $request, $data) {
            $message = $ticket->messages()->create([
                'sender_type' => 'user',
                'sender_id' => $user->id,
                'message' => trim((string) $data['message']),
            ]);

            $ticket->forceFill([
                'last_reply_at' => now(),
                'status' => $ticket->status === 'resolved' ? 'in_progress' : $ticket->status,
                'resolved_at' => $ticket->status === 'resolved' ? null : $ticket->resolved_at,
            ])->save();

            $this->storeAttachments(
                $ticket,
                $message,
                $request->file('attachments', [])
            );
        });

        $ticket->refresh()->load($this->detailRelations());

        return new SupportTicketResource($ticket);
    }

    private function findOwnedTicket(User $user, int $ticketId): SupportTicket
    {
        return SupportTicket::query()
            ->where('user_id', $user->id)
            ->with($this->detailRelations())
            ->findOrFail($ticketId);
    }

    private function detailRelations(): array
    {
        return [
            'assignedAdmin:id,name,email',
            'messages.sender:id,name',
            'messages.attachments',
        ];
    }

    private function resolveSupportedUser(?User $user): User
    {
        abort_if(! $user, 401);
        abort_if($user->role === 'admin', 403, 'Admins cannot use the support app API.');

        $this->resolveRoleType($user);

        return $user;
    }

    private function resolveRoleType(User $user): string
    {
        if ($user->role === 'gp' && $user->gp()->exists()) {
            return 'gp';
        }

        if ($user->diagnosticCenter()->exists()) {
            return 'diagnostic_center';
        }

        if ($user->role === 'specialist' && $user->specialist()->exists()) {
            return 'specialist';
        }

        abort(403, 'Support is only available for GP, Specialist, and Diagnostic Center accounts.');
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     */
    private function storeAttachments(SupportTicket $ticket, SupportTicketMessage $message, array|UploadedFile|null $files): void
    {
        $uploads = $files instanceof UploadedFile ? [$files] : (is_array($files) ? $files : []);

        foreach ($uploads as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('support-tickets/'.$ticket->id, 'public');

            SupportTicketAttachment::query()->create([
                'support_ticket_id' => $ticket->id,
                'support_ticket_message_id' => $message->id,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function priorities(): array
    {
        return ['low', 'medium', 'high', 'urgent'];
    }

    private function categories(): array
    {
        return ['general', 'account', 'referral', 'diagnostic', 'billing', 'technical', 'notification', 'other'];
    }
}
