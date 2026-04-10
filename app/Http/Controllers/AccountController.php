<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $family = $user->families()->first();
        $familyUserIds = $family ? $family->users()->pluck('users.id')->toArray() : [$user->id];

        $accounts = Account::whereIn('user_id', $familyUserIds)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $familyUsers = $family ? $family->users()->get(['users.id', 'users.name']) : collect([$user]);

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'familyUsers' => $familyUsers,
            'usdRate' => Setting::getUsdRate(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:banco,billetera_virtual,efectivo,otro',
            'institution' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'balance' => 'required|numeric',
            'color' => 'nullable|string|max:7',
        ], [
            'user_id.required' => 'Selecciona el titular de la cuenta.',
            'name.required' => 'El nombre de la cuenta es obligatorio.',
            'type.required' => 'Selecciona un tipo de cuenta.',
            'currency.required' => 'Selecciona una moneda.',
            'balance.required' => 'Ingresa el saldo de la cuenta.',
            'balance.numeric' => 'El saldo debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['balance_usd'] = $validated['currency'] === 'USD'
                ? $validated['balance']
                : round($validated['balance'] / $usdRate, 2);

            Account::create($validated);

            return redirect()->back()->with('success', 'La cuenta "' . $validated['name'] . '" fue creada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo crear la cuenta. Verifica los datos e intenta nuevamente.');
        }
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:banco,billetera_virtual,efectivo,otro',
            'institution' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'balance' => 'required|numeric',
            'color' => 'nullable|string|max:7',
        ], [
            'user_id.required' => 'Selecciona el titular de la cuenta.',
            'name.required' => 'El nombre de la cuenta es obligatorio.',
            'balance.required' => 'Ingresa el saldo de la cuenta.',
            'balance.numeric' => 'El saldo debe ser un numero valido.',
        ]);

        try {
            $usdRate = Setting::getUsdRate();
            $validated['balance_usd'] = $validated['currency'] === 'USD'
                ? $validated['balance']
                : round($validated['balance'] / $usdRate, 2);

            $account->update($validated);

            return redirect()->back()->with('success', 'La cuenta "' . $validated['name'] . '" fue actualizada.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo actualizar la cuenta. Intenta nuevamente.');
        }
    }

    public function destroy(Account $account)
    {
        try {
            $name = $account->name;
            $account->delete();
            return redirect()->back()->with('success', 'La cuenta "' . $name . '" fue eliminada.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar la cuenta. Puede tener gastos o ingresos asociados.');
        }
    }
}
