<?php

namespace App\Filament\Resources\MrVisits\Pages;

use App\Filament\Resources\MrVisits\MrVisitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMrVisits extends ManageRecords
{
    protected static string $resource = MrVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Log visit for an MR'),
        ];
    }
}
