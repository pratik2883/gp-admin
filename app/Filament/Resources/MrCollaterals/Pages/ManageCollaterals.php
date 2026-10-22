<?php

namespace App\Filament\Resources\MrCollaterals\Pages;

use App\Filament\Resources\MrCollaterals\CollateralResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCollaterals extends ManageRecords
{
    protected static string $resource = CollateralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add collateral'),
        ];
    }
}
