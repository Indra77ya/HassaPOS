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

        // 2. Get/Create Business with all modules enabled
        $business = DB::table('business')->first();
        $all_modules = '["purchases","add_sale","pos_sale","stock_transfers","stock_adjustment","expenses","account","subscription","service_staff","tables","modifiers","kitchen","booking","types_of_service","product_catalogue","repair"]';

        if (!$business) {
            $business_id = DB::table('business')->insertGetId([
                'name' => 'Hassa POS Pratama Indonesia', 'currency_id' => 54, 'start_date' => '2023-01-01', 'owner_id' => $user_id, 'time_zone' => 'Asia/Jakarta',
                'fy_start_month' => 1, 'accounting_method' => 'fifo', 'default_profit_percent' => 25, 'created_at' => now(),
                'enabled_modules' => $all_modules,
                'ref_no_prefixes' => '{"purchase":"PO","stock_transfer":"ST","stock_adjustment":"SA","sell_return":"CN","expense":"EP","contacts":"CO","purchase_payment":"PP","sell_payment":"SP","business_location":"BL"}',
                'date_format' => 'd-m-Y', 'time_format' => '24'
            ]);
        } else {
            $business_id = $business->id;
            DB::table('business')->where('id', $business_id)->update(['currency_id' => 54, 'enabled_modules' => $all_modules]);
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
            'reference_counts', 'res_tables', 'expense_categories', 'stock_adjustment_lines',
            'customer_groups', 'selling_price_groups', 'warranties', 'variation_templates', 'variation_value_templates',
            'variation_group_prices'
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) { DB::table($table)->delete(); }
        }

        // 4. Seeding Data

        // Locations
        $loc1 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Toko Pusat Jakarta', 'city' => 'Jakarta Pusat', 'is_active' => 1, 'created_at' => $today]);
        $loc2 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Cabang Gudang Bekasi', 'city' => 'Bekasi', 'is_active' => 1, 'created_at' => $today]);

        // UNITS (Comprehensive Variety)
        $u_pcs = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pieces', 'short_name' => 'pcs', 'allow_decimal' => 0, 'created_by' => $user_id]);
        $u_gr = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Gram', 'short_name' => 'gr', 'allow_decimal' => 1, 'created_by' => $user_id]);
        $u_ml = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Mililiter', 'short_name' => 'ml', 'allow_decimal' => 1, 'created_by' => $user_id]);
        $u_cm = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Sentimeter', 'short_name' => 'cm', 'allow_decimal' => 1, 'created_by' => $user_id]);
        DB::table('units')->insert([
            ['business_id' => $business_id, 'actual_name' => 'Lusin', 'short_name' => 'lsn', 'allow_decimal' => 0, 'base_unit_id' => $u_pcs, 'base_unit_multiplier' => 12, 'created_by' => $user_id],
            ['business_id' => $business_id, 'actual_name' => 'Kodi', 'short_name' => 'kodi', 'allow_decimal' => 0, 'base_unit_id' => $u_pcs, 'base_unit_multiplier' => 20, 'created_by' => $user_id],
            ['business_id' => $business_id, 'actual_name' => 'Kilogram', 'short_name' => 'kg', 'allow_decimal' => 1, 'base_unit_id' => $u_gr, 'base_unit_multiplier' => 1000, 'created_by' => $user_id],
            ['business_id' => $business_id, 'actual_name' => 'Liter', 'short_name' => 'ltr', 'allow_decimal' => 1, 'base_unit_id' => $u_ml, 'base_unit_multiplier' => 1000, 'created_by' => $user_id],
            ['business_id' => $business_id, 'actual_name' => 'Box', 'short_name' => 'box', 'allow_decimal' => 0, 'base_unit_id' => $u_pcs, 'base_unit_multiplier' => 24, 'created_by' => $user_id],
            ['business_id' => $business_id, 'actual_name' => 'Sachet', 'short_name' => 'sct', 'allow_decimal' => 0, 'base_unit_id' => null, 'base_unit_multiplier' => null, 'created_by' => $user_id]
        ]);
        $all_u_ids = DB::table('units')->where('business_id', $business_id)->pluck('id')->toArray();

        // Selling Price Groups (1000)
        $spg_ids = [];
        for ($i = 1; $i <= 1000; $i++) { $spg_ids[] = DB::table('selling_price_groups')->insertGetId(['name' => 'Grup Harga #' . $i, 'business_id' => $business_id]); }

        // Customer Groups (1000)
        $cg_ids = [];
        for ($i = 1; $i <= 1000; $i++) { $cg_ids[] = DB::table('customer_groups')->insertGetId(['business_id' => $business_id, 'name' => 'Grup Pelanggan #' . $i, 'amount' => rand(1, 15), 'created_by' => $user_id]); }

        // Contacts (1000 Customers, 1000 Suppliers)
        $customers = [];
        $first_names = ['Andi', 'Budi', 'Cici', 'Dedi', 'Eko', 'Fani', 'Gita', 'Hadi', 'Indah', 'Joko', 'Kiki', 'Lani', 'Maya', 'Nico', 'Oki', 'Putu', 'Rina', 'Santi', 'Tono', 'Uli'];
        $last_names = ['Saputra', 'Wijaya', 'Kusuma', 'Pratama', 'Hidayat', 'Santoso', 'Gunawan', 'Lestari', 'Sari', 'Utami'];
        for ($i = 1; $i <= 1000; $i++) {
            $customers[] = DB::table('contacts')->insertGetId([
                'business_id' => $business_id, 'type' => 'customer', 'name' => $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)] . ' ' . $i,
                'contact_id' => 'CUST-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'customer_group_id' => $cg_ids[array_rand($cg_ids)], 'created_by' => $user_id, 'mobile' => '0812' . rand(10000000, 99999999)
            ]);
            DB::table('contacts')->insert([
                'business_id' => $business_id, 'type' => 'supplier', 'name' => 'Supplier Hassa Utama ' . $i, 'contact_id' => 'SUPP-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'created_by' => $user_id
            ]);
        }
        $suppliers = DB::table('contacts')->where('business_id', $business_id)->where('type', 'supplier')->pluck('id')->toArray();

        // Variation Templates (Extensive)
        $variation_templates_data = [
            'Warna' => ['Merah', 'Biru', 'Putih', 'Hitam', 'Abu-abu'],
            'Ukuran' => ['XS', 'S', 'M', 'L', 'XL'],
            'Rasa' => ['Original', 'Cokelat', 'Vanilla'],
            'Storage' => ['128GB', '256GB', '512GB']
        ];
        $vt_map = [];
        foreach ($variation_templates_data as $name => $values) {
            $vt_id = DB::table('variation_templates')->insertGetId(['name' => $name, 'business_id' => $business_id]);
            foreach ($values as $v) { DB::table('variation_value_templates')->insert(['name' => $v, 'variation_template_id' => $vt_id]); }
            $vt_map[$name] = ['id' => $vt_id, 'values' => $values];
        }

        // Warranties & Basics
        $w_ids = [];
        foreach (['1 Tahun', '6 Bulan', '7 Hari'] as $wn) { $w_ids[] = DB::table('warranties')->insertGetId(['name' => $wn, 'business_id' => $business_id, 'duration' => 1, 'duration_type' => 'years']); }
        $tax_id = DB::table('tax_rates')->insertGetId(['business_id' => $business_id, 'name' => 'PPN 11%', 'amount' => 11, 'created_by' => $user_id]);
        $b_id = DB::table('brands')->insertGetId(['business_id' => $business_id, 'name' => 'Brand Hassa', 'created_by' => $user_id]);
        $c_id = DB::table('categories')->insertGetId(['name' => 'Kategori Super', 'business_id' => $business_id, 'parent_id' => 0, 'created_by' => $user_id, 'category_type' => 'product']);

        // Products (1000 items - 30% Variable)
        $all_v_ids = [];
        for ($i = 1; $i <= 1000; $i++) {
            $is_variable = ($i % 3 == 0);
            $p_id = DB::table('products')->insertGetId([
                'name' => 'Produk Premium ' . $i, 'business_id' => $business_id, 'type' => ($is_variable ? 'variable' : 'single'), 'unit_id' => $all_u_ids[array_rand($all_u_ids)],
                'brand_id' => $b_id, 'category_id' => $c_id, 'warranty_id' => $w_ids[array_rand($w_ids)], 'tax' => $tax_id,
                'enable_stock' => 1, 'sku' => 'PRO-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'barcode_type' => 'C128', 'created_by' => $user_id, 'created_at' => $today
            ]);
            DB::table('product_locations')->insert([['product_id' => $p_id, 'location_id' => $loc1], ['product_id' => $p_id, 'location_id' => $loc2]]);

            if (!$is_variable) {
                $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'is_dummy' => 1]);
                $v_id = DB::table('variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'sub_sku' => 'PRO-'.str_pad($i, 5, '0', STR_PAD_LEFT), 'product_variation_id' => $pv_id, 'default_purchase_price' => 100000, 'dpp_inc_tax' => 111000, 'profit_percent' => 25, 'default_sell_price' => 125000, 'sell_price_inc_tax' => 138750, 'created_at' => $today]);
                $all_v_ids[] = ['p_id' => $p_id, 'v_id' => $v_id, 'price' => 125000];
                DB::table('variation_location_details')->insert(['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => 1000]);
            } else {
                $vt_k = array_rand($vt_map);
                $pv_id = DB::table('product_variations')->insertGetId(['name' => $vt_k, 'product_id' => $p_id, 'is_dummy' => 0]);
                foreach (array_slice($vt_map[$vt_k]['values'], 0, 3) as $vix => $vvl) {
                    $v_id = DB::table('variations')->insertGetId(['name' => $vvl, 'product_id' => $p_id, 'sub_sku' => 'PRO-'.str_pad($i, 5, '0', STR_PAD_LEFT).'-'.$vix, 'product_variation_id' => $pv_id, 'default_purchase_price' => 200000, 'dpp_inc_tax' => 222000, 'profit_percent' => 25, 'default_sell_price' => 250000, 'sell_price_inc_tax' => 277500, 'created_at' => $today]);
                    $all_v_ids[] = ['p_id' => $p_id, 'v_id' => $v_id, 'price' => 250000];
                    DB::table('variation_location_details')->insert(['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => 500]);
                }
            }
        }

        // Group Prices (for 500 variations)
        $vgp_data = [];
        foreach (array_slice($all_v_ids, 0, 500) as $vd) {
            $rspgs = (array) array_rand($spg_ids, 2);
            foreach ($rspgs as $si) { $vgp_data[] = ['variation_id' => $vd['v_id'], 'price_group_id' => $spg_ids[$si], 'price_inc_tax' => $vd['price'] * 0.9, 'created_at' => $today]; }
        }
        DB::table('variation_group_prices')->insert($vgp_data);

        // Sales (1000), Purchases (100), Expenses (100)
        for ($i = 1; $i <= 1000; $i++) {
            $p = $all_v_ids[array_rand($all_v_ids)];
            $dt = Carbon::now()->subDays(rand(0, 180))->format('Y-m-d H:i:s');
            $tid = DB::table('transactions')->insertGetId(['business_id' => $business_id, 'location_id' => $loc1, 'type' => 'sell', 'status' => 'final', 'payment_status' => 'paid', 'contact_id' => $customers[array_rand($customers)], 'invoice_no' => 'INV-'.time().'-'.$i, 'transaction_date' => $dt, 'final_total' => $p['price'], 'created_by' => $user_id, 'created_at' => $dt]);
            DB::table('transaction_sell_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'], 'quantity' => 1, 'unit_price' => $p['price'], 'unit_price_inc_tax' => $p['price'], 'created_at' => $dt]);
        }
        for ($i = 1; $i <= 100; $i++) {
            $p = $all_v_ids[array_rand($all_v_ids)];
            DB::table('transactions')->insertGetId(['business_id' => $business_id, 'location_id' => $loc1, 'type' => 'purchase', 'status' => 'received', 'payment_status' => 'paid', 'contact_id' => $suppliers[array_rand($suppliers)], 'ref_no' => 'PUR-'.time().'-'.$i, 'transaction_date' => $today, 'final_total' => $p['price'] * 10, 'created_by' => $user_id]);
        }
        $ec_id = DB::table('expense_categories')->insertGetId(['name' => 'Operasional Toko', 'business_id' => $business_id]);
        for ($i = 1; $i <= 100; $i++) {
            DB::table('transactions')->insert(['business_id' => $business_id, 'location_id' => $loc1, 'type' => 'expense', 'status' => 'final', 'payment_status' => 'paid', 'expense_category_id' => $ec_id, 'ref_no' => 'EXP-'.time().'-'.$i, 'transaction_date' => $today, 'final_total' => rand(5, 50) * 10000, 'created_by' => $user_id]);
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        DB::commit();
        $this->command->info("ULTRALENGKAP & ULTRAMASIF Dummy Seeder Berhasil Selesai!");
    }
}
