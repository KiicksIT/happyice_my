@inject('people', 'App\Person')

<div class="col-md-12" ng-cloak>
    <div class="panel panel-success row">
        <div class="panel-heading">
            <div class="panel-title">
                <div class="pull-left display_panel_title">
                    @unless($transaction->status == 'Cancelled' or $transaction->status == 'Deleted')
                    <h3 class="panel-title"><strong>Selected : {{$person->cust_id}} - {{$person->company}} ({{$person->name}})</strong></h3>
                    @else
                    <h3 class="panel-title"><strong><del>Selected : {{$person->cust_id}} - {{$person->company}} ({{$person->name}})</del></strong></h3>
                    @endunless
                </div>
            </div>
        </div>

        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-list-search table-hover table-bordered">
                    <tr style="background-color: #DDFDF8">
                        <th class="col-md-1 text-center">
                            #
                        </th>
                        <th class="col-md-1 text-center">
                            Item Code
                        </th>
                        <th class="col-md-4 text-center">
                            Description
                        </th>
                        <th class="col-md-1 text-center">
                            Pieces
                        </th>
                        <th class="col-md-1 text-center">
                            Quantity
                        </th>
                        <th class="col-md-1 text-center">
                            Unit Price
                        </th>
                        <th class="col-md-1 text-center">
                            Amount
                        </th>
                        <th class="col-md-1 text-center">
                            Action
                        </th>
                    </tr>

                    <tbody>
                        <tr ng-repeat="deal in deals">
                            <td class="col-md-1 text-center">@{{ $index + 1 }}</td>
                            <td class="col-md-1 text-center">@{{ deal.product_id }}</td>
                            <td class="col-md-5">@{{ deal.item_name }}<br> <small>@{{ deal.item_remark }}</small></td>
                            <td class="col-md-1 text-right">@{{ deal.pieces }}</td>
                            <td class="col-md-2 @{{deal.is_inventory===1 ? 'text-right' : 'text-left'}}">
                                <span ng-if="!deal.divisor && deal.is_inventory === 1">
                                    @{{ deal.qty % 1 == 0 ? Math.round(deal.qty) : deal.qty }} @{{ deal.unit }}
                                </span>
                                <span ng-if="(deal.divisor != 1.00 && deal.divisor != null)  && deal.is_inventory == 1">
                                    @{{deal.dividend | removeZero}} / @{{deal.divisor | removeZero}}
                                </span>
                                <span ng-if="deal.divisor == 1.00 && deal.is_inventory == 1">
                                    @{{deal.qty}}
                                </span>
                                <span ng-if="deal.is_inventory === 0 && deal.dividend == 1.00">
                                    1 Unit
                                </span>
                                <span ng-if="deal.is_inventory === 0 && deal.dividend != 1.00">
                                    @{{deal.dividend | removeZero}} Unit
                                </span>
                            </td>
                            {{-- unit price --}}
                            <td class="col-md-1 text-right" ng-if="! deal.unit_price">@{{ (deal.amount / deal.qty) | currency: ""}}</td>
                            <td class="col-md-1 text-right" ng-if="deal.unit_price">@{{ deal.unit_price }}</td>
                            {{-- deal amount --}}
                            <td class="col-md-1 text-right" ng-if="deal.amount != 0">@{{ (deal.amount/100 * 100) | currency: "" }}</td>
                            <td class="col-md-1 text-right" ng-if="deal.amount == 0"><strong>FOC</strong></td>
                            <td class="col-md-1 text-center">
                                @php
                                    $valid = false;
                                    $status = $transaction->status;

                                    if($transaction->is_freeze !== 1) {
                                        foreach(Auth::user()->roles as $role) {
                                            $access = $role->name;
                                            switch($access) {
                                                case 'admin':
                                                case 'account':
                                                case 'supervisor':
                                                case 'accountadmin':
                                                    $valid = true;
                                                    break;
                                                case 'franchisee':
                                                    $valid = false;
                                                    break;
                                                default:
                                                    switch($status) {
                                                        case 'Draft':
                                                        case 'Pending':
                                                        case 'Confirmed':
                                                            $valid = true;
                                                            break;
                                                        default:
                                                            $valid = false;
                                                    }
                                            }
                                        }
                                    }else {
                                        $valid = false;
                                    }
                                @endphp

                                @if($valid)
                                    <button class="btn btn-danger btn-sm btn-delete" ng-click="confirmDelete($event, deal.deal_id)">Delete</button>
                                @else
                                    <button class="btn btn-danger btn-sm btn-delete" ng-click="confirmDelete($event, deal.deal_id)" disabled>Delete</button>
                                @endif
                            </td>
                        </tr>

                        <tr ng-if="delivery">
                            <td colspan="3" class="text-right">
                                <strong>Delivery Fee</strong>
                            </td>
                            <td colspan="3"></td>
                            <td class="col-md-1 text-right">
                                <strong>@{{delivery | currency: ""}}</strong>
                            </td>
                        </tr>
                        @if($transaction->gst and $transaction->is_gst_inclusive)
                            <tr ng-if="deals.length>0">
                                <td colspan="3" class="text-right">
                                    <strong>Total</strong>
                                </td>
                                <td class="col-md-1 text-right">
                                    @{{getTotalPieces()}}
                                </td>
                                <td class="col-md-1 text-right">
                                    <strong>@{{totalqtyModel}}</strong>
                                </td>
                                <td colspan="1"></td>
                                <td class="col-md-1 text-right">
                                    <strong>@{{totalModel | currency: ""}}</strong>
                                </td>
                            </tr>
                            <tr ng-if="deals.length>0">
                                <td colspan="3" class="text-right">
                                    <strong>GST ({{number_format($transaction->gst_rate)}}%)</strong>
                                </td>
                                <td></td>
                                <td colspan="2"></td>
                                <td class="col-md-1 text-right">
                                    @{{taxModel | currency: ""}}
                                </td>
                            </tr>
                            <tr ng-if="deals.length>0">
                                <td colspan="3" class="text-right">
                                    <strong>Exclude GST</strong>
                                </td>
                                <td></td>
                                <td colspan="2"></td>
                                <td class="col-md-1 text-right">
                                    @{{subtotalModel | currency: ""}}
                                </td>
                            </tr>
                        @elseif($transaction->gst and !$transaction->is_gst_inclusive)
                            <tr ng-if="deals.length>0">
                                <td colspan="3" class="text-right">
                                    <strong>Subtotal</strong>
                                </td>
                                <td></td>
                                <td colspan="2"></td>
                                <td class="col-md-1 text-right">
                                    @{{subtotalModel}}
                                </td>
                            </tr>
                            <tr ng-if="deals.length>0">
                                <td colspan="3" class="text-right">
                                    <strong>GST ({{number_format($transaction->gst_rate)}}%)</strong>
                                </td>
                                <td></td>
                                <td colspan="2"></td>
                                <td class="col-md-1 text-right">
                                    @{{taxModel}}
                                </td>
                            </tr>
                            <tr ng-if="deals.length>0">
                                <td colspan="3" class="text-right">
                                    <strong>Total</strong>
                                </td>
                                <td class="col-md-1 text-right">
                                    @{{getTotalPieces()}}
                                </td>
                                <td class="col-md-1 text-right">
                                    <strong>@{{totalqtyModel}}</strong>
                                </td>
                                <td colspan="1"></td>
                                <td class="col-md-1 text-right">
                                    <strong>@{{totalModel}}</strong>
                                </td>
                            </tr>
                        @else
                            <tr ng-if="deals.length>0">
                                <td colspan="3" class="text-right">
                                    <strong>Total</strong>
                                </td>
                                <td class="col-md-1 text-right">
                                    @{{getTotalPieces()}}
                                </td>
                                <td class="col-md-1 text-right">
                                    <strong>@{{totalqtyModel}}</strong>
                                </td>
                                <td colspan="1"></td>
                                <td class="col-md-1 text-right">
                                    <strong>@{{totalModel}}</strong>
                                </td>
                            </tr>
                        @endif
                        <tr ng-show="(deals | filter:search).deals == 0 || ! deals.length">
                            <td colspan="12" class="text-center">No Records Found!</td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <div style="font-size: 15px;">
                <table class="table table-list-search table-hover table-bordered table-condensed">
                    <tr style="background-color: #DDFDF8">
                        <th class="text-center">
                            Item
                        </th>
                    </tr>

                    <tr ng-repeat="deal in deals">
                        <td>
                            <span>
                                @{{ $index + 1 }}.
                                <strong>
                                    @{{ deal.product_id }}
                                </strong>
                                <br>
                                <strong>
                                    @{{ deal.item_name }} <small>@{{ deal.item_remark }}</small>
                                </strong>
                            </span>
                            <br>
                            <span class="row">
                                <span class="col-xs-5">
                                    <strong>
                                        Pcs
                                    </strong>
                                </span>
                                <span class="col-xs-7 text-right">
                                    @{{ deal.pieces }}
                                </span>
                            </span>
                            <span class="row">
                                <span class="col-xs-5">
                                    <strong>
                                        Qty
                                    </strong>
                                </span>
                                <span class="col-xs-7 @{{deal.is_inventory===1 ? 'text-right' : 'text-left'}}">
                                    <span ng-if="!deal.divisor && deal.is_inventory === 1">
                                        @{{ deal.qty % 1 == 0 ? Math.round(deal.qty) : deal.qty }} @{{ deal.unit }}
                                    </span>
                                    <span ng-if="(deal.divisor != 1.00 && deal.divisor != null)  && deal.is_inventory == 1">
                                        @{{deal.dividend | removeZero}} / @{{deal.divisor | removeZero}}
                                    </span>
                                    <span ng-if="deal.divisor == 1.00 && deal.is_inventory == 1">
                                        @{{deal.qty}}
                                    </span>
                                    <span ng-if="deal.is_inventory === 0 && deal.dividend == 1.00">
                                        1 Unit
                                    </span>
                                    <span ng-if="deal.is_inventory === 0 && deal.dividend != 1.00">
                                        @{{deal.dividend | removeZero}} Unit
                                    </span>
                                </span>
                            </span>
                            <span class="row">
                                <span class="col-xs-5">
                                    <strong>
                                        Unit Price
                                    </strong>
                                </span>
                                <span class="col-xs-7 text-right">
                                    <span ng-if="! deal.unit_price">
                                       @{{ (deal.amount / deal.qty) | currency: ""}}
                                    </span>
                                    <span ng-if="deal.unit_price">
                                       @{{ deal.unit_price }}
                                    </span>
                                </span>
                            </span>
                            <span class="row">
                                <span class="col-xs-5">
                                    <strong>
                                        Amount
                                    </strong>
                                </span>
                                <span class="col-xs-7 text-right">
                                    <span ng-if="deal.amount != 0">
                                        @{{ (deal.amount/100 * 100) | currency: "" }}
                                    </span>
                                    <span ng-if="deal.amount == 0">
                                        <strong>
                                            FOC
                                        </strong>
                                    </span>
                                </span>
                            </span>
                            <span class="row">
                                @php
                                    $valid = false;
                                    $status = $transaction->status;

                                    if($transaction->is_freeze !== 1) {
                                        foreach(Auth::user()->roles as $role) {
                                            $access = $role->name;
                                            switch($access) {
                                                case 'admin':
                                                case 'account':
                                                case 'supervisor':
                                                case 'accountadmin':
                                                    $valid = true;
                                                    break;
                                                case 'franchisee':
                                                    $valid = false;
                                                    break;
                                                default:
                                                    switch($status) {
                                                        case 'Draft':
                                                        case 'Pending':
                                                        case 'Confirmed':
                                                            $valid = true;
                                                            break;
                                                        default:
                                                            $valid = false;
                                                    }
                                            }
                                        }
                                    }else {
                                        $valid = false;
                                    }
                                @endphp

                                @if($valid)
                                    <button class="btn btn-danger btn-block btn-delete" ng-click="confirmDelete($event, deal.deal_id)">Delete</button>
                                @else
                                    <button class="btn btn-danger btn-block btn-delete" ng-click="confirmDelete($event, deal.deal_id)" disabled>Delete</button>
                                @endif
                            </span>
                        </td>
                    </tr>

                    <tr ng-if="delivery">
                        <span class="row">
                            <span class="col-xs-5">
                                <strong>
                                    Delivery Fee
                                </strong>
                            </span>
                            <span class="col-xs-7 text-right">
                                <strong>
                                    @{{delivery | currency: ""}}
                                </strong>
                            </span>
                        </span>
                    </tr>

                        @if($transaction->gst and $transaction->is_gst_inclusive)
                            <tr ng-if="deals.length>0">
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            Total Pcs
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{getTotalPieces()}}
                                        </strong>
                                    </span>
                                </span>
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            Total Qty
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{totalqtyModel}}
                                        </strong>
                                    </span>
                                </span>
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            Total Amount
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{totalModel | currency: ""}}
                                        </strong>
                                    </span>
                                </span>
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            GST ({{number_format($transaction->gst_rate)}}%)
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{taxModel | currency: ""}}
                                        </strong>
                                    </span>
                                </span>
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            Exclude GST
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{subtotalModel | currency: ""}}
                                        </strong>
                                    </span>
                                </span>
                            </tr>
                        @elseif($transaction->gst and !$transaction->is_gst_inclusive)
                            <tr ng-if="deals.length>0">
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            Total Pcs
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{getTotalPieces()}}
                                        </strong>
                                    </span>
                                </span>
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            Total Qty
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{totalqtyModel}}
                                        </strong>
                                    </span>
                                </span>
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            Subtotal
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{subtotalModel}}
                                        </strong>
                                    </span>
                                </span>
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            GST ({{number_format($transaction->gst_rate)}}%)
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{taxModel | currency: ""}}
                                        </strong>
                                    </span>
                                </span>
                                <span class="row">
                                    <span class="col-xs-5">
                                        <strong>
                                            Total Amount
                                        </strong>
                                    </span>
                                    <span class="col-xs-7 text-right">
                                        <strong>
                                            @{{totalModel}}
                                        </strong>
                                    </span>
                                </span>
                            </tr>
                        @else
                            <tr ng-if="deals.length>0">
                                <td colspan="3" class="text-right">
                                    <strong>Total</strong>
                                </td>
                                <td class="col-md-1 text-right">
                                    @{{getTotalPieces()}}
                                </td>
                                <td class="col-md-1 text-right">
                                    <strong>@{{totalqtyModel}}</strong>
                                </td>
                                <td colspan="1"></td>
                                <td class="col-md-1 text-right">
                                    <strong>@{{totalModel}}</strong>
                                </td>
                            </tr>
                        @endif
                        <tr ng-show="(deals | filter:search).deals == 0 || ! deals.length">
                            <td colspan="12" class="text-center">No Records Found!</td>
                        </tr>

                </table>
            </div>
        </div>

        <div class="panel-footer">
            <label ng-if="deals" class="pull-right totalnum" for="totalnum">Total of @{{deals.length}} entries</label>
        </div>
    </div>
</div>