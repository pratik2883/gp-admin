<?php

namespace App\Filament\Resources\SupportTickets;

use App\Filament\Resources\SupportTickets\Pages\ManageSupportTickets;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Notifications\SupportTicketAdminReplyNotification;
use App\Notifications\SupportTicketClosedNotification;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|\UnitEnum|null $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    public static function getPluralLabel(): ?string
    {
        return 'Support Tickets';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user:id,name,email,mobile',
                'assignedAdmin:id,name,email',
                'messages.sender:id,name',
                'messages.attachments',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket')
                ->schema([
                    Select::make('status')
                        ->options(static::statusOptions())
                        ->required(),
                    Select::make('priority')
                        ->options(static::priorityOptions())
                        ->required(),
                    Select::make('category')
                        ->options(static::categoryOptions())
                        ->required(),
                    Select::make('assigned_admin_id')
                        ->label('Assigned Admin')
                        ->relationship('assignedAdmin', 'name', fn (Builder $query) => $query->where('role', 'admin'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket Summary')
                ->schema([
                    TextEntry::make('ticket_no')->label('Ticket No'),
                    TextEntry::make('subject'),
                    TextEntry::make('role_type')
                        ->label('Role')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => static::formatRoleType($state)),
                    TextEntry::make('priority')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => ucfirst((string) $state)),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => static::formatStatus($state)),
                    TextEntry::make('category')
                        ->formatStateUsing(fn (?string $state) => ucfirst(str_replace('_', ' ', (string) $state))),
                    TextEntry::make('assignedAdmin.name')->label('Assigned Admin')->placeholder('-'),
                    TextEntry::make('last_reply_at')->dateTime('d M Y, h:i A')->placeholder('-'),
                    TextEntry::make('resolved_at')->dateTime('d M Y, h:i A')->placeholder('-'),
                    TextEntry::make('closed_at')->dateTime('d M Y, h:i A')->placeholder('-'),
                ])
                ->columns(2),
            Section::make('Requester')
                ->schema([
                    TextEntry::make('user.name')->label('Name'),
                    TextEntry::make('user.email')->label('Email')->placeholder('-'),
                    TextEntry::make('user.mobile')->label('Mobile')->placeholder('-'),
                    TextEntry::make('created_at')->label('Created')->dateTime('d M Y, h:i A'),
                ])
                ->columns(2),
            Section::make('Conversation')
                ->schema([
                    TextEntry::make('conversation')
                        ->hiddenLabel()
                        ->state(fn (SupportTicket $record) => static::conversationHtml($record))
                        ->html()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_reply_at', 'desc')
            ->columns([
                TextColumn::make('ticket_no')
                    ->label('Ticket No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('role_type')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::formatRoleType($state)),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => ucfirst((string) $state))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'open' => 'warning',
                        'in_progress' => 'info',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => static::formatStatus($state))
                    ->sortable(),
                TextColumn::make('assignedAdmin.name')
                    ->label('Assigned Admin')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('last_reply_at')
                    ->label('Last Reply')
                    ->dateTime('d M, H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(static::statusOptions()),
                SelectFilter::make('role_type')
                    ->label('Role')
                    ->options([
                        'gp' => 'GP',
                        'specialist' => 'Specialist',
                        'diagnostic_center' => 'Diagnostic Center',
                    ]),
                SelectFilter::make('priority')->options(static::priorityOptions()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('assign_to_me')
                    ->label('Assign to Me')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->visible(fn (SupportTicket $record) => $record->assigned_admin_id !== auth()->id())
                    ->action(function (SupportTicket $record): void {
                        $record->forceFill([
                            'assigned_admin_id' => auth()->id(),
                            'status' => $record->status === 'open' ? 'in_progress' : $record->status,
                        ])->save();

                        Notification::make()->title('Ticket assigned to you')->success()->send();
                    }),
                Action::make('reply')
                    ->label('Reply')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->form([
                        Textarea::make('message')
                            ->required()
                            ->rows(5)
                            ->maxLength(5000),
                        FileUpload::make('attachments')
                            ->multiple()
                            ->disk('public')
                            ->directory(fn (SupportTicket $record) => 'support-tickets/'.$record->id.'/admin')
                            ->preserveFilenames()
                            ->maxFiles(5)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'text/plain',
                            ]),
                    ])
                    ->action(function (array $data, SupportTicket $record): void {
                        if ($record->isClosed()) {
                            Notification::make()->title('Closed tickets cannot receive replies')->danger()->send();
                            return;
                        }

                        $message = $record->messages()->create([
                            'sender_type' => 'admin',
                            'sender_id' => auth()->id(),
                            'message' => trim((string) ($data['message'] ?? '')),
                        ]);

                        foreach ((array) ($data['attachments'] ?? []) as $path) {
                            if (! is_string($path) || $path === '') {
                                continue;
                            }

                            SupportTicketAttachment::query()->create([
                                'support_ticket_id' => $record->id,
                                'support_ticket_message_id' => $message->id,
                                'original_name' => basename($path),
                                'file_path' => $path,
                                'mime_type' => Storage::disk('public')->mimeType($path) ?: null,
                                'size' => Storage::disk('public')->size($path) ?: null,
                            ]);
                        }

                        $record->forceFill([
                            'assigned_admin_id' => $record->assigned_admin_id ?: auth()->id(),
                            'last_reply_at' => now(),
                            'status' => 'in_progress',
                            'resolved_at' => null,
                        ])->save();

                        $record->refresh()->load(['user', 'messages.attachments']);
                        $record->user?->notify(new SupportTicketAdminReplyNotification($record, $message));

                        Notification::make()->title('Reply sent')->success()->send();
                    }),
                Action::make('mark_in_progress')
                    ->label('In Progress')
                    ->color('info')
                    ->visible(fn (SupportTicket $record) => in_array($record->status, ['open', 'resolved'], true))
                    ->action(function (SupportTicket $record): void {
                        $record->forceFill([
                            'status' => 'in_progress',
                            'assigned_admin_id' => $record->assigned_admin_id ?: auth()->id(),
                            'resolved_at' => null,
                        ])->save();

                        Notification::make()->title('Ticket marked in progress')->success()->send();
                    }),
                Action::make('resolve')
                    ->label('Resolve')
                    ->color('success')
                    ->visible(fn (SupportTicket $record) => ! in_array($record->status, ['resolved', 'closed'], true))
                    ->requiresConfirmation()
                    ->action(function (SupportTicket $record): void {
                        $record->forceFill([
                            'status' => 'resolved',
                            'assigned_admin_id' => $record->assigned_admin_id ?: auth()->id(),
                            'resolved_at' => now(),
                        ])->save();

                        Notification::make()->title('Ticket marked resolved')->success()->send();
                    }),
                Action::make('close')
                    ->label('Close')
                    ->color('gray')
                    ->visible(fn (SupportTicket $record) => $record->status !== 'closed')
                    ->requiresConfirmation()
                    ->action(function (SupportTicket $record): void {
                        $record->forceFill([
                            'status' => 'closed',
                            'assigned_admin_id' => $record->assigned_admin_id ?: auth()->id(),
                            'resolved_at' => $record->resolved_at ?: now(),
                            'closed_at' => now(),
                        ])->save();

                        $record->refresh()->load('user');
                        $record->user?->notify(new SupportTicketClosedNotification($record));

                        Notification::make()->title('Ticket closed')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSupportTickets::route('/'),
        ];
    }

    public static function conversationHtml(SupportTicket $record): string
    {
        $parts = [];

        foreach ($record->messages as $message) {
            $senderLabel = $message->sender_type === 'admin'
                ? 'Admin'.($message->sender?->name ? ' · '.$message->sender->name : '')
                : 'User'.($message->sender?->name ? ' · '.$message->sender->name : '');

            $attachments = '';
            if ($message->attachments->isNotEmpty()) {
                $links = $message->attachments
                    ->map(function ($attachment) {
                        $url = $attachment->file_path ? Storage::disk('public')->url($attachment->file_path) : null;
                        $name = e($attachment->original_name);

                        return $url
                            ? '<li><a href="'.e($url).'" target="_blank" style="color:#2563eb;text-decoration:underline;">'.$name.'</a></li>'
                            : '<li>'.$name.'</li>';
                    })
                    ->implode('');

                $attachments = '<div style="margin-top:8px;"><strong>Attachments</strong><ul style="margin:6px 0 0 18px;">'.$links.'</ul></div>';
            }

            $parts[] = sprintf(
                '<div style="border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-bottom:12px;background:%s;">
                    <div style="font-weight:600;color:#111827;">%s</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:2px;">%s</div>
                    <div style="margin-top:10px;white-space:pre-wrap;color:#111827;">%s</div>
                    %s
                </div>',
                $message->sender_type === 'admin' ? '#eff6ff' : '#f9fafb',
                e($senderLabel),
                e(optional($message->created_at)->format('d M Y, h:i A') ?? '-'),
                nl2br(e($message->message ?? '')),
                $attachments
            );
        }

        return $parts === []
            ? '<div style="color:#6b7280;">No conversation messages yet.</div>'
            : implode('', $parts);
    }

    public static function statusOptions(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            'general' => 'General',
            'account' => 'Account',
            'referral' => 'Referral',
            'diagnostic' => 'Diagnostic',
            'billing' => 'Billing',
            'technical' => 'Technical',
            'notification' => 'Notification',
            'other' => 'Other',
        ];
    }

    private static function formatRoleType(?string $state): string
    {
        return match ($state) {
            'gp' => 'GP',
            'specialist' => 'Specialist',
            'diagnostic_center' => 'Diagnostic Center',
            default => ucfirst(str_replace('_', ' ', (string) $state)),
        };
    }

    private static function formatStatus(?string $state): string
    {
        return match ($state) {
            'in_progress' => 'In Progress',
            default => ucfirst(str_replace('_', ' ', (string) $state)),
        };
    }
}
