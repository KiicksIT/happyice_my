<?php

namespace App;
use App\Month;
use Carbon\Carbon;

trait HasMonthOptions{

    // normal builder
    public function getMonthOptions()
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

}