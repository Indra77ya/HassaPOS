<?php

namespace Modules\Project\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Schema;

class ProjectDummySeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('pjt_projects')) {
            return;
        }

        $faker = Faker::create();
        $business_id = DB::table('business')->pluck('id')->first();
        $user_id = DB::table('users')->where('business_id', $business_id)->pluck('id')->first();

        if (!$business_id || !$user_id) {
            return;
        }

        // Projects
        for ($i = 0; $i < 10; $i++) {
            $project_id = DB::table('pjt_projects')->insertGetId([
                'business_id' => $business_id,
                'name' => $faker->bs . ' Project',
                'description' => $faker->paragraph,
                'start_date' => $faker->dateTimeThisYear()->format('Y-m-d'),
                'end_date' => $faker->dateTimeThisYear()->format('Y-m-d'),
                'status' => $faker->randomElement(['not_started', 'on_hold', 'in_progress', 'completed']),
                'created_by' => $user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Members
            if (Schema::hasTable('pjt_project_members')) {
                DB::table('pjt_project_members')->insert([
                    'project_id' => $project_id,
                    'user_id' => $user_id,
                ]);
            }

            // Tasks
            if (Schema::hasTable('pjt_project_tasks')) {
                for ($j = 0; $j < 10; $j++) {
                    $task_id = DB::table('pjt_project_tasks')->insertGetId([
                        'project_id' => $project_id,
                        'subject' => $faker->sentence(4),
                        'description' => $faker->sentence,
                        'status' => $faker->randomElement(['not_started', 'in_progress', 'completed']),
                        'priority' => $faker->randomElement(['low', 'medium', 'high']),
                        'created_by' => $user_id,
                        'business_id' => $business_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Time Logs
                    if (Schema::hasTable('pjt_project_time_logs')) {
                        DB::table('pjt_project_time_logs')->insert([
                            'project_id' => $project_id,
                            'project_task_id' => $task_id,
                            'user_id' => $user_id,
                            'start_datetime' => $faker->dateTimeThisMonth(),
                            'end_datetime' => $faker->dateTimeThisMonth(),
                            'note' => $faker->sentence,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
