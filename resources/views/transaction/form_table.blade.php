<div class="panel panel-primary">
    <div class="panel-heading">
        <div class="panel-title">
            <div class="pull-left display_panel_title">
                @unless($transaction->status == 'Cancelled')
                <h3 class="panel-title">
                    <strong>Create List :
                        <br>
                        {{$person->cust_id}} - {{$person->company}}
                    </strong>
                </h3>
                @else
                <h3 class="panel-title">
                    <strong>
                        <del>Create List :
                            <br>
                            {{$person->cust_id}} - {{$person->company}}</del>
                    </strong>
                </h3>
                @endunless
            </div>
        </div>
    </div>

    @php
        $amount_access = true;

        if(auth()->user()->hasRole('logistics')) {
            $amount_access = false;
        }
    @endphp

    <div class="panel-body">
        <div>
            <div class="table-responsive">
                <table class="table table-list-search table-hover table-bordered table-condensed">
                    <tr style="background-color: #DDFDF8;">
                        <th class="text-center" >
                            Item
                        </th>
                        <th class="text-center">
                            Ctn
                        </th>
                        <th class="text-center {{$amount_access ? '' : 'hidden'}}">
                            Pieces
                        </th>
                        <th class="text-center {{$amount_access ? '' : 'hidden'}}">
                            Retail ({{$transaction->person->profile->currency ? $transaction->person->profile->currency->symbol: '$'}})
                        </th>
                        <th class="text-center {{$amount_access ? '' : 'hidden'}}">
                            Quote ({{$transaction->person->profile->currency ? $transaction->person->profile->currency->symbol: '$'}})
                        </th>
                        <th class="text-center {{$amount_access ? '' : 'hidden'}}">
                            Amount ({{$transaction->person->profile->currency ? $transaction->person->profile->currency->symbol: '$'}})
                        </th>
                    </tr>

                    <tr ng-repeat="price in prices">
                        <td class="col-md-4 col-xs-1" >
                            <span class="hidden-xs">
                                <strong>@{{price.product_id}}</strong>
                                - @{{price.name}}
                                <small>@{{price.remark}}</small>
                            </span>
                            <span class="hidden-lg hidden-md hidden-sm">
                                <strong>@{{price.product_id}}</strong><br>
                                @{{price.name}}<br>
                                <small>@{{price.remark}}</small>
                            </span>
                        </td>
                        <td class="col-md-1 col-xs-2">
                            <input type="text" name="ctn[@{{price.item_id}}]" ng-model="price.ctn" style="min-width: 70px; max-width: 100px;" class="text-right form-control" autocomplete="off_string"/>
                        </td>
                        <td class="col-md-1 col-xs-2 {{$amount_access ? '' : 'hidden'}}">
                            <input type="text" name="pcs[@{{price.item_id}}]" ng-model="price.pcs" style="min-width: 70px; max-width: 100px;" class="text-right form-control" autocomplete="off_string" ng-readonly="!price.is_inventory"/>
                        </td>
                        <td class="col-md-2 col-xs-2 {{$amount_access ? '' : 'hidden'}}">
                            <input type="text" name="retail[@{{price.item_id}}]" ng-model="price.retail_price" class="text-right form-control" autocomplete="off_string" readonly="readonly" />
                        </td>
                        <td class="col-md-2 col-xs-2 {{$amount_access ? '' : 'hidden'}}">
                            <input type="text" name="quote[@{{price.item_id}}]" ng-model="price.quote_price" style="min-width: 70px;" class="text-right form-control" autocomplete="off_string" />
                        </td>
                        <td class="col-md-2 col-xs-3 {{$amount_access ? '' : 'hidden'}}">
                            <input type="text" name="amounts[@{{price.item_id}}]" ng-model="price.amount" ng-value="getAmount(price)" class="text-right form-control" readonly="readonly"/>
                        </td>
                    </tr>
                    <tr ng-if="!prices || prices.length == 0">
                        <td colspan="18" class="text-center">No Records Found</td>
                    </tr>
                    <tr>
                        <td class="col-md-1 col-xs-2 text-center"><strong>Total</strong></td>
                        <td colspan="4" class="col-md-3 text-right {{$amount_access ? '' : 'hidden'}}">
                            <td class="text-right" {{$amount_access ? '' : 'hidden'}}>
                                <strong>
                                    <input type="text" name="total_create" class="text-right form-control" readonly="readonly" ng-value="getTotal()"/>
                                </strong>
                            </td>
                            <td class="text-right {{$amount_access ? 'hidden' : ''}}" >
                                <strong>
                                    <input type="text" name="total_create" class="text-right form-control" readonly="readonly" ng-value="getTotalQty()" style="min-width: 70px; max-width: 100px;"/>
                                </strong>
                            </td>
                        </td>
                    </tr>

                </table>
            </div>

            <div>
                <table class="table table-list-search table-hover table-bordered table-condensed hidden" style="font-size: 15px;">
                    <tr style="background-color: #DDFDF8;">
                        <th class="text-center">
                            Item
                        </th>
                    </tr>

                    <tr ng-repeat="price in prices">
                        <td>
                            <span>
                                <strong>
                                    @{{price.product_id}}
                                </strong>
                                <br>
                                @{{price.name}}
                                <small>
                                    @{{price.remark}}
                                </small>
                            </span>
                            <br>
                            <span class="row">
                                <span class="col-xs-6">
                                    <label for="ctn" class="form-label">Ctn</label>
                                    <input type="text" name="ctn[@{{price.item_id}}]" ng-model="price.ctn"  class="text-right form-control" autocomplete="off_string"/>
                                </span>
                                <span class="col-xs-6">
                                    <label for="pcs" class="form-label">Pcs</label>
                                    <input type="text" name="pcs[@{{price.item_id}}]" ng-model="price.pcs" class="text-right form-control" autocomplete="off_string" ng-readonly="!price.is_inventory"/>
                                </span>
                            </span>
                            <span class="row">
                                <span class="col-xs-6">
                                    <label for="ctn" class="form-label">Retail ({{$transaction->person->profile->currency ? $transaction->person->profile->currency->symbol: '$'}})</label>
                                    <input type="text" name="retail[@{{price.item_id}}]" ng-model="price.retail_price" class="text-right form-control" autocomplete="off_string" readonly="readonly"/>
                                </span>
                                <span class="col-xs-6">
                                    <label for="pcs" class="form-label">Quote ({{$transaction->person->profile->currency ? $transaction->person->profile->currency->symbol: '$'}})</label>
                                    <input type="text" name="quote[@{{price.item_id}}]" ng-model="price.quote_price" class="text-right form-control" autocomplete="off_string"/>
                                </span>
                            </span>
                            <span class="row">
                                <span class="col-xs-12">
                                    <label for="ctn" class="form-label">Amount ({{$transaction->person->profile->currency ? $transaction->person->profile->currency->symbol: '$'}})</label>
                                    <input type="text" name="amount[@{{price.item_id}}]" ng-model="price.amount" ng-value="getAmount(price)" class="text-right form-control" readonly="readonly"/>
                                </span>
                            </span>
                        </td>
                    </tr>
                    <tr ng-if="!prices || prices.length == 0">
                        <td colspan="18" class="text-center">No Records Found</td>
                    </tr>
                    <tr>
                        <td>
                            <label for="total">Total</label>
                            <strong>
                                <input type="text" name="total_create" class="text-right form-control" readonly="readonly" ng-value="getTotal()"/>
                            </strong>
                        </td>
                    </tr>

                </table>
            </div>

        </div>
    </div>
</div>
