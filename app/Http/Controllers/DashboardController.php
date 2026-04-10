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
    private function ccTotalArs(array $userIds, int $month, int $year, float $usdRate): float
    {
        $expenses = DB::table('credit_card_expenses')
            ->whereIn('user_id', $userIds)
            ->whereMonth('purchase_date', $month)
            ->whereYear('purchase_date', $year)
            ->select('installment_amount', 'currency')
            ->get();

        return round($expenses->sum(function ($e) use ($usdRate) {
            return ($e->currency ?? 'ARS') === 'USD'
                ? $e->installment_amount * $usdRate
                : $e->installment_amount;
        }), 2);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        $familyUserIds = $family ? $family->users()->pluck('users.id')->toArray() : [$user->id];

        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);
        $filterUser = $request->get('filter_user', 'all');
        $usdRate = Setting::getUsdRate();

        $userIds = $filterUser === 'all' ? $familyUserIds : [(int) $filterUser];

        // Ingresos (ARS, convertidos desde USD si aplica)
        $incomeRows = Income::whereIn('user_id', $userIds)
            ->whereMonth('date', $month)->whereYear('date', $year)->get();
        $totalIncomes = $incomeRows->sum(fn($i) => ($i->currency ?? 'ARS') === 'USD' ? $i->amount * $usdRate : $i->amount);

        // Gastos fijos pagados
        $totalFixedExpenses = DB::table('fixed_expenses')
            ->join('fixed_expense_payments', 'fixed_expenses.id', '=', 'fixed_expense_payments.fixed_expense_id')
            ->whereIn('fixed_expenses.user_id', $userIds)
            ->where('fixed_expense_payments.month', $month)
            ->where('fixed_expense_payments.year', $year)
            ->where('fixed_expense_payments.paid', true)
            ->sum('fixed_expense_payments.amount_paid');

        // Gastos variables (ARS, convertidos desde USD si aplica)
        $variableRows = VariableExpense::whereIn('user_id', $userIds)
            ->whereMonth('date', $month)->whereYear('date', $year)->get();
        $totalVariableExpenses = $variableRows->sum(fn($e) => ($e->currency ?? 'ARS') === 'USD' ? $e->amount * $usdRate : $e->amount);

        // Supermercado
        $totalSupermarket = SupermarketPurchase::whereIn('user_id', $userIds)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->sum('total');

        // Tarjetas (convertido todo a ARS)
        $totalCreditCard = $this->ccTotalArs($userIds, $month, $year, $usdRate);

        $totalExpenses = $totalFixedExpenses + $totalVariableExpenses + $totalSupermarket + $totalCreditCard;
        $balance = $totalIncomes - $totalExpenses;

        // Necesarios vs innecesarios (gastos variables, ARS)
        $toArs = fn($e) => ($e->currency ?? 'ARS') === 'USD' ? $e->amount * $usdRate : $e->amount;
        $necessaryExpenses   = $variableRows->where('is_necessary', true)->sum($toArs);
        $unnecessaryExpenses = $variableRows->where('is_necessary', false)->sum($toArs);

        // Gastos por categoría: variables + gastos fijos pagados (ambos en ARS)
        $categoryTotals = [];

        foreach ($variableRows as $e) {
            $cat = $e->category ?: 'Sin categoría';
            $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + $toArs($e);
        }

        $fixedPaidRows = DB::table('fixed_expenses')
            ->join('fixed_expense_payments', 'fixed_expenses.id', '=', 'fixed_expense_payments.fixed_expense_id')
            ->whereIn('fixed_expenses.user_id', $userIds)
            ->where('fixed_expense_payments.month', $month)
            ->where('fixed_expense_payments.year', $year)
            ->where('fixed_expense_payments.paid', true)
            ->select('fixed_expenses.category', 'fixed_expenses.currency', 'fixed_expense_payments.amount_paid')
            ->get();

        foreach ($fixedPaidRows as $p) {
            $cat = $p->category ?: 'Gastos Fijos';
            $amountArs = ($p->currency ?? 'ARS') === 'USD' ? $p->amount_paid * $usdRate : $p->amount_paid;
            $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + $amountArs;
        }

        $expensesByCategory = collect($categoryTotals)
            ->map(fn($total, $cat) => ['category' => $cat, 'total' => round($total, 2)])
            ->sortByDesc('total')
            ->values();

        // Evolución mensual (últimos 6 meses) — incluye todos los tipos
        $monthlyEvolution = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $m = $d->month;
            $y = $d->year;

            $incRows = Income::whereIn('user_id', $userIds)->whereMonth('date', $m)->whereYear('date', $y)->get();
            $inc = $incRows->sum(fn($r) => ($r->currency ?? 'ARS') === 'USD' ? $r->amount * $usdRate : $r->amount);
            $varRows = VariableExpense::whereIn('user_id', $userIds)->whereMonth('date', $m)->whereYear('date', $y)->get();
            $var = $varRows->sum(fn($r) => ($r->currency ?? 'ARS') === 'USD' ? $r->amount * $usdRate : $r->amount);
            $fix  = DB::table('fixed_expenses')
                ->join('fixed_expense_payments', 'fixed_expenses.id', '=', 'fixed_expense_payments.fixed_expense_id')
                ->whereIn('fixed_expenses.user_id', $userIds)
                ->where('fixed_expense_payments.month', $m)->where('fixed_expense_payments.year', $y)
                ->where('fixed_expense_payments.paid', true)->sum('fixed_expense_payments.amount_paid');
            $super = SupermarketPurchase::whereIn('user_id', $userIds)->whereMonth('date', $m)->whereYear('date', $y)->sum('total');
            $cc   = $this->ccTotalArs($userIds, $m, $y, $usdRate);

            $monthlyEvolution[] = [
                'month'    => $d->translatedFormat('M Y'),
                'ingresos' => round($inc, 2),
                'gastos'   => round($var + $fix + $super + $cc, 2),
            ];
        }

        // Gastos por persona — todos los tipos
        $expensesByUser = [];
        $familyUsers = $family ? $family->users : collect([$user]);
        foreach ($familyUsers as $fu) {
            $fuVarRows = VariableExpense::where('user_id', $fu->id)->whereMonth('date', $month)->whereYear('date', $year)->get();
            $var = $fuVarRows->sum(fn($e) => ($e->currency ?? 'ARS') === 'USD' ? $e->amount * $usdRate : $e->amount);
            $fix   = DB::table('fixed_expenses')
                ->join('fixed_expense_payments', 'fixed_expenses.id', '=', 'fixed_expense_payments.fixed_expense_id')
                ->where('fixed_expenses.user_id', $fu->id)
                ->where('fixed_expense_payments.month', $month)->where('fixed_expense_payments.year', $year)
                ->where('fixed_expense_payments.paid', true)->sum('fixed_expense_payments.amount_paid');
            $super = SupermarketPurchase::where('user_id', $fu->id)->whereMonth('date', $month)->whereYear('date', $year)->sum('total');
            $cc    = $this->ccTotalArs([$fu->id], $month, $year, $usdRate);
            $expensesByUser[] = ['name' => $fu->name, 'total' => round($var + $fix + $super + $cc, 2)];
        }

        // Presupuestos
        $budgets = Budget::whereIn('user_id', $familyUserIds)->get()->map(function ($b) use ($month, $year) {
            $spent = $b->variableExpenses()->whereMonth('date', $month)->whereYear('date', $year)->sum('amount');
            return [
                'id' => $b->id, 'name' => $b->name, 'amount' => $b->amount,
                'spent' => round($spent, 2),
                'percentage' => $b->amount > 0 ? round(($spent / $b->amount) * 100, 1) : 0,
                'color' => $b->color,
            ];
        });

        // Patrimonio = activos + saldo de cuentas
        $assets   = Asset::whereIn('user_id', $familyUserIds)->get();
        $accounts = Account::whereIn('user_id', $familyUserIds)->get();

        $totalAssets = $assets->sum('value_ars')
            + $accounts->sum(fn($a) => $a->currency === 'USD' ? $a->balance * $usdRate : $a->balance);
        $totalAssetsUsd = $assets->sum('value_usd')
            + $accounts->sum(fn($a) => $a->currency === 'USD' ? $a->balance : round($a->balance / $usdRate, 2));

        // Cuentas para mostrar en el dashboard
        $accountsForDashboard = Account::whereIn('user_id', $familyUserIds)->with('user')->get();

        // Trend gastos innecesarios
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear  = $month == 1 ? $year - 1 : $year;
        $prevUnnRows = VariableExpense::whereIn('user_id', $userIds)
            ->whereMonth('date', $prevMonth)->whereYear('date', $prevYear)
            ->where('is_necessary', false)->get();
        $prevUnnecessary = $prevUnnRows->sum(fn($e) => ($e->currency ?? 'ARS') === 'USD' ? $e->amount * $usdRate : $e->amount);
        $unnecessaryTrend = $prevUnnecessary > 0
            ? round((($unnecessaryExpenses - $prevUnnecessary) / $prevUnnecessary) * 100, 1)
            : 0;

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalIncomes'         => round($totalIncomes, 2),
                'totalIncomesUsd'      => round($totalIncomes / $usdRate, 2),
                'totalExpenses'        => round($totalExpenses, 2),
                'totalExpensesUsd'     => round($totalExpenses / $usdRate, 2),
                'totalFixedExpenses'   => round($totalFixedExpenses, 2),
                'totalVariableExpenses'=> round($totalVariableExpenses, 2),
                'totalSupermarket'     => round($totalSupermarket, 2),
                'totalCreditCard'      => round($totalCreditCard, 2),
                'balance'              => round($balance, 2),
                'balanceUsd'           => round($balance / $usdRate, 2),
                'necessaryExpenses'    => round($necessaryExpenses, 2),
                'unnecessaryExpenses'  => round($unnecessaryExpenses, 2),
                'unnecessaryTrend'     => $unnecessaryTrend,
                'totalAssets'          => round($totalAssets, 2),
                'totalAssetsUsd'       => round($totalAssetsUsd, 2),
            ],
            'expensesByCategory' => $expensesByCategory,
            'monthlyEvolution'   => $monthlyEvolution,
            'expensesByUser'     => $expensesByUser,
            'budgets'            => $budgets,
            'accounts'           => $accountsForDashboard,
            'familyUsers'        => $familyUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'avatar' => $u->avatar]),
            'familyCode'         => $family?->id,
            'filters'            => ['month' => (int) $month, 'year' => (int) $year, 'filter_user' => $filterUser],
            'usdRate'            => $usdRate,
        ]);
    }
}
