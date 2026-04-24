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

        // 2. Business setup
        $business = DB::table('business')->first();
        $all_modules = '["purchases","add_sale","pos_sale","stock_transfers","stock_adjustment","expenses","account","subscription","service_staff","tables","modifiers","kitchen","booking","types_of_service","product_catalogue","repair"]';

        if (!$business) {
            $business_id = DB::table('business')->insertGetId([
                'name' => 'Hassa Mega Store', 'currency_id' => 54, 'start_date' => '2023-01-01', 'owner_id' => $user_id, 'time_zone' => 'Asia/Jakarta',
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

        // 3. Cleanup
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

        // 4. Seeding

        // Locations
        $loc1 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Hassa POS Jakarta', 'city' => 'Jakarta', 'is_active' => 1, 'created_at' => $today]);
        $loc2 = DB::table('business_locations')->insertGetId(['business_id' => $business_id, 'name' => 'Hassa POS Bekasi', 'city' => 'Bekasi', 'is_active' => 1, 'created_at' => $today]);

        // Variation Templates (Extremely High Variety)
        $variation_templates_data = [
            'Warna Utama' => ['Merah', 'Biru', 'Hijau', 'Kuning', 'Hitam', 'Putih', 'Abu-abu', 'Cokelat', 'Ungu', 'Oranye', 'Pink', 'Emas', 'Perak'],
            'Ukuran Pakaian' => ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'All Size'],
            'Ukuran Celana (Nomor)' => ['27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '38', '40'],
            'Ukuran Sepatu (EU)' => ['35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'],
            'Rasa Makanan/Minuman' => ['Original', 'Cokelat', 'Vanilla', 'Stroberi', 'Keju', 'Balado', 'Pedas Mampus', 'Asin Gurih', 'BBQ', 'Jagung Bakar', 'Madu'],
            'Bahan Material' => ['Katun 30s', 'Katun 24s', 'Polyester', 'Denim', 'Kulit Sintetis', 'Kulit Asli', 'Plastik ABS', 'Aluminium', 'Stainless Steel', 'Kayu Jati'],
            'Kapasitas Penyimpanan' => ['16GB', '32GB', '64GB', '128GB', '256GB', '512GB', '1TB', '2TB', '4TB'],
            'Memori RAM' => ['1GB', '2GB', '3GB', '4GB', '6GB', '8GB', '12GB', '16GB', '32GB', '64GB'],
            'Tipe Kemasan' => ['Sachet', 'Pouch', 'Botol 330ml', 'Botol 600ml', 'Botol 1.5L', 'Dus Kecil', 'Dus Besar', 'Pack Isi 10', 'Kiloan', 'Curah'],
            'Voltase & Daya' => ['5W', '10W', '15W', '20W', '40W', '60W', '100W', '110V', '220V'],
            'Tipe Koneksi' => ['Wired', 'Wireless', 'Bluetooth 5.0', 'Bluetooth 5.2', 'USB-C', 'Lightning', 'Micro USB'],
            'Lama Garansi' => ['Tanpa Garansi', '1 Bulan', '3 Bulan', '6 Bulan', '1 Tahun', '2 Tahun', '5 Tahun', 'Lifetime']
        ];

        $vt_map = [];
        foreach ($variation_templates_data as $name => $values) {
            $vt_id = DB::table('variation_templates')->insertGetId(['name' => $name, 'business_id' => $business_id]);
            foreach ($values as $v) {
                DB::table('variation_value_templates')->insert(['name' => $v, 'variation_template_id' => $vt_id]);
            }
            $vt_map[$name] = ['id' => $vt_id, 'values' => $values];
        }

        // Entities
        $tax_id = DB::table('tax_rates')->insertGetId(['business_id' => $business_id, 'name' => 'PPN 11%', 'amount' => 11, 'created_by' => $user_id]);
        $u_pcs = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pieces', 'short_name' => 'pcs', 'created_by' => $user_id]);
        $b_ids = [];
        $brands_list = ['Indofood', 'Unilever', 'Wings', 'Mayora', 'ABC', 'Nestle', 'Aqua', 'Samsung', 'Oppo', 'Vivo', 'Xiaomi', 'Polytron', 'Sharp', 'LG', 'Philips', 'Dji', 'Asus', 'Acer', 'HP', 'Lenovo'];
        foreach ($brands_list as $b) { $b_ids[] = DB::table('brands')->insertGetId(['business_id' => $business_id, 'name' => $b, 'created_by' => $user_id]); }
        $c_ids = [];
        $categories_list = ['Sembako', 'Makanan Ringan', 'Minuman', 'Elektronik', 'Kebutuhan Mandi', 'Rokok', 'Alat Tulis Kantor', 'Obat Umum', 'Pakaian Pria', 'Pakaian Wanita', 'Komputer', 'Smartphone', 'Kamera', 'Audio', 'Perkakas'];
        foreach ($categories_list as $c) { $c_ids[] = DB::table('categories')->insertGetId(['name' => $c, 'business_id' => $business_id, 'parent_id' => 0, 'created_by' => $user_id, 'category_type' => 'product']); }

        // Customer Groups (1000)
        $cg_ids = [];
        for ($i = 1; $i <= 1000; $i++) { $cg_ids[] = DB::table('customer_groups')->insertGetId(['business_id' => $business_id, 'name' => 'Grup Pelanggan VIP ' . $i, 'amount' => rand(1, 25), 'created_by' => $user_id]); }

        // Contacts (1000 Customers, 1000 Suppliers)
        $cust_ids = [];
        for ($i = 1; $i <= 1000; $i++) {
            $cust_ids[] = DB::table('contacts')->insertGetId([
                'business_id' => $business_id, 'type' => 'customer', 'name' => 'Pelanggan Hassa ' . $i,
                'contact_id' => 'C-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'customer_group_id' => $cg_ids[array_rand($cg_ids)],
                'is_default' => ($i==1), 'created_by' => $user_id, 'created_at' => $today, 'mobile' => '081' . rand(100000000, 999999999)
            ]);
            DB::table('contacts')->insert([
                'business_id' => $business_id, 'type' => 'supplier', 'name' => 'Supplier Hassa Utama ' . $i,
                'contact_id' => 'S-' . str_pad($i, 5, '0', STR_PAD_LEFT), 'created_by' => $user_id, 'created_at' => $today
            ]);
        }

        // Products (1000 items - 40% Variable for high variation demo)
        $product_list = [];
        for ($i = 1; $i <= 1000; $i++) {
            $is_variable = ($i % 2.5 == 0); // Approx 40%
            $type = $is_variable ? 'variable' : 'single';
            $price = rand(10, 10000) * 500; // 5k to 5M

            $p_id = DB::table('products')->insertGetId([
                'name' => 'Produk Super Hassa ' . $i, 'business_id' => $business_id, 'type' => $type, 'unit_id' => $u_pcs,
                'brand_id' => $b_ids[array_rand($b_ids)], 'category_id' => $c_ids[array_rand($c_ids)], 'tax' => $tax_id,
                'enable_stock' => 1, 'sku' => 'PRO-' . str_pad($i, 6, '0', STR_PAD_LEFT), 'barcode_type' => 'C128', 'created_by' => $user_id, 'created_at' => $today
            ]);

            DB::table('product_locations')->insert([['product_id' => $p_id, 'location_id' => $loc1], ['product_id' => $p_id, 'location_id' => $loc2]]);

            if (!$is_variable) {
                $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'is_dummy' => 1]);
                $v_id = DB::table('variations')->insertGetId([
                    'name' => 'DUMMY', 'product_id' => $p_id, 'sub_sku' => 'PRO-' . str_pad($i, 6, '0', STR_PAD_LEFT), 'product_variation_id' => $pv_id,
                    'default_purchase_price' => $price * 0.75, 'dpp_inc_tax' => $price * 0.75, 'profit_percent' => 25, 'default_sell_price' => $price, 'sell_price_inc_tax' => $price, 'created_at' => $today
                ]);
                DB::table('variation_location_details')->insert([
                    ['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => rand(100, 1000), 'created_at' => $today],
                    ['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc2, 'qty_available' => rand(50, 500), 'created_at' => $today]
                ]);
                if ($i <= 500) $product_list[] = ['id' => $p_id, 'v_id' => $v_id, 'price' => $price];
            } else {
                $vt_key = array_rand($vt_map);
                $vt = $vt_map[$vt_key];
                $pv_id = DB::table('product_variations')->insertGetId(['name' => $vt_key, 'product_id' => $p_id, 'is_dummy' => 0]);

                // Pick 4 random values from template for this product
                $selected_vals = (array) array_rand(array_flip($vt['values']), min(4, count($vt['values'])));
                foreach ($selected_vals as $v_idx => $v_val) {
                    $v_price = $price + ($v_idx * 10000);
                    $v_id = DB::table('variations')->insertGetId([
                        'name' => $v_val, 'product_id' => $p_id, 'sub_sku' => 'PRO-' . str_pad($i, 6, '0', STR_PAD_LEFT) . '-' . ($v_idx+1), 'product_variation_id' => $pv_id,
                        'default_purchase_price' => $v_price * 0.7, 'dpp_inc_tax' => $v_price * 0.7, 'profit_percent' => 30, 'default_sell_price' => $v_price, 'sell_price_inc_tax' => $v_price, 'created_at' => $today
                    ]);
                    DB::table('variation_location_details')->insert([
                        ['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc1, 'qty_available' => rand(50, 200), 'created_at' => $today],
                        ['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $loc2, 'qty_available' => rand(20, 100), 'created_at' => $today]
                    ]);
                    if ($i <= 500) $product_list[] = ['id' => $p_id, 'v_id' => $v_id, 'price' => $v_price];
                }
            }
        }

        // Transactions (1000 Sales)
        for ($i = 1; $i <= 1000; $i++) {
            $p = $product_list[array_rand($product_list)];
            $q = rand(1, 5); $total = $p['price'] * $q;
            $dt = Carbon::now()->subDays(rand(0, 180))->format('Y-m-d H:i:s');
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc1, 'type' => 'sell', 'status' => 'final', 'payment_status' => 'paid', 'contact_id' => $cust_ids[array_rand($cust_ids)],
                'invoice_no' => 'INV-' . time() . '-' . $i, 'transaction_date' => $dt, 'total_before_tax' => $total, 'final_total' => $total, 'created_by' => $user_id, 'created_at' => $dt
            ]);
            DB::table('transaction_sell_lines')->insert(['transaction_id' => $tid, 'product_id' => $p['id'], 'variation_id' => $p['v_id'], 'quantity' => $q, 'unit_price' => $p['price'], 'unit_price_inc_tax' => $p['price'], 'created_at' => $dt]);
            DB::table('transaction_payments')->insert(['transaction_id' => $tid, 'amount' => $total, 'method' => 'cash', 'paid_on' => $dt, 'created_by' => $user_id]);
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        DB::commit();
        $this->command->info("Dummy Database Skala Ultra Masif (IDR) Selesai:");
        $this->command->info("- 1000 Produk (Banyak Variasi), 1000 Pelanggan, 1000 Supplier");
        $this->command->info("- 12 Template Variasi (Warna, Ukuran, Rasa, RAM, Storage, dll)");
        $this->command->info("- 1000 Penjualan, 1000 Grup Pelanggan");
    }
}
