<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;

use Venturecraft\Revisionable\Revision;
use Response;
use App;
use DB;
use Auth;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Transaction;
use App\Unitcost;
use App\Item;
use App\Person;
use App\Price;
use App\Deal;
use Carbon\Carbon;
use App\Profile;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Laracasts\Flash\Flash;
use App\EmailAlert;
use App\DtdPrice;
use App\DtdTransaction;
use App\DtdDeal;
use App\GeneralSetting;
use App\Invattachment;
use App\TransSubscription;
use App\User;
// use App\Ftransaction;

// traits
use App\HasProfileAccess;
use App\CreateRemoveDealLogic;
use App\GetIncrement;
use App\HasFranchiseeAccess;

class TransactionController extends Controller
{
    use HasProfileAccess, CreateRemoveDealLogic, HasFranchiseeAccess;
    //qty status condition
    /*
        qty_status = 1 (Stock Order/ Confirmed)
        qty_status = 2 (Actual Stock Deducted/ Delivered)
        qty_status = 3 (Stock Removed/ Deleted || Cancelled)
    */

    //auth-only login can see
    public function __construct()
    {
        $this->middleware('auth');
    }

    // get transactions api data based on delivery date
    public function getData()
    {
        // showing total amount init
        $total_amount = 0;
        // initiate the page num when null given
        $pageNum = request('pageNum') ? request('pageNum') : 100;

        $transactions = $this->getTransactionsData();

        $total_amount = $this->calDBTransactionTotal($transactions);
        $delivery_total = $this->calDBDeliveryTotal($transactions);

        if(request('sortName')){
            $transactions = $transactions->orderBy(request('sortName'), request('sortBy') ? 'asc' : 'desc');
        }

        if($pageNum == 'All'){
            $transactions = $transactions->latest('transactions.created_at')->get();
            // dd($transactions);
        }else{
            $transactions = $transactions->latest('transactions.created_at')->paginate($pageNum);
        }

        $data = [
            'total_amount' => $total_amount + $delivery_total,
            'transactions' => $transactions,
        ];
        return $data;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('transaction.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('transaction.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'person_id' => 'required',
        ],[
            'person_id.required' => 'Please choose an option',
        ]);

        $request->merge(array('updated_by' => Auth::user()->name));
        $request->merge(['delivery_date' => Carbon::today()]);
        $request->merge(['order_date' => Carbon::today()]);
        $input = $request->all();

        // filter delivery date if the invoice lock date is before request delivery date
        if($freeze_date = GeneralSetting::firstOrFail()->INVOICE_FREEZE_DATE) {
            if($freeze_date->min(Carbon::parse($request->delivery_date)) != $freeze_date) {
                Flash::error('The delivery date is locked, alter the invoice lock date to after '.Carbon::parse($freeze_date)->format('Y-m-d'));
                return back();
            }
        }

        $transaction = Transaction::create($input);
        // create dtd transaction once detect person code is D
        if($transaction->person->cust_id[0] === 'D'){
            $request->merge(array('type' => 'Deal'));
            $dtdtransaction = DtdTransaction::create($input);
            $dtdtransaction->transaction_id = $transaction->id;
            $dtdtransaction->save();
            $transaction->dtdtransaction_id = $dtdtransaction->id;
            $transaction->save();
        }

        // check profile is vending then analog required
        if($transaction->person->is_vending) {
            $transaction->is_required_analog = 1;
            $transaction->save();
        }

        return Redirect::action('TransactionController@edit', $transaction->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $transaction = Transaction::findOrFail($id);

        return $transaction;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $transaction = Transaction::findOrFail($id);

        $invattachments = $transaction->invattachments;

        $person = Person::findOrFail($transaction->person_id);

        // retrieve manually to order product id asc
        if($transaction->person->cust_id[0] === 'D'){
            $prices = DB::table('dtdprices')
                        ->leftJoin('items', 'dtdprices.item_id', '=', 'items.id')
                        ->select(
                            'dtdprices.*', 'items.product_id', 'items.name', 'items.remark', 'items.id as item_id', 'items.base_unit as pieces')
                        ->where('items.is_active', 1)
                        ->orderBy('product_id')
                        ->get();
        }else if($transaction->person->cust_id[0] === 'H'){
            $prices = DB::table('d2d_online_sales')
                            ->leftJoin('people', 'd2d_online_sales.person_id', '=', 'people.id')
                            ->leftJoin('items', 'd2d_online_sales.item_id', '=', 'items.id')
                            ->leftJoin('prices', function($join) {
                                $join->on('prices.person_id', '=', 'people.id')
                                        ->on('prices.item_id', '=', 'items.id');
                            })
                            ->select(
                                'prices.*', 'items.product_id', 'items.name', 'items.remark', 'items.id as item_id', 'items.base_unit as pieces'
                                )
                            ->where('items.is_active', 1)
                            ->orderBy('product_id')
                            ->get();
        }else{
            $prices = DB::table('prices')
                        ->leftJoin('items', 'prices.item_id', '=', 'items.id')
                        ->select(
                            'prices.*', 'items.product_id', 'items.name', 'items.remark', 'items.id as item_id', 'items.base_unit as pieces'
                        )
                        ->where('prices.person_id', '=', $transaction->person_id)
                        ->where('items.is_active', 1)
                        ->orderBy('product_id')
                        ->get();
/*            $prices = Price::with('item')
                            ->where('person_id', $transaction->person_id)
                            ->orderBy('product_id')
                            ->get();*/
        }

        return view('transaction.edit', compact('transaction', 'person', 'prices', 'invattachments'));
    }

    // return transaction related components, deals (int transaction_id)
    public function editApi($transaction_id)
    {
        $total = 0;
        $subtotal = 0;
        $tax = 0;

        $transaction = Transaction::with('person')->findOrFail($transaction_id);

        $deals = DB::table('deals')
                    ->leftJoin('transactions', 'transactions.id', '=', 'deals.transaction_id')
                    ->leftJoin('people', 'people.id', '=', 'transactions.person_id')
                    ->leftJoin('profiles', 'profiles.id', '=', 'people.profile_id')
                    ->leftJoin('items', 'items.id', '=', 'deals.item_id')
                    ->select(
                                'deals.transaction_id', 'deals.dividend', 'deals.divisor', 'deals.qty', 'deals.unit_price', 'deals.amount', 'deals.id AS deal_id',
                                'items.id AS item_id', 'items.product_id', 'items.name AS item_name', 'items.remark AS item_remark', 'items.is_inventory', 'items.unit',
                                'people.cust_id', 'people.company', 'people.name', 'people.id as person_id',
                                'transactions.del_postcode', 'transactions.status', 'transactions.delivery_date', 'transactions.driver',
                                DB::raw('ROUND((CASE WHEN transactions.gst=1 THEN (
                                                    CASE
                                                    WHEN transactions.is_gst_inclusive=0
                                                    THEN total*((100+transactions.gst_rate)/100)
                                                    ELSE transactions.total
                                                    END)
                                                ELSE transactions.total END) + (CASE WHEN transactions.delivery_fee>0 THEN transactions.delivery_fee ELSE 0 END), 2) AS total'),
                                'transactions.total_qty', 'transactions.pay_status','transactions.updated_by', 'transactions.updated_at', 'transactions.delivery_fee', 'transactions.id',
                                'profiles.id as profile_id', 'transactions.gst', 'transactions.is_gst_inclusive', 'transactions.gst_rate',
                                DB::raw('
                                    ROUND(CASE WHEN deals.divisor > 1
                                    THEN (items.base_unit * deals.dividend/deals.divisor)
                                    ELSE (items.base_unit * deals.qty)
                                    END, 0) AS pieces
                                ')
                            )
                    ->where('deals.transaction_id', $transaction->id)
                    ->get();

        $subtotal = 0;
        $tax = 0;
        $total = number_format($transaction->total, 2);

        if($transaction->gst) {
            if($transaction->is_gst_inclusive) {
                $total = number_format($transaction->total, 2);
                $tax = number_format($transaction->total - $transaction->total/((100 + $transaction->gst_rate)/ 100), 2);
                $subtotal = number_format($transaction->total - $tax, 2);
            }else {
                $subtotal = number_format($transaction->total, 2);
                $tax = number_format($transaction->total * ($transaction->gst_rate)/100, 2);
                $total = number_format(((float)$transaction->total + (float) $tax), 2);
            }
        }

        $delivery_fee = $transaction->delivery_fee;

        if($delivery_fee) {
            $total += number_format($delivery_fee, 2);
        }

        return $data = [
            'transaction' => $transaction,
            'deals' => $deals,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'delivery_fee' => $delivery_fee
        ];

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TransactionRequest $request, $id)
    {
        // dynamic form arrays
        $quantities = $request->qty;
        $amounts = $request->amount;
        $quotes = $request->quote;
        $cartons = $request->ctn;
        $pieces = $request->pcs;
        $transaction = Transaction::findOrFail($id);
        // find out deals created
        $deals = Deal::where('transaction_id', $transaction->id)->get();

        if($request->input('save')){
            $request->merge(array('status' => 'Pending'));
        }else if($request->input('del_paid')){
            $this->vendingMachineValidation($request, $id);
            $request->merge(array('status' => 'Delivered'));
            $request->merge(array('pay_status' => 'Paid'));
            if(! $request->paid_by){
                $request->merge(array('paid_by' => Auth::user()->name));
            }
            $request->merge(array('paid_at' => Carbon::now()->format('Y-m-d h:i A')));

            if(! $request->driver){
                $request->merge(array('driver'=>Auth::user()->name));
            }
            if(count($deals) == 0){
                Flash::error('Please entry the list');
                return Redirect::action('TransactionController@edit', $transaction->id);
            }
        }elseif($request->input('del_owe')){
            $this->vendingMachineValidation($request, $id);
            $request->merge(array('status' => 'Delivered'));
            $request->merge(array('pay_status' => 'Owe'));
            if(! $request->driver){
                $request->merge(array('driver'=>Auth::user()->name));
            }
            $request->merge(array('paid_by'=>null));

            if(count($deals) == 0){
                Flash::error('Please entry the list');
                return Redirect::action('TransactionController@edit', $transaction->id);
            }
        }elseif($request->input('paid')){
            $request->merge(array('pay_status' => 'Paid'));

            if(! $request->paid_by){
                $request->merge(array('paid_by' => Auth::user()->name));
            }
            $request->merge(array('paid_at' => Carbon::now()->format('Y-m-d h:i A')));

            if(count($deals) == 0){
                Flash::error('Please entry the list');
                return Redirect::action('TransactionController@edit', $transaction->id);
            }

        }elseif($request->input('confirm')){
            // confirmation must with the entries start
            if(array_filter($amounts) != null) {
                $request->merge(array('status' => 'Confirmed'));
            }else{
                Flash::error('The list cannot be empty upon confirmation');
                return Redirect::action('TransactionController@edit', $transaction->id);
            }
            // confirmation must with the entries end

        }elseif($request->input('unpaid')){
            $request->merge(array('pay_status' => 'Owe'));
            $request->merge(array('paid_by' => null));
            $request->merge(array('paid_at' => null));
        }elseif($request->input('update')){
            if($transaction->status === 'Confirmed'){
                $request->merge(array('driver' => null));
                $request->merge(array('paid_by' => null));
                $request->merge(array('paid_at' => null));
            }else if(($transaction->status === 'Delivered' or $transaction->status === 'Verified Owe') and $transaction->pay_status === 'Owe'){
                $this->vendingMachineValidation($request, $id);
                $request->merge(array('paid_by' => null));
                $request->merge(array('paid_at' => null));
            }else {
                $this->vendingMachineValidation($request, $id);
            }
        }

        // filter delivery date if the invoice lock date is before request delivery date
        if($freeze_date = GeneralSetting::firstOrFail()->INVOICE_FREEZE_DATE) {
            if($freeze_date->min(Carbon::parse($request->delivery_date)) != $freeze_date) {
                Flash::error('The delivery date is locked, please choose the date after '.Carbon::parse($freeze_date)->format('Y-m-d'));
                return back();
            }
        }

        $analog_clock = request('analog_clock');

        if($transaction->person->is_vending and $transaction->is_required_analog) {
            $vendcash_check = Transaction::whereHas('deals', function($q) {
                                    $q->whereHas('item', function($q) {
                                        $q->where('product_id', '051');
                                    });
                                })
                                ->where('id', $transaction->id)
                                ->get();

            if(count($vendcash_check) > 0) {
                if($analog_clock > 0) {
                    $delivery_date = $transaction->delivery_date ? $transaction->delivery_date : Carbon::today()->toDateString();

                    $prev_inv = Transaction::where('person_id', $transaction->person_id)->where('is_required_analog', 1)->whereNotIn('id', [$transaction->id])->whereDate('delivery_date', '<', $delivery_date)->latest()->first();

                    if($prev_inv) {
                        $prev_analog = (int)Transaction::where('person_id', $transaction->person_id)->where('is_required_analog', 1)->whereNotIn('id', [$transaction->id])->whereDate('delivery_date', '<', $delivery_date)->latest()->first()->analog_clock;


                        $current_analog = (int)request('analog_clock');

                        if($current_analog < $prev_analog) {
                            Flash::error('Analog Clock value must be equals or greater than previous invoice ('.$prev_analog.')');
                            return redirect()->action('TransactionController@edit', $transaction->id);
                        }
                    }else {
                        Flash::error('Vend cash shouldnt be received when the previous invoice is not detected');
                        return redirect()->action('TransactionController@edit', $transaction->id);
                    }
                }else {
                    if($analog_clock == 0 or $analog_clock == null) {
                        Flash::error('Analog Clock must be filled and cannot be 0');
                        return redirect()->action('TransactionController@edit', $transaction->id);
                    }
                }
            }
        }

        // analog required validate by roles
        if(auth()->user()->hasRole('admin') or auth()->user()->hasRole('account')) {
            $request->merge(array('is_required_analog' => $request->has('is_required_analog') ? 1 : 0));
        }else {
            $request->merge(array('is_required_analog' => $transaction->is_required_analog));
        }

        $request->merge(array('person_id' => $request->input('person_copyid')));
        $request->merge(array('updated_by' => Auth::user()->name));
        $request->merge(array('gst' => $transaction->person->profile->gst));
        $request->merge(array('is_gst_inclusive' => $transaction->person->is_gst_inclusive));
        $request->merge(array('gst_rate' => $transaction->person->gst_rate));
        $transaction->update($request->all());

        //Qty insert to on order upon confirmed(1) transaction status
        if($transaction->status === 'Confirmed'){
            $this->syncDeal($transaction, $cartons, $pieces, $amounts, $quotes, 1);
        }else if($transaction->status === 'Delivered' or $transaction->status === 'Verified Owe' or $transaction->status === 'Verified Paid'){
            $this->syncDeal($transaction, $cartons, $pieces, $amounts, $quotes, 2);
        }

        if($transaction->person->cust_id[0] === 'D'){
            $this->syncOrder($transaction->id);
            // sync transaction status once not belongs to those status
            if($transaction->status !== 'Pending' or $transaction->status !== 'Verify Owe' or $transaction->status !== 'Verify Paid'){
                $dtdtransaction = DtdTransaction::findOrFail($transaction->dtdtransaction_id);
                // sync to be replace <-> original
                $this->transactionXChange($dtdtransaction, $transaction);
            }
        }

        // waive off delivery fees if update with more than 4 quantities (dividend)
        if($transaction->person->cust_id[0] === 'H') {
            $total_qty = Deal::whereTransactionId($transaction->id)->sum('dividend');
            if($total_qty >= 4) {
                $transaction->delivery_fee = 0;
                $transaction->save();
            }
        }

        // record the transactions to ftransaction when franchisee id is detected
/*        if($transaction->person->franchisee_id) {
            $this->syncFtransactionsAndTransactions($transaction);
        }*/

        return Redirect::action('TransactionController@edit', $transaction->id);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        if($request->input('form_delete')){

            $transaction = Transaction::findOrFail($id);
            $transaction->cancel_trace = $transaction->status;
            $transaction->status = 'Cancelled';
            $transaction->updated_by = auth()->user()->name;
            $transaction->save();

            if($transaction->dtdtransaction_id){
                $dtdtransaction = DtdTransaction::findOrFail($transaction->dtdtransaction_id);
                $dtdtransaction->cancel_trace = $dtdtransaction->status;
                $dtdtransaction->status = 'Cancelled';
                $dtdtransaction->updated_by = auth()->user()->name;
                $dtdtransaction->save();
            }

            $this->dealDeleteMultiple($transaction->id);

            return Redirect::action('TransactionController@edit', $transaction->id);

        }else if($request->input('form_wipe')){
            $transaction = Transaction::findOrFail($id);
            if($transaction->dtdtransaction_id){
                $dtdtransaction = DtdTransaction::find($transaction->dtdtransaction_id);
                if($dtdtransaction){
                    $dtdtransaction->delete();
                }
            }
            $transaction->delete();

            return Redirect::action('PersonController@edit', $transaction->person->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     *transaction
     * @param  int  $id
     * @return json
     */
    public function destroyAjax($id)
    {
        $transaction = Transaction::findOrFail($id);

        $transaction->delete();

        return $transaction->id . 'has been successfully deleted';
    }

    public function getCust($person_id)
    {
        $person =  Person::findOrFail($person_id);

        return $person;
    }

    public function getItem($person_id)
    {
/*        return Item::whereHas('prices', function($query) use ($person_id){

            $query->where('person_id', $person_id)->whereNotNull('quote_price');

        })->get();*/
        //select(DB::raw("CONCAT(product_id,' - ',name,' - ',remark) AS full, id"))->lists('full', 'id');
        $item =  Item::with(['prices' => function($query) use ($person_id){

            $query->where('person_id', $person_id);

        }])->get();

        return $item;


    }

    // return the price lists set in the person
    public function getPrice($person_id, $item_id)
    {

        return Price::with('item')->where('person_id', $person_id)->where('item_id', $item_id)->first();

    }

    public function storeCust($trans_id, Request $request)
    {
        $input = $request->all();

        $transaction = Transaction::findOrFail($trans_id);

        //take the first value of the array
        $transaction->person_id = reset($input);

        $transaction->save();

        return "Sucess updating transaction #" . $transaction->id;

    }

    public function storeCustcode($trans_id, Request $request)
    {

        $transaction = Transaction::findOrFail($trans_id);

        //take the first value of the array
        $transaction->person_code = $request->input('person_code');

        $transaction->save();

        return "Sucess updating transaction #" . $transaction->id;

    }

    public function storeTotal($trans_id, Request $request)
    {
        $input = $request->all();

        $transaction = Transaction::findOrFail($trans_id);

        //take the first value of the array
        $transaction->total = reset($input);

        $transaction->save();

        return "Sucess updating transaction #" . $transaction->id;

    }

    public function storeTotalQty($trans_id, Request $request)
    {
        $input = $request->all();

        $transaction = Transaction::findOrFail($trans_id);

        //take the first value of the array
        $transaction->total_qty = reset($input);

        $transaction->save();

        return "Sucess updating transaction #" . $transaction->id;

    }

    // generate pdf invoice for transaction
    public function generateInvoice($id)
    {
        $transaction = Transaction::findOrFail($id);
        $person = Person::findOrFail($transaction->person_id);
        $deals = Deal::whereTransactionId($transaction->id)->get();
        $totalprice = DB::table('deals')->whereTransactionId($transaction->id)->sum('amount');
        $totalqty = DB::table('deals')->whereTransactionId($transaction->id)->sum('qty');
        // $profile = Profile::firstOrFail();
        $data = [
            'inv_id' => $transaction->id,
            'transaction'   =>  $transaction,
            'person'        =>  $person,
            'deals'         =>  $deals,
            'totalprice'    =>  $totalprice,
            'totalqty'      =>  $totalqty,
            // 'profile'       =>  $profile,
        ];

        $name = 'Inv('.$transaction->id.')_'.$person->cust_id.'_'.$person->company.'.pdf';
        $pdf = PDF::loadView('transaction.invoice', $data);
        $pdf->setPaper('a4');
        $pdf->setOption('dpi', 85);
        return $pdf->download($name);
    }

    public function generateLogs($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transHistory = $transaction->revisionHistory;
        return view('transaction.log', compact('transaction', 'transHistory'));
    }

    // status changing to verified owe/ paid
    public function changeStatus($id)
    {
        $transaction = Transaction::findOrFail($id);
        $status = $transaction->status;
        $pay_status = $transaction->pay_status;
        if($status == 'Delivered' and $pay_status == 'Owe'){
            $transaction->status = 'Verified Owe';
            $transaction->updated_by = Auth::user()->name;
            $transaction->save();
        }else if(($status == 'Verified Owe' or $status == 'Delivered') and $pay_status == 'Paid'){
            $transaction->status = 'Verified Paid';
            // $transaction->pay_method = 'cash';
            $transaction->updated_by = Auth::user()->name;
            $transaction->save();
        }
        // using redirect back since applied in different views
        return redirect()->back();
    }

    public function showPersonTransac($person_id)
    {
        $transactions = DB::table('transactions')
                ->leftJoin('people', 'transactions.person_id', '=', 'people.id')
                ->leftJoin('profiles', 'people.profile_id', '=', 'profiles.id')
                ->leftJoin('custcategories', 'people.custcategory_id', '=', 'custcategories.id')
                ->select(
                            'people.cust_id', 'people.company',
                            'people.name', 'people.id as person_id', 'transactions.del_postcode',
                            'transactions.status', 'transactions.delivery_date', 'transactions.driver',
                            'transactions.total_qty', 'transactions.pay_status',
                            'transactions.updated_by', 'transactions.updated_at', 'transactions.delivery_fee', 'transactions.id',
                            DB::raw('ROUND((CASE WHEN transactions.gst=1 THEN (
                                                CASE
                                                WHEN transactions.is_gst_inclusive=0
                                                THEN total*((100+transactions.gst_rate)/100)
                                                ELSE transactions.total
                                                END)
                                            ELSE transactions.total END) + (CASE WHEN transactions.delivery_fee>0 THEN transactions.delivery_fee ELSE 0 END), 2) AS total'),
                            'profiles.id as profile_id', 'transactions.gst', 'transactions.is_gst_inclusive', 'transactions.gst_rate',
                             'custcategories.name as custcategory'
                        )
                ->where('people.id', $person_id)
                ->orderBy('transactions.created_at', 'desc')
                ->take(5)
                ->get();
        return $transactions;
        // Transaction::with(['person', 'person.profile'])->wherePersonId($person_id)->latest()->take(5)->get();
    }

    // undo the cancelled transaction
    public function reverse($id)
    {
        $transaction = Transaction::findOrFail($id);
        $deals = Deal::where('transaction_id', $transaction->id)->where('qty_status', '3')->get();
        if($transaction->cancel_trace){
            $this->dealUndoDelete($transaction->id);
            $transaction->status = $transaction->cancel_trace;
            $transaction->cancel_trace = '';
            $transaction->updated_by = Auth::user()->name;
            if($transaction->dtdtransaction_id){
                $dtdtransaction = DtdTransaction::findOrFail($transaction->dtdtransaction_id);
                $dtdtransaction->status = 'Confirmed';
                $dtdtransaction->save();
            }
        }

        $transaction->save();
        return Redirect::action('TransactionController@edit', $transaction->id);
    }

    // storing payment method and note in rpt
    public function rptDetail(Request $request, $id)
    {
        $paymethod = $request->input('paymethod');
        $note = $request->input('note');
        $transaction = Transaction::findOrFail($id);
        $transaction->pay_method = $paymethod;
        $transaction->note = $note;
        $transaction->save();
        return "Sucess updating transaction #" . $transaction->id;
    }

    // send invoice to D/ H email upon button clicked
    public function sendEmailInv($id)
    {
        $email_draft = GeneralSetting::firstOrFail()->DTDCUST_EMAIL_CONTENT;
        $transaction = Transaction::findOrFail($id);
        $self = Auth::user()->name;
        $deals = Deal::whereTransactionId($transaction->id)->get();
        $totalprice = DB::table('deals')->whereTransactionId($transaction->id)->sum('amount');
        $totalqty = DB::table('deals')->whereTransactionId($transaction->id)->sum('qty');
        $person = Person::findOrFail($transaction->person_id);

        $email = $person->email;

        if(! $email){
            Flash::error('Please set the email before sending');
            return Redirect::action('TransactionController@edit', $id);
        }else {
            if(strpos($email, ';') !== FALSE) {
                $email = explode(';', $email);
            }
        }

        $now = Carbon::now()->format('dmyhis');
        // $profile = Profile::firstOrFail();
        $data = [
            'inv_id' => $transaction->id,
            'transaction'   =>  $transaction,
            'person'        =>  $person,
            'deals'         =>  $deals,
            'totalprice'    =>  $totalprice,
            'totalqty'      =>  $totalqty,
        ];
        $name = 'Inv('.$transaction->id.')_'.$person->cust_id.'_'.$person->company.'('.$now.').pdf';
        $pdf = PDF::loadView('transaction.invoice', $data);
        $pdf->setPaper('a4');
        $sent = $pdf->save(storage_path('/invoice/'.$name));
        $store_path = storage_path('/invoice/'.$name);
        $sender = 'system@happyice.com.sg';
        $datamail = [
            'person' => $person,
            'transaction' => $transaction,
            'email_draft' => $email_draft,
            'self' => $self,
            'url' => 'http://www.happyice.com.sg',
        ];

        Mail::send('email.send_invoice', $datamail, function ($message) use ($email, $sender, $store_path, $transaction)
        {
            $message->from($sender);
            $message->subject('[Invoice - '.$transaction->id.'] Happy Ice - Thanks for Your Support');
            $message->setTo($email);
            $message->attach($store_path);
        });

        if($sent){
            Flash::success('Successfully Sent');
        }else{
            Flash::error('Please Try Again');
        }

        return Redirect::action('TransactionController@edit', $id);
    }

    // return invoice date freeze page()
    public function getFreezeInvoiceDate()
    {
        return view('transaction.freezedate');
    }

    // return invoice date freeze page api()
    public function getFreezeInvoiceDateApi()
    {
        $general_setting = GeneralSetting::firstOrFail();

        return $general_setting;
    }

    // store invoice freeze date()
    public function freezeInvoiceDate()
    {
        $general_setting = GeneralSetting::firstOrFail();

        $general_setting->INVOICE_FREEZE_DATE = request('freeze_date');

        $general_setting->save();

        DB::table('transactions')
            ->whereDate('delivery_date', '<=', $general_setting->INVOICE_FREEZE_DATE)
            ->update(['is_freeze' => 1]);

        DB::table('dtdtransactions')
            ->whereDate('delivery_date', '<=', $general_setting->INVOICE_FREEZE_DATE)
            ->update(['is_freeze' => 1]);

        return Redirect::action('TransactionController@freezeInvoiceDate');
    }

    // attach file on the invoice(int transaction_id)
    public function addInvoiceAttachment($transaction_id)
    {
        $transaction = Transaction::findOrFail($transaction_id);
        $file = request()->file('file');
        $name = (Carbon::now()->format('dmYHi')).$file->getClientOriginalName();
        $file->move('inv_attachments/'.$transaction->person->cust_id.'/', $name);
        $transaction->invattachments()->create(['path' => "/inv_attachments/".$transaction->person->cust_id."/{$name}"]);
    }

    // remove attachment from the transaction invoice(int attachment_id)
    public function removeAttachment($attachment_id)
    {
        $invattachment = Invattachment::findOrFail($attachment_id);
        $filename = $invattachment->path;
        $path = public_path();
        if (!File::delete($path.$filename))
        {
            $invattachment->delete();
            return redirect()->action('TransactionController@edit', $invattachment->transaction->id);
        }else {
            $invattachment->delete();
            return redirect()->action('TransactionController@edit', $invattachment->transaction->id);
        }
    }

    // return email subscription page index()
    public function subscibeTransactionEmail()
    {
        return view('transaction.trans_subscription');
    }

    // return subscribed transaction email users()
    public function subscibeTransactionEmailApi()
    {
        $users = User::with(['roles', 'transSubscription'])->has('transSubscription')->get();

        return $users;
    }

    // return non subscribed transaction email users()
    public function nonSubscibeTransactionEmailApi()
    {
        $users = User::with(['roles'])->whereDoesntHave('transSubscription')->get();

        return $users;
    }

    // add user subscribed list ($user_id)
    public function addSubscriberTransactionEmailApi()
    {
        $trans_subs = new TransSubscription;
        $user = User::findOrFail(request('user_id'));
        $trans_subs->user_id = $user->id;
        $trans_subs->save();
        // $user->transSubscription()->save($trans_subs);
    }

    // remove user subscribed list(int $user_id)
    public function removeSubscriberTransactionEmailApi($user_id)
    {
        $user = User::findOrFail($user_id);

        $user->transSubscription()->delete();
    }

    // export account consolidate report from transactions index()
    public function exportAccConsolidatePdf()
    {

        $now = Carbon::now()->format('d-m-Y H:i');

        if(request('exportpdf') == 'do') {
            $title = 'Consolidated DO';
            $name = 'Consolidated_DO(' . $now . ').pdf';
        }else {
            $title = 'Consolidated Tax Invoice';
            $name = 'Consolidated_Tax_Invoice(' . $now . ').pdf';
        }

        $transactions = $this->getTransactionsData();
        if(!request('delivery_from') and !request('delivery_to')){
            $delivery_from = Carbon::today()->toDateString();
            $delivery_to = Carbon::today()->toDateString();
            $transactions = $transactions->where('transactions.delivery_date', '=', Carbon::today()->toDateString());
        }else {
            $delivery_from = request('delivery_from');
            $delivery_to = request('delivery_to');
        }
        $totalprice = $this->calDBTransactionTotal($transactions);
        $transactions = $transactions->oldest('transactions.created_at')->get();
        $person = Person::findOrFail(request('person_account'));

        $data = [
            'transactions' => $transactions,
            'totalprice' => $totalprice,
            'person' => $person,
            'delivery_from' => $delivery_from,
            'delivery_to' => $delivery_to,
            'title' => $title
        ];
        $pdf = PDF::loadView('transaction.acc_consolidate', $data);
        $pdf->setPaper('a4');
        $pdf->setOption('margin-top', 5);
        $pdf->setOption('margin-bottom', 5);
        $pdf->setOption('margin-left', 8);
        $pdf->setOption('margin-right', 8);
        $pdf->setOption('footer-right', 'Page [page]/[topage]');
        $pdf->setOption('dpi', 70);
        $pdf->setOption('page-width', '210mm');
        $pdf->setOption('page-height', '297mm');
        return $pdf->download($name);
    }

    // retrieve transactions data ()
    private function getTransactionsData()
    {
        $transactions = DB::table('transactions')
                        ->leftJoin('people', 'transactions.person_id', '=', 'people.id')
                        ->leftJoin('profiles', 'people.profile_id', '=', 'profiles.id')
                        ->leftJoin('custcategories', 'people.custcategory_id', '=', 'custcategories.id')
                        ->select(
                                    'people.cust_id', 'people.company',
                                    'people.name', 'people.id as person_id', 'transactions.del_postcode',
                                    'transactions.status', 'transactions.delivery_date', 'transactions.driver',
                                    'transactions.total_qty', 'transactions.pay_status',
                                    'transactions.updated_by', 'transactions.updated_at', 'transactions.delivery_fee', 'transactions.id',
                                    DB::raw('DATE(transactions.delivery_date) AS del_date'),
                                    DB::raw('ROUND((CASE WHEN transactions.gst=1 THEN (
                                                CASE
                                                WHEN transactions.is_gst_inclusive=0
                                                THEN total*((100+transactions.gst_rate)/100)
                                                ELSE transactions.total
                                                END) ELSE transactions.total END) + (CASE WHEN transactions.delivery_fee>0 THEN transactions.delivery_fee ELSE 0 END), 2) AS total'),
                                    'profiles.id as profile_id', 'transactions.gst', 'transactions.is_gst_inclusive', 'transactions.gst_rate',
                                     'custcategories.name as custcategory'
                                );

        // reading whether search input is filled
        if(request('id') or request('cust_id') or request('company') or request('status') or request('pay_status') or request('updated_by') or request('updated_at') or request('delivery_from') or request('delivery_to') or request('driver') or request('profile_id') or request('custcategory') or request('franchisee_id')){
            $transactions = $this->searchDBFilter($transactions);
        }

        // add user profile filters
        $transactions = $this->filterUserDbProfile($transactions);

        // toll to check is franchisee or not
        $transactions = $this->filterFranchiseeTransactionDB($transactions);

        return $transactions;
    }

    private function syncTransaction(Request $request)
    {
        $transaction = Auth::user()->transactions()->create($request->all());

        $this->syncItems($transaction, $request);
    }

    private function syncItems($transaction, $request)
    {
        if ( ! $request->has('item_list'))
        {
            $transaction->items()->detach();

            return;
        }

        $allItemsId = array();

        foreach ($request->item_list as $itemId)
        {
            if (substr($itemId, 0, 4) == 'new:')
            {
                $newItem = Item::create(['name'=>substr($itemId, 4)]);
                $allItemsId[] = $newItem->id;
                continue;
            }
            $allItemsId[] = $itemId;
        }

        $transaction->items()->sync($allItemsId);
    }

    // sync deals with email alert, deals and inventory deduction
    private function syncDeal($transaction, $cartons, $pieces, $amounts, $quotes, $status)
    {
        if($amounts){
            if(array_filter($amounts) != null){
                // create array of errors to fetch errors from loop if any
                $errors = array();
                foreach($amounts as $index => $amount){

                    $dividend = 0;
                    $divisor = 1;



                    if(strpos($qty, '/') !== false) {
                        $dividend = explode('/', $qty)[0];
                        $divisor = explode('/', $qty)[1];
                        $qty = explode('/', $qty)[0]/ explode('/', $qty)[1];
                    }

                    if($qty != NULL or $qty != 0 ){
                        // inventory lookup before saving to deals
                        $item = Item::findOrFail($index);
                        $unitcost = Unitcost::whereItemId($item->id)->whereProfileId($transaction->person->profile_id)->first();
                        // inventory email notification for stock running low
                        if($item->email_limit and !$item->is_vending and $item->is_inventory){
                            if(($status == 1 and $this->calOrderEmailLimit($qty, $item)) or ($status == 2 and $this->calActualEmailLimit($qty, $item))){
                                if(! $item->emailed){
                                    $this->sendEmailAlert($item);
                                    // restrict only send 1 mail if insufficient
                                    $item->emailed = true;
                                    $item->save();
                                }
                            }else{
                                // reactivate email alert
                                $item->emailed = false;
                                $item->save();
                            }
                        }

                        // restrict picking negative stock & deduct/add actual/order if success
                        if($status == 1){
                            if($this->calOrderLimit($qty, $item)){
                                array_push($errors, $item->product_id.' - '.$item->name);
                            }else{
                                $deal = new Deal();
                                $deal->transaction_id = $transaction->id;
                                $deal->item_id = $index;
                                $deal->dividend = $dividend ? $dividend : $qty;
                                $deal->divisor = $divisor;
                                $deal->amount = $amounts[$index];
                                $deal->unit_price = $quotes[$index];
                                $deal->qty_status = $status;
                                if($unitcost) {
                                    $deal->unit_cost = $unitcost->unit_cost;
                                }
                                if($item->is_inventory) {
                                    $deal->qty = $qty;
                                }
                                $deal->save();
                                $this->dealSyncOrder($index);
                            }
                        }else if($status == 2){
                            if($this->calActualLimit($qty, $item)){
                                array_push($errors, $item->product_id.' - '.$item->name);
                            }else{
                                $deal = new Deal();
                                $deal->transaction_id = $transaction->id;
                                $deal->item_id = $index;
                                $deal->dividend = $dividend ? $dividend : $qty;
                                $deal->divisor = $divisor;
                                $deal->amount = $amounts[$index];
                                $deal->unit_price = $quotes[$index];
                                $deal->qty_status = $status;
                                if($unitcost) {
                                    $deal->unit_cost = $unitcost->unit_cost;
                                }
                                if($item->is_inventory) {
                                    $deal->qty = $qty;
                                    $deal->qty_before = $item->qty_now;
                                    $item->qty_now -= strstr($qty, '/') ? $this->fraction($qty) : $qty;
                                    $item->save();
                                    $deal->qty_after = $item->qty_now;
                                }
                                $deal->save();
                                $this->dealSyncOrder($index);
                            }
                        }
                    }
                }
            }
        }

        if($status == 2){
            $this->dealOrder2Actual($transaction->id);
        }

        $deals = Deal::whereTransactionId($transaction->id)->get();
        $deal_total = $deals->sum('amount');
        $deal_totalqty = $deals->sum('qty');
        $transaction->total = $deal_total;
        $transaction->total_qty = $deal_totalqty;
        $transaction->save();

        // sync dtdtransaction totals if valid
        if($transaction->dtdtransaction_id){
            $dtdtransaction = DtdTransaction::findOrFail($transaction->dtdtransaction_id);
            $dtdtransaction->total = $deal_total;
            $dtdtransaction->total_qty = $deal_totalqty;
            $dtdtransaction->save();
        }

        if(isset($errors)){
            if(count($errors) > 0){
                $errors_str = '';
                $errors_str = implode(" <br>", $errors);
                Flash::error('Stock Insufficient 缺货 (Please contact company 请联络公司): <br> '.$errors_str)->important();
            }
        }else{
            Flash::success('Successfully Added');
        }
    }

    // email alert for stock insufficient
    private function sendEmailAlert($item)
    {
        $today = Carbon::now()->format('d-m-Y H:i');
        $emails = EmailAlert::where('status', 'active')->get();
        $email_list = array();
        foreach($emails as $email){
            $email_list[] = $email->email;
        }
        $email = array_unique($email_list);

        // $sender = 'daniel.ma@happyice.com.sg';
        $sender = 'system@happyice.com.sg';

        $data = [
            'product_id' => $item->product_id,
            'name' => $item->name,
            'remark' => $item->remark,
            'unit' => $item->unit,
            'qty_now' => $item->qty_now,
            'lowest_limit' => $item->lowest_limit,
            'email_limit' => $item->email_limit,
        ];

        Mail::send('email.stock_alert', $data, function ($message) use ($item, $email, $today, $sender)
        {
            $message->from($sender);
            $message->subject('Stock Insufficient Alert ['.$item->product_id.'-'.$item->name.'] - '.$today);
            $message->setTo($email);
        });
    }

    private function dealSyncOrder($item_id)
    {
        $item = Item::findOrFail($item_id);
        if($item->is_inventory) {
            $deals = Deal::where('qty_status', '1')->where('item_id', $item_id);
            $item->qty_order = $deals->sum('qty');
            $item->save();
        }
    }

    // convert order to actual deduction
    private function dealOrder2Actual($transaction_id)
    {
        $deals = Deal::where('qty_status', '1')->where('transaction_id', $transaction_id)->get();
        foreach($deals as $deal){
            $item = Item::findOrFail($deal->item_id);
            $deal->qty_status = 2;
            $deal->save();
            if($item->is_inventory === 1) {
                $deal->qty_before = $item->qty_now;
                $item->qty_now -= $deal->qty;
                $item->save();
                $deal->qty_after = $item->qty_now;
                $deal->save();
            }
            $this->dealSyncOrder($item->id);
        }
    }

    private function dealDeleteMultiple($transaction_id)
    {
        $deals = Deal::where('transaction_id', $transaction_id)->get();
        foreach($deals as $deal){
            $item = Item::findOrFail($deal->item_id);
            if($deal->qty_status == '1'){
                $deal->qty_status = 3;
                $deal->save();
            }else if($deal->qty_status == '2'){
                if($item->is_inventory === 1) {
                    $item->qty_now += $deal->qty;
                    $item->save();
                }
                $deal->qty_status = 3;
                $deal->save();
            }
            $this->dealSyncOrder($item->id);
        }
    }

    private function dealUndoDelete($transaction_id)
    {
        $deals = Deal::where('transaction_id', $transaction_id)->where('qty_status', '3')->get();
        $transaction = Transaction::findOrFail($transaction_id);
        if($transaction->cancel_trace === 'Confirmed'){
            foreach($deals as $deal){
                $item = Item::findOrFail($deal->item_id);
                $deal->qty_status = 1;
                $deal->save();
                $this->dealSyncOrder($item->id);
            }
        }else if($transaction->cancel_trace === 'Delivered' or $transaction->cancel_trace === 'Verified Owe' or $transaction->cancel_trace === 'Verified Paid'){
            foreach($deals as $deal){
                $item = Item::findOrFail($deal->item_id);
                $deal->qty_status = 2;
                $deal->save();
                if($item->is_inventory === 1) {
                    $deal->qty_before = $item->qty_now;
                    $item->qty_now -= $deal->qty;
                    $item->save();
                    $deal->qty_after = $item->qty_after;
                    $deal->save();
                }
                // $this->newDealFilter($deal->id);
            }
        }
    }

    private function calOrderLimit($qty, $item)
    {
        $qty = bcdiv($qty, 1, 4);
        if(($item->qty_now - $item->qty_order - $qty < $item->lowest_limit ? $item->lowest_limit : 0) and ($qty > 0) and ($item->is_inventory === 1)) {
            return true;
        }else {
            return false;
        }
    }

    private function calActualLimit($qty, $item)
    {
        if(strstr($qty, '/')) {
            $qty = $this->fraction($qty);
        }
        $qty = bcdiv($qty, 1, 4);
        if(($item->qty_now - $qty < $item->lowest_limit ? $item->lowest_limit : 0) and ($qty > 0) and ($item->is_inventory === 1)) {
            return true;
        }else {
            return false;
        }
    }

    private function calOrderEmailLimit($qty, $item)
    {
        if(strstr($qty, '/')) {
            $qty = $this->fraction($qty);
        }
        $qty = bcdiv($qty, 1, 4);
        if(($item->qty_now - $item->qty_order - $qty < $item->email_limit) and ($qty > 0) and ($item->is_inventory === 1)) {
            return true;
        }else {
            return false;
        }
    }

    private function calActualEmailLimit($qty, $item)
    {
        if(strstr($qty, '/')) {
            $qty = $this->fraction($qty);
        }
        $qty = bcdiv($qty, 1, 4);
        if(($item->qty_now - $qty < $item->email_limit) and ($qty > 0) and ($item->is_inventory === 1)) {
            return true;
        }else{
            return false;
        }
    }

    private function fraction($frac)
    {
        $fraction = explode("/",$frac);
        if($fraction[1] != 0) {
            return $fraction[0]/$fraction[1];
        }
        return "Division by zero error!";
    }

    private function syncOrder($transaction_id)
    {
        $transaction = Transaction::find($transaction_id);
        $dtdtransaction = DtdTransaction::where('transaction_id', $transaction_id)->first();
        // sync to be replaced <-> original
        $this->transactionXChange($dtdtransaction, $transaction);
        // find and sync deals
        $deals = Deal::where('transaction_id', $transaction_id)->get();
        $dtddeals = DtdDeal::where('transaction_id', $dtdtransaction->id)->get();

        if(count($deals) != count($dtddeals)){
            $deal_arr = array();
            $dtddeal_arr = array();
            $dtddeals = DtdDeal::where('transaction_id', $dtdtransaction->id)->get();
            foreach($dtddeals as $dtddeal){
                array_push($dtddeal_arr, $dtddeal->deal_id);
            }

            $dealresults = Deal::where('transaction_id', $dtdtransaction->transaction_id)->whereNotIn('id', $dtddeal_arr)->get();
            foreach($dealresults as $dealresult){
                $unitcost = Unitcost::whereProfileId($dtdtransaction->person->profile_id)->whereItemId($dealresult->item_id)->first();
                $dtddeal = new DtdDeal();
                $dtddeal->item_id = $dealresult->item_id;
                $dtddeal->transaction_id = $dtdtransaction->id;
                $dtddeal->dividend = $dealresult->dividend;
                $dtddeal->divisor = $dealresult->divisor;
                $dtddeal->amount = $dealresult->amount;
                $dtddeal->unit_price = $dealresult->unit_price;
                $dtddeal->qty_status = $dealresult->qty_status;
                $dtddeal->deal_id = $dealresult->id;
                $item = Item::find($dealresult->item_id);
                if($unitcost) {
                    $dtddeal->unit_cost = $unitcost->unit_cost;
                }
                if($item->is_inventoy) {
                    $dtddeal->qty = $dealresult->qty;
                }
                $dtddeal->save();
            }

            $deals = Deal::where('transaction_id', $transaction_id)->get();
            foreach($deals as $deal){
                array_push($deal_arr, $deal->id);
            }

            $dtdresults = DtdDeal::where('transaction_id', $dtdtransaction->id)->whereNotIn('deal_id', $deal_arr)->get();
            foreach($dtdresults as $dtdresult){
                $dtdresult->delete();
            }
        }
    }

    private function dtdDelUpdate($transaction)
    {
        $dtdtransaction = DtdTransaction::where('id', $transaction->dtdtransaction_id)->first();
        if($dtdtransaction){
            $dtdtransaction->status = 'Delivered';
            $dtdtransaction->save();
        }
    }

    private function dtdPaidUpdate($transaction)
    {
        $dtdtransaction = DtdTransaction::where('id', $transaction->dtdtransaction_id)->first();
        if($dtdtransaction){
            $dtdtransaction->pay_status = 'Paid';
            $dtdtransaction->save();
        }
    }

    // exchange transaction attributes
    private function transactionXChange($transactionSync, $transactionOri)
    {
        $transactionSync->total = $transactionOri->total;
        $transactionSync->total_qty = $transactionOri->total_qty;
        $transactionSync->delivery_date = $transactionOri->delivery_date;
        $transactionSync->del_postcode = $transactionOri->del_postcode;
        $transactionSync->status = $transactionOri->status;
        $transactionSync->transremark = $transactionOri->transremark;
        $transactionSync->updated_by = $transactionOri->updated_by;
        $transactionSync->pay_status = $transactionOri->pay_status;
        $transactionSync->person_code = $transactionOri->person_code;
        $transactionSync->person_id = $transactionOri->person_id;
        $transactionSync->order_date = $transactionOri->order_date;
        $transactionSync->del_address = $transactionOri->del_address;
        $transactionSync->name = $transactionOri->name;
        $transactionSync->po_no = $transactionOri->po_no;
        $transactionSync->save();
    }

    // pass value into filter search (collection, collection request) [query]
    private function searchFilter($transactions, Request $request)
    {
        if($request->id){
            $transactions = $transactions->searchId($request->id);
        }
        if($request->cust_id){
            $transactions = $transactions->searchCustId($request->cust_id);
        }
        if($request->company){
            $transactions = $transactions->searchCompany($request->company);
        }
        if($request->status){
            $transactions = $transactions->searchStatus($request->status);
        }
        if($request->pay_status){
            $transactions = $transactions->searchPayStatus($request->pay_status);
        }
        if($request->updated_by){
            $transactions = $transactions->searchUpdatedBy($request->updated_by);
        }
        if($request->updated_at){
            $transactions = $transactions->searchUpdatedAt($request->updated_at);
        }
        if($request->delivery_date){
            $transactions = $transactions->searchDeliveryDate($request->delivery_date);
        }
        if($request->driver){
            $transactions = $transactions->searchDriver($request->driver);
        }
        if($request->profile){
            $transactions = $transactions->searchProfile($request->profile);
        }
        if($request->sortName){
            $transactions = $transactions->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }
        return $transactions;
    }

    // pass value into filter search for DB (collection, collection request) [query]
    private function searchDBFilter($transactions)
    {
        if(request('id')){
            $transactions = $transactions->where('transactions.id', 'LIKE', '%'.request('id').'%');
        }
        if(request('cust_id')){
            $transactions = $transactions->where('people.cust_id', 'LIKE', '%'.request('cust_id').'%');
        }
        if(request('company')){
            $com = request('company');
            $transactions = $transactions->where(function($query) use ($com){
                $query->where('people.company', 'LIKE', '%'.$com.'%')
                        ->orWhere(function ($query) use ($com){
                            $query->where('people.cust_id', 'LIKE', 'D%')
                                    ->where('people.name', 'LIKE', '%'.$com.'%');
                        });
                });
        }
        if(request('status')){
            $transactions = $transactions->where('transactions.status', 'LIKE', '%'.request('status').'%');
        }
        if(request('pay_status')){
            $transactions = $transactions->where('transactions.pay_status', 'LIKE', '%'.request('pay_status').'%');
        }
        if(request('updated_by')){
            $transactions = $transactions->where('transactions.updated_by', 'LIKE', '%'.request('updated_by').'%');
        }
        if(request('updated_at')){
            $transactions = $transactions->where('transactions.updated_at', 'LIKE', '%'.$request->updated_at.'%');
        }
        if(request('delivery_from') === request('delivery_to')){
            if(request('delivery_from') != '' and request('delivery_to') != ''){
                $transactions = $transactions->where('transactions.delivery_date', '=', request('delivery_from'));
            }
        }else{
            if(request('delivery_from')){
                $transactions = $transactions->where('transactions.delivery_date', '>=', request('delivery_from'));
            }
            if(request('delivery_to')){
                $transactions = $transactions->where('transactions.delivery_date', '<=', request('delivery_to'));
            }
        }
        if(request('driver')){
            $transactions = $transactions->where('transactions.driver', 'LIKE', '%'.request('driver').'%');
        }
        if(request('profile_id')){
            $transactions = $transactions->where('profiles.id', request('profile_id'));
        }
        if(request('custcategory')) {
            $transactions = $transactions->where('custcategories.id', request('custcategory'));
        }
        // add in franchisee checker
        if (auth()->user()->hasRole('franchisee')) {
            $transactions = $transactions->whereIn('people.franchisee_id', [auth()->user()->id]);
        } else if(auth()->user()->hasRole('subfranchisee')) {
            $transactions = $transactions->whereIn('people.franchisee_id', [auth()->user()->master_franchisee_id]);
        } else if(request('franchisee_id')) {
            $transactions = $transactions->where('people.franchisee_id', request('franchisee_id'));
        }

        if(request('sortName')){
            $transactions = $transactions->orderBy(request('sortName'), request('sortBy') ? 'asc' : 'desc');
        }
        return $transactions;
    }

    // calculating gst and non for delivered total
    private function calTransactionTotal($query)
    {
        $total_amount = 0;
        $nonGst_amount = 0;
        $gst_exclusive = 0;
        $gst_inclusive = 0;
        $query1 = clone $query;
        $query2 = clone $query;
        $query3 = clone $query;

        $nonGst_amount = $query1->with('person.profile')->whereHas('person.profile', function($query1){
                            $query1->where('gst', 0);
                        })->sum(DB::raw('ROUND(total, 2)'));

        $gst_exclusive = $query2->whereHas('person', function($query2) {
                                    $query2->where('is_gst_inclusive', 0);
                                })
                                ->sum(DB::raw('ROUND((total * person.gst_rate/100), 2)'));

        $gst_inclusive = $query3->whereHas('person', function($query3) {
                                    $query3->where('is_gst_inclusive', 1);
                                })
                                ->sum(DB::raw('ROUND(total, 2)'));

        $total_amount = $nonGst_amount + $gst_exclusive + $gst_inclusive;
        return $total_amount;
    }

    // calculating gst and non for delivered total
    private function calDBTransactionTotal($query)
    {
        $total_amount = 0;
        $nonGst_amount = 0;
        $gst_exclusive = 0;
        $gst_inclusive = 0;
        $query1 = clone $query;
        $query2 = clone $query;
        $query3= clone $query;

        $nonGst_amount = $query1->where('transactions.gst', 0)->where('transactions.status', '!=', 'Cancelled')->sum(DB::raw('ROUND(transactions.total, 2)'));
        $gst_exclusive = $query2->where('transactions.gst', 1)->where('transactions.is_gst_inclusive', 0)->where('transactions.status', '!=', 'Cancelled')->sum(DB::raw('ROUND((transactions.total * (100 + transactions.gst_rate)/100), 2)'));
        $gst_inclusive = $query3->where('transactions.gst', 1)->where('transactions.is_gst_inclusive', 1)->where('transactions.status', '!=', 'Cancelled')->sum(DB::raw('ROUND(transactions.total, 2)'));

        $total_amount = $nonGst_amount + $gst_exclusive + $gst_inclusive;

        return $total_amount;
    }

    // calculate delivery fees total
    private function calDBDeliveryTotal($query)
    {
        $query3 = clone $query;
        $delivery_fee = $query3->where('transactions.status', '!=', 'Cancelled')->sum(DB::raw('ROUND(transactions.delivery_fee, 2)'));
        return $delivery_fee;
    }

    // validate is whether vending machine for the final state udpate(Formrequest $request, int transaction_id)
    private function vendingMachineValidation($request, $transaction_id)
    {
        $transaction = Transaction::findOrFail($transaction_id);
        // dd($transaction->person->toArray());
        if($transaction->person->is_vending === 1) {
            $this->validate($request, [
                'digital_clock' => 'integer',
                'balance_coin' => 'numeric'
            ], [
                'digital_clock.integer' => 'Digital clock must be in integer',
                'balance_coin.numeric' => 'Balance coin must be in numbers'
            ]);
        }

        // sales count and sales amount figure must be filled
        if($transaction->person->is_dvm and auth()->user()->hasRole('driver')) {
            $this->validate($request, [
                'sales_count' => 'required',
                'sales_amount' => 'required'
            ], [
                'sales_count.required' => 'Sales Count (pcs) must be filled',
                'sales_amount.required' => 'Sales Amount ($) must be filled'
            ]);
            if($request->sales_count <= 0 and $request->sales_amount <= 0) {
                Flash::error('Sales count and Sales amount must be greater than 0');
            }
        }
    }

    // lookup and sync ftransaction if applicable(collection transaction)
/*    private function syncFtransactionsAndTransactions($transaction)
    {
        $ftransaction = Ftransaction::updateOrCreate([
            'transaction_id' => $transaction->id
        ], [
            'ftransaction_id' => $transaction->ftransaction_id ? $transaction->ftransaction_id : $this->getFtransactionIncrement($transaction->person->franchisee_id),
            'total' => $transaction->total ? $transaction->total : 0,
            'delivery_date' => $transaction->delivery_date,
            'status' => $transaction->status,
            'transremark' => $transaction->transremark,
            'updated_by' => $transaction->updated_by,
            'pay_status' => $transaction->pay_status,
            'person_code' => $transaction->person_code,
            'person_id' => $transaction->person_id,
            'order_date' => $transaction->order_date,
            'driver' => $transaction->driver,
            'paid_by' => $transaction->paid_by,
            'del_address' => $transaction->del_address,
            'name' => $transaction->name,
            'po_no' => $transaction->po_no,
            'total_qty' => $transaction->total_qty,
            'pay_method' => $transaction->pay_method,
            'note' => $transaction->note,
            'paid_at' => $transaction->paid_at,
            'cancel_trace' => $transaction->cancel_trace,
            'contact' => $transaction->contact,
            'del_postcode' => $transaction->del_postcode,
            'delivery_fee' => $transaction->delivery_fee,
            'bill_address' => $transaction->bill_address,
            'digital_clock' => $transaction->digital_clock,
            'analog_clock' => $transaction->analog_clock,
            'balance_coin' => $transaction->balance_coin,
            'is_freeze' => $transaction->is_freeze,
            'is_required_analog' => $transaction->is_required_analog,
            'franchisee_id' => $transaction->person->franchisee_id,
        ]);
    }*/
}
