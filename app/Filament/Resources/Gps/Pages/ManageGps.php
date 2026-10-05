<?php

namespace App\Filament\Resources\Gps\Pages;

use App\Filament\Resources\Gps\GpResource;
use App\Models\Gp;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageGps extends ManageRecords
{
    protected static string $resource = GpResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Pending Approval ⏳')
                ->badge(Gp::query()->where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            'all' => Tab::make('All Signups')
                ->badge(Gp::query()->count()),
            'approved' => Tab::make('Approved ✅')
                ->badge(Gp::query()->where('status', 'approved')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'blocked' => Tab::make('Blocked 🛑')
                ->badge(Gp::query()->where('status', 'blocked')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'blocked')),
        ];
    }
}

