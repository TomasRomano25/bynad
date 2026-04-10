<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'user_id', 'name', 'type', 'institution', 'currency', 'balance', 'balance_usd', 'color', 'icon',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'balance_usd' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fixedExpenses(): HasMany
    {
        return $this->hasMany(FixedExpense::class);
    }

    public function variableExpenses(): HasMany
    {
        return $this->hasMany(VariableExpense::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function recalculateBalance(): void
    {
        $totalIncomes = $this->incomes()->sum('amount');
        $totalFixed = $this->fixedExpenses()
            ->join('fixed_expense_payments', 'fixed_expenses.id', '=', 'fixed_expense_payments.fixed_expense_id')
            ->where('fixed_expense_payments.paid', true)
            ->sum('fixed_expense_payments.amount_paid');
        $totalVariable = $this->variableExpenses()->sum('amount');
        $totalSupermarket = SupermarketPurchase::where('account_id', $this->id)->sum('total');

        $this->balance = $totalIncomes - $totalFixed - $totalVariable - $totalSupermarket;

        $usdRate = Setting::where('key', 'usd_rate')->value('value') ?: 1200;
        $this->balance_usd = round($this->balance / $usdRate, 2);
        $this->save();
    }
}
