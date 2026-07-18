<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Specialist Normal - 1 Month', 'slug' => 'specialist-normal-1-month', 'category' => 'specialist', 'plan_family' => 'normal', 'bed_slab' => null, 'duration_months' => 1, 'price' => 299, 'currency' => 'INR', 'description' => 'Basic listing access for 1 month.', 'feature_points' => ['Listing access', 'Referral visibility'], 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Specialist Normal - 5 Months', 'slug' => 'specialist-normal-5-months', 'category' => 'specialist', 'plan_family' => 'normal', 'bed_slab' => null, 'duration_months' => 5, 'price' => 1299, 'currency' => 'INR', 'description' => 'Basic listing access for 5 months.', 'feature_points' => ['Listing access', 'Referral visibility'], 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Specialist Normal - 12 Months', 'slug' => 'specialist-normal-12-months', 'category' => 'specialist', 'plan_family' => 'normal', 'bed_slab' => null, 'duration_months' => 12, 'price' => 2499, 'currency' => 'INR', 'description' => 'Basic listing access for 12 months.', 'feature_points' => ['Listing access', 'Referral visibility'], 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Specialist Premium - 1 Month', 'slug' => 'specialist-premium-1-month', 'category' => 'specialist', 'plan_family' => 'premium', 'bed_slab' => null, 'duration_months' => 1, 'price' => 599, 'currency' => 'INR', 'description' => 'Premium visibility for 1 month.', 'feature_points' => ['Premium badge', 'Priority recommendation'], 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Specialist Premium - 5 Months', 'slug' => 'specialist-premium-5-months', 'category' => 'specialist', 'plan_family' => 'premium', 'bed_slab' => null, 'duration_months' => 5, 'price' => 2499, 'currency' => 'INR', 'description' => 'Premium visibility for 5 months.', 'feature_points' => ['Premium badge', 'Priority recommendation'], 'is_active' => true, 'sort_order' => 5],
            ['name' => 'Specialist Premium - 12 Months', 'slug' => 'specialist-premium-12-months', 'category' => 'specialist', 'plan_family' => 'premium', 'bed_slab' => null, 'duration_months' => 12, 'price' => 4999, 'currency' => 'INR', 'description' => 'Premium visibility for 12 months.', 'feature_points' => ['Premium badge', 'Priority recommendation'], 'is_active' => true, 'sort_order' => 6],
            ['name' => 'Hospital 25 Beds - 6 Months', 'slug' => 'hospital-25-beds-6-months', 'category' => 'hospital', 'plan_family' => null, 'bed_slab' => 'upto_25', 'duration_months' => 6, 'price' => 900, 'currency' => 'INR', 'description' => 'Suitable for small hospitals.', 'feature_points' => ['Hospital listing', 'Referral intake'], 'is_active' => true, 'sort_order' => 10],
            ['name' => 'Hospital 25 Beds - 12 Months', 'slug' => 'hospital-25-beds-12-months', 'category' => 'hospital', 'plan_family' => null, 'bed_slab' => 'upto_25', 'duration_months' => 12, 'price' => 1500, 'currency' => 'INR', 'description' => 'Suitable for small hospitals.', 'feature_points' => ['Hospital listing', 'Referral intake'], 'is_active' => true, 'sort_order' => 11],
            ['name' => 'Hospital 50 Beds - 6 Months', 'slug' => 'hospital-50-beds-6-months', 'category' => 'hospital', 'plan_family' => null, 'bed_slab' => 'upto_50', 'duration_months' => 6, 'price' => 1800, 'currency' => 'INR', 'description' => 'Suitable for mid-size hospitals.', 'feature_points' => ['Hospital listing', 'Referral intake'], 'is_active' => true, 'sort_order' => 12],
            ['name' => 'Hospital 50 Beds - 12 Months', 'slug' => 'hospital-50-beds-12-months', 'category' => 'hospital', 'plan_family' => null, 'bed_slab' => 'upto_50', 'duration_months' => 12, 'price' => 3200, 'currency' => 'INR', 'description' => 'Suitable for mid-size hospitals.', 'feature_points' => ['Hospital listing', 'Referral intake'], 'is_active' => true, 'sort_order' => 13],
            ['name' => 'Hospital 100+ Beds - 6 Months', 'slug' => 'hospital-100plus-beds-6-months', 'category' => 'hospital', 'plan_family' => null, 'bed_slab' => 'above_100', 'duration_months' => 6, 'price' => 3500, 'currency' => 'INR', 'description' => 'Suitable for large hospitals.', 'feature_points' => ['Hospital listing', 'Priority support'], 'is_active' => true, 'sort_order' => 14],
            ['name' => 'Hospital 100+ Beds - 12 Months', 'slug' => 'hospital-100plus-beds-12-months', 'category' => 'hospital', 'plan_family' => null, 'bed_slab' => 'above_100', 'duration_months' => 12, 'price' => 6500, 'currency' => 'INR', 'description' => 'Suitable for large hospitals.', 'feature_points' => ['Hospital listing', 'Priority support'], 'is_active' => true, 'sort_order' => 15],
            ['name' => 'Diagnostic Center - 6 Months', 'slug' => 'diagnostic-center-6-months', 'category' => 'diagnostic_center', 'plan_family' => null, 'bed_slab' => null, 'duration_months' => 6, 'price' => 1200, 'currency' => 'INR', 'description' => 'Diagnostic listing for 6 months.', 'feature_points' => ['Center listing', 'Referral intake'], 'is_active' => true, 'sort_order' => 20],
            ['name' => 'Diagnostic Center - 12 Months', 'slug' => 'diagnostic-center-12-months', 'category' => 'diagnostic_center', 'plan_family' => null, 'bed_slab' => null, 'duration_months' => 12, 'price' => 2200, 'currency' => 'INR', 'description' => 'Diagnostic listing for 12 months.', 'feature_points' => ['Center listing', 'Referral intake'], 'is_active' => true, 'sort_order' => 21],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
