<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'member_id',
        'amount_cash',
        'amount_upi',
        'payment_date',
        'months_paid',
    ];

    protected $casts = [
        'amount_cash' => 'decimal:2',
        'amount_upi' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
