<?php

namespace App\Filament\Resources\DiagnosticCenters\Pages;

use App\Filament\Resources\DiagnosticCenters\DiagnosticCenterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDiagnosticCenters extends ManageRecords
{
    protected static string $resource = DiagnosticCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
