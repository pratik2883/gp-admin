<?php

namespace App\Filament\Resources\Specialists\Pages;

use App\Filament\Resources\Specialists\SpecialistResource;
use App\Models\Specialist;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManageSpecialists extends ManageRecords
{
    protected static string $resource = SpecialistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): Specialist {
                    return DB::transaction(function () use ($data): Specialist {
                        $name = data_get($data, 'user_name') ?? data_get($data, 'user.name');
                        $email = data_get($data, 'user_email') ?? data_get($data, 'user.email');
                        $mobile = data_get($data, 'user_mobile') ?? data_get($data, 'user.mobile');

                        $user = User::create([
                            'name' => $name,
                            'email' => $email,
                            'mobile' => $mobile,
                            'role' => 'specialist',
                            'status' => 'active',
                            'password' => Hash::make(str()->random(12)),
                        ]);

                        $data['user_id'] = $user->id;
                        unset($data['user_name'], $data['user_email'], $data['user_mobile'], $data['user']);

                        $specialist = new Specialist;
                        $specialist->fill($data);
                        $specialist->save();

                        return $specialist;
                    });
                }),
        ];
    }
}
