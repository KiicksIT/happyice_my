<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Baum;

class Person extends Baum\Node
{

    use \Venturecraft\Revisionable\RevisionableTrait;
    use SoftDeletes;
    public static function boot()
    {
        parent::boot();
    }
    public function identifiableName()
    {
        return $this->title;
    }
    protected $dontKeepRevisionOf = array(
        'cust_id', 'profile_id', 'salutation',
        'user_id', 'parent_name', 'parent_id',
        'block', 'floor', 'unit'
    );
    protected $revisionEnabled = true;
    protected $revisionCleanup = true;
    protected $historyLimit = 500;
    protected $revisionCreationsEnabled = true;
    protected $revisionFormattedFieldNames = array(
        'contact' => 'Contact Number',
        'alt_contact' => 'Alt Contact',
        'email' => 'Email',
        'name' => 'Att Name',
        'remark' => 'Remark',
        'del_postcode'  => 'Postcode',
        'company' => 'ID Name',
        'bill_address' => 'Billing Address',
        'del_address' => 'Delivery Address',
        'payterm' => 'Pay Term',
        'cost_rate' => 'Cost Rate',
        'active' => 'Active',
        'site_name' => 'Site Name',
        'com_remark' => 'Company',
        'cust_type' => 'Role Level',
        'time_range' => 'Available Time Range',
        'block_coverage' => 'Block Coverage',
        'custcategory_id' => 'Customer Category',
        'is_vending' => 'Vending?',
        'vending_piece_price' => 'Piece/ Price',
        'vending_monthly_rental' => 'Monthly Rental',
        'vending_profit_sharing' => 'Profit Sharing'
    );

    protected $fillable = [
    'contact', 'alt_contact', 'com_remark', 'email', 'name', 'cust_id',
    'remark', 'del_postcode', 'company', 'bill_address', 'del_address',
    'payterm', 'cost_rate', 'active', 'site_name', 'profile_id',
    'note', 'salutation', 'dob', 'cust_type', 'user_id', 'parent_name',
    'parent_id', 'block', 'floor', 'unit', 'time_range', 'block_coverage',
    'custcategory_id', 'is_vending', 'vending_piece_price', 'vending_monthly_rental', 'vending_profit_sharing'
    ];

    protected $dates = ['deleted_at'];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function freezers()
    {
        return $this->belongsToMany(Freezer::class);
    }

    public function accessories()
    {
        return $this->belongsToMany(Accessory::class);
    }

    public function custcategory()
    {
        return $this->belongsTo('App\Custcategory');
    }

    public function transaction()
    {
        return $this->hasMany('App\Transaction');
    }

    public function area()
    {
        return $this->belongsTo('App\Area');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function sale()
    {
        return $this->hasOne('App\Sale');
    }

    public function files()
    {
        return $this->hasMany('App\StoreFile');
    }

    public function payterm()
    {
        return $this->belongsTo('App\Payterm');
    }

    public function prices()
    {
        return $this->hasMany('App\Price');
    }

    public function profile()
    {
        return $this->belongsTo('App\Profile');
    }

    public function notifymanagers()
    {
        return $this->hasMany('App\NotifyManager');
    }

    public function manager()
    {
        return $this->belongsTo('App\Person', 'parent_id');
    }

    public function postcodes()
    {
        return $this->hasMany('App\Postcode');
    }

    // getter and setter

    public function setDobAttribute($date)
    {
        $this->attributes['dob'] = $date? Carbon::parse($date) : null;
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = $value ?: null;
    }

    public function setRemarkAttribute($value)
    {
        $this->attributes['remark'] = $value ?: null;
    }

    public function setParentIdAttribute($value)
    {
        $this->attributes['parent_id'] = $value ?: null;
    }

    public function setCustcategoryIdAttribute($value)
    {
        $this->attributes['custcategory_id'] = $value ?: null;
    }

    public function getPostcodeListAttribute()
    {
        return $this->postcodes->lists('id')->toArray();
    }

    public function getRoleListAttribute()
    {
        return $this->roles->lists('id')->all();
    }

    public function getCreatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('Y-m-d');
    }

    public function getDobAttribute($date)
    {
        return Carbon::parse($date)->format('d-F-Y');
    }

    public function getFreezerListAttribute()
    {
        return $this->freezers->lists('id')->all();
    }

    public function getAccessoryListAttribute()
    {
        return $this->accessories->lists('id')->all();
    }

    public function getFullNameAttribute()
    {
        return $this->attribute['cust_id'].'-'.$this->attribute['name'];
    }

    // scopes
    public function scopeSearchName($query, $name)
    {
        return $query->where('name', 'like', "%$name%");
    }

    public function scopeSearchContact($query, $contact)
    {
        return $query->where('contact', 'like', "%$contact%");
    }

    public function scopeSearchEmail($query, $email)
    {
        return $query->where('email', 'like', "%$email%");
    }

    public function scopeSearchArea($query, $area)
    {
        return $query->where('area','=', $area);
    }

}
