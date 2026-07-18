<?php

namespace App\Filament\Widgets;

use App\Models\Gp;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class GpSignupsWidget extends TableWidget
{
    protected static ?string $heading = 'Latest GP Signups';

    protected static ?string $description = 'Most recently registered general practitioners';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Gp::query()
                    ->when(session('dashboard_city'), fn ($q, $city) => $q->where('city', $city))
                    ->with('user')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('GP Name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.mobile')
                    ->label('Mobile')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'blocked',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Signed Up')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->paginated(false)
            ->striped();
    }
}
