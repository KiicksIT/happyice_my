<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests;
use App\Paysummaryinfo;
use App\Transaction;
use App\Month;
use App\Item;
use App\Person;
use Carbon\Carbon;
use Auth;
use DB;

class DetailRptController extends Controller
{
    // detect authed
    public function __construct()
    {
        $this->middleware('auth');
    }

    // return index page for detailed report - account
    public function accountIndex()
    {
        $month_options = $this->getMonthOptions();
        return view('detailrpt.account.index', compact('month_options'));
    }

    // return index page for detailed report - sales
    public function salesIndex()
    {
        $month_options = $this->getMonthOptions();
        return view('detailrpt.sales.index', compact('month_options'));
    }

    // retrieve the account cust detail rpt(FormRequest $request)
    public function getAccountCustdetailApi(Request $request)
    {
        // showing total amount init
        $total_amount = 0;
        $input = $request->all();
        // initiate the page num when null given
        $pageNum = $request->pageNum ? $request->pageNum : 100;

        $transactions = DB::table('transactions')
                        ->leftJoin('people', 'transactions.person_id', '=', 'people.id')
                        ->leftJoin('profiles', 'people.profile_id', '=', 'profiles.id')
                        ->leftJoin('custcategories', 'custcategories.id', '=', 'people.custcategory_id')
                        ->select(
                                    DB::raw('ROUND(CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN transactions.total*107/100 + transactions.delivery_fee ELSE transactions.total*107/100 END) ELSE (CASE WHEN transactions.delivery_fee>0 THEN transactions.total + transactions.delivery_fee ELSE transactions.total END) END, 2) AS total'),
                                    'transactions.id', 'people.cust_id', 'people.company',
                                    'people.name', 'people.id as person_id',
                                    'transactions.status', 'transactions.delivery_date', 'profiles.name as profile_name',
                                    'transactions.pay_status',
                                    'profiles.id as profile_id', 'transactions.order_date',
                                    'profiles.gst', 'transactions.delivery_fee', 'transactions.paid_at',
                                    'custcategories.name as custcategory'
                                );

        // reading whether search input is filled
        if($request->id or $request->cust_id or $request->company or $request->status or $request->pay_status or $request->updated_by or $request->updated_at or $request->delivery_from or $request->delivery_to or $request->driver or $request->profile or $request->custcategory){
            $transactions = $this->searchTransactionDBFilter($transactions, $request);
        }

        $total_amount = $this->calDBOriginalTotal($transactions);

        if($request->exportSOA) {
            $this->convertSoaExcel($transactions, $total_amount);
        }

        if($request->sortName){
            $transactions = $transactions->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }

        if($pageNum == 'All'){
            $transactions = $transactions->latest('transactions.created_at')->get();
        }else{
            $transactions = $transactions->latest('transactions.created_at')->paginate($pageNum);
        }

        if($request->exportExcel) {
            $this->convertAccountCustdetailExcel($transactions, $total_amount);
        }

        $data = [
            'total_amount' => $total_amount,
            'transactions' => $transactions,
        ];

        return $data;
    }

    // retrieve the account outstanding rpt(FormRequest $request)
    public function getAccountOutstandingApi(Request $request)
    {
        // showing total amount init
        $total_amount = 0;
        $input = $request->all();
        // initiate the page num when null given
        $pageNum = $request->pageNum ? $request->pageNum : 100;

        // indicate the month and year
        $carbondate = Carbon::createFromFormat('m-Y', $request->current_month);
        $prevMonth = Carbon::createFromFormat('m-Y', $request->current_month)->subMonth();
        $prev2Months = Carbon::createFromFormat('m-Y', $request->current_month)->subMonths(2);
        $prev3Months = Carbon::createFromFormat('m-Y', $request->current_month)->subMonths(3);

        $thistotal = DB::raw("(SELECT ROUND(SUM(CASE WHEN profiles.gst=1 THEN (CASE WHEN delivery_fee>0 THEN total*107/100 + delivery_fee ELSE total*107/100 END) ELSE (CASE WHEN delivery_fee>0 THEN total + delivery_fee ELSE total END) END), 2) AS thistotal, people.id AS person_id, people.profile_id FROM transactions
                                LEFT JOIN people ON transactions.person_id=people.id
                                LEFT JOIN profiles ON people.profile_id=profiles.id
                                WHERE transactions.delivery_date>='".$carbondate->startOfMonth()->toDateString()."'
                                AND transactions.delivery_date<='".$carbondate->endOfMonth()->toDateString()."'
                                AND pay_status='Owe'
                                AND (status='Delivered' OR status='Verified Owe')
                                GROUP BY people.id) thistotal");

        $prevtotal = DB::raw("(SELECT ROUND(SUM(CASE WHEN profiles.gst=1 THEN (CASE WHEN delivery_fee>0 THEN total*107/100 + delivery_fee ELSE total*107/100 END) ELSE (CASE WHEN delivery_fee>0 THEN total + delivery_fee ELSE total END) END), 2) AS prevtotal, people.id AS person_id, people.profile_id FROM transactions
                                LEFT JOIN people ON transactions.person_id=people.id
                                LEFT JOIN profiles ON people.profile_id=profiles.id
                                WHERE transactions.delivery_date>='".$prevMonth->startOfMonth()->toDateString()."'
                                AND transactions.delivery_date<='".$prevMonth->endOfMonth()->toDateString()."'
                                AND pay_status='Owe'
                                AND (status='Delivered' OR status='Verified Owe')
                                GROUP BY people.id) prevtotal");

        $prev2total = DB::raw("(SELECT ROUND(SUM(CASE WHEN profiles.gst=1 THEN (CASE WHEN delivery_fee>0 THEN total*107/100 + delivery_fee ELSE total*107/100 END) ELSE (CASE WHEN delivery_fee>0 THEN total + delivery_fee ELSE total END) END), 2) AS prev2total, people.id AS person_id, people.profile_id FROM transactions
                                LEFT JOIN people ON transactions.person_id=people.id
                                LEFT JOIN profiles ON people.profile_id=profiles.id
                                WHERE transactions.delivery_date>='".$prev2Months->startOfMonth()->toDateString()."'
                                AND transactions.delivery_date<='".$prev2Months->endOfMonth()->toDateString()."'
                                AND pay_status='Owe'
                                AND (status='Delivered' OR status='Verified Owe')
                                GROUP BY people.id) prev2total");

        $prevmore3total = DB::raw("(SELECT ROUND(SUM(CASE WHEN profiles.gst=1 THEN (CASE WHEN delivery_fee>0 THEN total*107/100 + delivery_fee ELSE total*107/100 END) ELSE (CASE WHEN delivery_fee>0 THEN total + delivery_fee ELSE total END) END), 2) AS prevmore3total, people.id AS person_id, people.profile_id FROM transactions
                                LEFT JOIN people ON transactions.person_id=people.id
                                LEFT JOIN profiles ON people.profile_id=profiles.id
                                WHERE transactions.delivery_date<='".$prev3Months->endOfMonth()->toDateString()."'
                                AND pay_status='Owe'
                                AND (status='Delivered' OR status='Verified Owe')
                                GROUP BY people.id) prevmore3total");

        $transactions = DB::table('transactions')
                        ->leftJoin('people', 'transactions.person_id', '=', 'people.id')
                        ->leftJoin('profiles', 'people.profile_id', '=', 'profiles.id')
                        ->leftJoin('custcategories', 'custcategories.id', '=', 'people.custcategory_id')
                        ->leftJoin($thistotal, 'people.id', '=', 'thistotal.person_id')
                        ->leftJoin($prevtotal, 'people.id', '=', 'prevtotal.person_id')
                        ->leftJoin($prev2total, 'people.id', '=', 'prev2total.person_id')
                        ->leftJoin($prevmore3total, 'people.id', '=', 'prevmore3total.person_id')
                        ->select(
                                    'people.cust_id', 'people.company', 'people.name', 'people.id as person_id',
                                    'profiles.name as profile_name', 'profiles.id as profile_id', 'profiles.gst',
                                    'transactions.id', 'transactions.status', 'transactions.delivery_date', 'transactions.pay_status', 'transactions.delivery_fee', 'transactions.paid_at', 'transactions.created_at',
                                    'custcategories.name as custcategory',
                                    'thistotal.thistotal AS thistotal', 'prevtotal.prevtotal AS prevtotal', 'prev2total.prev2total AS prev2total', 'prevmore3total.prevmore3total AS prevmore3total'
                                );

        if($request->id or $request->cust_id or $request->company or $request->status or $request->pay_status or $request->updated_by or $request->updated_at or $request->driver or $request->profile){
            $transactions = $this->searchTransactionDBFilter($transactions, $request);
        }
        $transactions = $transactions->latest('transactions.created_at')->groupBy('people.id');
        if($request->sortName){
            $transactions = $transactions->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }
        $total_amount = $this->calCustoutstandingTotal($transactions);

        if($pageNum == 'All'){
            $transactions = $transactions->get();
        }else{
            $transactions = $transactions->paginate($pageNum);
        }

        $data = [
            'total_amount' => $total_amount,
            'transactions' => $transactions,
        ];

        return $data;
    }

    // retrieve the account customer payment detail rpt
    public function getAccountPaydetailApi(Request $request)
    {
        // showing total amount init
        $total_amount = 0;
        $input = $request->all();
        $pageNum = $request->pageNum ? $request->pageNum : 100;

        $transactions = DB::table('transactions')
                        ->leftJoin('people', 'transactions.person_id', '=', 'people.id')
                        ->leftJoin('profiles', 'people.profile_id', '=', 'profiles.id')
                        ->leftJoin('custcategories', 'custcategories.id', '=', 'people.custcategory_id')
                        ->select(
                                    'people.cust_id', 'people.company', 'people.name', 'people.id as person_id',
                                    'profiles.name as profile_name', 'profiles.id as profile_id',
                                    'transactions.id', 'transactions.delivery_fee', 'transactions.paid_at', 'transactions.status', 'transactions.delivery_date', 'transactions.pay_status', 'transactions.order_date', 'transactions.note', 'transactions.pay_method',
                                    'custcategories.name as custcategory',
                                    DB::raw('(CASE WHEN transactions.delivery_fee>0 THEN (transactions.total + transactions.delivery_fee) ELSE transactions.total END) AS inv_amount'),
                                    DB::raw('(CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END) AS amount'),
                                    DB::raw('(CASE WHEN profiles.gst=1 THEN (transactions.total * 7/100) ELSE null END) AS gst')
                                );
        // reading whether search input is filled
        if($request->profile_id or $request->payment_from or $request->delivery_from or $request->cust_id or $request->payment_to or $request->delivery_to or $request->company or $request->payment or $request->status or $request->person_id or $request->pay_method or $request->custcategory) {
            $transactions = $this->searchTransactionDBFilter($transactions, $request);
        }
        if($request->sortName){
            $transactions = $transactions->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }

        $caldata = $this->calPayDetailTotal($transactions);

        if($pageNum == 'All'){
            $transactions = $transactions->latest('transactions.created_at')->get();
        }else{
            $transactions = $transactions->latest('transactions.created_at')->paginate($pageNum);
        }

        $data = [
            'total_inv_amount' => $caldata['total_inv_amount'],
            'total_gst' => $caldata['total_gst'],
            'total_amount' => $caldata['total_amount'],
            'transactions' => $transactions,
        ];

        return $data;
    }

    // retrieve the account customer payment summary api
    public function getAccountPaysummaryApi(Request $request)
    {
        // showing total amount init
        $total_amount = 0;
        $input = $request->all();
        // initiate the page num when null given
        $pageNum = $request->pageNum ? $request->pageNum : 100;

        $transactions = DB::table('transactions')
                        ->leftJoin('people', 'people.id', '=', 'transactions.person_id')
                        ->leftJoin('profiles', 'people.profile_id', '=', 'profiles.id')
                        ->leftJoin('paysummaryinfos', function($join) {
                            $join->on(DB::raw('Date(paysummaryinfos.paid_at)'), '=', DB::raw('Date(transactions.paid_at)'));
                            $join->on('paysummaryinfos.pay_method', '=', 'transactions.pay_method');
                            $join->on('paysummaryinfos.profile_id', '=', 'profiles.id');
                        })
                        ->leftJoin('users', 'users.id', '=', 'paysummaryinfos.user_id')
                        ->select(
                                    'profiles.name as profile', 'profiles.id as profile_id',
                                    'transactions.delivery_fee', 'transactions.pay_status', 'transactions.pay_method', 'transactions.paid_at as payreceived_date',
                                    'users.name',
                                    'paysummaryinfos.remark',
                                    DB::raw('DATE(paysummaryinfos.bankin_date) AS bankin_date'),
                                    DB::raw('SUM(ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)) AS total')
                                );
        // reading whether search input is filled
        if($request->profile_id or $request->payment_from or $request->payment_to or $request->bankin_from or $request->bankin_to){
            $transactions = $this->searchTransactionDBFilter($transactions, $request);
        }
        // paid conditions
        $transactions = $transactions->where('transactions.pay_status', 'Paid')->whereNotNull('transactions.pay_method');
        $caldata = $this->calAccPaySummary($transactions);

        $transactions = $transactions->groupBy(DB::raw('Date(transactions.paid_at)'), 'profiles.id', 'transactions.pay_method')->orderBy('transactions.paid_at', 'profiles.id');
        if($request->sortName){
            $transactions = $transactions->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }
        if($pageNum == 'All'){
            $transactions = $transactions->get();
        }else{
            $transactions = $transactions->paginate($pageNum);
        }

        $data = [
            'total_cash_happyice' => $caldata['total_cash_happyice'],
            'total_cheque_happyice' => $caldata['total_cheque_happyice'],
            'total_tt_happyice' => $caldata['total_tt_happyice'],
            'total_cash_logistic' => $caldata['total_cash_logistic'],
            'total_cheque_logistic' => $caldata['total_cheque_logistic'],
            'total_tt_logistic' => $caldata['total_tt_logistic'],
            'total_cash_all' => $caldata['total_cash_all'],
            'total_cheque_all' => $caldata['total_cheque_all'],
            'total_tt_all' => $caldata['total_tt_all'],
            'transactions' => $transactions,
        ];

        if($request->export_excel) {
            $this->paySummaryExportExcel($data);
        }

        return $data;
    }

    // retrieve the sales cust detail(FormRequest $request)
    public function getSalesCustdetailApi(Request $request)
    {
        // showing total amount init
        $total_amount = 0;
        $input = $request->all();
        // initiate the page num when null given
        $pageNum = $request->pageNum ? $request->pageNum : 100;

        // indicate the month and year
        $carbondate = Carbon::createFromFormat('m-Y', $request->current_month);
        $prevMonth = Carbon::createFromFormat('m-Y', $request->current_month)->subMonth();
        $prev2Months = Carbon::createFromFormat('m-Y', $request->current_month)->subMonths(2);
        $prevYear = Carbon::createFromFormat('m-Y', $request->current_month)->subYear();
        $delivery_from = $carbondate->startOfMonth()->toDateString();
        $delivery_to = $carbondate->endOfMonth()->toDateString();
        $request->merge(array('delivery_from' => $delivery_from));
        $request->merge(array('delivery_to' => $delivery_to));
        $status = $request->status;
        if($status) {
            if($status == 'Delivered') {
                $statusStr = " (transactions.status='Delivered' or transactions.status='Verified Owe' or transactions.status='Verified Paid')";
            }else {
                $statusStr = " transactions.status='".$status."'";
            }
        }else {
            $statusStr = ' 1=1';
        }

        $thistotal = DB::raw("(
                            SELECT people.id AS person_id, ROUND(SUM(deals.amount), 2) AS thistotal,
                                people.profile_id
                                FROM deals
                                LEFT JOIN items ON items.id=deals.item_id
                                LEFT JOIN transactions ON transactions.id=deals.transaction_id
                                LEFT JOIN people ON transactions.person_id=people.id
                                LEFT JOIN profiles ON people.profile_id=profiles.id
                                WHERE transactions.delivery_date>='".$delivery_from."'
                                AND transactions.delivery_date<='".$delivery_to."'
                                AND ".$statusStr."
                                GROUP BY people.id) thistotal");

        $prevtotal = DB::raw("(SELECT people.id AS person_id, ROUND(SUM(deals.amount), 2) AS prevtotal,
                                people.profile_id
                                FROM deals
                                LEFT JOIN items ON items.id=deals.item_id
                                LEFT JOIN transactions ON transactions.id=deals.transaction_id
                                LEFT JOIN people ON transactions.person_id=people.id
                                LEFT JOIN profiles ON people.profile_id=profiles.id
                                WHERE transactions.delivery_date>='".$prevMonth->startOfMonth()->toDateString()."'
                                AND transactions.delivery_date<='".$prevMonth->endOfMonth()->toDateString()."'
                                AND ".$statusStr."
                                GROUP BY people.id) prevtotal");

        $prev2total = DB::raw("(SELECT people.id AS person_id, ROUND(SUM(deals.amount), 2) AS prev2total,
                                people.profile_id
                                FROM deals
                                LEFT JOIN items ON items.id=deals.item_id
                                LEFT JOIN transactions ON transactions.id=deals.transaction_id
                                LEFT JOIN people ON transactions.person_id=people.id
                                LEFT JOIN profiles ON people.profile_id=profiles.id
                                WHERE transactions.delivery_date>='".$prev2Months->startOfMonth()->toDateString()."'
                                AND transactions.delivery_date<='".$prev2Months->endOfMonth()->toDateString()."'
                                AND ".$statusStr."
                                GROUP BY people.id) prev2total");

        $prevyeartotal = DB::raw("(SELECT people.id AS person_id, ROUND(SUM(deals.amount), 2) AS prevyeartotal,
                                people.profile_id
                                FROM deals
                                LEFT JOIN items ON items.id=deals.item_id
                                LEFT JOIN transactions ON transactions.id=deals.transaction_id
                                LEFT JOIN people ON transactions.person_id=people.id
                                LEFT JOIN profiles ON people.profile_id=profiles.id
                                WHERE transactions.delivery_date>='".$prevYear->startOfMonth()->toDateString()."'
                                AND transactions.delivery_date<='".$prevYear->endOfMonth()->toDateString()."'
                                AND ".$statusStr."
                                GROUP BY people.id) prevyeartotal");

        $transactions = DB::table('deals')
                        ->leftJoin('items', 'items.id', '=', 'deals.item_id')
                        ->leftJoin('transactions', 'transactions.id', '=', 'deals.transaction_id')
                        ->leftJoin('people', 'transactions.person_id', '=', 'people.id')
                        ->leftJoin('profiles', 'people.profile_id', '=', 'profiles.id')
                        ->leftJoin('custcategories', 'custcategories.id', '=', 'people.custcategory_id')
                        ->leftJoin($thistotal, 'people.id', '=', 'thistotal.person_id')
                        ->leftJoin($prevtotal, 'people.id', '=', 'prevtotal.person_id')
                        ->leftJoin($prev2total, 'people.id', '=', 'prev2total.person_id')
                        ->leftJoin($prevyeartotal, 'people.id', '=', 'prevyeartotal.person_id')
                        ->select(
                                    'people.cust_id', 'people.company', 'people.name', 'people.id as person_id',
                                    'profiles.name as profile_name', 'profiles.id as profile_id', 'profiles.gst',
                                    'transactions.id', 'transactions.status', 'transactions.delivery_date', 'transactions.pay_status', 'transactions.delivery_fee', 'transactions.paid_at', 'transactions.created_at',
                                    'custcategories.name as custcategory',
                                    'thistotal.thistotal AS thistotal', 'prevtotal.prevtotal AS prevtotal', 'prev2total.prev2total AS prev2total', 'prevyeartotal.prevyeartotal AS prevyeartotal'
                                );

        if($request->id or $request->current_month or $request->cust_id or $request->company or $request->delivery_from or $request->delivery_to or $request->profile_id or $request->id_prefix or $request->custcategory or $request->status){
            $transactions = $this->searchTransactionDBFilter($transactions, $request);
        }

        $transactions = $transactions->orderBy('people.cust_id')->groupBy('people.id');

        if($request->sortName){
            $transactions = $transactions->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }

        $total_amount = $this->calTransactionTotalSql($transactions);

        if($pageNum == 'All'){
            $transactions = $transactions->get();
        }else{
            $transactions = $transactions->paginate($pageNum);
        }

        $data = [
            'total_amount' => $total_amount,
            'transactions' => $transactions,
        ];

        return $data;
    }

    // retrieve the product detail for month api(FormRequest $request)
    public function getSalesProductDetailMonthApi(Request $request)
    {
        // showing total amount init
        $total_amount = 0;
        $input = $request->all();
        // initiate the page num when null given
        $pageNum = $request->pageNum ? $request->pageNum : 100;
        $thismonth = Carbon::createFromFormat('m-Y', $request->current_month);
        $prevMonth = Carbon::createFromFormat('m-Y', $request->current_month)->subMonth();
        $prev2Months = Carbon::createFromFormat('m-Y', $request->current_month)->subMonths(2);
        $prevYear = Carbon::createFromFormat('m-Y', $request->current_month)->subYear();
        $profile_id = $request->profile_id;

        $thistotal = "(SELECT ROUND(SUM(amount), 2) AS amount, ROUND(SUM(qty), 4) AS qty, deals.item_id, profiles.name AS profile_name, profiles.id AS profile_id, deals.id AS id FROM deals
                        LEFT JOIN transactions ON transactions.id=deals.transaction_id
                        LEFT JOIN people ON people.id=transactions.person_id
                        LEFT JOIN profiles ON profiles.id=people.profile_id
                        WHERE transactions.delivery_date>='".$thismonth->startOfMonth()->toDateString()."'
                        AND transactions.delivery_date<='".$thismonth->endOfMonth()->toDateString()."'";
        $prevqty = "(SELECT ROUND(SUM(qty), 4) AS qty, deals.item_id, profiles.name AS profile_name, profiles.id AS profile_id, deals.id FROM deals
                    LEFT JOIN transactions ON transactions.id=deals.transaction_id
                    LEFT JOIN people ON people.id=transactions.person_id
                    LEFT JOIN profiles ON profiles.id=people.profile_id
                    WHERE transactions.delivery_date>='".$prevMonth->startOfMonth()->toDateString()."'
                    AND transactions.delivery_date<='".$prevMonth->endOfMonth()->toDateString()."'";
        $prev2qty = "(SELECT ROUND(SUM(qty), 4) AS qty, deals.item_id, profiles.name AS profile_name, profiles.id AS profile_id, deals.id FROM deals
                    LEFT JOIN transactions ON transactions.id=deals.transaction_id
                    LEFT JOIN people ON people.id=transactions.person_id
                    LEFT JOIN profiles ON profiles.id=people.profile_id
                    WHERE transactions.delivery_date>='".$prev2Months->startOfMonth()->toDateString()."'
                    AND transactions.delivery_date<='".$prev2Months->endOfMonth()->toDateString()."'";
        $prevyrqty = "(SELECT ROUND(SUM(qty), 4) AS qty, deals.item_id, profiles.name AS profile_name, profiles.id AS profile_id, deals.id FROM deals
                        LEFT JOIN transactions ON transactions.id=deals.transaction_id
                        LEFT JOIN people ON people.id=transactions.person_id
                        LEFT JOIN profiles ON profiles.id=people.profile_id
                        WHERE transactions.delivery_date>='".$prevYear->startOfMonth()->toDateString()."'
                        AND transactions.delivery_date<='".$prevYear->endOfMonth()->toDateString()."'";

        if($request->status) {
            if($request->status === 'Delivered') {
                $thistotal .= " AND (transactions.status='Delivered' OR transactions.status='Verified Owe' OR transactions.status='Verified Paid')";
                $prevqty .= " AND (transactions.status='Delivered' OR transactions.status='Verified Owe' OR transactions.status='Verified Paid')";
                $prev2qty .= " AND (transactions.status='Delivered' OR transactions.status='Verified Owe' OR transactions.status='Verified Paid')";
                $prevyrqty .= " AND (transactions.status='Delivered' OR transactions.status='Verified Owe' OR transactions.status='Verified Paid')";
            }else {
                $thistotal .= " AND transactions.status='".$request->status."'";
                $prevqty .= " AND transactions.status='".$request->status."'";
                $prev2qty .= " AND transactions.status='".$request->status."'";
                $prevyrqty .= " AND transactions.status='".$request->status."'";
            }
        }
        if($request->profile_id) {
            $thistotal .= " GROUP BY item_id, profile_id) thistotal";
            $prevqty .= " GROUP BY item_id, profile_id) prevqty";
            $prev2qty .= " GROUP BY item_id, profile_id) prev2qty";
            $prevyrqty .= " GROUP BY item_id, profile_id) prevyrqty";
        }else {
            $thistotal .= " GROUP BY item_id) thistotal";
            $prevqty .= " GROUP BY item_id) prevqty";
            $prev2qty .= " GROUP BY item_id) prev2qty";
            $prevyrqty .= " GROUP BY item_id) prevyrqty";
        }
        $thistotal = DB::raw($thistotal);
        $prevqty = DB::raw($prevqty);
        $prev2qty = DB::raw($prev2qty);
        $prevyrqty = DB::raw($prevyrqty);

        $items = DB::table('deals')
                ->leftJoin('items', 'items.id', '=', 'deals.item_id')
                ->leftJoin('transactions', 'transactions.id', '=', 'deals.transaction_id')
                ->leftJoin('people', 'people.id', '=', 'transactions.person_id')
                ->leftJoin('profiles', 'profiles.id', '=', 'people.profile_id')
                ->leftJoin($thistotal, function($join) use ($profile_id) {
                    if($profile_id) {
                        $join->on('thistotal.profile_id', '=', 'profiles.id');
                    }
                    $join->on('thistotal.item_id', '=', 'items.id');
                })
                ->leftJoin($prevqty, function($join) use ($profile_id){
                    if($profile_id) {
                        $join->on('thistotal.profile_id', '=', 'profiles.id');
                    }
                    $join->on('prevqty.item_id', '=', 'items.id');
                })
                ->leftJoin($prev2qty, function($join) use ($profile_id){
                    if($profile_id) {
                        $join->on('prev2qty.profile_id', '=', 'profiles.id');
                    }
                    $join->on('prev2qty.item_id', '=', 'items.id');
                })
                ->leftJoin($prevyrqty, function($join) use ($profile_id){
                    if($profile_id) {
                        $join->on('prevyrqty.profile_id', '=', 'profiles.id');
                    }
                    $join->on('prevyrqty.item_id', '=', 'items.id');
                })
                ->select(
                        'items.name AS product_name', 'items.remark', 'items.product_id', 'items.id', 'items.is_inventory',
                        'thistotal.amount AS amount', 'thistotal.qty AS qty', 'profiles.name AS profile_name', 'profiles.id AS profile_id',
                        'transactions.status',
                        'prevqty.qty AS prevqty', 'prev2qty.qty AS prev2qty', 'prevyrqty.qty AS prevyrqty'
                    );

        // reading whether search input is filled
        if($request->profile_id or $request->current_month or $request->id_prefix or $request->cust_id or $request->company or $request->custcategory or $request->status) {
            $items = $this->searchItemDBFilter($items, $request);
        }
        if($request->profile_id) {
            $items = $items->groupBy('items.id', 'profiles.id')->orderBy('items.product_id');
        }else {
            $items = $items->groupBy('items.id')->orderBy('items.product_id');
        }

        if($request->sortName){
            $items = $items->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }

        if($pageNum == 'All'){
            $items = $items->get();
        }else{
            $items = $items->paginate($pageNum);
        }

        $totals = $this->calSalesProductDetailMonthTotals($items);

        $data = [
            'total_qty' => $totals['total_qty'],
            'total_amount' => $totals['total_amount'],
            'items' => $items,
        ];
        return $data;
    }

    // retrieve the product detail for day api(FormRequest $request)
    public function getSalesProductDetailDayApi(Request $request)
    {
        // showing total amount init
        $total_amount = 0;
        $input = $request->all();
        // initiate the page num when null given
        $pageNum = $request->pageNum ? $request->pageNum : 100;

        $amountstr = "SELECT ROUND(SUM(amount), 2) AS thisamount, ROUND(SUM(qty), 2) AS thisqty, item_id, transaction_id
                        FROM deals
                        LEFT JOIN items ON items.id=deals.item_id
                        LEFT JOIN transactions ON transactions.id=deals.transaction_id
                        LEFT JOIN people ON people.id=transactions.person_id
                        LEFT JOIN profiles ON profiles.id=people.profile_id
                        LEFT JOIN custcategories ON custcategories.id=people.custcategory_id
                        WHERE items.is_inventory=1";

        if($request->delivery_from) {
            $amountstr = $amountstr." AND delivery_date >= '".$request->delivery_from."'";
        }
        if($request->delivery_to) {
            $amountstr = $amountstr." AND delivery_date <= '".$request->delivery_to."'";
        }
        if($request->cust_id) {
            $amountstr = $amountstr." AND people.cust_id LIKE '%".$request->cust_id."%'";
        }
        if($request->company) {
            $amountstr = $amountstr." AND people.company LIKE '%".$request->company."%'";
        }
        if($request->profile_id) {
            $amountstr = $amountstr." AND profiles.id =".$request->profile_id;
        }
        if($request->cust_category) {
            $amountstr = $amountstr." AND custcategories.id =".$request->cust_category;
        }
        if($request->status) {
            if($request->status === 'Delivered') {
                $amountstr .= " AND (transactions.status='Delivered' OR transactions.status='Verified Owe' OR transactions.status='Verified Paid')";
            }else {
                $amountstr .=" AND transactions.status='".$request->status."'";
            }
        }
        $totals = DB::raw("(".$amountstr." GROUP BY item_id) totals");

        $items = DB::table('items')
                        ->leftJoin($totals, 'items.id', '=', 'totals.item_id')
                        ->select(
                                    'items.name AS product_name', 'items.remark', 'items.product_id',
                                    'totals.thisamount AS amount', 'totals.thisqty AS qty'
                                )
                        ->where('items.is_inventory', 1);
        if($request->sortName){
            $items = $items->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }

        if($pageNum == 'All'){
            $items = $items->orderBy('items.product_id')->get();
        }else{
            $items = $items->orderBy('items.product_id')->paginate($pageNum);
        }

        $totals_arr = $this->calItemTotals($items);

        $data = [
            'total_qty' => $totals_arr['total_qty'],
            'total_amount' => $totals_arr['total_amount'],
            'items' => $items,
        ];

        return $data;
    }

    // submit pay summary form (Formrequest $request)
    public function submitPaySummary(Request $request)
    {
        $checkboxes = $request->checkboxes;
        $bankin_dates = $request->bankin_dates;
        $paid_ats = $request->paid_ats;
        $pay_methods = $request->pay_methods;
        $profile_ids = $request->profile_ids;
        $remarks = $request->remarks;
        if($checkboxes) {
            foreach($checkboxes as $index => $checkbox) {
                if($bankin_dates[$index] !== '' or $remarks[$index] !== '') {
                    $exist = Paysummaryinfo::whereDate('paid_at', '=', Carbon::parse($paid_ats[$index])->toDateString())->wherePayMethod($pay_methods[$index])->whereProfileId($profile_ids[$index])->first();
                    // dd($exist->toArray(), Carbon::parse($paid_ats[$index]), $profile_ids[$index], $request->all());
                    if(! $exist) {
                        $paysummaryinfo = new Paysummaryinfo();
                        $paysummaryinfo->paid_at = $paid_ats[$index];
                        $paysummaryinfo->pay_method = $pay_methods[$index];
                        $paysummaryinfo->profile_id = $profile_ids[$index];
                    }else {
                        $paysummaryinfo = $exist;
                    }
                    $paysummaryinfo->remark = $remarks[$index];
                    $paysummaryinfo->bankin_date = $bankin_dates[$index] ? Carbon::parse($bankin_dates[$index]) : null;
                    $paysummaryinfo->user_id = Auth::user()->id;
                    $paysummaryinfo->save();
                }
            }
        }
        return redirect('detailrpt/account');
    }

    // retrieve customers sales summary api(Formrequest $request)
    public function getSalesCustSummaryApi(Request $request)
    {
        // showing total amount init
        $total_amount = 0;
        $input = $request->all();
        // initiate the page num when null given
        $pageNum = $request->pageNum ? $request->pageNum : 100;

        // indicate the month and year
        $carbondate = Carbon::createFromFormat('m-Y', $request->current_month);
        $prevMonth = Carbon::createFromFormat('m-Y', $request->current_month)->subMonth();
        $prev2Months = Carbon::createFromFormat('m-Y', $request->current_month)->subMonths(2);
        $prevYear = Carbon::createFromFormat('m-Y', $request->current_month)->subYear();
        $delivery_from = $carbondate->startOfMonth()->toDateString();
        $delivery_to = $carbondate->endOfMonth()->toDateString();
        $profile_id = $request->profile_id;
        $request->merge(array('delivery_from' => $delivery_from));
        $request->merge(array('delivery_to' => $delivery_to));
        $status = $request->status;
        if($status) {
            if($status == 'Delivered') {
                $statusStr = " AND (transactions.status='Delivered' or transactions.status='Verified Owe' or transactions.status='Verified Paid')";
            }else {
                $statusStr = " AND transactions.status='".$status."'";
            }
        }

        $thistotal_str = "(SELECT people.id AS person_id, ROUND(SUM(deals.amount), 2) AS thistotal,
                            people.profile_id, custcategories.id AS custcategory_id
                            FROM deals
                            LEFT JOIN items ON items.id=deals.item_id
                            LEFT JOIN transactions ON transactions.id=deals.transaction_id
                            LEFT JOIN people ON transactions.person_id=people.id
                            LEFT JOIN profiles ON people.profile_id=profiles.id
                            LEFT JOIN custcategories ON custcategories.id=people.custcategory_id
                            WHERE transactions.delivery_date>='".$delivery_from."'
                            AND transactions.delivery_date<='".$delivery_to."'";

        $prevtotal_str = "(SELECT people.id AS person_id, ROUND(SUM(deals.amount), 2) AS prevtotal,
                            people.profile_id, custcategories.id AS custcategory_id
                            FROM deals
                            LEFT JOIN items ON items.id=deals.item_id
                            LEFT JOIN transactions ON transactions.id=deals.transaction_id
                            LEFT JOIN people ON transactions.person_id=people.id
                            LEFT JOIN profiles ON people.profile_id=profiles.id
                            LEFT JOIN custcategories ON custcategories.id=people.custcategory_id
                            WHERE transactions.delivery_date>='".$prevMonth->startOfMonth()->toDateString()."'
                            AND transactions.delivery_date<='".$prevMonth->endOfMonth()->toDateString()."'";

        $prev2total_str = "(SELECT people.id AS person_id, ROUND(SUM(deals.amount), 2) AS prev2total,
                            people.profile_id, custcategories.id AS custcategory_id
                            FROM deals
                            LEFT JOIN items ON items.id=deals.item_id
                            LEFT JOIN transactions ON transactions.id=deals.transaction_id
                            LEFT JOIN people ON transactions.person_id=people.id
                            LEFT JOIN profiles ON people.profile_id=profiles.id
                            LEFT JOIN custcategories ON custcategories.id=people.custcategory_id
                            WHERE transactions.delivery_date>='".$prev2Months->startOfMonth()->toDateString()."'
                            AND transactions.delivery_date<='".$prev2Months->endOfMonth()->toDateString()."'";

        $prevyeartotal_str = "(SELECT people.id AS person_id, ROUND(SUM(deals.amount), 2) AS prevyeartotal,
                                people.profile_id, custcategories.id AS custcategory_id
                                FROM deals
                                LEFT JOIN items ON items.id=deals.item_id
                                LEFT JOIN transactions ON transactions.id=deals.transaction_id
                                LEFT JOIN people ON transactions.person_id=people.id
                                LEFT JOIN profiles ON people.profile_id=profiles.id
                                LEFT JOIN custcategories ON custcategories.id=people.custcategory_id
                                WHERE transactions.delivery_date>='".$prevYear->startOfMonth()->toDateString()."'
                                AND transactions.delivery_date<='".$prevYear->endOfMonth()->toDateString()."'";

        if($status) {
            $thistotal_str .= $statusStr;
            $prevtotal_str .= $statusStr;
            $prev2total_str .= $statusStr;
            $prevyeartotal_str .= $statusStr;
        }

        if($profile_id) {
            $thistotal_str .= " GROUP BY profiles.id, custcategories.id) thistotal";
            $prevtotal_str .= " GROUP BY profiles.id, custcategories.id) prevtotal";
            $prev2total_str .= " GROUP BY profiles.id, custcategories.id) prev2total";
            $prevyeartotal_str .= " GROUP BY profiles.id, custcategories.id) prevyeartotal";
        }else  {
            $thistotal_str .= " GROUP BY custcategories.id) thistotal";
            $prevtotal_str .= " GROUP BY custcategories.id) prevtotal";
            $prev2total_str .= " GROUP BY custcategories.id) prev2total";
            $prevyeartotal_str .= " GROUP BY custcategories.id) prevyeartotal";
        }

        $thistotal = DB::raw($thistotal_str);
        $prevtotal = DB::raw($prevtotal_str);
        $prev2total = DB::raw($prev2total_str);
        $prevyeartotal = DB::raw($prevyeartotal_str);

        $transactions = DB::table('deals')
                        ->leftJoin('transactions', 'transactions.id', '=', 'deals.transaction_id')
                        ->leftJoin('items', 'items.id', '=', 'deals.item_id')
                        ->leftJoin('people', 'transactions.person_id', '=', 'people.id')
                        ->leftJoin('profiles', 'people.profile_id', '=', 'profiles.id')
                        ->leftJoin('custcategories', 'custcategories.id', '=', 'people.custcategory_id')
                        ->leftJoin($thistotal, function($join) use ($profile_id) {
                            if($profile_id) {
                                $join->on('thistotal.profile_id', '=', 'profiles.id');
                            }
                            $join->on('thistotal.custcategory_id', '=', 'custcategories.id');
                        })
                        ->leftJoin($prevtotal, function($join) use ($profile_id) {
                            if($profile_id) {
                                $join->on('prevtotal.profile_id', '=', 'profiles.id');
                            }
                            $join->on('prevtotal.custcategory_id', '=', 'custcategories.id');
                        })
                        ->leftJoin($prev2total, function($join) use ($profile_id) {
                            if($profile_id) {
                                $join->on('prev2total.profile_id', '=', 'profiles.id');
                            }
                            $join->on('prev2total.custcategory_id', '=', 'custcategories.id');
                        })
                        ->leftJoin($prevyeartotal, function($join) use ($profile_id) {
                            if($profile_id) {
                                $join->on('prevyeartotal.profile_id', '=', 'profiles.id');
                            }
                            $join->on('prevyeartotal.custcategory_id', '=', 'custcategories.id');
                        })
                        ->select(
                                    'people.cust_id', 'people.company', 'people.name', 'people.id as person_id',
                                    'profiles.name as profile_name', 'profiles.id as profile_id', 'profiles.gst',
                                    'transactions.id', 'transactions.status', 'transactions.delivery_date', 'transactions.pay_status', 'transactions.delivery_fee', 'transactions.paid_at', 'transactions.created_at',
                                    'custcategories.name as custcategory',
                                    'thistotal.thistotal AS thistotal', 'prevtotal.prevtotal AS prevtotal', 'prev2total.prev2total AS prev2total', 'prevyeartotal.prevyeartotal AS prevyeartotal'
                                );

        if($request->id or $request->current_month or $request->cust_id or $request->company or $request->delivery_from or $request->delivery_to or $request->profile_id or $request->id_prefix or $request->custcategory or $request->status){
            $transactions = $this->searchTransactionDBFilter($transactions, $request);
        }

        if($profile_id) {
            $transactions = $transactions->orderBy('custcategories.name')->groupBy('custcategories.id', 'profiles.id');
        }else {
            $transactions = $transactions->orderBy('custcategories.name')->groupBy('custcategories.id');
        }

        if($request->sortName){
            $transactions = $transactions->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }

        $total_amount = $this->calTransactionTotalSql($transactions);

        if($pageNum == 'All'){
            $transactions = $transactions->get();
        }else{
            $transactions = $transactions->paginate($pageNum);
        }

        $data = [
            'total_amount' => $total_amount,
            'transactions' => $transactions,
        ];

        return $data;
    }

    // retrieve invoice breakdown detail (Formrequest $request)
    public function getInvoiceBreakdownDetail(Request $request)
    {
        $itemsId = [];
        // $latest3ArrId = [];
        $transactionsId = [];
        $status = $request->status;
        $delivery_from = $request->delivery_from;
        $delivery_to = $request->delivery_to;

        $transactions = Transaction::with(['deals', 'deals.item'])->wherePersonId($request->person_id);
        // $allTransactions = clone $transactions;

        if($status) {
            if($status == 'Delivered') {
                $transactions = $transactions->where(function($query) {
                    $query->where('transactions.status', 'Delivered')->orWhere('transactions.status', 'Verified Owe')->orWhere('transactions.status', 'Verified Paid');
                });
            }else {
                $transactions = $transactions->where('transactions.status', $status);
            }
        }
        // $allTransactions = $allTransactions->latest()->get();

        if($delivery_from){
            $transactions = $transactions->whereDate('transactions.delivery_date', '>=', $delivery_from);
        }
        if($delivery_to){
            $transactions = $transactions->whereDate('transactions.delivery_date', '<=', $delivery_to);
        }

        $transactions = $transactions->orderBy('created_at', 'desc')->get();

        foreach($transactions as $transaction) {
            array_push($transactionsId, $transaction->id);
            foreach($transaction->deals as $deal) {
                array_push($itemsId, $deal->item_id);
            }
        }
        $itemsId = array_unique($itemsId);
        $person_id = $request->person_id ? Person::find($request->person_id)->id : null ;

        if($request->export_excel) {
            $this->exportInvoiceBreakdownExcel($request, $transactionsId, $itemsId, $person_id);
        }

        return view('detailrpt.invbreakdown.detail', compact('request' ,'transactionsId', 'itemsId', 'person_id'));
    }

    // get invoice breakdown page()
    public function getInvoiceBreakdownSummary()
    {
        return view('detailrpt.invbreakdown.summary');
    }

    // retrieve invoice breakdown summary(formrequest request)
    public function getInvoiceBreakdownSummaryApi(Request $request)
    {
        // initiate the page num when null given
        $pageNum = $request->pageNum ? $request->pageNum : 100;
        $delivery_from = $request->delivery_from;
        $delivery_to = $request->delivery_to;
        if($delivery_from and $delivery_to) {
            $date_diff = Carbon::parse($delivery_from)->diffInDays(Carbon::parse($delivery_to));
        }else {
            $date_diff = 1;
        }

        $first_date = DB::raw("(SELECT MIN(DATE(transactions.delivery_date)) AS delivery_date, people.id AS person_id FROM transactions
                                LEFT JOIN people ON people.id=transactions.person_id
                                GROUP BY people.id) AS first_date");
        $paid = DB::raw("(SELECT transactions.total AS total, people.id AS person_id, transactions.id AS transaction_id FROM transactions
                LEFT JOIN people ON people.id=transactions.person_id
                WHERE pay_status='Paid'
                AND transactions.delivery_date>='".$delivery_from."'
                AND transactions.delivery_date<='".$delivery_to."'
                GROUP BY people.id) AS paid");
        $owe = DB::raw("(SELECT transactions.total AS total, people.id AS person_id, transactions.id AS transaction_id FROM transactions
                LEFT JOIN people ON people.id=transactions.person_id
                WHERE pay_status='Owe'
                AND transactions.delivery_date>='".$delivery_from."'
                AND transactions.delivery_date<='".$delivery_to."'
                GROUP BY people.id) AS owe");
        $sales = DB::raw(
                "(SELECT (MAX(transactions.analog_clock) - MIN(transactions.analog_clock)) AS sales_qty,
                ((MAX(transactions.analog_clock) - MIN(transactions.analog_clock))/ DATEDIFF(MAX(transactions.delivery_date), MIN(transactions.delivery_date))) AS sales_avg_day,
                people.id AS person_id,
                transactions.id AS transaction_id
                FROM transactions
                LEFT JOIN people ON people.id=transactions.person_id
                WHERE transactions.delivery_date>='".$delivery_from."'
                AND transactions.delivery_date<='".$delivery_to."'
                GROUP BY people.id) AS sales"
            );
        $latest_data = DB::raw(
                "(SELECT people.id AS person_id, SUBSTRING_INDEX(GROUP_CONCAT(transactions.balance_coin ORDER BY transactions.created_at DESC), ',' ,1) AS balance_coin, SUBSTRING_INDEX(GROUP_CONCAT(transactions.analog_clock ORDER BY transactions.created_at DESC), ',' ,1) AS analog_clock, transactions.created_at FROM transactions
                LEFT JOIN people ON people.id=transactions.person_id
                WHERE transactions.delivery_date>='".$delivery_from."'
                AND transactions.delivery_date<='".$delivery_to."'
                GROUP BY people.id) AS latest_data"
            );
        $oldest_data = DB::raw(
                "(SELECT people.id AS person_id, SUBSTRING_INDEX(GROUP_CONCAT(transactions.analog_clock ORDER BY transactions.created_at ASC), ',' ,1) AS analog_clock, transactions.created_at FROM transactions
                LEFT JOIN people ON people.id=transactions.person_id
                WHERE transactions.delivery_date>='".$delivery_from."'
                AND transactions.delivery_date<='".$delivery_to."'
                GROUP BY people.id) AS oldest_data"
            );
        $total_vending_cash = DB::raw(
                "(SELECT SUM(deals.amount) AS amount, people.id AS person_id FROM deals
                LEFT JOIN transactions ON transactions.id=deals.transaction_id
                LEFT JOIN items ON items.id=deals.item_id
                LEFT JOIN people ON people.id=transactions.person_id
                WHERE items.product_id='051'
                AND transactions.delivery_date>='".$delivery_from."'
                AND transactions.delivery_date<='".$delivery_to."'
                GROUP BY people.id) AS total_vending_cash"
            );
        $total_vending_float = DB::raw(
                "(SELECT SUM(deals.amount) AS amount, people.id AS person_id FROM deals
                LEFT JOIN transactions ON transactions.id=deals.transaction_id
                LEFT JOIN items ON items.id=deals.item_id
                LEFT JOIN people ON people.id=transactions.person_id
                WHERE items.product_id='052'
                AND transactions.delivery_date>='".$delivery_from."'
                AND transactions.delivery_date<='".$delivery_to."'
                GROUP BY people.id) AS total_vending_float"
            );
        $total_stock_value = DB::raw(
                "(SELECT SUM(deals.amount) AS amount, people.id AS person_id FROM deals
                LEFT JOIN transactions ON transactions.id=deals.transaction_id
                LEFT JOIN items ON items.id=deals.item_id
                LEFT JOIN people ON people.id=transactions.person_id
                WHERE items.product_id='051a'
                AND transactions.delivery_date>='".$delivery_from."'
                AND transactions.delivery_date<='".$delivery_to."'
                GROUP BY people.id) AS total_stock_value"
            );


        $deals = DB::table('deals')
                ->leftJoin('transactions', 'transactions.id', '=', 'deals.transaction_id')
                ->leftJoin('people', 'people.id', '=', 'transactions.person_id')
                ->leftJoin('profiles', 'profiles.id', '=', 'people.profile_id')
                ->leftJoin('custcategories', 'custcategories.id', '=', 'people.custcategory_id')
                ->leftJoin($first_date, 'people.id', '=', 'first_date.person_id')
                ->leftJoin($paid, 'people.id', '=', 'paid.person_id')
                ->leftJoin($owe, 'people.id', '=', 'owe.person_id')
                ->leftJoin($sales, 'people.id', '=', 'sales.person_id')
                ->leftjoin($latest_data, 'people.id', '=', 'latest_data.person_id')
                ->leftjoin($oldest_data, 'people.id', '=', 'oldest_data.person_id')
                ->leftjoin($total_vending_cash, 'people.id', '=', 'total_vending_cash.person_id')
                ->leftjoin($total_vending_float, 'people.id', '=', 'total_vending_float.person_id')
                ->leftjoin($total_stock_value, 'people.id', '=', 'total_stock_value.person_id')
                ->select(
                    'people.cust_id AS cust_id', 'people.company AS company',
                    'custcategories.name AS custcategory_name',
                    'first_date.delivery_date AS first_date',
                    DB::raw('ROUND(CASE WHEN profiles.gst=1 THEN ROUND((SUM(deals.amount) * 107/100), 2) ELSE (SUM(deals.amount)) END, 2) AS total'),
                    'profiles.gst AS gst',
                    DB::raw('ROUND((SUM(deals.amount) * 7/100), 2) AS gsttotal'),
                    DB::raw('(SUM(deals.amount)) AS subtotal'),
                    DB::raw('ROUND(SUM(deals.unit_cost * deals.qty), 2) AS cost'),
                    DB::raw('(SUM(deals.amount) - ROUND(SUM(deals.unit_cost * deals.qty), 2)) AS gross_money'),
                    DB::raw('ROUND(CASE WHEN SUM(deals.amount)>0 THEN ((SUM(deals.amount) - ROUND(SUM(deals.unit_cost * deals.qty), 2))/ SUM(deals.amount) * 100) ELSE (SUM(deals.amount) - ROUND(SUM(deals.unit_cost * deals.qty), 2)) END, 2) AS gross_percent'),
                    DB::raw('paid.total AS paid'),
                    DB::raw('owe.total AS owe'),
                    'people.is_vending', 'people.vending_piece_price', 'people.vending_monthly_rental', 'people.vending_profit_sharing',
                    'sales.sales_qty AS sales_qty', 'sales.sales_avg_day AS sales_avg_day',
                    DB::raw('ROUND(((COALESCE(latest_data.balance_coin, 0) + COALESCE(total_vending_cash.amount, 0) + COALESCE(total_vending_float.amount, 0))-((COALESCE(latest_data.analog_clock, 0) - COALESCE(oldest_data.analog_clock, 0)) * COALESCE(people.vending_piece_price, 0))), 2) AS difference'),
                    DB::raw('ROUND((COALESCE(total_stock_value.amount, 0) + COALESCE(latest_data.balance_coin, 0)) + (COALESCE(total_vending_cash.amount, 0) + COALESCE(total_vending_float.amount, 0)), 2) AS vm_stock_value')
                );

        if($request->profile_id or $request->delivery_from or $request->delivery_to or $request->status or $request->cust_id or $request->company or $request->person_id or $request->custcategory) {
            $deals = $this->invoiceBreakdownSummaryFilter($request, $deals);
        }

        $deals = $deals->groupBy('people.id');

        if($request->sortName){
            $deals = $deals->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }else {
            $deals = $deals->orderBy('cust_id');
        }

        $fixedtotals = $this->calInvbreakdownSummaryFixedTotals($deals);

        if($pageNum == 'All'){
            $deals = $deals->get();
        }else{
            $deals = $deals->paginate($pageNum);
        }

        $dynamictotals = $this->calInvbreakdownSummaryDynamicTotals($deals);

        $data = [
            'deals' => $deals,
            'fixedtotals' => $fixedtotals,
            'dynamictotals' => $dynamictotals
        ];

        return $data;
    }

    // show the total this month sales product detail month(int $item_id)
    public function getProductDetailMonthThisMonth($item_id)
    {
        $item = Item::findOrFail($item_id);

        return view('detailrpt.sales.thismonth_total', compact('item'));
    }

    // show the total this month sales product detail month(int $item_id)
    public function getProductDetailMonthThisMonthApi($item_id)
    {
        $item = Item::findOrFail($item_id);
        $monthyear = Carbon::createFromFormat('m-Y', request('current'));

        $transactions = Transaction::with(['person', 'person.profile', 'person.custcategory'])
                        ->whereHas('deals', function($query) use ($item_id) {
                            $query->whereQtyStatus(1)->whereItemId($item_id);
                        })
                        ->whereDate('delivery_date', '>=', $monthyear->startOfMonth())
                        ->whereDate('delivery_date', '<=', $monthyear->endOfMonth());

        if($request->sortName){
            $transactions = $transactions->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }

        $transactions = $transactions->latest()->get();

        $data = [
            'transactions' => $transactions,
            'item' => $item
        ];

        return $data;
    }

    // filter functions for invoice breakdown summary (formrequest request, query deals)
    private function invoiceBreakdownSummaryFilter($request, $deals)
    {
        $profile_id = $request->profile_id;
        $delivery_from = $request->delivery_from;
        $delivery_to = $request->delivery_to;
        $status = $request->status;
        $cust_id = $request->cust_id;
        $company = $request->company;
        $person_id = $request->person_id;
        $custcategory = $request->custcategory;

        if($profile_id) {
            $deals = $deals->where('profiles.id', $profile_id);
        }
        if($delivery_from) {
            $deals = $deals->whereDate('transactions.delivery_date', '>=', $delivery_from);
        }else {
            $deals = $deals->whereDate('transactions.delivery_date', '>=', Carbon::today()->startOfMonth()->toDateString());
        }
        if($delivery_to) {
            $deals = $deals->whereDate('transactions.delivery_date', '<=', $delivery_to);
        }else {
            $deals = $deals->whereDate('transactions.delivery_date', '<=', Carbon::today()->toDateString());
        }
        if($status) {
            if($status === 'Delivered' ) {
                $deals = $deals->where(function($query) {
                    $query->where('transactions.status', 'Delivered')->orWhere('transactions.status', 'Verified Owe')->orWhere('transactions.status', 'Verified Paid');
                });
            }else {
                $deals = $deals->where('transactions.status', $status);
            }
        }
        if($cust_id) {
            $deals = $deals->where('people.cust_id', 'LIKE', '%'.$cust_id.'%');
        }
        if($company) {
            $deals = $deals->where('people.company', 'LIKE', '%'.$company.'%');
        }
        if($person_id) {
            $deals = $deals->where('people.id', '=', $person_id);
        }
        if($custcategory) {
            $deals = $deals->where('custcategories.id', $custcategory);
        }
        return $deals;
    }

    // calculate totals for the invoice breakdown summary(collection $deals)
    private function calInvbreakdownSummaryFixedTotals($deals)
    {
        $grand_total = 0;
        $taxtotal = 0;
        $subtotal = 0;
        $total_gross_money = 0;
        $total_gross_percent = 0;

        foreach($deals->get() as $deal) {
            $grand_total += $deal->total;
            if($deal->gst) {
                $taxtotal += $deal->gsttotal;
            }
            $subtotal += $deal->subtotal;
            $total_gross_money += $deal->gross_money;
            $total_gross_percent += $deal->gross_percent;
        }

        $totals = [
            'grand_total' => $grand_total,
            'taxtotal' => $taxtotal,
            'subtotal' => $subtotal,
            'total_gross_money' => $total_gross_money,
            'total_gross_percent' => $total_gross_percent
        ];

        return $totals;
    }

    // calculate dynamic average and totals for invoice breakdown summary(collection $deals)
    private function calInvbreakdownSummaryDynamicTotals($deals)
    {
        $avg_grand_total = 0;
        $avg_subtotal = 0;
        $avg_cost = 0;
        $avg_gross_money = 0;
        $avg_gross_percent = 0;
        $avg_vending_piece_price = 0;
        $avg_vending_monthly_rental = 0;
        $avg_sales_qty = 0;
        $avg_sales_avg_day = 0;
        $avg_difference = 0;
        $avg_vm_stock_value = 0;

        $total_grand_total = 0;
        $total_gsttotal = 0;
        $total_subtotal = 0;
        $total_cost = 0;
        $total_gross_money = 0;
        $total_gross_percent = 0;
        $total_owe = 0;
        $total_paid = 0;
        $total_vending_monthly_rental = 0;
        $total_sales_qty = 0;
        $total_difference = 0;
        $total_vm_stock_value = 0;
        // placeholder
        $total_vending_piece_price = 0;
        $total_sales_avg_day = 0;

        $dealscount = count($deals);

        foreach($deals as $deal) {
            $total_grand_total += $deal->total;
            $total_subtotal += $deal->subtotal;
            if($deal->gst) {
                $total_gsttotal += $deal->gsttotal;
            }
            $total_cost += $deal->cost;
            $total_gross_money += $deal->gross_money;
            $total_gross_percent += $deal->gross_percent;
            $total_owe += $deal->owe;
            $total_paid += $deal->paid;
            $total_vending_monthly_rental += $deal->vending_monthly_rental;
            $total_sales_qty += $deal->sales_qty;
            $total_difference += $deal->difference;
            $total_vm_stock_value += $deal->vm_stock_value;
            $total_vending_piece_price += $deal->vending_piece_price;
            $total_sales_avg_day += $deal->sales_avg_day;
        }

        if($dealscount > 0) {
            $avg_grand_total = $total_grand_total / $dealscount;
            $avg_subtotal = $total_subtotal / $dealscount;
            $avg_cost = $total_cost / $dealscount;
            $avg_gross_money = $total_gross_money / $dealscount;
            $avg_gross_percent = $total_gross_percent / $dealscount;
            $avg_vending_piece_price = $total_vending_piece_price / $dealscount;
            $avg_vending_monthly_rental = $total_vending_monthly_rental / $dealscount;
            $avg_sales_qty = $total_sales_qty / $dealscount;
            $avg_sales_avg_day = $total_sales_avg_day / $dealscount;
            $avg_difference = $total_difference / $dealscount;
            $avg_vm_stock_value = $total_vm_stock_value / $dealscount;
        }

        $totals = [
            'avg_grand_total' => $avg_grand_total,
            'avg_subtotal' => $avg_subtotal,
            'avg_cost' => $avg_cost,
            'avg_gross_money' => $avg_gross_money,
            'avg_gross_percent' => $avg_gross_percent,
            'avg_vending_piece_price' => $avg_vending_piece_price,
            'avg_vending_monthly_rental' => $avg_vending_monthly_rental,
            'avg_sales_qty' => $avg_sales_qty,
            'avg_sales_avg_day' => $avg_sales_avg_day,
            'avg_difference' => $avg_difference,
            'avg_vm_stock_value' => $avg_vm_stock_value,

            'total_grand_total' => $total_grand_total,
            'total_subtotal' => $total_subtotal,
            'total_gsttotal' => $total_gsttotal,
            'total_cost' => $total_cost,
            'total_gross_money' => $total_gross_money,
            'total_gross_percent' => $total_gross_percent,
            'total_owe' => $total_owe,
            'total_paid' => $total_paid,
            'total_vending_monthly_rental' => $total_vending_monthly_rental,
            'total_sales_qty' => $total_sales_qty,
            'total_difference' => $total_difference,
            'total_vm_stock_value' => $total_vm_stock_value
        ];

        return $totals;
    }

    // export SOA report(Array $data)
    private function convertSoaExcel($transactions, $total)
    {
        $soa_query = clone $transactions;
        $data = $soa_query->orderBy('people.cust_id')->orderBy('transactions.id')->get();
        $title = 'Account SOA';

        Excel::create($title.'_'.Carbon::now()->format('dmYHis'), function($excel) use ($data, $total) {
            $excel->sheet('sheet1', function($sheet) use ($data, $total) {
                $sheet->setAutoSize(true);
                $sheet->setColumnFormat(array(
                    'A:D' => '@',
                    'E' => '0.00'
                ));
                $sheet->loadView('detailrpt.account.custdetail_soa_excel', compact('data', 'total'));
            });
        })->download('xls');
    }

    // export account cust detail excel(Collection $transactions, float $total)
    private function convertAccountCustdetailExcel($transactions, $total)
    {
        $data = $transactions;
        $title = 'Cust Detail (Account)';
        Excel::create($title.'_'.Carbon::now()->format('dmYHis'), function($excel) use ($data, $total) {
            $excel->sheet('sheet1', function($sheet) use ($data, $total) {
                $sheet->setAutoSize(true);
                $sheet->setColumnFormat(array(
                    'A:F' => '@',
                    'G' => '0.00'
                ));
                $sheet->loadView('detailrpt.account.custdetail_excel', compact('data', 'total'));
            });
        })->download('xls');
    }

    // conditional filter parser(Collection $query, Formrequest $request)
    private function searchTransactionDBFilter($transactions, $request)
    {
        $profile_id = $request->profile_id;
        $delivery_from = $request->delivery_from;
        $payment_from = $request->payment_from;
        $cust_id = $request->cust_id;
        $delivery_to = $request->delivery_to;
        $payment_to = $request->payment_to;
        $company = $request->company;
        $status = $request->status;
        $person_id = $request->person_id;
        $payment = $request->payment;
        $pay_method = $request->pay_method;
        $id_prefix = $request->id_prefix;
        $custcategory = $request->custcategory;
        $bankin_from = $request->bankin_from;
        $bankin_to = $request->bankin_to;

        if($profile_id){
            $transactions = $transactions->where('profiles.id', $profile_id);
        }
        if($delivery_from){
            $transactions = $transactions->whereDate('transactions.delivery_date', '>=', $delivery_from);
        }
        if($payment_from){
            $transactions = $transactions->whereDate('transactions.paid_at', '>=', $payment_from);
        }
        if($cust_id){
            $transactions = $transactions->where('people.cust_id', 'LIKE', '%'.$cust_id.'%');
        }
        if($delivery_to){
            $transactions = $transactions->whereDate('transactions.delivery_date', '<=', $delivery_to);
        }
        if($payment_to){
            $transactions = $transactions->whereDate('transactions.paid_at', '<=', $payment_to);
        }
        if($status) {
            if($status == 'Delivered') {
                $transactions = $transactions->where(function($query) {
                    $query->where('transactions.status', 'Delivered')->orWhere('transactions.status', 'Verified Owe')->orWhere('transactions.status', 'Verified Paid');
                });
            }else {
                $transactions = $transactions->where('transactions.status', $status);
            }
        }
        if($company) {
            $transactions = $transactions->where(function($query) use ($company){
                $query->where('people.company', 'LIKE', '%'.$company.'%')
                        ->orWhere(function ($query) use ($company){
                            $query->where('people.cust_id', 'LIKE', 'D%')
                                    ->where('people.name', 'LIKE', '%'.$company.'%');
                        });
                });
        }
        if($person_id) {
            $transactions = $transactions->where('people.id', $person_id);
        }
        if($payment) {
            $transactions = $transactions->where('transactions.pay_status', $payment);
        }
        if($pay_method) {
            $transactions = $transactions->where('transactions.pay_method', $pay_method);
        }
        if($id_prefix) {
            $transactions = $transactions->where('people.cust_id', 'LIKE', $id_prefix.'%');
        }
        if($custcategory) {
            $transactions = $transactions->where('custcategories.id', $custcategory);
        }
        if($bankin_from) {
            $transactions = $transactions->whereDate('paysummaryinfos.bankin_date', '>=', $bankin_from);
        }
        if($bankin_to) {
            $transactions = $transactions->whereDate('paysummaryinfos.bankin_date', '<=', $bankin_to);
        }
        if($request->sortName){
            $transactions = $transactions->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }
        return $transactions;
    }

    // calculating gst and non for delivered total
    private function calDBTransactionTotal($query)
    {
        $total_amount = 0;
        $nonGst_amount = 0;
        $gst_amount = 0;
        $query1 = clone $query;
        $query2 = clone $query;

        $nonGst_amount = $query1->where('profiles.gst', 0)->sum(DB::raw('ROUND(transactions.total, 2)'));
        $gst_amount = $query2->where('profiles.gst', 1)->sum(DB::raw('ROUND((transactions.total * 107/100), 2)'));

        $total_amount = $nonGst_amount + $gst_amount;

        return $total_amount;
    }

    // calculate original total
    private function calDBOriginalTotal($query)
    {
        $total_amount = 0;
        $query1 = clone $query;
        $total_amount = $query1->sum(DB::raw('ROUND(CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN transactions.total*107/100 + transactions.delivery_fee ELSE transactions.total*107/100 END) ELSE (CASE WHEN transactions.delivery_fee>0 THEN transactions.total + transactions.delivery_fee ELSE transactions.total END) END, 2)'));
        return $total_amount;
    }

    // calculate delivery fees total
    private function calDBDeliveryTotal($query)
    {
        $query3 = clone $query;
        $delivery_fee = $query3->sum(DB::raw('ROUND(transactions.delivery_fee, 2)'));
        return $delivery_fee;
    }

    // calculate total when sql done the filter job
    private function calTransactionTotalSql($query)
    {
        $total_amount = 0;
        $query1 = clone $query;
        $totals = $query1->get();
        foreach($totals as $total) {
            $total_amount += $total->thistotal;
        }
        return $total_amount;
    }

    // calculate account cust outstanding total
    private function calCustoutstandingTotal($query)
    {
        $total_amount = 0;
        $query1 = clone $query;
        $totals = $query1->get();
        foreach($totals as $total) {
            $total_amount += $total->thistotal;
        }
        return $total_amount;
    }

    // cal independent total for inv_total gst and amount
    private function calPayDetailTotal($query)
    {
        $query1 = clone $query;
        $query2 = clone $query;
        $query3 = clone $query;

        $total_inv_amount = $query1->sum(DB::raw('(CASE WHEN transactions.delivery_fee>0 THEN (transactions.total + transactions.delivery_fee) ELSE transactions.total END)'));
        $total_gst = $query2->sum(DB::raw('(CASE WHEN profiles.gst=1 THEN (transactions.total * 7/100) ELSE 0 END)'));
        $total_amount = $query3->sum(DB::raw('(CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END)'));

        $caldata = [
            'total_inv_amount' => $total_inv_amount,
            'total_gst' => $total_gst,
            'total_amount' => $total_amount,
        ];

        return $caldata;
    }

    // conditional filter items parser(Collection $query, Formrequest $request)
    private function searchItemDBFilter($items, $request)
    {
        $product_id = $request->product_id;
        $product_name = $request->product_name;
        $profile_id = $request->profile_id;
        $status = $request->status;

        if($product_id) {
            $items = $items->where('items.product_id', 'LIKE', '%'.$product_id.'%');
        }
        if($product_name) {
            $items = $items->where('items.name', 'LIKE', '%'.$product_name.'%');
        }
        if($profile_id) {
            $items = $items->where('profiles.id', $profile_id);
        }
        if($status) {
            if($status === 'Delivered') {
                $items = $items->where(function($query) {
                    $query->where('transactions.status', 'Delivered')->orWhere('transactions.status', 'Verified Owe')->orWhere('transactions.status', 'Verified Paid');
                });
            }else {
                $items = $items->where('transactions.status', $status);
            }
        }
        if($request->sortName){
            $items = $items->orderBy($request->sortName, $request->sortBy ? 'asc' : 'desc');
        }
        return $items;
    }

    // retrieve total amount and qty for the items product detail daily(Collection $items)
    private function calItemTotals($query)
    {
        $query_total = clone $query;
        $query_qty = clone $query;

        $total_amount = $query_total->sum('amount');
        $total_qty = $query_qty->sum('qty');
        $totals = [
            'total_amount' => $total_amount,
            'total_qty' => $total_qty
        ];

        return $totals;
    }

    // calculate all the totals for pay summary detailed rpt (query $transactions)
    private function calAccPaySummary($transactions)
    {
        $cash_happyice = clone $transactions;
        $cheque_happyice = clone $transactions;
        $tt_happyice = clone $transactions;
        $cash_logistic = clone $transactions;
        $cheque_logistic = clone $transactions;
        $tt_logistic = clone $transactions;
        $cash_all = clone $transactions;
        $cheque_all = clone $transactions;
        $tt_all = clone $transactions;
        $total_cash_happyice = 0;
        $total_cheque_happyice = 0;
        $total_tt_happyice = 0;
        $total_cash_logistic = 0;
        $total_cheque_logistic = 0;
        $total_tt_logistic = 0;
        $total_cash_all = 0;
        $total_cheque_all = 0;
        $total_tt_all = 0;

        $total_cash_happyice = $cash_happyice->where('profiles.name', '=', 'HAPPY ICE PTE LTD')->where('transactions.pay_method', '=', 'cash')->sum(DB::raw('ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)'));
        $total_cheque_happyice = $cheque_happyice->where('profiles.name', '=', 'HAPPY ICE PTE LTD')->where('transactions.pay_method', '=', 'cheque')->sum(DB::raw('ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)'));
        $total_tt_happyice = $tt_happyice->where('profiles.name', '=', 'HAPPY ICE PTE LTD')->where('transactions.pay_method', '=', 'tt')->sum(DB::raw('ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)'));
        $total_cash_logistic = $cash_logistic->where('profiles.name', '=', 'HAPPY ICE LOGISTIC PTE LTD')->where('transactions.pay_method', '=', 'cash')->sum(DB::raw('ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)'));
        $total_cheque_logistic = $cheque_logistic->where('profiles.name', '=', 'HAPPY ICE LOGISTIC PTE LTD')->where('transactions.pay_method', '=', 'cheque')->sum(DB::raw('ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)'));
        $total_tt_logistic = $tt_logistic->where('profiles.name', '=', 'HAPPY ICE LOGISTIC PTE LTD')->where('transactions.pay_method', '=', 'tt')->sum(DB::raw('ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)'));
        $total_cash_all = $cash_all->where('transactions.pay_method', '=', 'cash')->sum(DB::raw('ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)'));
        $total_cheque_all = $cheque_all->where('transactions.pay_method', '=', 'cheque')->sum(DB::raw('ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)'));
        $total_tt_all = $tt_all->where('transactions.pay_method', '=', 'tt')->sum(DB::raw('ROUND((CASE WHEN profiles.gst=1 THEN (CASE WHEN transactions.delivery_fee>0 THEN (transactions.total * 107/100 + transactions.delivery_fee) ELSE (transactions.total * 107/100) END) ELSE transactions.total END), 2)'));

        $data = [
           'total_cash_happyice' =>  $total_cash_happyice,
           'total_cheque_happyice' => $total_cheque_happyice,
           'total_tt_happyice' => $total_tt_happyice,
           'total_cash_logistic' => $total_cash_logistic,
           'total_cheque_logistic' => $total_cheque_logistic,
           'total_tt_logistic' => $total_tt_logistic,
           'total_cash_all' => $total_cash_all,
           'total_cheque_all' => $total_cheque_all,
           'total_tt_all' => $total_tt_all
        ];

        return $data;
    }

    // calculate sales product detail months total(query $items)
    private function calSalesProductDetailMonthTotals($items)
    {
        $total_amount = 0;
        $total_qty = 0;
        foreach($items as $item) {
            $total_amount += $item->amount;
        }
        foreach($items as $item) {
            if($item->is_inventory === 1) {
                $total_qty += $item->qty;
            }

        }
        return $data = [
            'total_amount' => $total_amount,
            'total_qty' => $total_qty
        ];
    }

    // generate month options for a past year from this month()
    private function getMonthOptions()
    {
        // past year till now months option
        $month_options = array();
        $oneyear_ago = Carbon::today()->subYears(3);
        $diffmonths = Carbon::today()->diffInMonths($oneyear_ago);
        $month_options[$oneyear_ago->month.'-'.$oneyear_ago->year] = Month::findOrFail($oneyear_ago->month)->name.' '.$oneyear_ago->year;
        for($i=1; $i<=$diffmonths; $i++) {
            $oneyear_ago = $oneyear_ago->addMonth();
            $month_options[$oneyear_ago->month.'-'.$oneyear_ago->year] = Month::findOrFail($oneyear_ago->month)->name.' '.$oneyear_ago->year;
        }
        return $month_options;
    }

    // export excel for account pay summary (Collection $data)
    private function paySummaryExportExcel($data)
    {
        $title = 'Payment Summary(Account)';
        Excel::create($title.'_'.Carbon::now()->format('dmYHis'), function($excel) use ($data) {
            $excel->sheet('sheet1', function($sheet) use ($data) {
                $sheet->setColumnFormat(array('A:P' => '@'));
                $sheet->getPageSetup()->setPaperSize('A4');
                $sheet->loadView('detailrpt.account.paymentsummary_excel', compact('data'));
            });
        })->download('xlsx');
    }

    // search sales invoice breakdown db scope(Query $transactions, Formrequest $request)
    private function searchSalesInvoiceBreakdown($transactions, $request)
    {
        $status = $request->status;
        $delivery_from = $request->delivery_from;
        $delivery_to = $request->delivery_to;

        if($status) {
            if($status == 'Delivered') {
                $transactions = $transactions->where(function($query) {
                    $query->where('transactions.status', 'Delivered')->orWhere('transactions.status', 'Verified Owe')->orWhere('transactions.status', 'Verified Paid');
                });
            }else {
                $transactions = $transactions->where('transactions.status', $status);
            }
        }

        if($delivery_from){
            $transactions = $transactions->whereDate('transactions.delivery_date', '>=', $delivery_from);
        }else {
            $transactions = $transactions->whereDate('transactions.delivery_date', '>=', Carbon::today()->subMonth()->startOfMonth()->toDateString());
        }
        if($delivery_to){
            $transactions = $transactions->whereDate('transactions.delivery_date', '<=', $delivery_to);
        }else {
            $transactions = $transactions->whereDate('transactions.delivery_date', '<=', Carbon::today()->endOfMonth()->toDateString());
        }
        return $transactions;
    }

    // export excel for invoice breakdown (Formrequest $request, Array $transactionsId, Array itemsId, int person_id)
    private function exportInvoiceBreakdownExcel($request, $transactionsId, $itemsId, $person_id)
    {
        $person = Person::findOrFail($person_id);
        $title = 'Invoice Breakdown ('.$person->cust_id.')';
        Excel::create($title.'_'.Carbon::now()->format('dmYHis'), function($excel) use ($request, $transactionsId, $itemsId, $person_id) {
            $excel->sheet('sheet1', function($sheet) use ($request, $transactionsId, $itemsId, $person_id) {
                $sheet->setColumnFormat(array('A:P' => '@'));
                $sheet->getPageSetup()->setPaperSize('A4');
                $sheet->setAutoSize(true);
                $sheet->loadView('detailrpt.invoicebreakdown_excel', compact('request', 'transactionsId', 'itemsId', 'person_id'));
            });
        })->download('xlsx');
    }
}
