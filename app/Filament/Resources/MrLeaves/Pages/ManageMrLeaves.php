<?php

namespace App\Filament\Resources\MrLeaves\Pages;

use App\Filament\Resources\MrLeaves\MrLeaveResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMrLeaves extends ManageRecords
{
    protected static string $resource = MrLeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add leave for an MR'),
        ];
    }
}
