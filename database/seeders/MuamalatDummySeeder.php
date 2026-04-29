<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MuamalatDummySeeder extends Seeder
{
    public function run()
    {
        // 1. Get user ID 2 & its business
        $owner = DB::table('users')->where('id', 2)->first();
        if (!$owner) {
            $this->command->error("User ID 2 not found. Please make sure the user exists.");
            return;
        }

        $business_id = $owner->business_id;
        if (!$business_id) {
            $this->command->error("User ID 2 is not associated with any business.");
            return;
        }

        $this->command->info("Seeding data for Business ID: $business_id (Owner: $owner->first_name)");

        $today = Carbon::now()->format('Y-m-d H:i:s');
        $driver = DB::getDriverName();
        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 0'); }

        // 2. Roles (10 new roles)
        $this->command->info("Creating 10 Roles...");
        $role_names = [
            'Manajer Wilayah', 'Kepala Gudang Pusat', 'Supervisor Toko',
            'Kasir Senior', 'Admin Purchasing', 'Sales Executive',
            'Staff Logistik', 'Audit Internal', 'Koordinator Cabang', 'Admin Finance'
        ];
        $role_ids = [];
        foreach ($role_names as $rname) {
            $role = Role::updateOrCreate(
                ['name' => $rname . '#' . $business_id, 'business_id' => $business_id],
                ['guard_name' => 'web']
            );
            $role_ids[] = $role->id;
        }

        // 3. Business Locations (5 new branches)
        $this->command->info("Creating 5 Business Locations...");
        $cities = [
            ['name' => 'Cabang Surabaya', 'city' => 'Surabaya', 'state' => 'Jawa Timur', 'zip' => '60111'],
            ['name' => 'Cabang Yogyakarta', 'city' => 'Yogyakarta', 'state' => 'DIY', 'zip' => '55111'],
            ['name' => 'Cabang Makassar', 'city' => 'Makassar', 'state' => 'Sulawesi Selatan', 'zip' => '90111'],
            ['name' => 'Cabang Medan', 'city' => 'Medan', 'state' => 'Sumatera Utara', 'zip' => '20111'],
            ['name' => 'Cabang Balikpapan', 'city' => 'Balikpapan', 'state' => 'Kalimantan Timur', 'zip' => '76111'],
        ];

        $location_ids = [];
        $loc_count = DB::table('business_locations')->where('business_id', $business_id)->count();

        $invoice_scheme = DB::table('invoice_schemes')->where('business_id', $business_id)->first();
        $invoice_layout = DB::table('invoice_layouts')->where('business_id', $business_id)->first();

        foreach ($cities as $index => $city) {
            $loc_id_code = 'BL' . str_pad($loc_count + $index + 1, 4, '0', STR_PAD_LEFT);
            $location_ids[] = DB::table('business_locations')->insertGetId([
                'business_id' => $business_id,
                'location_id' => $loc_id_code,
                'name' => $city['name'],
                'landmark' => 'Area Komersial ' . $city['city'],
                'city' => $city['city'],
                'zip_code' => $city['zip'],
                'state' => $city['state'],
                'country' => 'Indonesia',
                'is_active' => 1,
                'invoice_scheme_id' => $invoice_scheme->id ?? 1,
                'invoice_layout_id' => $invoice_layout->id ?? 1,
                'created_at' => $today
            ]);
        }

        // 4. Users (6 new users)
        $this->command->info("Creating 6 Users...");
        $user_data = [
            ['first' => 'Ahmad', 'last' => 'Hidayat', 'user' => 'ahmad_h'],
            ['first' => 'Siti', 'last' => 'Aminah', 'user' => 'siti_a'],
            ['first' => 'Bambang', 'last' => 'Pamungkas', 'user' => 'bambang_p'],
            ['first' => 'Dewi', 'last' => 'Lestari', 'user' => 'dewi_l'],
            ['first' => 'Eko', 'last' => 'Prasetyo', 'user' => 'eko_p'],
            ['first' => 'Farida', 'last' => 'Utami', 'user' => 'farida_u'],
        ];
        $new_user_ids = [];
        $password = Hash::make('123456');
        foreach ($user_data as $index => $u) {
            $uid = DB::table('users')->insertGetId([
                'surname' => 'Sdr/i',
                'first_name' => $u['first'],
                'last_name' => $u['last'],
                'username' => $u['user'] . '_' . $business_id,
                'email' => $u['user'] . '@muamalat.test',
                'password' => $password,
                'business_id' => $business_id,
                'allow_login' => 1,
                'created_at' => $today
            ]);
            $new_user_ids[] = $uid;

            // Assign random role from the 10 created
            $role_name = $role_names[array_rand($role_names)] . '#' . $business_id;
            $user_obj = \App\User::find($uid);
            $user_obj->assignRole($role_name);

            // Give location permission
            $loc_permission = 'location.' . $location_ids[$index % 5];
            Permission::findOrCreate($loc_permission, 'web');
            $user_obj->givePermissionTo($loc_permission);
        }

        // 5. Units & Categories
        $this->command->info("Adding extra Master Data...");
        $u_dus = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Dus', 'short_name' => 'dus', 'allow_decimal' => 0, 'created_by' => 2]);
        $u_pak = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pak', 'short_name' => 'pak', 'allow_decimal' => 0, 'created_by' => 2]);

        $cat_ids = [];
        $categories = ['Sembako', 'Elektronik Rumah Tangga', 'Alat Tulis Kantor', 'Kebutuhan Mandi', 'Camilan Nusantara'];
        foreach ($categories as $cat) {
            $cat_ids[] = DB::table('categories')->insertGetId([
                'name' => $cat, 'business_id' => $business_id, 'category_type' => 'product', 'parent_id' => 0, 'created_by' => 2
            ]);
        }

        // 6. Contacts (20 Customers, 10 Suppliers)
        $this->command->info("Seeding 30 Contacts...");
        $cust_ids = [];
        for ($i = 1; $i <= 20; $i++) {
            $cust_ids[] = DB::table('contacts')->insertGetId([
                'business_id' => $business_id, 'type' => 'customer', 'name' => 'Pelanggan Setia ' . $i,
                'first_name' => 'Pelanggan', 'last_name' => 'Setia ' . $i,
                'contact_id' => 'CUST-MUA-' . $i, 'created_by' => 2, 'mobile' => '0812' . rand(10000000, 99999999), 'created_at' => $today
            ]);
        }
        $supp_ids = [];
        for ($i = 1; $i <= 10; $i++) {
            $supp_ids[] = DB::table('contacts')->insertGetId([
                'business_id' => $business_id, 'type' => 'supplier', 'name' => 'Vendor Utama ' . $i,
                'first_name' => 'Vendor', 'last_name' => 'Utama ' . $i,
                'contact_id' => 'SUPP-MUA-' . $i, 'created_by' => 2, 'mobile' => '0857' . rand(10000000, 99999999), 'created_at' => $today
            ]);
        }

        // 7. Products (50)
        $this->command->info("Seeding 50 Products...");
        $product_v_ids = [];
        for ($i = 1; $i <= 50; $i++) {
            $p_id = DB::table('products')->insertGetId([
                'name' => 'Produk Muamalat ' . $i, 'business_id' => $business_id, 'type' => 'single', 'unit_id' => $u_pak,
                'category_id' => $cat_ids[array_rand($cat_ids)], 'tax_type' => 'exclusive', 'barcode_type' => 'C128',
                'enable_stock' => 1, 'sku' => 'MUA-SKU-' . str_pad($i, 4, '0', STR_PAD_LEFT), 'created_by' => 2, 'created_at' => $today
            ]);

            // Assign to all 5 new locations
            foreach ($location_ids as $lid) {
                DB::table('product_locations')->insert(['product_id' => $p_id, 'location_id' => $lid]);
            }

            $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'is_dummy' => 1]);
            $buy = rand(10, 200) * 1000; $sell = $buy * 1.2;
            $v_id = DB::table('variations')->insertGetId([
                'name' => 'DUMMY', 'product_id' => $p_id, 'sub_sku' => 'MUA-SKU-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'product_variation_id' => $pv_id, 'default_purchase_price' => $buy, 'dpp_inc_tax' => $buy,
                'profit_percent' => 20, 'default_sell_price' => $sell, 'sell_price_inc_tax' => $sell, 'created_at' => $today
            ]);
            $product_v_ids[] = ['p_id' => $p_id, 'v_id' => $v_id, 'buy' => $buy, 'sell' => $sell];

            // Stock for each location
            foreach ($location_ids as $lid) {
                DB::table('variation_location_details')->insert([
                    'product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id,
                    'location_id' => $lid, 'qty_available' => rand(50, 200)
                ]);
            }
        }

        // 8. Transactions (Purchases, Sells, Transfers)
        $this->command->info("Seeding Transactions (Purchases & Sells)...");
        for ($i = 1; $i <= 50; $i++) {
            $p = $product_v_ids[array_rand($product_v_ids)];
            $loc = $location_ids[array_rand($location_ids)];
            $dt = Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s');

            // Purchase
            $tid_p = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc, 'type' => 'purchase', 'status' => 'received',
                'payment_status' => 'paid', 'contact_id' => $supp_ids[array_rand($supp_ids)],
                'ref_no' => 'PUR-MUA-' . Str::random(4) . $i, 'transaction_date' => $dt,
                'total_before_tax' => $p['buy'] * 10, 'final_total' => $p['buy'] * 10, 'created_by' => 2, 'created_at' => $dt
            ]);
            DB::table('purchase_lines')->insert([
                'transaction_id' => $tid_p, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'],
                'quantity' => 10, 'purchase_price' => $p['buy'], 'purchase_price_inc_tax' => $p['buy'], 'created_at' => $dt
            ]);

            // Sell
            $tid_s = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc, 'type' => 'sell', 'status' => 'final',
                'payment_status' => 'paid', 'contact_id' => $cust_ids[array_rand($cust_ids)],
                'invoice_no' => 'INV-MUA-' . Str::random(4) . $i, 'transaction_date' => $dt,
                'total_before_tax' => $p['sell'] * 2, 'final_total' => $p['sell'] * 2, 'created_by' => 2, 'created_at' => $dt
            ]);
            DB::table('transaction_sell_lines')->insert([
                'transaction_id' => $tid_s, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'],
                'quantity' => 2, 'unit_price' => $p['sell'], 'unit_price_inc_tax' => $p['sell'],
                'item_tax' => 0, 'unit_price_before_discount' => $p['sell'], 'created_at' => $dt
            ]);
            DB::table('transaction_payments')->insert([
                'transaction_id' => $tid_s, 'business_id' => $business_id, 'amount' => $p['sell'] * 2,
                'method' => 'cash', 'paid_on' => $dt, 'created_by' => 2, 'created_at' => $dt
            ]);
        }

        // 9. Stock Transfers (10)
        $this->command->info("Seeding Stock Transfers...");
        for ($i = 1; $i <= 10; $i++) {
            $p = $product_v_ids[array_rand($product_v_ids)];
            $from = $location_ids[0];
            $to = $location_ids[1];
            $qty = 5;

            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $from, 'type' => 'sell_transfer', 'status' => 'final',
                'ref_no' => 'ST-MUA-' . $i, 'transaction_date' => $today, 'total_before_tax' => $p['buy'] * $qty,
                'final_total' => $p['buy'] * $qty, 'created_by' => 2, 'created_at' => $today
            ]);
            DB::table('transactions')->insert([
                'business_id' => $business_id, 'location_id' => $to, 'type' => 'purchase_transfer', 'status' => 'received',
                'ref_no' => 'ST-MUA-' . $i, 'transaction_date' => $today, 'total_before_tax' => $p['buy'] * $qty,
                'final_total' => $p['buy'] * $qty, 'transfer_parent_id' => $tid, 'created_by' => 2, 'created_at' => $today
            ]);
        }

        // 10. Stock Adjustments (10)
        $this->command->info("Seeding Stock Adjustments...");
        for ($i = 1; $i <= 10; $i++) {
            $p = $product_v_ids[array_rand($product_v_ids)];
            $loc = $location_ids[array_rand($location_ids)];
            $qty = rand(1, 5);

            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $loc, 'type' => 'stock_adjustment', 'status' => 'final',
                'adjustment_type' => (rand(0, 1) ? 'normal' : 'abnormal'), 'ref_no' => 'SA-MUA-' . $i,
                'transaction_date' => $today, 'total_before_tax' => $p['buy'] * $qty,
                'final_total' => $p['buy'] * $qty, 'created_by' => 2, 'created_at' => $today
            ]);

            DB::table('stock_adjustment_lines')->insert([
                'transaction_id' => $tid, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'],
                'quantity' => $qty, 'unit_price' => $p['buy'], 'created_at' => $today
            ]);
        }

        // 11. Expenses (10)
        $this->command->info("Seeding Expenses...");
        $exp_cat = DB::table('expense_categories')->where('business_id', $business_id)->first();
        if (!$exp_cat) {
            $exp_cat_id = DB::table('expense_categories')->insertGetId(['business_id' => $business_id, 'name' => 'Operasional Cabang']);
        } else {
            $exp_cat_id = $exp_cat->id;
        }

        for ($i = 1; $i <= 10; $i++) {
            $tid = DB::table('transactions')->insertGetId([
                'business_id' => $business_id, 'location_id' => $location_ids[array_rand($location_ids)],
                'type' => 'expense', 'status' => 'final', 'payment_status' => 'paid',
                'ref_no' => 'EXP-MUA-' . $i, 'transaction_date' => $today, 'total_before_tax' => 50000,
                'final_total' => 50000, 'expense_category_id' => $exp_cat_id, 'created_by' => 2, 'created_at' => $today
            ]);
            DB::table('transaction_payments')->insert([
                'transaction_id' => $tid, 'business_id' => $business_id, 'amount' => 50000,
                'method' => 'cash', 'paid_on' => $today, 'created_by' => 2, 'created_at' => $today
            ]);
        }

        // 11. Payment Accounts
        $this->command->info("Seeding Payment Accounts...");
        $acc_types = DB::table('account_types')->where('business_id', $business_id)->pluck('id')->toArray();
        if (empty($acc_types)) {
            $atid = DB::table('account_types')->insertGetId(['name' => 'Assets', 'business_id' => $business_id]);
            $acc_types = [$atid];
        }

        foreach ($location_ids as $lid) {
            $loc_name = DB::table('business_locations')->where('id', $lid)->value('name');
            $aid = DB::table('accounts')->insertGetId([
                'business_id' => $business_id, 'name' => 'Kas Toko - ' . $loc_name,
                'account_number' => 'ACC-' . $lid, 'account_type_id' => $acc_types[0], 'created_by' => 2, 'created_at' => $today
            ]);

            // Opening balance for account
            DB::table('account_transactions')->insert([
                'account_id' => $aid, 'type' => 'credit', 'sub_type' => 'opening_balance',
                'amount' => 10000000, 'reff_no' => 'OB-MUA-' . $lid,
                'operation_date' => $today, 'created_by' => 2, 'created_at' => $today
            ]);
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        $this->command->info("Muamalat Dummy Seeder Selesai! 10 Roles, 5 Cabang, 6 User, 50 Produk, dan Transaksi telah ditambahkan.");
    }
}
