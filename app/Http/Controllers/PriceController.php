<?php

namespace App\Http\Controllers;

use App\Http\Requests\PriceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Person;
use App\Price;
use App\Fprice;
use App\Item;
use Laracasts\Flash\Flash;
use DB;

class PriceController extends Controller
{
    //auth-only login can see
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    // 10-Feb now fully depends only on store
    public function store(PriceRequest $request)
    {

        $person_id = $request->input('person_id');
        $retail_price = $request->retail;
        $quote_price = $request->quote;

        foreach ($quote_price as $index => $quote) {
            if ($quote != 0 and $quote != null) {
                if (auth()->user()->hasRole('franchisee')) {
                    $price = Fprice::wherePersonId($person_id)->whereItemId($index)->first();
                } else {
                    $price = Price::wherePersonId($person_id)->whereItemId($index)->first();
                }

                if ($price) {
                    $price->retail_price = $retail_price[$index];
                    $price->quote_price = $quote_price[$index];
                    $price->save();
                } else {
                    if (auth()->user()->hasRole('franchisee')) {
                        $price = new Fprice();
                    } else {
                        $price = new Price();
                    }
                    $price->retail_price = $retail_price[$index];
                    $price->quote_price = $quote_price[$index];
                    $price->person_id = $person_id;
                    $price->item_id = $index;
                    $price->save();
                }
            } else {
                if (auth()->user()->hasRole('franchisee')) {
                    $price = Fprice::wherePersonId($person_id)->whereItemId($index)->first();
                } else {
                    $price = Price::wherePersonId($person_id)->whereItemId($index)->first();
                }

                if ($retail_price[$index] == 0 or $retail_price[$index] == null) {
                    if ($price) {
                        $price->delete();
                    }
                } else {
                    $price->retail_price = $retail_price[$index];
                    $price->quote_price = $quote_price[$index];
                    $price->person_id = $person_id;
                    $price->item_id = $index;
                    $price->save();
                }
            }
        }
/*
        $retail_price = $request->input('retail_price');

        if(! $request->has('quote_price')){
            $request->merge(array('quote_price' => $this->calquote($person_id, $retail_price)));
        }

        $input = $request->all();
        $price = Price::create($input);
         */

        return Redirect::action('PersonController@edit', $person_id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $person = Person::findOrFail($id);

        return view('person.price.create', compact('person'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $price = Price::findOrFail($id);

        return view('person.price.edit', compact('price'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PriceRequest $request, $id)
    {
        $price = Price::findOrFail($id);
        $retail_price = $request->input('retail_price');

        if (!$request->has('quote_price')) {
            $request->merge(array('quote_price' => $this->calquote($price->person_id, $retail_price)));
        }

        $input = $request->all();
        $price->update($input);

        return Redirect::action('PersonController@edit', $price->person_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $price = Price::findOrFail($id);
        $person_id = $price->person_id;
        $price->delete();

        return Redirect::action('PersonController@edit', $person_id);
    }

    // return prices for specific item and person (int $item_id, int $person_id)
    public function lookupPrices($item_id, $person_id)
    {
        $prices = Price::where('item_id', $item_id)->where('person_id', $person_id)->get();

        return $prices;
    }

    /*
    // return items horizontal th for price matrix
    public function getPriceMatrixItems()
    {
        $items = $this->filterPriceMatrixItems();

        return $items;
    }

    // return items horizontal th for price matrix
    public function getPriceMatrixPeople()
    {
        $people = $this->filterPriceMatrixPeople();

        return $people;
    }*/

    // return items horizontal th for price matrix index
    public function getPriceMatrixIndex()
    {
        return view('person.price_matrix');
    }


    // return items horizontal th for price matrix
    public function getPriceMatrixIndexApi()
    {
        $people = DB::table('people')
            ->select(
                'people.id',
                'people.cust_id',
                'people.custcategory_id',
                'people.cost_rate'
            );

        $people = $this->filterPriceMatrixPeople();

        $people = $people->orderBy('cust_id')->get();

        $items = DB::table('items')
            ->select(
                'items.id',
                'items.product_id',
                'items.name',
                'items.itemcategory_id'
            );

        $items = $this->filterPriceMatrixItems();

        $items = $items->orderBy('product_id')->get();

        $prices = array();

        foreach ($people as $index1 => $person) {
            foreach ($items as $index2 => $item) {
                $price = DB::table('prices')
                    ->where('person_id', $person->id)
                    ->where('item_id', $item->id)
                    ->first();

                $prices[$index1][$index2] = [
                    'person_id' => $person->id,
                    'price_id' => $price ? $price->id : '',
                    'item_id' => $item->id,
                    'retail_price' => $price ? $price->retail_price : '',
                    'quote_price' => $price ? $price->quote_price : ''
                ];
            }
        }

        $data = [
            'people' => $people,
            'items' => $items,
            'prices' => $prices
        ];

        return $data;
    }

    // edit and update the price matrix
    public function editPriceMatrixApi()
    {
        $person_id = request('person_id');
        $item_id = request('item_id');
        $retail_price = request('retail_price');
        $quote_price = request('quote_price');
        $price_id = request('price_id');

        $price = Price::find($price_id);

        if ($price) {
            if ($retail_price and $quote_price) {
                $price->update([
                    'retail_price' => $retail_price,
                    'quote_price' => $quote_price
                ]);
            } else {
                $price->delete();
            }
        } else {
            Price::create([
                'person_id' => $person_id,
                'item_id' => $item_id,
                'retail_price' => $retail_price,
                'quote_price' => $quote_price,
            ]);
        }
    }

    // update the person costrate
    public function editCostrateApi()
    {
        $person_id = request('id');
        $cost_rate = request('cost_rate');
        $person = Person::find($person_id);

        if ($person) {
            $person->update([
                'cost_rate' => $cost_rate
            ]);
        }
    }

    // processing batch confirm for price matrix()
    public function batchConfirmPriceMatrix()
    {
        $checkboxes = request('checkbox');
        $costrates = request('cost_rate');
        $retailprices = request('retail_price');
        $quoteprices = request('quote_price');

        if ($checkboxes) {
            foreach ($checkboxes as $index => $checkbox) {
                $person = Person::findOrFail($index);
                $person->cost_rate = $costrates[$index];
                $person->save();

                foreach ($retailprices as $retailindex => $retailprice) {
                    if (explode('-', $retailindex)[1] == $index) {
                        $price = Price::where('person_id', $index)->where('item_id', explode('-', $retailindex)[0])->first();
                        if ($price) {
                            if (($retailprice != 0.00 and $retailprice != '') or ($quoteprices[$retailindex] != 0.00 and $quoteprices[$retailindex] != '')) {
                                $price->retail_price = $retailprice;
                                $price->quote_price = $quoteprices[$retailindex];
                                $price->save();
                            } else {
                                $price->delete();
                            }
                        } else {
                            if (($retailprice != 0.00 and $retailprice != '') or ($quoteprices[$retailindex] != 0.00 and $quoteprices[$retailindex] != '')) {
                                $price = new Price();
                                $price->person_id = $person->id;
                                $price->item_id = explode('-', $retailindex)[0];
                                $price->retail_price = $retailprice;
                                $price->quote_price = $quoteprices[$retailindex];
                                $price->save();
                            }
                        }
                        // dd($price->quote_price, $retailprice, $quoteprices[$retailindex]);

                    }
                }
            }

        } else {
            Flash::error('Please select at least one checkbox');
        }
        return redirect()->action('PriceController@getPriceMatrix');
    }

    // return price list based on person id
    public function getPersonPricesApi()
    {
        $person_id = request('person_id');

        $prices = DB::table('prices')
            ->leftJoin('items', 'items.id', '=', 'prices.item_id')
            ->leftJoin('people', 'people.id', '=', 'prices.person_id')
            ->select(
                'prices.id',
                'prices.retail_price',
                'prices.quote_price',
                'items.product_id',
                'items.name',
                'items.remark',
                'items.base_unit',
                'items.is_inventory',
                'items.id AS item_id'
            )
            ->where('people.id', $person_id)
            ->where('items.is_active', 1)
            ->orderBy('items.product_id', 'asc')
            ->get();
/*
        $person = Person::findOrFail($person_id);


        $personprice = DB::raw(
            "(
                            SELECT prices.retail_price, prices.quote_price, prices.item_id, people.cost_rate FROM prices
                            LEFT JOIN people ON people.id = prices.person_id
                            WHERE people.id = " . $person_id . "
                            ) personprice"
        );

        if (auth()->user()->hasRole('franchisee')) {
            $personprice = DB::raw(
                "(
                                SELECT fprices.retail_price, fprices.quote_price, fprices.item_id, people.cost_rate FROM fprices
                                LEFT JOIN people ON people.id = fprices.person_id
                                WHERE people.id = " . $person_id . "
                                ) personprice"
            );
        } */
/*
        $items = DB::table('items')
            ->leftJoin($personprice, 'personprice.item_id', '=', 'items.id')
            ->select(
                'items.id AS item_id',
                'items.product_id',
                'items.name',
                'items.remark',
                'items.base_unit',
                'personprice.retail_price',
                'personprice.quote_price',
                'personprice.cost_rate'
            );

        $items = $items->where('items.is_active', 1)
            ->orderBy('items.product_id', 'asc')
            ->get(); */

        return $prices;

    }

    // return price matrix items filter api()
    private function filterPriceMatrixItems()
    {
        $product_id = request('product_id');
        $name = request('name');
        $is_inventory = request('is_inventory');

        $items = new Item();

        if($product_id) {
            $items = $items->where('items.product_id', 'LIKE', '%' . $product_id . '%');
        }
        if($name) {
            $items = $items->where('items.name', 'LIKE', '%' . $name . '%');
        }

        if($is_inventory) {
            $items = $items->where('items.is_inventory', $is_inventory);
        }

        return $items;
    }

    // return price matrix customers filter api()
    private function filterPriceMatrixPeople()
    {
        $cust_id = request('cust_id');
        $custcategory_id = request('custcategory_id');
        $company = request('company');

        $people = new Person();

        if ($cust_id) {
            $people = $people->where('people.cust_id', 'LIKE', '%' . $cust_id . '%');
        }
        if ($custcategory_id) {
            $people = $people->where('people.custcategory_id', $custcategory_id);
        }
        if ($company) {
            $people = $people->where('people.company', 'LIKE', '%' . $company . '%');
        }

        return $people;
    }

    private function calquote($id, $retail_price)
    {
        $person = Person::findOrFail($id);

        $cost_rate = $person->cost_rate;

        if ($cost_rate) {
            $result = round($retail_price * ($cost_rate / 100), 2);
            return $result;
        } else {
            return false;
        }

    }
}
