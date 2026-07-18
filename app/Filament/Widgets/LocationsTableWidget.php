<?php

namespace App\Filament\Widgets;

use App\Models\Location;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LocationsTableWidget extends TableWidget
{
    protected static ?string $heading = 'Top Locations by Activity';

    protected static ?string $description = 'Most active locations with hospitals & specialists';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Location::query()
                    ->when(session('dashboard_city'), fn ($q, $city) => $q->where('name', $city))
                    ->withCount(['hospitals', 'specialists', 'gps'])
                    ->orderByDesc('hospitals_count')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->width(20),
                Tables\Columns\TextColumn::make('name')
                    ->label('Location')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('hospitals_count')
                    ->label('Hospitals')
                    ->numeric(),
                Tables\Columns\TextColumn::make('specialists_count')
                    ->label('Specialists')
                    ->numeric(),
                Tables\Columns\TextColumn::make('gps_count')
                    ->label('GPs')
                    ->numeric(),
            ])
            ->paginated(false)
            ->striped();
    }
}
