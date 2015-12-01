@inject('payterm', 'App\Payterm')

<div class="col-md-6">

    <div class="form-group">
        {!! Form::label('cust_id', 'ID', ['class'=>'control-label']) !!}
        {!! Form::text('cust_id', null, ['class'=>'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('company', 'Company', ['class'=>'control-label']) !!}
        {!! Form::text('company', null, ['class'=>'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('name', 'Att To', ['class'=>'control-label']) !!}
        {!! Form::text('name', null, ['class'=>'form-control']) !!}
    </div> 

    <div class="form-group">
        {!! Form::label('contact', 'Contact', ['class'=>'control-label']) !!}
        {!! Form::text('contact', null, ['class'=>'form-control']) !!}
    </div>          

    <div class="form-group">
        {!! Form::label('alt_contact', 'Alt Contact', ['class'=>'control-label']) !!}
        {!! Form::text('alt_contact', null, ['class'=>'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('bill_to', 'Bill To', ['class'=>'control-label']) !!}
        {!! Form::text('bill_to', null, ['class'=>'form-control']) !!}
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('del_address', 'Delivery Address', ['class'=>'control-label']) !!}
        {!! Form::textarea('del_address', null, ['class'=>'form-control', 'rows'=>'1']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('postcode', 'Postcode', ['class'=>'control-label']) !!}
        {!! Form::text('postcode', null, ['class'=>'form-control']) !!}
    </div>    

    <div class="form-group">
        {!! Form::label('email', 'Email', ['class'=>'control-label']) !!}
        {!! Form::email('email', null, ['class'=>'form-control']) !!}
    </div>    

    <div class="form-group">
        {!! Form::label('payterm', 'Terms', ['class'=>'control-label']) !!}    
        {!! Form::select('payterm', $payterm::lists('name', 'name'), null, ['id'=>'payterm', 'class'=>'select form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('cost_rate', 'Cost Rate (%)', ['class'=>'control-label']) !!}
        {!! Form::text('cost_rate', null, ['class'=>'form-control']) !!}
    </div>          

    <div class="form-group">
        {!! Form::label('remark', 'Remark', ['class'=>'control-label']) !!}
        {!! Form::textarea('remark', null, ['class'=>'form-control', 'rows'=>'2']) !!}
    </div>        
</div>

<script>
    $('.select').select2();
</script>
