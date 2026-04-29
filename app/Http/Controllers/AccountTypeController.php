<?php

namespace App\Http\Controllers;

use App\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        $account_types = AccountType::where('business_id', $business_id)
                                     ->whereNull('parent_account_type_id')
                                     ->get();

        return view('account_types.create')
                ->with(compact('account_types'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'parent_account_type_id']);
            $input['business_id'] = $request->session()->get('user.business_id');

            AccountType::create($input);
            $output = ['success' => true,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\AccountType  $accountType
     * @return \Illuminate\Http\Response
     */
    public function show(AccountType $accountType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AccountType  $accountType
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        $account_type = AccountType::where('business_id', $business_id)
                                     ->findOrFail($id);

        $account_types = AccountType::where('business_id', $business_id)
                                     ->whereNull('parent_account_type_id')
                                     ->get();

        return view('account_types.edit')
                ->with(compact('account_types', 'account_type'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AccountType  $accountType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'parent_account_type_id']);
            $business_id = $request->session()->get('user.business_id');

            $account_type = AccountType::where('business_id', $business_id)
                                     ->findOrFail($id);

            //Account type is changed to subtype update all its sub type's parent type
            if (empty($account_type->parent_account_type_id) && ! empty($input['parent_account_type_id'])) {
                AccountType::where('business_id', $business_id)
                        ->where('parent_account_type_id', $account_type->id)
                        ->update(['parent_account_type_id' => $input['parent_account_type_id']]);
            }

            $account_type->update($input);

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AccountType  $accountType
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        AccountType::where('business_id', $business_id)
                                     ->where('id', $id)
                                     ->delete();

        //Upadete parent account if set
        AccountType::where('business_id', $business_id)
                 ->where('parent_account_type_id', $id)
                 ->update(['parent_account_type_id' => null]);

        $output = ['success' => true,
            'msg' => __('lang_v1.deleted_success'),
        ];

        return redirect()->back()->with('status', $output);
    }

    /**
     * Add default account types for the business.
     *
     * @return \Illuminate\Http\Response
     */
    public function seedDefault()
    {
        if (! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = session()->get('user.business_id');

            $default_types = [
                // 1. AKTIVA LANCAR
                ['name' => 'AKTIVA LANCAR', 'parent' => null],
                ['name' => 'Kas dan Setara Kas', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Bank', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Piutang Usaha', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Piutang Lain-lain', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Persediaan Barang Dagang', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Persediaan Bahan Baku', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Persediaan Bahan Pembantu', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Perlengkapan Toko', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Perlengkapan Kantor', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Biaya Dibayar Dimuka', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Sewa Dibayar Dimuka', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Asuransi Dibayar Dimuka', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Pajak Dibayar Dimuka (PPN In)', 'parent' => 'AKTIVA LANCAR'],
                ['name' => 'Uang Muka Pembelian', 'parent' => 'AKTIVA LANCAR'],

                // 2. AKTIVA TETAP
                ['name' => 'AKTIVA TETAP', 'parent' => null],
                ['name' => 'Tanah', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Bangunan', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Akumulasi Penyusutan Bangunan', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Kendaraan', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Akumulasi Penyusutan Kendaraan', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Peralatan Kantor', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Akumulasi Penyusutan Peralatan Kantor', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Mesin & Peralatan', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Akumulasi Penyusutan Mesin', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Inventaris Toko', 'parent' => 'AKTIVA TETAP'],
                ['name' => 'Akumulasi Penyusutan Inventaris Toko', 'parent' => 'AKTIVA TETAP'],

                // 3. KEWAJIBAN LANCAR
                ['name' => 'KEWAJIBAN LANCAR', 'parent' => null],
                ['name' => 'Hutang Usaha', 'parent' => 'KEWAJIBAN LANCAR'],
                ['name' => 'Hutang Gaji', 'parent' => 'KEWAJIBAN LANCAR'],
                ['name' => 'Hutang Listrik, Air & Telepon', 'parent' => 'KEWAJIBAN LANCAR'],
                ['name' => 'Hutang Pajak (PPN Out)', 'parent' => 'KEWAJIBAN LANCAR'],
                ['name' => 'Hutang PPh 21', 'parent' => 'KEWAJIBAN LANCAR'],
                ['name' => 'Uang Muka Penjualan', 'parent' => 'KEWAJIBAN LANCAR'],
                ['name' => 'Hutang Biaya Lainnya', 'parent' => 'KEWAJIBAN LANCAR'],

                // 4. KEWAJIBAN JANGKA PANJANG
                ['name' => 'KEWAJIBAN JANGKA PANJANG', 'parent' => null],
                ['name' => 'Hutang Bank (Long Term)', 'parent' => 'KEWAJIBAN JANGKA PANJANG'],
                ['name' => 'Hutang Pembiayaan Kendaraan', 'parent' => 'KEWAJIBAN JANGKA PANJANG'],
                ['name' => 'Hutang Jangka Panjang Lainnya', 'parent' => 'KEWAJIBAN JANGKA PANJANG'],

                // 5. EKUITAS
                ['name' => 'EKUITAS', 'parent' => null],
                ['name' => 'Modal Pemilik', 'parent' => 'EKUITAS'],
                ['name' => 'Prive / Pengambilan Pribadi', 'parent' => 'EKUITAS'],
                ['name' => 'Laba Ditahan', 'parent' => 'EKUITAS'],
                ['name' => 'Laba Tahun Berjalan', 'parent' => 'EKUITAS'],

                // 6. PENDAPATAN
                ['name' => 'PENDAPATAN', 'parent' => null],
                ['name' => 'Pendapatan Penjualan', 'parent' => 'PENDAPATAN'],
                ['name' => 'Retur Penjualan', 'parent' => 'PENDAPATAN'],
                ['name' => 'Potongan Penjualan', 'parent' => 'PENDAPATAN'],
                ['name' => 'Pendapatan Jasa / Service', 'parent' => 'PENDAPATAN'],
                ['name' => 'Pendapatan Lain-lain', 'parent' => 'PENDAPATAN'],

                // 7. HARGA POKOK PENJUALAN
                ['name' => 'HARGA POKOK PENJUALAN', 'parent' => null],
                ['name' => 'HPP Produk', 'parent' => 'HARGA POKOK PENJUALAN'],
                ['name' => 'HPP Jasa', 'parent' => 'HARGA POKOK PENJUALAN'],
                ['name' => 'Biaya Angkut Pembelian', 'parent' => 'HARGA POKOK PENJUALAN'],
                ['name' => 'Potongan Pembelian', 'parent' => 'HARGA POKOK PENJUALAN'],

                // 8. BIAYA OPERASIONAL
                ['name' => 'BIAYA OPERASIONAL', 'parent' => null],
                ['name' => 'Biaya Gaji & Tunjangan', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Listrik, Air & Internet', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Sewa', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Pemasaran & Iklan', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Perbaikan & Pemeliharaan', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Transportasi & Bensin', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Keperluan Kantor', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Keperluan Toko', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Penyusutan Aktiva Tetap', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Adm Bank & Pajak Bunga', 'parent' => 'BIAYA OPERASIONAL'],
                ['name' => 'Biaya Operasional Lainnya', 'parent' => 'BIAYA OPERASIONAL'],
            ];

            $type_map = [];
            foreach ($default_types as $at) {
                // Check if already exists
                $exists = AccountType::where('business_id', $business_id)
                                     ->where('name', $at['name'])
                                     ->first();
                if ($exists) {
                    $type_map[$at['name']] = $exists->id;
                    continue;
                }

                $parent_id = $at['parent'] ? ($type_map[$at['parent']] ?? null) : null;
                $new_type = AccountType::create([
                    'name' => $at['name'],
                    'business_id' => $business_id,
                    'parent_account_type_id' => $parent_id
                ]);
                $type_map[$at['name']] = $new_type->id;
            }

            $output = ['success' => true,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }
}
