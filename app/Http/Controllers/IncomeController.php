<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Income;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        $familyUserIds = $family ? $family->users()->pluck('users.id')->toArray() : [$user->id];

        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $filterUser = $request->get('filter_user', 'all');
        $viewMode = $request->get('view', 'monthly');

        $query = Income::whereIn('user_id', $familyUserIds)->with(['user', 'account']);

        if ($viewMode === 'monthly') {
            $query->whereMonth('date', $month)->whereYear('date', $year);
        } elseif ($viewMode === 'yearly') {
            $query->whereYear('date', $year);
        }

        if ($filterUser !== 'all') {
            $query->where('user_id', $filterUser);
        }

        $incomes = $query->orderBy('date', 'desc')->get();

        $usdRate = Setting::getUsdRate();

        // Helper to convert income to ARS
        $toArs = fn($i) => ($i->currency ?? 'ARS') === 'USD' ? $i->amount * $usdRate : $i->amount;

        // Monthly evolution for chart (ARS)
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $rows = Income::whereIn('user_id', $familyUserIds)
                ->whereMonth('date', $m)->whereYear('date', $year)->get();
            $monthlyData[] = [
                'month' => $m,
                'total' => round($rows->sum($toArs), 2),
            ];
        }

        // By source/job (ARS)
        $bySource = Income::whereIn('user_id', $familyUserIds)
            ->whereYear('date', $year)
            ->get()
            ->groupBy(fn($i) => $i->job ?: 'Sin especificar')
            ->map(fn($group, $job) => [
                'job' => $job,
                'total' => round($group->sum($toArs), 2),
            ])->values();

        $accounts = Account::whereIn('user_id', $familyUserIds)->get();
        $familyUsers = $family ? $family->users()->get(['users.id', 'users.name']) : collect([$user]);

        $totalMonthArs = round($incomes->sum($toArs), 2);

        return Inertia::render('Incomes/Index', [
            'incomes' => $incomes,
            'accounts' => $accounts,
            'familyUsers' => $familyUsers,
            'monthlyData' => $monthlyData,
            'bySource' => $bySource,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'filter_user' => $filterUser,
                'view' => $viewMode,
            ],
            'totalMonth' => $totalMonthArs,
            'totalMonthUsd' => round($totalMonthArs / $usdRate, 2),
            'usdRate' => $usdRate,
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
            'source'       => 'nullable|string|max:255',
            'job'          => 'nullable|string|max:255',
            'date'         => 'required|date',
            'is_recurring' => 'boolean',
            'notes'        => 'nullable|string',
        ], [
            'user_id.required'     => 'Selecciona el titular del ingreso.',
            'description.required' => 'Ingresa una descripcion del ingreso.',
            'amount.required'      => 'Ingresa el monto del ingreso.',
            'amount.numeric'       => 'El monto debe ser un numero valido.',
            'currency.required'    => 'Selecciona la moneda del ingreso.',
            'date.required'        => 'Selecciona la fecha del ingreso.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = $validated['currency'] === 'USD'
                ? $validated['amount']
                : round($validated['amount'] / $usdRate, 2);

            $income = Income::create($validated);

            // Add to linked account
            if ($income->account_id) {
                $this->adjustAccount($income->account_id, $validated['amount'], $validated['currency'], '+', $usdRate);
            }

            return redirect()->back()->with('success', 'El ingreso "' . $validated['description'] . '" fue registrado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo registrar el ingreso. Intenta nuevamente.');
        }
    }

    public function update(Request $request, Income $income)
    {
        $validated = $request->validate([
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0',
            'currency'     => 'required|in:ARS,USD',
            'account_id'   => 'nullable|exists:accounts,id',
            'source'       => 'nullable|string|max:255',
            'job'          => 'nullable|string|max:255',
            'date'         => 'required|date',
            'is_recurring' => 'boolean',
            'notes'        => 'nullable|string',
        ], [
            'description.required' => 'Ingresa una descripcion del ingreso.',
            'amount.required'      => 'Ingresa el monto del ingreso.',
            'amount.numeric'       => 'El monto debe ser un numero valido.',
            'currency.required'    => 'Selecciona la moneda del ingreso.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = $validated['currency'] === 'USD'
                ? $validated['amount']
                : round($validated['amount'] / $usdRate, 2);

            // Reverse old account effect
            if ($income->account_id) {
                $this->adjustAccount($income->account_id, $income->amount, $income->currency ?? 'ARS', '-', $usdRate);
            }

            $income->update($validated);

            // Apply new account effect
            if ($income->account_id) {
                $this->adjustAccount($income->account_id, $validated['amount'], $validated['currency'], '+', $usdRate);
            }

            return redirect()->back()->with('success', 'El ingreso "' . $validated['description'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el ingreso. Intenta nuevamente.');
        }
    }

    public function destroy(Income $income)
    {
        try {
            $desc    = $income->description;
            $usdRate = Setting::getUsdRate();

            // Reverse account effect
            if ($income->account_id) {
                $this->adjustAccount($income->account_id, $income->amount, $income->currency ?? 'ARS', '-', Setting::getUsdRate());
            }

            $income->delete();
            return redirect()->back()->with('success', 'El ingreso "' . $desc . '" fue eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el ingreso. Intenta nuevamente.');
        }
    }

    private function adjustAccount(int $accountId, float $amount, string $currency, string $direction, float $usdRate): void
    {
        $account = Account::find($accountId);
        if (!$account) return;

        // Convert amount to account's native currency
        if ($account->currency === 'USD' && $currency === 'ARS') {
            $delta = round($amount / $usdRate, 2);
        } elseif ($account->currency === 'ARS' && $currency === 'USD') {
            $delta = round($amount * $usdRate, 2);
        } else {
            $delta = $amount; // same currency
        }

        $account->balance = $direction === '+' ? $account->balance + $delta : $account->balance - $delta;
        $account->balance_usd = $account->currency === 'USD'
            ? round($account->balance, 2)
            : round($account->balance / $usdRate, 2);
        $account->save();
    }
}
