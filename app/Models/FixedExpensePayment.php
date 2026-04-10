<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedExpensePayment extends Model
{
    protected $fillable = [
        'fixed_expense_id', 'account_id', 'month', 'year', 'paid', 'paid_date', 'amount_paid', 'amount_paid_usd',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'paid_date' => 'date',
        'amount_paid' => 'decimal:2',
        'amount_paid_usd' => 'decimal:2',
    ];

    public function fixedExpense(): BelongsTo
    {
        return $this->belongsTo(FixedExpense::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
