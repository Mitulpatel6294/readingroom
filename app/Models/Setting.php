<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'site_name',
        'monthly_fee',
        'total_seats',
    ];
}
