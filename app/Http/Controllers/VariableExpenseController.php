<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Setting;
use App\Models\VariableExpense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VariableExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        $familyUserIds = $family ? $family->users()->pluck('users.id')->toArray() : [$user->id];

        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $filterUser = $request->get('filter_user', 'all');
        $filterBudget = $request->get('filter_budget', 'all');
        $filterNecessary = $request->get('filter_necessary', 'all');

        $query = VariableExpense::whereIn('user_id', $familyUserIds)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with(['user', 'account', 'budget']);

        if ($filterUser !== 'all') {
            $query->where('user_id', $filterUser);
        }
        if ($filterBudget !== 'all') {
            $query->where('budget_id', $filterBudget);
        }
        if ($filterNecessary !== 'all') {
            $query->where('is_necessary', $filterNecessary === 'yes');
        }

        $expenses = $query->orderBy('date', 'desc')->get();

        $accounts = Account::whereIn('user_id', $familyUserIds)->get();
        $budgets = Budget::whereIn('user_id', $familyUserIds)->get()->map(function ($b) use ($month, $year) {
            $spent = $b->variableExpenses()->whereMonth('date', $month)->whereYear('date', $year)->sum('amount');
            $b->spent = round($spent, 2);
            $b->remaining = round($b->amount - $spent, 2);
            $b->percentage = $b->amount > 0 ? round(($spent / $b->amount) * 100, 1) : 0;
            return $b;
        });
        $familyUsers = $family ? $family->users()->get(['users.id', 'users.name']) : collect([$user]);

        $usdRate2 = Setting::getUsdRate();
        $toArs = fn($e) => ($e->currency ?? 'ARS') === 'USD' ? $e->amount * $usdRate2 : $e->amount;
        $totalNecessary = $expenses->where('is_necessary', true)->sum($toArs);
        $totalUnnecessary = $expenses->where('is_necessary', false)->sum($toArs);

        return Inertia::render('VariableExpenses/Index', [
            'expenses' => $expenses,
            'accounts' => $accounts,
            'budgets' => $budgets,
            'familyUsers' => $familyUsers,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'filter_user' => $filterUser,
                'filter_budget' => $filterBudget,
                'filter_necessary' => $filterNecessary,
            ],
            'totals' => [
                'necessary' => round($totalNecessary, 2),
                'unnecessary' => round($totalUnnecessary, 2),
                'total' => round($totalNecessary + $totalUnnecessary, 2),
            ],
            'usdRate' => Setting::getUsdRate(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0',
            'currency'     => 'required|in:ARS,USD',
            'account_id'   => 'nullable|exists:accounts,id',
            'budget_id'    => 'nullable|exists:budgets,id',
            'date'         => 'required|date',
            'category'     => 'nullable|string|max:255',
            'is_necessary' => 'boolean',
            'notes'        => 'nullable|string',
        ], [
            'user_id.required'     => 'Selecciona el titular del gasto.',
            'description.required' => 'Ingresa una descripcion del gasto.',
            'amount.required'      => 'Ingresa el monto del gasto.',
            'amount.numeric'       => 'El monto debe ser un numero valido.',
            'currency.required'    => 'Selecciona la moneda del gasto.',
            'date.required'        => 'Selecciona la fecha del gasto.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = $validated['currency'] === 'USD'
                ? $validated['amount']
                : round($validated['amount'] / $usdRate, 2);

            $expense = VariableExpense::create($validated);

            // Deduct from linked account
            if ($expense->account_id) {
                $this->adjustAccount($expense->account_id, $validated['amount'], $validated['currency'], '-', $usdRate);
            }

            return redirect()->back()->with('success', 'El gasto "' . $validated['description'] . '" fue registrado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo registrar el gasto. Intenta nuevamente.');
        }
    }

    public function update(Request $request, VariableExpense $variableExpense)
    {
        $validated = $request->validate([
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0',
            'currency'     => 'required|in:ARS,USD',
            'account_id'   => 'nullable|exists:accounts,id',
            'budget_id'    => 'nullable|exists:budgets,id',
            'date'         => 'required|date',
            'category'     => 'nullable|string|max:255',
            'is_necessary' => 'boolean',
            'notes'        => 'nullable|string',
        ], [
            'description.required' => 'Ingresa una descripcion del gasto.',
            'amount.required'      => 'Ingresa el monto del gasto.',
            'amount.numeric'       => 'El monto debe ser un numero valido.',
            'currency.required'    => 'Selecciona la moneda del gasto.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = $validated['currency'] === 'USD'
                ? $validated['amount']
                : round($validated['amount'] / $usdRate, 2);

            // Reverse old account effect
            if ($variableExpense->account_id) {
                $this->adjustAccount($variableExpense->account_id, $variableExpense->amount, $variableExpense->currency ?? 'ARS', '+', $usdRate);
            }

            $variableExpense->update($validated);

            // Apply new account effect
            if ($variableExpense->account_id) {
                $this->adjustAccount($variableExpense->account_id, $validated['amount'], $validated['currency'], '-', $usdRate);
            }

            return redirect()->back()->with('success', 'El gasto "' . $validated['description'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el gasto. Intenta nuevamente.');
        }
    }

    public function destroy(VariableExpense $variableExpense)
    {
        try {
            $desc = $variableExpense->description;

            // Restore account balance
            if ($variableExpense->account_id) {
                $this->adjustAccount($variableExpense->account_id, $variableExpense->amount, $variableExpense->currency ?? 'ARS', '+', Setting::getUsdRate());
            }

            $variableExpense->delete();
            return redirect()->back()->with('success', 'El gasto "' . $desc . '" fue eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el gasto. Intenta nuevamente.');
        }
    }

    private function adjustAccount(int $accountId, float $amount, string $currency, string $direction, float $usdRate): void
    {
        $account = Account::find($accountId);
        if (!$account) return;

        if ($account->currency === 'USD' && $currency === 'ARS') {
            $delta = round($amount / $usdRate, 2);
        } elseif ($account->currency === 'ARS' && $currency === 'USD') {
            $delta = round($amount * $usdRate, 2);
        } else {
            $delta = $amount;
        }

        $account->balance = $direction === '+' ? $account->balance + $delta : $account->balance - $delta;
        $account->balance_usd = $account->currency === 'USD'
            ? round($account->balance, 2)
            : round($account->balance / $usdRate, 2);
        $account->save();
    }
}
