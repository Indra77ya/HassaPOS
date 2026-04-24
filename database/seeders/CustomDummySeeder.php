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

        // 2. Get/Create Business with all modules enabled to show all menus
        $business = DB::table('business')->first();
        $all_modules = '["purchases","add_sale","pos_sale","stock_transfers","stock_adjustment","expenses","account","subscription","service_staff","tables","modifiers","kitchen","booking","types_of_service","product_catalogue","repair"]';

        if (!$business) {
            $business_id = DB::table('business')->insertGetId([
                'name' => 'Hassa POS Pratama', 'currency_id' => 54, 'start_date' => '2023-01-01', 'owner_id' => $user_id, 'time_zone' => 'Asia/Jakarta',
                'fy_start_month' => 1, 'accounting_method' => 'fifo', 'default_profit_percent' => 25, 'created_at' => now(),
                'enabled_modules' => $all_modules,
                'ref_no_prefixes' => '{"purchase":"PO","stock_transfer":"ST","stock_adjustment":"SA","sell_return":"CN","expense":"EP","contacts":"CO","purchase_payment":"PP","sell_payment":"SP","business_location":"BL"}',
                'date_format' => 'd-m-Y', 'time_format' => '24'
            ]);
        } else {
            $business_id = $business->id;
            DB::table('business')->where('id', $business_id)->update([
                'currency_id' => 54,
                'enabled_modules' => $all_modules
            ]);
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
            'reference_counts', 'res_tables', 'expense_categories', 'stock_adjustment_lines',
            'customer_groups', 'selling_price_groups', 'warranties', 'variation_templates', 'variation_value_templates'
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) { DB::table($table)->delete(); }
        }

        // 4. Seeding Data

        // Tax Rates
        $tax_id = DB::table('tax_rates')->insertGetId(['business_id' => $business_id, 'name' => 'PPN 11%', 'amount' => 11, 'created_by' => $user_id, 'created_at' => $today]);

        // Locations
        $loc1 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Toko Pusat Jakarta', 'city' => 'Jakarta Pusat', 'is_active' => 1, 'created_at' => $today]);
        $loc2 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Cabang Gudang Bekasi', 'city' => 'Bekasi', 'is_active' => 1, 'created_at' => $today]);

        // Warranties
        $w_ids = [];
        foreach ([['name' => 'Garansi 7 Hari', 'duration' => 7, 'duration_type' => 'days'], ['name' => 'Garansi 1 Tahun', 'duration' => 1, 'duration_type' => 'years']] as $w) {
            $w['business_id'] = $business_id; $w_ids[] = DB::table('warranties')->insertGetId($w);
        }

        // Selling Price Groups
        $spg_ids = [];
        foreach (['Retail', 'Grosir', 'Member VIP', 'Distributor'] as $spg) {
            $spg_ids[] = DB::table('selling_price_groups')->insertGetId(['name' => $spg, 'business_id' => $business_id]);
        }

        // Variation Templates
        $vt_id = DB::table('variation_templates')->insertGetId(['name' => 'Warna & Ukuran', 'business_id' => $business_id]);
        foreach (['Merah', 'Biru', 'Putih', 'S', 'M', 'L', 'XL'] as $val) {
            DB::table('variation_value_templates')->insert(['name' => $val, 'variation_template_id' => $vt_id]);
        }

        // Customer Groups (100)
        $cg_ids = [];
        for ($i = 1; $i <= 100; $i++) { $cg_ids[] = DB::table('customer_groups')->insertGetId(['business_id' => $business_id, 'name' => 'Grup Loyalitas ' . $i, 'amount' => rand(1, 15), 'created_by' => $user_id]); }

        // Contacts (1000 Customers, 500 Suppliers)
        $customers = [];
        for ($i = 1; $i <= 1000; $i++) {
            $customers[] = DB::table('contacts')->insertGetId([
                'business_id' => $business_id, 'type' => 'customer', 'name' => 'Pelanggan Hassa ' . $i,
                'contact_id' => 'CUST-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'customer_group_id' => $cg_ids[array_rand($cg_ids)],
                'is_default' => ($i==1), 'created_by' => $user_id, 'created_at' => $today, 'mobile' => '0812' . rand(10000000, 99999999)
            ]);
        }
        $suppliers = [];
        for ($i = 1; $i <= 500; $i++) {
            $suppliers[] = DB::table('contacts')->insertGetId([
                'business_id' => $business_id, 'type' => 'supplier', 'name' => 'Mitra Supplier ' . $i, 'contact_id' => 'SUPP-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'created_by' => $user_id, 'created_at' => $today
            ]);
        }

        // Units, Brands, Categories
        $u_pcs = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pieces', 'short_name' => 'pcs', 'created_by' => $user_id]);
        $brands_list = ['Indofood', 'Unilever', 'Wings', 'Mayora', 'ABC', 'Nestle', 'Aqua', 'Samsung', 'Sharp', 'LG'];
        $b_ids = [];
        foreach ($brands_list as $b) { $b_ids[] = DB::table('brands')->insertGetId(['business_id' => $business_id, 'name' => $b, 'created_by' => $user_id]); }

        $c_ids = [];
        foreach (['Makanan', 'Minuman', 'Elektronik', 'Kebutuhan Rumah'] as $c) {
            $parent_id = DB::table('categories')->insertGetId(['name' => $c, 'business_id' => $business_id, 'parent_id' => 0, 'created_by' => $user_id, 'category_type' => 'product']);
            $c_ids[] = $parent_id;
            DB::table('categories')->insert(['name' => $c . ' Sub-Kategori', 'business_id' => $business_id, 'parent_id' => $parent_id, 'created_by' => $user_id, 'category_type' => 'product']);
        }

        // Products (1000 items)
        $product_list = [];
        for ($i = 1; $i <= 1000; $i++) {
            $price = rand(5, 5000) * 1000;
            $p_id = DB::table('products')->insertGetId([
                'name' => 'Produk Premium ' . $i, 'business_id' => $business_id, 'type' => 'single', 'unit_id' => $u_pcs,
                'brand_id' => $b_ids[array_rand($b_ids)], 'category_id' => $c_ids[array_rand($c_ids)], 'warranty_id' => $w_ids[array_rand($w_ids)],
                'tax' => $tax_id, 'tax_type' => 'exclusive', 'enable_stock' => 1, 'sku' => 'PRO-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'barcode_type' => 'C128', 'created_by' => $user_id, 'created_at' => $today
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
            if ($i <= 200) { $product_list[] = ['id' => $p_id, 'v_id' => $v_id, 'price' => $price]; }
        }

        // Transactions (1000 Sales, 100 Purchases, 50 Adjustments, 100 Expenses, 50 Transfers)
        for ($i = 1; $i <= 1000; $i++) {
            $p = $product_list[array_rand($product_list)];
            $q = rand(1, 5); $total = $p['price'] * $q;
            $dt = Carbon::now()->subDays(rand(0, 365))->format('Y-m-d H:i:s');
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'sell', 'status' => 'final', 'payment_status' => 'paid', 'contact_id' => $customers[array_rand($customers)],
                'invoice_no' => 'INV-' . time() . '-' . $i, 'transaction_date' => $dt, 'total_before_tax' => $total, 'final_total' => $total, 'created_by' => $user_id, 'created_at' => $dt
            ]);
            DB::table('transaction_sell_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['id'], 'variation_id' => $p['v_id'], 'quantity' => $q, 'unit_price' => $p['price'], 'unit_price_inc_tax' => $p['price'], 'created_at' => $dt]);
        }

        for ($i = 1; $i <= 100; $i++) {
            $p = $product_list[array_rand($product_list)];
            $q = rand(50, 200); $total = ($p['price'] * 0.7) * $q;
            $dt = Carbon::now()->subDays(rand(0, 365))->format('Y-m-d H:i:s');
            DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'purchase', 'status' => 'received', 'payment_status' => 'paid', 'contact_id' => $suppliers[array_rand($suppliers)],
                'ref_no' => 'PUR-' . time() . '-' . $i, 'transaction_date' => $dt, 'total_before_tax' => $total, 'final_total' => $total, 'created_by' => $user_id, 'created_at' => $dt
            ]);
        }

        for ($i = 1; $i <= 50; $i++) {
            $p = $product_list[array_rand($product_list)];
            DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc2, 'transfer_parent_id' => $loc1, 'type' => 'stock_transfer', 'status' => 'final', 'ref_no' => 'TRF-' . $i,
                'transaction_date' => $today, 'total_before_tax' => $p['price'] * 10, 'final_total' => $p['price'] * 10, 'created_by' => $user_id, 'created_at' => $today
            ]);
        }

        $e_cats = ['Sewa', 'Listrik', 'Gaji', 'Marketing', 'Logistik'];
        foreach ($e_cats as $ec) {
            $ec_id = DB::table('expense_categories')->insertGetId(['name' => $ec, 'business_id' => $business_id]);
            for ($j = 1; $j <= 20; $j++) {
                DB::table('transactions')->insert(['business_id' => $business_id, 'location_id' => $loc1, 'type' => 'expense', 'status' => 'final', 'payment_status' => 'paid', 'expense_category_id' => $ec_id, 'ref_no' => 'EXP-' . rand(1000, 9999), 'transaction_date' => $today, 'final_total' => rand(5, 500) * 10000, 'created_by' => $user_id]);
            }
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        DB::commit();
        $this->command->info("Dummy database Ultra Lengkap (IDR) Selesai. Selamat mencoba!");
    }
}
