<?php

namespace Modules\Essentials\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Schema;

class EssentialsDummySeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('essentials_to_dos')) {
            return;
        }

        $faker = Faker::create();
        $business_id = DB::table('business')->pluck('id')->first();
        $user_id = DB::table('users')->where('business_id', $business_id)->pluck('id')->first();
        $leave_type_id = Schema::hasTable('essentials_leave_types') ? DB::table('essentials_leave_types')->where('business_id', $business_id)->pluck('id')->first() : null;

        if (!$business_id || !$user_id) {
            return;
        }

        // To Dos
        for ($i = 0; $i < 20; $i++) {
            DB::table('essentials_to_dos')->insert([
                'business_id' => $business_id,
                'task' => $faker->sentence,
                'description' => $faker->paragraph,
                'status' => $faker->randomElement(['pending', 'completed']),
                'priority' => $faker->randomElement(['low', 'medium', 'high']),
                'created_by' => $user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Leaves
        if ($leave_type_id && Schema::hasTable('essentials_leaves')) {
            for ($i = 0; $i < 10; $i++) {
                DB::table('essentials_leaves')->insert([
                    'business_id' => $business_id,
                    'user_id' => $user_id,
                    'essentials_leave_type_id' => $leave_type_id,
                    'start_date' => $faker->dateTimeThisYear()->format('Y-m-d'),
                    'end_date' => $faker->dateTimeThisYear()->format('Y-m-d'),
                    'reason' => $faker->sentence,
                    'status' => 'approved',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Attendances
        if (Schema::hasTable('essentials_attendances')) {
            for ($i = 0; $i < 20; $i++) {
                DB::table('essentials_attendances')->insert([
                    'business_id' => $business_id,
                    'user_id' => $user_id,
                    'clock_in_time' => $faker->dateTimeThisMonth(),
                    'clock_out_time' => $faker->dateTimeThisMonth(),
                    'ip_address' => $faker->ipv4,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Holidays
        if (Schema::hasTable('essentials_holidays')) {
            for ($i = 0; $i < 5; $i++) {
                DB::table('essentials_holidays')->insert([
                    'business_id' => $business_id,
                    'name' => $faker->word . ' Holiday',
                    'start_date' => $faker->dateTimeThisYear()->format('Y-m-d'),
                    'end_date' => $faker->dateTimeThisYear()->format('Y-m-d'),
                    'description' => $faker->sentence,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Messages
        if (Schema::hasTable('essentials_messages')) {
            for ($i = 0; $i < 15; $i++) {
                DB::table('essentials_messages')->insert([
                    'business_id' => $business_id,
                    'sender_id' => $user_id,
                    'message' => $faker->paragraph,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
