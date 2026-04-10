<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        $familyUserIds = $family ? $family->users()->pluck('users.id')->toArray() : [$user->id];

        $usdRate = Setting::getUsdRate();

        $assets = Asset::whereIn('user_id', $familyUserIds)
            ->with('user')
            ->orderBy('type')
            ->get();

        $accounts = Account::whereIn('user_id', $familyUserIds)->with('user')->get();

        $accountsArs = $accounts->sum(fn($a) => $a->currency === 'USD' ? $a->balance * $usdRate : $a->balance);
        $accountsUsd = $accounts->sum(fn($a) => $a->currency === 'USD' ? $a->balance : round($a->balance / $usdRate, 2));

        $totalArs = $assets->sum('value_ars') + $accountsArs;
        $totalUsd = $assets->sum('value_usd') + $accountsUsd;

        $byType = $assets->groupBy('type')->map(function ($group, $type) {
            return [
                'type' => $type,
                'total_ars' => round($group->sum('value_ars'), 2),
                'total_usd' => round($group->sum('value_usd'), 2),
                'count' => $group->count(),
            ];
        })->values();

        $familyUsers = $family ? $family->users()->get(['users.id', 'users.name']) : collect([$user]);

        return Inertia::render('Assets/Index', [
            'assets' => $assets,
            'accounts' => $accounts,
            'totalArs' => round($totalArs, 2),
            'totalUsd' => round($totalUsd, 2),
            'byType' => $byType,
            'familyUsers' => $familyUsers,
            'usdRate' => $usdRate,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:inmueble,vehiculo,inversion,crypto,ahorro,otro',
            'value_ars' => 'nullable|numeric|min:0',
            'value_usd' => 'nullable|numeric|min:0',
            'currency_input' => 'required|in:ARS,USD',
            'description' => 'nullable|string',
        ], [
            'user_id.required' => 'Selecciona el titular del activo.',
            'name.required' => 'El nombre del activo es obligatorio.',
            'type.required' => 'Selecciona un tipo de activo.',
            'type.in' => 'El tipo de activo seleccionado no es valido.',
            'value_ars.numeric' => 'El valor en pesos debe ser un numero valido.',
            'value_usd.numeric' => 'El valor en dolares debe ser un numero valido.',
            'currency_input.required' => 'Selecciona la moneda del valor ingresado.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();

            if ($validated['currency_input'] === 'ARS') {
                $validated['value_usd'] = round(($validated['value_ars'] ?? 0) / $usdRate, 2);
            } else {
                $validated['value_ars'] = round(($validated['value_usd'] ?? 0) * $usdRate, 2);
            }

            Asset::create($validated);

            return redirect()->back()->with('success', 'El activo "' . $validated['name'] . '" fue creado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo crear el activo. Verifica los datos e intenta nuevamente.');
        }
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:inmueble,vehiculo,inversion,crypto,ahorro,otro',
            'value_ars' => 'nullable|numeric|min:0',
            'value_usd' => 'nullable|numeric|min:0',
            'currency_input' => 'required|in:ARS,USD',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'El nombre del activo es obligatorio.',
            'value_ars.numeric' => 'El valor en pesos debe ser un numero valido.',
            'value_usd.numeric' => 'El valor en dolares debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();

            if ($validated['currency_input'] === 'ARS') {
                $validated['value_usd'] = round(($validated['value_ars'] ?? 0) / $usdRate, 2);
            } else {
                $validated['value_ars'] = round(($validated['value_usd'] ?? 0) * $usdRate, 2);
            }

            $asset->update($validated);

            return redirect()->back()->with('success', 'El activo "' . $validated['name'] . '" fue actualizado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar el activo. Intenta nuevamente.');
        }
    }

    public function destroy(Asset $asset)
    {
        try {
            $name = $asset->name;
            $asset->delete();
            return redirect()->back()->with('success', 'El activo "' . $name . '" fue eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar el activo. Intenta nuevamente.');
        }
    }
}
