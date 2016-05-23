@extends('template')
@section('title')
Deals
@stop
@section('content')

<div class="create_edit">
<div class="panel panel-primary" ng-app="app" ng-controller="dealsController">

    <div class="panel-heading">
        <div class="col-md-4">
        <h4>
            @if($transaction->status == 'Cancelled')
            <del><strong>Invoice : {{$transaction->id}}</strong> ({{$transaction->status}})
                @unless($transaction->person->cust_id[0] == 'D')
                    - {{$transaction->pay_status}}</del>
                @endunless
            @else
            <strong>Invoice : {{$transaction->id}}</strong> ({{$transaction->status}})
                @unless($transaction->person->cust_id[0] == 'D')
                    - {{$transaction->pay_status}}
                @endunless
            @endif
            {!! Form::hidden('transaction_id', $transaction->id, ['id'=>'transaction_id','class'=>'form-control']) !!}
        </h4>
        </div>
    </div>

    <div class="panel-body">
{{--
            {!! Form::model($transaction, ['id'=>'log', 'method'=>'POST', 'action'=>['MarketingController@generateLogs', $transaction->id]]) !!}
            {!! Form::close() !!}
        <div class="row">
            <div class="col-md-12" style="padding: 0px 30px 10px 0px;">
                {!! Form::submit('Log History', ['class'=> 'btn btn-warning pull-right', 'form'=>'log']) !!}
            </div>
        </div> --}}

        <div class="col-md-12">
            <div class="row">
                <div class="col-md-12">
                    {!! Form::model($transaction,['id'=>'form_cust', 'method'=>'PATCH','action'=>['MarketingController@update', $transaction->id]]) !!}
                        @include('market.deal.form_cust')

                    <div class="row">
                        <div class="col-md-12" style="padding-top:15px;">
                            @include('transaction.form_dealtable')
                        </div>
                    </div>

                    @unless($transaction->status == 'Delivered' and $transaction->pay_status == 'Paid')
                        <div class="row">
                            <div class="col-md-12" style="padding-top:15px;">
                                @include('market.deal.form_table')
                            </div>
                        </div>
                    @else
                        @cannot('transaction_view')
                        @cannot('supervisor_view')
                        <div class="row">
                            <div class="col-md-12" style="padding-top:15px;">
                                @include('market.deal.form_table')
                            </div>
                        </div>
                        @endcannot
                        @endcannot
                    @endunless
                    {!! Form::close() !!}

                    {!! Form::open([ 'id'=>'form_delete', 'method'=>'DELETE', 'action'=>['TransactionController@destroy', $transaction->id], 'onsubmit'=>'return confirm("Are you sure you want to cancel invoice?")']) !!}
                    {!! Form::close() !!}
                    {!! Form::open([ 'id'=>'form_reverse', 'method'=>'POST', 'action'=>['TransactionController@reverse', $transaction->id], 'onsubmit'=>'return confirm("Are you sure you want to reverse the cancellation?")']) !!}
                    {!! Form::close() !!}
                </div>
            </div>

                @if($transaction->status == 'Pending' and $transaction->pay_status == 'Owe')
                <div class="row">
                    <div class="col-md-12" >
                        <div class="pull-left">
                            {!! Form::submit('Cancel Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_delete']) !!}
                        </div>
                        <div class="pull-right">

                            {!! Form::submit('Confirm', ['name'=>'confirm', 'class'=> 'btn btn-primary', 'form'=>'form_cust']) !!}
                            <a href="/transaction" class="btn btn-default">Cancel</a>
                        </div>
                    </div>
                </div>
                @elseif($transaction->status == 'Confirmed' and $transaction->pay_status == 'Owe')
                <div class="row">
                    <div class="col-md-12">
                        <div class="pull-left">
                            @can('transaction_deleteitem')
                            {!! Form::submit('Cancel Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_delete']) !!}
                            @endcan
                        </div>
                        <div class="pull-right">
                            @unless($transaction->person->cust_id[0] == 'D')
                            {!! Form::submit('Delivered & Paid', ['name'=>'del_paid', 'class'=> 'btn btn-success', 'form'=>'form_cust', 'onclick'=>'clicked(event)' ]) !!}
                            {!! Form::submit('Delivered & Owe', ['name'=>'del_owe', 'class'=> 'btn btn-warning', 'form'=>'form_cust', 'onclick'=>'clicked(event)']) !!}
                            @else
                            {!! Form::submit('Submit Order', ['name'=>'submit_deal', 'class'=> 'btn btn-success', 'form'=>'form_cust', 'onclick'=>'clicked(event)']) !!}
                            @endunless
                            {!! Form::submit('Update', ['name'=>'update', 'class'=> 'btn btn-default', 'form'=>'form_cust']) !!}


                            <a href="/market/deal/download/{{$transaction->id}}" class="btn btn-primary">Print</a>
                            <a href="/market/deal" class="btn btn-default">Cancel</a>

                        </div>
                    </div>
                </div>
                @elseif(($transaction->status == 'Delivered' or $transaction->status == 'Verified Owe' or $transaction->status == 'Verified Paid') and $transaction->pay_status == 'Owe')
                <div class="col-md-12">
                    <div class="row">
                        <div class="pull-left">
                            @can('transaction_deleteitem')
                            @cannot('supervisor_view')
                            {!! Form::submit('Cancel Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_delete']) !!}
                            @endcannot
                            @endcan
                        </div>
                        <div class="pull-right">

                            {!! Form::submit('Paid', ['name'=>'paid', 'class'=> 'btn btn-success', 'form'=>'form_cust', 'onclick'=>'clicked(event)']) !!}
                            <a href="/market/deal/download/{{$transaction->id}}" class="btn btn-primary">Print</a>
                            {!! Form::submit('Update', ['name'=>'update', 'class'=> 'btn btn-default', 'form'=>'form_cust']) !!}
                            <a href="/market/deal" class="btn btn-default">Cancel</a>

                        </div>
                    </div>
                </div>
                @elseif($transaction->status == 'Cancelled')
                <div class="col-md-12">
                    <div class="row">
                        <div class="pull-right">
                            <a href="/market/deal" class="btn btn-default">Cancel</a>
                            @cannot('transaction_view')
                                {!! Form::submit('Delete Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_wipe']) !!}
                                {!! Form::submit('Undo Cancel', ['class'=> 'btn btn-warning', 'form'=>'form_reverse', 'name'=>'form_reverse']) !!}
                            @endcan
                        </div>
                    </div>
                </div>
                @else
                <div class="col-md-12">
                    <div class="row">
                        <div class="pull-left">
                            @can('transaction_deleteitem')
                            @cannot('transaction_view')
                            @cannot('supervisor_view')
                                {!! Form::submit('Cancel Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_delete']) !!}
                                {!! Form::submit('Unpaid', ['name'=>'unpaid', 'class'=> 'btn btn-warning', 'form'=>'form_cust']) !!}
                            @endcannot
                            @endcannot
                            @endcan
                        </div>
                        <div class="pull-right">
                            @cannot('supervisor_view')
                            @cannot('transaction_view')
                                {!! Form::submit('Update', ['name'=>'update', 'class'=> 'btn btn-warning', 'form'=>'form_cust']) !!}
                            @endcannot
                            @endcannot
                            <a href="/market/deal/download/{{$transaction->id}}" class="btn btn-primary">Print</a>
                            <a href="/market/deal" class="btn btn-default">Cancel</a>
                        </div>
                    </div>
                </div>
                @endif

        </div>

    </div>
</div>
</div>
@stop

@section('footer')
<script src="/js/deal_edit.js"></script>
<script>
    function clicked(e){
        if(!confirm('Are you sure?'))e.preventDefault();
    }
    $('.select').select2({
        placeholder: 'Please Select'
    });
</script>
@stop