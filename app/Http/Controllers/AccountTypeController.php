<?php

namespace App\Http\Controllers;

use App\AccountType;
use Illuminate\Http\Request;

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
