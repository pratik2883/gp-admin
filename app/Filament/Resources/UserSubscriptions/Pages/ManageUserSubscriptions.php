<?php

namespace App\Filament\Resources\UserSubscriptions\Pages;

use App\Filament\Resources\UserSubscriptions\UserSubscriptionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUserSubscriptions extends ManageRecords
{
    protected static string $resource = UserSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
