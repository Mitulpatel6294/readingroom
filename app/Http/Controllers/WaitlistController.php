<?php

namespace App\Http\Controllers;

use App\Models\Waitlist;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WaitlistController extends Controller
{
    public function index()
    {
        $waitlist = Waitlist::orderBy('added_on', 'asc')->orderBy('id', 'asc')->get();
        
        $settings = Setting::find(1);
        $totalSeats = $settings ? (int) $settings->total_seats : 80;
        $monthlyFee = $settings ? (float) $settings->monthly_fee : 1500;
        
        $occupiedSeats = Member::pluck('seat_number')->toArray();
        $vacantSeats = [];
        for ($i = 1; $i <= $totalSeats; $i++) {
            if (!in_array($i, $occupiedSeats)) {
                $vacantSeats[] = $i;
            }
        }
        
        return Inertia::render('Waitlist/Index', [
            'waitlist' => $waitlist,
            'vacantSeats' => $vacantSeats,
            'settings' => [
                'monthly_fee' => $monthlyFee,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'added_on' => ['required', 'date', 'after_or_equal:today'],
        ]);

        Waitlist::create($validated);

        return back()->with('success', 'Added to waiting list successfully');
    }

    public function destroy(Waitlist $waitlist)
    {
        $waitlist->delete();
        return back()->with('success', 'Removed from waiting list successfully');
    }
}
