<?php

namespace Database\Seeders;

use App\Models\Mr;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyMrSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['mobile' => '9876543210'],
            [
                'name' => 'Amit Kulkarni (Test MR)',
                'email' => 'mr.amit@specialistconnectpro.com',
                'password' => Hash::make('password123'),
                'role' => 'mr',
                'status' => 'active',
            ]
        );

        Mr::updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_code' => 'MR-1001',
                'territory_zone' => 'Mumbai & Thane Zone',
                'headquarters_city' => 'Thane',
                'daily_visit_target' => 10,
                'monthly_subscription_target' => 5,
                'status' => 'active',
            ]
        );
    }
}
