@inject('people', 'App\Person')
@inject('users', 'App\User')

<div class="panel panel-primary">
    <div class="panel-body">

{{--             @if($transaction->status == 'Pending')
        <div class="form-group">
            {!! Form::label('person_id', 'Customer', ['class'=>'control-label']) !!}
            {!! Form::select('person_id',
                $people::select(DB::raw("CONCAT(cust_id,' - ',company) AS full, id"))->lists('full', 'id'),
                null,
                [
                'id'=>'person_id',
                'class'=>'person form-control',
                'ng-model'=>'form.person',
                'ng-change'=>'onPersonSelected(form.person)'
                ])
            !!}
        </div>
        @else --}}
            {{-- {!! Form::text('person_id', $transaction->person->cust_id.' - '.$transaction->person->company, ['class'=>'form-control', 'id'=>'person_id', 'readonly'=>'readonly', 'style'=>'margin-bottom:10px']) !!} --}}
        <label style="margin-bottom: 15px; font-size: 18px;">
            <a href="/person/@{{ form.person }}">
                {{$transaction->person->cust_id}} - {{$transaction->person->company}}
            </a>
        </label>
{{--            @endif --}}
        {!! Form::text('person_id', '@{{form.person}}', ['class'=>'hidden form-control']) !!}
        {!! Form::text('person_copyid', '@{{form.person}}', ['class'=>'hidden form-control']) !!}
        {!! Form::text('person_code', '@{{form.cust_id}}', ['class'=>'hidden form-control']) !!}
        {!! Form::text('name', '@{{form.name}}', ['class'=>'hidden form-control']) !!}

        <div class="row">
            <div class="col-md-6 form-group">
                {!! Form::label('bill_address', 'Bill To', ['class'=>'control-label']) !!}
                {!! Form::textarea('bill_address', null, ['class'=>'form-control',
                'ng-model'=>'form.bill_address',
                'rows'=>'3']) !!}
            </div>

            @if($transaction->status == 'Cancelled' or (Auth::user()->can('transaction_view') and $transaction->status === 'Delivered'))
                <div class="col-md-6 form-group">
                    {!! Form::label('del_address', 'Delivery Add', ['class'=>'control-label']) !!}
                    {!! Form::textarea('del_address', null, ['class'=>'form-control',
                    'ng-model'=>'form.del_address',
                    'readonly'=>'readonly',
                    'rows'=>'3']) !!}
                </div>
            @else
                <div class="col-md-6 form-group">
                    {!! Form::label('del_address', 'Delivery Add', ['class'=>'control-label']) !!}
                    {!! Form::textarea('del_address', null, ['class'=>'form-control',
                    'ng-model'=>'form.del_address',
                    'rows'=>'3']) !!}
                </div>
            @endif
        </div>


        <div class="row">
        @if($transaction->status == 'Cancelled' or (Auth::user()->can('transaction_view') and $transaction->status === 'Delivered'))
            <div class="col-md-3 col-sm-12 col-xs-12 form-group">
                {!! Form::label('form.order_date', 'Order On', ['class'=>'control-label']) !!}
                <div class="input-group">
                    <datepicker>
                        <input
                            type = "text"
                            class = "form-control"
                            placeholder = "Order Date"
                            ng-model = "form.order_date"
                            ng-change = "dateChanged('order_date', form.order_date)"
                            readonly
                        />
                    </datepicker>
                    <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('order_date', form.order_date)"></span>
                    <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('order_date', form.order_date)"></span>
                </div>
            </div>

            <div class="col-md-3 col-sm-12 col-xs-12 form-group">
                {!! Form::label('form.delivery_date', 'Delivery On', ['class'=>'control-label']) !!}
                <div class="input-group">
                    <datepicker>
                        <input
                            type = "text"
                            class = "form-control"
                            placeholder = "Delivery Date"
                            ng-model = "form.delivery_date"
                            ng-change = "dateChanged('delivery_date', form.delivery_date)"
                            readonly
                        />
                    </datepicker>
                    <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('delivery_date', form.delivery_date)"></span>
                    <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('delivery_date', form.delivery_date)"></span>
                </div>
            </div>
        @else
            <div class="col-md-3 col-sm-12 col-xs-12 form-group">
                {!! Form::label('form.order_date', 'Order On', ['class'=>'control-label']) !!}
                <div class="input-group">
                    <datepicker>
                        <input
                            type = "text"
                            name = "order_date"
                            class = "form-control"
                            placeholder = "Order Date"
                            ng-model = "form.order_date"
                            ng-change = "dateChanged('order_date', form.order_date)"
                        />
                    </datepicker>
                    <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('order_date', form.order_date)"></span>
                    <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('order_date', form.order_date)"></span>
                </div>
            </div>

            <div class="col-md-3 col-sm-12 col-xs-12 form-group">
                {!! Form::label('form.delivery_date', 'Delivery On', ['class'=>'control-label']) !!}
                <div class="input-group">
                    <datepicker>
                        <input
                            type = "text"
                            name = "delivery_date"
                            class = "form-control"
                            placeholder = "Delivery Date"
                            ng-model = "form.delivery_date"
                            ng-change = "dateChanged('delivery_date', form.delivery_date)"
                        />
                    </datepicker>
                    <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('delivery_date', form.delivery_date)"></span>
                    <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('delivery_date', form.delivery_date)"></span>
                </div>
            </div>
        @endif

            <div class="col-md-3 col-xs-6 form-group">
                {!! Form::label('payterm', 'Pay Term', ['class'=>'control-label']) !!}
                {!! Form::textarea('payterm', null, ['class'=>'form-control',
                'ng-model'=>'form.payterm',
                'readonly'=>'readonly',
                'rows'=>'1']) !!}
            </div>

            @if($transaction->status == 'Cancelled' or (Auth::user()->can('transaction_view') and $transaction->status === 'Delivered'))
                <div class="col-md-3 col-xs-6 form-group">
                    {!! Form::label('del_postcode', 'PostCode', ['class'=>'control-label']) !!}
                    {!! Form::text('del_postcode', null, ['class'=>'form-control',
                    'ng-model'=>'form.del_postcode',
                    'readonly'=>'readonly']) !!}
                </div>
            @else
                <div class="col-md-3 col-xs-6 form-group">
                    {!! Form::label('del_postcode', 'PostCode', ['class'=>'control-label']) !!}
                    {!! Form::text('del_postcode', null, ['class'=>'form-control',
                    'ng-model'=>'form.del_postcode']) !!}
                </div>
            @endif
        </div>

        <div class="row">
            @if($transaction->status == 'Cancelled' or (Auth::user()->can('transaction_view') and $transaction->status === 'Delivered'))
                <div class="col-md-4 form-group">
                    {!! Form::label('po_no', 'PO #', ['class'=>'control-label']) !!}
                    {!! Form::text('po_no', null, ['class'=>'form-control', 'readonly'=>'readonly']) !!}
                </div>

                <div class="col-md-4 form-group">
                    {!! Form::label('name', 'Attn. Name', ['class'=>'control-label']) !!}
                    {!! Form::text('name', null, ['class'=>'form-control', 'ng-model'=>'form.attn_name', 'readonly'=>'readonly']) !!}
                </div>

                <div class="col-md-4 form-group">
                    {!! Form::label('contact', 'Tel No.', ['class'=>'control-label']) !!}
                    {!! Form::text('contact', null, ['class'=>'form-control', 'ng-model'=>'form.contact', 'readonly'=>'readonly']) !!}
                </div>
            @else
                <div class="col-md-4 form-group">
                    {!! Form::label('po_no', 'PO #', ['class'=>'control-label']) !!}
                    {!! Form::text('po_no', null, ['class'=>'form-control']) !!}
                </div>

                <div class="col-md-4 form-group">
                    {!! Form::label('name', 'Attn. Name', ['class'=>'control-label']) !!}
                    {!! Form::text('name', null, ['class'=>'form-control',
                    'ng-model'=>'form.attn_name']) !!}
                </div>

                <div class="col-md-4 form-group">
                    {!! Form::label('contact', 'Tel No.', ['class'=>'control-label']) !!}
                    {!! Form::text('contact', null, ['class'=>'form-control',
                    'ng-model'=>'form.contact']) !!}
                </div>
            @endif
        </div>

        <div class="row">
            @if($transaction->status === 'Confirmed' or $transaction->status ==='Delivered' or $transaction->status === 'Verified Owe' or $transaction->status === 'Verified Paid')
                @cannot('transaction_view')
                    <div class="col-md-4 form-group">
                        {!! Form::label('driver', 'Delivered By', ['class'=>'control-label']) !!}
                        {!! Form::select('driver',
                                [''=>null]+$users::lists('name', 'name')->all(),
                                null,
                                ['class'=>'select form-control'])
                        !!}
                    </div>

                    <div class="col-md-4 form-group">
                        {!! Form::label('paid_by', 'Payment Received By', ['class'=>'control-label']) !!}
                        {!! Form::select('paid_by',
                                [''=>null]+$users::lists('name', 'name')->all(),
                                null,
                                ['class'=>'select form-control'])
                        !!}
                    </div>

                    <div class="col-md-4 form-group">
                        {!! Form::label('paid_at', 'Payment Received On', ['class'=>'control-label']) !!}
                    <div class="input-group date">
                        {!! Form::text('paid_at', null, ['class'=>'form-control', 'id'=>'paid_at']) !!}
                        <span class="input-group-addon"><span class="glyphicon-calendar glyphicon"></span></span>
                    </div>
                    </div>
                @endcannot
            @endif

            @if($transaction->status === 'Verified Paid' or $transaction->pay_status === 'Paid')
                <div class="col-md-4 form-group">
                    {!! Form::label('pay_method', 'Payment Method', ['class'=>'control-label']) !!}
                    {!! Form::select('pay_method',
                        [''=>null, 'cash'=>'Cash', 'cheque'=>'Cheque', 'tt'=>'Tt'],
                        null,
                        ['class'=>'select form-control'])
                    !!}
                </div>

                <div class="col-md-4 form-group">
                    {!! Form::label('note', 'Note', ['class'=>'control-label']) !!}
                    {!! Form::text('note', null, ['class'=>'form-control']) !!}
                </div>
            @endif
        </div>

        <div class="row">
            @if($transaction->status == 'Cancelled' or (Auth::user()->can('transaction_view') and $transaction->status === 'Delivered'))
                <div class="col-md-12 form-group">
                    {!! Form::label('transremark', 'Remark', ['class'=>'control-label']) !!}
                    {!! Form::textarea('transremark', null, ['class'=>'form-control', 'rows'=>'5', 'readonly'=>'readonly']) !!}
                </div>
            @else
                <div class="col-md-12 form-group">
                    {!! Form::label('transremark', 'Remark', ['class'=>'control-label']) !!}
                    {!! Form::textarea('transremark', null, ['class'=>'form-control',
                    'ng-model'=>'form.transremark',
                    'rows'=>'5']) !!}
                </div>
            @endif
        </div>

        @if($transaction->person->is_vending === 1 or $transaction->person->is_dvm === 1)
            <div style="width: 100%; height: 20px; border-bottom: 1px solid black; text-align: center; margin: 15px 0 20px 0;">
              <span style="font-size: 20px; background-color: #F3F5F6; padding: 0 20px;">
                Vending Machine
              </span>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-4 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('digital_clock', 'Digital Clocker', ['class'=>'control-label']) !!}
                        {!! Form::text('digital_clock', null, ['class'=>'form-control']) !!}
                    </div>
                </div>
                <div class="col-md-4 col-sm-4 col-xs-12">
                    @cannot('transaction_view')
                    <div class="col-md-8 col-sm-8 col-xs-8">
                    @endcannot
                        <div class="form-group">
                            {!! Form::label('analog_clock', 'Analog Clocker', ['class'=>'control-label']) !!}
                            {!! Form::text('analog_clock', null, ['class'=>'form-control']) !!}
                        </div>
                    @cannot('transaction_view')
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-4" style="padding-top: 25px;">
                            {!! Form::checkbox('is_required_analog', $transaction->is_required_analog) !!}
                            <small>{!! Form::label('is_required_analog', 'Required', ['class'=>'control-label']) !!}</small>
                    </div>
                    @endcannot
                </div>
                <div class="col-md-4 col-sm-4 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('balance_coin', 'Balance Coin', ['class'=>'control-label']) !!}
                        {!! Form::text('balance_coin', null, ['class'=>'form-control']) !!}
                    </div>
                </div>
            </div>
            @if($transaction->person->is_dvm)
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('sales_count', 'Sales Count (pcs)', ['class'=>'control-label']) !!}
                        {!! Form::text('sales_count', null, ['class'=>'form-control']) !!}
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('sales_amount', 'Sales Amount ($)', ['class'=>'control-label']) !!}
                        {!! Form::text('sales_amount', null, ['class'=>'form-control']) !!}
                    </div>
                </div>
            </div>
            @endif
        @endif
    </div>
</div>

