<?php

namespace Modules\Superadmin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Schema;

class SuperadminDummySeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('packages')) {
            return;
        }

        $faker = Faker::create();
        $business_id = DB::table('business')->pluck('id')->first();
        $user_id = DB::table('users')->where('business_id', $business_id)->pluck('id')->first();

        if (!$user_id) {
            return;
        }

        // Packages
        for ($i = 0; $i < 5; $i++) {
            $package_id = DB::table('packages')->insertGetId([
                'name' => $faker->word . ' Pack',
                'description' => $faker->sentence,
                'location_count' => $faker->numberBetween(1, 10),
                'user_count' => $faker->numberBetween(1, 20),
                'product_count' => $faker->numberBetween(100, 1000),
                'invoice_count' => $faker->numberBetween(100, 1000),
                'interval' => 'months',
                'interval_count' => 1,
                'trial_days' => 30,
                'price' => $faker->randomFloat(2, 10, 100),
                'is_active' => 1,
                'created_by' => $user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Subscriptions
            if ($business_id && Schema::hasTable('subscriptions')) {
                DB::table('subscriptions')->insert([
                    'business_id' => $business_id,
                    'package_id' => $package_id,
                    'start_date' => now()->format('Y-m-d'),
                    'end_date' => now()->addMonths(1)->format('Y-m-d'),
                    'package_price' => $faker->randomFloat(2, 10, 100),
                    'status' => 'approved',
                    'created_id' => $user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Coupons
        if (Schema::hasTable('superadmin_coupons')) {
            for ($i = 0; $i < 10; $i++) {
                DB::table('superadmin_coupons')->insert([
                    'coupon_code' => strtoupper($faker->bothify('???###')),
                    'coupon_type' => $faker->randomElement(['fixed', 'percentage']),
                    'amount' => $faker->numberBetween(5, 50),
                    'expiry_date' => $faker->dateTimeBetween('now', '+6 months'),
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
