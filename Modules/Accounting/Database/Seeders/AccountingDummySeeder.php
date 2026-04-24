<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AccountingDummySeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $business_id = DB::table('business')->pluck('id')->first();
        $user_id = DB::table('users')->where('business_id', $business_id)->pluck('id')->first();

        if (!$business_id || !$user_id) {
            return;
        }

        // Accounts
        for ($i = 0; $i < 10; $i++) {
            $account_id = DB::table('accounting_accounts')->insertGetId([
                'name' => $faker->company . ' Account',
                'business_id' => $business_id,
                'account_primary_type' => $faker->randomElement(['asset', 'liability', 'equity', 'income', 'expense']),
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Transactions
            for ($j = 0; $j < 5; $j++) {
                DB::table('accounting_accounts_transactions')->insert([
                    'accounting_account_id' => $account_id,
                    'transaction_type' => $faker->randomElement(['debit', 'credit']),
                    'amount' => $faker->randomFloat(2, 100, 10000),
                    'operation_date' => $faker->dateTimeThisYear(),
                    'created_by' => $user_id,
                    'business_id' => $business_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Budgets
            DB::table('accounting_budgets')->insert([
                'business_id' => $business_id,
                'accounting_account_id' => $account_id,
                'fiscal_year' => now()->year,
                'value' => $faker->randomFloat(2, 1000, 50000),
                'created_by' => $user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
