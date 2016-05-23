@extends('template')
@section('title')
Customers
@stop
@section('content')

<div class="create_edit">
<div class="panel panel-primary">

    <div class="panel-heading">
        <h3 class="panel-title"><strong>Edit Customer: {{$person->cust_id}} - {{$person->name}}</strong></h3>
    </div>

    <div class="panel-body">
        {!! Form::model($person, ['id'=>'update_cust', 'action'=>['MarketingController@updateCustomer', $person->id]]) !!}

            @include('market.customer.form')

            <div class="col-md-12" style="padding-top:15px;">
                <div class="form-group pull-left">

                    @if($person->active == 'Yes')
                        {!! Form::submit('Deactivate', ['name'=>'deactive', 'class'=> 'btn btn-warning', 'form'=>'update_cust']) !!}
                    @else
                        {!! Form::submit('Activate', ['name'=>'active', 'class'=> 'btn btn-success', 'form'=>'update_cust']) !!}
                    @endif

                </div>
                <div class="form-group pull-right">
                    {!! Form::submit('Edit', ['class'=> 'btn btn-success', 'form'=>'update_cust']) !!}
                    <a href="/market/customer" class="btn btn-default">Cancel</a>
                </div>
            </div>
        {!! Form::close() !!}
    </div>
</div>
</div>

<script>
    function clicked(e){
        if(!confirm('Are you sure?'))e.preventDefault();
    }
</script>
@stop