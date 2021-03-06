<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\PaytermRequest;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Payterm;

class PaytermController extends Controller
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
        $payterms =  Payterm::all();

        return $payterms;
    } 

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('user.payterm.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PaytermRequest $request)
    {
        $input = $request->all();

        $payterm = Payterm::create($input);

        return redirect('user');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $payterm = Payterm::findOrFail($id);

        return view('user.payterm.edit', compact('payterm'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $payterm = Payterm::findOrFail($id);

        return view('user.payterm.edit', compact('payterm'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PaytermRequest $request, $id)
    {
        $payterm = Payterm::findOrFail($id);

        $input = $request->all();

        $payterm->update($input);

        return redirect('user');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payterm = Payterm::findOrFail($id);

        $payterm->delete();

        return redirect('user');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return json
     */
    public function destroyAjax($id)
    {
        $payterm = Payterm::findOrFail($id);

        $payterm->delete();

        return $payterm->name . 'has been successfully deleted';
    }
}
