<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Utils\TransactionUtil;

class TrialBalanceDummySeeder extends Seeder
{
    public function run()
    {
        $business_id = 1; // Assuming business ID 1
        $today = Carbon::now()->format('Y-m-d H:i:s');

        $this->command->info("Seeding Dummy Trial Balance for Business ID: $business_id");

        // 1. Ensure Account Types exist (simplified for this seeder)
        $asset_type_id = DB::table('account_types')->where('business_id', $business_id)->where('name', 'Assets')->value('id');
        if (!$asset_type_id) {
            $asset_type_id = DB::table('account_types')->insertGetId(['name' => 'Assets', 'business_id' => $business_id]);
        }

        $equity_type_id = DB::table('account_types')->where('business_id', $business_id)->where('name', 'Equity')->value('id');
        if (!$equity_type_id) {
            $equity_type_id = DB::table('account_types')->insertGetId(['name' => 'Equity', 'business_id' => $business_id]);
        }

        // 2. Create Accounts
        $kas_id = DB::table('accounts')->insertGetId([
            'business_id' => $business_id,
            'name' => 'Kas Utama',
            'account_number' => '101001',
            'account_type_id' => $asset_type_id,
            'created_by' => 1,
            'created_at' => $today
        ]);

        $modal_id = DB::table('accounts')->insertGetId([
            'business_id' => $business_id,
            'name' => 'Modal Disetor',
            'account_number' => '301001',
            'account_type_id' => $equity_type_id,
            'created_by' => 1,
            'created_at' => $today
        ]);

        // 3. Initial Balance (Debit Kas, Credit Modal)
        DB::table('account_transactions')->insert([
            ['account_id' => $kas_id, 'type' => 'debit', 'amount' => 100000000, 'operation_date' => $today, 'created_by' => 1],
            ['account_id' => $modal_id, 'type' => 'credit', 'amount' => 100000000, 'operation_date' => $today, 'created_by' => 1]
        ]);

        // 4. Create Contact (Supplier & Customer)
        $customer_id = DB::table('contacts')->insertGetId([
            'business_id' => $business_id,
            'type' => 'customer',
            'name' => 'Customer Dummy',
            'first_name' => 'Customer',
            'last_name' => 'Dummy',
            'mobile' => '08123456789',
            'created_by' => 1
        ]);

        $supplier_id = DB::table('contacts')->insertGetId([
            'business_id' => $business_id,
            'type' => 'supplier',
            'name' => 'Supplier Dummy',
            'first_name' => 'Supplier',
            'last_name' => 'Dummy',
            'mobile' => '08987654321',
            'created_by' => 1
        ]);

        // 5. Create Product
        $unit_id = DB::table('units')->where('business_id', $business_id)->value('id') ?: DB::table('units')->insertGetId(['business_id'=>$business_id,'actual_name'=>'Pcs','short_name'=>'pcs','allow_decimal'=>0,'created_by'=>1]);

        $product_id = DB::table('products')->insertGetId([
            'name' => 'Product Dummy',
            'business_id' => $business_id,
            'type' => 'single',
            'unit_id' => $unit_id,
            'tax_type' => 'exclusive',
            'barcode_type' => 'C128',
            'sku' => 'DUMMY-01',
            'created_by' => 1
        ]);

        $p_v_id = DB::table('product_variations')->insertGetId(['name'=>'DUMMY','product_id'=>$product_id,'is_dummy'=>1]);
        $variation_id = DB::table('variations')->insertGetId([
            'name' => 'DUMMY',
            'product_id' => $product_id,
            'sub_sku' => 'DUMMY-01',
            'product_variation_id' => $p_v_id,
            'default_purchase_price' => 10000,
            'dpp_inc_tax' => 10000,
            'default_sell_price' => 15000,
            'sell_price_inc_tax' => 15000
        ]);

        $location_id = DB::table('business_locations')->where('business_id', $business_id)->value('id');

        // 6. Transactions
        // A. Purchase: 100 qty @ 10000 = 1,000,000 + 10% Tax (100,000) = 1,100,000. Shipping 50,000. Total 1,150,000. Paid 500,000. Due 650,000.
        $purchase_id = DB::table('transactions')->insertGetId([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'partial',
            'contact_id' => $supplier_id,
            'ref_no' => 'PUR-001',
            'transaction_date' => $today,
            'total_before_tax' => 1000000,
            'tax_amount' => 100000,
            'shipping_charges' => 50000,
            'final_total' => 1150000,
            'created_by' => 1
        ]);

        $payment_id = DB::table('transaction_payments')->insertGetId([
            'transaction_id' => $purchase_id,
            'business_id' => $business_id,
            'amount' => 500000,
            'method' => 'cash',
            'paid_on' => $today,
            'created_by' => 1,
            'account_id' => $kas_id
        ]);

        DB::table('account_transactions')->insert([
            'account_id' => $kas_id,
            'type' => 'credit',
            'amount' => 500000,
            'operation_date' => $today,
            'created_by' => 1,
            'transaction_id' => $purchase_id,
            'transaction_payment_id' => $payment_id
        ]);

        // B. Sale: 50 qty @ 20000 = 1,000,000 + 10% Tax (100,000) = 1,100,000. Shipping 20,000. Round off 500. Total 1,120,500. Paid 1,000,000. Due 120,500.
        $sale_id = DB::table('transactions')->insertGetId([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'partial',
            'contact_id' => $customer_id,
            'invoice_no' => 'SAL-001',
            'transaction_date' => $today,
            'total_before_tax' => 1000000,
            'tax_amount' => 100000,
            'shipping_charges' => 20000,
            'round_off_amount' => 500,
            'final_total' => 1120500,
            'created_by' => 1
        ]);

        $payment_id = DB::table('transaction_payments')->insertGetId([
            'transaction_id' => $sale_id,
            'business_id' => $business_id,
            'amount' => 1000000,
            'method' => 'cash',
            'paid_on' => $today,
            'created_by' => 1,
            'account_id' => $kas_id
        ]);

        DB::table('account_transactions')->insert([
            'account_id' => $kas_id,
            'type' => 'debit',
            'amount' => 1000000,
            'operation_date' => $today,
            'created_by' => 1,
            'transaction_id' => $sale_id,
            'transaction_payment_id' => $payment_id
        ]);

        $this->command->info("Trial Balance Dummy Data seeded successfully.");
    }
}
