<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use App\Models\Setting;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Requests\RenewMemberRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class MemberController extends Controller
{
    /**
     * Display the members listing page.
     */
    public function index()
    {
        $settings = Setting::find(1) ?? (object)[
            'total_seats' => 80,
            'monthly_fee' => 1500,
            'site_name' => "Clever's Reading Room",
        ];

        $totalSeats = (int) $settings->total_seats;
        $members = Member::orderBy('seat_number', 'asc')->get();

        // Calculate days_left and status for each member
        $today = Carbon::today();
        $membersData = $members->map(function ($member) use ($today) {
            $dueDate = Carbon::parse($member->due_date)->startOfDay();
            $daysLeft = (int) $today->diffInDays($dueDate, false);

            $status = 'active';
            if ($daysLeft <= 0) {
                $status = 'expired';
            } elseif ($daysLeft <= 5) {
                $status = 'due_soon';
            }

            return [
                'member_id' => $member->id,
                'seat_number' => $member->seat_number,
                'name' => $member->name,
                'phone' => $member->phone,
                'age' => $member->age,
                'registration_number' => $member->registration_number,
                'join_date' => $member->join_date->toDateString(),
                'due_date' => $member->due_date->toDateString(),
                'last_paid_on' => $member->last_paid_on?->toDateString(),
                'days_left' => $daysLeft,
                'status' => $status,
                'is_occupied' => true,
            ];
        });

        // Build occupied seat numbers set
        $occupiedSeats = $members->pluck('seat_number')->toArray();

        // Build vacant seats list
        $vacantSeats = [];
        for ($i = 1; $i <= $totalSeats; $i++) {
            if (!in_array($i, $occupiedSeats)) {
                $vacantSeats[] = $i;
            }
        }

        return Inertia::render('Members/Index', [
            'members' => $membersData,
            'vacantSeats' => $vacantSeats,
            'settings' => [
                'monthly_fee' => (float) $settings->monthly_fee,
                'total_seats' => $totalSeats,
                'site_name' => $settings->site_name,
            ],
        ]);
    }

    /**
     * Store a new member with initial payment (atomic).
     */
    public function store(StoreMemberRequest $request)
    {
        $validated = $request->validated();

        // Check if seat is already occupied
        $existingSeat = Member::where('seat_number', $validated['seat_number'])->first();
        if ($existingSeat) {
            return back()->withErrors([
                'seat_number' => "Seat number {$validated['seat_number']} is already occupied",
            ]);
        }

        // Check registration number uniqueness
        $trimmedReg = trim($validated['registration_number'] ?? '');
        if ($trimmedReg) {
            $existingReg = Member::where('registration_number', $trimmedReg)->first();
            if ($existingReg) {
                return back()->withErrors([
                    'registration_number' => 'Registration/ID Card number already exists',
                ]);
            }
        }

        // Calculate due date: join_date + months_paid
        $joinDate = Carbon::parse($validated['join_date']);
        $dueDate = $joinDate->copy()->addMonths((int) $validated['months_paid']);
        $paymentDate = $validated['payment_date'] ?? $validated['join_date'];

        DB::transaction(function () use ($validated, $dueDate, $paymentDate, $trimmedReg) {
            $member = Member::create([
                'seat_number' => $validated['seat_number'],
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'age' => $validated['age'] ?? null,
                'registration_number' => $trimmedReg ?: null,
                'join_date' => $validated['join_date'],
                'due_date' => $dueDate->toDateString(),
                'last_paid_on' => $paymentDate,
            ]);

            Payment::create([
                'member_id' => $member->id,
                'amount_cash' => $validated['amount_cash'] ?? 0,
                'amount_upi' => $validated['amount_upi'] ?? 0,
                'payment_date' => $paymentDate,
                'months_paid' => $validated['months_paid'],
            ]);
        });

        return back()->with('success', 'Member added and seat booked successfully');
    }

    /**
     * Update member details.
     */
    public function update(UpdateMemberRequest $request, Member $member)
    {
        $validated = $request->validated();

        // Check if seat is occupied by another member
        $existingSeat = Member::where('seat_number', $validated['seat_number'])
            ->where('id', '!=', $member->id)
            ->first();
        if ($existingSeat) {
            return back()->withErrors([
                'seat_number' => "Seat number {$validated['seat_number']} is already occupied by another member",
            ]);
        }

        // Check registration number uniqueness
        $trimmedReg = trim($validated['registration_number'] ?? '');
        if ($trimmedReg) {
            $existingReg = Member::where('registration_number', $trimmedReg)
                ->where('id', '!=', $member->id)
                ->first();
            if ($existingReg) {
                return back()->withErrors([
                    'registration_number' => 'Registration/ID Card number already exists',
                ]);
            }
        }

        $member->update([
            'seat_number' => $validated['seat_number'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'age' => $validated['age'] ?? null,
            'registration_number' => $trimmedReg ?: null,
            'join_date' => $validated['join_date'],
            'due_date' => $validated['due_date'],
        ]);

        return back()->with('success', 'Member profile updated successfully');
    }

    /**
     * Renew membership (extend due date and log payment) - atomic.
     */
    public function renew(RenewMemberRequest $request, Member $member)
    {
        $validated = $request->validated();

        // Replicate original renewal logic:
        // If expired, renew from today; otherwise extend from current due date
        $currentDueDate = Carbon::parse($member->due_date)->startOfDay();
        $today = Carbon::today();
        $baseDate = $currentDueDate->lt($today) ? $today : $currentDueDate;

        $newDueDate = $baseDate->copy()->addMonths((int) $validated['months_paid']);

        DB::transaction(function () use ($member, $validated, $newDueDate) {
            $member->update([
                'due_date' => $newDueDate->toDateString(),
                'last_paid_on' => $validated['payment_date'],
            ]);

            Payment::create([
                'member_id' => $member->id,
                'amount_cash' => $validated['amount_cash'] ?? 0,
                'amount_upi' => $validated['amount_upi'] ?? 0,
                'payment_date' => $validated['payment_date'],
                'months_paid' => $validated['months_paid'],
            ]);
        });

        return back()->with('success', 'Membership renewed successfully');
    }

    /**
     * Vacate seat (delete member - payments cascade via FK).
     */
    public function destroy(Member $member)
    {
        $seatNumber = $member->seat_number;
        $name = $member->name;

        $member->delete();

        return back()->with('success', "Seat {$seatNumber} vacated. Member {$name} removed successfully.");
    }

    /**
     * Get payment records for a member (JSON response for modal).
     */
    public function payments(Member $member)
    {
        $payments = $member->payments()
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount_cash' => (float) $payment->amount_cash,
                    'amount_upi' => (float) $payment->amount_upi,
                    'payment_date' => $payment->payment_date?->toDateString(),
                    'months_paid' => $payment->months_paid,
                    'total' => (float) $payment->amount_cash + (float) $payment->amount_upi,
                ];
            });

        return response()->json([
            'member_id' => $member->id,
            'member_name' => $member->name,
            'seat_number' => $member->seat_number,
            'payments' => $payments,
        ]);
    }
}
