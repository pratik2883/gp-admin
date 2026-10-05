<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SendNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static \UnitEnum|string|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Send Notification';

    protected static ?string $title = 'Send Notification';

    protected string $view = 'filament.pages.send-notification';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Audience')
                    ->schema([
                        Select::make('audience')
                            ->label('Send to')
                            ->options([
                                'all_users' => 'All users (GP + Specialist)',
                                'gp' => 'All GPs',
                                'specialist' => 'All Specialists',
                                'hospital' => 'All Hospitals',
                                'diagnostic_center' => 'All Diagnostic Centers',
                                'specific_user' => 'Specific user...',
                            ])
                            ->default('specific_user')
                            ->live()
                            ->required(),
                        Select::make('user_id')
                            ->label('User')
                            ->options(fn () => User::query()
                                ->where('role', '!=', 'admin')
                                ->orderBy('name')
                                ->limit(500)
                                ->get()
                                ->mapWithKeys(fn (User $user) => [$user->id => "{$user->name} ({$user->mobile})"])
                                ->toArray())
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return User::query()
                                    ->where('role', '!=', 'admin')
                                    ->where(function (Builder $q) use ($search) {
                                        $q->where('name', 'like', "%{$search}%")
                                            ->orWhere('email', 'like', "%{$search}%")
                                            ->orWhere('mobile', 'like', "%{$search}%");
                                    })
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (User $user) => [$user->id => "{$user->name} ({$user->mobile})"])
                                    ->toArray();
                            })
                            ->visible(fn (Get $get): bool => $get('audience') === 'specific_user')
                            ->required(fn (Get $get): bool => $get('audience') === 'specific_user'),
                    ]),

                Section::make('Message')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('body')
                            ->label('Message')
                            ->required()
                            ->rows(4),
                    ]),
            ]);
    }

    public function send(): void
    {
        $data = $this->form->getState();
        $title = trim((string) ($data['title'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));

        if ($title === '' || $body === '') {
            FilamentNotification::make()
                ->title('Title and message are required')
                ->danger()
                ->send();

            return;
        }

        $users = $this->resolveRecipients($data);

        if ($users->isEmpty()) {
            FilamentNotification::make()
                ->title('No recipients found for the selected audience')
                ->warning()
                ->send();

            return;
        }

        foreach ($users as $user) {
            $user->notify(new AdminBroadcastNotification($title, $body, [
                'role' => $user->role,
            ]));
        }

        $this->form->fill();

        FilamentNotification::make()
            ->title("Notification sent to {$users->count()} user(s)")
            ->success()
            ->send();
    }

    private function resolveRecipients(array $data): \Illuminate\Support\Collection
    {
        $audience = (string) ($data['audience'] ?? 'specific_user');

        $query = match ($audience) {
            'all_users' => User::query()->where('role', '!=', 'admin'),
            'gp' => User::query()->where('role', 'gp'),
            'specialist' => User::query()->where('role', 'specialist'),
            'hospital' => User::query()->where('role', 'specialist')->where('role_subtype', 'hospital'),
            'diagnostic_center' => User::query()->where('role', 'specialist')->where('role_subtype', 'diagnostic_center'),
            default => User::query()->where('id', (int) ($data['user_id'] ?? 0)),
        };

        return $query->get();
    }
}
