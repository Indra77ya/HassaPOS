<?php

namespace Modules\Woocommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Schema;

class WoocommerceDummySeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('woocommerce_sync_logs')) {
            return;
        }

        $faker = Faker::create();
        $business_id = DB::table('business')->pluck('id')->first();

        if (!$business_id) {
            return;
        }

        // Sync Logs
        for ($i = 0; $i < 20; $i++) {
            DB::table('woocommerce_sync_logs')->insert([
                'business_id' => $business_id,
                'sync_type' => $faker->randomElement(['all', 'products', 'categories']),
                'operation_type' => $faker->randomElement(['created', 'updated', 'deleted']),
                'details' => $faker->sentence,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
