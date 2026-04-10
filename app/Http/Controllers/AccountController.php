<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'user_id'     => 'required|exists:users,id',
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:banco,billetera_virtual,efectivo,otro',
            'institution' => 'nullable|string|max:255',
            'currency'    => 'required|string|max:10',
            'color'       => 'nullable|string|max:7',
        ], [
            'user_id.required' => 'Selecciona el titular de la cuenta.',
            'name.required'    => 'El nombre de la cuenta es obligatorio.',
        ]);

        try {
            // Balance is never overwritten on edit — it's managed automatically
            // by income/expense/transfer operations
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

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id'   => 'required|exists:accounts,id|different:from_account_id',
            'amount'          => 'required|numeric|min:0.01',
            'commission'      => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:255',
        ], [
            'from_account_id.required'  => 'Selecciona la cuenta de origen.',
            'to_account_id.required'    => 'Selecciona la cuenta de destino.',
            'to_account_id.different'   => 'Las cuentas deben ser distintas.',
            'amount.required'           => 'Ingresa el monto a transferir.',
            'amount.min'                => 'El monto debe ser mayor a 0.',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $usdRate    = Setting::getUsdRate();
                $from       = Account::findOrFail($validated['from_account_id']);
                $to         = Account::findOrFail($validated['to_account_id']);
                $amount     = (float) $validated['amount'];
                $commission = (float) ($validated['commission'] ?? 0);

                // Deduct amount + commission from source
                $from->balance -= ($amount + $commission);
                $from->balance_usd = $from->currency === 'USD'
                    ? round($from->balance, 2)
                    : round($from->balance / $usdRate, 2);
                $from->save();

                // Convert to destination currency
                if ($from->currency === $to->currency) {
                    $received = $amount;
                } elseif ($from->currency === 'USD') {
                    $received = round($amount * $usdRate, 2);
                } else {
                    $received = round($amount / $usdRate, 2);
                }

                $to->balance += $received;
                $to->balance_usd = $to->currency === 'USD'
                    ? round($to->balance, 2)
                    : round($to->balance / $usdRate, 2);
                $to->save();

                AccountTransfer::create([
                    'from_account_id' => $from->id,
                    'to_account_id'   => $to->id,
                    'amount'          => $amount,
                    'amount_received' => $received,
                    'commission'      => $commission,
                    'notes'           => $validated['notes'] ?? null,
                ]);
            });

            return redirect()->back()->with('success', 'Transferencia realizada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo realizar la transferencia.');
        }
    }
}
