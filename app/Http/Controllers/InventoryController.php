<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Inventory;
use App\InvRecord;
use Auth;
use App\Item;
use Laracasts\Flash\Flash;
use Session;
use App\EmailAlert;

class InventoryController extends Controller
{

    //auth-only login can see
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function getData()
    {
        $inventories =  Inventory::latest()->get();

        return $inventories;
    }

    public function itemInventory($inventory_id)
    {
        $inventory = InvRecord::where('inventory_id', $inventory_id)
                    ->with(['item' => function($query) {
                            $query->withoutGlobalScopes();
                        }])
                    ->get();

        return $inventory;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('inventory.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // creation record
        $request->merge(array('creator_id' => Auth::user()->id));

        $request->merge(array('created_by' => Auth::user()->name));



        // retrive stock in array
        $currentQty = $request->current;

        $incomingQty = $request->incoming;
        // dd($request->all());
        // dd($currentQty);
        // dd(array_filter($incomingQty));

        $afterQty = $request->after;

        // validation for incoming must fill in batch number
        if($request->type == 'Incoming'){

            $this->validate($request, [

                'batch_num' => 'required',

            ]);
        }

        $input = $request->all();

        $inventory = Inventory::create($input);

        if(array_filter($incomingQty)){

            // if($this->createInvRecord($inventory->id, $currentQty, $incomingQty, $afterQty)){

                // store all the history total into inventory
                // qtytotal_current used to record original entry
                $inventory->qtytotal_current = array_sum($currentQty);

                $inventory->qtytotal_incoming = array_sum($incomingQty);

                $inventory->qtytotal_after = array_sum($afterQty);

                $inventory->save();

                Flash::success('Entries added');

                return redirect('item');

            // }else{

                // Flash::error('Item Current Qty must not become negative');

                // return Redirect::action('InventoryController@edit', $inventory->id);

            // }

        }else{

            Flash::error('Please fill up the form');

            return view('inventory.create');

        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $inventory = Inventory::findOrFail($id);

        $invrecs = InvRecord::where('inventory_id', $inventory->id)
                    ->with(['item' => function($query) {
                            $query->withoutGlobalScopes();
                        }])
                    ->get();

        return view('inventory.edit', compact('inventory', 'invrecs'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
/*
        $originalArr = $request->original;

        $incomingArr = $request->incoming;

        $afterArr = $request->after;*/

        $inventory = Inventory::findOrFail($id);
/*
        if(($inventory->qtytotal_incoming != $request->total_incoming)){

            $incomingDiff = 0.0000;

            foreach($incomingArr as $index => $incoming){

                // update the invrecords accordingly
                $invrec = InvRecord::where('inventory_id', $request->inventory_id)->where('item_id', $index)->first();

                if($invrec){

                    // update the item now and last inventory
                    $item = Item::findOrFail($invrec->item_id);

                    if($incoming == $invrec->qtyrec_incoming){

                        $incomingDiff = 0;

                    }else{

                        $incomingDiff = $incoming - $originalArr[$index];

                    }

                    // prevent the stock qty is deduct to negative
                    if($item->qty_now + $incomingDiff < 0){

                        Flash::error('The product '.$item->product_id.' has been deducted to less than zero');

                        return Redirect::action('InventoryController@edit', $inventory->id);

                    }else{

                        $invrec->qtyrec_incoming = $incoming;

                        $invrec->qtyrec_after = $afterArr[$index];

                        $invrec->save();

                        $item->qty_now = $item->qty_now + $incomingDiff;

                        $item->save();

                        Flash::success('The changes has been saved');

                    }

                }else{

                    if($incoming != NULL or $incoming != 0){

                        $item = Item::findOrFail($index);

                        if($item->qty_now + $incoming < 0){

                            Flash::error('The product '.$item->product_id.' has been deducted to less than zero');

                            return Redirect::action('InventoryController@edit', $inventory->id);

                        }else{

                            $invrec_new = new InvRecord;

                            $invrec_new->qtyrec_incoming = $incoming;

                            $invrec_new->qtyrec_after = $afterArr[$index];

                            $invrec_new->item_id = $item->id;

                            $invrec_new->inventory_id = $inventory->id;

                            $invrec_new->save();

                            $item->qty_now = $item->qty_now + $incoming;

                            $item->save();

                            Flash::success('The changes has been saved');

                        }

                    }
                }
            }

            $inventory->qtytotal_incoming = array_sum($incomingArr);

            $inventory->qtytotal_after = array_sum($afterArr);

            $inventory->save();

        }*/

        $request->merge(array('updated_by' => Auth::user()->name));

        $inventory->update($request->all());

        return Redirect::action('InventoryController@edit', $inventory->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return json
     */
    public function destroy($id)
    {
        $inventory = Inventory::findOrFail($id);

        $invrecs = InvRecord::where('inventory_id', $inventory->id)->get();

        foreach($invrecs as $invrec){

            $item = Item::findOrFail($invrec->item_id);

            $item->qty_last = $item->qty_now;

            $item->qty_now = $item->qty_now - $invrec->qtyrec_incoming;

            $item->save();

            $invrec->delete();
        }

        $inventory->delete();

        return redirect('item');
    }

    // inventory setting pages
    public function invIndex()
    {
        return view('inventory.setting.index');
    }

    public function invLowest(Request $request)
    {
        $lowestArr = $request->lowest;

        foreach($lowestArr as $index => $lowest){

            $item = Item::findOrFail($index);

            if($lowest != NULL and is_numeric($lowest)){

                $item->lowest_limit = $lowest;

            }else{

                $item->lowest_limit = 0.0000;
            }

            $item->save();
        }

        Flash::success('Entries saved');

        return view('inventory.setting.index');
    }

    // email alert inventories
    public function invEmail()
    {
        return view('inventory.setting.email_alert');
    }

    public function invEmailUpdate(Request $request)
    {
        $lowestArr = $request->lowest;

        foreach($lowestArr as $index => $lowest){

            $item = Item::findOrFail($index);

            if($lowest != NULL and is_numeric($lowest)){

                $item->email_limit = $lowest;

            }else{

                $item->email_limit = 0.0000;
            }

            $item->save();
        }

        $this->syncEmail($request);

        Flash::success('Entries saved');

        return view('inventory.setting.email_alert');
    }

    // individual line record for each of the inventory incoming/ decution
    private function createInvRecord($inv_id, array $currentQty, array $incomingQty, array $afterQty)
    {
        foreach(array_filter($incomingQty) as $index => $qty){

            if($qty != NULL or $qty != 0 ){

                $rec = new InvRecord();

                $item = Item::findOrFail($index);

                $rec->inventory_id = $inv_id;

                $rec->item_id = $index;

                // qtyrec_current used to record the original qty
                $rec->qtyrec_current = $currentQty[$index];

                $rec->qtyrec_incoming = $qty;

                $rec->qtyrec_after = $afterQty[$index];

                // check whether the summation smaller than zero or not
                if($currentQty[$index] + $incomingQty[$index] < 0){

                    return false;

                }else{

                    $rec->save();

                    $item->qty_last = $currentQty[$index];

                    $item->qty_now = $currentQty[$index] + $incomingQty[$index];

                    $item->save();

                }
            }

        }
        return true;
    }


    private function syncEmail($request)
    {

        $emails = EmailAlert::all();

        foreach($emails as $email){

            $email->status = 'inactive';

            $email->save();

        }

        if($request->email_notification){

            foreach($request->email_notification as $itemId)
            {
                if (substr($itemId, 0, 4) == 'new:')
                {
                    $email = EmailAlert::create(['email'=>substr($itemId, 4)]);

                    $email->status = 'active';

                    $email->save();

                    continue;

                }else{

                    $email = EmailAlert::findOrFail($itemId);

                    $email->status = 'active';

                    $email->save();

                }

            }
        }
    }
}
