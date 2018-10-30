@inject('transaction', 'App\Transaction')

<meta charset="utf-8">
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Address Postcode Singapore</th>
        <th>Postcode Singapore</th>
        <th>Cust ID + ID Name</th>
        <th>Inv#</th>
        <th>Ops Note</th>
        @foreach($dates as $date)
        <th>
            {{\Carbon\Carbon::parse($date)->format('y-m-d')}} ({{\Carbon\Carbon::parse($date)->format('D')}})
        </th>
        @endforeach
    </tr>
    </thead>

    <tbody>
    @foreach($people as $indexpeople => $person)
        <tr>
            <td>{{$indexpeople + 1}}</td>
            <td>{{$person->del_address}} {{$person->del_postcode}} Singapore</td>
            <td>{{$person->del_postcode}} Singapore</td>
            <td>{{$person->cust_id}} {{$person->company}}</td>
            @php
                $transactionsStr = '';
                $transArr = [];

                $transactions = $transaction::where('person_id', $person->person_id)
                                ->whereDate('delivery_date', '=', request('chosen_date'))
                                ->get();

                if(count($transactions) > 0) {
                    foreach($transactions as $trans) {
                        array_push($transArr, $trans->id);
                    }
                    $transactionsStr = implode(",", $transArr);
                }
            @endphp
            <td>{{$transactionsStr}}</td>
            <td>{{$person->operation_note}}</td>
            @foreach($alldata[$indexpeople] as $data)
            @php
                $color = $data['color'];

                switch($color) {
                    case 'Yellow':
                        $color = '#ffff00';
                        break;
                    case 'Red':
                        $color = '#ff0000';
                        break;
                    default:
                        $color = 'ffffff';
                }

                if($data['bool_transaction']) {
                    $color = '#77d867';
                    $data['qty'] = 0;
                }

            @endphp
                <td style="background-color: {{$color}}; border: thin solid #000000;">
                    {{$data['qty']}}
                </td>
            @endforeach

        </tr>
    @endforeach
    </tbody>
</table>