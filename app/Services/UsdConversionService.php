<?php

namespace App\Services;

use App\Models\Family;
use Illuminate\Support\Facades\DB;

class UsdConversionService
{
    /**
     * Recalculate every stored ARS/USD counterpart for all records owned by the
     * given family, using the family's current usd_rate.
     *
     * The canonical value stored in each record's own currency is never touched;
     * only the derived *_usd (or *_ars for assets) columns are refreshed so the
     * whole app reflects the new rate consistently.
     */
    public function recalculateForFamily(Family $family): void
    {
        $rate = (float) $family->usd_rate;
        if ($rate <= 0) {
            return;
        }

        $userIds = $family->users()->pluck('users.id')->all();
        if (empty($userIds)) {
            return;
        }

        DB::transaction(function () use ($userIds, $rate) {
            // Accounts: balance is canonical, balance_usd derived.
            $this->refreshCurrencyColumn('accounts', 'balance', 'balance_usd', 'currency', $userIds, $rate);

            // Incomes / variable / fixed expenses: amount canonical, amount_usd derived.
            $this->refreshCurrencyColumn('incomes', 'amount', 'amount_usd', 'currency', $userIds, $rate);
            $this->refreshCurrencyColumn('variable_expenses', 'amount', 'amount_usd', 'currency', $userIds, $rate);
            $this->refreshCurrencyColumn('fixed_expenses', 'amount', 'amount_usd', 'currency', $userIds, $rate);

            // Credit cards: limit is ARS-only (no currency column), limit_amount_usd derived.
            DB::table('credit_cards')->whereIn('user_id', $userIds)
                ->update(['limit_amount_usd' => DB::raw('ROUND(limit_amount / ' . $rate . ', 2)')]);

            // Budgets: ARS only (no currency column).
            DB::table('budgets')->whereIn('user_id', $userIds)
                ->update(['amount_usd' => DB::raw('ROUND(amount / ' . $rate . ', 2)')]);

            // Supermarket purchases: total in ARS, total_usd derived.
            DB::table('supermarket_purchases')->whereIn('user_id', $userIds)
                ->update(['total_usd' => DB::raw('ROUND(total / ' . $rate . ', 2)')]);

            // Credit card expenses: joined by card ownership.
            DB::table('credit_card_expenses')
                ->join('credit_cards', 'credit_cards.id', '=', 'credit_card_expenses.credit_card_id')
                ->whereIn('credit_cards.user_id', $userIds)
                ->update([
                    'credit_card_expenses.amount_usd' => DB::raw(
                        "ROUND(CASE WHEN credit_card_expenses.currency = 'USD' THEN credit_card_expenses.amount ELSE credit_card_expenses.amount / {$rate} END, 2)"
                    ),
                ]);

            // Fixed expense payments: amount_paid canonical (in the expense currency).
            DB::table('fixed_expense_payments')
                ->join('fixed_expenses', 'fixed_expenses.id', '=', 'fixed_expense_payments.fixed_expense_id')
                ->whereIn('fixed_expenses.user_id', $userIds)
                ->whereNotNull('fixed_expense_payments.amount_paid')
                ->update([
                    'fixed_expense_payments.amount_paid_usd' => DB::raw(
                        "ROUND(CASE WHEN fixed_expenses.currency = 'USD' THEN fixed_expense_payments.amount_paid ELSE fixed_expense_payments.amount_paid / {$rate} END, 2)"
                    ),
                ]);

            // Assets: the entered value (currency_input) is canonical, the other side derived.
            DB::table('assets')->whereIn('user_id', $userIds)->where('currency_input', 'USD')
                ->update(['value_ars' => DB::raw('ROUND(value_usd * ' . $rate . ', 2)')]);
            DB::table('assets')->whereIn('user_id', $userIds)->where('currency_input', '!=', 'USD')
                ->update(['value_usd' => DB::raw('ROUND(value_ars / ' . $rate . ', 2)')]);
        });
    }

    /**
     * Refresh a derived USD column: canonical amount is in each row's own
     * currency; when currency is USD the USD column equals the amount, otherwise
     * it is amount / rate.
     */
    private function refreshCurrencyColumn(string $table, string $amountCol, string $usdCol, string $currencyCol, array $userIds, float $rate): void
    {
        DB::table($table)->whereIn('user_id', $userIds)->where($currencyCol, 'USD')
            ->update([$usdCol => DB::raw("ROUND({$amountCol}, 2)")]);

        DB::table($table)->whereIn('user_id', $userIds)
            ->where(function ($q) use ($currencyCol) {
                $q->where($currencyCol, '!=', 'USD')->orWhereNull($currencyCol);
            })
            ->update([$usdCol => DB::raw("ROUND({$amountCol} / {$rate}, 2)")]);
    }
}
