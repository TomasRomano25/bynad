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

        $totalNecessary = $expenses->where('is_necessary', true)->sum('amount');
        $totalUnnecessary = $expenses->where('is_necessary', false)->sum('amount');

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
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'budget_id' => 'nullable|exists:budgets,id',
            'date' => 'required|date',
            'category' => 'nullable|string|max:255',
            'is_necessary' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'user_id.required' => 'Selecciona el titular del gasto.',
            'description.required' => 'Ingresa una descripcion del gasto.',
            'amount.required' => 'Ingresa el monto del gasto.',
            'amount.numeric' => 'El monto debe ser un numero valido.',
            'amount.min' => 'El monto no puede ser negativo.',
            'account_id.exists' => 'La cuenta seleccionada no existe.',
            'budget_id.exists' => 'El presupuesto seleccionado no existe.',
            'date.required' => 'Selecciona la fecha del gasto.',
            'date.date' => 'La fecha ingresada no es valida.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = round($validated['amount'] / $usdRate, 2);

            VariableExpense::create($validated);

            return redirect()->back()->with('success', 'El gasto "' . $validated['description'] . '" fue registrado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo registrar el gasto. Intenta nuevamente.');
        }
    }

    public function update(Request $request, VariableExpense $variableExpense)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'budget_id' => 'nullable|exists:budgets,id',
            'date' => 'required|date',
            'category' => 'nullable|string|max:255',
            'is_necessary' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'description.required' => 'Ingresa una descripcion del gasto.',
            'amount.required' => 'Ingresa el monto del gasto.',
            'amount.numeric' => 'El monto debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = round($validated['amount'] / $usdRate, 2);

            $variableExpense->update($validated);

            return redirect()->back()->with('success', 'El gasto "' . $validated['description'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el gasto. Intenta nuevamente.');
        }
    }

    public function destroy(VariableExpense $variableExpense)
    {
        try {
            $desc = $variableExpense->description;
            $variableExpense->delete();
            return redirect()->back()->with('success', 'El gasto "' . $desc . '" fue eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el gasto. Intenta nuevamente.');
        }
    }
}
