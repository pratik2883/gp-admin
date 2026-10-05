<?php

namespace App\Filament\Resources\Mrs\Pages;

use App\Filament\Resources\Mrs\MrResource;
use App\Models\Mr;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManageMrs extends ManageRecords
{
    protected static string $resource = MrResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create MR User')
                ->using(function (array $data): Mr {
                    $user = User::create([
                        'name' => $data['name'],
                        'mobile' => $data['mobile'],
                        'email' => $data['email'] ?: $data['mobile'].'@specialistconnectpro.com',
                        'password' => Hash::make($data['password'] ?? '12345678'),
                        'role' => 'mr',
                        'status' => 'active',
                    ]);

                    return Mr::create([
                        'user_id' => $user->id,
                        'employee_code' => $data['employee_code'] ?? 'MR-'.rand(1000, 9999),
                        'territory_zone' => $data['territory_zone'] ?? 'Mumbai & Thane',
                        'headquarters_city' => $data['headquarters_city'] ?? 'Mumbai',
                        'daily_visit_target' => $data['daily_visit_target'] ?? 10,
                        'monthly_subscription_target' => $data['monthly_subscription_target'] ?? 5,
                        'status' => $data['status'] ?? 'active',
                    ]);
                }),
        ];
    }
}
