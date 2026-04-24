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

        // 2. Get the first existing business or create one
        $business = DB::table('business')->first();
        if (!$business) {
            $productcatalogue_settings = json_encode([
                'enable_whatsapp_ordering' => 1,
                'order_receiving_whatsapp_number' => '123456789',
            ]);
            $business_id = DB::table('business')->insertGetId([
                'name' => 'Toko Dummy Indonesia',
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
            // Update currency to IDR for existing business if seeding dummy data
            DB::table('business')->where('id', $business_id)->update(['currency_id' => 54]);
        }

        DB::beginTransaction();

        $today = Carbon::now()->format('Y-m-d H:i:s');

        // Portable Foreign Key Check Disable
        $driver = DB::getDriverName();
        if ($driver == 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        } elseif ($driver == 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        // 3. Cleanup tables (except users and business)
        $tables = [
            'brands', 'categories', 'contacts', 'products', 'product_variations', 'variations',
            'variation_location_details', 'transactions', 'transaction_payments',
            'transaction_sell_lines', 'purchase_lines', 'business_locations',
            'invoice_schemes', 'invoice_layouts', 'units', 'tax_rates', 'group_sub_taxes',
            'variation_templates', 'variation_value_templates', 'reference_counts',
            'res_tables', 'cash_register_transactions', 'hms_room_types', 'hms_rooms',
            'hms_extras', 'hms_coupons', 'hms_booking_lines', 'hms_booking_extras',
            'mfg_recipes', 'mfg_recipe_ingredients', 'gym_classes', 'gym_packages',
            'repair_device_models', 'repair_statuses', 'essentials_shifts', 'essentials_user_shifts',
            'notification_templates', 'product_locations'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        // 4. Seeding Data

        // Location
        $location_id = DB::table('business_locations')->insertGetId([
            'business_id' => $business_id,
            'name' => 'Toko Utama',
            'country' => 'Indonesia',
            'state' => 'DKI Jakarta',
            'city' => 'Jakarta Pusat',
            'zip_code' => '10110',
            'invoice_scheme_id' => 1,
            'invoice_layout_id' => 1,
            'sale_invoice_layout_id' => 1,
            'is_active' => 1,
            'created_at' => $today
        ]);

        // Units
        $unit_pcs = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pcs', 'short_name' => 'pcs', 'allow_decimal' => 0, 'created_by' => $user_id, 'created_at' => $today]);
        $unit_kg = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Kilogram', 'short_name' => 'kg', 'allow_decimal' => 1, 'created_by' => $user_id, 'created_at' => $today]);

        // Brands
        $brands = ['Indofood', 'Wings', 'Unilever', 'Aqua', 'Samsung', 'Mayora', 'ABC', 'Nestle'];
        $brand_ids = [];
        foreach ($brands as $b) {
            $brand_ids[] = DB::table('brands')->insertGetId(['business_id' => $business_id, 'name' => $b, 'created_by' => $user_id, 'created_at' => $today]);
        }

        // Categories
        $categories = ['Makanan', 'Minuman', 'Elektronik', 'Kebutuhan Rumah', 'Obat-obatan', 'Pakaian'];
        $cat_ids = [];
        foreach ($categories as $c) {
            $cat_ids[] = DB::table('categories')->insertGetId(['name' => $c, 'business_id' => $business_id, 'parent_id' => 0, 'created_by' => $user_id, 'category_type' => 'product', 'created_at' => $today]);
        }

        // Products (50 items)
        $product_list = [];
        for ($i = 1; $i <= 50; $i++) {
            $price = rand(2, 1000) * 500; // 1.000 to 500.000
            $purchase_price = $price * 0.8;

            $product_id = DB::table('products')->insertGetId([
                'name' => 'Produk Contoh ' . $i,
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => ($i % 5 == 0) ? $unit_kg : $unit_pcs,
                'brand_id' => $brand_ids[array_rand($brand_ids)],
                'category_id' => $cat_ids[array_rand($cat_ids)],
                'tax_type' => 'exclusive',
                'enable_stock' => 1,
                'sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'barcode_type' => 'C128',
                'created_by' => $user_id,
                'created_at' => $today
            ]);

            $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $product_id, 'is_dummy' => 1, 'created_at' => $today]);

            $v_id = DB::table('variations')->insertGetId([
                'name' => 'DUMMY',
                'product_id' => $product_id,
                'sub_sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'product_variation_id' => $pv_id,
                'default_purchase_price' => $purchase_price,
                'dpp_inc_tax' => $purchase_price,
                'profit_percent' => 25,
                'default_sell_price' => $price,
                'sell_price_inc_tax' => $price,
                'created_at' => $today
            ]);

            DB::table('product_locations')->insert(['product_id' => $product_id, 'location_id' => $location_id]);
            DB::table('variation_location_details')->insert([
                'product_id' => $product_id,
                'product_variation_id' => $pv_id,
                'variation_id' => $v_id,
                'location_id' => $location_id,
                'qty_available' => rand(100, 1000),
                'created_at' => $today
            ]);

            $product_list[] = ['id' => $product_id, 'variation_id' => $v_id, 'price' => $price];
        }

        // Contacts
        $contact_id = DB::table('contacts')->insertGetId(['business_id' => $business_id, 'type' => 'customer', 'name' => 'Pelanggan Umum', 'is_default' => 1, 'created_by' => $user_id, 'created_at' => $today]);
        DB::table('contacts')->insert(['business_id' => $business_id, 'type' => 'supplier', 'name' => 'Supplier Utama', 'is_default' => 0, 'created_by' => $user_id, 'created_at' => $today]);

        // Invoice
        $scheme_id = DB::table('invoice_schemes')->insertGetId(['business_id' => $business_id, 'name' => 'Default', 'scheme_type' => 'blank', 'prefix' => 'INV', 'start_number' => 1, 'invoice_count' => 0, 'total_digits' => 4, 'is_default' => 1, 'created_at' => $today]);
        $layout_id = DB::table('invoice_layouts')->insertGetId(['business_id' => $business_id, 'name' => 'Default Indonesia', 'is_default' => 1, 'created_at' => $today]);

        DB::table('business_locations')->where('id', $location_id)->update([
            'invoice_scheme_id' => $scheme_id,
            'invoice_layout_id' => $layout_id,
            'sale_invoice_layout_id' => $layout_id
        ]);

        // Transactions (30 sales)
        for ($i = 1; $i <= 30; $i++) {
            $random_product = $product_list[array_rand($product_list)];
            $qty = rand(1, 10);
            $total = $random_product['price'] * $qty;
            $sale_date = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->format('Y-m-d H:i:s');

            $trans_id = DB::table('transactions')->insertGetId([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'type' => 'sell',
                'status' => 'final',
                'payment_status' => 'paid',
                'contact_id' => $contact_id,
                'invoice_no' => 'SALE-' . Carbon::parse($sale_date)->format('Ymd') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'transaction_date' => $sale_date,
                'total_before_tax' => $total,
                'final_total' => $total,
                'created_by' => $user_id,
                'created_at' => $sale_date
            ]);

            DB::table('transaction_sell_lines')->insert([
                'transaction_id' => $trans_id,
                'product_id' => $random_product['id'],
                'variation_id' => $random_product['variation_id'],
                'quantity' => $qty,
                'unit_price' => $random_product['price'],
                'unit_price_inc_tax' => $random_product['price'],
                'created_at' => $sale_date
            ]);

            DB::table('transaction_payments')->insert([
                'transaction_id' => $trans_id,
                'amount' => $total,
                'method' => 'cash',
                'paid_on' => $sale_date,
                'created_by' => $user_id,
                'created_at' => $sale_date
            ]);
        }

        if ($driver == 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        // Notification templates - Safe check
        if (class_exists('App\NotificationTemplate') && method_exists('App\NotificationTemplate', 'defaultNotificationTemplates')) {
            $notification_template_data = \App\NotificationTemplate::defaultNotificationTemplates();
            foreach ($notification_template_data as $notification_template) {
                $notification_template['business_id'] = $business_id;
                DB::table('notification_templates')->insert($notification_template);
            }
        }

        DB::commit();

        $this->command->info("Data dummy berhasil dibuat (IDR).");
        $this->command->info("Terhubung ke user: " . $user->username);
        $this->command->info("Volume: 50 Produk, 30 Transaksi");
    }
}
