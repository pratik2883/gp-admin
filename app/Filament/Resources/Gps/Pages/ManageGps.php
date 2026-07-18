<?php

namespace App\Filament\Resources\Gps\Pages;

use App\Filament\Resources\Gps\GpResource;
use Filament\Resources\Pages\ManageRecords;

class ManageGps extends ManageRecords
{
    protected static string $resource = GpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Creation disabled for admin; GP signups come from app
        ];
    }
}
