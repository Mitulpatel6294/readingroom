<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::orderBy('date', 'desc')->orderBy('id', 'desc')->get();
        
        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        Expense::create($validated);

        return back()->with('success', 'Expense added successfully');
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $expense->update($validated);

        return back()->with('success', 'Expense updated successfully');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return back()->with('success', 'Expense deleted successfully');
    }
}
