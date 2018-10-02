<?php

namespace App;
// namespace MyApp\Models;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    //qty status condition
    /*
        qty_status = 1 (Stock Order/ Confirmed)
        qty_status = 2 (Actual Stock Deducted/ Delivered)
        qty_status = 3 (Stock Removed/ Deleted || Cancelled)
    */

    use \Venturecraft\Revisionable\RevisionableTrait;

    public static function boot()
    {
        parent::boot();
    }

    protected $revisionEnabled = true;

    //Remove old revisions (works only when used with $historyLimit)
    protected $revisionCleanup = true;

    //Maintain a maximum of 500 changes at any point of time, while cleaning up old revisions.
    protected $historyLimit = 500;

    //storing new creation
    protected $revisionCreationsEnabled = true;

    //revision appear format name
    protected $revisionFormattedFieldNames = array(
        'item_id' => 'Item',
        'qty' => 'Quantity',
        'amount' => 'Amount',
        'unit_price' => 'Unit Price',
    );

    protected $dontKeepRevisionOf = array(
        'qty_status', 'dividend', 'divisor', 'qty_before', 'qty_after'
    );

    protected $fillable = [
        'item_id', 'transaction_id', 'qty', 'amount', 'unit_price', 'qty_status',
        'dividend', 'divisor', 'unit_cost', 'qty_before', 'qty_after', 'ctn', 'pcs'
    ];

    public function item()
    {
        return $this->belongsTo('App\Item');
    }

    public function transaction()
    {
        return $this->belongsTo('App\Transaction');
    }

    public function setQtyAttribute($value)
    {
        if(strstr($value, '/')){
            $this->attributes['qty'] = $this->fraction($value);
        }else if(!$value) {
            $this->attributes['qty'] = null;
        }else {
            $this->attributes['qty'] = $value;
        }
    }

    public function scopeIsTransactionAnalog($query)
    {
        return $query->whereHas('transaction', function($query) {
            $query->where('is_required_analog', 1);
        });
    }

    private function fraction($frac)
    {
        $fraction = explode("/",$frac);
        if($fraction[1] != 0) {
            return $fraction[0]/$fraction[1];
        }
        return "Division by zero error!";

    }
}
