@inject('payterm', 'App\Payterm')
@inject('members', 'App\Person')

<div class="col-md-6">
{{--
    <div class="form-group">
        {!! Form::label('cust_id', 'ID', ['class'=>'control-label']) !!}
        {!! Form::text('cust_id', null, ['class'=>'form-control']) !!}
    </div> --}}
    @if(isset($self))
    <div class="form-group">
        {!! Form::label('name', 'Name', ['class'=>'control-label']) !!}
        {!! Form::text('name', null, ['class'=>'form-control', 'readonly'=>'readonly']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('company', 'Username', ['class'=>'control-label']) !!}
        {!! Form::text('company', null, ['class'=>'form-control', 'readonly'=>'readonly']) !!}
    </div>
    @else
    <div class="form-group">
        {!! Form::label('name', 'Name', ['class'=>'control-label']) !!}
        {!! Form::text('name', null, ['class'=>'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('company', 'Username', ['class'=>'control-label']) !!}
        {!! Form::text('company', null, ['class'=>'form-control']) !!}
    </div>
    @endif

    <div class="form-group">
        {!! Form::label('com_remark', 'Company', ['class'=>'control-label']) !!}
        {!! Form::text('com_remark', null, ['class'=>'form-control']) !!}
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
        {!! Form::label('email', 'Email', ['class'=>'control-label']) !!}
        {!! Form::email('email', null, ['class'=>'form-control']) !!}
    </div>

    @if(Auth::user()->hasRole('admin'))
    <div class="form-group">
        {!! Form::label('cost_rate', 'Cost Rate (%)', ['class'=>'control-label']) !!}
        {!! Form::text('cost_rate', null, ['class'=>'form-control', 'placeholder'=>'Leave Blank for 100% as Default']) !!}
    </div>
    @endif

</div>

<div class="col-md-6">

    <div class="form-group">
        {!! Form::label('site_name', 'Site Name', ['class'=>'control-label']) !!}
        {!! Form::text('site_name', null, ['class'=>'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('bill_address', 'Billing Address', ['class'=>'control-label']) !!}
        {!! Form::textarea('bill_address', null, ['class'=>'form-control', 'rows'=>'2']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('del_address', 'Delivery Address', ['class'=>'control-label']) !!}
        {!! Form::textarea('del_address', null, ['class'=>'form-control', 'rows'=>'2']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('del_postcode', 'Delivery Postcode', ['class'=>'control-label']) !!}
        {!! Form::text('del_postcode', null, ['class'=>'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('remark', 'Remark', ['class'=>'control-label']) !!}
        {!! Form::textarea('remark', null, ['class'=>'form-control', 'rows'=>'2']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('payterm', 'Terms', ['class'=>'control-label']) !!}
        {!! Form::select('payterm', $payterm::lists('name', 'name'), null, ['id'=>'payterm', 'class'=>'select form-control']) !!}
    </div>
</div>

    <div class="row"></div>

    <hr>

    <div class="col-md-12">
        {{-- @if(isset($person) and Auth::user()->hasRole('admin')) --}}
        @if(isset($person))
        <div class="form-group">
            {!! Form::label('parent_id', 'Assign Parent', ['class'=>'control-label']) !!}
            {!! Form::select('parent_id', [''=>null] + $members::where('cust_id', 'LIKE', 'D%')->where('active', 'Yes')->whereNotIn('id', [$person->id])->lists('name', 'id')->all(), null, ['id'=>'parent_id', 'class'=>'select form-control']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('cust_type', 'Role Level', ['class'=>'control-label']) !!}
            {!! Form::select('cust_type', [
                                    ''=>null,
                                    'OM' => 'OM',
                                    'OE' => 'OE',
                                    'AM' => 'AM',
                                    'AB' => 'AB',
            ], null, ['id'=>'parent_id', 'class'=>'select form-control']) !!}
        </div>
        @endif
    </div>

<script>
    $('.select').select2({
        placeholder: 'Please Select...'
    });
</script>
