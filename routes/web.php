<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });
    
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [\App\Http\Controllers\SettingsController::class, 'update']);

    Route::get('/seats', [\App\Http\Controllers\SeatController::class, 'index'])->name('seats');

    Route::get('/waitlist', [\App\Http\Controllers\WaitlistController::class, 'index'])->name('waitlist');
    Route::post('/waitlist', [\App\Http\Controllers\WaitlistController::class, 'store']);
    Route::delete('/waitlist/{waitlist}', [\App\Http\Controllers\WaitlistController::class, 'destroy']);

    Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses');
    Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store']);
    Route::put('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'update']);
    Route::delete('/expenses/{expense}', [\App\Http\Controllers\ExpenseController::class, 'destroy']);

    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports');

    Route::get('/members', [\App\Http\Controllers\MemberController::class, 'index'])->name('members');
    Route::post('/members', [\App\Http\Controllers\MemberController::class, 'store'])->name('members.store');
    Route::put('/members/{member}', [\App\Http\Controllers\MemberController::class, 'update'])->name('members.update');
    Route::post('/members/{member}/renew', [\App\Http\Controllers\MemberController::class, 'renew'])->name('members.renew');
    Route::delete('/members/{member}', [\App\Http\Controllers\MemberController::class, 'destroy'])->name('members.destroy');
    Route::get('/members/{member}/payments', [\App\Http\Controllers\MemberController::class, 'payments'])->name('members.payments');
});
