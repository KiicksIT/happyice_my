<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $table = 'generalsettings';

    protected $fillable = [
        'DTDCUST_EMAIL_CONTENT'
    ];
}