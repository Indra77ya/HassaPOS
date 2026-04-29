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
        // 1. Get target business (User ID 2)
        $owner = DB::table('users')->where('id', 2)->first();
        if (!$owner) { return; }
        $business_id = $owner->business_id;

        $this->command->info("Seeding ULTRA-MASSIVE data for Business ID: $business_id");
        $today = Carbon::now()->format('Y-m-d H:i:s');
        $driver = DB::getDriverName();
        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 0'); }

        // 2. Roles & Permissions (10 roles)
        $role_permissions = [
            'Direktur Operasional' => ['access_all_locations', 'dashboard.data', 'purchase_n_sell_report.view', 'contacts_report.view', 'stock_report.view', 'tax_report.view', 'trending_product_report.view', 'register_report.view', 'expense_report.view', 'sell.view', 'purchase.view', 'product.view'],
            'Kepala Logistik' => ['product.view', 'product.create', 'product.update', 'stock_report.view', 'view_purchase_price', 'unit.view', 'category.view', 'brand.view'],
            'Store Manager' => ['sell.view', 'sell.create', 'sell.update', 'product.view', 'stock_report.view', 'dashboard.data', 'customer.view'],
            'Head Cashier' => ['sell.view', 'sell.create', 'sell.payments', 'register_report.view', 'customer.view', 'customer.create'],
            'Procurement Admin' => ['purchase.view', 'purchase.create', 'purchase.update', 'purchase.payments', 'supplier.view', 'supplier.create', 'view_purchase_price'],
            'Senior Sales' => ['sell.view', 'sell.create', 'customer.view', 'customer.create', 'product.view'],
            'Staff Gudang' => ['product.view', 'stock_report.view', 'access_shipping'],
            'Internal Auditor' => ['sell.view', 'purchase.view', 'product.view', 'supplier.view', 'customer.view', 'purchase_n_sell_report.view', 'contacts_report.view', 'stock_report.view', 'expense_report.view'],
            'Branch Coordinator' => ['sell.view', 'product.view', 'stock_report.view', 'dashboard.data', 'expense_report.view'],
            'Finance & Tax' => ['expense.access', 'expense_report.view', 'account.access', 'sell.payments', 'purchase.payments']
        ];
        foreach ($role_permissions as $rname => $permissions) {
            $role = Role::updateOrCreate(['name' => $rname . '#' . $business_id, 'business_id' => $business_id], ['guard_name' => 'web']);
            $role->syncPermissions(Permission::whereIn('name', $permissions)->pluck('name')->toArray());
        }

        // 3. Locations & Users (5 branches, 6 users)
        $location_ids = [];
        $loc_count = DB::table('business_locations')->where('business_id', $business_id)->count();
        $is = DB::table('invoice_schemes')->where('business_id', $business_id)->first();
        $il = DB::table('invoice_layouts')->where('business_id', $business_id)->first();
        $branches = [['n'=>'Pusat Jakarta','c'=>'Jakarta','s'=>'DKI','z'=>'10110'],['n'=>'Cabang Bandung','c'=>'Bandung','s'=>'Jabar','z'=>'40111'],['n'=>'Cabang Surabaya','c'=>'Surabaya','s'=>'Jatim','z'=>'60111'],['n'=>'Cabang Medan','c'=>'Medan','s'=>'Sumut','z'=>'20111'],['n'=>'Cabang Makassar','c'=>'Makassar','s'=>'Sulsel','z'=>'90111']];
        foreach ($branches as $index => $b) {
            $location_ids[] = DB::table('business_locations')->insertGetId(['business_id'=>$business_id,'location_id'=>'BL'.str_pad($loc_count+$index+1,4,'0',STR_PAD_LEFT),'name'=>$b['n'],'landmark'=>'Ruko Strategis','city'=>$b['c'],'zip_code'=>$b['z'],'state'=>$b['s'],'country'=>'Indonesia','is_active'=>1,'invoice_scheme_id'=>$is->id??1,'invoice_layout_id'=>$il->id??1,'created_at'=>$today]);
        }

        $new_u_ids = [];
        $users_info = [['f'=>'Rahmat','l'=>'Hidayat','u'=>'rahmat_h'],['f'=>'Siti','l'=>'Aminah','u'=>'siti_a'],['f'=>'Budi','l'=>'Santoso','u'=>'budi_s'],['f'=>'Dewi','l'=>'Lestari','u'=>'dewi_l'],['f'=>'Eko','l'=>'Prasetyo','u'=>'eko_p'],['f'=>'Farida','l'=>'Utami','u'=>'farida_u']];
        foreach ($users_info as $index => $u) {
            $uid = DB::table('users')->insertGetId(['surname'=>'Sdr/i','first_name'=>$u['f'],'last_name'=>$u['l'],'username'=>$u['u'].'_'.$business_id,'email'=>$u['u'].'@muamalat.test','password'=>Hash::make('123456'),'business_id'=>$business_id,'allow_login'=>1,'created_at'=>$today]);
            $new_u_ids[] = $uid;
            $user_obj = User::find($uid);
            if ($user_obj) {
                $role_name = array_keys($role_permissions)[$index % 10] . '#' . $business_id;
                $user_obj->assignRole($role_name);
                $lid = $location_ids[$index % 5];
                Permission::findOrCreate('location.' . $lid, 'web');
                $user_obj->givePermissionTo('location.' . $lid);
            }
        }

        // 4. Units & Brands & Categories
        $u_ids = [DB::table('units')->insertGetId(['business_id'=>$business_id,'actual_name'=>'Pcs','short_name'=>'pcs','allow_decimal'=>0,'created_by'=>2]), DB::table('units')->insertGetId(['business_id'=>$business_id,'actual_name'=>'Dus','short_name'=>'dus','allow_decimal'=>0,'created_by'=>2]), DB::table('units')->insertGetId(['business_id'=>$business_id,'actual_name'=>'Ikat','short_name'=>'ikt','allow_decimal'=>0,'created_by'=>2])];
        $brand_ids = [];
        foreach (['Indofood','Wings','Unilever','Samsung','Polytron'] as $b) { $brand_ids[] = DB::table('brands')->insertGetId(['business_id'=>$business_id,'name'=>$b,'created_by'=>2]); }
        $cat_ids = [];
        foreach (['Sembako','Elektronik','Alat Tulis','Camilan','Minuman','Perabotan'] as $c) { $cat_ids[] = DB::table('categories')->insertGetId(['name'=>$c,'business_id'=>$business_id,'category_type'=>'product','parent_id'=>0,'created_by'=>2]); }

        // 5. Account Types & Kas Accounts
        $ats = [['n'=>'AKTIVA LANCAR','p'=>null],['n'=>'Kas dan Bank','p'=>'AKTIVA LANCAR'],['n'=>'Piutang','p'=>'AKTIVA LANCAR'],['n'=>'Stok','p'=>'AKTIVA LANCAR'],['n'=>'AKTIVA TETAP','p'=>null],['n'=>'KEWAJIBAN','p'=>null],['n'=>'Hutang Usaha','p'=>'KEWAJIBAN'],['n'=>'EKUITAS','p'=>null],['n'=>'Modal','p'=>'EKUITAS']];
        $type_map = [];
        foreach ($ats as $at) {
            $pid = $at['p'] ? ($type_map[$at['p']] ?? null) : null;
            $type_map[$at['n']] = DB::table('account_types')->insertGetId(['name'=>$at['n'],'business_id'=>$business_id,'parent_account_type_id'=>$pid,'created_at'=>$today]);
        }
        $loc_kas_ids = [];
        foreach ($location_ids as $lid) {
            $aid = DB::table('accounts')->insertGetId(['business_id'=>$business_id,'name'=>'Kas - '.DB::table('business_locations')->where('id',$lid)->value('name'),'account_number'=>'KAS-'.$lid,'account_type_id'=>$type_map['Kas dan Bank'],'created_by'=>2,'created_at'=>$today]);
            $loc_kas_ids[$lid] = $aid;
            DB::table('account_transactions')->insert(['account_id'=>$aid,'type'=>'debit','sub_type'=>'opening_balance','amount'=>1000000000,'operation_date'=>$today,'created_by'=>2,'created_at'=>$today]);
        }

        // 6. Products (500)
        $this->command->info("Inserting 500 Products...");
        $p_v_ids = [];
        for ($i = 1; $i <= 500; $i++) {
            $pid = DB::table('products')->insertGetId(['name'=>'Barang Muamalat '.$i,'business_id'=>$business_id,'type'=>'single','unit_id'=>$u_ids[array_rand($u_ids)],'brand_id'=>$brand_ids[array_rand($brand_ids)],'category_id'=>$cat_ids[array_rand($cat_ids)],'tax_type'=>'exclusive','barcode_type'=>'C128','enable_stock'=>1,'sku'=>'MUA-'.str_pad($i,5,'0',STR_PAD_LEFT),'created_by'=>2,'created_at'=>$today]);
            foreach($location_ids as $lid){ DB::table('product_locations')->insert(['product_id'=>$pid,'location_id'=>$lid]); }
            $pvid = DB::table('product_variations')->insertGetId(['name'=>'DUMMY','product_id'=>$pid,'is_dummy'=>1]);
            $buy = rand(10,500)*1000; $sell = $buy*1.2;
            $vid = DB::table('variations')->insertGetId(['name'=>'DUMMY','product_id'=>$pid,'sub_sku'=>'MUA-'.str_pad($i,5,'0',STR_PAD_LEFT),'product_variation_id'=>$pvid,'default_purchase_price'=>$buy,'dpp_inc_tax'=>$buy,'profit_percent'=>20,'default_sell_price'=>$sell,'sell_price_inc_tax'=>$sell,'created_at'=>$today]);
            $p_v_ids[] = ['p'=>$pid,'v'=>$vid,'b'=>$buy,'s'=>$sell];
            foreach($location_ids as $lid){ DB::table('variation_location_details')->insert(['product_id'=>$pid,'product_variation_id'=>$pvid,'variation_id'=>$vid,'location_id'=>$lid,'qty_available'=>5000]); }
        }

        // 7. Contacts
        $c_ids = []; $s_ids = [];
        for($i=1;$i<=150;$i++){ $c_ids[] = DB::table('contacts')->insertGetId(['business_id'=>$business_id,'type'=>'customer','name'=>'Pelanggan '.$i,'first_name'=>'Pel','last_name'=>$i,'contact_id'=>'C-'.$i,'created_by'=>2,'mobile'=>'0812'.rand(1000,9999).rand(1000,9999)]); }
        for($i=1;$i<=50;$i++){ $s_ids[] = DB::table('contacts')->insertGetId(['business_id'=>$business_id,'type'=>'supplier','name'=>'Supplier '.$i,'first_name'=>'Sup','last_name'=>$i,'contact_id'=>'S-'.$i,'created_by'=>2,'mobile'=>'0857'.rand(1000,9999).rand(1000,9999)]); }

        // 8. Modules: Mfg, Repair, CRM, Project
        $this->command->info("Seeding Extra Modules...");
        if(Schema::hasTable('mfg_recipes')){
            foreach(array_slice($p_v_ids,0,30) as $p){
                DB::table('mfg_recipes')->insert(['product_id'=>$p['p'],'variation_id'=>$p['v'],'ingredients'=>'[]','waste_percent'=>2,'ingredients_cost'=>$p['b']*0.7,'extra_cost'=>1000,'total_quantity'=>1,'final_price'=>$p['b']]);
            }
        }
        if(Schema::hasTable('repair_job_sheets')){
            for($i=1;$i<=50;$i++){
                DB::table('repair_job_sheets')->insert(['business_id'=>$business_id,'location_id'=>$location_ids[array_rand($location_ids)],'contact_id'=>$c_ids[array_rand($c_ids)],'job_sheet_no'=>'JS-'.$i,'status_id'=>1,'created_by'=>2]);
            }
        }
        if(Schema::hasTable('pjt_projects')){
            for($i=1;$i<=10;$i++){
                DB::table('pjt_projects')->insert(['business_id'=>$business_id,'name'=>'Proyek Muamalat '.$i,'contact_id'=>$c_ids[array_rand($c_ids)],'status'=>'not_started','created_by'=>2]);
            }
        }

        // 9. Massive Transactions (5000)
        $this->command->info("Generating 5,000 Transactions...");
        $exp_cat_id = DB::table('expense_categories')->insertGetId(['business_id'=>$business_id,'name'=>'Ops Umum']);
        foreach ($location_ids as $loc) {
            $kas_id = $loc_kas_ids[$loc];
            for ($i = 1; $i <= 1000; $i++) {
                $p = $p_v_ids[array_rand($p_v_ids)];
                $date = Carbon::now()->subDays(rand(1, 180))->format('Y-m-d H:i:s');
                $paid = rand(1, 10) > 2;

                // Purchase
                $tp = DB::table('transactions')->insertGetId(['business_id'=>$business_id,'location_id'=>$loc,'type'=>'purchase','status'=>'received','payment_status'=>($paid?'paid':'due'),'contact_id'=>$s_ids[array_rand($s_ids)],'ref_no'=>'P-'.$loc.'-'.$i,'transaction_date'=>$date,'total_before_tax'=>$p['b']*50,'final_total'=>$p['b']*50,'created_by'=>2,'created_at'=>$date]);
                DB::table('purchase_lines')->insert(['transaction_id'=>$tp,'product_id'=>$p['p'],'variation_id'=>$p['v'],'quantity'=>50,'purchase_price'=>$p['b'],'purchase_price_inc_tax'=>$p['b'],'created_at'=>$date]);
                if($paid){
                    $pid = DB::table('transaction_payments')->insertGetId(['transaction_id'=>$tp,'business_id'=>$business_id,'amount'=>$p['b']*50,'method'=>'cash','paid_on'=>$date,'created_by'=>2,'account_id'=>$kas_id]);
                    DB::table('account_transactions')->insert(['account_id'=>$kas_id,'type'=>'credit','amount'=>$p['b']*50,'operation_date'=>$date,'created_by'=>2,'transaction_id'=>$tp,'transaction_payment_id'=>$pid]);
                }

                // Sell
                $ts = DB::table('transactions')->insertGetId(['business_id'=>$business_id,'location_id'=>$loc,'type'=>'sell','status'=>'final','payment_status'=>($paid?'paid':'due'),'contact_id'=>$c_ids[array_rand($c_ids)],'invoice_no'=>'S-'.$loc.'-'.$i,'transaction_date'=>$date,'total_before_tax'=>$p['s']*10,'final_total'=>$p['s']*10,'created_by'=>2,'created_at'=>$date]);
                DB::table('transaction_sell_lines')->insert(['transaction_id'=>$ts,'product_id'=>$p['p'],'variation_id'=>$p['v'],'quantity'=>10,'unit_price'=>$p['s'],'unit_price_inc_tax'=>$p['s'],'item_tax'=>0,'unit_price_before_discount'=>$p['s'],'created_at'=>$date]);
                if($paid){
                    $pid = DB::table('transaction_payments')->insertGetId(['transaction_id'=>$ts,'business_id'=>$business_id,'amount'=>$p['s']*10,'method'=>'cash','paid_on'=>$date,'created_by'=>2,'account_id'=>$kas_id]);
                    DB::table('account_transactions')->insert(['account_id'=>$kas_id,'type'=>'debit','amount'=>$p['s']*10,'operation_date'=>$date,'created_by'=>2,'transaction_id'=>$ts,'transaction_payment_id'=>$pid]);
                }
            }
        }

        // 10. Accounting Balancing
        $this->command->info("Final Balancing...");
        $tu = app(TransactionUtil::class);
        foreach ($location_ids as $lid) {
            $kas_id = $loc_kas_ids[$lid];
            $sd = $tu->getOpeningClosingStock($business_id, now()->format('Y-m-d'), $lid);
            $pd = $tu->getPurchaseTotals($business_id, null, now()->format('Y-m-d'), $lid);
            $sl = $tu->getSellTotals($business_id, null, now()->format('Y-m-d'), $lid);
            $pl = $tu->getProfitLossDetails($business_id, $lid, '1970-01-01', now()->format('Y-m-d'));
            $kb = DB::table('account_transactions')->where('account_id',$kas_id)->whereNull('deleted_at')->select([DB::raw("SUM(IF(type='debit',amount,0)) as d"),DB::raw("SUM(IF(type='credit',amount,0)) as c")])->first();
            $td = ($sd['opening_stock']??0) + (($sl->invoice_due??0)-($pl['total_sell_return']??0)) + ($kb->d??0) + ($pl['total_purchase']??0) + ($pl['total_expense']??0) + ($pl['total_sell_return']??0) + ($pl['total_adjustment']??0) + ($pl['total_sell_discount']??0) + ($pl['total_reward_amount']??0) + ($pl['total_sell_round_off']>0?$pl['total_sell_round_off']:0);
            $tc = ($pd->purchase_due??0) + ($kb->c??0) + ($pl['total_sell']??0) + ($pl['total_purchase_return']??0) + ($pl['total_recovered']??0) + ($pl['total_purchase_discount']??0) + ($pl['total_sell_round_off']<0?abs($pl['total_sell_round_off']):0);
            $mid = DB::table('accounts')->insertGetId(['business_id'=>$business_id,'name'=>'Modal Cabang '.DB::table('business_locations')->where('id',$lid)->value('name'),'account_number'=>'301-'.$lid,'account_type_id'=>$type_map['Modal'],'created_by'=>2,'created_at'=>$today]);
            $diff = $td - $tc;
            DB::table('account_transactions')->insert(['account_id'=>$mid,'type'=>($diff>=0?'credit':'debit'),'sub_type'=>'opening_balance','amount'=>abs($diff),'operation_date'=>$today,'created_by'=>2]);
        }

        if ($driver == 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS = 1'); }
        $this->command->info("SELESAI! 500 Produk & 5000 Transaksi Seimbang Secara Akuntansi.");
    }
}
