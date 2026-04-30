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
                        <th rowspan="2" class="text-center" style="vertical-align: middle;">@lang('account.opening_balance')</th>
                        <th colspan="2" class="text-center">@lang('report.current_period')</th>
                        <th rowspan="2" class="text-center" style="vertical-align: middle;">@lang('account.balance')</th>
                    </tr>
                    <tr class="bg-gray">
                        <th class="text-center">@lang('account.debit')</th>
                        <th class="text-center">@lang('account.credit')</th>
                    </tr>
                </thead>
                <tbody id="trial_balance_details">
                </tbody>
                <tfoot>
                    <tr class="bg-gray">
                        <th class="text-right" colspan="2">@lang('sale.total')</th>
                        <td class="text-right">
                            <span class="remote-data" id="total_debit">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                        <td class="text-right">
                            <span class="remote-data" id="total_credit">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
                        <td class="text-right">
                            <span class="remote-data" id="total_balance">
                                <i class="fas fa-sync fa-spin fa-fw"></i>
                            </span>
                        </td>
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
        var loader = '<i class="fas fa-sync fa-spin fa-fw"></i>';
        $('span.remote-data').each( function() {
            $(this).html(loader);
        });

        $('#trial_balance_details').html('<tr><td colspan="5" class="text-center"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>');

        var start_date = $('input#start_date').val();
        var end_date = $('input#end_date').val();
        var location_id = $('#trial_bal_location_id').val()
        $.ajax({
            url: "{{action([\App\Http\Controllers\AccountReportsController::class, 'trialBalance'])}}?start_date=" + start_date + "&end_date=" + end_date + '&location_id=' + location_id,
            dataType: "json",
            success: function(result){
                var total_debit = 0;
                var total_credit = 0;
                var total_balance = 0;
                var rows = '';

                result.account_balances.forEach(function(account) {
                    var opening = parseFloat(account.opening_balance) || 0;
                    var debit = parseFloat(account.total_debit) || 0;
                    var credit = parseFloat(account.total_credit) || 0;

                    var fixed_key = account.fixed_key;
                    var balance = 0;
                    if (['hutang_usaha', 'hutang_lancar_lainnya', 'hutang_jangka_panjang', 'ekuitas', 'pendapatan_usaha', 'pendapatan_lainnya'].includes(fixed_key)) {
                        balance = (opening) + credit - debit;
                    } else {
                        balance = (-1 * opening) + debit - credit;
                    }

                    rows += '<tr>' +
                        '<td>' + account.name + '</td>' +
                        '<td class="text-right">' + __currency_trans_from_en(Math.abs(opening), true) + (opening > 0 ? ' (K)' : (opening < 0 ? ' (D)' : '')) + '</td>' +
                        '<td class="text-right">' + (debit > 0 ? __currency_trans_from_en(debit, true) : '') + '</td>' +
                        '<td class="text-right">' + (credit > 0 ? __currency_trans_from_en(credit, true) : '') + '</td>' +
                        '<td class="text-right">' + __currency_trans_from_en(balance, true) + '</td>' +
                    '</tr>';

                    total_debit += debit;
                    total_credit += credit;
                    total_balance += balance;
                });

                $('#trial_balance_details').html(rows);
                $('span#total_debit').text(__currency_trans_from_en(total_debit, true));
                $('span#total_credit').text(__currency_trans_from_en(total_credit, true));
                $('span#total_balance').text(__currency_trans_from_en(total_balance, true));
            }
        });
    }
</script>

@endsection