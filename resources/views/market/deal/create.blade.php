@extends('template')
@section('title')
Deals
@stop
@section('content')

<div class="create_edit" ng-app="app" ng-controller="dealsController">
<div class="panel panel-primary">

    <div class="panel-heading">
        <h3 class="panel-title"><strong>New Deal</strong></h3>
    </div>

    <div class="panel-body">
        {!! Form::model($transaction = new \App\DtdTransaction, ['action'=>'MarketingController@storeDeal']) !!}

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('person_id', 'Customer', ['class'=>'control-label']) !!}
                        {!! Form::select('person_id',
                            [''=>null] + $people->select(DB::raw("CONCAT(cust_id,' - ',company) AS full, id"))->whereActive('Yes')->reOrderBy('cust_id', 'asc')->lists('full', 'id')->all(),
                            null,
                            [
                            'id'=>'person_id',
                            'class'=>'person form-control',
                            'ng-model'=>'personModel',
                            'ng-change'=>'onPersonSelected(personModel)'
                            ])
                        !!}
                    </div>
                </div>
            </div>
            {{-- division of panel --}}
            <div class="panel panel-primary">
                <div class="panel-body">

                    <div class="panel-body">
                        <div class="table-responsive">
                        <table class="table table-list-search table-hover table-bordered">
                            <tr style="background-color: #DDFDF8">
                                <th class="col-md-1 text-center">
                                    #
                                </th>
                                <th class="col-md-1 text-center">
                                    INV #
                                </th>
                                <th class="col-md-1 text-center">
                                    ID
                                </th>
                                <th class="col-md-1 text-center">
                                    Company
                                </th>
                                <th class="col-md-1 text-center">
                                    Del Postcode
                                </th>
                                <th class="col-md-1 text-center">
                                    Status
                                </th>
                                <th class="col-md-1 text-center">
                                    Delivery Date
                                </th>
                                <th class="col-md-1 text-center">
                                    Total Amount
                                </th>
                                <th class="col-md-1 text-center">
                                    Total Qty
                                </th>
                                <th class="col-md-1 text-center">
                                    Payment
                                </th>
                            </tr>

                            <tbody>

                                <tr dir-paginate="transaction in transactions | filter:search | orderBy:sortType:sortReverse | itemsPerPage:itemsPerPage"  current-page="currentPage" ng-controller="repeatController">
                                    <td class="col-md-1 text-center">@{{ number }} </td>
                                    <td class="col-md-1 text-center">
                                        <a href="/transaction/@{{ transaction.id }}/edit">
                                            @{{ transaction.id }}
                                        </a>
                                    </td>
                                    <td class="col-md-1 text-center">@{{ transaction.person.cust_id }} </td>
                                    <td class="col-md-1 text-center">@{{ transaction.person.company }} </td>
                                    <td class="col-md-1 text-center">@{{ transaction.person.del_postcode }} </td>
                                    {{-- status by color --}}
                                    <td class="col-md-1 text-center" style="color: red;" ng-if="transaction.status == 'Pending'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: orange;" ng-if="transaction.status == 'Confirmed'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: green;" ng-if="transaction.status == 'Delivered' || transaction.status == 'Submitted'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: black; background-color:orange;" ng-if="transaction.status == 'Verified Owe'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: black; background-color:green;" ng-if="transaction.status == 'Verified Paid'">
                                        @{{ transaction.status }}
                                    </td>
                                    <td class="col-md-1 text-center" ng-if="transaction.status == 'Cancelled'">
                                        <span style="color: white; background-color: red;"> @{{ transaction.status }} </span>
                                    </td>
                                    {{-- status by color ended --}}
                                    <td class="col-md-1 text-center">@{{ transaction.delivery_date }}</td>
                                    <td class="col-md-1 text-center">@{{ transaction.total }}</td>
                                    <td class="col-md-1 text-center">@{{ transaction.total_qty }}</td>
                                    {{-- pay status --}}
                                    <td class="col-md-1 text-center" style="color: red;" ng-if="transaction.pay_status == 'Owe'">
                                        @{{ transaction.person.cust_id[0] ? '-' : transaction.pay_status }}
                                    </td>
                                    <td class="col-md-1 text-center" style="color: green;" ng-if="transaction.pay_status == 'Paid'">
                                        @{{ transaction.person.cust_id[0] ? '-' : transaction.pay_status }}
                                    </td>
                                </tr>
                                <tr ng-if="(transactions | filter:search).length == 0 || ! transactions.length">
                                    <td colspan="10" class="text-center">No Records Found</td>
                                </tr>

                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
            {{-- end of the division --}}

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group pull-right" style="padding: 30px 0px 0px 0px;">
                        {!! Form::submit('Add', ['class'=> 'btn btn-success']) !!}
                        <a href="/market/deal" class="btn btn-default">Cancel</a>
                    </div>
                </div>
            </div>
        {!! Form::close() !!}
    </div>
</div>
</div>

<script src="/js/deal_create.js"></script>
<script>
    $('.select').select2();
</script>

@stop