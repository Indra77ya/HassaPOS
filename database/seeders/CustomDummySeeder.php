<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class CustomDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Get the first existing user to link dummy data
        $user = DB::table('users')->first();
        if (!$user) {
            $this->command->error("No user found in the database. Please create a user first.");
            return;
        }
        $user_id = $user->id;

        // 2. Get/Create Business
        $business = DB::table('business')->first();
        if (!$business) {
            $productcatalogue_settings = json_encode([
                'enable_whatsapp_ordering' => 1,
                'order_receiving_whatsapp_number' => '123456789',
            ]);
            $business_id = DB::table('business')->insertGetId([
                'name' => 'Hassa POS Pratama',
                'currency_id' => 54, // IDR
                'start_date' => '2023-01-01',
                'owner_id' => $user_id,
                'time_zone' => 'Asia/Jakarta',
                'fy_start_month' => 1,
                'accounting_method' => 'fifo',
                'default_profit_percent' => 25,
                'created_at' => now(),
                'productcatalogue_settings' => $productcatalogue_settings,
                'enabled_modules' => '["purchases","add_sale","pos_sale","stock_transfers","stock_adjustment","expenses","account"]',
                'ref_no_prefixes' => '{"purchase":"PO","stock_transfer":"ST","stock_adjustment":"SA","sell_return":"CN","expense":"EP","contacts":"CO","purchase_payment":"PP","sell_payment":"SP","business_location":"BL"}',
                'date_format' => 'd-m-Y',
                'time_format' => '24'
            ]);
        } else {
            $business_id = $business->id;
            DB::table('business')->where('id', $business_id)->update(['currency_id' => 54]);
        }

        DB::beginTransaction();
        $today = Carbon::now()->format('Y-m-d H:i:s');
        $driver = DB::getDriverName();
        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 0'); }
        elseif ($driver == 'sqlite') { DB::statement('PRAGMA foreign_keys = OFF'); }

        // 3. Cleanup tables
        $tables = [
            'brands', 'categories', 'contacts', 'products', 'product_variations', 'variations',
            'variation_location_details', 'transactions', 'transaction_payments',
            'transaction_sell_lines', 'purchase_lines', 'business_locations',
            'invoice_schemes', 'invoice_layouts', 'units', 'tax_rates', 'group_sub_taxes',
            'reference_counts', 'res_tables', 'expense_categories', 'stock_adjustment_lines'
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) { DB::table($table)->delete(); }
        }

        // 4. Seeding Data

        // Locations (2 for Transfers)
        $loc1 = DB::table('business_locations')->insertGetId([
            'business_id' => $business_id, 'name' => 'Toko Utama Jakarta', 'city' => 'Jakarta Pusat', 'is_active' => 1, 'created_at' => $today
        ]);
        $loc2 = DB::table('business_locations')->insertGetId([
            'business_id' => $business_id, 'name' => 'Cabang Gudang Bekasi', 'city' => 'Bekasi', 'is_active' => 1, 'created_at' => $today
        ]);

        // Units
        $u_pcs = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pcs', 'short_name' => 'pcs', 'created_by' => $user_id, 'created_at' => $today]);
        $u_kg = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Kilogram', 'short_name' => 'kg', 'allow_decimal' => 1, 'created_by' => $user_id, 'created_at' => $today]);

        // Brands & Categories
        $brands = ['Indofood', 'Wings', 'Unilever', 'Aqua', 'Samsung', 'Mayora', 'ABC', 'Nestle', 'Gudang Garam', 'Sampoerna'];
        $b_ids = [];
        foreach ($brands as $b) { $b_ids[] = DB::table('brands')->insertGetId(['business_id' => $business_id, 'name' => $b, 'created_by' => $user_id, 'created_at' => $today]); }

        $categories = ['Makanan', 'Minuman', 'Sembako', 'Elektronik', 'Kebutuhan Rumah', 'Rokok', 'Alat Tulis', 'Kesehatan'];
        $c_ids = [];
        foreach ($categories as $c) { $c_ids[] = DB::table('categories')->insertGetId(['name' => $c, 'business_id' => $business_id, 'parent_id' => 0, 'created_by' => $user_id, 'category_type' => 'product', 'created_at' => $today]); }

        // Products (100 items)
        $product_list = [];
        for ($i = 1; $i <= 100; $i++) {
            $price = rand(10, 3000) * 500; // 5k to 1.5M
            $p_id = DB::table('products')->insertGetId([
                'name' => 'Produk Super ' . $i, 'business_id' => $business_id, 'type' => 'single', 'unit_id' => ($i % 10 == 0 ? $u_kg : $u_pcs),
                'brand_id' => $b_ids[array_rand($b_ids)], 'category_id' => $c_ids[array_rand($c_ids)], 'tax_type' => 'exclusive', 'enable_stock' => 1,
                'sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'barcode_type' => 'C128', 'created_by' => $user_id, 'created_at' => $today
            ]);
            $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'is_dummy' => 1, 'created_at' => $today]);
            $v_id = DB::table('variations')->insertGetId([
                'name' => 'DUMMY', 'product_id' => $p_id, 'sub_sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'product_variation_id' => $pv_id,
                'default_purchase_price' => $price * 0.8, 'dpp_inc_tax' => $price * 0.8, 'profit_percent' => 25, 'default_sell_price' => $price, 'sell_price_inc_tax' => $price, 'created_at' => $today
            ]);
            DB::table('product_locations')->insert([['product_id' => $p_id, 'location_id' => $loc1], ['product_id' => $p_id, 'location_id' => $loc2]]);
            DB::table('variation_location_details')->insert([
                ['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => rand(100, 1000), 'created_at' => $today],
                ['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc2, 'qty_available' => rand(50, 500), 'created_at' => $today]
            ]);
            $product_list[] = ['id' => $p_id, 'v_id' => $v_id, 'price' => $price];
        }

        // Contacts
        $customers = [];
        for ($i = 1; $i <= 20; $i++) { $customers[] = DB::table('contacts')->insertGetId(['business_id' => $business_id, 'type' => 'customer', 'name' => 'Pelanggan Setia ' . $i, 'is_default' => ($i==1), 'created_by' => $user_id, 'created_at' => $today]); }
        $suppliers = [];
        for ($i = 1; $i <= 10; $i++) { $suppliers[] = DB::table('contacts')->insertGetId(['business_id' => $business_id, 'type' => 'supplier', 'name' => 'Distributor Utama ' . $i, 'created_by' => $user_id, 'created_at' => $today]); }

        // Sales (100)
        for ($i = 1; $i <= 100; $i++) {
            $p = $product_list[array_rand($product_list)];
            $q = rand(1, 5); $total = $p['price'] * $q;
            $dt = Carbon::now()->subDays(rand(0, 90))->format('Y-m-d H:i:s');
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'sell', 'status' => 'final', 'payment_status' => 'paid', 'contact_id' => $customers[array_rand($customers)],
                'invoice_no' => 'SALE-ID-' . $i . '-' . time(), 'transaction_date' => $dt, 'total_before_tax' => $total, 'final_total' => $total, 'created_by' => $user_id, 'created_at' => $dt
            ]);
            DB::table('transaction_sell_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['id'], 'variation_id' => $p['v_id'], 'quantity' => $q, 'unit_price' => $p['price'], 'unit_price_inc_tax' => $p['price'], 'created_at' => $dt]);
            DB::table('transaction_payments')->insert(['transaction_id' => $tid, 'amount' => $total, 'method' => 'cash', 'paid_on' => $dt, 'created_by' => $user_id, 'created_at' => $dt]);
        }

        // Purchases (30)
        for ($i = 1; $i <= 30; $i++) {
            $p = $product_list[array_rand($product_list)];
            $q = rand(50, 200); $cost = $p['price'] * 0.75; $total = $cost * $q;
            $dt = Carbon::now()->subDays(rand(0, 90))->format('Y-m-d H:i:s');
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'purchase', 'status' => 'received', 'payment_status' => 'paid', 'contact_id' => $suppliers[array_rand($suppliers)],
                'ref_no' => 'PUR-' . $i . '-' . time(), 'transaction_date' => $dt, 'total_before_tax' => $total, 'final_total' => $total, 'created_by' => $user_id, 'created_at' => $dt
            ]);
            DB::table('purchase_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['id'], 'variation_id' => $p['v_id'], 'quantity' => $q, 'purchase_price' => $cost, 'purchase_price_inc_tax' => $cost, 'created_at' => $dt]);
        }

        // Stock Transfers (15)
        for ($i = 1; $i <= 15; $i++) {
            $p = $product_list[array_rand($product_list)];
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc1, 'transfer_parent_id' => $loc2, 'type' => 'stock_transfer', 'status' => 'final', 'ref_no' => 'ST-' . $i,
                'transaction_date' => $today, 'total_before_tax' => $p['price'] * 10, 'final_total' => $p['price'] * 10, 'created_by' => $user_id, 'created_at' => $today
            ]);
        }

        // Stock Adjustments (15)
        for ($i = 1; $i <= 15; $i++) {
            $p = $product_list[array_rand($product_list)];
            $q = rand(1, 10);
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'stock_adjustment', 'adjustment_type' => 'normal', 'ref_no' => 'ADJ-' . $i,
                'transaction_date' => $today, 'total_before_tax' => $p['price'] * $q, 'final_total' => $p['price'] * $q, 'created_by' => $user_id, 'created_at' => $today
            ]);
            DB::table('stock_adjustment_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['id'], 'variation_id' => $p['v_id'], 'quantity' => $q, 'unit_price' => $p['price'], 'created_at' => $today]);
        }

        // Expenses (30)
        $e_cats = ['Listrik & Air', 'Sewa Ruko', 'Gaji Staff', 'Transportasi', 'Konsumsi', 'Internet', 'Pemeliharaan', 'Lain-lain'];
        foreach ($e_cats as $ec) {
            $ec_id = DB::table('expense_categories')->insertGetId(['name' => $ec, 'business_id' => $business_id]);
            for ($j = 1; $j <= rand(3, 5); $j++) {
                $amt = rand(10, 500) * 5000;
                DB::table('transactions')->insert([
                    'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'expense', 'status' => 'final', 'payment_status' => 'paid',
                    'expense_category_id' => $ec_id, 'ref_no' => 'EXP-' . $ec_id . '-' . $j . '-' . time(), 'transaction_date' => $today, 'final_total' => $amt, 'created_by' => $user_id, 'created_at' => $today
                ]);
            }
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        DB::commit();
        $this->command->info("Dummy Data Masif (IDR) Berhasil Dibuat:");
        $this->command->info("- 100 Produk, 100 Penjualan, 30 Pembelian");
        $this->command->info("- 15 Transfer Stok, 15 Penyesuaian Stok, 30+ Pengeluaran");
        $this->command->info("- Terhubung ke user: " . $user->username);
    }
}
