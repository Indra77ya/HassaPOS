<?php

namespace Modules\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class CrmDummySeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $business_id = DB::table('business')->pluck('id')->first();
        $user_id = DB::table('users')->where('business_id', $business_id)->pluck('id')->first();
        $contact_id = DB::table('contacts')->where('business_id', $business_id)->pluck('id')->first();

        if (!$business_id || !$user_id) {
            return;
        }

        // Schedules
        for ($i = 0; $i < 15; $i++) {
            DB::table('crm_schedules')->insert([
                'business_id' => $business_id,
                'title' => $faker->sentence(3),
                'description' => $faker->paragraph,
                'start_datetime' => $faker->dateTimeBetween('now', '+1 month'),
                'end_datetime' => $faker->dateTimeBetween('+1 month', '+2 month'),
                'created_by' => $user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Campaigns
        for ($i = 0; $i < 10; $i++) {
            DB::table('crm_campaigns')->insert([
                'business_id' => $business_id,
                'name' => $faker->word . ' Campaign',
                'description' => $faker->sentence,
                'created_by' => $user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Call Logs
        if ($contact_id) {
            for ($i = 0; $i < 15; $i++) {
                DB::table('crm_call_logs')->insert([
                    'business_id' => $business_id,
                    'contact_id' => $contact_id,
                    'mobile' => $faker->phoneNumber,
                    'call_duration' => $faker->numberBetween(10, 300),
                    'description' => $faker->sentence,
                    'created_by' => $user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Proposals
        if ($contact_id) {
            for ($i = 0; $i < 10; $i++) {
                DB::table('crm_proposals')->insert([
                    'business_id' => $business_id,
                    'contact_id' => $contact_id,
                    'subject' => $faker->sentence(5),
                    'body' => $faker->paragraph(5),
                    'status' => $faker->randomElement(['pending', 'open', 'revised', 'accepted', 'declined']),
                    'created_by' => $user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
