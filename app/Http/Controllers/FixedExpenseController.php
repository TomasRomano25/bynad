<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FixedExpense;
use App\Models\FixedExpensePayment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FixedExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        $familyUserIds = $family ? $family->users()->pluck('users.id')->toArray() : [$user->id];

        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $expenses = FixedExpense::whereIn('user_id', $familyUserIds)
            ->with(['user', 'account', 'payments' => function ($q) use ($month, $year) {
                $q->where('month', $month)->where('year', $year);
            }])
            ->orderBy('due_day')
            ->get()
            ->map(function ($expense) use ($month, $year) {
                $payment = $expense->payments->first();
                $expense->is_paid = $payment ? $payment->paid : false;
                $expense->payment_id = $payment?->id;
                return $expense;
            });

        $accounts = Account::whereIn('user_id', $familyUserIds)->get();

        $familyUsers = $family ? $family->users()->get(['users.id', 'users.name']) : collect([$user]);

        return Inertia::render('FixedExpenses/Index', [
            'expenses' => $expenses,
            'accounts' => $accounts,
            'familyUsers' => $familyUsers,
            'filters' => ['month' => (int) $month, 'year' => (int) $year],
            'usdRate' => Setting::getUsdRate(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'name'       => 'required|string|max:255',
            'amount'     => 'required|numeric|min:0',
            'currency'   => 'required|in:ARS,USD',
            'account_id' => 'nullable|exists:accounts,id',
            'due_day'    => 'nullable|integer|min:1|max:31',
            'category'   => 'nullable|string|max:255',
            'notes'      => 'nullable|string',
        ], [
            'user_id.required' => 'Selecciona el titular del gasto.',
            'name.required'    => 'El nombre del gasto fijo es obligatorio.',
            'amount.required'  => 'Ingresa el monto del gasto.',
            'amount.numeric'   => 'El monto debe ser un numero valido.',
            'currency.required'=> 'Selecciona la moneda del gasto.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = $validated['currency'] === 'USD'
                ? $validated['amount']
                : round($validated['amount'] / $usdRate, 2);

            FixedExpense::create($validated);

            return redirect()->back()->with('success', 'El gasto fijo "' . $validated['name'] . '" fue creado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo crear el gasto fijo. Intenta nuevamente.');
        }
    }

    public function update(Request $request, FixedExpense $fixedExpense)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'amount'     => 'required|numeric|min:0',
            'currency'   => 'required|in:ARS,USD',
            'account_id' => 'nullable|exists:accounts,id',
            'due_day'    => 'nullable|integer|min:1|max:31',
            'category'   => 'nullable|string|max:255',
            'notes'      => 'nullable|string',
        ], [
            'name.required'    => 'El nombre del gasto fijo es obligatorio.',
            'amount.required'  => 'Ingresa el monto del gasto.',
            'amount.numeric'   => 'El monto debe ser un numero valido.',
            'currency.required'=> 'Selecciona la moneda del gasto.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = $validated['currency'] === 'USD'
                ? $validated['amount']
                : round($validated['amount'] / $usdRate, 2);

            $fixedExpense->update($validated);

            return redirect()->back()->with('success', 'El gasto fijo "' . $validated['name'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el gasto fijo. Intenta nuevamente.');
        }
    }

    public function destroy(FixedExpense $fixedExpense)
    {
        try {
            $name = $fixedExpense->name;
            $fixedExpense->delete();
            return redirect()->back()->with('success', 'El gasto fijo "' . $name . '" fue eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el gasto fijo. Intenta nuevamente.');
        }
    }

    public function togglePayment(Request $request, FixedExpense $fixedExpense)
    {
        try {
            $month   = $request->get('month', now()->month);
            $year    = $request->get('year', now()->year);
            $usdRate = Setting::getUsdRate();

            $wasAlreadyExisting = FixedExpensePayment::where([
                'fixed_expense_id' => $fixedExpense->id,
                'month' => $month,
                'year'  => $year,
            ])->exists();

            $payment = FixedExpensePayment::firstOrNew([
                'fixed_expense_id' => $fixedExpense->id,
                'month' => $month,
                'year'  => $year,
            ]);

            $previouslyPaid    = $wasAlreadyExisting && $payment->paid;
            $previousAccountId = $payment->account_id;

            $payment->paid       = !($wasAlreadyExisting && $payment->paid);
            $payment->paid_date  = $payment->paid ? now() : null;
            $payment->amount_paid     = $payment->paid ? $fixedExpense->amount : null;
            $payment->amount_paid_usd = $payment->paid ? round($fixedExpense->amount / $usdRate, 2) : null;
            $payment->account_id = $request->get('account_id', $fixedExpense->account_id);
            $payment->save();

            // Adjust account balance
            $accountId = $payment->paid ? $payment->account_id : $previousAccountId;
            if ($accountId) {
                $account = Account::find($accountId);
                if ($account) {
                    if ($payment->paid) {
                        // Deduct from account
                        $deduct = $account->currency === 'USD'
                            ? (float) $payment->amount_paid_usd
                            : (float) $payment->amount_paid;
                        $account->balance -= $deduct;
                    } else {
                        // Restore to account (was previously paid)
                        $restore = $account->currency === 'USD'
                            ? round($fixedExpense->amount / $usdRate, 2)
                            : (float) $fixedExpense->amount;
                        $account->balance += $restore;
                    }
                    $account->balance_usd = $account->currency === 'USD'
                        ? round($account->balance, 2)
                        : round($account->balance / $usdRate, 2);
                    $account->save();
                }
            }

            $status = $payment->paid ? 'pagado' : 'pendiente';
            return redirect()->back()->with('success', '"' . $fixedExpense->name . '" marcado como ' . $status . '.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el estado de pago. Intenta nuevamente.');
        }
    }
}
