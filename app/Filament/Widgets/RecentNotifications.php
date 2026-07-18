<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class RecentNotifications extends TableWidget
{
    protected static ?string $heading = 'Recent Notifications';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn (DatabaseNotification $record) => $record->data['title'] ?? '-')
                    ->wrap(),
                Tables\Columns\TextColumn::make('body')
                    ->label('Body')
                    ->getStateUsing(fn (DatabaseNotification $record) => $record->data['body'] ?? '-')
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M, h:i A')
                    ->sortable(),
            ])
            ->actions([
                Action::make('Open')
                    ->label('Open')
                    ->url(function (DatabaseNotification $record) {
                        $data = $record->data ?? [];
                        if (! empty($data['referral_id'] ?? null)) {
                            return url('/admin/referrals');
                        }
                        if (! empty($data['gp_id'] ?? null)) {
                            return url('/admin/gps');
                        }
                        if (! empty($data['specialist_id'] ?? null)) {
                            return url('/admin/specialists');
                        }

                        return null;
                    })
                    ->openUrlInNewTab(false)
                    ->visible(function (DatabaseNotification $record) {
                        $data = $record->data ?? [];

                        return ! empty($data['referral_id'] ?? null) || ! empty($data['gp_id'] ?? null) || ! empty($data['specialist_id'] ?? null);
                    }),
            ])
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }

    protected function query(): Builder
    {
        $user = auth()->user();

        return DatabaseNotification::query()
            ->whereMorphedTo('notifiable', $user)
            ->latest();
    }
}
