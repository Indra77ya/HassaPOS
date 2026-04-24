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
        // 1. Get user
        $user = DB::table('users')->first();
        if (!$user) {
            $this->command->error("No user found. Please create at least one user manually.");
            return;
        }
        $user_id = $user->id;

        // 2. Get/Create Business
        $business = DB::table('business')->first();
        if (!$business) {
            $business_id = DB::table('business')->insertGetId([
                'name' => 'Hassa POS Pratama', 'currency_id' => 54, 'start_date' => '2023-01-01', 'owner_id' => $user_id, 'time_zone' => 'Asia/Jakarta',
                'fy_start_month' => 1, 'accounting_method' => 'fifo', 'default_profit_percent' => 25, 'created_at' => now(),
                'enabled_modules' => '["purchases","add_sale","pos_sale","stock_transfers","stock_adjustment","expenses","account"]',
                'ref_no_prefixes' => '{"purchase":"PO","stock_transfer":"ST","stock_adjustment":"SA","sell_return":"CN","expense":"EP","contacts":"CO","purchase_payment":"PP","sell_payment":"SP","business_location":"BL"}',
                'date_format' => 'd-m-Y', 'time_format' => '24'
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

        // 3. Cleanup tables (Keep users and business)
        $tables = [
            'brands', 'categories', 'contacts', 'products', 'product_variations', 'variations',
            'variation_location_details', 'transactions', 'transaction_payments',
            'transaction_sell_lines', 'purchase_lines', 'business_locations',
            'invoice_schemes', 'invoice_layouts', 'units', 'tax_rates', 'group_sub_taxes',
            'reference_counts', 'res_tables', 'expense_categories', 'stock_adjustment_lines', 'customer_groups'
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) { DB::table($table)->delete(); }
        }

        // 4. Seeding Data

        // Locations
        $loc1 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Hassa POS Jakarta', 'city' => 'Jakarta Pusat', 'is_active' => 1, 'created_at' => $today]);
        $loc2 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Hassa POS Bekasi', 'city' => 'Bekasi', 'is_active' => 1, 'created_at' => $today]);

        // Customer Groups (50)
        $cg_ids = [];
        for ($i = 1; $i <= 50; $i++) {
            $cg_ids[] = DB::table('customer_groups')->insertGetId([
                'business_id' => $business_id, 'name' => 'Grup Loyalitas ' . $i, 'amount' => rand(1, 15), 'created_by' => $user_id, 'created_at' => $today
            ]);
        }

        // Contacts (1000 Customers, 100 Suppliers)
        $customers = [];
        $first_names = ['Andi', 'Budi', 'Cici', 'Dedi', 'Eko', 'Fani', 'Gita', 'Hadi', 'Indah', 'Joko', 'Kiki', 'Lani', 'Maya', 'Nico', 'Oki', 'Putu', 'Rina', 'Santi', 'Tono', 'Uli'];
        $last_names = ['Saputra', 'Wijaya', 'Kusuma', 'Pratama', 'Hidayat', 'Santoso', 'Gunawan', 'Lestari', 'Sari', 'Utami'];

        for ($i = 1; $i <= 1000; $i++) {
            $name = $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)] . ' ' . $i;
            $customers[] = DB::table('contacts')->insertGetId([
                'business_id' => $business_id, 'type' => 'customer', 'name' => $name, 'contact_id' => 'CUST-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'customer_group_id' => $cg_ids[array_rand($cg_ids)], 'is_default' => ($i==1), 'created_by' => $user_id, 'created_at' => $today, 'mobile' => '0812' . rand(10000000, 99999999)
            ]);
        }
        $suppliers = [];
        for ($i = 1; $i <= 100; $i++) {
            $suppliers[] = DB::table('contacts')->insertGetId([
                'business_id' => $business_id, 'type' => 'supplier', 'name' => 'PT. Supplier Utama ' . $i, 'contact_id' => 'SUPP-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'created_by' => $user_id, 'created_at' => $today
            ]);
        }

        // Units, Brands, Categories
        $u_pcs = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pieces', 'short_name' => 'pcs', 'created_by' => $user_id]);
        $u_box = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Box', 'short_name' => 'box', 'created_by' => $user_id]);

        $brands_list = ['Indofood', 'Unilever', 'Wings', 'Mayora', 'ABC', 'Nestle', 'Aqua', 'Samsung', 'Oppo', 'Vivo', 'Garam', 'Sampoerna', 'Djarum', 'Polytron', 'Sharp'];
        $b_ids = [];
        foreach ($brands_list as $b) { $b_ids[] = DB::table('brands')->insertGetId(['business_id' => $business_id, 'name' => $b, 'created_by' => $user_id]); }

        $categories_list = ['Sembako', 'Makanan Ringan', 'Minuman', 'Elektronik', 'Kebutuhan Mandi', 'Rokok', 'Alat Tulis Kantor', 'Obat Umum', 'Pakaian Pria', 'Pakaian Wanita'];
        $c_ids = [];
        foreach ($categories_list as $c) { $c_ids[] = DB::table('categories')->insertGetId(['name' => $c, 'business_id' => $business_id, 'parent_id' => 0, 'created_by' => $user_id, 'category_type' => 'product']); }

        // Products (200 items)
        $product_list = [];
        for ($i = 1; $i <= 200; $i++) {
            $price = rand(5, 5000) * 500; // 2.5k to 2.5M
            $p_id = DB::table('products')->insertGetId([
                'name' => 'Produk Unggulan ' . $i, 'business_id' => $business_id, 'type' => 'single', 'unit_id' => ($i % 15 == 0 ? $u_box : $u_pcs),
                'brand_id' => $b_ids[array_rand($b_ids)], 'category_id' => $c_ids[array_rand($c_ids)], 'tax_type' => 'exclusive', 'enable_stock' => 1,
                'sku' => 'PRO-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'barcode_type' => 'C128', 'created_by' => $user_id, 'created_at' => $today
            ]);
            $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'is_dummy' => 1]);
            $v_id = DB::table('variations')->insertGetId([
                'name' => 'DUMMY', 'product_id' => $p_id, 'sub_sku' => 'PRO-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'product_variation_id' => $pv_id,
                'default_purchase_price' => $price * 0.7, 'dpp_inc_tax' => $price * 0.7, 'profit_percent' => 30, 'default_sell_price' => $price, 'sell_price_inc_tax' => $price, 'created_at' => $today
            ]);
            DB::table('product_locations')->insert([['product_id' => $p_id, 'location_id' => $loc1], ['product_id' => $p_id, 'location_id' => $loc2]]);
            DB::table('variation_location_details')->insert([
                ['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => rand(500, 5000), 'created_at' => $today],
                ['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc2, 'qty_available' => rand(200, 2000), 'created_at' => $today]
            ]);
            $product_list[] = ['id' => $p_id, 'v_id' => $v_id, 'price' => $price];
        }

        // Sales (300)
        for ($i = 1; $i <= 300; $i++) {
            $p = $product_list[array_rand($product_list)];
            $q = rand(1, 10); $total = $p['price'] * $q;
            $dt = Carbon::now()->subDays(rand(0, 120))->format('Y-m-d H:i:s');
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'sell', 'status' => 'final', 'payment_status' => 'paid', 'contact_id' => $customers[array_rand($customers)],
                'invoice_no' => 'INV-' . $i . '-' . time(), 'transaction_date' => $dt, 'total_before_tax' => $total, 'final_total' => $total, 'created_by' => $user_id, 'created_at' => $dt
            ]);
            DB::table('transaction_sell_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['id'], 'variation_id' => $p['v_id'], 'quantity' => $q, 'unit_price' => $p['price'], 'unit_price_inc_tax' => $p['price'], 'created_at' => $dt]);
            DB::table('transaction_payments')->insert(['transaction_id' => $tid, 'amount' => $total, 'method' => 'cash', 'paid_on' => $dt, 'created_by' => $user_id, 'created_at' => $dt]);
        }

        // Purchases (50)
        for ($i = 1; $i <= 50; $i++) {
            $p = $product_list[array_rand($product_list)];
            $q = rand(100, 1000); $cost = $p['price'] * 0.7; $total = $cost * $q;
            $dt = Carbon::now()->subDays(rand(0, 120))->format('Y-m-d H:i:s');
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc2, 'type' => 'purchase', 'status' => 'received', 'payment_status' => 'paid', 'contact_id' => $suppliers[array_rand($suppliers)],
                'ref_no' => 'PUR-' . $i . '-' . time(), 'transaction_date' => $dt, 'total_before_tax' => $total, 'final_total' => $total, 'created_by' => $user_id, 'created_at' => $dt
            ]);
            DB::table('purchase_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['id'], 'variation_id' => $p['v_id'], 'quantity' => $q, 'purchase_price' => $cost, 'purchase_price_inc_tax' => $cost, 'created_at' => $dt]);
        }

        // Stock Transfers (25)
        for ($i = 1; $i <= 25; $i++) {
            $p = $product_list[array_rand($product_list)];
            DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc2, 'transfer_parent_id' => $loc1, 'type' => 'stock_transfer', 'status' => 'final', 'ref_no' => 'TRF-' . $i . '-' . time(),
                'transaction_date' => $today, 'total_before_tax' => $p['price'] * 20, 'final_total' => $p['price'] * 20, 'created_by' => $user_id, 'created_at' => $today
            ]);
        }

        // Stock Adjustments (25)
        for ($i = 1; $i <= 25; $i++) {
            $p = $product_list[array_rand($product_list)];
            $q = rand(5, 20);
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'stock_adjustment', 'adjustment_type' => 'normal', 'ref_no' => 'ADJ-' . $i . '-' . time(),
                'transaction_date' => $today, 'total_before_tax' => $p['price'] * $q, 'final_total' => $p['price'] * $q, 'created_by' => $user_id, 'created_at' => $today
            ]);
            DB::table('stock_adjustment_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['id'], 'variation_id' => $p['v_id'], 'quantity' => $q, 'unit_price' => $p['price'], 'created_at' => $today]);
        }

        // Expenses (100)
        $e_cats = ['Operasional Kantor', 'Gaji & Bonus', 'Sewa Gedung & Listrik', 'Marketing & Iklan', 'Pemeliharaan Aset', 'Logistik & Kurir', 'Pajak & Legalitas', 'Lain-lain'];
        foreach ($e_cats as $ec) {
            $ec_id = DB::table('expense_categories')->insertGetId(['name' => $ec, 'business_id' => $business_id]);
            for ($j = 1; $j <= rand(10, 15); $j++) {
                $amt = rand(10, 1000) * 10000; // 100k to 10M
                DB::table('transactions')->insert([
                    'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'expense', 'status' => 'final', 'payment_status' => 'paid',
                    'expense_category_id' => $ec_id, 'ref_no' => 'EXP-' . $ec_id . '-' . $j . '-' . time(), 'transaction_date' => $today, 'final_total' => $amt, 'created_by' => $user_id, 'created_at' => $today
                ]);
            }
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        DB::commit();
        $this->command->info("Konfigurasi dummy database (IDR) Selesai:");
        $this->command->info("- 1000 Pelanggan, 100 Supplier, 50 Grup Pelanggan");
        $this->command->info("- 200 Produk Unggulan, 300 Penjualan, 50 Pembelian");
        $this->command->info("- 25 Transfer Stok, 25 Penyesuaian, 100+ Pengeluaran");
        $this->command->info("- Terhubung ke user: " . $user->username);
    }
}
