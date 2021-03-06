
<div class="panel panel-primary">
    <div class="panel-heading">
        <div class="panel-title">
            <div class="pull-left display_panel_title">
                @unless($ftransaction->status == 'Cancelled')
                <h3 class="panel-title"><strong>Create List : {{$person->cust_id}} - {{$person->company}}</strong></h3>
                @else
                <h3 class="panel-title"><strong><del>Create List : {{$person->cust_id}} - {{$person->company}}</del></strong></h3>
                @endunless
            </div>
        </div>
    </div>

    <div class="panel-body">
        <div>
            <div class="table-responsive">
                <table class="table table-list-search table-hover table-bordered table-condensed">
                    <tr style="background-color: #DDFDF8;">
                        <th class="text-center">
                            Item
                        </th>
                        <th class="text-center">
                            Qty
                        </th>
                        <th class="text-center">
                            Retail Price ({{$ftransaction->person->profile->currency ? $ftransaction->person->profile->currency->symbol: '$'}})
                        </th>
                        <th class="text-center">
                            Quote Price ({{$ftransaction->person->profile->currency ? $ftransaction->person->profile->currency->symbol: '$'}})
                        </th>
                         <th class="text-center">
                            Amount ({{$ftransaction->person->profile->currency ? $ftransaction->person->profile->currency->symbol: '$'}})
                        </th>
                    </tr>

                    @unless(count($fprices)>0)
                    <td class="text-center" colspan="7">No Records Found</td>
                    @else
                    @foreach($fprices as $price)
                    <tr class="txtMult">
                        <td class="col-md-5 col-xs-4 hidden-xs">
                                <strong>{{$price->product_id}}</strong></span>
                                - {{$price->name}}
                                <small>{{$price->remark}}</small>
                        </td>
                        <td class="col-md-5 col-xs-4 hidden-lg hidden-md hidden-sm">
                                <strong>{{$price->product_id}}</strong><br>
                                {{$price->name}}<br>
                                <small>{{$price->remark}}</small>
                        </td>
                        <td class="col-md-1 col-xs-2">
                            @if($ftransaction->status == 'Pending' or $ftransaction->status == 'Confirmed')
                                <input type="text" name="qty[{{$price->item_id}}]" style="min-width: 70px;" class="qtyClass form-control"/>
                            @else
                                @can('transaction_view')
                                <input type="text" name="qty[{{$price->item_id}}]" style="min-width: 70px;" class="qtyClass form-control" readonly="readonly" />
                                @else
                                <input type="text" name="qty[{{$price->item_id}}]" style="min-width: 70px;" class="qtyClass form-control"/>
                                @endcan
                            @endif
                        </td>
                        <td class="col-md-2 col-xs-2">
                            <strong>
                            <input type="text" name="retail[{{$price->item_id}}]"
                             value="{{$price->retail_price}}"
                            class="text-right retailClass form-control" readonly="readonly"/>
                            </strong>
                        </td>
                        <td class="col-md-2 col-xs-2">
                            <strong>
                            @if($ftransaction->status == 'Cancelled')
                                <input type="text" name="quote[{{$price->item_id}}]"
                                value="{{$price->quote_price}}"
                                class="text-right form-control quoteClass" readonly="readonly"/>
                            @else
                                <input type="text" name="quote[{{$price->item_id}}]"
                                value="{{$price->quote_price}}"
                                class="text-right form-control quoteClass"/>
                            @endif
                            </strong>
                            {{-- @if($price->quote_price != '' or $price->quote_price != null or $price->quote_price != 0 or $ftransaction->status == 'Cancelled')                             --}}
                        </td>
                        <td class="col-md-2 col-xs-2">
                            <input type="text" name="amount[{{$price->item_id}}]"
                            class="text-right form-control amountClass" style="min-width: 100px;" readonly="readonly"/>
                        </td>
                    </tr>
                    @endforeach
                    @endunless
                    <tr>
                        <td class="col-md-1 col-xs-2 text-center"><strong>Total</strong></td>
                        <td colspan="3" class="col-md-3 text-right">
                            <td class="text-right" id="grandTotal" >
                                <strong>
                                    <input type="text" name="total_create" class="text-right form-control grandTotal" readonly="readonly" />
                                </strong>
                            </td>
                        </td>
                    </tr>
                </table>
            </div>
{{--             <div class="hidden-lg hidden-md hidden-sm">
                <table class="table table-list-search table-hover table-bordered table-condensed">
                    <tr style="background-color: #DDFDF8">
                        <th>Item</th>
                    </tr>
                    @foreach($prices as $index => $price)
                    <tr class="txtMult form-group">
                        <span class="row">
                            <strong>({{$price->product_id}}) {{$price->name}}</strong>
                            @if($price->remark)
                                <br>
                                {{$price->remark}}
                            @endif
                            <br>
                            Qty:
                            <input type="text" name="qty[{{$price->item_id}}]" class="qtyClass" style="width: 80px" />
                            <br>
                            Retail
                            <input type="text" retail[{{$price->item_id}}]" value="{{$price->retail_price}}" class="text-right retailClass form-control" style="width: 80px" />
                        </span>

                    </tr>
                    @endforeach
                </table>
            </div> --}}
        </div>
    </div>
</div>
