@extends('template')
@section('title')
{{ $ITEM_TITLE }}
@stop
@section('content')

    <div class="row">
        <a class="title_hyper pull-left" href="/item">
            <h1> {{ $ITEM_TITLE }}<i class="fa fa-shopping-cart"></i></h1>
        </a>
    </div>
    <div ng-app="app">
        <div class="panel panel-info" ng-cloak>
            <div class="panel-heading">
                <div class="panel-title">
                    <div class="pull-right">
                        @cannot('transaction_view')
                        @if(!auth()->user()->hasRole('logistics'))
                            <a href="/item/create" class="btn btn-success">+ New Product</a>
                            <a href="/inventory/create" class="btn btn-primary">+ Stock Movement</a>
                        @endif
                        <a href="/inventory/setting" class="btn btn-warning"><i class="fa fa-cog"></i> Limit Setting</a>
                        <a href="/inventory/email" class="btn btn-info"> Email Alert Limit Setting</a>
                        @endcannot
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <ul class="nav nav-pills nav-justified" role="tablist">
                            <li class="active"><a href="#item" role="tab" data-toggle="tab">Item</a></li>
                            @cannot('transaction_view')
                                <li><a href="#stock" role="tab" data-toggle="tab">Stock Movement</a></li>
                                @if(Auth::user()->hasRole('admin'))
                                    <li><a href="#unit_cost" role="tab" data-toggle="tab">Unit Cost</a></li>
                                @endif
                            @endcannot
                        </ul>
                    </div>

                    <div class="panel-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="item">
                                @include('item.item_list')
                            </div>
                            <div class="tab-pane" id="stock">
                                @include('item.stockmovement')
                            </div>
                            <div class="tab-pane" id="unit_cost">
                                @include('item.unit_cost')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="/js/item.js"></script>
@stop