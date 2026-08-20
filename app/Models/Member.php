<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Member extends Model
{
    protected $fillable = [
        'seat_number',
        'name',
        'phone',
        'age',
        'registration_number',
        'join_date',
        'due_date',
        'last_paid_on',
    ];

    protected $casts = [
        'join_date' => 'date',
        'due_date' => 'date',
        'last_paid_on' => 'date',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calculate days left until due date.
     */
    public function getDaysLeftAttribute(): int
    {
        $today = Carbon::today();
        $dueDate = Carbon::parse($this->due_date)->startOfDay();

        return (int) $today->diffInDays($dueDate, false);
    }

    /**
     * Get the member's status based on days_left.
     */
    public function getStatusAttribute(): string
    {
        $daysLeft = $this->days_left;

        if ($daysLeft <= 0) {
            return 'expired';
        }
        if ($daysLeft <= 5) {
            return 'due_soon';
        }

        return 'active';
    }
}
