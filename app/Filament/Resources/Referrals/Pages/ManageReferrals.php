<?php

namespace App\Filament\Resources\Referrals\Pages;

use App\Filament\Resources\Referrals\ReferralResource;
use App\Models\Referral;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageReferrals extends ManageRecords
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::SevenExtraLarge)
                ->mutateDataUsing(function (array $data): array {
                    if (($data['appointment_type'] ?? null) === 'opd') {
                        $data['hospital_id'] = null;
                    }

                    $maxId = Referral::max('id') ?? 0;
                    $data['lead_code'] = 'SSC-'.str_pad((string) ($maxId + 1), 4, '0', STR_PAD_LEFT);
                    $data['status'] = $data['status'] ?? 'sent';

                    return $data;
                }),
        ];
    }
}
