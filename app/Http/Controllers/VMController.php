<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
// use Chrisbjr\ApiGuard\Http\Controllers\ApiGuardController;
use DB;
use App\Vending;

class VMController extends Controller
{

    //auth-only login can see
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['dataIndex']]);
    }

    // api linkup function
    public function dataIndex()
    {
        return 'hello';
    }

    // get vending index page
    public function vendingIndex()
    {
        return view('vm.index');
    }

    // get vending index api
    public function getVendingIndexApi()
    {
        // showing total amount init
        $total_amount = 0;
        // initiate the page num when null given
        $pageNum = request('pageNum') ? request('pageNum') : 100;

        $vms = $this->getVmsData();

        if (request('sortName')) {
            $vms = $vms->orderBy(request('sortName'), request('sortBy') ? 'asc' : 'desc');
        }

        if ($pageNum == 'All') {
            $vms = $vms->latest('vendings.created_at')->get();
        } else {
            $vms = $vms->latest('vendings.created_at')->paginate($pageNum);
        }

        $data = [
            'vms' => $vms
        ];
        return $data;
    }

    // return vending create page
    public function getVendingCreate()
    {
        return view('vm.create');
    }

    public function storeVending(Request $request)
    {
        $this->validate($request, [
            'serial_no' => 'required|unique:vendings,serial_no'
        ], [
            'serial_no.required' => 'Please fill in serial number',
            'serial_no.unique' => 'The serial number has been used'
        ]);

        $input = $request->all();

        Vending::create($input);

        return redirect('vm');
    }

    public function destroyVending($id)
    {
        $vending = Vending::findOrFail($id);

        $vending->delete();
    }

    public function editVending($id)
    {
        $vending = Vending::findOrFail($id);

        return view('vm.edit', compact('vending'));
    }

    public function updateVending(Request $request, $id)
    {
        $input = $request->all();

        $vending = Vending::findOrFail($id);

        $vending->update($input);

        return redirect('vm');
    }

    // retrieve vms data ()
    private function getVmsData()
    {
        $vendings = DB::table('vendings')
            ->leftJoin('people', 'vendings.person_id', '=', 'people.id')
            ->leftJoin('profiles', 'people.profile_id', '=', 'profiles.id')
            ->leftJoin('custcategories', 'people.custcategory_id', '=', 'custcategories.id')
            ->select(
                'people.cust_id', 'people.company', 'people.id as person_id',
                'vendings.vend_id', 'vendings.serial_no', 'vendings.type', 'vendings.router', 'vendings.desc', 'vendings.updated_by', 'vendings.created_at', 'vendings.id',
                'profiles.id as profile_id',
                'custcategories.name as custcategory'
            );

        // reading whether search input is filled
        if (request('vend_id') or request('cust_id') or request('company') or request('custcategory')) {
            $vendings = $this->searchDBFilter($vendings);
        }

        return $vendings;
    }   
    
    // pass value into filter search for DB (collection, collection request) [query]
    private function searchDBFilter($vendings)
    {
        if (request('vend_id')) {
            $vendings = $vendings->where('vendings.vend_id', 'LIKE', '%' . request('vend_id') . '%');
        }
        if (request('cust_id')) {
            $vendings = $vendings->where('people.cust_id', 'LIKE', '%' . request('cust_id') . '%');
        }
        if (request('company')) {
            $com = request('company');
            $vendings = $vendings->where(function ($query) use ($com) {
                $query->where('people.company', 'LIKE', '%' . $com . '%')
                    ->orWhere(function ($query) use ($com) {
                        $query->where('people.cust_id', 'LIKE', 'D%')
                            ->where('people.name', 'LIKE', '%' . $com . '%');
                    });
            });
        }
        if (request('custcategory')) {
            $vendings = $vendings->where('custcategories.id', request('custcategory'));
        }

        if (request('sortName')) {
            $vendings = $vendings->orderBy(request('sortName'), request('sortBy') ? 'asc' : 'desc');
        }
        return $vendings;
    }
    
}
