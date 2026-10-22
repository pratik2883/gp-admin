<?php

namespace App\Filament\Resources\MrAttendances\Pages;

use App\Filament\Resources\MrAttendances\MrAttendanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMrAttendances extends ManageRecords
{
    protected static string $resource = MrAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add attendance entry'),
        ];
    }
}
