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
                'name' => 'Hassa Mega POS Pratama', 'currency_id' => 54, 'start_date' => '2023-01-01', 'owner_id' => $user_id, 'time_zone' => 'Asia/Jakarta',
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

        // 3. Cleanup tables
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

        // Variation Templates
        $variation_templates_data = [
            'Warna' => ['Merah', 'Biru', 'Hijau', 'Kuning', 'Hitam', 'Putih', 'Abu-abu', 'Cokelat'],
            'Ukuran' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'Rasa' => ['Original', 'Cokelat', 'Vanilla', 'Stroberi', 'Keju', 'Pedas'],
            'Storage' => ['64GB', '128GB', '256GB', '512GB'],
            'RAM' => ['4GB', '8GB', '12GB', '16GB']
        ];
        $vt_map = [];
        foreach ($variation_templates_data as $name => $values) {
            $vt_id = DB::table('variation_templates')->insertGetId(['name' => $name, 'business_id' => $business_id]);
            foreach ($values as $v) { DB::table('variation_value_templates')->insert(['name' => $v, 'variation_template_id' => $vt_id]); }
            $vt_map[$name] = ['id' => $vt_id, 'values' => $values];
        }

        // Selling Price Groups (1000)
        $spg_ids = [];
        for ($i = 1; $i <= 1000; $i++) {
            $spg_ids[] = DB::table('selling_price_groups')->insertGetId(['name' => 'Grup Harga #' . $i, 'business_id' => $business_id]);
        }

        // Customer Groups (1000)
        $cg_ids = [];
        for ($i = 1; $i <= 1000; $i++) {
            $cg_ids[] = DB::table('customer_groups')->insertGetId(['business_id' => $business_id, 'name' => 'Grup Loyalitas #' . $i, 'amount' => rand(1, 15), 'created_by' => $user_id]);
        }

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
                'business_id' => $business_id, 'type' => 'supplier', 'name' => 'Supplier Utama #' . $i, 'contact_id' => 'SUPP-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'created_by' => $user_id
            ]);
        }

        // Warranties (5)
        $w_ids = [];
        foreach ([['name' => '1 Tahun', 'duration' => 1, 'duration_type' => 'years'], ['name' => '6 Bulan', 'duration' => 6, 'duration_type' => 'months']] as $w) {
            $w['business_id'] = $business_id; $w_ids[] = DB::table('warranties')->insertGetId($w);
        }

        // Units, Brands, Categories
        $u_pcs = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pieces', 'short_name' => 'pcs', 'created_by' => $user_id]);
        $b_id = DB::table('brands')->insertGetId(['business_id' => $business_id, 'name' => 'Hassa Brand', 'created_by' => $user_id]);
        $c_id = DB::table('categories')->insertGetId(['name' => 'Kategori Utama', 'business_id' => $business_id, 'parent_id' => 0, 'created_by' => $user_id, 'category_type' => 'product']);

        // Products (1000 items - 30% Variable)
        $all_v_ids = [];
        for ($i = 1; $i <= 1000; $i++) {
            $is_variable = ($i % 3 == 0);
            $type = $is_variable ? 'variable' : 'single';
            $price = rand(10, 5000) * 1000;

            $p_id = DB::table('products')->insertGetId([
                'name' => 'Produk Hassa ' . $i, 'business_id' => $business_id, 'type' => $type, 'unit_id' => $u_pcs,
                'brand_id' => $b_id, 'category_id' => $c_id, 'warranty_id' => $w_ids[array_rand($w_ids)],
                'tax_type' => 'exclusive', 'enable_stock' => 1, 'sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'barcode_type' => 'C128', 'created_by' => $user_id, 'created_at' => $today
            ]);

            DB::table('product_locations')->insert([['product_id' => $p_id, 'location_id' => $loc1], ['product_id' => $p_id, 'location_id' => $loc2]]);

            if (!$is_variable) {
                $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'is_dummy' => 1]);
                $v_id = DB::table('variations')->insertGetId([
                    'name' => 'DUMMY', 'product_id' => $p_id, 'sub_sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'product_variation_id' => $pv_id,
                    'default_purchase_price' => $price * 0.8, 'dpp_inc_tax' => $price * 0.8, 'profit_percent' => 25, 'default_sell_price' => $price, 'sell_price_inc_tax' => $price, 'created_at' => $today
                ]);
                $all_v_ids[] = ['p_id' => $p_id, 'v_id' => $v_id, 'price' => $price];
                DB::table('variation_location_details')->insert(['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => 100]);
            } else {
                $vt_key = array_rand($vt_map);
                $pv_id = DB::table('product_variations')->insertGetId(['name' => $vt_key, 'product_id' => $p_id, 'is_dummy' => 0]);
                foreach (array_slice($vt_map[$vt_key]['values'], 0, 3) as $v_idx => $v_val) {
                    $v_price = $price + ($v_idx * 10000);
                    $v_id = DB::table('variations')->insertGetId([
                        'name' => $v_val, 'product_id' => $p_id, 'sub_sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT) . '-' . ($v_idx+1), 'product_variation_id' => $pv_id,
                        'default_purchase_price' => $v_price * 0.8, 'dpp_inc_tax' => $v_price * 0.8, 'profit_percent' => 25, 'default_sell_price' => $v_price, 'sell_price_inc_tax' => $v_price, 'created_at' => $today
                    ]);
                    $all_v_ids[] = ['p_id' => $p_id, 'v_id' => $v_id, 'price' => $v_price];
                    DB::table('variation_location_details')->insert(['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => 50]);
                }
            }
        }

        // Group Prices for Variations
        $vgp_data = [];
        foreach (array_slice($all_v_ids, 0, 500) as $v_data) {
            $random_spgs = (array) array_rand($spg_ids, 3);
            foreach ($random_spgs as $spg_idx) {
                $vgp_data[] = ['variation_id' => $v_data['v_id'], 'price_group_id' => $spg_ids[$spg_idx], 'price_inc_tax' => $v_data['price'] * 0.9, 'created_at' => $today];
            }
            if (count($vgp_data) >= 500) { DB::table('variation_group_prices')->insert($vgp_data); $vgp_data = []; }
        }

        // Sales (1000)
        for ($i = 1; $i <= 1000; $i++) {
            $p = $all_v_ids[array_rand($all_v_ids)];
            $dt = Carbon::now()->subDays(rand(0, 180))->format('Y-m-d H:i:s');
            $tid = DB::table('transactions')->insertGetId(['business_id' => $business_id, 'location_id' => $loc1, 'type' => 'sell', 'status' => 'final', 'payment_status' => 'paid', 'contact_id' => $customers[array_rand($customers)], 'invoice_no' => 'INV-'.$i.'-'.time(), 'transaction_date' => $dt, 'final_total' => $p['price'], 'created_by' => $user_id, 'created_at' => $dt]);
            DB::table('transaction_sell_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'], 'quantity' => 1, 'unit_price' => $p['price'], 'unit_price_inc_tax' => $p['price'], 'created_at' => $dt]);
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        DB::commit();
        $this->command->info("Dummy Data ULTRA MASIF & LENGKAP Berhasil Dibuat (IDR).");
        $this->command->info("- 1000 Produk, 1000 Pelanggan, 1000 Supplier, 1000 Grup Harga, 1000 Grup Pelanggan");
        $this->command->info("- 1000 Penjualan, Variasi Berlimpah & Harga Grup Aktif.");
    }
}
