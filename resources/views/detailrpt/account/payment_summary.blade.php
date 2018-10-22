<style>
    td ._720kb-datepicker-calendar{
        margin-top:0;
        z-index: 9999;
    }
</style>

<div ng-controller="custPaySummaryController">
{!! Form::open(['id'=>'export_excel', 'method'=>'POST','action'=>['DetailRptController@getAccountPaysummaryApi']]) !!}
<div class="col-md-12 col-xs-12" style="padding-bottom:20px;">
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('profile', 'Profile', ['class'=>'control-label search-title']) !!}
                {!! Form::select('profile_id', [''=>'All']+
                    $profiles::filterUserProfile()
                        ->pluck('name', 'id')
                        ->all(),
                    null,
                    [
                    'class'=>'select form-control',
                    'ng-model'=>'search.profile_id',
                    'ng-change'=>'searchDB()'
                    ])
                !!}
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('payment_from', 'Payment From', ['class'=>'control-label search-title']) !!}
                <div class="input-group">
                    <datepicker selector="form-control">
                        <input
                            type = "text"
                            class = "form-control input-sm"
                            placeholder = "Payment From"
                            ng-model = "search.payment_from"
                            ng-change = "onPaymentFromChanged(search.payment_from)"
                        />
                    </datepicker>
                    <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('payment_from', search.payment_from)"></span>
                    <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('payment_from', search.payment_from)"></span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('payment_to', 'Payment To', ['class'=>'control-label search-title']) !!}
                <div class="input-group">
                    <datepicker selector="form-control">
                        <input
                            type = "text"
                            class = "form-control input-sm"
                            placeholder = "Payment To"
                            ng-model = "search.payment_to"
                            ng-change = "onPaymentToChanged(search.payment_to)"
                        />
                    </datepicker>
                    <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('payment_to', search.payment_to)"></span>
                    <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('payment_to', search.payment_to)"></span>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('bankin_from', 'Bank In From', ['class'=>'control-label search-title']) !!}
                <div class="input-group">
                    <datepicker selector="form-control">
                        <input
                            type = "text"
                            class = "form-control input-sm"
                            placeholder = "Bank In From"
                            ng-model = "search.bankin_from"
                            ng-change = "onBankinFromChanged(search.bankin_from)"
                        />
                    </datepicker>
                    <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('bankin_from', search.bankin_from)"></span>
                    <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('bankin_from', search.bankin_from)"></span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('bankin_to', 'Bank In To', ['class'=>'control-label search-title']) !!}
                <div class="input-group">
                    <datepicker selector="form-control">
                        <input
                            type = "text"
                            class = "form-control input-sm"
                            placeholder = "Bank In To"
                            ng-model = "search.bankin_to"
                            ng-change = "onBankinToChanged(search.bankin_to)"
                        />
                    </datepicker>
                    <span class="input-group-addon fa fa-backward" ng-click="onPrevSingleClicked('bankin_to', search.bankin_to)"></span>
                    <span class="input-group-addon fa fa-forward" ng-click="onNextSingleClicked('bankin_to', search.bankin_to)"></span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('pay_method', 'Pay Method', ['class'=>'control-label search-title']) !!}
                {!! Form::select('pay_method', [''=>'All', 'cash' => 'Cash', 'cheque' => 'Cheque', 'tt' => 'T/t'],
                    null,
                    [
                    'class'=>'select form-control',
                    'ng-model'=>'search.pay_method',
                    'ng-change'=>'searchDB()'
                    ])
                !!}
            </div>
        </div>
        <div class="col-md-4 col-xs-6 text-right">
            <div class="row">
            <label for="display_num">Display</label>
            <select ng-model="search.itemsPerPage" ng-init="search.itemsPerPage='100'" name="pageNum" ng-change="pageNumChanged()">
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
</div>
{!! Form::close() !!}

<div class="row" style="padding-left: 15px; padding-top: 20px;">
    <div class="col-md-12 col-sm-12 col-xs-12">
        {{-- <button class="btn btn-primary" ng-click="exportData()"><i class="fa fa-file-excel-o"></i><span class="hidden-xs"> Export Excel</span></button> --}}
        <button class="btn btn-primary" type="submit" form="export_excel" name="export_excel" value="export_excel"><i class="fa fa-file-excel-o"></i><span class="hidden-xs"> Export Excel</span></button>
        <button class="btn btn-success" type="submit" form="submit_form" name="submitbtn" value="submit"><i class="fa fa-pencil-square-o"></i><span class="hidden-xs"> Batch Update</span></button>
        @if(auth()->user()->hasRole('admin') or auth()->user()->hasRole('account'))
            <button class="btn btn-success" type="submit" form="submit_form" name="submitbtn" value="verify"><i class="fa fa-check"></i><span class="hidden-xs"> Batch Verify</span></button>
            <button class="btn btn-danger" type="submit" form="submit_form" name="submitbtn" value="reject"><i class="fa fa-times"></i><span class="hidden-xs"> Batch Reject</span></button>
        @endif
        <span ng-show="spinner"> <i style="color:red;" class="fa fa-spinner fa-2x fa-spin"></i></span>
    </div>
</div>

<div class="row" style="padding-top: 15px;">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div ng-repeat="total in totals" class="col-md-3 col-md-6 col-xs-12">
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                    <strong>@{{total.name}}</strong>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-6 text-right">
                    Cash:
                </div>
                <div class="col-md-6 col-sm-6 col-xs-6 text-right" style="border: thin black solid">
                    <strong>@{{ total.cash ? total.cash : 0.00 | currency: "": 2}}</strong>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-6 text-right">
                    Cheque In:
                </div>
                <div class="col-md-6 col-sm-6 col-xs-6 text-right" style="border: thin black solid">
                    <strong>@{{ total.chequein ? total.chequein : 0.00 | currency: "": 2}}</strong>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-6 text-right">
                    Cheque Out:
                </div>
                <div class="col-md-6 col-sm-6 col-xs-6 text-right" style="border: thin black solid">
                    <strong>@{{ total.chequeout ? total.chequeout : 0.00 | currency: "": 2}}</strong>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-6 text-right">
                    TT:
                </div>
                <div class="col-md-6 col-sm-6 col-xs-6 text-right" style="border: thin black solid">
                    <strong>@{{ total.tt ? total.tt : 0.00 | currency: "": 2}}</strong>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-6 text-right">
                    Subtotal:
                </div>
                <div class="col-md-6 col-sm-6 col-xs-6 text-right" style="border: thin black solid">
                    <strong>@{{ total.subtotal ? total.subtotal : 0.00 | currency: "": 2}}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

    {!! Form::open(['id'=>'submit_form', 'method'=>'POST','action'=>['DetailRptController@submitPaySummary']]) !!}
    <div id="exportable_paysummary" style="padding-top: 20px;" style="overflow-x: scroll;">
        <table class="table table-list-search table-hover table-bordered">

            {{-- hidden table for excel export --}}
            <tr class="hidden">
                <td></td>
                <td>Happyice P/L</td>
                <td></td>
                <td></td>
                <td>Happyice Logistic P/L</td>
            </tr>
            <tr class="hidden">
                <td></td>
                <td>Total Cash</td>
                <td class="text-right">@{{total_cash_happyice | currency: "": 2}}</td>
                <td></td>
                <td>Total Cash</td>
                <td class="text-right">@{{total_cash_logistic | currency: "": 2}}</td>
            </tr>
            <tr class="hidden">
                <td></td>
                <td>Total Cheque/ TT</td>
                <td class="text-right">@{{total_cheque_happyice | currency: "": 2}}</td>
                <td></td>
                <td>Total Cheque/ TT</td>
                <td class="text-right">@{{total_cheque_logistic | currency: "": 2}}</td>
            </tr>
            <tr class="hidden" data-tableexport-display="always">
                <td></td>
            </tr>

            <tr style="background-color: #DDFDF8">
                <th class="col-md-1 text-center">
                    <input type="checkbox" id="checkAll" />
                </th>
                <th class="col-md-1 text-center">
                    #
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('transactions.paid_at')">
                    Pay Received Date
                    <span ng-if="search.sortName == 'transactions.paid_at' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'transactions.paid_at' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('transactions.pay_method')">
                    Pay Method
                    <span ng-if="search.sortName == 'transactions.pay_method' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'transactions.pay_method' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('total')">
                    Total
                    <span ng-if="search.sortName == 'total' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'total' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-2 text-center">
                    <a href="" ng-click="sortTable('profiles.id')">
                    Profile
                    <span ng-if="search.sortName == 'profiles.id' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'profiles.id' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-2 text-center">
                    <a href="" ng-click="sortTable('bankin_date')">
                    Bank In Date
                    <span ng-if="search.sortName == 'bankin_date' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'bankin_date' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-2 text-center">
                    Remark
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('updated_by')">
                    Updated By
                    <span ng-if="search.sortName == 'updated_by' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'updated_by' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('is_verified')">
                    Validation
                    <span ng-if="search.sortName == 'is_verified' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'is_verified' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
            </tr>
            <tbody>
                <tr dir-paginate="transaction in alldata | itemsPerPage:itemsPerPage | orderBy:sortType:sortReverse" pagination-id="payment_summary" total-items="totalCount">
                    <td class="col-md-1 text-center">{!! Form::checkbox('checkboxes[@{{$index}}]') !!}</td>
                    <td class="col-md-1 text-center">@{{ $index + indexFrom }} </td>
                    <td class="col-md-1 text-center">@{{ transaction.payreceived_date | delDate: "yyyy-MM-dd" }}</td>
                    <td class="col-md-1 text-center">@{{ transaction.pay_method | capitalize }}</td>
                    <td class="col-md-1 text-right">@{{ transaction.total | currency: "": 2 }} </td>
                    <td class="col-md-2 text-left">@{{ transaction.profile }} </td>
                    <td class="col-md-2 text-left" data-tableexport-display="none">
                        <datepicker date-format="yyyy-MM-dd">
                            <input
                                type="text"
                                name="bankin_dates[@{{$index}}]"
                                class="form-control"
                                placeholder="Date"
                                ng-model="transaction.bankin_date"
                            />
                        </datepicker>
                    </td>
                    <td class="col-md-2 text-left">
                        <textarea name="remarks[@{{$index}}]" ng-model="transaction.remark" class="form-control"></textarea>
                    </td>
                    <td class="col-md-1 text-left">@{{ transaction.name }} </td>
                    <td class="col-md-1 text-left">
                        <span class="col-md-12 col-sm-12 col-xs-12">@{{transaction.is_verified == null ? 'Pending' : (transaction.is_verified == 1 ? 'Verified' : 'Rejected')}}</span>
                        @if(auth()->user()->hasRole('admin') or auth()->user()->hasRole('account'))
                            <button ng-if="transaction.is_verified != '1'" class="btn btn-sm btn-success" ng-click="verifyPaysummary($event, transaction, 1)"><i class="fa fa-check"></i> Verify</button>
                            <button ng-if="transaction.is_verified != '0'" class="btn btn-sm btn-danger" ng-click="verifyPaysummary($event, transaction, 0)"><i class="fa fa-cross"></i> Reject</button>
                        @endif
                    </td>

                    <td class="hidden">{!! Form::text('paid_ats[@{{$index}}]', null, ['class'=>'form-control hidden', 'ng-model'=>'transaction.payreceived_date']) !!}</td>
                    <td class="hidden">{!! Form::text('pay_methods[@{{$index}}]', null, ['class'=>'form-control hidden', 'ng-model'=>'transaction.pay_method']) !!}</td>
                    <td class="hidden">{!! Form::text('profile_ids[@{{$index}}]', null, ['class'=>'form-control hidden', 'ng-model'=>'transaction.profile_id']) !!}</td>
                </tr>
                <tr ng-if="!alldata || alldata.length == 0">
                    <td colspan="14" class="text-center">No Records Found</td>
                </tr>
            </tbody>
        </table>
        {!! Form::close() !!}
        <div>
              <dir-pagination-controls max-size="5" pagination-id="payment_summary" direction-links="true" boundary-links="true" class="pull-left" on-page-change="pageChanged(newPageNumber)"> </dir-pagination-controls>
        </div>
    </div>
</div>

<script>
    $('.date').datetimepicker({
        format: 'YYYY-MM-DD'
    });
</script>