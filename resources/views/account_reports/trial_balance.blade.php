@extends('layouts.app')
@section('title', __( 'account.trial_balance' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang( 'account.trial_balance')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row no-print">
        <div class="col-sm-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('trial_bal_location_id',  __('purchase.business_location') . ':') !!}
                    {!! Form::select('trial_bal_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                    <label for="start_date">@lang('report.start_date'):</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        <input type="text" id="start_date" value="{{@format_date('first day of this month')}}" class="form-control" readonly>
                    </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                    <label for="end_date">@lang('report.end_date'):</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        <input type="text" id="end_date" value="{{@format_date('now')}}" class="form-control" readonly>
                    </div>
            </div>
            @endcomponent
        </div>
    </div>
    <br>
    <div class="box box-solid">
        <div class="box-header print_section">
            <h3 class="box-title">{{session()->get('business.name')}} - @lang( 'account.trial_balance')</h3>
        </div>
        <div class="box-body">
            <table class="table table-bordered table-pl-12" id="trial_balance_table">
                <thead>
                    <tr class="bg-gray">
                        <th rowspan="2" class="text-center" style="vertical-align: middle;">@lang('account.account')</th>
                        <th colspan="2" class="text-center">@lang('account.opening_balance')</th>
                        <th colspan="2" class="text-center">@lang('account.current_period')</th>
                        <th colspan="2" class="text-center">@lang('account.balance')</th>
                    </tr>
                    <tr class="bg-gray">
                        <th class="text-center">@lang('account.debit')</th>
                        <th class="text-center">@lang('account.credit')</th>
                        <th class="text-center">@lang('account.debit')</th>
                        <th class="text-center">@lang('account.credit')</th>
                        <th class="text-center">@lang('account.debit')</th>
                        <th class="text-center">@lang('account.credit')</th>
                    </tr>
                </thead>
                <tbody id="trial_balance_details">
                </tbody>
                <tfoot>
                    <tr class="bg-gray">
                        <th class="text-right">@lang('sale.total')</th>
                        <td class="text-right" id="total_opening_debit"></td>
                        <td class="text-right" id="total_opening_credit"></td>
                        <td class="text-right" id="total_debit"></td>
                        <td class="text-right" id="total_credit"></td>
                        <td class="text-right" id="total_balance_debit"></td>
                        <td class="text-right" id="total_balance_credit"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="box-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print pull-right"onclick="window.print()">
          <i class="fa fa-print"></i> @lang('messages.print')</button>
        </div>
    </div>

</section>
<!-- /.content -->
@stop
@section('javascript')

<script type="text/javascript">
    $(document).ready( function(){
        //Date picker
        $('#start_date, #end_date').datepicker({
            autoclose: true,
            format: datepicker_date_format
        });
        update_trial_balance();

        $('#start_date, #end_date').change( function() {
            update_trial_balance();
        });
        $('#trial_bal_location_id').change( function() {
            update_trial_balance();
        });
    });

    function update_trial_balance(){
        $('#trial_balance_details').html('<tr><td colspan="7" class="text-center"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>');

        var start_date = $('input#start_date').val();
        var end_date = $('input#end_date').val();
        var location_id = $('#trial_bal_location_id').val()
        $.ajax({
            url: "{{action([\App\Http\Controllers\AccountReportsController::class, 'trialBalance'])}}?start_date=" + start_date + "&end_date=" + end_date + '&location_id=' + location_id,
            dataType: "json",
            success: function(result){
                var total_opening_debit = 0;
                var total_opening_credit = 0;
                var total_debit = 0;
                var total_credit = 0;
                var total_balance_debit = 0;
                var total_balance_credit = 0;
                var rows = '';

                result.account_balances.forEach(function(account) {
                    var opening = parseFloat(account.opening_balance) || 0;
                    var debit = parseFloat(account.total_debit) || 0;
                    var credit = parseFloat(account.total_credit) || 0;

                    var fixed_key = account.fixed_key;
                    var balance = 0;

                    // Normal balance check
                    var is_debit_normal = account.normal_balance == 'debit';
                    if (!account.normal_balance) {
                        is_debit_normal = !(['hutang_usaha', 'hutang_lancar_lainnya', 'hutang_jangka_panjang', 'ekuitas', 'pendapatan_usaha', 'pendapatan_lainnya'].includes(fixed_key));
                    }

                    // Opening Balance columns
                    // Note: opening balance in SQL is SUM(IF(type='credit', amount, -1*amount))
                    // So negative opening means DEBIT balance, positive means CREDIT balance.
                    var opening_debit = opening < 0 ? Math.abs(opening) : 0;
                    var opening_credit = opening > 0 ? opening : 0;

                    // Final Balance calculation
                    var final_bal = 0;
                    if (is_debit_normal) {
                        final_bal = opening_debit - opening_credit + debit - credit;
                    } else {
                        final_bal = opening_credit - opening_debit + credit - debit;
                    }

                    var final_debit = 0;
                    var final_credit = 0;
                    if (is_debit_normal) {
                        if (final_bal >= 0) {
                            final_debit = final_bal;
                        } else {
                            final_credit = Math.abs(final_bal);
                        }
                    } else {
                        if (final_bal >= 0) {
                            final_credit = final_bal;
                        } else {
                            final_debit = Math.abs(final_bal);
                        }
                    }

                    rows += '<tr>' +
                        '<td>' + account.name + '</td>' +
                        '<td class="text-right">' + (opening_debit > 0 ? __currency_trans_from_en(opening_debit, true) : '') + '</td>' +
                        '<td class="text-right">' + (opening_credit > 0 ? __currency_trans_from_en(opening_credit, true) : '') + '</td>' +
                        '<td class="text-right">' + (debit > 0 ? __currency_trans_from_en(debit, true) : '') + '</td>' +
                        '<td class="text-right">' + (credit > 0 ? __currency_trans_from_en(credit, true) : '') + '</td>' +
                        '<td class="text-right">' + (final_debit > 0 ? __currency_trans_from_en(final_debit, true) : '') + '</td>' +
                        '<td class="text-right">' + (final_credit > 0 ? __currency_trans_from_en(final_credit, true) : '') + '</td>' +
                    '</tr>';

                    total_opening_debit += opening_debit;
                    total_opening_credit += opening_credit;
                    total_debit += debit;
                    total_credit += credit;
                    total_balance_debit += final_debit;
                    total_balance_credit += final_credit;
                });

                $('#trial_balance_details').html(rows);
                $('#total_opening_debit').text(__currency_trans_from_en(total_opening_debit, true));
                $('#total_opening_credit').text(__currency_trans_from_en(total_opening_credit, true));
                $('#total_debit').text(__currency_trans_from_en(total_debit, true));
                $('#total_credit').text(__currency_trans_from_en(total_credit, true));
                $('#total_balance_debit').text(__currency_trans_from_en(total_balance_debit, true));
                $('#total_balance_credit').text(__currency_trans_from_en(total_balance_credit, true));
            }
        });
    }
</script>

@endsection