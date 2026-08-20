<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Setting;
use App\Models\Waitlist;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $settings = Setting::find(1);
        $totalSeats = $settings ? (int) $settings->total_seats : 80;

        // Fetch basic data
        $members = Member::all();
        $waitlistCount = Waitlist::count();

        // Calculate occupancy and statuses
        $occupiedSeats = $members->count();
        $availableSeats = $totalSeats - $occupiedSeats;

        $expiredCount = 0;
        $dueSoonCount = 0;
        $today = Carbon::today();

        foreach ($members as $member) {
            $dueDate = Carbon::parse($member->due_date)->startOfDay();
            $diffDays = $today->diffInDays($dueDate, false); // false for positive/negative

            if ($diffDays <= 0) {
                $expiredCount++;
            } elseif ($diffDays <= 5) {
                $dueSoonCount++;
            }
        }

        // --- Calculate Summary for 'all' ---
        $allPayments = Payment::all();
        $allCash = $allPayments->sum('amount_cash');
        $allUpi = $allPayments->sum('amount_upi');
        $allTotalRev = $allCash + $allUpi;

        $allExpensesList = Expense::all();
        $allTotalExp = $allExpensesList->sum('amount');
        
        $summaryAll = [
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

        // --- Calculate Summary for 'current-month' ---
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthPayments = Payment::whereBetween('payment_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])->get();
        $monthCash = $monthPayments->sum('amount_cash');
        $monthUpi = $monthPayments->sum('amount_upi');
        $monthTotalRev = $monthCash + $monthUpi;

        $monthExpensesList = Expense::whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])->get();
        $monthTotalExp = $monthExpensesList->sum('amount');

        $summaryMonth = [
            'total_seats' => $totalSeats,
            'occupied_seats' => $occupiedSeats,
            'available_seats' => $availableSeats,
            'expired_count' => $expiredCount,
            'due_soon_count' => $dueSoonCount,
            'waiting_count' => $waitlistCount,
            'revenue' => [
                'cash' => $monthCash,
                'upi' => $monthUpi,
                'total' => $monthTotalRev,
            ],
            'expenses' => $monthTotalExp,
            'net_profit' => $monthTotalRev - $monthTotalExp,
        ];

        // --- Payments History ---
        $payments = Payment::select(
            'payments.id',
            'payments.member_id',
            'payments.amount_cash',
            'payments.amount_upi',
            'payments.payment_date',
            'payments.months_paid',
            'payments.created_at',
            'members.name as member_name',
            'members.seat_number'
        )
        ->leftJoin('members', 'payments.member_id', '=', 'members.id')
        ->orderBy('payments.payment_date', 'desc')
        ->orderBy('payments.id', 'desc')
        ->get();

        // --- Monthly Financials ---
        $paymentGroup = Payment::selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount_cash) as cash_rev, SUM(amount_upi) as upi_rev, SUM(amount_cash + amount_upi) as total_rev")
            ->groupBy(DB::raw("DATE_FORMAT(payment_date, '%Y-%m')"))
            ->get()
            ->keyBy('month');

        $expenseGroup = Expense::selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(amount) as total_exp")
            ->groupBy(DB::raw("DATE_FORMAT(date, '%Y-%m')"))
            ->get()
            ->keyBy('month');

        $months = $paymentGroup->keys()->merge($expenseGroup->keys())->unique()->sort()->values();

        $monthlyFinancials = [];
        foreach ($months as $m) {
            $p = $paymentGroup->get($m);
            $e = $expenseGroup->get($m);

            $cashRev = $p ? (float)$p->cash_rev : 0;
            $upiRev = $p ? (float)$p->upi_rev : 0;
            $totalRev = $p ? (float)$p->total_rev : 0;
            $totalExp = $e ? (float)$e->total_exp : 0;

            $monthlyFinancials[] = [
                'month' => $m,
                'revenue' => $totalRev,
                'cash_revenue' => $cashRev,
                'upi_revenue' => $upiRev,
                'expenses' => $totalExp,
                'profit' => $totalRev - $totalExp,
            ];
        }

        return Inertia::render('Reports/Index', [
            'summaryAll' => $summaryAll,
            'summaryMonth' => $summaryMonth,
            'payments' => $payments,
            'expenses' => $allExpensesList,
            'monthlyFinancials' => $monthlyFinancials,
            'isSubAdmin' => false, // Will keep false since readingroom uses full admin currently
        ]);
    }
}
