<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AccountingDummySeeder extends Seeder
{
    public function run()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('accounting_accounts')) {
            return;
        }

        $faker = Faker::create();
        $business_id = DB::table('business')->pluck('id')->first();

        if (!$business_id) {
            return;
        }

        // Accounts
        for ($i = 0; $i < 10; $i++) {
            DB::table('accounting_accounts')->insert([
                'name' => $faker->word . ' Account',
                'business_id' => $business_id,
                'account_primary_type' => $faker->randomElement(['asset', 'liability', 'equity', 'income', 'expenses']),
                'account_sub_type_id' => $faker->numberBetween(1, 10),
                'detail_type_id' => $faker->numberBetween(1, 20),
                'account_number' => $faker->bankAccountNumber,
                'description' => $faker->sentence,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
