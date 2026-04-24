<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class CustomDummySeeder extends Seeder
{
    public function run()
    {
        // 1. Get user & business
        $user = DB::table('users')->first();
        if (!$user) {
            $this->command->error("No user found. Please create at least one user manually.");
            return;
        }
        $user_id = $user->id;

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

        $today = Carbon::now()->format('Y-m-d H:i:s');
        $driver = DB::getDriverName();
        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 0'); }

        // 2. Cleanup
        $tables = [
            'brands', 'categories', 'contacts', 'products', 'product_variations', 'variations',
            'variation_location_details', 'transactions', 'transaction_payments',
            'transaction_sell_lines', 'purchase_lines', 'business_locations', 'product_locations',
            'units', 'tax_rates', 'expense_categories', 'customer_groups', 'selling_price_groups',
            'warranties', 'variation_templates', 'variation_value_templates', 'variation_group_prices'
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) { DB::table($table)->delete(); }
        }

        // 3. Master Data
        $loc1 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Toko Pusat Jakarta', 'city' => 'Jakarta Pusat', 'is_active' => 1, 'created_at' => $today]);
        $loc2 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Cabang Gudang Bekasi', 'city' => 'Bekasi', 'is_active' => 1, 'created_at' => $today]);

        // Units
        $u_pcs = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pieces', 'short_name' => 'pcs', 'allow_decimal' => 0, 'created_by' => $user_id]);
        $u_gr = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Gram', 'short_name' => 'gr', 'allow_decimal' => 1, 'created_by' => $user_id]);
        $u_ml = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Mililiter', 'short_name' => 'ml', 'allow_decimal' => 1, 'created_by' => $user_id]);
        $u_cm = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Sentimeter', 'short_name' => 'cm', 'allow_decimal' => 1, 'created_by' => $user_id]);

        $units_data = [
            ['actual_name' => 'Lusin', 'short_name' => 'lsn', 'allow_decimal' => 0, 'base_unit_id' => $u_pcs, 'base_unit_multiplier' => 12],
            ['actual_name' => 'Kodi', 'short_name' => 'kodi', 'allow_decimal' => 0, 'base_unit_id' => $u_pcs, 'base_unit_multiplier' => 20],
            ['actual_name' => 'Kilogram', 'short_name' => 'kg', 'allow_decimal' => 1, 'base_unit_id' => $u_gr, 'base_unit_multiplier' => 1000],
            ['actual_name' => 'Liter', 'short_name' => 'ltr', 'allow_decimal' => 1, 'base_unit_id' => $u_ml, 'base_unit_multiplier' => 1000],
            ['actual_name' => 'Box', 'short_name' => 'box', 'allow_decimal' => 0, 'base_unit_id' => $u_pcs, 'base_unit_multiplier' => 24],
            ['actual_name' => 'Meter', 'short_name' => 'm', 'allow_decimal' => 1, 'base_unit_id' => $u_cm, 'base_unit_multiplier' => 100],
            ['actual_name' => 'Rim', 'short_name' => 'rim', 'allow_decimal' => 0, 'base_unit_id' => $u_pcs, 'base_unit_multiplier' => 500],
            ['actual_name' => 'Dus', 'short_name' => 'dus', 'allow_decimal' => 0, 'base_unit_id' => $u_pcs, 'base_unit_multiplier' => 40],
            ['actual_name' => 'Pak', 'short_name' => 'pak', 'allow_decimal' => 0, 'base_unit_id' => $u_pcs, 'base_unit_multiplier' => 10],
            ['actual_name' => 'Roll', 'short_name' => 'roll', 'allow_decimal' => 1, 'base_unit_id' => $u_cm, 'base_unit_multiplier' => 1000],
            ['actual_name' => 'Karung', 'short_name' => 'krg', 'allow_decimal' => 1, 'base_unit_id' => $u_gr, 'base_unit_multiplier' => 50000],
            ['actual_name' => 'Botol', 'short_name' => 'btl', 'allow_decimal' => 0, 'base_unit_id' => null, 'base_unit_multiplier' => null],
            ['actual_name' => 'Kaleng', 'short_name' => 'klg', 'allow_decimal' => 0, 'base_unit_id' => null, 'base_unit_multiplier' => null],
            ['actual_name' => 'Ikat', 'short_name' => 'ikt', 'allow_decimal' => 0, 'base_unit_id' => null, 'base_unit_multiplier' => null],
            ['actual_name' => 'Bungkus', 'short_name' => 'bks', 'allow_decimal' => 0, 'base_unit_id' => null, 'base_unit_multiplier' => null],
            ['actual_name' => 'Sachet', 'short_name' => 'sct', 'allow_decimal' => 0, 'base_unit_id' => null, 'base_unit_multiplier' => null]
        ];
        foreach ($units_data as $ud) {
            DB::table('units')->insert(array_merge($ud, ['business_id' => $business_id, 'created_by' => $user_id]));
        }
        $all_u_ids = DB::table('units')->where('business_id', $business_id)->pluck('id')->toArray();

        // Customer Groups (1,000)
        $this->command->info("Seeding 1,000 Customer Groups...");
        for ($i = 1; $i <= 1000; $i++) {
            DB::table('customer_groups')->insert(['business_id' => $business_id, 'name' => 'Grup Pelanggan #'.$i, 'amount' => rand(1, 15), 'created_by' => $user_id]);
        }
        $cg_ids = DB::table('customer_groups')->where('business_id', $business_id)->pluck('id')->toArray();

        // Brands & Categories (100 each)
        $brand_ids = [];
        for ($i = 1; $i <= 100; $i++) { $brand_ids[] = DB::table('brands')->insertGetId(['business_id' => $business_id, 'name' => 'Brand Hassa-'.str_pad($i, 3, '0', STR_PAD_LEFT), 'created_by' => $user_id]); }
        $cat_ids = [];
        for ($i = 1; $i <= 100; $i++) { $cat_ids[] = DB::table('categories')->insertGetId(['business_id' => $business_id, 'name' => 'Kategori-'.str_pad($i, 3, '0', STR_PAD_LEFT), 'category_type' => 'product', 'created_by' => $user_id]); }

        // Variation Templates
        $vt_data = [
            'Warna' => ['Merah', 'Biru', 'Putih', 'Hitam', 'Kuning', 'Hijau', 'Cokelat'],
            'Ukuran' => ['S', 'M', 'L', 'XL', 'XXL', 'All Size'],
            'Bahan' => ['Katun', 'Polyester', 'Linen', 'Sutra']
        ];
        $vt_map = [];
        foreach ($vt_data as $name => $vals) {
            $vt_id = DB::table('variation_templates')->insertGetId(['name' => $name, 'business_id' => $business_id]);
            foreach ($vals as $v) { DB::table('variation_value_templates')->insert(['name' => $v, 'variation_template_id' => $vt_id]); }
            $vt_map[$name] = ['id' => $vt_id, 'values' => $vals];
        }

        $tax_id = DB::table('tax_rates')->insertGetId(['business_id' => $business_id, 'name' => 'PPN 11%', 'amount' => 11, 'created_by' => $user_id]);

        // 4. Contacts (5,000 each)
        $this->command->info("Seeding 5,000 Customers & 5,000 Suppliers...");
        $fnames = ['Andi', 'Budi', 'Cici', 'Dedi', 'Eko', 'Fani', 'Gita', 'Hadi', 'Indah', 'Joko', 'Kiki', 'Lani', 'Maya', 'Nico', 'Oki', 'Putu', 'Rina', 'Santi', 'Tono', 'Uli'];
        $lnames = ['Saputra', 'Wijaya', 'Kusuma', 'Pratama', 'Hidayat', 'Santoso', 'Gunawan', 'Lestari', 'Sari', 'Utami'];

        for ($chunk = 0; $chunk < 10; $chunk++) {
            $contacts = [];
            for ($i = 1; $i <= 500; $i++) {
                $idx = ($chunk * 500) + $i;
                $contacts[] = [ 'business_id' => $business_id, 'type' => 'customer', 'name' => $fnames[array_rand($fnames)].' '.$lnames[array_rand($lnames)].' '.$idx, 'contact_id' => 'CUST-'.str_pad($idx, 6, '0', STR_PAD_LEFT), 'customer_group_id' => $cg_ids[array_rand($cg_ids)], 'created_by' => $user_id, 'mobile' => '08'.rand(11, 59).rand(1000000, 9999999), 'created_at' => $today ];
                $contacts[] = [ 'business_id' => $business_id, 'type' => 'supplier', 'name' => 'Supplier Hassa Utama '.$idx, 'contact_id' => 'SUPP-'.str_pad($idx, 6, '0', STR_PAD_LEFT), 'customer_group_id' => null, 'created_by' => $user_id, 'mobile' => null, 'created_at' => $today ];
            }
            DB::table('contacts')->insert($contacts);
        }
        $cust_ids = DB::table('contacts')->where('business_id', $business_id)->where('type', 'customer')->pluck('id')->toArray();

        // 5. Products (15,000)
        $this->command->info("Seeding 15,000 Products...");
        $all_v_ids = [];
        for ($chunk = 0; $chunk < 30; $chunk++) {
            for ($i = 1; $i <= 500; $i++) {
                $idx = ($chunk * 500) + $i;
                $is_var = ($idx % 4 == 0);
                $p_id = DB::table('products')->insertGetId([
                    'name' => 'Produk Hassa Super '.$idx, 'business_id' => $business_id, 'type' => ($is_var ? 'variable' : 'single'), 'unit_id' => $all_u_ids[array_rand($all_u_ids)],
                    'brand_id' => $brand_ids[array_rand($brand_ids)], 'category_id' => $cat_ids[array_rand($cat_ids)], 'tax' => $tax_id,
                    'enable_stock' => 1, 'sku' => 'SKU-'.str_pad($idx, 7, '0', STR_PAD_LEFT), 'created_by' => $user_id, 'created_at' => $today
                ]);
                DB::table('product_locations')->insert([['product_id' => $p_id, 'location_id' => $loc1], ['product_id' => $p_id, 'location_id' => $loc2]]);

                if (!$is_var) {
                    $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'is_dummy' => 1]);
                    $buy = rand(5, 500) * 1000; $sell = $buy * 1.25;
                    $v_id = DB::table('variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'sub_sku' => 'SKU-'.str_pad($idx, 7, '0', STR_PAD_LEFT), 'product_variation_id' => $pv_id, 'default_purchase_price' => $buy, 'dpp_inc_tax' => $buy * 1.11, 'profit_percent' => 25, 'default_sell_price' => $sell, 'sell_price_inc_tax' => $sell * 1.11, 'created_at' => $today]);
                    $all_v_ids[] = ['p_id' => $p_id, 'v_id' => $v_id, 'price' => $sell];
                    DB::table('variation_location_details')->insert(['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => rand(100, 5000)]);
                } else {
                    $vt_k = array_rand($vt_map);
                    $pv_id = DB::table('product_variations')->insertGetId(['name' => $vt_k, 'product_id' => $p_id, 'is_dummy' => 0]);
                    foreach (array_slice($vt_map[$vt_k]['values'], 0, 3) as $vix => $vvl) {
                        $buy = rand(10, 1000) * 1000; $sell = $buy * 1.25;
                        $v_id = DB::table('variations')->insertGetId(['name' => $vvl, 'product_id' => $p_id, 'sub_sku' => 'SKU-'.str_pad($idx, 7, '0', STR_PAD_LEFT).'-'.$vix, 'product_variation_id' => $pv_id, 'default_purchase_price' => $buy, 'dpp_inc_tax' => $buy * 1.11, 'profit_percent' => 25, 'default_sell_price' => $sell, 'sell_price_inc_tax' => $sell * 1.11, 'created_at' => $today]);
                        $all_v_ids[] = ['p_id' => $p_id, 'v_id' => $v_id, 'price' => $sell];
                        DB::table('variation_location_details')->insert(['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => rand(100, 2000)]);
                    }
                }
            }
        }

        // 6. Transactions (30,000)
        $this->command->info("Seeding 30,000 Sales Transactions...");
        for ($chunk = 0; $chunk < 60; $chunk++) {
            for ($i = 1; $i <= 500; $i++) {
                $idx = ($chunk * 500) + $i;
                $p = $all_v_ids[array_rand($all_v_ids)];
                $dt = Carbon::now()->subDays(rand(0, 365))->format('Y-m-d H:i:s');
                $tid = DB::table('transactions')->insertGetId([
                    'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'sell', 'status' => 'final', 'payment_status' => 'paid',
                    'contact_id' => $cust_ids[array_rand($cust_ids)], 'invoice_no' => 'INV-MASS-'.time().'-'.$idx, 'transaction_date' => $dt,
                    'final_total' => $p['price'], 'created_by' => $user_id, 'created_at' => $dt
                ]);
                DB::table('transaction_sell_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'], 'quantity' => 1, 'unit_price' => $p['price'], 'unit_price_inc_tax' => $p['price'], 'created_at' => $dt]);
            }
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        $this->command->info("MASSIVE Dummy Seeder Selesai! 15rb Produk & 30rb Transaksi berhasil dibuat.");
    }
}
