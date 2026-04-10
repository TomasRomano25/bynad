<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Budget;
use App\Models\CreditCard;
use App\Models\FixedExpense;
use App\Models\Income;
use App\Models\Setting;
use App\Models\VariableExpense;
use App\Models\SupermarketPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        $familyUserIds = $family ? $family->users()->pluck('users.id')->toArray() : [$user->id];

        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $filterUser = $request->get('filter_user', 'all');
        $usdRate = Setting::getUsdRate();

        $userIds = $filterUser === 'all' ? $familyUserIds : [(int) $filterUser];

        // Total ingresos del mes
        $totalIncomes = Income::whereIn('user_id', $userIds)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->sum('amount');

        // Total gastos fijos pagados del mes
        $totalFixedExpenses = DB::table('fixed_expenses')
            ->join('fixed_expense_payments', 'fixed_expenses.id', '=', 'fixed_expense_payments.fixed_expense_id')
            ->whereIn('fixed_expenses.user_id', $userIds)
            ->where('fixed_expense_payments.month', $month)
            ->where('fixed_expense_payments.year', $year)
            ->where('fixed_expense_payments.paid', true)
            ->sum('fixed_expense_payments.amount_paid');

        // Total gastos variables del mes
        $totalVariableExpenses = VariableExpense::whereIn('user_id', $userIds)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->sum('amount');

        // Total supermercado
        $totalSupermarket = SupermarketPurchase::whereIn('user_id', $userIds)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->sum('total');

        // Gastos tarjetas de crédito
        $totalCreditCard = DB::table('credit_card_expenses')
            ->whereIn('user_id', $userIds)
            ->whereMonth('purchase_date', $month)->whereYear('purchase_date', $year)
            ->sum('installment_amount');

        $totalExpenses = $totalFixedExpenses + $totalVariableExpenses + $totalSupermarket + $totalCreditCard;
        $balance = $totalIncomes - $totalExpenses;

        // Gastos necesarios vs innecesarios
        $necessaryExpenses = VariableExpense::whereIn('user_id', $userIds)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->where('is_necessary', true)->sum('amount');
        $unnecessaryExpenses = VariableExpense::whereIn('user_id', $userIds)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->where('is_necessary', false)->sum('amount');

        // Gastos por categoría
        $expensesByCategory = VariableExpense::whereIn('user_id', $userIds)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        // Evolución mensual (últimos 6 meses)
        $monthlyEvolution = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $m = $d->month;
            $y = $d->year;

            $inc = Income::whereIn('user_id', $userIds)->whereMonth('date', $m)->whereYear('date', $y)->sum('amount');
            $exp = VariableExpense::whereIn('user_id', $userIds)->whereMonth('date', $m)->whereYear('date', $y)->sum('amount');
            $fix = DB::table('fixed_expenses')
                ->join('fixed_expense_payments', 'fixed_expenses.id', '=', 'fixed_expense_payments.fixed_expense_id')
                ->whereIn('fixed_expenses.user_id', $userIds)
                ->where('fixed_expense_payments.month', $m)->where('fixed_expense_payments.year', $y)
                ->where('fixed_expense_payments.paid', true)->sum('fixed_expense_payments.amount_paid');

            $monthlyEvolution[] = [
                'month' => $d->translatedFormat('M Y'),
                'ingresos' => round($inc, 2),
                'gastos' => round($exp + $fix, 2),
            ];
        }

        // Gastos por persona
        $expensesByUser = [];
        $familyUsers = $family ? $family->users : collect([$user]);
        foreach ($familyUsers as $fu) {
            $userExp = VariableExpense::where('user_id', $fu->id)
                ->whereMonth('date', $month)->whereYear('date', $year)->sum('amount');
            $expensesByUser[] = ['name' => $fu->name, 'total' => round($userExp, 2)];
        }

        // Presupuestos con % gastado
        $budgets = Budget::whereIn('user_id', $familyUserIds)->get()->map(function ($b) use ($month, $year) {
            $spent = $b->variableExpenses()->whereMonth('date', $month)->whereYear('date', $year)->sum('amount');
            return [
                'id' => $b->id,
                'name' => $b->name,
                'amount' => $b->amount,
                'spent' => round($spent, 2),
                'percentage' => $b->amount > 0 ? round(($spent / $b->amount) * 100, 1) : 0,
                'color' => $b->color,
            ];
        });

        // Patrimonio total
        $totalAssets = Asset::whereIn('user_id', $familyUserIds)->sum('value_ars');
        $totalAssetsUsd = Asset::whereIn('user_id', $familyUserIds)->sum('value_usd');

        // Cuentas
        $accounts = Account::whereIn('user_id', $familyUserIds)->with('user')->get();

        // Gastos innecesarios comparado con mes anterior
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        $prevUnnecessary = VariableExpense::whereIn('user_id', $userIds)
            ->whereMonth('date', $prevMonth)->whereYear('date', $prevYear)
            ->where('is_necessary', false)->sum('amount');

        $unnecessaryTrend = $prevUnnecessary > 0
            ? round((($unnecessaryExpenses - $prevUnnecessary) / $prevUnnecessary) * 100, 1)
            : 0;

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalIncomes' => round($totalIncomes, 2),
                'totalIncomesUsd' => round($totalIncomes / $usdRate, 2),
                'totalExpenses' => round($totalExpenses, 2),
                'totalExpensesUsd' => round($totalExpenses / $usdRate, 2),
                'totalFixedExpenses' => round($totalFixedExpenses, 2),
                'totalVariableExpenses' => round($totalVariableExpenses, 2),
                'totalSupermarket' => round($totalSupermarket, 2),
                'totalCreditCard' => round($totalCreditCard, 2),
                'balance' => round($balance, 2),
                'balanceUsd' => round($balance / $usdRate, 2),
                'necessaryExpenses' => round($necessaryExpenses, 2),
                'unnecessaryExpenses' => round($unnecessaryExpenses, 2),
                'unnecessaryTrend' => $unnecessaryTrend,
                'totalAssets' => round($totalAssets, 2),
                'totalAssetsUsd' => round($totalAssetsUsd, 2),
            ],
            'expensesByCategory' => $expensesByCategory,
            'monthlyEvolution' => $monthlyEvolution,
            'expensesByUser' => $expensesByUser,
            'budgets' => $budgets,
            'accounts' => $accounts,
            'familyUsers' => $familyUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'filter_user' => $filterUser,
            ],
            'usdRate' => $usdRate,
        ]);
    }
}
