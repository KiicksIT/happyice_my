<div ng-controller="custPaySummaryController">
<div class="col-md-12 col-xs-12" style="padding-bottom:20px;">
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('profile', 'Profile', ['class'=>'control-label search-title']) !!}
                {!! Form::select('profile_id', [''=>'All']+$profiles::lists('name', 'id')->all(), null,
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
                <datepicker>
                    <input
                        type="text"
                        class="form-control input-sm"
                        name="payment_from"
                        placeholder="Payment From"
                        ng-model="search.payment_from"
                        ng-change="onPaymentFromChanged(search.payment_from)"
                    />
                </datepicker>
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('payment_to', 'Payment To', ['class'=>'control-label search-title']) !!}
                <datepicker>
                    <input
                        type="text"
                        class="form-control input-sm"
                        name="payment_to"
                        placeholder="Payment To"
                        ng-model="search.payment_to"
                        ng-change="onPaymentToChanged(search.payment_to)"
                    />
                </datepicker>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('bankin_from', 'Bank In From', ['class'=>'control-label search-title']) !!}
                <datepicker>
                    <input
                        type="text"
                        class="form-control input-sm"
                        name="bankin_from"
                        placeholder="Bank In From"
                        ng-model="search.bankin_from"
                        ng-change="onBankinFromChanged(search.bankin_from)"
                    />
                </datepicker>
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('bankin_to', 'Bank In To', ['class'=>'control-label search-title']) !!}
                <datepicker>
                    <input
                        type="text"
                        class="form-control input-sm"
                        name="bankin_to"
                        placeholder="Bank In To"
                        ng-model="search.bankin_to"
                        ng-change="onBankinToChanged(search.bankin_to)"
                    />
                </datepicker>
            </div>
        </div>
        <div class="col-md-4 col-xs-6 text-right">
            <div class="row">
            <label for="display_num">Display</label>
            <select ng-model="search.itemsPerPage" ng-init="search.itemsPerPage='100'"  name="pageNum" ng-change="pageNumChanged()">
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

<div class="row" style="padding-left: 15px; padding-top: 20px;">
    <div class="col-md-2 col-xs-12">
        <button class="btn btn-primary" ng-click="exportData()"><i class="fa fa-file-excel-o"></i><span class="hidden-xs"> Export Excel</span></button>
        <button class="btn btn-success" type="submit" form="submit_form"><i class="fa fa-pencil-square-o"></i><span class="hidden-xs"> Batch Update</span></button>
        <span ng-show="spinner"> <i style="color:red;" class="fa fa-spinner fa-2x fa-spin"></i></span>
    </div>
    <div class="col-md-3 col-xs-12">
        <div class="row">
            <div class="col-md-12 col-xs-12 text-center">
                <strong>HappyIce P/L</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-xs-6 text-right">
                Cash:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_cash_happyice ? total_cash_happyice : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-xs-6 text-right">
                Cheque:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_cheque_happyice ? total_cheque_happyice : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-xs-6 text-right">
                TT:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_tt_happyice ? total_tt_happyice : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-xs-12">
        <div class="row">
            <div class="col-md-12 col-xs-12 text-center">
                <strong>HappyIce Logistics P/L</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-xs-6 text-right">
                Cash:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_cash_logistic ? total_cash_logistic : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-xs-6 text-right">
                Cheque:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_cheque_logistic ? total_cheque_logistic : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-xs-6 text-right">
                TT:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_tt_logistic ? total_tt_logistic : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-xs-12">
        <div class="row">
            <div class="col-md-12 col-xs-12 text-center">
                <strong>All Profile</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-xs-6 text-right">
                Cash:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_cash_all ? total_cash_all : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-xs-6 text-right">
                Cheque:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_cheque_all ? total_cheque_all : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 col-xs-6 text-right">
                TT:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_tt_all ? total_tt_all : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
    </div>
</div>

    {!! Form::open(['id'=>'submit_form', 'method'=>'POST','action'=>['DetailRptController@submitPaySummary']]) !!}
    <div id="exportable" style="padding-top: 20px;" style="overflow-x: scroll;">
        <table class="table table-list-search table-hover table-bordered">

            {{-- hidden table for excel export --}}
            <tr class="hidden">
                <td></td>
                <td data-tableexport-display="always">Happyice P/L</td>
                <td></td>
                <td></td>
                <td data-tableexport-display="always">Happyice Logistic P/L</td>
            </tr>
            <tr class="hidden">
                <td></td>
                <td data-tableexport-display="always">Total Cash</td>
                <td data-tableexport-display="always" class="text-right">@{{total_cash_happyice | currency: "": 2}}</td>
                <td></td>
                <td data-tableexport-display="always">Total Cash</td>
                <td data-tableexport-display="always" class="text-right">@{{total_cash_logistic | currency: "": 2}}</td>
            </tr>
            <tr class="hidden">
                <td></td>
                <td data-tableexport-display="always">Total Cheque/ TT</td>
                <td data-tableexport-display="always" class="text-right">@{{total_cheque_happyice | currency: "": 2}}</td>
                <td></td>
                <td data-tableexport-display="always">Total Cheque/ TT</td>
                <td data-tableexport-display="always" class="text-right">@{{total_cheque_logistic | currency: "": 2}}</td>
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
            </tr>
            <tbody>
                <tr dir-paginate="transaction in alldata | itemsPerPage:itemsPerPage | orderBy:sortType:sortReverse" pagination-id="payment_summary" total-items="totalCount">
                    <td class="col-md-1 text-center">{!! Form::checkbox('checkboxes[@{{$index}}]') !!}</td>
                    <td class="col-md-1 text-center">@{{ $index + indexFrom }} </td>
                    <td class="col-md-1 text-center">@{{ transaction.payreceived_date | delDate: "yyyy-MM-dd" }}</td>
                    <td class="col-md-1 text-center">@{{ transaction.pay_method | capitalize }}</td>
                    <td class="col-md-1 text-right">@{{ transaction.total }} </td>
                    <td class="col-md-2 text-left">@{{ transaction.profile }} </td>
                    <td class="col-md-2 text-left">
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