<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\FixedExpense;
use App\Models\Income;
use App\Models\Setting;
use App\Models\SupermarketPurchase;
use App\Models\VariableExpense;
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

    public function show(Request $request, Account $account)
    {
        $usdRate = Setting::getUsdRate();
        $movements = collect();

        // Incomes
        Income::where('account_id', $account->id)->with('user')->get()
            ->each(function ($i) use (&$movements, $usdRate) {
                $amountArs = ($i->currency ?? 'ARS') === 'USD' ? $i->amount * $usdRate : $i->amount;
                $movements->push([
                    'id'          => 'income-' . $i->id,
                    'date'        => $i->date,
                    'description' => $i->source,
                    'type'        => 'income',
                    'label'       => 'Ingreso',
                    'amount'      => (float) $i->amount,
                    'amount_ars'  => round($amountArs, 2),
                    'currency'    => $i->currency ?? 'ARS',
                    'direction'   => '+',
                    'person'      => $i->user?->name,
                    'category'    => null,
                    'necessary'   => null,
                ]);
            });

        // Variable expenses
        VariableExpense::where('account_id', $account->id)->with('user')->get()
            ->each(function ($e) use (&$movements, $usdRate) {
                $amountArs = ($e->currency ?? 'ARS') === 'USD' ? $e->amount * $usdRate : $e->amount;
                $movements->push([
                    'id'          => 'var-' . $e->id,
                    'date'        => $e->date,
                    'description' => $e->description,
                    'type'        => 'variable_expense',
                    'label'       => 'Gasto Variable',
                    'amount'      => (float) $e->amount,
                    'amount_ars'  => round($amountArs, 2),
                    'currency'    => $e->currency ?? 'ARS',
                    'direction'   => '-',
                    'person'      => $e->user?->name,
                    'category'    => $e->category,
                    'necessary'   => $e->is_necessary,
                ]);
            });

        // Fixed expense payments
        DB::table('fixed_expense_payments')
            ->join('fixed_expenses', 'fixed_expenses.id', '=', 'fixed_expense_payments.fixed_expense_id')
            ->join('users', 'users.id', '=', 'fixed_expenses.user_id')
            ->where('fixed_expenses.account_id', $account->id)
            ->where('fixed_expense_payments.paid', true)
            ->select('fixed_expense_payments.*', 'fixed_expenses.name as expense_name',
                     'fixed_expenses.category', 'fixed_expenses.currency',
                     'users.name as user_name')
            ->get()
            ->each(function ($p) use (&$movements, $usdRate) {
                $amountArs = ($p->currency ?? 'ARS') === 'USD' ? $p->amount_paid * $usdRate : $p->amount_paid;
                $movements->push([
                    'id'          => 'fixed-' . $p->id,
                    'date'        => \Carbon\Carbon::create($p->year, $p->month, 1)->toDateString(),
                    'description' => $p->expense_name,
                    'type'        => 'fixed_expense',
                    'label'       => 'Gasto Fijo',
                    'amount'      => (float) $p->amount_paid,
                    'amount_ars'  => round($amountArs, 2),
                    'currency'    => $p->currency ?? 'ARS',
                    'direction'   => '-',
                    'person'      => $p->user_name,
                    'category'    => $p->category,
                    'necessary'   => null,
                ]);
            });

        // Supermarket purchases
        SupermarketPurchase::where('account_id', $account->id)->with('user')->get()
            ->each(function ($s) use (&$movements) {
                $movements->push([
                    'id'          => 'super-' . $s->id,
                    'date'        => $s->date,
                    'description' => $s->store ?? 'Compra supermercado',
                    'type'        => 'supermarket',
                    'label'       => 'Supermercado',
                    'amount'      => (float) $s->total,
                    'amount_ars'  => (float) $s->total,
                    'currency'    => 'ARS',
                    'direction'   => '-',
                    'person'      => $s->user?->name,
                    'category'    => 'Supermercado',
                    'necessary'   => true,
                ]);
            });

        // Transfers out
        AccountTransfer::where('from_account_id', $account->id)->with('toAccount')->get()
            ->each(function ($t) use (&$movements, $account) {
                $movements->push([
                    'id'          => 'transfer-out-' . $t->id,
                    'date'        => $t->transferred_at ?? $t->created_at->toDateString(),
                    'description' => 'Transferencia a ' . ($t->toAccount?->name ?? '?'),
                    'type'        => 'transfer_out',
                    'label'       => 'Transferencia',
                    'amount'      => (float) $t->amount,
                    'amount_ars'  => (float) $t->amount,
                    'currency'    => $account->currency,
                    'direction'   => '-',
                    'person'      => null,
                    'category'    => null,
                    'necessary'   => null,
                ]);
            });

        // Transfers in
        AccountTransfer::where('to_account_id', $account->id)->with('fromAccount')->get()
            ->each(function ($t) use (&$movements, $account) {
                $movements->push([
                    'id'          => 'transfer-in-' . $t->id,
                    'date'        => $t->transferred_at ?? $t->created_at->toDateString(),
                    'description' => 'Transferencia desde ' . ($t->fromAccount?->name ?? '?'),
                    'type'        => 'transfer_in',
                    'label'       => 'Transferencia',
                    'amount'      => (float) $t->amount_received,
                    'amount_ars'  => (float) $t->amount_received,
                    'currency'    => $account->currency,
                    'direction'   => '+',
                    'person'      => null,
                    'category'    => null,
                    'necessary'   => null,
                ]);
            });

        $sorted = $movements->sortByDesc('date')->values();

        return Inertia::render('Accounts/Show', [
            'account'   => $account->load('user'),
            'movements' => $sorted,
            'usdRate'   => $usdRate,
            'summary'   => [
                'total_in'  => round($movements->where('direction', '+')->sum('amount_ars'), 2),
                'total_out' => round($movements->where('direction', '-')->sum('amount_ars'), 2),
            ],
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
