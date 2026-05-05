<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\IncomeJob;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class IncomeJobController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        if (!$family) {
            return Inertia::render('Jobs/Index', [
                'jobs' => [], 'usdRate' => Setting::getUsdRate(),
            ]);
        }

        $usdRate = Setting::getUsdRate();
        $familyUserIds = $family->users()->pluck('users.id');
        $year = (int) $request->get('year', now()->year);

        $jobs = IncomeJob::where('family_id', $family->id)->orderBy('name')->get();

        $toArs = fn($i) => ($i->currency ?? 'ARS') === 'USD' ? (float) $i->amount * $usdRate : (float) $i->amount;

        $data = $jobs->map(function ($job) use ($familyUserIds, $year, $usdRate, $toArs) {
            $allIncomes = Income::where('job', $job->name)->whereIn('user_id', $familyUserIds)->get();
            $yearIncomes = $allIncomes->filter(fn($i) => (int) date('Y', strtotime($i->date)) === $year);

            $monthly = array_fill(1, 12, 0);
            foreach ($yearIncomes as $i) {
                $m = (int) date('n', strtotime($i->date));
                $monthly[$m] += $toArs($i);
            }

            $totalYearArs = round($yearIncomes->sum($toArs), 2);
            $lastIncome = $allIncomes->sortByDesc('date')->first();

            return [
                'id'             => $job->id,
                'name'           => $job->name,
                'color'          => $job->color,
                'count'          => $allIncomes->count(),
                'count_year'     => $yearIncomes->count(),
                'total_year_ars' => $totalYearArs,
                'total_year_usd' => round($totalYearArs / $usdRate, 2),
                'monthly'        => array_values(array_map(fn($v) => round($v, 2), $monthly)),
                'last_date'      => $lastIncome?->date?->toDateString(),
                'last_amount'    => $lastIncome ? (float) $lastIncome->amount : null,
                'last_currency'  => $lastIncome?->currency ?? 'ARS',
            ];
        });

        return Inertia::render('Jobs/Index', [
            'jobs'    => $data,
            'usdRate' => $usdRate,
            'year'    => $year,
        ]);
    }

    public function show(Request $request, IncomeJob $incomeJob)
    {
        $family = $request->user()->families()->first();
        abort_unless($family && $incomeJob->family_id === $family->id, 403);

        $usdRate = Setting::getUsdRate();
        $familyUserIds = $family->users()->pluck('users.id');
        $year = (int) $request->get('year', now()->year);

        $allIncomes = Income::where('job', $incomeJob->name)
            ->whereIn('user_id', $familyUserIds)
            ->with(['user', 'account'])
            ->orderByDesc('date')
            ->get();

        $yearIncomes = $allIncomes->filter(fn($i) => (int) date('Y', strtotime($i->date)) === $year);

        $toArs = fn($i) => ($i->currency ?? 'ARS') === 'USD' ? (float) $i->amount * $usdRate : (float) $i->amount;

        // Monthly evolution (current year)
        $monthly = array_fill(1, 12, 0);
        foreach ($yearIncomes as $i) {
            $m = (int) date('n', strtotime($i->date));
            $monthly[$m] += $toArs($i);
        }
        $monthly = array_values(array_map(fn($v) => round($v, 2), $monthly));

        // By account (current year)
        $byAccount = $yearIncomes->groupBy(fn($i) => $i->account_id ?? 0)
            ->map(function ($group) use ($toArs) {
                $first = $group->first();
                return [
                    'account_id'   => $first->account_id,
                    'account_name' => $first->account?->name ?? 'Sin cuenta',
                    'account_color'=> $first->account?->color ?? '#9ca3af',
                    'total'        => round($group->sum($toArs), 2),
                    'count'        => $group->count(),
                ];
            })->values()->sortByDesc('total')->values();

        // By currency
        $byCurrency = $yearIncomes->groupBy(fn($i) => $i->currency ?? 'ARS')
            ->map(fn($g, $c) => [
                'currency' => $c,
                'total'    => round($g->sum(fn($i) => (float) $i->amount), 2),
                'count'    => $g->count(),
            ])->values();

        // By person
        $byPerson = $yearIncomes->groupBy(fn($i) => $i->user_id)
            ->map(function ($group) use ($toArs) {
                $first = $group->first();
                return [
                    'user_id' => $first->user_id,
                    'name'    => $first->user?->name ?? '-',
                    'total'   => round($group->sum($toArs), 2),
                    'count'   => $group->count(),
                ];
            })->values();

        // Yearly totals (last 5 years)
        $minYear = $allIncomes->isEmpty() ? $year : (int) date('Y', strtotime($allIncomes->min('date')));
        $yearly = [];
        for ($y = max($minYear, $year - 4); $y <= $year; $y++) {
            $rows = $allIncomes->filter(fn($i) => (int) date('Y', strtotime($i->date)) === $y);
            $yearly[] = ['year' => $y, 'total' => round($rows->sum($toArs), 2)];
        }

        $totalAllArs  = round($allIncomes->sum($toArs), 2);
        $totalYearArs = round($yearIncomes->sum($toArs), 2);

        $stats = [
            'total_all_ars'      => $totalAllArs,
            'total_all_usd'      => round($totalAllArs / $usdRate, 2),
            'total_year_ars'     => $totalYearArs,
            'total_year_usd'     => round($totalYearArs / $usdRate, 2),
            'count_all'          => $allIncomes->count(),
            'count_year'         => $yearIncomes->count(),
            'avg_per_income_ars' => $allIncomes->count() ? round($totalAllArs / $allIncomes->count(), 2) : 0,
            'first_date'         => $allIncomes->min('date')?->toDateString(),
            'last_date'          => $allIncomes->max('date')?->toDateString(),
        ];

        return Inertia::render('Jobs/Show', [
            'job'        => $incomeJob,
            'incomes'    => $allIncomes->values(),
            'monthly'    => $monthly,
            'byAccount'  => $byAccount,
            'byCurrency' => $byCurrency,
            'byPerson'   => $byPerson,
            'yearly'     => $yearly,
            'stats'      => $stats,
            'year'       => $year,
            'usdRate'    => $usdRate,
        ]);
    }

    public function store(Request $request)
    {
        $family = $request->user()->families()->first();
        if (!$family) {
            return redirect()->back()->with('error', 'Debes pertenecer a una familia.');
        }

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('income_jobs', 'name')->where('family_id', $family->id),
            ],
            'color' => 'nullable|string|max:16',
        ], [
            'name.required' => 'Ingresa el nombre del trabajo.',
            'name.unique'   => 'Ya existe un trabajo con ese nombre.',
        ]);

        IncomeJob::create([
            'family_id' => $family->id,
            'name'      => $validated['name'],
            'color'     => $validated['color'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Trabajo "' . $validated['name'] . '" agregado.');
    }

    public function update(Request $request, IncomeJob $incomeJob)
    {
        $family = $request->user()->families()->first();
        abort_unless($family && $incomeJob->family_id === $family->id, 403);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('income_jobs', 'name')->where('family_id', $family->id)->ignore($incomeJob->id),
            ],
            'color' => 'nullable|string|max:16',
        ]);

        $oldName = $incomeJob->name;
        $incomeJob->update($validated);

        // Sync existing incomes that referenced the old name
        if ($oldName !== $validated['name']) {
            \App\Models\Income::where('job', $oldName)
                ->whereIn('user_id', $family->users()->pluck('users.id'))
                ->update(['job' => $validated['name']]);
        }

        return redirect()->back()->with('success', 'Trabajo actualizado.');
    }

    public function destroy(Request $request, IncomeJob $incomeJob)
    {
        $family = $request->user()->families()->first();
        abort_unless($family && $incomeJob->family_id === $family->id, 403);

        $name = $incomeJob->name;
        $incomeJob->delete();

        return redirect()->back()->with('success', 'Trabajo "' . $name . '" eliminado.');
    }
}
