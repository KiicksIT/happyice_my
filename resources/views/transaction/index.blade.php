@inject('profiles', 'App\Profile')
@inject('people', 'App\Person')
@inject('custcategories', 'App\Custcategory')
@inject('franchisees', 'App\User')

@extends('template')
@section('title')
{{ $TRANS_TITLE }}
@stop
@section('content')

    <div ng-app="app" ng-controller="transController">

    <div class="row">
        <a class="title_hyper pull-left" href="/transaction"><h1>{{ $TRANS_TITLE }} <i class="fa fa-briefcase"></i> <span ng-show="spinner"> <i class="fa fa-spinner fa-1x fa-spin"></i></span></h1></a>
    </div>

        <div class="panel panel-default" ng-cloak>
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="pull-right">
                            @if(!auth()->user()->hasRole('franchisee'))
                                <a href="/transaction/create" class="btn btn-success">
                                    <i class="fa fa-plus"></i>
                                    <span class="hidden-xs"> New {{ $TRANS_TITLE }} </span>
                                </a>
                            @endif
                            @if(Auth::user()->hasRole('admin'))
                            <a href="/transaction/freeze/date" class="btn btn-primary">
                                <i class="fa fa-clock-o"></i>
                                <span class="hidden-xs">Freeze Transaction Invoice </span>
                            </a>
                            <a href="/transaction/email/subscription/" class="btn btn-default">
                                <i class="fa fa-calendar"></i>
                                <span class="hidden-xs">Weekly Email Subscription </span>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="row">
                {!! Form::open(['id'=>'transaction_rpt', 'method'=>'POST','action'=>['TransactionController@exportAccConsolidatePdf']]) !!}
                    <div class="row">
                    <div class="form-group col-md-3 col-sm-6 col-xs-12">
                        {!! Form::label('invoice', 'Invoice', ['class'=>'control-label search-title']) !!}
                        {!! Form::text('invoice', null,
                                                        [
                                                            'class'=>'form-control input-sm',
                                                            'ng-model'=>'search.id',
                                                            'ng-change'=>'searchDB()',
                                                            'placeholder'=>'Inv Num',
                                                            'ng-model-options'=>'{ debounce: 500 }'
                                                        ]) !!}
                    </div>
                    <div class="form-group col-md-3 col-sm-6 col-xs-12">
                        {!! Form::label('id', 'ID', ['class'=>'control-label search-title']) !!}
                        {!! Form::text('id', null,
                                                    [
                                                        'class'=>'form-control input-sm',
                                                        'ng-model'=>'search.cust_id',
                                                        'ng-change'=>'searchDB()',
                                                        'placeholder'=>'Cust ID',
                                                        'ng-model-options'=>'{ debounce: 500 }'
                                                    ])
                        !!}
                    </div>
                    <div class="form-group col-md-3 col-sm-6 col-xs-12">
                        {!! Form::label('company', 'ID Name', ['class'=>'control-label search-title']) !!}
                        {!! Form::text('company', null,
                                                        [
                                                            'class'=>'form-control input-sm',
                                                            'ng-model'=>'search.company',
                                                            'ng-change'=>'searchDB()',
                                                            'placeholder'=>'ID Name',
                                                            'ng-model-options'=>'{ debounce: 500 }'
                                                        ])
                        !!}
                    </div>
                    <div class="form-group col-md-3 col-sm-6 col-xs-12">
                        {!! Form::label('statuses', 'Status', ['class'=>'control-label search-title']) !!}
                        <select name="statuses" class="selectmultiple form-control" ng-model="search.statuses" ng-change="searchDB()" multiple>
                            <option value="">All</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Verified Owe">Verified Owe</option>
                            <option value="Verified Paid">Verified Paid</option>
                        </select>
                    </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('pay_status', 'Payment', ['class'=>'control-label search-title']) !!}
                            {!! Form::select('pay_status', [''=>'All', 'Owe'=>'Owe', 'Paid'=>'Paid'], null,
                                [
                                'class'=>'select form-control',
                                'ng-model'=>'search.pay_status',
                                'ng-change'=>'searchDB()'
                                ])
                            !!}
                        </div>

                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('updated_by', 'Last Modify By', ['class'=>'control-label search-title']) !!}
                            {!! Form::text('updated_by', null,
                                                                [
                                                                    'class'=>'form-control input-sm',
                                                                    'ng-model'=>'search.updated_by',
                                                                    'ng-change'=>'searchDB()',
                                                                    'placeholder'=>'Last Modified By',
                                                                    'ng-model-options'=>'{ debounce: 500 }'
                                                                ])
                            !!}
                        </div>
                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('updated_at', 'Last Modify Dt', ['class'=>'control-label search-title']) !!}
                            <div class="input-group">
                                <datepicker>
                                    <input
                                        type = "text"
                                        class = "form-control input-sm"
                                        placeholder = "Last Modify Date"
                                        ng-model = "search.updated_at"
                                        ng-change = "dateChange2(search.updated_at)"
                                    />
                                </datepicker>
                                <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('updated_at', search.updated_at)"></span>
                                <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('updated_at', search.updated_at)"></span>
                            </div>
                        </div>
                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('driver', 'Delivered By', ['class'=>'control-label search-title']) !!}
                            {!! Form::text('driver', null,
                                                            [
                                                                'id'=>'updated_at',
                                                                'class'=>'form-control input-sm',
                                                                'ng-model'=>'search.driver',
                                                                'ng-change'=>'searchDB()',
                                                                'placeholder'=>'Delivered By',
                                                                'ng-model-options'=>'{ debounce: 500 }'
                                                            ]) !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('custcategory', 'Category', ['class'=>'control-label search-title']) !!}
                            <select name="custcategories[]" class="selectmultiple form-control" ng-model="search.custcategories" ng-change="searchDB()" multiple>
                                <option value="">All</option>
                                @foreach($custcategories::orderBy('name')->get() as $custcategory)
                                    <option value="{{$custcategory->id}}">
                                        {{$custcategory->name}}
                                    </option>
                                @endforeach
                            </select>
{{--                             {!! Form::select('custcategory', [''=>'All']+$custcategories::orderBy('name')->pluck('name', 'id')->all(), null,
                                [
                                'class'=>'select form-control',
                                'ng-model'=>'search.custcategory',
                                'ng-change'=>'searchDB()'
                                ])
                            !!} --}}
                        </div>
                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('profile_id', 'Profile', ['class'=>'control-label search-title']) !!}
                            {!! Form::select('profile_id', [''=>'All']+$profiles::filterUserProfile()->pluck('name', 'id')->all(), null, ['id'=>'profile_id',
                                'class'=>'select form-control',
                                'ng-model'=>'search.profile_id',
                                'ng-change' => 'searchDB()'
                                ])
                            !!}
                        </div>
                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('franchisee_id', 'Franchisee', ['class'=>'control-label search-title']) !!}
                            {!! Form::select('franchisee_id', [''=>'All', '0' => 'Own']+$franchisees::filterUserFranchise()->select(DB::raw("CONCAT(user_code,' (',name,')') AS full, id"))->orderBy('user_code')->pluck('full', 'id')->all(), null, ['id'=>'franchisee_id',
                                'class'=>'select form-control',
                                'ng-model'=>'search.franchisee_id',
                                'ng-change' => 'searchDB()'
                                ])
                            !!}
                        </div>

                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('greater_zero', 'Greater Than Zero', ['class'=>'control-label search-title']) !!}
                            <select name="greater_zero" id="person_active" class="select form-control" ng-model="search.greater_zero" ng-change="searchDB()">
                                <option value="">All</option>
                                <option value="1">Greater</option>
                                <option value="2">Smaller</option>
                                <option value="3">Equals</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('person_active', 'Customer Status', ['class'=>'control-label search-title']) !!}
                            <select name="person_active" id="person_active" class="select form-control" ng-model="search.person_active" ng-change="searchDB()">
                                <option value="">All</option>
                                <option value="Yes">Active</option>
                                @if(!auth()->user()->hasRole('driver'))
                                    <option value="No">Inactive</option>
                                    <option value="Pending">Pending</option>
                                @endif
                            </select>
                        </div>
                        <div class="form-group col-md-3 col-sm-6 col-xs-12">
                            {!! Form::label('pay_method', 'Pay Method', ['class'=>'control-label search-title']) !!}
                            <select name="pay_method" id="pay_method" class="select form-control" ng-model="search.pay_method" ng-change="searchDB()">
                                <option value="">All</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="tt">TT</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="row">
                    <div class="form-group col-md-3 col-sm-6 col-xs-12">
                        {!! Form::label('delivery_from', 'Delivery From', ['class'=>'control-label search-title']) !!}
                        <div class="input-group">
                            <datepicker>
                                <input
                                    name = "delivery_from"
                                    type = "text"
                                    class = "form-control input-sm"
                                    placeholder = "Delivery From"
                                    ng-model = "search.delivery_from"
                                    ng-change = "delFromChange(search.delivery_from)"
                                />
                            </datepicker>
                            <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('delivery_from', search.delivery_from)"></span>
                            <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('delivery_from', search.delivery_from)"></span>
                        </div>
                    </div>
                    <div class="form-group col-md-3 col-sm-6 col-xs-12">
                        {!! Form::label('delivery_to', 'Delivery To', ['class'=>'control-label search-title']) !!}
                        <div class="input-group">
                            <datepicker>
                                <input
                                    name = "delivery_to"
                                    type = "text"
                                    class = "form-control input-sm"
                                    placeholder = "Delivery To"
                                    ng-model = "search.delivery_to"
                                    ng-change = "delToChange(search.delivery_to)"
                                />
                            </datepicker>
                            <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('delivery_to', search.delivery_to)"></span>
                            <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('delivery_to', search.delivery_to)"></span>
                        </div>
                    </div>
                    <div class="form-group col-md-3 col-sm-6 col-xs-12">
                        <div class="row col-md-12 col-sm-12 col-xs-12">
                            {!! Form::label('delivery_shortcut', 'Date Shortcut', ['class'=>'control-label search-title']) !!}
                        </div>
                        <div class="btn-group">
                            <a href="" ng-click="onPrevDateClicked()" class="btn btn-default"><i class="fa fa-backward"></i></a>
                            <a href="" ng-click="onTodayDateClicked()" class="btn btn-default"><i class="fa fa-circle"></i></a>
                            <a href="" ng-click="onNextDateClicked()" class="btn btn-default"><i class="fa fa-forward"></i></a>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <button class="btn btn-primary" ng-click="exportData($event)">Export Excel</button>
                        @if(auth()->user()->hasRole('admin') or auth()->user()->hasRole('accountadmin'))
                            <button class="btn btn-default" ng-click="enableAccConsolidate($event)">
                                Export Acc Consolidate
                                <span ng-if="!show_acc_consolidate_div" class="fa fa-caret-down"></span>
                                <span ng-if="show_acc_consolidate_div" class="fa fa-caret-up"></span>
                            </button>
                        @endif
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12" style="padding-top:5px;">
                        <div class="col-md-5 col-xs-5">
                            Total
                        </div>
                        <div class="col-md-7 col-xs-7 text-right" style="border: thin black solid">
                            <strong>@{{total_amount ? total_amount : 0.00 | currency: "": 2}}</strong>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6 col-xs-12 text-right">
                        <div class="row">
                            <label for="display_num">Display</label>
                            <select ng-model="itemsPerPage" name="pageNum" ng-init="itemsPerPage='100'" ng-change="pageNumChanged()">
                                <option ng-value="100">100</option>
                                <option ng-value="200">200</option>
                                <option ng-value="All">All</option>
                            </select>
                            <label for="display_num2" style="padding-right: 20px">per Page</label>
                        </div>
                        <div class="row">
                            <label class="" style="padding-right:18px;" for="totalnum">Showing @{{alldata.length}} of @{{totalCount}} entries</label>
                        </div>
                    </div>
                </div>

                <div ng-show="show_acc_consolidate_div">
                <hr class="row">
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-group">
                                    {!! Form::label('person_account', 'Customer to bill ', ['class'=>'control-label search-title']) !!}
                                    {!! Form::select('person_account',
                                        $people::whereHas('profile', function($q){
                                            $q->filterUserProfile();
                                        })->select(DB::raw("CONCAT(cust_id,' - ',company) AS full, id"))->orderBy('cust_id')->whereActive('Yes')->where('cust_id', 'NOT LIKE', 'H%')->lists('full', 'id')->all(),
                                        null,
                                        [
                                            'class'=>'select form-control',
                                            'ng-model'=>'form.person_account',
                                        ])
                                    !!}
                                    <p class="text-muted">*For Acc Consolidate Rpt, must select "Customer to bill"</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-group">
                                <label class="control-label"></label>
                                <div class="btn-group-control">
                                    <button type="submit" class="btn btn-default" form="transaction_rpt" name="exportpdf" value="do" ng-disabled="!form.person_account"><i class="fa fa-compress"></i> Export DO</button>
                                    <button type="submit" class="btn btn-default" form="transaction_rpt" name="exportpdf" value="invoice" ng-disabled="!form.person_account"><i class="fa fa-compress"></i> Export Tax Invoice</button>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <hr class="row">
                </div>
                {!! Form::close() !!}
                    <div class="table-responsive" id="exportable" style="padding-top:20px;">
                        <table class="table table-list-search table-hover table-bordered">
                            {{-- hidden table for excel export --}}
                            <tr class="hidden">
                                <td></td>
                                <td data-tableexport-display="always">Total Amount</td>
                                <td data-tableexport-display="always" class="text-right">@{{total_amount | currency: "": 2}}</td>
                            </tr>
                            <tr class="hidden" data-tableexport-display="always">
                                <td></td>
                            </tr>
                            <tr style="background-color: #DDFDF8">
                                <th class="col-md-1 text-center">
                                    #
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('id')">
                                    INV #
                                    <span ng-if="search.sortName == 'id' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'id' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('cust_id')">
                                    ID
                                    <span ng-if="search.sortName == 'cust_id' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'cust_id' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('company')">
                                    ID Name
                                    <span ng-if="search.sortName == 'company' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'company' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('custcategory_id')">
                                    Cust Cat
                                    <span ng-if="search.sortName == 'custcategory_id' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'custcategory_id' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('del_postcode')">
                                    Del Postcode
                                    <span ng-if="search.sortName == 'del_postcode' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'del_postcode' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('status')">
                                    Status
                                    <span ng-if="search.sortName == 'status' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'status' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('delivery_date')">
                                    Delivery Date
                                    <span ng-if="search.sortName == 'delivery_date' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'delivery_date' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('driver')">
                                    Delivered By
                                    <span ng-if="search.sortName == 'driver' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'driver' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('total')">
                                    Total Amount
                                    <span ng-if="search.sortName == 'total' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'total' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('total_qty')">
                                    Total Qty
                                    <span ng-if="search.sortName == 'total_qty' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'total_qty' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('pay_status')">
                                    Payment
                                    <span ng-if="search.sortName == 'pay_status' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'pay_status' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('updated_by')">
                                    Last Modified By
                                    <span ng-if="search.sortName == 'updated_by' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'updated_by' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    <a href="" ng-click="sortTable('transactions.updated_at')">
                                    Last Modified Time
                                    <span ng-if="search.sortName == 'transactions.updated_at' && !search.sortBy" class="fa fa-caret-down"></span>
                                    <span ng-if="search.sortName == 'transactions.updated_at' && search.sortBy" class="fa fa-caret-up"></span>
                                </th>
                                <th class="col-md-1 text-center">
                                    Action
                                </th>
                            </tr>
                            <tbody>
                                <tr dir-paginate="transaction in alldata | itemsPerPage:itemsPerPage | orderBy:sortType:sortReverse" total-items="totalCount">
                                    <td class="col-md-1 text-center">@{{ $index + indexFrom }} </td>
                                    <td class="col-md-1 text-center">
                                        <a href="/transaction/@{{ transaction.id }}/edit">
                                            @{{ transaction.id }}
                                        </a>
                                    </td>
                                    <td class="col-md-1 text-center">@{{ transaction.cust_id }} </td>
                                    <td class="col-md-1 text-center">
                                        <a href="/person/@{{ transaction.person_id }}">
                                            @{{ transaction.cust_id[0] == 'D' || transaction.cust_id[0] == 'H' ? transaction.name : transaction.company }}
                                        </a>
                                    </td>
                                    <td class="col-md-1 text-center">@{{ transaction.custcategory }} </td>
                                    <td class="col-md-1 text-center">@{{ transaction.del_postcode }}</td>
                                    {{-- status by color --}}
                                    <td class="col-md-1 text-center" style="color: red;" ng-if="transaction.status == 'Pending'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: orange;" ng-if="transaction.status == 'Confirmed'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: green;" ng-if="transaction.status == 'Delivered'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: black; background-color:orange;" ng-if="transaction.status == 'Verified Owe'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: black; background-color:green;" ng-if="transaction.status == 'Verified Paid'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" ng-if="transaction.status == 'Cancelled'">
                                        <span style="color: white; background-color: red;" > @{{ transaction.status }} </span>
                                    </td>
                                    {{-- status by color ended --}}
                                    <td class="col-md-1 text-center">@{{ transaction.del_date}}</td>
                                    <td class="col-md-1 text-center">@{{ transaction.driver }}</td>
                                    <td class="col-md-1 text-center">
                                        @{{ transaction.total | currency: "": 2}}
                                    </td>
                                    <td class="col-md-1 text-center">@{{ transaction.total_qty }}</td>
                                    {{-- pay status --}}
                                    <td class="col-md-1 text-center" style="color: red;" ng-if="transaction.pay_status == 'Owe'">
                                        @{{ transaction.pay_status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: green;" ng-if="transaction.pay_status == 'Paid'">
                                        @{{ transaction.pay_status }}
                                    </td>
                                    {{-- pay status ended --}}
                                    <td class="col-md-1 text-center">@{{ transaction.updated_by}}</td>
                                    <td class="col-md-1 text-center">@{{ transaction.updated_at }}</td>
                                    <td class="col-md-1 text-center">
                                        {{-- print invoice         --}}
                                        <a href="/transaction/download/@{{ transaction.id }}" class="btn btn-primary btn-sm" ng-if="transaction.status != 'Pending' && transaction.status != 'Cancelled'">Print</a>
                                        {{-- button view shown when cancelled --}}
                                        <a href="/transaction/@{{ transaction.id }}/edit" class="btn btn-sm btn-default" ng-if="transaction.status == 'Cancelled'">View</a>

                                        {{-- Payment Verification --}}
{{--                                         @cannot('supervisor_view')
                                        @cannot('transaction_view')
                                        <a href="/transaction/status/@{{ transaction.id }}" class="btn btn-warning btn-sm" ng-if="transaction.status == 'Delivered' && transaction.pay_status == 'Owe'">Verify Owe</a>
                                        <a href="/transaction/status/@{{ transaction.id }}" class="btn btn-success btn-sm" ng-if="(transaction.status == 'Verified Owe' || transaction.status == 'Delivered') && transaction.pay_status == 'Paid'">Verify Paid</a>
                                        @endcannot
                                        @endcannot --}}
                                    </td>
                                </tr>
                                <tr ng-if="!alldata || alldata.length == 0">
                                    <td colspan="18" class="text-center">No Records Found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <dir-pagination-controls max-size="5" direction-links="true" boundary-links="true" class="pull-left" on-page-change="pageChanged(newPageNumber)"> </dir-pagination-controls>
                    </div>
        </div>
    </div>

    <script src="/js/transaction_index.js"></script>
    <script>
        $('#delfrom').datetimepicker({
            format: 'DD-MMMM-YYYY'
        });

        // $('.select').select2({});
    </script>
@stop