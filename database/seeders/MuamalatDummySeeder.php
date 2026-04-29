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
use App\User;
use App\Utils\TransactionUtil;

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

        // 2. Roles & Permissions (10 new roles)
        $this->command->info("Creating 10 Roles with Permissions...");

        $role_permissions = [
            'Manajer Wilayah' => ['access_all_locations', 'dashboard.data', 'purchase_n_sell_report.view', 'contacts_report.view', 'stock_report.view', 'tax_report.view', 'trending_product_report.view', 'register_report.view', 'expense_report.view', 'sell.view', 'purchase.view', 'product.view'],
            'Kepala Gudang Pusat' => ['product.view', 'product.create', 'product.update', 'stock_report.view', 'view_purchase_price', 'unit.view', 'category.view', 'brand.view'],
            'Supervisor Toko' => ['sell.view', 'sell.create', 'sell.update', 'product.view', 'stock_report.view', 'dashboard.data', 'customer.view'],
            'Kasir Senior' => ['sell.view', 'sell.create', 'sell.payments', 'register_report.view', 'customer.view', 'customer.create'],
            'Admin Purchasing' => ['purchase.view', 'purchase.create', 'purchase.update', 'purchase.payments', 'supplier.view', 'supplier.create', 'view_purchase_price'],
            'Sales Executive' => ['sell.view', 'sell.create', 'customer.view', 'customer.create', 'product.view'],
            'Staff Logistik' => ['product.view', 'stock_report.view', 'access_shipping'],
            'Audit Internal' => ['sell.view', 'purchase.view', 'product.view', 'supplier.view', 'customer.view', 'purchase_n_sell_report.view', 'contacts_report.view', 'stock_report.view', 'expense_report.view'],
            'Koordinator Cabang' => ['sell.view', 'product.view', 'stock_report.view', 'dashboard.data', 'expense_report.view'],
            'Admin Finance' => ['expense.access', 'expense_report.view', 'account.access', 'sell.payments', 'purchase.payments']
        ];

        foreach ($role_permissions as $rname => $permissions) {
            $role = Role::updateOrCreate(
                ['name' => $rname . '#' . $business_id, 'business_id' => $business_id],
                ['guard_name' => 'web']
            );
            $existing_permissions = Permission::whereIn('name', $permissions)->pluck('name')->toArray();
            $role->syncPermissions($existing_permissions);
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
                'business_id' => $business_id, 'location_id' => $loc_id_code, 'name' => $city['name'], 'landmark' => 'Area Komersial ' . $city['city'],
                'city' => $city['city'], 'zip_code' => $city['zip'], 'state' => $city['state'], 'country' => 'Indonesia',
                'is_active' => 1, 'invoice_scheme_id' => $invoice_scheme->id ?? 1, 'invoice_layout_id' => $invoice_layout->id ?? 1, 'created_at' => $today
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
        $password = Hash::make('123456');
        foreach ($user_data as $index => $u) {
            $uid = DB::table('users')->insertGetId([
                'surname' => 'Sdr/i', 'first_name' => $u['first'], 'last_name' => $u['last'], 'username' => $u['user'] . '_' . $business_id,
                'email' => $u['user'] . '@muamalat.test', 'password' => $password, 'business_id' => $business_id, 'allow_login' => 1, 'created_at' => $today
            ]);
            $roles_list = array_keys($role_permissions);
            $role_name = $roles_list[$index % count($roles_list)] . '#' . $business_id;
            $user_obj = User::find($uid);
            if ($user_obj) {
                $user_obj->assignRole($role_name);
                $loc_permission = 'location.' . $location_ids[$index % 5];
                Permission::findOrCreate($loc_permission, 'web');
                $user_obj->givePermissionTo($loc_permission);
            }
        }

        // 5. Units & Categories
        $u_pak = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pak', 'short_name' => 'pak', 'allow_decimal' => 0, 'created_by' => 2]);
        $cat_ids = [];
        $categories = ['Sembako', 'Elektronik', 'Alat Tulis', 'Kebutuhan Mandi', 'Camilan'];
        foreach ($categories as $cat) {
            $cat_ids[] = DB::table('categories')->insertGetId(['name' => $cat, 'business_id' => $business_id, 'category_type' => 'product', 'parent_id' => 0, 'created_by' => 2]);
        }

        // 6. Account Types & Kas Accounts
        $this->command->info("Seeding Account Types & Branch Kas Accounts...");
        $account_types_data = [
            ['name' => 'AKTIVA LANCAR', 'parent' => null],
            ['name' => 'Kas dan Setara Kas', 'parent' => 'AKTIVA LANCAR'],
            ['name' => 'Piutang Usaha', 'parent' => 'AKTIVA LANCAR'],
            ['name' => 'Persediaan Barang', 'parent' => 'AKTIVA LANCAR'],
            ['name' => 'Biaya Dibayar Dimuka', 'parent' => 'AKTIVA LANCAR'],
            ['name' => 'AKTIVA TETAP', 'parent' => null],
            ['name' => 'Tanah dan Bangunan', 'parent' => 'AKTIVA TETAP'],
            ['name' => 'Kendaraan', 'parent' => 'AKTIVA TETAP'],
            ['name' => 'Peralatan Kantor', 'parent' => 'AKTIVA TETAP'],
            ['name' => 'Akumulasi Penyusutan', 'parent' => 'AKTIVA TETAP'],
            ['name' => 'KEWAJIBAN LANCAR', 'parent' => null],
            ['name' => 'Hutang Usaha', 'parent' => 'KEWAJIBAN LANCAR'],
            ['name' => 'Hutang Pajak', 'parent' => 'KEWAJIBAN LANCAR'],
            ['name' => 'KEWAJIBAN JANGKA PANJANG', 'parent' => null],
            ['name' => 'Hutang Bank', 'parent' => 'KEWAJIBAN JANGKA PANJANG'],
            ['name' => 'EKUITAS', 'parent' => null],
            ['name' => 'Modal Pemilik', 'parent' => 'EKUITAS'],
            ['name' => 'Laba Ditahan', 'parent' => 'EKUITAS'],
        ];

        $type_map = [];
        foreach ($account_types_data as $at) {
            $parent_id = $at['parent'] ? ($type_map[$at['parent']] ?? null) : null;
            $at_id = DB::table('account_types')->insertGetId(['name' => $at['name'], 'business_id' => $business_id, 'parent_account_type_id' => $parent_id, 'created_at' => $today]);
            $type_map[$at['name']] = $at_id;
        }
        $kas_bank_type = $type_map['Kas dan Setara Kas'];
        $modal_type = $type_map['Modal Pemilik'];

        $loc_accounts = [];
        foreach ($location_ids as $lid) {
            $loc_name = DB::table('business_locations')->where('id', $lid)->value('name');
            $aid = DB::table('accounts')->insertGetId([
                'business_id' => $business_id, 'name' => 'Kas - ' . $loc_name,
                'account_number' => '101-' . $lid, 'account_type_id' => $kas_bank_type, 'created_by' => 2, 'created_at' => $today
            ]);
            $loc_accounts[$lid] = $aid;
            DB::table('account_transactions')->insert(['account_id' => $aid, 'type' => 'debit', 'sub_type' => 'opening_balance', 'amount' => 50000000, 'reff_no' => 'INIT-KAS-' . $lid, 'operation_date' => $today, 'created_by' => 2, 'created_at' => $today]);
        }

        // 7. Contacts
        $cust_ids = [];
        for ($i = 1; $i <= 20; $i++) {
            $cust_ids[] = DB::table('contacts')->insertGetId(['business_id' => $business_id, 'type' => 'customer', 'name' => 'Pelanggan ' . $i, 'first_name' => 'Pelanggan', 'last_name' => $i, 'contact_id' => 'CUST-MUA-' . $i, 'created_by' => 2, 'mobile' => '0812' . rand(10000000, 99999999), 'created_at' => $today]);
        }
        $supp_ids = [];
        for ($i = 1; $i <= 10; $i++) {
            $supp_ids[] = DB::table('contacts')->insertGetId(['business_id' => $business_id, 'type' => 'supplier', 'name' => 'Vendor ' . $i, 'first_name' => 'Vendor', 'last_name' => $i, 'contact_id' => 'SUPP-MUA-' . $i, 'created_by' => 2, 'mobile' => '0857' . rand(10000000, 99999999), 'created_at' => $today]);
        }

        // 8. Products
        $product_v_ids = [];
        for ($i = 1; $i <= 50; $i++) {
            $p_id = DB::table('products')->insertGetId(['name' => 'Produk Muamalat ' . $i, 'business_id' => $business_id, 'type' => 'single', 'unit_id' => $u_pak, 'category_id' => $cat_ids[array_rand($cat_ids)], 'tax_type' => 'exclusive', 'barcode_type' => 'C128', 'enable_stock' => 1, 'sku' => 'MUA-SKU-' . str_pad($i, 4, '0', STR_PAD_LEFT), 'created_by' => 2, 'created_at' => $today]);
            foreach ($location_ids as $lid) { DB::table('product_locations')->insert(['product_id' => $p_id, 'location_id' => $lid]); }
            $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'is_dummy' => 1]);
            $buy = rand(10, 100) * 1000; $sell = $buy * 1.2;
            $v_id = DB::table('variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $p_id, 'sub_sku' => 'MUA-SKU-' . str_pad($i, 4, '0', STR_PAD_LEFT), 'product_variation_id' => $pv_id, 'default_purchase_price' => $buy, 'dpp_inc_tax' => $buy, 'profit_percent' => 20, 'default_sell_price' => $sell, 'sell_price_inc_tax' => $sell, 'created_at' => $today]);
            $product_v_ids[] = ['p_id' => $p_id, 'v_id' => $v_id, 'buy' => $buy, 'sell' => $sell, 'pv_id' => $pv_id];
            foreach ($location_ids as $lid) { DB::table('variation_location_details')->insert(['product_id' => $p_id, 'product_variation_id' => $pv_id, 'variation_id' => $v_id, 'location_id' => $lid, 'qty_available' => 100]); }
        }

        // 9. Transactions
        $this->command->info("Seeding Purchases, Sells, Expenses, Transfers, Adjustments...");
        $exp_cat_id = DB::table('expense_categories')->insertGetId(['business_id' => $business_id, 'name' => 'Operasional Cabang']);

        foreach ($location_ids as $loc_idx => $loc) {
            $acc_id = $loc_accounts[$loc];
            for ($i = 1; $i <= 10; $i++) {
                $p = $product_v_ids[array_rand($product_v_ids)];
                $dt = Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s');
                // Purchase (Paid)
                $tid_p = DB::table('transactions')->insertGetId(['business_id' => $business_id, 'location_id' => $loc, 'type' => 'purchase', 'status' => 'received', 'payment_status' => 'paid', 'contact_id' => $supp_ids[array_rand($supp_ids)], 'ref_no' => 'PUR-' . $loc . '-' . $i, 'transaction_date' => $dt, 'total_before_tax' => $p['buy'] * 10, 'final_total' => $p['buy'] * 10, 'created_by' => 2, 'created_at' => $dt]);
                DB::table('purchase_lines')->insert(['transaction_id' => $tid_p, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'], 'quantity' => 10, 'purchase_price' => $p['buy'], 'purchase_price_inc_tax' => $p['buy'], 'created_at' => $dt]);
                $tpid_p = DB::table('transaction_payments')->insertGetId(['transaction_id' => $tid_p, 'business_id' => $business_id, 'amount' => $p['buy'] * 10, 'method' => 'cash', 'paid_on' => $dt, 'created_by' => 2, 'created_at' => $dt, 'account_id' => $acc_id]);
                DB::table('account_transactions')->insert(['account_id' => $acc_id, 'type' => 'credit', 'amount' => $p['buy'] * 10, 'operation_date' => $dt, 'created_by' => 2, 'transaction_id' => $tid_p, 'transaction_payment_id' => $tpid_p, 'created_at' => $dt]);

                // Sell (Paid)
                $tid_s = DB::table('transactions')->insertGetId(['business_id' => $business_id, 'location_id' => $loc, 'type' => 'sell', 'status' => 'final', 'payment_status' => 'paid', 'contact_id' => $cust_ids[array_rand($cust_ids)], 'invoice_no' => 'INV-' . $loc . '-' . $i, 'transaction_date' => $dt, 'total_before_tax' => $p['sell'] * 5, 'final_total' => $p['sell'] * 5, 'created_by' => 2, 'created_at' => $dt]);
                DB::table('transaction_sell_lines')->insert(['transaction_id' => $tid_s, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'], 'quantity' => 5, 'unit_price' => $p['sell'], 'unit_price_inc_tax' => $p['sell'], 'item_tax' => 0, 'unit_price_before_discount' => $p['sell'], 'created_at' => $dt]);
                $tpid_s = DB::table('transaction_payments')->insertGetId(['transaction_id' => $tid_s, 'business_id' => $business_id, 'amount' => $p['sell'] * 5, 'method' => 'cash', 'paid_on' => $dt, 'created_by' => 2, 'created_at' => $dt, 'account_id' => $acc_id]);
                DB::table('account_transactions')->insert(['account_id' => $acc_id, 'type' => 'debit', 'amount' => $p['sell'] * 5, 'operation_date' => $dt, 'created_by' => 2, 'transaction_id' => $tid_s, 'transaction_payment_id' => $tpid_s, 'created_at' => $dt]);
            }
            // Sell (Unpaid)
            for ($i = 1; $i <= 2; $i++) {
                $p = $product_v_ids[array_rand($product_v_ids)];
                $tid_su = DB::table('transactions')->insertGetId(['business_id' => $business_id, 'location_id' => $loc, 'type' => 'sell', 'status' => 'final', 'payment_status' => 'due', 'contact_id' => $cust_ids[array_rand($cust_ids)], 'invoice_no' => 'INV-DUE-' . $loc . '-' . $i, 'transaction_date' => $today, 'total_before_tax' => $p['sell'] * 2, 'final_total' => $p['sell'] * 2, 'created_by' => 2, 'created_at' => $today]);
                DB::table('transaction_sell_lines')->insert(['transaction_id' => $tid_su, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'], 'quantity' => 2, 'unit_price' => $p['sell'], 'unit_price_inc_tax' => $p['sell'], 'item_tax' => 0, 'unit_price_before_discount' => $p['sell'], 'created_at' => $today]);
            }
            // Purchase (Unpaid)
            for ($i = 1; $i <= 2; $i++) {
                $p = $product_v_ids[array_rand($product_v_ids)];
                $tid_pu = DB::table('transactions')->insertGetId(['business_id' => $business_id, 'location_id' => $loc, 'type' => 'purchase', 'status' => 'received', 'payment_status' => 'due', 'contact_id' => $supp_ids[array_rand($supp_ids)], 'ref_no' => 'PUR-DUE-' . $loc . '-' . $i, 'transaction_date' => $today, 'total_before_tax' => $p['buy'] * 5, 'final_total' => $p['buy'] * 5, 'created_by' => 2, 'created_at' => $today]);
                DB::table('purchase_lines')->insert(['transaction_id' => $tid_pu, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'], 'quantity' => 5, 'purchase_price' => $p['buy'], 'purchase_price_inc_tax' => $p['buy'], 'created_at' => $today]);
            }
            // Expenses
            for ($i = 1; $i <= 5; $i++) {
                $tid_e = DB::table('transactions')->insertGetId(['business_id' => $business_id, 'location_id' => $loc, 'type' => 'expense', 'status' => 'final', 'payment_status' => 'paid', 'ref_no' => 'EXP-' . $loc . '-' . $i, 'transaction_date' => $today, 'total_before_tax' => 50000, 'final_total' => 50000, 'expense_category_id' => $exp_cat_id, 'created_by' => 2, 'created_at' => $today]);
                $tpid_e = DB::table('transaction_payments')->insertGetId(['transaction_id' => $tid_e, 'business_id' => $business_id, 'amount' => 50000, 'method' => 'cash', 'paid_on' => $today, 'created_by' => 2, 'created_at' => $today, 'account_id' => $acc_id]);
                DB::table('account_transactions')->insert(['account_id' => $acc_id, 'type' => 'credit', 'amount' => 50000, 'operation_date' => $today, 'created_by' => 2, 'transaction_id' => $tid_e, 'transaction_payment_id' => $tpid_e, 'created_at' => $today]);
            }
            // Stock Adjustments
            for ($i = 1; $i <= 2; $i++) {
                $p = $product_v_ids[array_rand($product_v_ids)];
                $tid_sa = DB::table('transactions')->insertGetId(['business_id' => $business_id, 'location_id' => $loc, 'type' => 'stock_adjustment', 'status' => 'final', 'adjustment_type' => 'normal', 'ref_no' => 'SA-' . $loc . '-' . $i, 'transaction_date' => $today, 'total_before_tax' => $p['buy'], 'final_total' => $p['buy'], 'created_by' => 2, 'created_at' => $today]);
                DB::table('stock_adjustment_lines')->insert(['transaction_id' => $tid_sa, 'product_id' => $p['p_id'], 'variation_id' => $p['v_id'], 'quantity' => 1, 'unit_price' => $p['buy'], 'created_at' => $today]);
            }
        }

        // 10. Financial Balancing for Trial Balance & Balance Sheet
        $this->command->info("Balancing Financials PER BRANCH for absolute report parity...");
        $transactionUtil = app(TransactionUtil::class);

        foreach ($location_ids as $lid) {
            $loc_name = DB::table('business_locations')->where('id', $lid)->value('name');
            $kas_acc_id = $loc_accounts[$lid];

            $stock_details = $transactionUtil->getOpeningClosingStock($business_id, now()->format('Y-m-d'), $lid);
            $opening_stock = $stock_details['opening_stock'] ?? 0;
            $purchase_details = $transactionUtil->getPurchaseTotals($business_id, null, now()->format('Y-m-d'), $lid);
            $sell_details = $transactionUtil->getSellTotals($business_id, null, now()->format('Y-m-d'), $lid);
            $pl = $transactionUtil->getProfitLossDetails($business_id, $lid, '1970-01-01', now()->format('Y-m-d'));

            $customer_due = ($sell_details->invoice_due ?? 0) - ($pl['total_sell_return'] ?? 0);
            $supplier_due = $purchase_details->purchase_due ?? 0;

            $kas_bal = DB::table('account_transactions')->where('account_id', $kas_acc_id)->whereNull('deleted_at')
                ->select([DB::raw("SUM( IF(type='debit', amount, 0) ) as debit"), DB::raw("SUM( IF(type='credit', amount, 0) ) as credit")])->first();
            $kas_debit = $kas_bal->debit ?? 0;
            $kas_credit = $kas_bal->credit ?? 0;

            // Total Debit = Assets (Nature: Debit) + Expenses (Nature: Debit)
            $total_debit = $opening_stock + $customer_due + $kas_debit +
                           ($pl['total_purchase'] ?? 0) + ($pl['total_expense'] ?? 0) +
                           ($pl['total_sell_return'] ?? 0) + ($pl['total_adjustment'] ?? 0) +
                           ($pl['total_sell_discount'] ?? 0) + ($pl['total_reward_amount'] ?? 0) +
                           ($pl['total_sell_round_off'] > 0 ? $pl['total_sell_round_off'] : 0);

            // Total Credit = Liabilities (Nature: Credit) + Income (Nature: Credit)
            $total_credit = $supplier_due + $kas_credit + ($pl['total_sell'] ?? 0) +
                            ($pl['total_purchase_return'] ?? 0) + ($pl['total_recovered'] ?? 0) +
                            ($pl['total_purchase_discount'] ?? 0) +
                            ($pl['total_sell_round_off'] < 0 ? abs($pl['total_sell_round_off']) : 0);

            $modal_acc_id = DB::table('accounts')->insertGetId([
                'business_id' => $business_id, 'name' => 'Modal - ' . $loc_name,
                'account_number' => '301-' . $lid, 'account_type_id' => $modal_type, 'created_by' => 2, 'created_at' => $today
            ]);

            $balancing_modal = $total_debit - $total_credit;
            DB::table('account_transactions')->insert([
                'account_id' => $modal_acc_id, 'type' => ($balancing_modal >= 0 ? 'credit' : 'debit'),
                'sub_type' => 'opening_balance', 'amount' => abs($balancing_modal),
                'reff_no' => 'BAL-TB-' . $lid, 'operation_date' => $today, 'created_by' => 2, 'created_at' => $today
            ]);

            $this->command->info("Branch $loc_name balanced. Modal adjustment: " . number_format($balancing_modal));
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        $this->command->info("Muamalat Dummy Seeder Selesai! Data rapi dan seimbang secara akuntansi.");
    }
}
