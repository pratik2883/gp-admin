<?php

namespace App\Filament\Resources\NearbyLocationMappings\Pages;

use App\Filament\Resources\NearbyLocationMappings\NearbyLocationMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNearbyLocationMappings extends ManageRecords
{
    protected static string $resource = NearbyLocationMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
