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

        // Monthly evolution for chart
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $total = Income::whereIn('user_id', $familyUserIds)
                ->whereMonth('date', $m)->whereYear('date', $year)->sum('amount');
            $monthlyData[] = [
                'month' => $m,
                'total' => round($total, 2),
            ];
        }

        // By source/job
        $bySource = Income::whereIn('user_id', $familyUserIds)
            ->whereYear('date', $year)
            ->select('job', DB::raw('SUM(amount) as total'))
            ->groupBy('job')
            ->get();

        $accounts = Account::whereIn('user_id', $familyUserIds)->get();
        $familyUsers = $family ? $family->users()->get(['users.id', 'users.name']) : collect([$user]);
        $usdRate = Setting::getUsdRate();

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
            'totalMonth' => round($incomes->sum('amount'), 2),
            'totalMonthUsd' => round($incomes->sum('amount') / $usdRate, 2),
            'usdRate' => $usdRate,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'source' => 'nullable|string|max:255',
            'job' => 'nullable|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'user_id.required' => 'Selecciona el titular del ingreso.',
            'description.required' => 'Ingresa una descripcion del ingreso.',
            'amount.required' => 'Ingresa el monto del ingreso.',
            'amount.numeric' => 'El monto debe ser un numero valido.',
            'amount.min' => 'El monto no puede ser negativo.',
            'account_id.exists' => 'La cuenta seleccionada no existe.',
            'date.required' => 'Selecciona la fecha del ingreso.',
            'date.date' => 'La fecha ingresada no es valida.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = round($validated['amount'] / $usdRate, 2);

            Income::create($validated);

            return redirect()->back()->with('success', 'El ingreso "' . $validated['description'] . '" fue registrado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo registrar el ingreso. Intenta nuevamente.');
        }
    }

    public function update(Request $request, Income $income)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'source' => 'nullable|string|max:255',
            'job' => 'nullable|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'description.required' => 'Ingresa una descripcion del ingreso.',
            'amount.required' => 'Ingresa el monto del ingreso.',
            'amount.numeric' => 'El monto debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['amount_usd'] = round($validated['amount'] / $usdRate, 2);

            $income->update($validated);

            return redirect()->back()->with('success', 'El ingreso "' . $validated['description'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el ingreso. Intenta nuevamente.');
        }
    }

    public function destroy(Income $income)
    {
        try {
            $desc = $income->description;
            $income->delete();
            return redirect()->back()->with('success', 'El ingreso "' . $desc . '" fue eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el ingreso. Intenta nuevamente.');
        }
    }
}
