<?php

namespace App\Filament\Widgets;

use App\Models\Specialist;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SpecialistsTableWidget extends TableWidget
{
    protected static ?string $heading = 'Top Specialists by Referrals';

    protected static ?string $description = 'Specialists with the most referral activity';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Specialist::query()
                    ->when(session('dashboard_city'), fn ($q, $city) => $q->where('clinic_city', $city))
                    ->with(['user', 'specialty'])
                    ->withCount('referrals')
                    ->orderByDesc('referrals_count')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->width(20),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('primary_specialization')
                    ->label('Specialty')
                    ->formatStateUsing(fn (?string $state, Specialist $s) => $s->specialty?->plain_label ?: ($s->specialty?->name ?: $state))
                    ->searchable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('clinic_city')
                    ->label('City')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('referrals_count')
                    ->label('Referrals')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state >= 10 ? 'success' : ($state >= 5 ? 'warning' : 'gray')),
            ])
            ->paginated(false)
            ->striped();
    }
}
