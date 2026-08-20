<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    use HasFactory;

    protected $table = 'waiting_list';

    protected $fillable = [
        'name',
        'phone',
        'age',
        'registration_number',
        'added_on',
    ];

    protected $casts = [
        'added_on' => 'date',
        'age' => 'integer',
    ];
}
