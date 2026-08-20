<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class SeatController extends Controller
{
    public function index()
    {
        $settings = Setting::find(1);
        $totalSeats = $settings ? (int) $settings->total_seats : 80;
        $monthlyFee = $settings ? (float) $settings->monthly_fee : 1500;
        
        $members = Member::all();
        $occupiedSeatsMap = [];
        $today = Carbon::today();
        
        foreach ($members as $member) {
            $dueDate = Carbon::parse($member->due_date)->startOfDay();
            $diffDays = (int) $today->diffInDays($dueDate, false);
            
            $status = 'active';
            if ($diffDays <= 0) {
                $status = 'expired';
            } elseif ($diffDays <= 5) {
                $status = 'due_soon';
            }
            
            $occupiedSeatsMap[$member->seat_number] = [
                'member_id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
                'age' => $member->age,
                'registration_number' => $member->registration_number,
                'join_date' => $member->join_date->toDateString(),
                'due_date' => $member->due_date->toDateString(),
                'last_paid_on' => $member->last_paid_on?->toDateString(),
                'days_left' => $diffDays,
                'status' => $status,
                'is_occupied' => true,
            ];
        }
        
        $seats = [];
        $occupiedCount = 0;
        
        for ($i = 1; $i <= $totalSeats; $i++) {
            if (isset($occupiedSeatsMap[$i])) {
                $seats[] = array_merge(['seat_number' => $i], $occupiedSeatsMap[$i]);
                $occupiedCount++;
            } else {
                $seats[] = [
                    'seat_number' => $i,
                    'is_occupied' => false,
                    'status' => 'available',
                ];
            }
        }
        
        return Inertia::render('Seats/Index', [
            'seats' => $seats,
            'summary' => [
                'total_seats' => $totalSeats,
                'occupied_count' => $occupiedCount,
                'available_count' => $totalSeats - $occupiedCount,
            ],
            'settings' => [
                'monthly_fee' => $monthlyFee,
            ]
        ]);
    }
}
