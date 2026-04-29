<?php

namespace App;

use App\Utils\Util;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'account_details' => 'array',
    ];

    public static function forDropdown($business_id, $prepend_none, $closed = false, $show_balance = false)
    {
        $query = Account::where('business_id', $business_id);

        $permitted_locations = auth()->user()->permitted_locations();
        $account_ids = [];
        if ($permitted_locations != 'all') {
            $locations = BusinessLocation::where('business_id', $business_id)
                            ->whereIn('id', $permitted_locations)
                            ->get();

            foreach ($locations as $location) {
                if (! empty($location->default_payment_accounts)) {
                    $default_payment_accounts = json_decode($location->default_payment_accounts, true);
                    foreach ($default_payment_accounts as $key => $account) {
                        if (! empty($account['is_enabled']) && ! empty($account['account'])) {
                            $account_ids[] = $account['account'];
                        }
                    }
                }
            }

            $account_ids = array_unique($account_ids);
        }

        if ($permitted_locations != 'all') {
            $query->whereIn('accounts.id', $account_ids);
        }

        $can_access_account = auth()->user()->can('account.access');
        if ($can_access_account && $show_balance) {
            // $query->leftjoin('account_transactions as AT', function ($join) {
            //     $join->on('AT.account_id', '=', 'accounts.id');
            //     $join->whereNull('AT.deleted_at');
            // })
            $query->select('accounts.name',
                    'accounts.id',
                    DB::raw("(SELECT SUM( IF(account_transactions.type='credit', amount, -1*amount) ) as balance from account_transactions where account_transactions.account_id = accounts.id AND deleted_at is NULL) as balance")
                );
        }

        if (! $closed) {
            $query->where('is_closed', 0);
        }

        $accounts = $query->get();

        $dropdown = [];
        if ($prepend_none) {
            $dropdown[''] = __('lang_v1.none');
        }

        $commonUtil = new Util;
        foreach ($accounts as $account) {
            $name = $account->name;

            if ($can_access_account && $show_balance) {
                $name .= ' ('.__('lang_v1.balance').': '.$commonUtil->num_f($account->balance).')';
            }

            $dropdown[$account->id] = $name;
        }

        return $dropdown;
    }

    /**
     * Scope a query to only include not closed accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotClosed($query)
    {
        return $query->where('is_closed', 0);
    }

    /**
     * Scope a query to only include non capital accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    // public function scopeNotCapital($query)
    // {
    //     return $query->where(function ($q) {
    //         $q->where('account_type', '!=', 'capital');
    //         $q->orWhereNull('account_type');
    //     });
    // }

    public static function accountTypes()
    {
        return [
            '' => __('account.not_applicable'),
            'saving_current' => __('account.saving_current'),
            'capital' => __('account.capital'),
        ];
    }

    public function account_type()
    {
        return $this->belongsTo(\App\AccountType::class, 'account_type_id');
    }

    /**
     * Returns whether the account is an asset or not.
     *
     * @return bool
     */
    public function isAsset($type_name = null, $parent_type_name = null)
    {
        return $this->getBalanceType($type_name, $parent_type_name) == 'debit' &&
               !$this->isExpense($type_name, $parent_type_name);
    }

    /**
     * Returns the balance type of the account (debit or credit).
     *
     * @return string
     */
    public function getBalanceType($type_name = null, $parent_type_name = null)
    {
        // Try to get from database first
        if (!empty($this->account_type->balance_type)) {
            return $this->account_type->balance_type;
        }

        $type = '';
        if (!empty($type_name)) {
            $type = ($parent_type_name ?? '') . ' ' . $type_name;
        } elseif (!empty($this->account_type)) {
            $type = ($this->account_type->parent_account ? $this->account_type->parent_account->name : '') . ' ' . $this->account_type->name;
        }

        $type = strtolower($type);
        $name = strtolower($this->name);

        // Credit Normal Accounts: Liability, Equity, Income
        if (str_contains($type, 'liability') || str_contains($type, 'utang') || str_contains($type, 'kewajiban') || str_contains($type, 'pasiva') || str_contains($name, 'utang') || str_contains($name, 'kewajiban')) {
            return 'credit';
        } elseif (str_contains($type, 'equity') || str_contains($type, 'modal') || str_contains($type, 'ekuitas') || str_contains($type, 'capital') || str_contains($name, 'modal') || str_contains($name, 'ekuitas')) {
            return 'credit';
        } elseif (str_contains($type, 'pendapatan') || str_contains($type, 'penjualan') || str_contains($type, 'income') || str_contains($type, 'revenue') || str_contains($name, 'pendapatan')) {
            return 'credit';
        }

        // Debit Normal Accounts: Asset, Expense
        return 'debit';
    }

    /**
     * Returns whether the account is an expense or not.
     *
     * @return bool
     */
    public function isExpense($type_name = null, $parent_type_name = null)
    {
        $type = '';
        if (!empty($type_name)) {
            $type = ($parent_type_name ?? '') . ' ' . $type_name;
        } elseif (!empty($this->account_type)) {
            $type = ($this->account_type->parent_account ? $this->account_type->parent_account->name : '') . ' ' . $this->account_type->name;
        }

        $type = strtolower($type);
        $name = strtolower($this->name);

        if (str_contains($type, 'expense') || str_contains($type, 'biaya') || str_contains($type, 'beban') || str_contains($type, 'hpp') || str_contains($type, 'harga pokok') || str_contains($name, 'biaya') || str_contains($name, 'beban')) {
            return true;
        }
        return false;
    }
}
