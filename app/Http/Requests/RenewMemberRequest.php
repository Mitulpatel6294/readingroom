<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class RenewMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'months_paid' => ['required', 'integer', 'min:1', 'max:12'],
            'amount_cash' => ['nullable', 'numeric', 'min:0'],
            'amount_upi' => ['nullable', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
        ];
    }

    /**
     * Configure the validator instance.
     * Validates that amount_cash + amount_upi exactly equals monthly_fee * months_paid.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $amountCash = (float) ($this->amount_cash ?? 0);
            $amountUpi = (float) ($this->amount_upi ?? 0);
            $totalPaid = $amountCash + $amountUpi;
            $monthsPaid = (int) ($this->months_paid ?? 1);

            $settings = Setting::find(1);
            $monthlyFee = $settings ? (float) $settings->monthly_fee : 0;
            $totalDue = $monthlyFee * $monthsPaid;

            if ($totalPaid <= 0) {
                $validator->errors()->add('amount_cash', 'Please enter payment amounts for cash or UPI');
                return;
            }

            if (abs($totalPaid - $totalDue) > 0.01) {
                $validator->errors()->add(
                    'amount_cash',
                    "Payment must exactly match total due (₹" . number_format($totalDue, 2) . ")."
                );
            }
        });
    }
}
