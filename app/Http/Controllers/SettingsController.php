<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Http\Requests\UpdateSettingsRequest;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display the settings form.
     */
    public function index()
    {
        $settings = Setting::find(1);

        if (!$settings) {
            $settings = [
                'id' => 1,
                'site_name' => "Clever's Reading Room",
                'monthly_fee' => 1500.00,
                'total_seats' => 80
            ];
        }

        return Inertia::render('Settings', [
            'settings' => $settings
        ]);
    }

    /**
     * Update the settings.
     */
    public function update(UpdateSettingsRequest $request)
    {
        $settings = Setting::find(1);
        
        if (!$settings) {
            $settings = new Setting();
            $settings->id = 1;
        }

        $settings->site_name = $request->site_name;
        $settings->monthly_fee = $request->monthly_fee;
        $settings->total_seats = $request->total_seats;
        $settings->save();

        return back()->with('success', 'Settings updated successfully');
    }
}
