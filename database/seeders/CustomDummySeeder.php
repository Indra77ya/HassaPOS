<?php

namespace Database\Seeders;

use App\NotificationTemplate;
use App\Utils\InstallUtil;
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

        // 2. Get the first existing business
        $business = DB::table('business')->first();
        if (!$business) {
            // Create a dummy business if none exists
            $productcatalogue_settings = json_encode([
                'enable_whatsapp_ordering' => 1,
                'order_receiving_whatsapp_number' => '123456789',
            ]);
            $business_id = DB::table('business')->insertGetId([
                'name' => 'Awesome Shop',
                'currency_id' => 2,
                'start_date' => '2018-01-01',
                'owner_id' => $user_id,
                'time_zone' => 'America/Phoenix',
                'fy_start_month' => 1,
                'accounting_method' => 'fifo',
                'default_profit_percent' => 25,
                'created_at' => now(),
                'productcatalogue_settings' => $productcatalogue_settings,
                'enabled_modules' => '["purchases","add_sale","pos_sale","stock_transfers","stock_adjustment","expenses","account"]',
                'ref_no_prefixes' => '{"purchase":"PO","stock_transfer":"ST","stock_adjustment":"SA","sell_return":"CN","expense":"EP","contacts":"CO","purchase_payment":"PP","sell_payment":"SP","business_location":"BL"}'
            ]);
        } else {
            $business_id = $business->id;
        }

        DB::beginTransaction();

        $today = Carbon::now()->format('Y-m-d H:i:s');

        // Portable Foreign Key Check Disable
        $driver = DB::getDriverName();
        if ($driver == 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        } elseif ($driver == 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } elseif ($driver == 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL DEFERRED');
        }

        // 3. Cleanup tables (except users and business)
        // We use TRUNCATE where possible to reset auto-increment, but for many tables TRUNCATE might fail due to FK even with checks off in some DBs.
        // delete() is safer but IDs won't reset. We MUST use dynamic IDs.
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

        // 4. Start Seeding Dummy Data linked to $user_id and $business_id

        // Business Location
        $location_id = DB::table('business_locations')->insertGetId([
            'business_id' => $business_id,
            'name' => 'Main Store',
            'landmark' => 'Linking Street',
            'country' => 'USA',
            'state' => 'Arizona',
            'city' => 'Phoenix',
            'zip_code' => '85001',
            'invoice_scheme_id' => 1,
            'invoice_layout_id' => 1,
            'sale_invoice_layout_id' => 1,
            'is_active' => 1,
            'created_at' => $today
        ]);

        // Units
        $unit_id = DB::table('units')->insertGetId(['business_id' => $business_id, 'actual_name' => 'Pieces', 'short_name' => 'Pc(s)', 'allow_decimal' => 0, 'created_by' => $user_id, 'created_at' => $today]);

        // Brands
        $brand_id = DB::table('brands')->insertGetId(['business_id' => $business_id, 'name' => 'Levis', 'created_by' => $user_id, 'created_at' => $today]);

        // Categories
        $cat_id = DB::table('categories')->insertGetId(['name' => 'Clothing', 'business_id' => $business_id, 'parent_id' => 0, 'created_by' => $user_id, 'category_type' => 'product', 'created_at' => $today]);

        // Products
        for ($i = 1; $i <= 5; $i++) {
            $product_id = DB::table('products')->insertGetId([
                'name' => 'Dummy Product ' . $i,
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => $unit_id,
                'brand_id' => $brand_id,
                'category_id' => $cat_id,
                'tax_type' => 'exclusive',
                'enable_stock' => 1,
                'sku' => 'SKU-C' . time() . $i,
                'barcode_type' => 'C128',
                'created_by' => $user_id,
                'created_at' => $today
            ]);

            $pv_id = DB::table('product_variations')->insertGetId(['name' => 'DUMMY', 'product_id' => $product_id, 'is_dummy' => 1, 'created_at' => $today]);

            $v_id = DB::table('variations')->insertGetId([
                'name' => 'DUMMY',
                'product_id' => $product_id,
                'sub_sku' => 'SKU-C' . time() . $i,
                'product_variation_id' => $pv_id,
                'default_purchase_price' => 100 * $i,
                'dpp_inc_tax' => 100 * $i,
                'profit_percent' => 25,
                'default_sell_price' => 125 * $i,
                'sell_price_inc_tax' => 125 * $i,
                'created_at' => $today
            ]);

            DB::table('product_locations')->insert(['product_id' => $product_id, 'location_id' => $location_id]);
            DB::table('variation_location_details')->insert([
                'product_id' => $product_id,
                'product_variation_id' => $pv_id,
                'variation_id' => $v_id,
                'location_id' => $location_id,
                'qty_available' => 100,
                'created_at' => $today
            ]);

            // Save first product and variation for transaction seeding
            if ($i == 1) {
                $first_product_id = $product_id;
                $first_variation_id = $v_id;
            }
        }

        // Contacts
        $contact_id = DB::table('contacts')->insertGetId(['business_id' => $business_id, 'type' => 'customer', 'name' => 'Walk-In Customer', 'is_default' => 1, 'created_by' => $user_id, 'created_at' => $today]);

        // Invoice Schemes and Layouts
        $scheme_id = DB::table('invoice_schemes')->insertGetId(['business_id' => $business_id, 'name' => 'Default', 'scheme_type' => 'blank', 'prefix' => 'INV', 'start_number' => 1, 'invoice_count' => 0, 'total_digits' => 4, 'is_default' => 1, 'created_at' => $today]);
        $layout_id = DB::table('invoice_layouts')->insertGetId(['business_id' => $business_id, 'name' => 'Default', 'is_default' => 1, 'created_at' => $today]);

        // Update business location with new IDs if needed
        DB::table('business_locations')->where('id', $location_id)->update([
            'invoice_scheme_id' => $scheme_id,
            'invoice_layout_id' => $layout_id,
            'sale_invoice_layout_id' => $layout_id
        ]);

        // Transactions (Sells)
        for ($i = 1; $i <= 3; $i++) {
            $trans_id = DB::table('transactions')->insertGetId([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'type' => 'sell',
                'status' => 'final',
                'payment_status' => 'paid',
                'contact_id' => $contact_id,
                'invoice_no' => 'SALE-' . time() . $i,
                'transaction_date' => $today,
                'total_before_tax' => 125,
                'final_total' => 125,
                'created_by' => $user_id,
                'created_at' => $today
            ]);

            DB::table('transaction_sell_lines')->insert([
                'transaction_id' => $trans_id,
                'product_id' => $first_product_id,
                'variation_id' => $first_variation_id,
                'quantity' => 1,
                'unit_price' => 125,
                'unit_price_inc_tax' => 125,
                'created_at' => $today
            ]);

            DB::table('transaction_payments')->insert([
                'transaction_id' => $trans_id,
                'amount' => 125,
                'method' => 'cash',
                'paid_on' => $today,
                'created_by' => $user_id,
                'created_at' => $today
            ]);
        }

        if ($driver == 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        } elseif ($driver == 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        // Notification templates
        if (class_exists('App\NotificationTemplate')) {
            $notification_template_data = NotificationTemplate::defaultNotificationTemplates();
            foreach ($notification_template_data as $notification_template) {
                $notification_template['business_id'] = $business_id;
                DB::table('notification_templates')->insert($notification_template);
            }
        }

        $installUtil = new InstallUtil();
        if (method_exists($installUtil, 'createExistingProductsVariationsToTemplate')) {
             $installUtil->createExistingProductsVariationsToTemplate();
        }

        DB::commit();

        $this->command->info("Dummy database created successfully linked to user: " . $user->username . " (ID: $user_id) and business ID: $business_id");
    }
}
