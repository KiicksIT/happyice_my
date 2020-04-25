@extends('template')
@section('title')
{{ $TRANS_TITLE }}
@stop
@section('content')

<div class="create_edit">
<div class="panel panel-primary" ng-app="app" ng-controller="transactionController">

    <div class="panel-heading">
        <div class="col-md-4">
        <h4>
            @if($transaction->status == 'Cancelled')
            <del><strong>Invoice : {{$transaction->id}}</strong> ({{$transaction->status}}) - {{$transaction->pay_status}}</del>
            @else
            <strong>Invoice : {{$transaction->id}}</strong> ({{$transaction->status}}) - {{$transaction->pay_status}}
            @endif
            {!! Form::text('transaction_id', $transaction->id, ['id'=>'transaction_id','class'=>'hidden form-control']) !!}
        </h4>
        </div>
        <div class="col-md-8">
            @if($transaction->paid_by)
            <div class="col-md-4">
                <label style="padding-top: 10px" class="pull-right">Paid by : {{ $transaction->paid_by }}</label>
            </div>
            @else
            <div class="col-md-4"></div>
            @endif
            @if($transaction->driver)
            <div class="col-md-4">
                <label style="padding-top: 10px" class="pull-right">Delivered by : {{ $transaction->driver }}</label>
            </div>
            @else
            <div class="col-md-4"></div>
            @endif
            <div class="col-md-4">
                <label style="padding-top: 10px" class="pull-right">Last Modified: {{ $transaction->updated_by }}</label>
            </div>
        </div>
    </div>

    {!! Form::model($transaction, ['id'=>'log', 'method'=>'POST', 'action'=>['TransactionController@generateLogs', $transaction->id]]) !!}
    {!! Form::close() !!}

    {!! Form::model($transaction, ['id'=>'new_transaction', 'method'=>'POST', 'action'=>['TransactionController@store']]) !!}
        <input type="text" id="person_id" class="hidden" name="person_id" value="{{$transaction->person->id}}">
    {!! Form::close() !!}

    <div class="panel-body">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12 form-group">
                <div class="pull-right">
                    <button type="submit" class="btn btn-success" form="new_transaction"><i class="fa fa-plus"></i> New Transaction - {{$transaction->person->cust_id}}</button>
                    @if(!auth()->user()->hasRole('logistics'))
                    {!! Form::submit('Log History', ['class'=> 'btn btn-warning', 'form'=>'log']) !!}
                    @endif
                </div>
            </div>
        </div>
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    {!! Form::model($transaction,['id'=>'form_cust', 'method'=>'PATCH','action'=>['TransactionController@update', $transaction->id], 'autocomplete'=>'off']) !!}
                        @include('transaction.form_cust')

                    <div class="row">
                        <div class="col-md-12" style="padding-top:15px;">
                            @include('transaction.form_dealtable')
                        </div>
                    </div>

                    @unless($transaction->status == 'Delivered' and $transaction->pay_status == 'Paid')
                        <div class="row">
                            <div class="col-md-12" style="padding-top:15px;">
                                @include('transaction.form_table')
                            </div>
                        </div>
                    @else
                        @cannot('transaction_view')
                        @cannot('supervisor_view')
                        <div class="row">
                            <div class="col-md-12" style="padding-top:15px;">
                                @include('transaction.form_table')
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

            @php
                //state 1 = cancel inv - confirm - cancel (pending)
                //state 2 = cancel inv - del paid - del owe - update - print - send inv - cancel (confirmed)
                //state 3 = cancel inv - paid - update - print - send inv - cancel (delivered unpaid)
                //state 4 = delete inv - undo - cancel (cancelled)
                //state 5 = cancel inv - unpaid - update - print - send inv - cancel (delivered paid)
                //state 6 = LOCKED label - cancel (is freeze true)

                $state = '';
                $status = $transaction->status;
                $pay_status = $transaction->pay_status;

                if($transaction->is_freeze) {
                    $state = 6;
                }else {
                    switch($status) {
                        case 'Pending':
                            $state = 1;
                            break;
                        case 'Confirmed':
                            $state = 2;
                            break;
                        case 'Delivered':
                        case 'Verified Owe':
                        case 'Verified Paid':
                            if($pay_status === 'Paid') {
                                $state = 5;
                            }else {
                                $state = 3;
                            }
                            break;
                        case 'Cancelled':
                            $state = 4;
                            break;
                        default:
                            $state = 6;
                    }
                }
            @endphp

            @if($state === 1)
            <div class="row">
                <div class="col-md-12" >
                    <div class="pull-left">
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics'))
                        {!! Form::submit('Cancel Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_delete']) !!}
                        @endif
                    </div>
                    <div class="pull-right hidden-xs">
                        {!! Form::submit('Confirm', ['name'=>'confirm', 'class'=> 'btn btn-primary', 'form'=>'form_cust']) !!}
                        <a href="/transaction" class="btn btn-default">Cancel</a>
                    </div>
                    <div class="hidden-lg hidden-md hidden-sm">
                        {!! Form::submit('Confirm', ['name'=>'confirm', 'class'=> 'btn btn-primary btn-block', 'form'=>'form_cust']) !!}
                        <a href="/transaction" class="btn btn-default btn-block">Cancel</a>
                    </div>
                </div>
            </div>
            @elseif($state === 2)
            <div class="row">
                <div class="col-md-12">
                    <div class="pull-left">
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics'))
                        {!! Form::submit('Cancel Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_delete']) !!}
                        @endif
                    </div>

                    <div class="pull-right hidden-xs">
                        @if(!auth()->user()->hasRole('franchisee') and $transaction->person->active != 'Pending')
                            @if(!auth()->user()->hasRole('logistics'))
                            {!! Form::submit('Delivered & Paid', ['name'=>'del_paid', 'class'=> 'btn btn-success', 'form'=>'form_cust', 'onclick'=>'clicked(event)' ]) !!}
                            @endif
                        {!! Form::submit('Delivered & Owe', ['name'=>'del_owe', 'class'=> 'btn btn-warning', 'form'=>'form_cust', 'onclick'=>'clicked(event)']) !!}
                        @endif
                        {!! Form::submit('Update', ['name'=>'update', 'class'=> 'btn btn-default', 'form'=>'form_cust']) !!}
                        @if(!auth()->user()->hasRole('logistics'))
                            <a href="/transaction/download/{{$transaction->id}}" class="btn btn-primary">Print</a>
                        @endif
                        <a href="/transaction/download/{{$transaction->id}}?type=1" class="btn btn-primary">Print DO</a>
                        @if(!auth()->user()->hasRole('logistics'))
                        <a href="/transaction/emailInv/{{$transaction->id}}" class="btn btn-primary">Send Inv Email</a>
                        @endif
                        <a href="/transaction" class="btn btn-default">Cancel</a>
                    </div>

                    <div class="hidden-lg hidden-md hidden-sm">
                        @if(!auth()->user()->hasRole('franchisee') and $transaction->person->active != 'Pending')
                            @if(!auth()->user()->hasRole('logistics'))
                            {!! Form::submit('Delivered & Paid', ['name'=>'del_paid', 'class'=> 'btn btn-success btn-block', 'form'=>'form_cust', 'onclick'=>'clicked(event)' ]) !!}
                            @endif
                        {!! Form::submit('Delivered & Owe', ['name'=>'del_owe', 'class'=> 'btn btn-warning btn-block', 'form'=>'form_cust', 'onclick'=>'clicked(event)']) !!}
                        @endif
                        {!! Form::submit('Update', ['name'=>'update', 'class'=> 'btn btn-default btn-block', 'form'=>'form_cust']) !!}
                        @if(!auth()->user()->hasRole('logistics'))
                            <a href="/transaction/download/{{$transaction->id}}" class="btn btn-primary btn-block">Print</a>
                        @endif
                        <a href="/transaction/download/{{$transaction->id}}?type=1" class="btn btn-primary btn-block">Print DO</a>
                        @if(!auth()->user()->hasRole('logistics'))
                        <a href="/transaction/emailInv/{{$transaction->id}}" class="btn btn-primary btn-block">Send Inv Email</a>
                        @endif
                        <a href="/transaction" class="btn btn-default btn-block">Cancel</a>
                    </div>
                </div>
            </div>
            @elseif($state === 3)
            <div class="col-md-12">
                <div class="row">
                    <div class="pull-left">
                        @can('transaction_deleteitem')
                        @cannot('supervisor_view')
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics'))
                        {!! Form::submit('Cancel Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_delete']) !!}
                        @endif
                        @endcannot
                        @endcan
                    </div>
                    <div class="pull-right hidden-xs">
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics') and $transaction->person->active != 'Pending')
                        {!! Form::submit('Paid', ['name'=>'paid', 'class'=> 'btn btn-success', 'form'=>'form_cust', 'onclick'=>'clicked(event)']) !!}
                        @endif
                        {!! Form::submit('Update', ['name'=>'update', 'class'=> 'btn btn-default', 'form'=>'form_cust']) !!}
                        @if(!auth()->user()->hasRole('logistics'))
                        <a href="/transaction/download/{{$transaction->id}}" class="btn btn-primary">Print</a>
                        @endif
                        <a href="/transaction/download/{{$transaction->id}}?type=1" class="btn btn-primary">Print DO</a>
                        @if(!auth()->user()->hasRole('logistics'))
                        <a href="/transaction/emailInv/{{$transaction->id}}" class="btn btn-primary">Send Inv Email</a>
                        @endif
                        <a href="/transaction" class="btn btn-default">Cancel</a>
                    </div>

                    <div class="hidden-lg hidden-md hidden-sm">
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics') and $transaction->person->active != 'Pending')
                        {!! Form::submit('Paid', ['name'=>'paid', 'class'=> 'btn btn-success btn-block', 'form'=>'form_cust', 'onclick'=>'clicked(event)']) !!}
                        @endif
                        {!! Form::submit('Update', ['name'=>'update', 'class'=> 'btn btn-default btn-block', 'form'=>'form_cust']) !!}
                        @if(!auth()->user()->hasRole('logistics'))
                        <a href="/transaction/download/{{$transaction->id}}" class="btn btn-primary btn-block">Print</a>
                        @endif
                        <a href="/transaction/download/{{$transaction->id}}?type=1" class="btn btn-primary">Print DO</a>
                        @if(!auth()->user()->hasRole('logistics'))
                        <a href="/transaction/emailInv/{{$transaction->id}}" class="btn btn-primary btn-block">Send Inv Email</a>
                        @endif
                        <a href="/transaction" class="btn btn-default btn-block">Cancel</a>
                    </div>
                </div>
            </div>
            @elseif($state === 4)
            <div class="col-md-12">
                <div class="row">
                    <div class="pull-right hidden-xs">
                        @cannot('transaction_view')
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics'))
                            {!! Form::submit('Delete Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_wipe']) !!}
                            {!! Form::submit('Undo Cancel', ['class'=> 'btn btn-warning', 'form'=>'form_reverse', 'name'=>'form_reverse']) !!}
                        @endif
                        @endcan
                        <a href="/transaction" class="btn btn-default">Cancel</a>
                    </div>

                    <div class="hidden-lg hidden-md hidden-sm">
                        @cannot('transaction_view')
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics'))
                            {!! Form::submit('Delete Invoice', ['class'=> 'btn btn-danger btn-block', 'form'=>'form_delete', 'name'=>'form_wipe']) !!}
                            {!! Form::submit('Undo Cancel', ['class'=> 'btn btn-warning btn-block', 'form'=>'form_reverse', 'name'=>'form_reverse']) !!}
                        @endif
                        @endcan
                        <a href="/transaction" class="btn btn-default btn-block">Cancel</a>
                    </div>
                </div>
            </div>
            @elseif($state === 5)
            <div class="col-md-12">
                <div class="row">
                    <div class="pull-left">
                        {{-- @can('transaction_deleteitem') --}}
                        @cannot('transaction_view')
                        @cannot('supervisor_view')
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics'))
                            {!! Form::submit('Cancel Invoice', ['class'=> 'btn btn-danger', 'form'=>'form_delete', 'name'=>'form_delete']) !!}
                            {!! Form::submit('Unpaid', ['name'=>'unpaid', 'class'=> 'btn btn-warning', 'form'=>'form_cust']) !!}
                        @endif
                        @endcannot
                        @endcannot
                        {{-- @endcan --}}
                    </div>
                    <div class="pull-right hidden-xs">
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics'))
                        {!! Form::submit('Update', ['name'=>'update', 'class'=> 'btn btn-warning', 'form'=>'form_cust']) !!}
                        @endif
                        @if(!auth()->user()->hasRole('logistics'))
                        <a href="/transaction/emailInv/{{$transaction->id}}" class="btn btn-primary">Send Inv Email</a>
                        <a href="/transaction/download/{{$transaction->id}}" class="btn btn-primary">Print</a>
                        @endif
                        <a href="/transaction" class="btn btn-default">Cancel</a>
                    </div>
                    <div class="hidden-lg hidden-md hidden-sm">
                        @if(!auth()->user()->hasRole('franchisee') and !auth()->user()->hasRole('logistics'))
                        {!! Form::submit('Update', ['name'=>'update', 'class'=> 'btn btn-warning btn-block', 'form'=>'form_cust']) !!}
                        @endif
                        @if(!auth()->user()->hasRole('logistics'))
                        <a href="/transaction/emailInv/{{$transaction->id}}" class="btn btn-primary btn-block">Send Inv Email</a>
                        <a href="/transaction/download/{{$transaction->id}}" class="btn btn-primary btn-block">Print</a>
                        @endif
                        <a href="/transaction" class="btn btn-default btn-block">Cancel</a>
                    </div>
                </div>
            </div>
            @elseif($state === 6)
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="row">
                    <div class="pull-right hidden-xs">
                        <span class="bg-info" style="margin-right: 15px;">
                            <i class="fa fa-lock"></i>
                            This invoice has been locked
                        </span>
                        <a href="/transaction" class="btn btn-default">Cancel</a>
                    </div>
                    <div class="hidden-lg hidden-md hidden-sm">
                        <span class="bg-info" style="margin-right: 15px;">
                            <i class="fa fa-lock"></i>
                            This invoice has been locked
                        </span>
                        <a href="/transaction" class="btn btn-default btn-block">Cancel</a>
                    </div>
                </div>
            </div>
            @endif
    </div>

    <div class="panel-footer">
        <div class="panel panel-primary">
            <div class="panel-heading">
                Printable Attachment(s)
            </div>
            <div class="panel-body">
                <table class="table table-list-search table-hover table-bordered">
                    <tr style="background-color: #DDFDF8">
                        <th class="col-md-1 text-center">
                            #
                        </th>
                        <th class="col-md-10 text-center">
                            Image
                        </th>
                        <th class="col-md-1 text-center">
                            Action
                        </th>
                    </tr>

                    <tbody>
                        @unless(count($invattachments)>0)
                            <td class="text-center" colspan="12">No Records Found</td>
                        @else
                            @foreach($invattachments as $index => $invattachment)
                            <tr>
                                <td class="col-md-1 text-center">
                                    {{ $index + 1 }}
                                </td>
                                <td class="col-md-10">
                                    <a href="{{$invattachment->path}}">
                                        <img src="{{$invattachment->path}}" alt="{{$invattachment->name}}" style="max-width:350px; max-height:350px;">
                                    </a>
                                </td>
                                <td class="col-md-1 text-center">
                                    <button type="submit" form="remove_file" class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i> <span class="hidden-xs">Delete</span></button>
                                </td>
                            </tr>
                            @endforeach
                        @endunless
                    </tbody>
                </table>

                @if(count($invattachments) > 0)
                    {!! Form::open(['id'=>'remove_file', 'method'=>'DELETE', 'action'=>['TransactionController@removeAttachment', $invattachment->id], 'onsubmit'=>'return confirm("Are you sure you want to delete?")']) !!}
                    {!! Form::close() !!}
                @endif


                {!! Form::open(['action'=>['TransactionController@addInvoiceAttachment', $transaction->id], 'class'=>'dropzone', 'style'=>'margin-top:20px']) !!}
{{--                 <form action="/transaction/invoice/attach" method="POST" enctype="multipart/form-data">
                    {{csrf_field()}}
                    <input type="file" name="img_file[]" multiple>
                    <button type="submit" class="pull-right btn btn-success">Upload</button>
                </form> --}}
                {!! Form::close() !!}
                <label class="pull-right totalnum" for="totalnum">
                    Total of {{count($invattachments)}} entries
                </label>
            </div>
        </div>
    </div>
</div>
</div>
@stop

@section('footer')
<script src="/js/transaction.js"></script>
<script>
    function clicked(e){
        if(!confirm('Are you sure?'))e.preventDefault();
    }
    $('.select').select2({
        placeholder: 'Please Select'
    });

    $(document).ready(function() {
        Dropzone.autoDiscover = false;
        $('.dropzone').dropzone({
            init: function()
            {
                this.on("complete", function()
                {
                  if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                    location.reload();
                  }
                });
            }

        });
    });
</script>
@stop