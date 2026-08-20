<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Setting;
use App\Models\Waitlist;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $settings = Setting::find(1);
        $totalSeats = $settings ? (int) $settings->total_seats : 80;

        // Fetch members and calculate occupancy
        $members = Member::all();
        $waitlistCount = Waitlist::count();
        $occupiedSeats = $members->count();
        $availableSeats = $totalSeats - $occupiedSeats;

        $expiredCount = 0;
        $dueSoonCount = 0;
        $today = Carbon::today();

        foreach ($members as $member) {
            $dueDate = Carbon::parse($member->due_date)->startOfDay();
            $diffDays = $today->diffInDays($dueDate, false);

            if ($diffDays <= 0) {
                $expiredCount++;
            } elseif ($diffDays <= 5) {
                $dueSoonCount++;
            }
        }

        // Financials (All Time)
        $allPayments = Payment::all();
        $allCash = $allPayments->sum('amount_cash');
        $allUpi = $allPayments->sum('amount_upi');
        $allTotalRev = $allCash + $allUpi;

        $allExpensesList = Expense::all();
        $allTotalExp = $allExpensesList->sum('amount');
        
        $summary = [
            'total_seats' => $totalSeats,
            'occupied_seats' => $occupiedSeats,
            'available_seats' => $availableSeats,
            'expired_count' => $expiredCount,
            'due_soon_count' => $dueSoonCount,
            'waiting_count' => $waitlistCount,
            'revenue' => [
                'cash' => $allCash,
                'upi' => $allUpi,
                'total' => $allTotalRev,
            ],
            'expenses' => $allTotalExp,
            'net_profit' => $allTotalRev - $allTotalExp,
        ];

        return Inertia::render('Dashboard', [
            'summary' => $summary,
            'isSubAdmin' => false,
        ]);
    }
}
