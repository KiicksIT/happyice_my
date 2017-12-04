@inject('people', 'App\Person')
@inject('fdeals', 'App\Fdeal')
@inject('deals', 'App\Deal')
@inject('items', 'App\Item')
@inject('ftransactions', 'App\Ftransaction')
<meta charset="utf-8">
<table>
    <tbody>
        @if($people::find($person_id))
            <tr>
                <th style="border: 1px solid #000000">
                    {{$people::find($person_id)->cust_id}} - {{$people::find($person_id)->company}}
                </th>
                <th style="border: 1px solid #000000">
                    {{$request->status}}
                </th>
                @if($request->delivery_from and $request->delivery_to)
                    <th style="border: 1px solid #000000">
                        {{$request->delivery_from}}
                    </th>
                    <th style="border: 1px solid #000000">
                        {{$request->delivery_to}}
                    </th>
                @endif
            </tr>
        @endif
        <tr></tr>
        <tr></tr>
        <tr>
            <th>Total Revenue ($)</th>
            <td data-format="0.00">
                {{$fdeals::where('item_id', 38)->whereIn('ftransaction_id', $ftransactionsId)->sum('amount')}}
            </td>
        </tr>
        <tr>
            <th>Total Ice Cream Cost ($)</th>
            <td data-format="0.00">
                {{
                    $deals::whereNotIn('item_id', [38, 61])->whereIn('transaction_id', $transactionsId)->sum(DB::raw('ABS(amount)'))
                }}
            </td>
        </tr>
        <tr>
            <th>Gross Earning ($)</th>
            <td data-format="0.00">{{$fdeals::whereIn('ftransaction_id', $ftransactionsId)->sum('amount') - $fdeals::whereIn('ftransaction_id', $ftransactionsId)->sum(DB::raw('qty * unit_cost'))}}</td>
        </tr>
         @if($fdeals::whereIn('ftransaction_id', $ftransactionsId)->sum('amount') != 0)
            <tr>
                <th>Gross Earning (%)</th>
                <td data-format="0.00">
                    {{(($fdeals::whereIn('ftransaction_id', $ftransactionsId)->sum('amount') - $fdeals::whereIn('ftransaction_id', $ftransactionsId)->sum(DB::raw('qty * unit_cost'))) / ($fdeals::whereIn('ftransaction_id', $ftransactionsId)->sum('amount'))) * 100}}
                </td>
            </tr>
        @endif
        @if(count($ftransactions::whereIn('id', $ftransactionsId)->get()) > 0)
            <tr>
                <th>First Inv Date</th>
                <td align="right">
                    {{$ftransactions::whereIn('id', $ftransactionsId)->oldest()->first()->delivery_date}}
                </td>
            </tr>
        @endif
        <tr></tr>
        <tr>
            <th>Invoice #</th>
            <th></th>
            <th></th>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                <th colspan="2" align="center">{{$ftransaction->ftransaction_id}}</th>
            @endforeach
        </tr>
        <tr>
            <th>Delivery Date</th>
            <th></th>
            <th></th>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                <th colspan="2" align="center">{{$ftransaction->delivery_date}}</th>
            @endforeach
        </tr>
        <tr>
            <th>Delivered By</th>
            <th></th>
            <th></th>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                <th colspan="2" align="center">{{$ftransaction->driver}}</th>
            @endforeach
        </tr>
        <tr>
            <th>Payment</th>
            <th></th>
            <th></th>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                <th colspan="2" align="center">{{$ftransaction->pay_method}}</th>
            @endforeach
        </tr>
        <tr>
            <th>Analog Required</th>
            <th></th>
            <th></th>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                <td colspan="2" align="center">{{$ftransaction->is_required_analog ? 'Yes' : 'No'}}</td>
            @endforeach
        </tr>
        <tr>
            <th align="center">Item</th>
            <th align="center">Total Qty</th>
            <th align="center">Total $</th>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                <th align="center">Qty</th>
                <th align="center">$</th>
            @endforeach
        </tr>





        @foreach($items::whereIn('id', $itemsId)->orderBy('product_id', 'asc')->get() as $item)
        <tr>
            <td>{{$item->product_id}} - {{$item->name}}</td>
            <td data-format="0.0000">{{$fdeals::whereIn('ftransaction_id', $ftransactionsId)->whereItemId($item->id)->sum('qty')}}</td>
            <td data-format="0.00">{{$fdeals::whereIn('ftransaction_id', $ftransactionsId)->whereItemId($item->id)->sum('amount')}}</td>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                <td data-format="0.0000">{{$fdeals::where('ftransaction_id', $ftransaction->id)->whereItemId($item->id)->sum('qty')}}</td>
                <td data-format="0.00">{{$fdeals::where('ftransaction_id', $ftransaction->id)->whereItemId($item->id)->sum('amount')}}</td>
            @endforeach
        </tr>
        @endforeach





        <tr>
            <th>Total</th>
            <th data-format="0.0000">{{$fdeals::whereIn('ftransaction_id', $ftransactionsId)->sum('qty')}}</th>
            <th data-format="0.00">{{$fdeals::whereIn('ftransaction_id', $ftransactionsId)->sum('amount')}}</th>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                <td data-format="0.0000">{{$fdeals::where('ftransaction_id', $ftransaction->id)->sum('qty')}}</td>
                <td data-format="0.00">{{$fdeals::where('ftransaction_id', $ftransaction->id)->sum('amount')}}</td>
            @endforeach
        </tr>
    </tbody>
</table>

@if($people::find($person_id) and auth()->user()->hasRole('admin') and count($ftransactions::whereIn('id', $ftransactionsId)->get()) > 0)
    @if($people::find($person_id)->is_vending)
    <table>
        <tbody>
            <tr></tr>
            <tr>
                <th>Price Per Piece ($)</th>
                <td data-format="0.00">{{$people::find($person_id)->vending_piece_price}}</td>
            </tr>
            <tr>
                <th>Monthly Rental ($)</th>
                <td data-format="0.00">{{$people::find($person_id)->vending_monthly_rental}}</td>
            </tr>
            <tr>
                <th>Profit Sharing</th>
                <td data-format="0.00">{{$people::find($person_id)->vending_profit_sharing}}</td>
            </tr>
            <tr>
                <th>Total Sales Qty</th>
                <td data-format="0.00">{{$ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->first()->analog_clock - $ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->oldest()->first()->analog_clock}}</td>
            </tr>
            <tr>
                <th>Average Sales Per Day</th>
                <td data-format="0.00">
                    @if(count($ftransactions::whereIn('id', $ftransactionsId)->get()) > 1)
                        {{
                            \Carbon\Carbon::parse($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->first()->delivery_date)->diffInDays(\Carbon\Carbon::parse($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->oldest()->first()->delivery_date))
                            ?
                            ($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->first()->analog_clock - $ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->oldest()->first()->analog_clock) / \Carbon\Carbon::parse($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->first()->delivery_date)->diffInDays(\Carbon\Carbon::parse($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->oldest()->first()->delivery_date))
                            :
                            ''
                        }}
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            <tr></tr>

            <tr>
                <th colspan="2">Sale Quatity (Based on Analog)</th>
                <th></th>
                @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $index => $ftransaction)
                    <td data-format="0" colspan="2" align="center">
                        @if($ftransaction->is_required_analog)
                        {{($index + 1) < count($ftransactions::whereIn('id', $ftransactionsId)->latest()->get()) ? $ftransaction->analog_clock - $ftransactions::whereIn('id', $ftransactionsId)->latest()->get()[$index + 1]->analog_clock : $ftransaction->digital_clock }}
                        @endif
                    </td>
                @endforeach
            </tr>
            <tr>
                <th colspan="2">Digital Clocker</th>
                <th></th>
                @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $index => $ftransaction)
                    <td data-format="0" colspan="2" align="center">
                        @if($ftransaction->is_required_analog)
                            {{$ftransaction->digital_clock}}
                        @endif
                    </td>

                @endforeach
            </tr>
            <tr>
                <th colspan="2">Analog Clocker</th>
                <th></th>
                @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $index => $ftransaction)
                    <td data-format="0" colspan="2" align="center">
                        @if($ftransaction->is_required_analog)
                            {{$ftransaction->analog_clock}}
                        @endif
                    </td>
                @endforeach
            </tr>
            <tr>
                <th colspan="2">Balance Coin</th>
                <th></th>
                @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $index => $ftransaction)
                    <td data-format="0.00" colspan="2" align="center">
                        @if($ftransaction->is_required_analog)
                            {{$ftransaction->balance_coin}}
                        @endif
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <table>
        <tr></tr>
        <tr>
            <th colspan="2">Payment Received</th>
            <th>Total</th>
        </tr>
        <tr>
            <td colspan="2">Expected Payment Received</td>
            <td data-format="0.00">
                {{ ($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->get()[0]->analog_clock - $ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->get()[count($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->get()) - 1]->analog_clock) * $people::find($person_id)->vending_piece_price }}
            </td>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $index => $ftransaction)
                <td colspan="2" data-format="0.00" align="center">
                    @if($ftransaction->is_required_analog)
                        {{$index + 1 < count($ftransactions::whereIn('id', $ftransactionsId)->latest()->get()) ? ($ftransaction->analog_clock - $ftransactions::whereIn('id', $ftransactionsId)->latest()->get()[$index + 1]->analog_clock) * $people::find($person_id)->vending_piece_price : 0.00}}
                    @endif
                </td>
            @endforeach
        </tr>
        <tr>
            <td colspan="2">Balance Coin</td>
            <td></td>
            @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                <td colspan="2" data-format="0.00" align="center">
                    @if($ftransaction->is_required_analog)
                        {{$ftransaction->balance_coin}}
                    @endif
                </td>
            @endforeach
        </tr>
        </table>

        @if($fdeals::whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('051')->first()->id)->first() or $fdeals::whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('051a')->first()->id)->first() or $fdeals::whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('052')->first()->id)->first())
            <tr>
                <td colspan="2">{{$items::whereProductId('051')->first()->product_id}} - {{$items::whereProductId('051')->first()->name}}</td>
                <td></td>
                @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                    <td colspan="2" data-format="0.00" align="center">
                        @if($ftransaction->is_required_analog)
                            {{$fdeals::where('ftransaction_id', $ftransaction->id)->whereItemId($items::whereProductId('051')->first()->id)->first() ? $fdeals::where('ftransaction_id', $ftransaction->id)->whereItemId($items::whereProductId('051')->first()->id)->first()->amount : 0.00}}
                        @endif
                    </td>
                @endforeach
            </tr>
            <tr>
                <td colspan="2">Actual Subtotal Received</td>
                <td data-format="0.00">
                    {{$ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->get()[0]->balance_coin + ($fdeals::isTransactionAnalog()->whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('051')->first()->id)->sum('amount')) + ($fdeals::isTransactionAnalog()->whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('052')->first()->id)->sum('amount'))}}
                </td>
            </tr>
            <tr>
                <th colspan="2">Difference(Actual - Expected)</th>
                <th data-format="0.00">
                    {{ $ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->get()[0]->balance_coin + ($fdeals::isTransactionAnalog()->whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('051')->first()->id)->sum('amount')) - ($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->get()[0]->analog_clock - $ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->get()[count($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->get())-1]->analog_clock) * $people::find($person_id)->vending_piece_price}}
                </th>
            </tr>
            <tr>
                <td colspan="2">
                    {{$items::whereProductId('051a')->first()->product_id}} - {{$items::whereProductId('051a')->first()->name}}
                </td>
                <td  data-format="0.00">
                    {{$fdeals::isTransactionAnalog()->whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('051a')->first()->id)->sum('amount')}}
                </td>
                @foreach($ftransactions::whereIn('id', $ftransactionsId)->latest()->get() as $ftransaction)
                    <td data-format="0.00" colspan="2" align="center">
                        @if($ftransaction->is_required_analog)
                            {{ $fdeals::where('ftransaction_id', $ftransaction->id)->whereItemId($items::whereProductId('051a')->first()->id)->first() ? $fdeals::where('ftransaction_id', $ftransaction->id)->whereItemId($items::whereProductId('051a')->first()->id)->first()->amount : 0.00 }}
                        @endif
                    </td>
                @endforeach
            </tr>
            <tr>
                <th colspan="2">Stock Value in VM</th>
                <th data-format="0.00">
                    {{ ($fdeals::isTransactionAnalog()->whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('051a')->first()->id)->sum('amount')) + ($ftransactions::isAnalog()->whereIn('id', $ftransactionsId)->latest()->get()[0]->balance_coin + ($fdeals::isTransactionAnalog()->whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('051')->first()->id)->sum('amount')) + ($fdeals::isTransactionAnalog()->whereIn('ftransaction_id', $ftransactionsId)->whereItemId($items::whereProductId('052')->first()->id)->sum('amount')))}}
                </th>
            </tr>
        @endif
    </table>
    @endif
@endif