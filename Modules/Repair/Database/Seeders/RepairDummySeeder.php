<?php

namespace Modules\Repair\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Schema;

class RepairDummySeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('repair_job_sheets')) {
            return;
        }

        $faker = Faker::create();
        $business_id = DB::table('business')->pluck('id')->first();
        $user_id = DB::table('users')->where('business_id', $business_id)->pluck('id')->first();
        $location_id = DB::table('business_locations')->where('business_id', $business_id)->pluck('id')->first();
        $contact_id = DB::table('contacts')->where('business_id', $business_id)->pluck('id')->first();
        $status_id = Schema::hasTable('repair_statuses') ? DB::table('repair_statuses')->where('business_id', $business_id)->pluck('id')->first() : null;

        if (!$business_id || !$user_id || !$location_id || !$contact_id) {
            return;
        }

        // Job Sheets
        for ($i = 0; $i < 15; $i++) {
            DB::table('repair_job_sheets')->insert([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'contact_id' => $contact_id,
                'job_sheet_no' => 'JS-' . $faker->unique()->numberBetween(1000, 9999),
                'created_by' => $user_id,
                'status_id' => $status_id ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Device Models
        if (Schema::hasTable('repair_device_models')) {
            $brand_id = DB::table('brands')->where('business_id', $business_id)->pluck('id')->first();
            $device_id = DB::table('categories')->where('business_id', $business_id)->where('category_type', 'device')->pluck('id')->first();

            if ($brand_id && $device_id) {
                for ($i = 0; $i < 10; $i++) {
                    DB::table('repair_device_models')->insert([
                        'business_id' => $business_id,
                        'name' => $faker->word . ' Model ' . $i,
                        'brand_id' => $brand_id,
                        'device_id' => $device_id,
                        'created_by' => $user_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
