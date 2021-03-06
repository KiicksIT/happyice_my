<!DOCTYPE html>
<html>
    <body>
      <p>
        Dear {{$name}} ({{$person->cust_id}}), <br><br>
        Thanks for the purchase on HappyIce Door to Door. The following is your order {{ $transaction ? $transaction->id : ''}} {{ $dtdtransaction ? $dtdtransaction->id : ''}}. <br><br>
        <div style="font-family: 'Open Sans'; font-size: 15px;">
            <table style="border: 1px solid black; border-collapse:collapse;">
                <tr style="background-color: #D8BFD8;">
                    <th style="border: 1px solid black; padding:5px 15px 5px 15px;">#</th>
                    <th style="border: 1px solid black; padding:5px 15px 5px 15px;">Item</th>
                    <th style="border: 1px solid black; padding:5px 15px 5px 15px;">Qty</th>
                    <th style="border: 1px solid black; padding:5px 15px 5px 15px;">Amount($)</th>
                </tr>
                <?php $counter = 0; ?>
                @foreach($qtyArr as $index => $qty)
                    @if($qty != null and $qty != '' and $qty != 0)
                    <?php $counter ++ ?>
                    <tr>
                        <td style="border: 1px solid black; padding:5px 15px 5px 15px;" align="center">{{$counter}}</td>
                        <td style="border: 1px solid black; padding:5px 15px 5px 15px;">{{$captionArr[$index]}}</td>
                        <td style="border: 1px solid black; padding:5px 15px 5px 15px;" align="center">{{$qty}}</td>
                        <td style="border: 1px solid black; padding:5px 15px 5px 15px;" align="right">{{number_format(($amountArr[$index]), 2, '.', ',')}}</td>
                    </tr>
                    @endif
                @endforeach
                @if($delivery != 0)
                <tr>
                    <th colspan="3" align="center" style="border: 1px solid black; padding:5px 15px 5px 15px;">
                        Delivery Fee
                    </th>
                    <td style="border: 1px solid black; padding:5px 15px 5px 15px; font-weight: bold;" align="right">
                        {{number_format($delivery, 2, '.', ',')}}
                    </td>
                </tr>
                @endif
                <tr>
                    <th colspan="3" align="center" style="border: 1px solid black; padding:5px 15px 5px 15px;">
                        Total
                    </th>
                    <td style="border: 1px solid black; padding:0 15px 0 15px; font-weight: bold;" align="right">
                        {{number_format($total, 2, '.', ',')}}
                    </td>
                </tr>
            </table>
        </div>
        <br>
        <p>
            <span style="font-weight:bold">Send to:</span><br>
            {{-- <span class="col-xs-12"> {{$person->block}}, #{{$person->floor}} - {{$person->unit}}</span> --}}
            <span class="col-xs-12">{{$block}}, #{{$floor}} - {{$unit}}, {{$street}};</span>
            <span class="col-xs-12">
                Singapore {{$postcode}}
            </span> <br>
            <span style="font-weight:bold">Contact Number:</span>&nbsp;{{ $person->contact }}<br>
            <span style="font-weight:bold">Preferred Timing:</span>&nbsp;{{ $timing }}<br>
            <span style="color:red;">**Cash payment upon delivery, We will contact you for final delivery timing</span>
            <br><br>
            @if($remark)
            <span style="font-weight:bold">Remark:</span>&nbsp;{{ $remark }} <br><br>
            @endif
            Thanks again and have a great day ahead.
        </p>
        <br>
        <p>
            Best Regards,<br>
            Happy Ice<br>
            <a href="www.happyice.com.sg/">www.happyice.com.sg</a>
        </p>
    </body>
</html>