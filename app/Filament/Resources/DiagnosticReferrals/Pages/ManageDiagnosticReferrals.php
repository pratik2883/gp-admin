<?php

namespace App\Filament\Resources\DiagnosticReferrals\Pages;

use App\Filament\Resources\DiagnosticReferrals\DiagnosticReferralResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDiagnosticReferrals extends ManageRecords
{
    protected static string $resource = DiagnosticReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
