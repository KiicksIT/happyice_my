@extends('template')
@section('title')
    {{ $PERSON_TITLE }}
@stop
@section('content')

@inject('custcategories', 'App\Custcategory')

    <div class="row">
        <a class="title_hyper pull-left" href="/detailrpt/operation"><h1>Price Matrix <i class="fa fa-book"></i></h1></a>
    </div>

    <div class="panel panel-primary" ng-app="app" ng-controller="pricematrixController">
        <div class="panel-heading">
            <div class="panel-title">
                <div class="pull-left display_panel_title">
                    <h3 class="panel-title"><strong>Price Matrix</strong></h3>
                </div>
            </div>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="form-group col-md-2 col-sm-4 col-xs-6">
                        {!! Form::label('cust_id', 'Customer ID', ['class'=>'control-label search-title']) !!}
                        {!! Form::text('cust_id',
                            null,
                            [
                                'class'=>'form-control input-sm',
                                'ng-model' => 'search.cust_id',
                                'placeholder'=>'ID',
                            ])
                        !!}
                    </div>
                    <div class="form-group col-md-2 col-sm-4 col-xs-6">
                        {!! Form::label('custcategory_id', 'Cust Category', ['class'=>'control-label search-title']) !!}
                        {!! Form::select('custcategory_id', [''=>'All']+$custcategories::orderBy('name')->pluck('name', 'id')->all(),
                            null,
                            [
                                'class'=>'select form-control',
                                'ng-model' => 'search.custcategory_id'
                            ])
                        !!}
                    </div>
                    <div class="form-group col-md-2 col-sm-4 col-xs-6">
                        {!! Form::label('company', 'Cust ID Name', ['class'=>'control-label search-title']) !!}
                        {!! Form::text('company',
                            null,
                            [
                                'class'=>'form-control input-sm',
                                'placeholder'=>'Cust ID Name',
                                'ng-model' => 'search.company'
                            ])
                        !!}
                    </div>
                    <div class="form-group col-md-2 col-sm-4 col-xs-6">
                        {!! Form::label('product_id', 'Product ID', ['class'=>'control-label search-title']) !!}
                        {!! Form::text('product_id',
                            null,
                            [
                                'class'=>'form-control input-sm',
                                'placeholder'=>'Product ID',
                                'ng-model' => 'search.product_id'
                            ])
                        !!}
                    </div>
                    <div class="form-group col-md-2 col-sm-4 col-xs-6">
                        {!! Form::label('name', 'Product Name', ['class'=>'control-label search-title']) !!}
                        {!! Form::text('name',
                            null,
                            [
                                'class'=>'form-control input-sm',
                                'placeholder'=>'Name',
                                'ng-model' => 'search.name'
                            ])
                        !!}
                    </div>

                    <div class="form-group col-md-2 col-sm-4 col-xs-6">
                        {!! Form::label('is_inventory', 'Product Type', ['class'=>'control-label search-title']) !!}
                        {!! Form::select('is_inventory',
                            ['1'=>'Inventory Item', 'All'=>'All'],
                            null,
                            [
                                'class'=>'select form-control',
                                'ng-model' => 'search.is_inventory'
                            ])
                        !!}
                    </div>
                </div>
            </div>

            <div class="row" style="padding-left: 15px;">
                <div class="col-md-8 col-sm-12 col-xs-12">
                    <button type="submit" class="btn btn-info" ng-click="searchDB($event)"><i class="fa fa-search"></i><span class="hidden-xs"></span> Search</button>

                    <span ng-show="spinner"> <i class="fa fa-spinner fa-2x fa-spin"></i></span>
                </div>
{{--
                <div class="col-md-4 col-sm-4 col-xs-12 text-right">
                    <div class="row">
                        <label for="display_num">Display</label>
                        <select ng-model="itemsPerPage" name="pageNum" ng-init="itemsPerPage='All'" ng-change="pageNumChanged()">
                            <option ng-value="100">100</option>
                            <option ng-value="200">200</option>
                            <option ng-value="All">All</option>
                        </select>
                        <label for="display_num2" style="padding-right: 20px">per Page</label>
                    </div>
                    <div class="row">
                        <label class="" style="padding-right:18px;" for="totalnum">Showing @{{people.length}} of @{{totalCount}} entries</label>
                    </div>
                </div> --}}
            </div>
            {!! Form::close() !!}

            <div class="table-responsive" id="exportable" style="padding-top: 20px;">
                <table id="datatable" class="table table-list-search table-bordered table-fixedheader">
                    <thead style="font-size: 11px;">
                    <tr style="background-color: #DDFDF8">
                        <th class="col-md-1 text-center">
                            #
                        </th>
                        <th class="col-md-1 text-center">
                            Customer
                        </th>
                        <th class="col-md-1 text-center">
                            Cost Rate (%)
                        </th>
                        <th class="col-md-1 text-center" ng-repeat="item in items">
                            (@{{item.product_id}}) @{{item.name}}
                        </th>
                    </tr>
                    </thead>

                    <tbody style="font-size: 11px;">
                        <tr ng-repeat="person in people">
                            <td class="col-md-1 text-center">
                                @{{$index + 1}}
                            </td>
                            <td class="col-md-1 text-center">
                                (@{{person.cust_id}}) @{{person.company}}
                            </td>
                            <td class="col-md-1">
                                {!! Form::text('operation_notes[@{{person.person_id}}]', null,
                                [
                                    'class'=>'text-right input-xs',
                                    'style'=>'min-width: 55px; font-size: 12px;',
                                    'ng-model'=>'person.cost_rate',
                                    'ng-change'=>'onCostrateChanged(person)',
                                    'ng-model-options'=>'{ debounce: 600 }'
                                ]) !!}
                            </td>
                            <td class="col-md-1 text-right" style="min-width: 70px;" ng-repeat="price in prices[$index]">
                                <div class="form-group">
                                    <label for="retail_price">Retail Price</label>
                                    <input type="text" class="input-xs text-right" ng-model="price.retail_price" ng-change="onPriceChanged(price)" ng-model-options="{debounce: 600}">
                                </div>
                                <div class="form-group">
                                    <label for="quote_price">Quote Price</label>
                                    <input type="text" class="input-xs text-right" ng-model="price.quote_price" ng-change="onPriceChanged(price)" ng-model-options="{debounce: 600}">
                                </div>
                            </td>
                        </tr>

                        <tr ng-if="!people.length > 0">
                            <td colspan="18" class="text-center">No Records Found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>

        </div>
    </div>

<script src="/js/price_matrix.js"></script>

@stop